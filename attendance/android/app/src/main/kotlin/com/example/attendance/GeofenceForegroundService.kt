package com.example.attendance

import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.Service
import android.content.Context
import android.content.Intent
import android.os.Build
import android.os.IBinder
import android.content.pm.ServiceInfo
import androidx.core.app.NotificationCompat
import android.util.Log
import java.net.HttpURLConnection
import java.net.URL
import kotlin.concurrent.thread
import java.io.OutputStreamWriter
import java.io.BufferedReader
import java.io.InputStreamReader
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import android.app.AlarmManager
import android.os.Handler
import android.os.Looper
import android.app.PendingIntent
import android.os.SystemClock

import android.location.Location
import android.location.LocationListener
import android.location.LocationManager
import android.os.Bundle

class GeofenceForegroundService : Service(), LocationListener {
    private val TAG = "GeofenceService"
    private val CHANNEL_ID = "geofence_service_channel"
    private val NOTIFICATION_ID = 12345
    
    // Heartbeat for "Self-Correction" (every 2 minutes for testing)
    private val handler = Handler(Looper.getMainLooper())
    private val heartbeatRunnable = object : Runnable {
        override fun run() {
            val prefs = getSharedPreferences("FlutterSharedPreferences", Context.MODE_PRIVATE)
            val globalEnabled = prefs.getString("flutter.global_auto_attendance", "0") == "1"
            
            if (!globalEnabled) {
                Log.d(TAG, "Heartbeat: Auto-attendance disabled. Stopping service.")
                stopSelf()
                return
            }

            // Only perform location check if we might need to punch IN.
            // If already clocked in (last_punch_type='in'), we don't need to ping every 2 mins.
            // The OS geofence 'EXIT' will handle the 'out' punch.
            val lastPunch = prefs.getString("flutter.last_punch_type", "")
            if (lastPunch == "in") {
                Log.d(TAG, "Heartbeat: Already clocked in. Skipping location check.")
                handler.postDelayed(this, 120000)
                return
            }

            Log.d(TAG, "Heartbeat: Performing manual location check for missed IN transitions...")
            try {
                val locationManager = getSystemService(Context.LOCATION_SERVICE) as LocationManager
                if (locationManager.isProviderEnabled(LocationManager.GPS_PROVIDER)) {
                     locationManager.requestSingleUpdate(LocationManager.GPS_PROVIDER, this@GeofenceForegroundService, Looper.getMainLooper())
                } else if (locationManager.isProviderEnabled(LocationManager.NETWORK_PROVIDER)) {
                     locationManager.requestSingleUpdate(LocationManager.NETWORK_PROVIDER, this@GeofenceForegroundService, Looper.getMainLooper())
                }
            } catch (e: Exception) {
                Log.e(TAG, "Heartbeat failed: ${e.message}")
            }
            handler.postDelayed(this, 120000)
        }
    }

    override fun onLocationChanged(location: Location) {
        if (isShiftLocked()) {
            return
        }
        
        if (!isAutoTimeEnabled()) {
            Log.w(TAG, "Auto-attendance blocked: Manual time detected.")
            updateNotification("Blocked: Please enable Automatic Time")
            return
        }

        val isMock = isCurrentLocationMocked(location)
        
        if (isMock) {
            Log.w(TAG, "Heartbeat: Mock location detected. Ignoring.")
            updateNotification("Blocked: Mock software detected")
            return
        }

        Log.d(TAG, "Heartbeat Location: ${location.latitude}, ${location.longitude}")
        
        // 1. Get stored fence
        val prefs = getSharedPreferences("FlutterSharedPreferences", Context.MODE_PRIVATE)
        // Note: We used 'flutter.' prefix in GeofenceManager persistence, so we match it here.
        val fenceLat = prefs.getFloat("flutter.fence_lat", 0.0f).toDouble()
        val fenceLng = prefs.getFloat("flutter.fence_lng", 0.0f).toDouble()
        val fenceRadius = prefs.getFloat("flutter.fence_radius", 0.0f)

        if (fenceLat != 0.0 && fenceLng != 0.0 && fenceRadius > 0) {
            // 2. Calculate Distance
            val results = FloatArray(1)
            Location.distanceBetween(location.latitude, location.longitude, fenceLat, fenceLng, results)
            val distance = results[0]
            
            Log.d(TAG, "Distance to fence: $distance meters (Radius: $fenceRadius)")
            
            // 3. If inside, trigger punch
            if (distance <= fenceRadius) {
                 Log.d(TAG, "User is INSIDE fence (Heartbeat). Triggering auto-punch.")
                 performAutoPunch("in", location.latitude, location.longitude, location.accuracy)
            }
        }
    }
    
    // Required LocationListener overrides
    override fun onStatusChanged(provider: String?, status: Int, extras: Bundle?) {}
    override fun onProviderEnabled(provider: String) {}
    override fun onProviderDisabled(provider: String) {}

    override fun onCreate() {
        super.onCreate()
        Log.d(TAG, "Service onCreate called")
        createNotificationChannel()
        
        // Essential: Re-register fences when service starts/restarts.
        // This handles:
        // 1. App kill/restart scenarios (Xiaomi persistence)
        // 2. "Already in office" cases (via INITIAL_TRIGGER_ENTER in GeofenceManager)
        try {
            val geofenceManager = GeofenceManager(this)
            geofenceManager.reRegisterGeofence()
        } catch (e: Exception) {
            Log.e(TAG, "Error re-registering fences on service create: ${e.message}")
        }
        
        // Start heartbeat
        handler.postDelayed(heartbeatRunnable, 120000)
    }

    override fun onDestroy() {
        handler.removeCallbacks(heartbeatRunnable)
        val locationManager = getSystemService(Context.LOCATION_SERVICE) as LocationManager
        locationManager.removeUpdates(this)
        super.onDestroy()
    }

    private var pendingPunchType: String? = null
    private var pendingLat: Double = 0.0
    private var pendingLng: Double = 0.0
    private var pendingAccuracy: Float = 0.0f
    private var pendingArrivalTime: String? = null

    private val dwellCheckRunnable = object : Runnable {
        override fun run() {
            Log.d(TAG, "Dwell verification timer finished. Checking location...")
            
            val punchType = pendingPunchType ?: return

            try {
                val locationManager = getSystemService(Context.LOCATION_SERVICE) as LocationManager
                val provider = if (locationManager.isProviderEnabled(LocationManager.GPS_PROVIDER)) 
                                LocationManager.GPS_PROVIDER else LocationManager.NETWORK_PROVIDER
                
                // Get fresh location
                val callback = { location: Location? ->
                    if (location != null) {
                        verifyAndPunch(location)
                    } else {
                        Log.e(TAG, "Dwell Check: Failed to get fresh location.")
                        updateNotification("Verification Failed: No GPS signal")
                    }
                }

                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
                    locationManager.getCurrentLocation(provider, null, java.util.concurrent.Executors.newSingleThreadExecutor(), callback)
                } else {
                    @Suppress("DEPRECATION")
                    locationManager.requestSingleUpdate(provider, object : LocationListener {
                        override fun onLocationChanged(location: Location) { callback(location) }
                        override fun onStatusChanged(p: String?, s: Int, e: Bundle?) {}
                        override fun onProviderEnabled(p: String) {}
                        override fun onProviderDisabled(p: String) {}
                    }, Looper.getMainLooper())
                }
            } catch (e: SecurityException) {
                Log.e(TAG, "Dwell check permission error: ${e.message}")
            }
        }
    }

    private fun verifyAndPunch(currentLocation: Location) {
        val type = pendingPunchType ?: return
        
        val isMock = isCurrentLocationMocked(currentLocation)

        if (isMock) {
            Log.w(TAG, "VERIFICATION DENIED: Mock location detected. Blocking $type punch.")
            updateNotification("Blocked: Mock software detected")
            pendingPunchType = null
            return
        }

        // Additional safeguard for OUT: If accuracy is exactly 1.0 or 0.0, many mock apps use this
        if (type == "out" && (currentLocation.accuracy == 1.0f || currentLocation.accuracy == 0.0f)) {
            Log.w(TAG, "SUSPICIOUS: OUT transition with exact integer accuracy (${currentLocation.accuracy}). Likely mock. BLOCKING.")
            updateNotification("Blocked: Suspicious GPS signal")
            pendingPunchType = null
            return
        }

        val prefs = getSharedPreferences("FlutterSharedPreferences", Context.MODE_PRIVATE)
        val fenceLat = prefs.getFloat("flutter.fence_lat", 0.0f).toDouble()
        val fenceLng = prefs.getFloat("flutter.fence_lng", 0.0f).toDouble()
        val fenceRadius = prefs.getFloat("flutter.fence_radius", 0.0f)

        if (fenceLat != 0.0 && fenceLng != 0.0 && fenceRadius > 0) {
            val results = FloatArray(1)
            Location.distanceBetween(currentLocation.latitude, currentLocation.longitude, fenceLat, fenceLng, results)
            val distance = results[0]
            
            // Check Polygon if available
            val polygonJson = prefs.getString("flutter.fence_polygon", "") ?: ""
            var isInsidePolygon = true // Default to true if only circle is used
            if (polygonJson.isNotEmpty()) {
                isInsidePolygon = isPointInPolygon(currentLocation.latitude, currentLocation.longitude, polygonJson)
                Log.d(TAG, "Polygon check result: $isInsidePolygon")
            }

            if (type == "in") {
                if (distance <= fenceRadius && isInsidePolygon) {
                    Log.d(TAG, "Dwell IN PASSED (Inside Polygon/Circle). Punching...")
                    performAutoPunch("in", currentLocation.latitude, currentLocation.longitude, currentLocation.accuracy)
                } else {
                    Log.d(TAG, "Dwell IN FAILED (Outside Polygon/Circle). User left/not inside. Ignoring.")
                    updateNotification("Attendance: Arrival verify failed")
                }
            } else if (type == "out") {
                // For OUT, we trigger if we leave the circle OR leave the polygon
                if (distance > fenceRadius || !isInsidePolygon) {
                    Log.d(TAG, "Dwell OUT PASSED (Outside Polygon/Circle). Punching...")
                    performAutoPunch("out", currentLocation.latitude, currentLocation.longitude, currentLocation.accuracy)
                } else {
                    Log.d(TAG, "Dwell OUT FAILED (Still inside office). Ignoring.")
                    updateNotification("Attendance: Stayed in office")
                }
            }
        }
        pendingPunchType = null
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        Log.d(TAG, "Service onStartCommand called")
        
        val prefs = getSharedPreferences("FlutterSharedPreferences", Context.MODE_PRIVATE)
        val globalEnabled = prefs.getString("flutter.global_auto_attendance", "0") == "1"
        
        if (!globalEnabled) {
            Log.d(TAG, "Geofence trigger or heartbeat ignored: Auto-attendance disabled globally.")
            stopSelf()
            return START_NOT_STICKY
        }

        val punchType = intent?.getStringExtra("punch_type") ?: ""
        val lat = intent?.getDoubleExtra("lat", 0.0) ?: 0.0
        val lng = intent?.getDoubleExtra("lng", 0.0) ?: 0.0
        val accuracy = intent?.getFloatExtra("accuracy", 0.0f) ?: 0.0f

        if (punchType.isNotEmpty()) {
            if (punchType == "in") {
                if (isShiftLocked()) {
                    Log.d(TAG, "Auto-IN ignored: Shift is locked.")
                    return START_STICKY
                }
                
                if (!isAutoTimeEnabled()) {
                    Log.w(TAG, "Auto-IN blocked: Manual time detected.")
                    updateNotification("Blocked: Enable Automatic Time")
                    return START_STICKY
                }

                Log.d(TAG, "Auto-IN detected. Starting 3-minute dwell verification...")
                
                // Store original arrival details
                pendingPunchType = "in"
                pendingLat = lat
                pendingLng = lng
                pendingAccuracy = accuracy
                val sdf = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault())
                pendingArrivalTime = sdf.format(Date()) // Capture exact arrival time
                
                updateNotification("Verifying arrival (Smart Dwell Check)...")
                
                handler.removeCallbacks(dwellCheckRunnable)
                handler.postDelayed(dwellCheckRunnable, 180000) // 3 minutes
                
                val notification = createNotification("Stay 3 mins to auto clock-in")
                startServiceForeground(notification)
            } else if (punchType == "out") {
                Log.d(TAG, "Auto-OUT transition. Starting 1-minute dwell verification...")
                handler.removeCallbacks(dwellCheckRunnable)
                pendingPunchType = "out"
                
                updateNotification("Verifying departure...")
                handler.postDelayed(dwellCheckRunnable, 60000) // 1 minute delay for OUT
                
                val notification = createNotification("Exit detected. Verifying...")
                startServiceForeground(notification)
            }
        } else {
            Log.d(TAG, "Service started for tracking visibility")
            val notification = createNotification("Attendance tracking active")
            startServiceForeground(notification)
        }

        return START_STICKY
    }

    private fun performAutoPunch(type: String, lat: Double, lng: Double, accuracy: Float, specificTime: String? = null, isMocked: Boolean = false) {
        val prefs = getSharedPreferences("FlutterSharedPreferences", Context.MODE_PRIVATE)
        val userId = prefs.getLong("flutter.user_id", 0L).toInt()
        val baseUrl = prefs.getString("flutter.api_base_url", "") ?: ""
        
        if (userId <= 0 || baseUrl.isEmpty()) {
            Log.e(TAG, "Missing user_id or api_base_url.")
            updateNotification("Error: Please re-login")
            return
        }

        thread {
            // Check mock AGAIN just before sending
            val finalMock = isMocked || isCurrentLocationMocked()
            
            if (finalMock) {
                Log.e(TAG, "Blocked auto-punch: Final mock check detected GPS spoofing.")
                updateNotification("Auto-Punch Blocked: Mock Software")
                return@thread
            }

            try {
                val url = URL("${baseUrl}clock.php")
                val conn = url.openConnection() as HttpURLConnection
                conn.requestMethod = "POST"
                conn.doOutput = true
                conn.setRequestProperty("Content-Type", "application/x-www-form-urlencoded")
                conn.connectTimeout = 15000
                conn.readTimeout = 15000

                val deviceId = prefs.getString("flutter.device_id", "native_android") ?: "native_android"
                
                val punchTime = if (specificTime != null) specificTime else {
                    val sdf = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault())
                    sdf.format(Date())
                }

                val reason = if (type == "in") "shift_start" else "shift_end"
                val postData = "user_id=$userId&type=$type&lat=$lat&lng=$lng&device_id=$deviceId&time=$punchTime&is_auto=1&is_mocked=${if(finalMock) 1 else 0}&reason=$reason"
                Log.d(TAG, "Sending auto-punch: $postData")
                
                OutputStreamWriter(conn.outputStream).use { it.write(postData) }

                val responseCode = conn.responseCode
                Log.d(TAG, "API Response Code: $responseCode")

                if (responseCode == 200) {
                    val reader = BufferedReader(InputStreamReader(conn.inputStream))
                    val response = reader.readText()
                    Log.d(TAG, "API Response: $response")
                    
                    if (response.contains("\"status\":\"success\"")) {
                        updateNotification("Auto ${if (type == "in") "Clock-In" else "Clock-Out"} Successful")
                        // Persist to SharedPreferences so heartbeat/UI can see it
                        prefs.edit().putString("flutter.last_punch_type", type).apply()
                    } else {
                        val errorMsg = if (response.contains("\"msg\":\"")) {
                            response.substringAfter("\"msg\":\"").substringBefore("\"")
                        } else "Server rejected"
                        
                        Log.e(TAG, "API Error: $errorMsg")
                        updateNotification("Auto-Punch Failed: $errorMsg")
                    }
                } else {
                    Log.e(TAG, "Server error: $responseCode")
                    updateNotification("Auto-punch failed (Server Error)")
                }
            } catch (e: Exception) {
                Log.e(TAG, "Error during API call: ${e.message}")
                updateNotification("Auto-punch failed (Network Error)")
            } finally {
                thread {
                    Thread.sleep(5000)
                    updateNotification("Attendance tracking active")
                }
                if (type == "in") {
                    pendingPunchType = null
                    pendingArrivalTime = null
                }
            }
        }
    }

    private fun createNotification(text: String): Notification {
        // Use R.mipmap.ic_launcher which is standard for Flutter apps
        val iconId = applicationContext.resources.getIdentifier("ic_launcher", "mipmap", packageName)
        
        return NotificationCompat.Builder(this, CHANNEL_ID)
            .setContentTitle("Attendance System")
            .setContentText(text)
            .setSmallIcon(if (iconId != 0) iconId else android.R.drawable.ic_menu_mylocation)
            .setPriority(NotificationCompat.PRIORITY_LOW)
            .setOngoing(true)
            .build()
    }

    private fun startServiceForeground(notification: Notification) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            startForeground(NOTIFICATION_ID, notification, ServiceInfo.FOREGROUND_SERVICE_TYPE_LOCATION)
        } else {
            startForeground(NOTIFICATION_ID, notification)
        }
    }

    private fun updateNotification(text: String) {
        val notification = createNotification(text)
        val notificationManager = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        notificationManager.notify(NOTIFICATION_ID, notification)
    }

    private fun createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val serviceChannel = NotificationChannel(
                CHANNEL_ID,
                "Attendance Geofence Service",
                NotificationManager.IMPORTANCE_LOW
            )
            val manager = getSystemService(NotificationManager::class.java)
            manager.createNotificationChannel(serviceChannel)
        }
    }

    private fun isCurrentLocationMocked(specificLoc: Location? = null): Boolean {
        val locationManager = getSystemService(Context.LOCATION_SERVICE) as LocationManager
        
        // 1. Check the provided location
        if (specificLoc != null) {
            val isMock = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
                specificLoc.isMock
            } else {
                @Suppress("DEPRECATION")
                specificLoc.isFromMockProvider
            }
            if (isMock) return true
        }

        // 2. Check all available providers for cached mocks
        try {
            val providers = listOf(LocationManager.GPS_PROVIDER, LocationManager.NETWORK_PROVIDER)
            for (provider in providers) {
                val loc = locationManager.getLastKnownLocation(provider)
                if (loc != null) {
                    val isMock = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
                        loc.isMock
                    } else {
                        @Suppress("DEPRECATION")
                        loc.isFromMockProvider
                    }
                    if (isMock) {
                        Log.d(TAG, "isCurrentLocationMocked: Found cached mock on $provider")
                        return true
                    }
                }
            }
        } catch (e: SecurityException) {
            Log.e(TAG, "isCurrentLocationMocked: Security exception: ${e.message}")
        }
        
        return false
    }

    private fun isShiftLocked(): Boolean {
        val prefs = getSharedPreferences("FlutterSharedPreferences", Context.MODE_PRIVATE)
        val lockTimeStr = prefs.getString("flutter.next_allowed_start", null) ?: return false
        
        return try {
            val sdf = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault())
            val lockDate = sdf.parse(lockTimeStr) ?: return false
            val now = Date()
            val isLocked = now.before(lockDate)
            if (isLocked) {
                Log.d(TAG, "Shift is LOCKED until $lockTimeStr")
            }
            isLocked
        } catch (e: Exception) {
            false
        }
    }

    private fun isAutoTimeEnabled(): Boolean {
        val isAutoTime = android.provider.Settings.Global.getInt(contentResolver, android.provider.Settings.Global.AUTO_TIME, 0) == 1
        val isAutoTimeZone = android.provider.Settings.Global.getInt(contentResolver, android.provider.Settings.Global.AUTO_TIME_ZONE, 0) == 1
        return isAutoTime && isAutoTimeZone
    }

    private fun isPointInPolygon(lat: Double, lng: Double, polygonJson: String): Boolean {
        try {
            // Very simple JSON parse for [[lng,lat],[lng,lat]]
            val cleanJson = polygonJson.replace("[", "").replace(" ", "")
            val parts = cleanJson.split("],")
            val polygon = mutableListOf<Pair<Double, Double>>()
            
            for (part in parts) {
                val coords = part.replace("]", "").split(",")
                if (coords.size >= 2) {
                    val pLng = coords[0].toDouble()
                    val pLat = coords[1].toDouble()
                    polygon.add(Pair(pLat, pLng))
                }
            }

            if (polygon.size < 3) return false

            var intersections = 0
            for (i in 0 until polygon.size) {
                val j = (i + 1) % polygon.size
                val vi = polygon[i]
                val vj = polygon[j]

                if (((vi.first > lat) != (vj.first > lat)) &&
                    (lng < (vj.second - vi.second) * (lat - vi.first) / (vj.first - vi.first) + vi.second)
                ) {
                    intersections++
                }
            }
            return (intersections % 2 != 0)
        } catch (e: Exception) {
            Log.e(TAG, "Polygon parse error: ${e.message}")
            return true // Fallback to circle-only if polygon fails
        }
    }

    override fun onBind(intent: Intent?): IBinder? = null

    override fun onTaskRemoved(rootIntent: Intent?) {
        Log.d(TAG, "onTaskRemoved called - attempting service restart")
        
        val restartServiceIntent = Intent(applicationContext, GeofenceForegroundService::class.java).also {
            it.setPackage(packageName)
        }
        val restartServicePendingIntent = PendingIntent.getService(this, 1, restartServiceIntent, PendingIntent.FLAG_ONE_SHOT or PendingIntent.FLAG_IMMUTABLE)
        val alarmService = applicationContext.getSystemService(Context.ALARM_SERVICE) as AlarmManager
        alarmService.set(
            AlarmManager.ELAPSED_REALTIME,
            SystemClock.elapsedRealtime() + 1000,
            restartServicePendingIntent
        )
        super.onTaskRemoved(rootIntent)
    }
}

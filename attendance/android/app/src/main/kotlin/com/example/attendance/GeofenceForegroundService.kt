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
            Log.d(TAG, "Heartbeat: Performing manual location check...")
            try {
                val locationManager = getSystemService(Context.LOCATION_SERVICE) as LocationManager
                // Request a single update from both providers to be safe, prioritizing GPS
                if (locationManager.isProviderEnabled(LocationManager.GPS_PROVIDER)) {
                     locationManager.requestSingleUpdate(LocationManager.GPS_PROVIDER, this@GeofenceForegroundService, Looper.getMainLooper())
                } else if (locationManager.isProviderEnabled(LocationManager.NETWORK_PROVIDER)) {
                     locationManager.requestSingleUpdate(LocationManager.NETWORK_PROVIDER, this@GeofenceForegroundService, Looper.getMainLooper())
                } else {
                    Log.e(TAG, "Heartbeat: No location providers enabled")
                }
            } catch (e: SecurityException) {
                Log.e(TAG, "Heartbeat permission error: ${e.message}")
            } catch (e: Exception) {
                Log.e(TAG, "Heartbeat failed: ${e.message}")
            }
            handler.postDelayed(this, 120000)
        }
    }

    override fun onLocationChanged(location: Location) {
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

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        Log.d(TAG, "Service onStartCommand called")
        val punchType = intent?.getStringExtra("punch_type") ?: ""
        val lat = intent?.getDoubleExtra("lat", 0.0) ?: 0.0
        val lng = intent?.getDoubleExtra("lng", 0.0) ?: 0.0
        val accuracy = intent?.getFloatExtra("accuracy", 0.0f) ?: 0.0f

        if (punchType.isNotEmpty()) {
            Log.d(TAG, "Auto-punch triggered: $punchType at $lat, $lng")
            val notification = createNotification("Processing auto-punch...")
            startServiceForeground(notification)
            performAutoPunch(punchType, lat, lng, accuracy)
        } else {
            Log.d(TAG, "Service started for tracking visibility")
            val notification = createNotification("Attendance tracking active")
            startServiceForeground(notification)
        }

        return START_STICKY
    }

    private fun performAutoPunch(type: String, lat: Double, lng: Double, accuracy: Float) {
        val prefs = getSharedPreferences("FlutterSharedPreferences", Context.MODE_PRIVATE)

        // NativeGeofenceService now saves keys without 'flutter.' prefix (but plugin file name remains)
        // However, standard shared_preferences plugin on Android adds 'flutter.' prefix to keys if using default instance? 
        // No, we are using SharedPreferences.getInstance() in Dart which uses "FlutterSharedPreferences" file and adds "flutter." prefix to keys.
        // BUT my previous edit in NativeGeofenceService REMOVED the manual prefix.
        // Wait, the plugin adds "flutter." prefix automatically to keys.
        // So in Dart: prefs.setInt('user_id', ...) -> Key in XML is "flutter.user_id"
        // So we MUST keep "flutter." prefix here in Kotlin.
        
        // Flutter saves int as Long in SharedPrefs
        val userId = prefs.getLong("flutter.user_id", 0L).toInt()
        val baseUrl = prefs.getString("flutter.api_base_url", "") ?: ""
        
        if (userId <= 0 || baseUrl.isEmpty()) {
            Log.e(TAG, "Missing user_id or api_base_url. UserId: $userId, BaseURL: $baseUrl")
            updateNotification("Error: Please re-login to sync settings")
            return
        }

        thread {
            try {
                val url = URL("${baseUrl}clock.php")
                val conn = url.openConnection() as HttpURLConnection
                conn.requestMethod = "POST"
                conn.doOutput = true
                conn.setRequestProperty("Content-Type", "application/x-www-form-urlencoded")
                conn.connectTimeout = 15000
                conn.readTimeout = 15000

                val deviceId = prefs.getString("flutter.device_id", "native_android") ?: "native_android"
                
                // clock.php requires 'time' parameter
                val sdf = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault())
                val currentTime = sdf.format(Date())

                val postData = "user_id=$userId&type=$type&lat=$lat&lng=$lng&device_id=$deviceId&time=$currentTime&is_auto=1"
                Log.d(TAG, "Sending auto-punch: $postData")
                
                OutputStreamWriter(conn.outputStream).use { it.write(postData) }

                val responseCode = conn.responseCode
                Log.d(TAG, "API Response Code: $responseCode")

                if (responseCode == 200) {
                    val reader = BufferedReader(InputStreamReader(conn.inputStream))
                    val response = reader.readText()
                    Log.d(TAG, "API Response: $response")
                    
                    if (response.contains("\"status\":\"error\"") || response.contains("\"error\"")) {
                        Log.e(TAG, "API returned application error: $response")
                        updateNotification("Auto-punch Error: Server rejected")
                    } else {
                        updateNotification("Auto ${if (type == "in") "Clock-In" else "Clock-Out"} Successful")
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

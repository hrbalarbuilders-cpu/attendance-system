package com.example.attendance

import android.content.Intent
import io.flutter.embedding.android.FlutterActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel

class MainActivity : FlutterActivity() {
    private val CHANNEL = "com.example.attendance/geofence"
    private lateinit var geofenceManager: GeofenceManager

    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)
        geofenceManager = GeofenceManager(this)

        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, CHANNEL).setMethodCallHandler { call, result ->
            when (call.method) {
                "startGeofence" -> {
                    val id = call.argument<String>("id") ?: "default"
                    val lat = call.argument<Double>("lat") ?: 0.0
                    val lng = call.argument<Double>("lng") ?: 0.0
                    val radius = call.argument<Double>("radius")?.toFloat() ?: 100f
                    
                    // 1. Register Geofence with OS
                    geofenceManager.startGeofence(id, lat, lng, radius)
                    
                    // 2. Start Foreground Service to show persistent notification
                    val serviceIntent = Intent(this, GeofenceForegroundService::class.java)
                    if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.O) {
                        startForegroundService(serviceIntent)
                    } else {
                        startService(serviceIntent)
                    }
                    
                    result.success(true)
                }
                "stopGeofence" -> {
                    geofenceManager.stopGeofence()
                    
                    // Stop the foreground service
                    val serviceIntent = Intent(this, GeofenceForegroundService::class.java)
                    stopService(serviceIntent)
                    
                    result.success(true)
                }
                else -> {
                    result.notImplemented()
                }
            }
        }
    }
}

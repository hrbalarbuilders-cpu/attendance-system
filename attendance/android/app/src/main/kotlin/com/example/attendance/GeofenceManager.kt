package com.example.attendance

import android.annotation.SuppressLint
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.os.Build
import android.util.Log
import com.google.android.gms.location.Geofence
import com.google.android.gms.location.GeofencingClient
import com.google.android.gms.location.GeofencingRequest
import com.google.android.gms.location.LocationServices

class GeofenceManager(private val context: Context) {
    private val TAG = "GeofenceManager"
    private val geofencingClient: GeofencingClient = LocationServices.getGeofencingClient(context)

    private val geofencePendingIntent: PendingIntent by lazy {
        val intent = Intent(context, GeofenceBroadcastReceiver::class.java)
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            PendingIntent.getBroadcast(context, 0, intent, PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_MUTABLE)
        } else {
            PendingIntent.getBroadcast(context, 0, intent, PendingIntent.FLAG_UPDATE_CURRENT)
        }
    }

    @SuppressLint("MissingPermission")
    fun startGeofence(id: String, lat: Double, lng: Double, radius: Float, polygon: String) {
        // Persist fence details for BootReceiver
        val prefs = context.getSharedPreferences("FlutterSharedPreferences", Context.MODE_PRIVATE)
        prefs.edit().apply {
            putString("flutter.fence_id", id)
            putFloat("flutter.fence_lat", lat.toFloat())
            putFloat("flutter.fence_lng", lng.toFloat())
            putFloat("flutter.fence_radius", radius)
            putString("flutter.fence_polygon", polygon)
            apply()
        }

        val geofence = Geofence.Builder()
            .setRequestId(id)
            .setCircularRegion(lat, lng, radius)
            .setExpirationDuration(Geofence.NEVER_EXPIRE)
            .setTransitionTypes(Geofence.GEOFENCE_TRANSITION_ENTER or Geofence.GEOFENCE_TRANSITION_EXIT)
            .build()

        val request = GeofencingRequest.Builder()
            .setInitialTrigger(GeofencingRequest.INITIAL_TRIGGER_ENTER)
            .addGeofence(geofence)
            .build()

        geofencingClient.addGeofences(request, geofencePendingIntent).run {
            addOnSuccessListener {
                Log.d(TAG, "Geofence added successfully: $id")
            }
            addOnFailureListener {
                Log.e(TAG, "Failed to add geofence: ${it.message}")
            }
        }
    }

    @SuppressLint("MissingPermission")
    fun reRegisterGeofence() {
        val prefs = context.getSharedPreferences("FlutterSharedPreferences", Context.MODE_PRIVATE)
        val id = prefs.getString("flutter.fence_id", null)
        val lat = prefs.getFloat("flutter.fence_lat", 0.0f).toDouble()
        val lng = prefs.getFloat("flutter.fence_lng", 0.0f).toDouble()
        val radius = prefs.getFloat("flutter.fence_radius", 0.0f)

        if (id != null && lat != 0.0 && lng != 0.0 && radius > 0) {
            val polygon = prefs.getString("flutter.fence_polygon", "") ?: ""
            Log.d(TAG, "Re-registering persisted geofence: $id with INITIAL_TRIGGER_ENTER")
            // This calls startGeofence, which sets INITIAL_TRIGGER_ENTER in the request builder
            startGeofence(id, lat, lng, radius, polygon)
        } else {
            Log.d(TAG, "No persisted geofence found to re-register")
        }
    }

    fun stopGeofence() {
        // Clear persisted fence details
        val prefs = context.getSharedPreferences("FlutterSharedPreferences", Context.MODE_PRIVATE)
        prefs.edit().apply {
            remove("flutter.fence_id")
            remove("flutter.fence_lat")
            remove("flutter.fence_lng")
            remove("flutter.fence_radius")
            apply()
        }

        geofencingClient.removeGeofences(geofencePendingIntent).run {
            addOnSuccessListener {
                Log.d(TAG, "Geofences removed successfully")
            }
            addOnFailureListener {
                Log.e(TAG, "Failed to remove geofences: ${it.message}")
            }
        }
    }
}

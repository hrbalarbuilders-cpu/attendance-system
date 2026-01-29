package com.example.attendance

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.util.Log
import com.google.android.gms.location.Geofence
import com.google.android.gms.location.GeofencingEvent
import android.location.LocationManager
import android.os.Build

class GeofenceBroadcastReceiver : BroadcastReceiver() {
    private val TAG = "GeofenceReceiver"

    override fun onReceive(context: Context, intent: Intent) {
        val geofencingEvent = GeofencingEvent.fromIntent(intent)
        if (geofencingEvent == null || geofencingEvent.hasError()) {
            Log.e(TAG, "GeofencingEvent error: ${geofencingEvent?.errorCode}")
            return
        }

        val transition = geofencingEvent.geofenceTransition
        if (transition == Geofence.GEOFENCE_TRANSITION_ENTER || transition == Geofence.GEOFENCE_TRANSITION_EXIT) {
            val type = if (transition == Geofence.GEOFENCE_TRANSITION_ENTER) "in" else "out"
            Log.d(TAG, "Geofence transition detected: $type")

            val location = geofencingEvent.triggeringLocation
            
            Log.d(TAG, "Transition $type - Accuracy: ${location?.accuracy}, Provider: ${location?.provider}")

            // BLOCK MOCK LOCATION - STAGE 1: BROADCAST RECEIVER
            if (location != null) {
                val isMock = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
                    location.isMock
                } else {
                    @Suppress("DEPRECATION")
                    location.isFromMockProvider
                }
                
                if (isMock) {
                    Log.w(TAG, "CRITICAL: Mock location detected during $type transition. BLOCKING.")
                    return
                }
            } else {
                // IMPORTANT: If location is NULL and it's an OUT transition, it's often a provider switch (mock toggle)
                if (type == "out") {
                    Log.w(TAG, "SUSPICIOUS: OUT transition with NULL location. Likely mock-toggle. BLOCKING.")
                    return
                }
                Log.w(TAG, "Triggering location is NULL for IN transition. Will verify in service.")
            }

            val serviceIntent = Intent(context, GeofenceForegroundService::class.java)
            serviceIntent.putExtra("punch_type", type)
            serviceIntent.putExtra("is_mock_trigger", false) // Since we already filtered mocks above
            
            if (location != null) {
                serviceIntent.putExtra("lat", location.latitude)
                serviceIntent.putExtra("lng", location.longitude)
                serviceIntent.putExtra("accuracy", location.accuracy)
            }

            if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.O) {
                context.startForegroundService(serviceIntent)
            } else {
                context.startService(serviceIntent)
            }
        }
    }
}

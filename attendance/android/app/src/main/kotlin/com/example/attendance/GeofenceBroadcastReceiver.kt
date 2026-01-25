package com.example.attendance

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.util.Log
import com.google.android.gms.location.Geofence
import com.google.android.gms.location.GeofencingEvent

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

            val serviceIntent = Intent(context, GeofenceForegroundService::class.java)
            serviceIntent.putExtra("punch_type", type)
            
            val location = geofencingEvent.triggeringLocation
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

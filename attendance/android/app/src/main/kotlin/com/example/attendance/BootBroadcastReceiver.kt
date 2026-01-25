package com.example.attendance

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.util.Log

class BootBroadcastReceiver : BroadcastReceiver() {
    private val TAG = "BootReceiver"

    override fun onReceive(context: Context, intent: Intent) {
        if (intent.action == Intent.ACTION_BOOT_COMPLETED || 
            intent.action == "android.intent.action.QUICKBOOT_POWERON" || 
            intent.action == "com.htc.intent.action.QUICKBOOT_POWERON") {
                
            Log.d(TAG, "Boot completed detected. Restoring geofences...")
            val geofenceManager = GeofenceManager(context)
            geofenceManager.reRegisterGeofence()
        }
    }
}

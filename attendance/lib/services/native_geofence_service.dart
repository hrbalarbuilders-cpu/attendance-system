import 'package:flutter/services.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:async';
import '../config.dart';
import 'location_service.dart' as app_location;

class NativeGeofenceService {
  static const MethodChannel _channel = MethodChannel(
    'com.example.attendance/geofence',
  );

  static final NativeGeofenceService _instance =
      NativeGeofenceService._internal();
  factory NativeGeofenceService() => _instance;
  NativeGeofenceService._internal();

  bool _isActive = false;
  bool get isActive => _isActive;

  /// Start native geofencing
  Future<void> initialize() async {
    print('NativeGeofenceService: Initializing...');
    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getInt('user_id') ?? 0;

    if (userId <= 0) {
      print('NativeGeofenceService: No user logged in. Skipping.');
      return;
    }

    // Sync settings to native-readable keys (Important: shared_preferences adds 'flutter.' prefix automatically)
    print('NativeGeofenceService: Syncing settings for user $userId');
    await prefs.setInt('user_id', userId);
    await prefs.setString('api_base_url', kBaseUrl);

    final deviceId = await app_location.LocationService.getDeviceId();
    await prefs.setString('device_id', deviceId);

    // Fetch primary geofence from backend
    print('NativeGeofenceService: Fetching geofences...');
    final fences = await app_location.LocationService.getGeoFences(
      baseUri: kBaseUri,
    );
    if (fences.isEmpty) {
      print('NativeGeofenceService: No geofences found on server.');
      return;
    }

    final fence = fences.first;
    print(
      'NativeGeofenceService: Registering fence: ${fence.name} (${fence.lat}, ${fence.lng})',
    );

    try {
      final bool success = await _channel.invokeMethod('startGeofence', {
        'id': 'office_fence_${fence.id}',
        'lat': fence.lat,
        'lng': fence.lng,
        'radius': fence.radiusMeters,
        'polygon': fence.polygon ?? '',
      });
      _isActive = success;
      print('NativeGeofenceService: System call success: $success');
    } catch (e) {
      print('NativeGeofenceService: Failed to start native geofence: $e');
    }
  }

  /// Update the earliest allowed time for auto-attendance
  Future<void> updateNextAllowedStart(String? timestamp) async {
    final prefs = await SharedPreferences.getInstance();
    if (timestamp != null) {
      await prefs.setString('next_allowed_start', timestamp);
      print('NativeGeofenceService: Lock updated to $timestamp');
    } else {
      await prefs.remove('next_allowed_start');
      print('NativeGeofenceService: Lock cleared');
    }
  }

  /// Check if automatic time and time zone are enabled
  Future<bool> isAutoTimeEnabled() async {
    try {
      final bool? isAuto = await _channel.invokeMethod('isAutoTimeEnabled');
      return isAuto ?? false;
    } catch (e) {
      print('Failed to check auto time: $e');
      return true; // Fallback to true so we don't block users if plugin fails
    }
  }

  /// Stop native geofencing
  Future<void> stop() async {
    try {
      await _channel.invokeMethod('stopGeofence');
      _isActive = false;
      print('Native Geofence stopped');
    } catch (e) {
      print('Failed to stop native geofence: $e');
    }
  }
}

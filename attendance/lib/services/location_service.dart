import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'dart:math' as math;
import 'package:geolocator/geolocator.dart';
import 'package:device_info_plus/device_info_plus.dart';
import 'package:http/http.dart' as http;
import 'package:permission_handler/permission_handler.dart' as ph;

/// Represents a geo-fence location from the server.
class GeoFence {
  final int id;
  final String group;
  final String name;
  final double lat;
  final double lng;
  final double radiusMeters;

  GeoFence({
    required this.id,
    required this.group,
    required this.name,
    required this.lat,
    required this.lng,
    required this.radiusMeters,
  });

  factory GeoFence.fromJson(Map<String, dynamic> json) {
    return GeoFence(
      id: json['id'] is int
          ? json['id']
          : int.tryParse(json['id'].toString()) ?? 0,
      group: (json['group'] ?? json['location_group'] ?? '').toString(),
      name: (json['name'] ?? json['location_name'] ?? '').toString(),
      lat: _parseDouble(json['lat'] ?? json['latitude']),
      lng: _parseDouble(json['lng'] ?? json['longitude']),
      radiusMeters: _parseDouble(
        json['radius'] ?? json['radius_meters'] ?? 100,
      ),
    );
  }

  static double _parseDouble(dynamic value) {
    if (value is double) return value;
    if (value is int) return value.toDouble();
    if (value is String) return double.tryParse(value) ?? 0.0;
    return 0.0;
  }
}

/// Result of geo-fence validation.
class GeoFenceResult {
  final bool isWithinFence;
  final double? userLat;
  final double? userLng;
  final String? nearestFenceName;
  final double? distanceToNearest;
  final String? errorMessage;

  GeoFenceResult({
    required this.isWithinFence,
    this.userLat,
    this.userLng,
    this.nearestFenceName,
    this.distanceToNearest,
    this.errorMessage,
  });
}

class LocationService {
  /// Get the unique device ID for this device.
  /// Returns a persistent identifier that can be used to lock an employee to a device.
  static Future<String> getDeviceId() async {
    final deviceInfo = DeviceInfoPlugin();

    try {
      if (Platform.isAndroid) {
        final androidInfo = await deviceInfo.androidInfo;
        // Use Android ID which is unique per app installation
        return androidInfo.id;
      } else if (Platform.isIOS) {
        final iosInfo = await deviceInfo.iosInfo;
        // Use identifierForVendor which is unique per vendor per device
        return iosInfo.identifierForVendor ?? 'ios_unknown';
      } else if (Platform.isWindows) {
        final windowsInfo = await deviceInfo.windowsInfo;
        return windowsInfo.deviceId;
      } else if (Platform.isLinux) {
        final linuxInfo = await deviceInfo.linuxInfo;
        return linuxInfo.machineId ?? 'linux_unknown';
      } else if (Platform.isMacOS) {
        final macInfo = await deviceInfo.macOsInfo;
        return macInfo.systemGUID ?? 'macos_unknown';
      }
    } catch (e) {
      // Fallback if device info fails
      return 'flutter_device_${DateTime.now().millisecondsSinceEpoch}';
    }

    return 'flutter_device';
  }

  /// Check if location services are enabled and permission is granted.
  /// Returns null if everything is OK, or an error message if not.
  static Future<String?> checkLocationPermission({
    bool requestBackground = false,
  }) async {
    bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      return 'Location services are disabled. Please enable GPS.';
    }

    // Standard Location Permission (Foreground)
    LocationPermission permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        return 'Location permission denied. Please allow location access.';
      }
    }

    if (permission == LocationPermission.deniedForever) {
      return 'Location permission permanently denied. Please enable in settings.';
    }

    // Additional permissions for Background Geofencing (Android 10+)
    if (requestBackground) {
      // 1. Activity Recognition (Mandatory for some devices to trigger geofences reliably)
      final activityStatus = await ph.Permission.activityRecognition.request();
      if (activityStatus.isDenied) {
        return 'Activity recognition is required for geofencing.';
      }

      // 2. Notifications (Android 13+)
      if (Platform.isAndroid) {
        await ph.Permission.notification.request();
      }

      // 3. Background Location (Always)
      if (permission != LocationPermission.always) {
        await Future.delayed(const Duration(milliseconds: 500));
        permission = await Geolocator.requestPermission();
        if (permission != LocationPermission.always) {
          return 'BACKGROUND_PERMISSION_NEED_MANUAL';
        }
      }
    }

    return null; // All OK
  }

  /// Force request of all permissions required for the app to function.
  /// Returns null if all granted, otherwise returns error message.
  static Future<String?> ensureAllPermissions() async {
    // 1. Basic Location
    final locStatus = await Geolocator.checkPermission();
    if (locStatus == LocationPermission.denied) {
      await Geolocator.requestPermission();
    }

    // 2. Notifications
    await ph.Permission.notification.request();

    // 3. Activity Recognition
    await ph.Permission.activityRecognition.request();

    // 4. Background Location (Step 2 of location)
    if (await Geolocator.checkPermission() != LocationPermission.always) {
      await Geolocator.requestPermission();
    }

    // Final check
    final finalLoc = await Geolocator.checkPermission();
    final finalActivity = await ph.Permission.activityRecognition.status;

    if (finalLoc != LocationPermission.always)
      return 'Location (Set to Always Allow)';
    if (!finalActivity.isGranted) return 'Physical Activity Recognition';

    return null;
  }

  /// Check specifically if background location permission (Always) is granted.
  static Future<bool> isBackgroundPermissionGranted() async {
    final permission = await Geolocator.checkPermission();
    return permission == LocationPermission.always;
  }

  /// Get the current GPS coordinates.
  static Future<Position?> getCurrentPosition({
    Duration timeout = const Duration(seconds: 15),
  }) async {
    try {
      return await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
        timeLimit: timeout,
      );
    } catch (e) {
      return null;
    }
  }

  /// Fetch all active geo-fence locations from the API.
  static Future<List<GeoFence>> getGeoFences({
    required Uri baseUri,
    Duration timeout = const Duration(seconds: 10),
  }) async {
    final url = Uri.parse('${baseUri.toString()}get_geo_fences.php');

    try {
      final response = await http.get(url).timeout(timeout);
      final data = json.decode(response.body);

      if (data is Map && data['status'] == 'success' && data['data'] is List) {
        return (data['data'] as List)
            .map((item) => GeoFence.fromJson(item as Map<String, dynamic>))
            .toList();
      }
      return [];
    } catch (e) {
      return [];
    }
  }

  /// Calculate distance between two coordinates using Haversine formula.
  /// Returns distance in meters.
  static double calculateDistance(
    double lat1,
    double lng1,
    double lat2,
    double lng2,
  ) {
    const double earthRadius = 6371000; // meters

    final dLat = _toRadians(lat2 - lat1);
    final dLng = _toRadians(lng2 - lng1);

    final a =
        math.sin(dLat / 2) * math.sin(dLat / 2) +
        math.cos(_toRadians(lat1)) *
            math.cos(_toRadians(lat2)) *
            math.sin(dLng / 2) *
            math.sin(dLng / 2);

    final c = 2 * math.atan2(math.sqrt(a), math.sqrt(1 - a));

    return earthRadius * c;
  }

  static double _toRadians(double degrees) {
    return degrees * (math.pi / 180);
  }

  /// Validate if the user is within any of the geo-fence locations.
  /// Returns a GeoFenceResult indicating success/failure and details.
  static Future<GeoFenceResult> validateGeoFence({
    required Uri baseUri,
    Duration timeout = const Duration(seconds: 15),
  }) async {
    // Step 1: Check permissions
    final permError = await checkLocationPermission();
    if (permError != null) {
      return GeoFenceResult(isWithinFence: false, errorMessage: permError);
    }

    // Step 2: Get current position
    final position = await getCurrentPosition(timeout: timeout);
    if (position == null) {
      return GeoFenceResult(
        isWithinFence: false,
        errorMessage: 'Unable to get your location. Please try again.',
      );
    }

    // Step 3: Fetch geo-fences
    final fences = await getGeoFences(baseUri: baseUri);
    if (fences.isEmpty) {
      // No geo-fences configured - allow clock in/out
      return GeoFenceResult(
        isWithinFence: true,
        userLat: position.latitude,
        userLng: position.longitude,
        nearestFenceName: 'No restriction',
        distanceToNearest: 0,
      );
    }

    // Step 4: Check if within any fence
    double? nearestDistance;
    GeoFence? nearestFence;

    for (final fence in fences) {
      final distance = calculateDistance(
        position.latitude,
        position.longitude,
        fence.lat,
        fence.lng,
      );

      if (nearestDistance == null || distance < nearestDistance) {
        nearestDistance = distance;
        nearestFence = fence;
      }

      // Check if within this fence's radius
      if (distance <= fence.radiusMeters) {
        return GeoFenceResult(
          isWithinFence: true,
          userLat: position.latitude,
          userLng: position.longitude,
          nearestFenceName: '${fence.group} - ${fence.name}',
          distanceToNearest: distance,
        );
      }
    }

    // Not within any fence
    return GeoFenceResult(
      isWithinFence: false,
      userLat: position.latitude,
      userLng: position.longitude,
      nearestFenceName: nearestFence != null
          ? '${nearestFence.group} - ${nearestFence.name}'
          : null,
      distanceToNearest: nearestDistance,
      errorMessage:
          'You are ${nearestDistance?.toStringAsFixed(0)}m away from the allowed area. Please move closer to clock in/out.',
    );
  }
}

import 'dart:async';
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:http/http.dart' as http;
import '../config.dart';
import 'location_service.dart' as app_location;
import 'native_geofence_service.dart';

class AppGeofenceService {
  static final AppGeofenceService _instance = AppGeofenceService._internal();
  factory AppGeofenceService() => _instance;
  AppGeofenceService._internal();

  final StreamController<String> _eventController =
      StreamController<String>.broadcast();
  Stream<String> get eventStream => _eventController.stream;

  final StreamController<String> _statusController =
      StreamController<String>.broadcast();
  Stream<String> get statusStream => _statusController.stream;

  static Stream<String> get autoAttendanceEvents => _instance.eventStream;
  static Stream<String> get serviceStatus => _instance.statusStream;

  bool get isRunning => NativeGeofenceService().isActive;

  /// Entry point to start geofencing if enabled by Admin
  Future<void> initialize() async {
    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getInt('user_id') ?? 0;
    if (userId <= 0) {
      print('AppGeofenceService: No user found, skipping init.');
      return;
    }

    _statusController.add('checking_settings');

    // 1. Check Admin Settings
    final settings = await _fetchAdminSettings();
    final isAutoEnabled = settings['global_auto_attendance'] == '1';

    if (!isAutoEnabled) {
      _statusController.add('disabled_by_admin');
      return;
    }

    _statusController.add('requesting_permissions');

    // 2. Request All Permissions (Mandatory for Android 15 consistency)
    final missing = await app_location.LocationService.ensureAllPermissions();
    if (missing != null) {
      if (missing.contains('Location')) {
        _statusController.add('BACKGROUND_PERMISSION_NEED_MANUAL');
      } else {
        _statusController.add('permission_denied');
      }
      print('Auto-attendance permission missing: $missing');
      return;
    }

    _statusController.add('starting_service');

    // 3. Start Native Service
    await NativeGeofenceService().initialize();

    if (NativeGeofenceService().isActive) {
      _statusController.add('active');
    } else {
      _statusController.add('error');
    }
  }

  /// Stop geofencing service
  Future<void> stop() async {
    await NativeGeofenceService().stop();
    _statusController.add('stopped');
  }

  Future<Map<String, dynamic>> _fetchAdminSettings() async {
    try {
      final res = await http
          .get(Uri.parse('${kBaseUrl}get_attendance_settings.php'))
          .timeout(const Duration(seconds: 5));
      final data = json.decode(res.body);
      if (data['success'] == true) return data['settings'];
    } catch (_) {}
    return {'global_auto_attendance': '0'};
  }
}

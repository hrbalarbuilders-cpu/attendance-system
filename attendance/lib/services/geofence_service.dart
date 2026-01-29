import 'dart:async';
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:http/http.dart' as http;
import 'package:geolocator/geolocator.dart';
import '../config.dart';
import 'location_service.dart' as app_location;
import 'native_geofence_service.dart';

class AppGeofenceService {
  static final AppGeofenceService _instance = AppGeofenceService._internal();
  factory AppGeofenceService() => _instance;

  String _currentStatus = 'initializing';
  String get currentStatus => _currentStatus;
  DateTime? _lastFetchTime;
  static const _fetchThrottle = Duration(minutes: 5);

  AppGeofenceService._internal() {
    // Listen to GPS toggle events globally
    Geolocator.getServiceStatusStream().listen((ServiceStatus status) {
      if (status == ServiceStatus.disabled) {
        _updateStatus('location_services_disabled');
      } else {
        if (NativeGeofenceService().isActive) {
          _updateStatus('active');
        } else {
          initialize();
        }
      }
    });
  }

  final StreamController<String> _eventController =
      StreamController<String>.broadcast();
  Stream<String> get eventStream => _eventController.stream;

  final StreamController<String> _statusController =
      StreamController<String>.broadcast();
  Stream<String> get statusStream => _statusController.stream;

  static Stream<String> get autoAttendanceEvents => _instance.eventStream;
  static Stream<String> get serviceStatus => _instance.statusStream;

  void _updateStatus(String status) {
    if (_currentStatus != status) {
      _currentStatus = status;
      _statusController.add(status);
    }
  }

  /// Entry point to start geofencing if enabled by Admin
  Future<void> initialize() async {
    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getInt('user_id') ?? 0;
    if (userId <= 0) return;

    _updateStatus('checking_settings');

    bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      _updateStatus('location_services_disabled');
      return;
    }

    final settings = await _fetchAdminSettings();
    final String globalAuto = settings['global_auto_attendance'] ?? '0';

    // Sync to native-readable key (plugin adds 'flutter.' prefix automatically)
    await prefs.setString('global_auto_attendance', globalAuto);

    if (globalAuto != '1') {
      await NativeGeofenceService().stop();
      _updateStatus('disabled_by_admin');
      return;
    }

    _updateStatus('requesting_permissions');
    final missing = await app_location.LocationService.ensureAllPermissions();
    if (missing != null) {
      _updateStatus(
        missing.contains('Location')
            ? 'BACKGROUND_PERMISSION_NEED_MANUAL'
            : 'permission_denied',
      );
      return;
    }

    _updateStatus('starting_service');
    await NativeGeofenceService().initialize();

    if (NativeGeofenceService().isActive) {
      _updateStatus('active');
    } else {
      _updateStatus('error');
    }
  }

  Future<void> stop() async {
    await NativeGeofenceService().stop();
    _updateStatus('disabled_by_user');
  }

  Future<Map<String, dynamic>> _fetchAdminSettings() async {
    final now = DateTime.now();
    if (_lastFetchTime != null &&
        now.difference(_lastFetchTime!) < _fetchThrottle) {
      final prefs = await SharedPreferences.getInstance();
      final lastVal = prefs.getString('global_auto_attendance') ?? '0';
      return {'success': true, 'global_auto_attendance': lastVal};
    }

    try {
      final res = await http
          .get(Uri.parse('${kBaseUrl}get_attendance_settings.php'))
          .timeout(const Duration(seconds: 5));
      final data = json.decode(res.body);
      if (data['success'] == true) {
        _lastFetchTime = now;
        return data['settings'];
      }
    } catch (_) {}
    return {'global_auto_attendance': '0'};
  }
}

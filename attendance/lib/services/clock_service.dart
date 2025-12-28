import 'dart:async';
import 'dart:convert';
import 'package:http/http.dart' as http;

class ClockResult {
  final bool success;
  final String message;
  final bool isClockedIn;

  ClockResult({required this.success, required this.message, required this.isClockedIn});
}

class ShiftInfo {
  final bool success;
  final String name;
  final String workingFrom;
  final String start; // expected format HH:mm or HH:mm:ss
  final String end;
  final String raw;

  ShiftInfo({required this.success, required this.name, required this.workingFrom, required this.start, required this.end, this.raw = ''});
}

class ClockService {
  /// Perform clock in/out request.
  /// [baseUri] should be the API base URI (e.g., kBaseUri from config).
  static Future<ClockResult> clockInOut({
    required Uri baseUri,
    required int userId,
    required String type,
    Duration timeout = const Duration(seconds: 10),
  }) async {
    if (userId <= 0) {
      return ClockResult(success: false, message: 'User not found.', isClockedIn: false);
    }

    final url = baseUri.resolve('clock.php');
    final now = DateTime.now();
    final deviceId = 'flutter_device';
    final body = {
      'user_id': userId.toString(),
      'type': type,
      'time': now.toIso8601String(),
      'device_id': deviceId,
      'lat': '',
      'lng': '',
      'working_from': '',
      'reason': type == 'in' ? 'shift_start' : 'shift_end',
    };

    try {
      final response = await http.post(url, body: body).timeout(timeout);
      final data = json.decode(response.body);

      if (data is Map && data['status'] == 'success') {
        return ClockResult(
          success: true,
          message: type == 'in' ? 'Clocked in successfully!' : 'Clocked out successfully!',
          isClockedIn: type == 'in',
        );
      }

      final msg = (data is Map && data['msg'] is String) ? data['msg'] as String : 'Error occurred.';
      return ClockResult(success: false, message: msg, isClockedIn: false);
    } on TimeoutException {
      return ClockResult(success: false, message: 'Request timed out. Please try again.', isClockedIn: false);
    } catch (e) {
      return ClockResult(success: false, message: 'Network error: ${e.toString()}', isClockedIn: false);
    }
  }

  /// Fetch shift info for a user from the API.
  /// Expects an endpoint `get_user_shift.php` that accepts `user_id` and returns JSON like:
  /// { status: 'success', data: { name: 'Main Office', working_from: 'Ville Flora', start: '10:00', end: '19:30' } }
  static Future<ShiftInfo> getUserShift({
    required Uri baseUri,
    required int userId,
    Duration timeout = const Duration(seconds: 10),
  }) async {
    if (userId <= 0) {
      return ShiftInfo(success: false, name: '', workingFrom: '', start: '', end: '');
    }

    // Build a full URL like: http://<host>/attendance/attendance_api/get_user_shift.php?user_id=123
    final url = Uri.parse('${baseUri.toString()}get_user_shift.php?user_id=${userId.toString()}');
    try {
      final response = await http.get(url).timeout(timeout);
      final data = json.decode(response.body);
      if (data is Map && data['status'] == 'success') {
        final name = data['shift_name'] is String ? data['shift_name'] as String : '';
        final start = data['start_time'] is String ? data['start_time'] as String : '';
        final end = data['end_time'] is String ? data['end_time'] as String : '';
        // Use `working_from` from API; do not fallback to shift name.
        final workingFrom = data['working_from'] is String ? data['working_from'] as String : '';
        return ShiftInfo(success: true, name: name, workingFrom: workingFrom, start: start, end: end, raw: response.body);
      }
      return ShiftInfo(success: false, name: '', workingFrom: '', start: '', end: '', raw: response.body);
    } on TimeoutException {
      return ShiftInfo(success: false, name: '', workingFrom: '', start: '', end: '', raw: '');
    } catch (e) {
      return ShiftInfo(success: false, name: '', workingFrom: '', start: '', end: '', raw: '');
    }
  }

  /// Fetch day summary (gross/effective/break/late minutes) for a user on a date (YYYY-MM-DD)
  static Future<Map<String, dynamic>> getDaySummary({
    required Uri baseUri,
    required int userId,
    required String date,
    Duration timeout = const Duration(seconds: 10),
  }) async {
    if (userId <= 0 || date.isEmpty) {
      return {'success': false};
    }

    final url = Uri.parse('${baseUri.toString()}get_day_summary.php?user_id=${userId.toString()}&date=${Uri.encodeComponent(date)}');
    try {
      final response = await http.get(url).timeout(timeout);
      final data = json.decode(response.body);
      if (data is Map && data['status'] == 'success' && data['data'] is Map) {
        return {'success': true, 'data': data['data']};
      }
      return {'success': false};
    } on TimeoutException {
      return {'success': false, 'error': 'timeout'};
    } catch (e) {
      return {'success': false, 'error': e.toString()};
    }
  }

  /// Fetch detailed attendance logs for a user on a date (YYYY-MM-DD)
  /// Calls `get_today_attendance.php` which returns logs and summary fields.
  static Future<Map<String, dynamic>> getDayAttendance({
    required Uri baseUri,
    required int userId,
    required String date,
    Duration timeout = const Duration(seconds: 10),
  }) async {
    if (userId <= 0 || date.isEmpty) return {'success': false};

    final url = Uri.parse('${baseUri.toString()}get_today_attendance.php?user_id=${userId.toString()}&date=${Uri.encodeComponent(date)}');
    try {
      final response = await http.get(url).timeout(timeout);
      final data = json.decode(response.body);
      if (data is Map && data['status'] == 'success') {
        return {
          'success': true,
          'clock_in': data['clock_in'],
          'clock_out': data['clock_out'],
          'total_punches_today': data['total_punches_today'],
          'last_punch_type': data['last_punch_type'],
          'logs': data['logs'],
          'gross_minutes': data['gross_minutes'],
          'effective_minutes': data['effective_minutes'],
          'break_minutes': data['break_minutes'],
          'late_minutes': data['late_minutes'],
        };
      }
      return {'success': false, 'raw': data};
    } on TimeoutException {
      return {'success': false, 'error': 'timeout'};
    } catch (e) {
      return {'success': false, 'error': e.toString()};
    }
  }

  /// Fetch upcoming wishes (birthdays / anniversaries) for the next [days] days.
  /// Returns a Map with 'success' and 'data' as a List of events.
  /// Each event: { id, name, type: 'birthday'|'anniversary', date: 'YYYY-MM-DD', days_until, years? }
  static Future<Map<String, dynamic>> getWishes({
    required Uri baseUri,
    int days = 7,
    Duration timeout = const Duration(seconds: 10),
  }) async {
    final url = Uri.parse('${baseUri.toString()}get_wishes.php?days=${days.toString()}');
    try {
      final response = await http.get(url).timeout(timeout);
      final data = json.decode(response.body);
      if (data is Map && data['status'] == 'success' && data['data'] is List) {
        return {'success': true, 'data': data['data']};
      }
      return {'success': false, 'raw': data};
    } on TimeoutException {
      return {'success': false, 'error': 'timeout'};
    } catch (e) {
      return {'success': false, 'error': e.toString()};
    }
  }
}

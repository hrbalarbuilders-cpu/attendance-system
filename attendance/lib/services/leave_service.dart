import 'dart:async';
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:attendance/config.dart';

class LeaveService {
  final Uri baseUri;

  LeaveService([Uri? baseUri]) : baseUri = baseUri ?? kBaseUri;

  Future<String> cancelLeave(int leaveId) async {
    final uri = baseUri.resolve('cancel_leave.php');
    try {
      final response = await http.post(
        uri,
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'leave_id': leaveId}),
      ).timeout(const Duration(seconds: 10));
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data is Map && data['status'] == 'error') {
          throw Exception(data['msg'] ?? 'Server returned error');
        }
        return data['msg'] ?? 'Leave cancelled';
      }
      throw Exception('Failed to cancel leave (status: ${response.statusCode})');
    } on TimeoutException {
      throw Exception('Request timed out while cancelling leave');
    } catch (e) {
      throw Exception('Error cancelling leave: $e');
    }
  }

  Future<List<dynamic>> fetchLeaveTypes(int employeeId) async {
    // Server exposes `get_employee_leaves.php` which returns leave types for an employee.
    if (employeeId <= 0) throw Exception('Invalid employee id');
    final uri = baseUri.resolve('get_employee_leaves.php').replace(queryParameters: {'emp_id': employeeId.toString()});
    try {
      final response = await http.get(uri).timeout(const Duration(seconds: 10));
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data is Map && data['status'] == 'error') {
          throw Exception('Server error: ${data['msg']} (uri: ${uri.toString()})');
        }
        return data['leave_types'] ?? [];
      } else {
        throw Exception('Failed to load leave types (status: ${response.statusCode}) - ${response.body} (uri: ${uri.toString()})');
      }
    } on TimeoutException {
      throw Exception('Request timed out while loading leave types');
    } catch (e) {
      throw Exception('Error loading leave types: $e');
    }
  }

  Future<Map<String, dynamic>> applyLeave(Map<String, dynamic> leaveData) async {
    final uri = baseUri.resolve('apply_leave.php');
    try {
      final response = await http.post(
        uri,
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode(leaveData),
      ).timeout(const Duration(seconds: 10));
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final success = data['status'] == 'success';
        final msg = data['msg'] ?? data['message'] ?? '';
        return {'success': success, 'message': msg};
      }
      return {'success': false, 'message': 'HTTP ${response.statusCode}'};
    } on TimeoutException {
      return {'success': false, 'message': 'Request timed out while applying leave'};
    } catch (e) {
      return {'success': false, 'message': 'Error applying leave: $e'};
    }
  }

  Future<List<dynamic>> fetchLeaveHistory(int employeeId) async {
    final uri = baseUri.resolve('get_leave_history.php').replace(queryParameters: {'emp_id': employeeId.toString()});
    try {
      final response = await http.get(uri).timeout(const Duration(seconds: 10));
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data is Map && data['status'] == 'error') {
          throw Exception(data['msg'] ?? 'Server returned error');
        }
        return (data['history'] is List) ? List<dynamic>.from(data['history']) : [];
      }
      throw Exception('Failed to load leave history (status: ${response.statusCode})');
    } on TimeoutException {
      throw Exception('Request timed out while loading leave history');
    } catch (e) {
      throw Exception('Error loading leave history: $e');
    }
  }

  Future<List<dynamic>> fetchLeaveBalances(int employeeId) async {
    if (employeeId <= 0) throw Exception('Invalid employee id');
    final uri = baseUri.resolve('get_leave_balances.php').replace(queryParameters: {'employee_id': employeeId.toString()});
    try {
      final response = await http.get(uri).timeout(const Duration(seconds: 10));
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data is Map && data['status'] == 'error') {
          throw Exception('Server error: ${data['msg']} (uri: ${uri.toString()})');
        }
        return (data['balances'] is List) ? List<dynamic>.from(data['balances']) : [];
      }
      throw Exception('Failed to load leave balances (status: ${response.statusCode}) (uri: ${uri.toString()})');
    } on TimeoutException {
      throw Exception('Request timed out while loading leave balances');
    } catch (e) {
      throw Exception('Error loading leave balances: $e');
    }
  }
}

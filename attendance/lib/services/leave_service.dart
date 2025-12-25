import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:attendance/config.dart';

class LeaveService {
  final Uri baseUri;

  LeaveService([Uri? baseUri]) : baseUri = baseUri ?? kBaseUri;

  Future<String> cancelLeave(int leaveId) async {
    final response = await http.post(
      baseUri.resolve('cancel_leave.php'),
      body: {'leave_id': leaveId.toString()},
    );
    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      return data['msg'] ?? 'Leave cancelled';
    } else {
      throw Exception('Failed to cancel leave');
    }
  }

  Future<List<dynamic>> fetchLeaveTypes(int employeeId) async {
    final uri = baseUri.resolve('get_leave_types.php').replace(queryParameters: {'employee_id': employeeId.toString()});
    final response = await http.get(uri);
    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      return data['leave_types'] ?? [];
    } else {
      throw Exception('Failed to load leave types');
    }
  }

  Future<bool> applyLeave(Map<String, dynamic> leaveData) async {
    final response = await http.post(
      baseUri.resolve('apply_leave.php'),
      body: leaveData,
    );
    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      return data['status'] == 'success';
    } else {
      return false;
    }
  }

  Future<List<dynamic>> fetchLeaveHistory(int employeeId) async {
    final uri = baseUri.resolve('get_leave_history.php').replace(queryParameters: {'employee_id': employeeId.toString()});
    final response = await http.get(uri);
    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      return data['history'] ?? [];
    } else {
      throw Exception('Failed to load leave history');
    }
  }
}

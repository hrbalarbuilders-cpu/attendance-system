import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;


class LeaveHistoryView extends StatefulWidget {
  final int employeeId;
  const LeaveHistoryView({Key? key, required this.employeeId}) : super(key: key);

  @override
  State<LeaveHistoryView> createState() => _LeaveHistoryViewState();
}

class _LeaveHistoryViewState extends State<LeaveHistoryView> {
  List<dynamic> history = [];
  bool isLoading = true;
  String? errorMsg;

  @override
  void initState() {
    super.initState();
    fetchHistory();
  }

  Future<void> fetchHistory() async {
    setState(() { isLoading = true; errorMsg = null; });
    try {
      final response = await http.get(Uri.parse(
        'http://192.168.1.132:8080/attendance/attendance_api/get_leave_history.php?emp_id=${widget.employeeId}',
      ));
      final data = json.decode(response.body);
      if (data['status'] == 'success') {
        setState(() {
          history = data['history'] ?? [];
          isLoading = false;
        });
      } else {
        setState(() {
          errorMsg = data['msg'] ?? 'Failed to load leave history.';
          isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        errorMsg = 'Error: $e';
        isLoading = false;
      });
    }
  }

  Future<void> cancelLeave(int leaveId) async {
    final response = await http.post(
      Uri.parse('http://192.168.1.132:8080/attendance/attendance_api/cancel_leave.php'),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({'leave_id': leaveId}),
    );
    final data = json.decode(response.body);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(data['msg'] ?? 'Cancel request sent')),
    );
    if (data['status'] == 'success') fetchHistory();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Leave History')),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : errorMsg != null
              ? Center(child: Text(errorMsg!))
              : history.isEmpty
                  ? const Center(child: Text('No leave history found.'))
                  : ListView.separated(
                      itemCount: history.length,
                      separatorBuilder: (_, __) => const Divider(height: 1),
                      itemBuilder: (context, i) {
                        String formatDate(String? ymd) {
                          if (ymd == null || ymd.isEmpty) return '';
                          final parts = ymd.split('-');
                          if (parts.length < 3) return ymd;
                          return '${parts[2]}-${parts[1]}-${parts[0]}';
                        }
                        String formatDateTime(String? ymdhms) {
                          if (ymdhms == null || ymdhms.isEmpty) return '';
                          final date = ymdhms.split(' ').first;
                          return formatDate(date);
                        }
                        final item = history[i];
                        return ListTile(
                          leading: const Icon(Icons.event_note),
                          title: Text(item['leave_type'] ?? ''),
                          subtitle: Text(
                            'From: ${formatDate(item['from_date'])}\nTo: ${formatDate(item['to_date'])}\nReason: ${item['reason']}',
                          ),
                          trailing: Row(
                            mainAxisSize: MainAxisSize.min,
                            crossAxisAlignment: CrossAxisAlignment.center,
                            children: [
                              Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  Text(
                                    item['status'] ?? '',
                                    style: TextStyle(
                                      color: (item['status'] == 'pending')
                                          ? Colors.orange
                                          : (item['status'] == 'approved')
                                              ? Colors.green
                                              : Colors.red,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                  Text(
                                    formatDateTime(item['created_at']),
                                    style: const TextStyle(fontSize: 12, color: Colors.grey),
                                  ),
                                ],
                              ),
                              if (item['status'] == 'pending')
                                Padding(
                                  padding: const EdgeInsets.only(left: 8.0),
                                  child: TextButton(
                                    onPressed: () async {
                                      final confirm = await showDialog<bool>(
                                        context: context,
                                        builder: (context) => AlertDialog(
                                          title: const Text('Cancel Leave'),
                                          content: const Text('Are you sure you want to cancel this leave request?'),
                                          actions: [
                                            TextButton(
                                              onPressed: () => Navigator.of(context).pop(false),
                                              child: const Text('No'),
                                            ),
                                            TextButton(
                                              onPressed: () => Navigator.of(context).pop(true),
                                              child: const Text('Yes'),
                                            ),
                                          ],
                                        ),
                                      );
                                      if (confirm == true) {
                                        cancelLeave(item['id']);
                                      }
                                    },
                                    child: const Text('Cancel', style: TextStyle(color: Colors.red)),
                                  ),
                                ),
                            ],
                          ),
                        );
                      },
                    ),
    );
  }
// ...existing code...
}

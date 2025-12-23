import 'package:flutter/material.dart';
import '../services/leave_service.dart';

class LeaveHistoryScreen extends StatefulWidget {
  final int employeeId;
  const LeaveHistoryScreen({super.key, required this.employeeId});

  @override
  State<LeaveHistoryScreen> createState() => _LeaveHistoryScreenState();
}

class _LeaveHistoryScreenState extends State<LeaveHistoryScreen> {
  final LeaveService leaveService = LeaveService();
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
      final result = await leaveService.fetchLeaveHistory(widget.employeeId);
      setState(() {
        history = result;
        isLoading = false;
      });
    } catch (e) {
      setState(() {
        errorMsg = 'Error: $e';
        isLoading = false;
      });
    }
  }

  Future<void> cancelLeave(int leaveId) async {
    try {
      final msg = await leaveService.cancelLeave(leaveId);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(msg)),
      );
      fetchHistory();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString())),
      );
    }
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
                      separatorBuilder: (context, index) => const Divider(height: 1),
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
}

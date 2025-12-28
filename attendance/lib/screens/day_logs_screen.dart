import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../config.dart';
import '../services/clock_service.dart';

class DayLogsScreen extends StatefulWidget {
  final int userId;
  final String date; // YYYY-MM-DD

  const DayLogsScreen({
    super.key,
    required this.userId,
    required this.date,
  });

  @override
  State<DayLogsScreen> createState() => _DayLogsScreenState();
}

class _DayLogsScreenState extends State<DayLogsScreen> {
  bool _loading = true;
  Map<String, dynamic>? _data;
  String _displayDate = '';

  @override
  void initState() {
    super.initState();
    _displayDate = _formatDisplayDate(widget.date);
    _fetchLogs();
  }

  String _formatDisplayDate(String ymd) {
    try {
      final dt = DateTime.parse(ymd);
      return DateFormat('EEE, d MMM yyyy').format(dt);
    } catch (_) {
      return ymd;
    }
  }

  Future<void> _fetchLogs() async {
    setState(() => _loading = true);

    final res = await ClockService.getDayAttendance(
      baseUri: kBaseUri,
      userId: widget.userId,
      date: widget.date,
    );

    if (!mounted) return;

    setState(() {
      _data = res['success'] == true ? res : null;
      _loading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Day Logs',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
            ),
            Text(
              _displayDate,
              style: const TextStyle(fontSize: 12, color: Colors.black54),
            ),
          ],
        ),
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        elevation: 1,
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _data == null
              ? _buildEmptyState()
              : RefreshIndicator(
                  onRefresh: _fetchLogs,
                  child: _buildContent(),
                ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 24.0),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.timelapse, size: 64, color: Colors.grey[300]),
            const SizedBox(height: 12),
            const Text(
              'No attendance data',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 6),
            const Text(
              'No punches recorded for this date. Pull to refresh or try another date.',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.black54),
            ),
          ],
        ),
      ),
    );
  }

  /// ------- CLEAN + ALIGNED CONTENT -------
  Widget _buildContent() {
    final d = _data!;
    final logs = d['logs'] as List? ?? [];

    String fmt(int mins) => '${mins ~/ 60}h ${mins % 60}m';

    return ListView(
      padding: const EdgeInsets.all(14),
      children: [
        // SUMMARY BAR
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: Colors.grey[50],
            borderRadius: BorderRadius.circular(12),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              _summaryItem('Clock In', d['clock_in'] ?? '-'),
              _summaryItem('Clock Out', d['clock_out'] ?? '-'),
              _summaryItem('Punches', '${d['total_punches_today'] ?? 0}'),
            ],
          ),
        ),

        const SizedBox(height: 16),

        // —— METRICS GRID (PERFECTLY ALIGNED) ——
        GridView.count(
          shrinkWrap: true,
          crossAxisSpacing: 10,
          mainAxisSpacing: 10,
          physics: const NeverScrollableScrollPhysics(),
          crossAxisCount: 2,
          childAspectRatio: 2.5,
          children: [
            _metricBox(
              icon: Icons.access_time,
              label: 'Gross',
              value: fmt(d['gross_minutes'] ?? 0),
            ),
            _metricBox(
              icon: Icons.task_alt,
              label: 'Effective',
              value: fmt(d['effective_minutes'] ?? 0),
            ),
            _metricBox(
              icon: Icons.free_breakfast,
              label: 'Break',
              value: fmt(d['break_minutes'] ?? 0),
            ),
            _metricBox(
              icon: Icons.schedule,
              label: 'Late',
              value: fmt(d['late_minutes'] ?? 0),
            ),
          ],
        ),

        const SizedBox(height: 18),

        const Text(
          'Punch Logs',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
        ),
        const SizedBox(height: 8),

        if (logs.isEmpty)
          Container(
            height: 120,
            alignment: Alignment.center,
            child: const Text('No punches recorded.'),
          )
        else
          ...logs.map(
            (e) => Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: _simpleLogItem(e as Map<String, dynamic>),
            ),
          ),
      ],
    );
  }

  Widget _summaryItem(String label, String value) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label,
            style:
                const TextStyle(fontSize: 11, color: Colors.black54)),
        const SizedBox(height: 4),
        Text(value,
            style: const TextStyle(fontWeight: FontWeight.w600)),
      ],
    );
  }

  Widget _metricBox({
    required IconData icon,
    required String label,
    required String value,
  }) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey[200]!),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: Colors.blueGrey.withAlpha((0.08 * 255).round()),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, size: 18, color: Colors.blueGrey),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(label,
                    style: const TextStyle(
                        fontSize: 11, color: Colors.black54)),
                const SizedBox(height: 3),
                Text(
                  value,
                  style: const TextStyle(
                      fontWeight: FontWeight.w600, fontSize: 14),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _simpleLogItem(Map<String, dynamic> item) {
    final type = (item['type'] ?? '').toString().toUpperCase();
    final isIn = type == 'IN';

    DateTime? t;
    try {
      t = DateTime.parse(item['time'] ?? '');
    } catch (_) {}

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey[200]!),
      ),
      child: Row(
        children: [
          Container(
            padding:
                const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: (isIn ? Colors.green : Colors.red).withAlpha((0.1 * 255).round()),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(
              type,
              style: TextStyle(
                color: isIn ? Colors.green : Colors.red,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
          const SizedBox(width: 12),

          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  (item['reason'] ?? '').toString().trim().isNotEmpty
                      ? item['reason']
                      : (isIn ? 'Clock In' : 'Clock Out'),
                ),
                if (t != null)
                  Text(
                    DateFormat('hh:mm a • dd MMM').format(t),
                    style: const TextStyle(
                        fontSize: 11, color: Colors.black54),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

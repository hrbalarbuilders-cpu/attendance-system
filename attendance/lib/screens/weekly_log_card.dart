import 'dart:async';

import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../services/clock_service.dart';

class WeeklyLogCard extends StatefulWidget {
  final int userId;
  final Uri baseUri;
  final Map<String, dynamic> preloadedData;

  const WeeklyLogCard({
    super.key,
    required this.userId,
    required this.baseUri,
    this.preloadedData = const {},
  });

  @override
  State<WeeklyLogCard> createState() => _WeeklyLogCardState();
}

class _WeeklyLogCardState extends State<WeeklyLogCard> {
  late final List<GlobalKey> _dayKeys;
  OverlayEntry? _overlayEntry;
  Timer? _overlayTimer;

  @override
  void initState() {
    super.initState();
    _dayKeys = List.generate(7, (_) => GlobalKey());
    // no wishes fetch here — weekly log no longer shows wish badges
  }

  @override
  void dispose() {
    try {
      _overlayTimer?.cancel();
    } catch (_) {}
    try {
      _overlayEntry?.remove();
    } catch (_) {}
    super.dispose();
  }

  DateTime _startOfWeek() {
    final now = DateTime.now();
    final weekday = now.weekday % 7; // make Sunday=0
    return DateTime(
      now.year,
      now.month,
      now.day,
    ).subtract(Duration(days: weekday));
  }

  Future<void> _showDayPopover(int index) async {
    // Dismiss existing
    try {
      _overlayEntry?.remove();
    } catch (_) {}
    _overlayTimer?.cancel();
    _overlayEntry = null;

    final start = _startOfWeek();
    final dayDate = start.add(Duration(days: index));
    final dateStr =
        '${dayDate.year.toString().padLeft(4, '0')}-${dayDate.month.toString().padLeft(2, '0')}-${dayDate.day.toString().padLeft(2, '0')}';

    Map<String, dynamic> res;

    // 1. Check if we have preloaded data for this date
    if (widget.preloadedData.containsKey(dateStr)) {
      res = widget.preloadedData[dateStr] as Map<String, dynamic>;
      // Add 'status' to mimic getDayAttendance response if needed,
      // but based on my PHP edit it already has the gross/effective/break keys.
    } else {
      // 2. Otherwise fetch from API
      res = await ClockService.getDayAttendance(
        baseUri: widget.baseUri,
        userId: widget.userId,
        date: dateStr,
      );
    }

    if (!mounted) return;

    final key = _dayKeys[index];
    final renderBox = key.currentContext?.findRenderObject() as RenderBox?;
    if (renderBox == null) return;
    final overlay = Overlay.of(context);
    final target = renderBox.localToGlobal(Offset.zero);
    final size = renderBox.size;

    final panelWidth = 260.0;
    final panelHeight = 140.0;
    final screenWidth = MediaQuery.of(context).size.width;
    double left = target.dx + size.width / 2 - panelWidth / 2;
    left = left.clamp(8.0, screenWidth - panelWidth - 8.0);
    double top = target.dy - panelHeight - 8.0;
    if (top < MediaQuery.of(context).padding.top + 8.0) {
      top = target.dy + size.height + 8.0;
    }

    final gross = res['gross'] ?? res['gross_minutes'] ?? 0;
    final effective = res['effective'] ?? res['effective_minutes'] ?? 0;
    final brk = res['break'] ?? res['break_minutes'] ?? 0;
    final late = res['late'] ?? res['late_minutes'] ?? 0;

    String fmtMins(int mins) {
      final h = mins ~/ 60;
      final m = mins % 60;
      return '${h}h ${m}m';
    }

    _overlayEntry = OverlayEntry(
      builder: (ctx) {
        return Stack(
          children: [
            Positioned.fill(
              child: GestureDetector(
                behavior: HitTestBehavior.translucent,
                onTap: () {
                  try {
                    _overlayEntry?.remove();
                  } catch (_) {}
                  _overlayEntry = null;
                  _overlayTimer?.cancel();
                  _overlayTimer = null;
                },
                child: Container(color: Colors.transparent),
              ),
            ),
            Positioned(
              left: left,
              top: top,
              width: panelWidth,
              child: Material(
                color: Colors.transparent,
                child: Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: const Color.fromRGBO(0, 0, 0, 0.85),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        DateFormat('EEE, d MMM yyyy').format(dayDate),
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            'Gross',
                            style: TextStyle(color: Colors.white70),
                          ),
                          Text(
                            fmtMins(gross),
                            style: const TextStyle(color: Colors.white),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            'Effective',
                            style: TextStyle(color: Colors.white70),
                          ),
                          Text(
                            fmtMins(effective),
                            style: const TextStyle(color: Colors.white),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            'Break',
                            style: TextStyle(color: Colors.white70),
                          ),
                          Text(
                            fmtMins(brk),
                            style: const TextStyle(color: Colors.white),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('Late', style: TextStyle(color: Colors.white70)),
                          Text(
                            fmtMins(late),
                            style: const TextStyle(color: Colors.white),
                          ),
                        ],
                      ),
                      // Wishes removed from popover by user request.
                    ],
                  ),
                ),
              ),
            ),
          ],
        );
      },
    );

    overlay.insert(_overlayEntry!);
    _overlayTimer = Timer(const Duration(seconds: 3), () {
      try {
        _overlayEntry?.remove();
      } catch (_) {}
      _overlayEntry = null;
      _overlayTimer = null;
    });
  }

  @override
  Widget build(BuildContext context) {
    final start = _startOfWeek();
    // total wishes badge removed — no weekly count shown

    // Show short month name and year under every weekday (e.g., 'DEC 2025')
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Color.fromARGB((0.05 * 255).toInt(), 0, 0, 0),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Weekly Time Log',
            style: TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 14,
              color: Colors.black,
            ),
          ),
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: List.generate(7, (i) {
              final dayDate = start.add(Duration(days: i));
              final day = DateFormat('EEE').format(dayDate).toUpperCase();
              final date = dayDate.day.toString();
              final monthYear = DateFormat(
                'MMM yyyy',
              ).format(dayDate).toUpperCase();
              final isToday =
                  DateUtils.dateOnly(dayDate) ==
                  DateUtils.dateOnly(DateTime.now());
              final color = isToday
                  ? const Color(0xFF7C6FE8)
                  : Colors.grey[300]!;
              return GestureDetector(
                key: _dayKeys[i],
                onTap: () => _showDayPopover(i),
                child: _buildWeekDay(day, date, monthYear, isToday, color),
              );
            }),
          ),
        ],
      ),
    );
  }

  Widget _buildWeekDay(
    String day,
    String date,
    String monthYear,
    bool isToday,
    Color barColor,
  ) {
    return Column(
      children: [
        Text(
          day,
          style: TextStyle(
            fontSize: 9,
            fontWeight: FontWeight.w600,
            color: isToday ? Colors.black : Colors.grey[600],
          ),
        ),
        const SizedBox(height: 3),
        Stack(
          clipBehavior: Clip.none,
          children: [
            Container(
              width: 36,
              height: 38,
              decoration: BoxDecoration(
                color: isToday ? const Color(0xFF7C6FE8) : Colors.transparent,
                borderRadius: BorderRadius.circular(10),
              ),
              alignment: Alignment.center,
              child: Text(
                date,
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: isToday ? Colors.white : Colors.black,
                ),
              ),
            ),
            // per-day wish badge removed; total count shown in header
          ],
        ),
        const SizedBox(height: 3),
        Text(
          monthYear,
          textAlign: TextAlign.center,
          style: TextStyle(fontSize: 7, color: Colors.grey[600], height: 1.1),
        ),
        const SizedBox(height: 4),
        Container(
          width: 36,
          height: 3,
          decoration: BoxDecoration(
            color: barColor,
            borderRadius: BorderRadius.circular(2),
          ),
        ),
      ],
    );
  }
}

import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:math' as math;
import 'dart:async';
import 'login_screen.dart';
import 'apply_leave_screen.dart';
import 'leave_history_screen.dart';
import 'day_logs_screen.dart';
import 'weekly_log_card.dart';
import '../config.dart';
import '../services/clock_service.dart';
import '../widgets/bottom_banner.dart';
import '../widgets/wish_them_section.dart';

class AttendanceScreen extends StatefulWidget {
  const AttendanceScreen({super.key});

  @override
  State<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends State<AttendanceScreen> with SingleTickerProviderStateMixin {
  String? userName;
  int? userId;
  bool isLoading = false;
  String? clockMessage;
  bool isClockedIn = false;
  String? lastClockIn;
  String? lastClockOut;
  late AnimationController _progressController;
  Animation<double>? _progressAnimation;
  double _progress = 0.0;
  // Shift times (placeholder; can be populated from API later)
  TimeOfDay shiftStart = const TimeOfDay(hour: 9, minute: 0);
  TimeOfDay shiftEnd = const TimeOfDay(hour: 18, minute: 0);
  String shiftName = '';
  String workingFrom = '';
  
  final GlobalKey _shiftInfoKey = GlobalKey();
  OverlayEntry? _shiftOverlayEntry;
  Timer? _shiftHideTimer;
  List<WishUser> _wishUsers = [];
  bool _wishesLoading = true;


  @override
  void initState() {
    super.initState();
    _loadUserData();
    _fetchWishes();
    _progressController = AnimationController(vsync: this, duration: const Duration(milliseconds: 800));
    _progressAnimation = AlwaysStoppedAnimation(_progress);
    _progressController.addListener(() {
      setState(() {});
    });
  }

  Future<void> _fetchWishes() async {
    setState(() { _wishesLoading = true; });
    final list = <WishUser>[];
    try {
      final res = await ClockService.getWishes(baseUri: kBaseUri, days: 7, timeout: const Duration(seconds: 10));
      if (res['success'] == true && res['data'] is List) {
        for (final item in (res['data'] as List)) {
          if (item is Map) {
            final name = (item['name'] ?? '').toString();
            final years = item['years'] != null ? '${item['years']} YRS' : '1 YRS';
            final date = item['date'] is String ? DateTime.tryParse(item['date']) : null;
            final dateLabel = date != null ? '${date.day.toString().padLeft(2,'0')} ${_shortMonth(date.month)}' : (item['date']?.toString() ?? '');
            // map server type string to WishType
            final typeStr = (item['type'] ?? '').toString().toLowerCase();
            var wtype = WishType.birthday;
            if (typeStr.contains('anniv') || typeStr.contains('anniversary')) {
              // If years == 0, this indicates the employee joined in the current year
              // and should be treated as a new joiner rather than an anniversary.
              int yearsNum = -1;
              if (item['years'] is num) {
                yearsNum = (item['years'] as num).toInt();
              } else if (item['years'] is String) {
                yearsNum = int.tryParse(item['years'] as String) ?? -1;
              }
              if (yearsNum == 0) {
                wtype = WishType.newJoin;
              } else {
                wtype = WishType.anniversary;
              }
            } else if (typeStr.contains('join') || typeStr.contains('new')) {
              wtype = WishType.newJoin;
            } else if (typeStr.contains('birth') || typeStr.contains('bday')) {
              wtype = WishType.birthday;
            }
            list.add(WishUser(name: name, photo: item['photo']?.toString(), years: years, date: dateLabel, type: wtype));
          }
        }
      }
    } catch (_) {
      // ignore; we will show retry or empty state
    } finally {
      if (mounted) setState(() { _wishUsers = list; _wishesLoading = false; });
    }
  }

  String _shortMonth(int m) {
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return months[(m-1).clamp(0,11)];
  }

  @override
  void dispose() {
    _progressController.dispose();
    try {
      _shiftHideTimer?.cancel();
    } catch (_) {}
    try {
      _shiftOverlayEntry?.remove();
    } catch (_) {}
    super.dispose();
  }

  String _formattedToday() {
    final now = DateTime.now();
    const weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    final wd = weekdays[now.weekday % 7];
    final day = now.day.toString().padLeft(2, '0');
    final m = months[now.month - 1];
    return '$wd, $day $m';
  }

  Future<void> _confirmLogout() async {
    final doLogout = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Confirm logout'),
        content: const Text('Are you sure you want to log out?'),
        actions: [
          TextButton(onPressed: () => Navigator.of(ctx).pop(false), child: const Text('Cancel')),
          TextButton(onPressed: () => Navigator.of(ctx).pop(true), child: const Text('Log out')),
        ],
      ),
    );

    if (doLogout == true) {
      final prefs = await SharedPreferences.getInstance();
      await prefs.clear();
      if (!mounted) return;
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const LoginScreen()),
      );
    }
  }

  

  Future<void> _loadUserData() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      userName = prefs.getString('user_name') ?? 'Sachin Mandal';
      // Prefer the stored employee id if available; fall back to user_id
      userId = prefs.getInt('employee_id') ?? prefs.getInt('user_id') ?? 0;
    });
    // load shift details after we have user id
    _loadShiftDetails();
  }

  Future<void> _loadShiftDetails() async {
    // Re-read SharedPreferences to get the freshest employee_id (avoid stale state).
    final prefs = await SharedPreferences.getInstance();
    final prefEmployeeId = prefs.getInt('employee_id') ?? prefs.getInt('user_id') ?? 0;
    // (production) do not log prefs
    if (prefEmployeeId <= 0) return;
    // Ensure local state reflects the persisted id
    setState(() { userId = prefEmployeeId; });

    final info = await ClockService.getUserShift(baseUri: kBaseUri, userId: prefEmployeeId);
    if (!mounted) return;
    // do not store raw response in production

    if (info.success) {
      setState(() {
        shiftName = info.name.isNotEmpty ? info.name : shiftName;
        workingFrom = info.workingFrom.isNotEmpty ? info.workingFrom : workingFrom;
        // parse start/end strings (expecting HH:mm or HH:mm:ss)
        try {
          final sParts = info.start.split(':');
          final eParts = info.end.split(':');
          if (sParts.length >= 2) {
            final sh = int.tryParse(sParts[0]) ?? shiftStart.hour;
            final sm = int.tryParse(sParts[1]) ?? shiftStart.minute;
            shiftStart = TimeOfDay(hour: sh, minute: sm);
          }
          if (eParts.length >= 2) {
            final eh = int.tryParse(eParts[0]) ?? shiftEnd.hour;
            final em = int.tryParse(eParts[1]) ?? shiftEnd.minute;
            shiftEnd = TimeOfDay(hour: eh, minute: em);
          }
        } catch (_) {}
      });
    }
  }

  Future<void> _clockInOut({required String type}) async {
    if (userId == null || userId == 0) {
      BottomBanner.show(context, 'User not found.', success: false);
      return;
    }

    setState(() {
      isLoading = true;
    });

    final now = DateTime.now();
    final result = await ClockService.clockInOut(baseUri: kBaseUri, userId: userId!, type: type);

    if (!mounted) return;

    if (result.success) {
      setState(() {
        isClockedIn = result.isClockedIn;
        // update displayed times locally
        if (type == 'in') {
          lastClockIn = TimeOfDay.fromDateTime(now).format(context);
        } else if (type == 'out') {
          lastClockOut = TimeOfDay.fromDateTime(now).format(context);
        }
      });
    }

    // Update progress: on clock-in set to 0, on clock-out animate to 1.0
    if (result.success) {
      if (type == 'in') {
        _progress = 0.0;
        _progressAnimation = AlwaysStoppedAnimation(_progress);
        _progressController.reset();
      } else if (type == 'out') {
        // animate from current progress to full completion
        _progressAnimation = Tween<double>(begin: _progress, end: 1.0).animate(CurvedAnimation(parent: _progressController, curve: Curves.easeInOut));
        _progressController.forward(from: 0.0).then((_) {
          _progress = 1.0;
          _progressAnimation = AlwaysStoppedAnimation(_progress);
        });
      }
    }

    BottomBanner.show(context, result.message, success: result.success);

    setState(() {
      isLoading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildGreetingSection(),
              const SizedBox(height: 16),
              _buildShiftCard(),
              const SizedBox(height: 16),
              _buildActionButtons(),
              const SizedBox(height: 16),
              _buildWishesSection(),
              const SizedBox(height: 16),
              WeeklyLogCard(userId: userId ?? 0, baseUri: kBaseUri),
            ],
          ),
        ),
      ),
    );
  }

  

  Widget _buildGreetingSection() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Hello,',
              style: TextStyle(color: Colors.grey[600], fontSize: 14),
            ),
            const SizedBox(height: 2),
            Text(
              userName?.toUpperCase() ?? 'USER',
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: Colors.black,
              ),
            ),
          ],
        ),
        Row(
          children: [
            Container(
              width: 38,
              height: 38,
              decoration: BoxDecoration(
                color: Colors.grey[200],
                shape: BoxShape.circle,
              ),
              child: Icon(Icons.notifications_outlined, color: Colors.grey[700], size: 20),
            ),
            const SizedBox(width: 10),
            PopupMenuButton<String>(
              onSelected: (value) async {
                if (value == 'logout') {
                  await _confirmLogout();
                }
              },
              itemBuilder: (ctx) => [
                const PopupMenuItem(value: 'logout', child: Text('Log out')),
              ],
              child: CircleAvatar(
                radius: 20,
                backgroundColor: Colors.grey[300],
                child: Icon(Icons.person, color: Colors.grey[600], size: 24),
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildShiftCard() {
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
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              // Compact SHIFT TODAY with tappable info icon (no extra padding)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 2, horizontal: 0),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.access_time, size: 16, color: Colors.grey[600]),
                    const SizedBox(width: 4),
                    Text(
                      'SHIFT TODAY',
                      style: TextStyle(
                        fontSize: 10,
                        color: Colors.grey[600],
                        fontWeight: FontWeight.w500,
                        letterSpacing: 0.5,
                      ),
                    ),
                    const SizedBox(width: 6),
                    GestureDetector(
                      key: _shiftInfoKey,
                      behavior: HitTestBehavior.opaque,
                      onTap: _showShiftDetails,
                      child: const Padding(
                        padding: EdgeInsets.all(4.0), // small touch target without extra visual spacing
                        child: Icon(Icons.info_outline, size: 16, color: Colors.grey),
                      ),
                    ),
                  ],
                ),
              ),
              Row(
                children: [
                  Icon(Icons.calendar_today, size: 14, color: Colors.grey[600]),
                  const SizedBox(width: 6),
                  Text(
                    _formattedToday(),
                    style: TextStyle(fontSize: 14, color: Colors.grey[700]),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            shiftName.isNotEmpty ? shiftName.toUpperCase() : 'MAIN OFFICE MALE',
            style: const TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 14,
              color: Colors.black,
            ),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              _buildCircularProgress(),
              const SizedBox(width: 20),
              Expanded(
                child: Column(
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'CLOCK IN',
                              style: TextStyle(
                                fontSize: 10,
                                color: Colors.grey[600],
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              lastClockIn ?? 'Missing',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 14,
                                color: Colors.black,
                              ),
                            ),
                          ],
                        ),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            Text(
                              'CLOCK OUT',
                              style: TextStyle(
                                fontSize: 10,
                                color: Colors.grey[600],
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              lastClockOut ?? 'Missing',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 14,
                                color: Colors.black,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    SizedBox(
                      width: double.infinity,
                      height: 42,
                      child: ElevatedButton(
                        onPressed: isLoading
                            ? null
                            : () => _clockInOut(type: isClockedIn ? 'out' : 'in'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: isClockedIn ? Colors.red : const Color(0xFF4CAF50),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                          elevation: 0,
                        ),
                        child: isLoading
                            ? const SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(
                                  color: Colors.white,
                                  strokeWidth: 2,
                                ),
                              )
                            : Text(
                                isClockedIn ? 'Clock Out' : 'Clock In',
                                style: const TextStyle(
                                  fontSize: 14,
                                  fontWeight: FontWeight.w600,
                                  color: Colors.white,
                                ),
                              ),
                      ),
                    ),
                    
                  ],
                ),
              ),
              const SizedBox(width: 8),
              GestureDetector(
                onTap: () {
                  if (userId == null || userId == 0) {
                    BottomBanner.show(context, 'User not found.', success: false);
                    return;
                  }
                  final today = DateTime.now();
                  final dateStr = '${today.year.toString().padLeft(4,'0')}-${today.month.toString().padLeft(2,'0')}-${today.day.toString().padLeft(2,'0')}';
                  Navigator.of(context).push(
                    MaterialPageRoute(builder: (_) => DayLogsScreen(userId: userId!, date: dateStr)),
                  );
                },
                child: Icon(Icons.chevron_right, color: Colors.grey[400]),
              ),
            ],
          ),
        ],
      ),
    );
  }

  void _showShiftDetails() {
    if (!mounted) return;

    // toggle
    if (_shiftOverlayEntry != null) {
      try {
        _shiftOverlayEntry?.remove();
      } catch (_) {}
      _shiftOverlayEntry = null;
      _shiftHideTimer?.cancel();
      _shiftHideTimer = null;
      return;
    }

    final start = shiftStart.format(context);
    final end = shiftEnd.format(context);
    final durationMinutes = (shiftEnd.hour * 60 + shiftEnd.minute) - (shiftStart.hour * 60 + shiftStart.minute);
    final hours = (durationMinutes ~/ 60).abs();
    final minutes = (durationMinutes % 60).abs();

    final renderBox = _shiftInfoKey.currentContext?.findRenderObject() as RenderBox?;
    if (renderBox == null) return;
    final overlay = Overlay.of(context);

    final target = renderBox.localToGlobal(Offset.zero);
    final size = renderBox.size;

    const double panelWidth = 260;
    const double panelHeight = 120;

    // center panel horizontally on the icon, clamp to screen
    final screenWidth = MediaQuery.of(context).size.width;
    double left = target.dx + size.width / 2 - panelWidth / 2;
    left = left.clamp(8.0, screenWidth - panelWidth - 8.0);

    // place above the icon if there's space, otherwise below
    double top = target.dy - panelHeight - 8.0;
    if (top < MediaQuery.of(context).padding.top + 8.0) {
      top = target.dy + size.height + 8.0;
    }

    _shiftOverlayEntry = OverlayEntry(builder: (ctx) {
      return Stack(
        children: [
          Positioned.fill(
            child: GestureDetector(
              behavior: HitTestBehavior.translucent,
              onTap: () {
                try {
                  _shiftOverlayEntry?.remove();
                } catch (_) {}
                _shiftOverlayEntry = null;
                _shiftHideTimer?.cancel();
                _shiftHideTimer = null;
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
                    const Text('About Shift', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                    const SizedBox(height: 6),
                    const SizedBox(height: 8),
                    const SizedBox(height: 8),
                    // Nicely formatted parsed fields
                    Text('Shift Name: ${shiftName.isNotEmpty ? shiftName : "-"}', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600)),
                    const SizedBox(height: 6),
                    Text('Working From: ${workingFrom.isNotEmpty ? workingFrom : "-"}', style: const TextStyle(color: Colors.white70)),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        const Icon(Icons.play_arrow, size: 14, color: Colors.white70),
                        const SizedBox(width: 6),
                        Text('Start: $start', style: const TextStyle(color: Colors.white)),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        const Icon(Icons.stop, size: 14, color: Colors.white70),
                        const SizedBox(width: 6),
                        Text('End: $end', style: const TextStyle(color: Colors.white)),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text('Duration: ${hours}h ${minutes}m', style: const TextStyle(color: Colors.white70)),
                  ],
                ),
              ),
            ),
          ),
        ],
      );
    });

    overlay.insert(_shiftOverlayEntry!);
    // auto-dismiss after 3 seconds
    _shiftHideTimer?.cancel();
    _shiftHideTimer = Timer(const Duration(seconds: 3), () {
      try {
        _shiftOverlayEntry?.remove();
      } catch (_) {}
      _shiftOverlayEntry = null;
      _shiftHideTimer = null;
    });
  }

  Widget _buildCircularProgress() {
  final progressValue = _progressAnimation?.value ?? _progress;
  return SizedBox(
    width: 110,
    height: 110,
    child: Stack(
      alignment: Alignment.center,
      children: [
        CustomPaint(
          size: const Size(110, 110),
          painter: CircularProgressPainter(
            progress: progressValue,
            backgroundColor: const Color(0xFFE0E0E0),
            progressColor: const Color(0xFF7C6FE8),
            strokeWidth: 12.0,
          ),
        ),
        Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Text(
              '0h 0m',
              style: TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 16,
                color: Colors.black,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              'Completed',
              style: TextStyle(
                fontSize: 11,
                color: Colors.grey[500],
              ),
            ),
          ],
        ),
      ],
    ),
  );
}

  Widget _buildActionButtons() {
    return Row(
      children: [
        Expanded(
          child: GestureDetector(
            onTap: () {
              if (userId == null || userId == 0) {
                BottomBanner.show(context, 'User not found.', success: false);
                return;
              }
              Navigator.of(context).push(MaterialPageRoute(builder: (_) => ApplyLeaveScreen(employeeId: userId!)));
            },
            child: _buildActionButton(Icons.flight_outlined, 'Apply\nLeave'),
          ),
        ),
        Expanded(child: _buildActionButton(Icons.receipt_long_outlined, 'View\nPayslip')),
        Expanded(child: _buildActionButton(Icons.confirmation_number_outlined, 'Raise\nTicket')),
        Expanded(
          child: GestureDetector(
            onTap: () {
              if (userId == null || userId == 0) {
                BottomBanner.show(context, 'User not found.', success: false);
                return;
              }
              Navigator.of(context).push(MaterialPageRoute(builder: (_) => LeaveHistoryScreen(employeeId: userId!)));
            },
            child: _buildActionButton(Icons.history, 'Leave\nHistory'),
          ),
        ),
      ],
    );
  }

  Widget _buildActionButton(IconData icon, String label) {
    return Column(
      children: [
        Container(
          width: 52,
          height: 52,
          decoration: BoxDecoration(
            color: Colors.white,
            shape: BoxShape.circle,
            border: Border.all(color: Colors.grey[300]!, width: 1),
            boxShadow: [
              BoxShadow(
                color: Color.fromARGB((0.04 * 255).toInt(), 0, 0, 0),
                blurRadius: 6,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: Icon(icon, color: Colors.black87, size: 22),
        ),
        const SizedBox(height: 6),
        Text(
          label,
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 10, color: Colors.black87, height: 1.2, fontWeight: FontWeight.w500),
        ),
      ],
    );
  }

  Widget _buildWishesSection() {
    if (_wishesLoading) {
      return Container(
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12)),
        padding: const EdgeInsets.all(12),
        child: Row(
          children: const [
            SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2)),
            SizedBox(width: 12),
            Text('Loading wishes...', style: TextStyle(color: Colors.black54)),
          ],
        ),
      );
    }

    if (_wishUsers.isEmpty) {
      return Container(
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12)),
        padding: const EdgeInsets.all(12),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text('No wishes today', style: TextStyle(color: Colors.black54)),
            TextButton(onPressed: _fetchWishes, child: const Text('Retry')),
          ],
        ),
      );
    }

    return WishThemSection(users: _wishUsers, cardWidth: 90);
  }

  // Weekly log UI moved to `WeeklyLogCard` widget.
}

class CircularProgressPainter extends CustomPainter {
  final double progress;
  final Color backgroundColor;
  final Color progressColor;
  final double strokeWidth;

  CircularProgressPainter({
    required this.progress,
    required this.backgroundColor,
    required this.progressColor,
    this.strokeWidth = 8.0,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final radius = size.width / 2;

    // Background circle
    final backgroundPaint = Paint()
      ..color = backgroundColor
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth
      ..strokeCap = StrokeCap.butt;

    canvas.drawCircle(center, radius - strokeWidth / 2, backgroundPaint);

    // Progress arc
    final progressPaint = Paint()
      ..color = progressColor
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth
      ..strokeCap = StrokeCap.butt;

    final startAngle = -math.pi / 2;
    final sweepAngle = 2 * math.pi * progress;

    canvas.drawArc(
      Rect.fromCircle(center: center, radius: radius - strokeWidth / 2),
      startAngle,
      sweepAngle,
      false,
      progressPaint,
    );
  }

  @override
  bool shouldRepaint(CircularProgressPainter oldDelegate) {
    return oldDelegate.progress != progress;
  }
}
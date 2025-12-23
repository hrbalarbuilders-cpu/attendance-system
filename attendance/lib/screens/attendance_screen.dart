import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:math' as math;
import 'package:http/http.dart' as http;
import 'dart:convert';
import '../config.dart';

class AttendanceScreen extends StatefulWidget {
  const AttendanceScreen({super.key});

  @override
  State<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends State<AttendanceScreen> {
  String? userName;
  int? userId;
  bool isLoading = false;
  String? clockMessage;
  bool isClockedIn = false;

  @override
  void initState() {
    super.initState();
    _loadUserData();
  }

  Future<void> _loadUserData() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      userName = prefs.getString('user_name') ?? 'Sachin Mandal';
      userId = prefs.getInt('user_id') ?? 0;
    });
  }

  Future<void> _clockInOut({required String type}) async {
    if (userId == null || userId == 0) {
      setState(() {
        clockMessage = 'User not found.';
      });
      print('User not found.');
      return;
    }
    setState(() {
      isLoading = true;
      clockMessage = null;
    });
    final now = DateTime.now();
    final url = Uri.parse('$kBaseUrl/clock.php');
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
      print('Sending request to: ${url.toString()}');
      print('Request body: ${body.toString()}');
      final response = await http.post(url, body: body);
      print('Response status: ${response.statusCode}');
      print('Response body: ${response.body}');
      final data = json.decode(response.body);
      setState(() {
        clockMessage = response.body;
      });
      if (data['status'] == 'success') {
        setState(() {
          isClockedIn = type == 'in';
          clockMessage = type == 'in' ? 'Clocked in successfully!' : 'Clocked out successfully!';
        });
      } else {
        setState(() {
          clockMessage = data['msg'] ?? 'Error occurred.';
        });
      }
    } catch (e) {
      print('Exception: ${e.toString()}');
      setState(() {
        clockMessage = 'Network error: ${e.toString()}';
      });
    } finally {
      setState(() {
        isLoading = false;
      });
    }
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
              _buildWeeklyLogCard(),
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
            CircleAvatar(
              radius: 20,
              backgroundColor: Colors.grey[300],
              child: Icon(Icons.person, color: Colors.grey[600], size: 24),
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
            color: Colors.black.withOpacity(0.05),
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
              Row(
                children: [
                  Icon(Icons.access_time, size: 16, color: Colors.grey[600]),
                  const SizedBox(width: 6),
                  Text(
                    'SHIFT TODAY',
                    style: TextStyle(
                      fontSize: 10,
                      color: Colors.grey[600],
                      fontWeight: FontWeight.w500,
                      letterSpacing: 0.5,
                    ),
                  ),
                  const SizedBox(width: 4),
                  Icon(Icons.info_outline, size: 14, color: Colors.grey[400]),
                ],
              ),
              Row(
                children: [
                  Icon(Icons.calendar_today, size: 12, color: Colors.grey[600]),
                  const SizedBox(width: 6),
                  Text(
                    'Tuesday, 23 Dec',
                    style: TextStyle(fontSize: 11, color: Colors.grey[700]),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 8),
          const Text(
            'MAIN OFFICE MALE',
            style: TextStyle(
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
                            const Text(
                              'Missing',
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
                            const Text(
                              'Missing',
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
                    if (clockMessage != null) ...[
                      const SizedBox(height: 8),
                      Text(
                        clockMessage!,
                        style: TextStyle(
                          color: clockMessage!.toLowerCase().contains('success') ? Colors.green : Colors.red,
                          fontSize: 13,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Icon(Icons.chevron_right, color: Colors.grey[400]),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildCircularProgress() {
  return SizedBox(
    width: 110, // ⬅ increased
    height: 110, // ⬅ increased
    child: Stack(
      alignment: Alignment.center,
      children: [
        CustomPaint(
          size: const Size(110, 110),
          painter: CircularProgressPainter(
            progress: 0.65,
            backgroundColor: const Color(0xFFE0E0E0),
            progressColor: const Color(0xFF7C6FE8),
            strokeWidth: 12.0, // ⬅ thicker stroke
          ),
        ),
        Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Text(
              '0h 0m',
              style: TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 16, // ⬅ bigger text
                color: Colors.black,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              'Completed',
              style: TextStyle(
                fontSize: 11, // ⬅ slightly bigger
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
        Expanded(child: _buildActionButton(Icons.flight_outlined, 'Apply\nLeave')),
        Expanded(child: _buildActionButton(Icons.receipt_long_outlined, 'View\nPayslip')),
        Expanded(child: _buildActionButton(Icons.confirmation_number_outlined, 'Raise\nTicket')),
        Expanded(child: _buildActionButton(Icons.history, 'Leave\nHistory')),
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
                color: Colors.black.withOpacity(0.04),
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
    // Sample list of wishes - you can replace this with your dynamic data
    final wishes = [
      {'initials': 'TB', 'name': 'Tejas Bala...', 'date': '01 Jan', 'years': '1 YRS', 'color': const Color(0xFFB8AFFF), 'hasImage': false},
      {'initials': 'RS', 'name': 'Raj Shah (...', 'date': '01 Jan', 'years': '2 YRS', 'color': const Color(0xFF8B9CFF), 'hasImage': true},
      {'initials': 'CP', 'name': 'Chand Pate...', 'date': '01 Jan', 'years': '1 YRS', 'color': const Color(0xFFB8AFFF), 'hasImage': false},
      {'initials': 'DB', 'name': 'Dhaval Bal...', 'date': '01 Jan', 'years': '1 YRS', 'color': const Color(0xFFB8AFFF), 'hasImage': false},
      {'initials': 'AM', 'name': 'Amit Mehta', 'date': '01 Jan', 'years': '3 YRS', 'color': const Color(0xFF8B9CFF), 'hasImage': false},
      {'initials': 'SK', 'name': 'Suresh Kumar', 'date': '01 Jan', 'years': '2 YRS', 'color': const Color(0xFFB8AFFF), 'hasImage': false},
    ];

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Wish them',
            style: TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 14,
              color: Colors.black,
            ),
          ),
          const SizedBox(height: 12),
          wishes.length <= 4
              ? Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: List.generate(wishes.length, (index) {
                    final wish = wishes[index];
                    return Expanded(
                      child: Padding(
                        padding: EdgeInsets.only(left: index > 0 ? 8 : 0),
                        child: _buildWishCard(
                          wish['initials'] as String,
                          wish['name'] as String,
                          wish['date'] as String,
                          wish['years'] as String,
                          wish['color'] as Color,
                          hasImage: wish['hasImage'] as bool,
                        ),
                      ),
                    );
                  }),
                )
              : SizedBox(
                  height: 100,
                  child: ListView.separated(
                    scrollDirection: Axis.horizontal,
                    itemCount: wishes.length,
                    separatorBuilder: (context, index) => const SizedBox(width: 8),
                    itemBuilder: (context, index) {
                      final wish = wishes[index];
                      return SizedBox(
                        width: 65,
                        child: _buildWishCard(
                          wish['initials'] as String,
                          wish['name'] as String,
                          wish['date'] as String,
                          wish['years'] as String,
                          wish['color'] as Color,
                          hasImage: wish['hasImage'] as bool,
                        ),
                      );
                    },
                  ),
                ),
        ],
      ),
    );
  }

  Widget _buildWishCard(String initials, String name, String date, String years, Color color, {bool hasImage = false}) {
    return Container(
      padding: const EdgeInsets.fromLTRB(6, 8, 6, 6),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(10),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          hasImage
              ? CircleAvatar(
                  radius: 18,
                  backgroundColor: Colors.grey[300],
                  child: Icon(Icons.person, color: Colors.grey[600], size: 18),
                )
              : CircleAvatar(
                  radius: 18,
                  backgroundColor: color.withOpacity(0.2),
                  child: Text(
                    initials,
                    style: TextStyle(
                      color: color,
                      fontWeight: FontWeight.bold,
                      fontSize: 12,
                    ),
                  ),
                ),
          const SizedBox(height: 4),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
            decoration: BoxDecoration(
              color: color,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text(
              years,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 8,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          const SizedBox(height: 3),
          Text(
            name,
            style: const TextStyle(fontSize: 9, color: Colors.black87),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            textAlign: TextAlign.center,
          ),
          Text(
            date,
            style: TextStyle(fontSize: 8, color: Colors.grey[600]),
          ),
        ],
      ),
    );
  }

  Widget _buildWeeklyLogCard() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
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
            children: [
              _buildWeekDay('SUN', '21', 'DEC\n2025', false, const Color(0xFF4CAF50)),
              _buildWeekDay('MON', '22', 'DEC\n2025', false, const Color(0xFF7C6FE8)),
              _buildWeekDay('TUE', '23', 'DEC\n2025', true, const Color(0xFF7C6FE8)),
              _buildWeekDay('WED', '24', 'DEC\n2025', false, Colors.grey[300]!),
              _buildWeekDay('THU', '25', 'DEC\n2025', false, Colors.grey[300]!),
              _buildWeekDay('FRI', '26', 'DEC\n2025', false, Colors.grey[300]!),
              _buildWeekDay('SAT', '27', 'DEC\n2025', false, Colors.grey[300]!),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildWeekDay(String day, String date, String monthYear, bool isToday, Color barColor) {
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
        const SizedBox(height: 3),
        Text(
          monthYear,
          textAlign: TextAlign.center,
          style: TextStyle(
            fontSize: 7,
            color: Colors.grey[600],
            height: 1.1,
          ),
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
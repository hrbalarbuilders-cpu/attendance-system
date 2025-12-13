import 'dart:convert';
import 'dart:math';
import 'dart:async'; // StreamSubscription
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:device_info_plus/device_info_plus.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:http/http.dart' as http;
import 'package:path/path.dart' as p;
import 'package:sqflite/sqflite.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const MyApp());
}

// ------------------- BASIC APP SHELL -------------------

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Attendance App',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        colorSchemeSeed: Colors.indigo,
        scaffoldBackgroundColor: const Color(0xFFF4F5FB),
        appBarTheme: const AppBarTheme(
          backgroundColor: Color(0xFFF4F5FB),
          elevation: 0,
          centerTitle: false,
          titleTextStyle: TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.w600,
            color: Colors.black,
          ),
        ),
        cardTheme: CardThemeData(
          elevation: 3,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(24),
          ),
          margin: const EdgeInsets.symmetric(vertical: 8),
        ),
      ),
      home: const AttendanceScreen(),
    );
  }
}

// ------------------- LOGIN SCREEN -------------------

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isLoading = false;
  bool _obscurePassword = true;
  final _formKey = GlobalKey<FormState>();

  // BACKEND CONFIG
  static const String baseUrl =
      "http://192.168.1.103:8080/attendance-system/attendance/attendance_api";

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() => _isLoading = true);

    try {
      final conn = await Connectivity().checkConnectivity();
      if (conn.contains(ConnectivityResult.none) || conn.isEmpty) {
        setState(() => _isLoading = false);
        _showSnack("No internet connection. Please connect to internet.");
        return;
      }

      final response = await http
          .post(
            Uri.parse("$baseUrl/login.php"),
            body: {
              'email': _emailController.text.trim(),
              'password': _passwordController.text.trim(),
            },
          )
          .timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);

        if (data['status'] == 'success') {
          // Save user data to SharedPreferences
          final prefs = await SharedPreferences.getInstance();
          await prefs.setInt('user_id', data['user_id']);
          await prefs.setInt('employee_id', data['employee_id']);
          await prefs.setString('emp_code', data['emp_code']);
          await prefs.setString('user_name', data['name']);
          await prefs.setString('user_email', data['email']);
          await prefs.setBool('is_logged_in', true);

          // Navigate to attendance screen
          if (mounted) {
            Navigator.of(context).pushReplacement(
              MaterialPageRoute(builder: (context) => const AttendanceScreen()),
            );
          }
        } else {
          setState(() => _isLoading = false);
          _showSnack(data['msg'] ?? "Login failed. Please try again.");
        }
      } else {
        setState(() => _isLoading = false);
        _showSnack("Server error. Please try again.");
      }
    } catch (e) {
      setState(() => _isLoading = false);
      _showSnack("Network error. Please check your connection and try again.");
    }
  }

  void _showSnack(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F5FB),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Form(
              key: _formKey,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Logo/Icon
                  Container(
                    width: 100,
                    height: 100,
                    margin: const EdgeInsets.only(bottom: 40),
                    decoration: BoxDecoration(
                      color: const Color(0xFF6366F1),
                      shape: BoxShape.circle,
                      boxShadow: [
                        BoxShadow(
                          color: const Color(0xFF6366F1).withValues(alpha: 0.3),
                          blurRadius: 20,
                          offset: const Offset(0, 10),
                        ),
                      ],
                    ),
                    child: const Icon(
                      Icons.access_time_filled,
                      size: 50,
                      color: Colors.white,
                    ),
                  ),

                  // Title
                  const Text(
                    "Attendance App",
                    style: TextStyle(
                      fontSize: 28,
                      fontWeight: FontWeight.bold,
                      color: Colors.black,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    "Login to continue",
                    style: TextStyle(fontSize: 16, color: Colors.grey),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 40),

                  // Email Field
                  TextFormField(
                    controller: _emailController,
                    keyboardType: TextInputType.emailAddress,
                    decoration: InputDecoration(
                      labelText: "Email",
                      hintText: "Enter your email",
                      prefixIcon: const Icon(Icons.email_outlined),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      filled: true,
                      fillColor: Colors.white,
                    ),
                    validator: (value) {
                      if (value == null || value.isEmpty) {
                        return 'Please enter your email';
                      }
                      if (!value.contains('@')) {
                        return 'Please enter a valid email';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 20),

                  // Password Field
                  TextFormField(
                    controller: _passwordController,
                    obscureText: _obscurePassword,
                    decoration: InputDecoration(
                      labelText: "Password",
                      hintText: "Enter your password",
                      prefixIcon: const Icon(Icons.lock_outlined),
                      suffixIcon: IconButton(
                        icon: Icon(
                          _obscurePassword
                              ? Icons.visibility_outlined
                              : Icons.visibility_off_outlined,
                        ),
                        onPressed: () {
                          setState(() => _obscurePassword = !_obscurePassword);
                        },
                      ),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      filled: true,
                      fillColor: Colors.white,
                    ),
                    validator: (value) {
                      if (value == null || value.isEmpty) {
                        return 'Please enter your password';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 8),

                  // Default Password Hint
                  const Padding(
                    padding: EdgeInsets.only(left: 16),
                    child: Text(
                      "Default password: 123456",
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey,
                        fontStyle: FontStyle.italic,
                      ),
                    ),
                  ),
                  const SizedBox(height: 30),

                  // Login Button
                  SizedBox(
                    height: 50,
                    child: ElevatedButton(
                      onPressed: _isLoading ? null : _handleLogin,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF6366F1),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                        elevation: 0,
                      ),
                      child: _isLoading
                          ? const SizedBox(
                              height: 20,
                              width: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                valueColor: AlwaysStoppedAnimation<Color>(
                                  Colors.white,
                                ),
                              ),
                            )
                          : const Text(
                              "Login",
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

// ------------------- ATTENDANCE SCREEN -------------------

class AttendanceScreen extends StatefulWidget {
  const AttendanceScreen({super.key});

  @override
  State<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends State<AttendanceScreen> {
  late StreamSubscription<List<ConnectivityResult>> _connSub;
  Timer? _progressTimer; // Timer for real-time progress updates

  // UI ke liye
  String? clockInTime;
  String? clockOutTime;
  bool isTodayMarked = false;
  bool isTodaySynced = false;
  bool isLoading = false;
  String? pendingClockType; // 'in' or 'out' - shows loading state
  String? pendingClockTime; // Time shown while processing
  String? shiftName; // User's shift name
  String? shiftStartTime; // Shift start time
  String? shiftEndTime; // Shift end time
  int totalPunches = 4; // Total punches allowed per day (default 4)
  int earlyClockInBefore =
      0; // Minutes before shift start when early clock in is allowed
  int lateMarkAfter = 30; // Minutes after shift start when late mark starts
  int halfDayAfter = 270; // Minutes from shift start to half day time
  String? lunchStartTime; // Lunch start time
  String? lunchEndTime; // Lunch end time
  int todayPunchesCount = 0; // Count of punches today
  String? lastPunchType; // Last punch type: 'in' or 'out'
  String?
  lastPunchReason; // Last punch reason: 'shift_start', 'lunch', 'tea', 'shift_end'
  List<Map<String, dynamic>> todayLogs = []; // All attendance logs for today

  // BACKEND CONFIG
  static const String baseUrl =
      "http://192.168.1.103:8080/attendance-system/attendance/attendance_api";

  int userId = 1; // Will be loaded from SharedPreferences
  String userName = "User"; // User name
  static const String workingFrom = "office"; // 'office' or 'home' (WFH)

  @override
  void initState() {
    super.initState();

    // Check login and load user data (will also fetch today's attendance)
    _checkLoginAndLoadUser();

    // Saved UI data (clock in/out time) laata hai (as fallback/initial state)
    _loadTodayFromPrefs();

    // Cached office location load karo (pehle)
    LocationHelper.loadFromPrefs();

    // App khulte hi office location fetch karo (server se latest)
    _fetchOfficeLocation();

    // Fetch user's shift information
    _fetchUserShift();

    // Connection listener for location, shift, and attendance updates
    _connSub = Connectivity().onConnectivityChanged.listen((results) {
      if (results.isNotEmpty && !results.contains(ConnectivityResult.none)) {
        // Internet ON → Fetch office location, shift, and today's attendance
        _fetchOfficeLocation();
        _fetchUserShift();
        _fetchTodayAttendance();
      }
    });

    // Start timer for real-time progress updates (every 30 seconds)
    // Timer updates UI when clocked in - always call setState and let progress calculation handle it
    _progressTimer = Timer.periodic(const Duration(seconds: 30), (timer) {
      if (mounted) {
        // Update UI to reflect real-time progress (progress calculation will handle clock in/out state)
        setState(() {});
      }
    });
  }

  @override
  void dispose() {
    _connSub.cancel();
    _progressTimer?.cancel();
    super.dispose();
  }

  // Check login and load user data
  Future<void> _checkLoginAndLoadUser() async {
    final prefs = await SharedPreferences.getInstance();
    final isLoggedIn = prefs.getBool('is_logged_in') ?? false;

    if (!isLoggedIn) {
      // Redirect to login if not logged in
      if (mounted) {
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (context) => const LoginScreen()),
        );
      }
      return;
    }

    // Load user data
    userId = prefs.getInt('user_id') ?? 1;
    userName = prefs.getString('user_name') ?? 'User';

    setState(() {});

    // After loading user data, fetch today's attendance from server
    _fetchTodayAttendance();
  }

  // Snackbar helper
  void _showSnack(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
  }

  // Show GPS alert dialog
  Future<void> _showGpsAlert() async {
    return showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext context) {
        return AlertDialog(
          title: const Row(
            children: [
              Icon(Icons.location_off, color: Colors.red),
              SizedBox(width: 8),
              Text('GPS is Turned Off'),
            ],
          ),
          content: const Text(
            'GPS location is required to clock in/out. Please turn on location services in your device settings.',
          ),
          actions: <Widget>[
            TextButton(
              child: const Text('Cancel'),
              onPressed: () {
                Navigator.of(context).pop();
              },
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF6366F1),
                foregroundColor: Colors.white,
              ),
              child: const Text('Open Settings'),
              onPressed: () async {
                Navigator.of(context).pop();
                // Open location settings
                await Geolocator.openLocationSettings();
              },
            ),
          ],
        );
      },
    );
  }

  Future<void> _saveTodayToPrefs() async {
    final prefs = await SharedPreferences.getInstance();
    final todayStr = DateTime.now().toIso8601String().substring(
      0,
      10,
    ); // e.g. 2025-12-08

    await prefs.setString('today_date', todayStr);
    await prefs.setString('today_clock_in', clockInTime ?? '');
    await prefs.setString('today_clock_out', clockOutTime ?? '');
    await prefs.setBool('today_marked', isTodayMarked);
    await prefs.setBool('today_synced', isTodaySynced);
  }

  Future<void> _loadTodayFromPrefs() async {
    final prefs = await SharedPreferences.getInstance();
    final savedDate = prefs.getString('today_date');
    final todayStr = DateTime.now().toIso8601String().substring(
      0,
      10,
    ); // e.g. 2025-12-08

    if (savedDate == todayStr) {
      setState(() {
        final inStr = prefs.getString('today_clock_in') ?? '';
        final outStr = prefs.getString('today_clock_out') ?? '';
        clockInTime = inStr.isEmpty ? null : inStr;
        clockOutTime = outStr.isEmpty ? null : outStr;
        isTodayMarked = prefs.getBool('today_marked') ?? false;
        isTodaySynced = prefs.getBool('today_synced') ?? false;
      });
    } else {
      // naya din -> reset
      setState(() {
        clockInTime = null;
        clockOutTime = null;
        isTodayMarked = false;
        isTodaySynced = false;
      });
    }
  }

  // Show break selection dialog
  Future<String?> _showBreakDialog(String type) async {
    // Check if user just came back from lunch (last punch was 'in' with reason 'lunch')
    final isAfterLunch =
        type == 'out' && lastPunchType == 'in' && lastPunchReason == 'lunch';

    return showDialog<String>(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext context) {
        return AlertDialog(
          title: Text('Clock $type - Select Type'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: type == 'out'
                ? isAfterLunch
                      ? [
                          // After lunch break, only show Shift End
                          ListTile(
                            leading: const Icon(
                              Icons.schedule,
                              color: Colors.blue,
                            ),
                            title: const Text('Shift End'),
                            subtitle: const Text('End of work shift'),
                            onTap: () => Navigator.pop(context, 'shift_end'),
                          ),
                        ]
                      : [
                          // Clock OUT options (before lunch or normal clock out)
                          ListTile(
                            leading: const Icon(
                              Icons.check_circle_outline,
                              color: Colors.green,
                            ),
                            title: const Text('Normal'),
                            subtitle: const Text('Regular clock out'),
                            onTap: () => Navigator.pop(context, 'shift_start'),
                          ),
                          ListTile(
                            leading: const Icon(
                              Icons.lunch_dining,
                              color: Colors.orange,
                            ),
                            title: const Text('Lunch Break'),
                            subtitle: const Text('Clock out for lunch'),
                            onTap: () => Navigator.pop(context, 'lunch'),
                          ),
                          ListTile(
                            leading: const Icon(
                              Icons.schedule,
                              color: Colors.blue,
                            ),
                            title: const Text('Shift End'),
                            subtitle: const Text('End of work shift'),
                            onTap: () => Navigator.pop(context, 'shift_end'),
                          ),
                        ]
                : [
                    // Clock IN options (for multiple punches)
                    ListTile(
                      leading: const Icon(
                        Icons.check_circle_outline,
                        color: Colors.green,
                      ),
                      title: const Text('Normal'),
                      subtitle: const Text('Regular clock in'),
                      onTap: () => Navigator.pop(context, 'shift_start'),
                    ),
                    ListTile(
                      leading: const Icon(
                        Icons.lunch_dining,
                        color: Colors.orange,
                      ),
                      title: const Text('Lunch Break'),
                      subtitle: const Text('Clock in after lunch'),
                      onTap: () => Navigator.pop(context, 'lunch'),
                    ),
                    ListTile(
                      leading: const Icon(Icons.coffee, color: Colors.brown),
                      title: const Text('Tea Break'),
                      subtitle: const Text('Clock in after tea/coffee'),
                      onTap: () => Navigator.pop(context, 'tea'),
                    ),
                  ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Cancel'),
            ),
          ],
        );
      },
    );
  }

  // Handle clock with specific reason (for End Lunch button)
  Future<void> handleClockWithReason(String type, String reason) async {
    // Check total punches limit before allowing clock in/out
    if (todayPunchesCount >= totalPunches) {
      _showSnack(
        "Maximum punches limit reached for today ($totalPunches punches). Cannot clock $type.",
      );
      return;
    }

    // Process the clock in/out with the provided reason (no dialog)
    await _processClock(type, reason);
  }

  Future<void> handleClock(String type) async {
    // Check total punches limit before allowing clock in/out
    if (todayPunchesCount >= totalPunches) {
      _showSnack(
        "Maximum punches limit reached for today ($totalPunches punches). Cannot clock $type.",
      );
      return;
    }

    String reason = 'shift_start'; // Default reason

    // Only show break dialog if:
    // 1. Clocking OUT (always show dialog)
    // 2. Clocking IN when already clocked in (multiple punches - show dialog)
    // Don't show dialog for first clock IN of the day
    if (type == 'out' || (type == 'in' && clockInTime != null)) {
      final selectedReason = await _showBreakDialog(type);
      if (selectedReason == null) {
        // User cancelled
        return;
      }
      reason = selectedReason;
    }

    // Process the clock in/out
    await _processClock(type, reason);
  }

  // Common method to process clock in/out
  Future<void> _processClock(String type, String reason) async {
    final now = DateTime.now();
    final nowIso = now.toIso8601String();
    final timeStr =
        "${now.hour.toString().padLeft(2, '0')}:${now.minute.toString().padLeft(2, '0')}";

    // Validate shift conditions for clock IN
    if (type == 'in' && shiftStartTime != null && shiftStartTime!.isNotEmpty) {
      try {
        final startParts = shiftStartTime!.split(':');
        if (startParts.length >= 2) {
          final today = DateTime(now.year, now.month, now.day);
          final startHour = int.parse(startParts[0]);
          final startMin = int.parse(startParts[1]);
          final shiftStart = today.add(
            Duration(hours: startHour, minutes: startMin),
          );

          // Check early clock in window
          final earlyClockInStart = shiftStart.subtract(
            Duration(minutes: earlyClockInBefore),
          );
          if (now.isBefore(earlyClockInStart)) {
            _showSnack(
              "Too early! You can clock in only $earlyClockInBefore minutes before shift start.",
            );
            return;
          }
        }
      } catch (e) {
        // If validation fails, continue anyway
      }
    }

    // Show loading state immediately - display time but keep in pending
    setState(() {
      isLoading = true;
      pendingClockType = type;
      pendingClockTime = timeStr;
      // Update UI to show pending time (will be confirmed after success)
      if (type == 'in') {
        clockInTime = timeStr; // Show immediately
      } else {
        clockOutTime = timeStr; // Show immediately
      }
      isTodayMarked = false; // Keep as false until confirmed
    });

    try {
      // 1) Get Device ID (fast, cached)
      final deviceId = await DeviceHelper.getDeviceId();

      // 2) Check if GPS is enabled first
      final isGpsEnabled = await Geolocator.isLocationServiceEnabled();
      if (!isGpsEnabled) {
        // Revert pending state
        setState(() {
          isLoading = false;
          pendingClockType = null;
          pendingClockTime = null;
          if (type == 'in') clockInTime = null;
          if (type == 'out') clockOutTime = null;
          isTodayMarked = false;
        });
        await _saveTodayToPrefs();

        // Show alert dialog to enable GPS
        await _showGpsAlert();
        return;
      }

      // 3) FRESH GPS LOCATION CHECK (Always check current location - no cache)
      Position? position;
      bool insideOffice = false;

      // Try to get fresh GPS location with retry (up to 2 attempts)
      LocationResult? locResult;
      int retryCount = 0;
      const maxRetries = 2;

      while (locResult == null && retryCount < maxRetries) {
        locResult = await LocationHelper.getFreshLocationFast();
        if (locResult == null && retryCount < maxRetries - 1) {
          // Wait a bit before retry
          await Future.delayed(const Duration(seconds: 1));
        }
        retryCount++;
      }

      if (locResult != null) {
        position = locResult.position;
        insideOffice = locResult.inside;
      } else {
        // GPS failed after retries - show error and don't allow
        setState(() {
          isLoading = false;
          pendingClockType = null;
          pendingClockTime = null;
          if (type == 'in') clockInTime = null;
          if (type == 'out') clockOutTime = null;
          isTodayMarked = false;
        });
        _showSnack(
          "Could not verify location. Please ensure GPS is enabled and try again.",
        );
        await _saveTodayToPrefs();
        return;
      }

      // Reject if outside office area
      if (!insideOffice) {
        setState(() {
          isLoading = false;
          pendingClockType = null;
          pendingClockTime = null;
          if (type == 'in') clockInTime = null;
          if (type == 'out') clockOutTime = null;
          isTodayMarked = false;
        });
        _showSnack("You are outside office area. Clock-$type not allowed.");
        await _saveTodayToPrefs();
        return;
      }

      // 4) Network check (don't wait for location)
      final conn = await Connectivity().checkConnectivity();
      final isOnline =
          conn.isNotEmpty && !conn.contains(ConnectivityResult.none);

      // 5) Request body
      final body = {
        "user_id": userId.toString(),
        "type": type, // 'in' / 'out'
        "time": nowIso,
        "device_id": deviceId,
        "lat": position.latitude.toString(),
        "lng": position.longitude.toString(),
        "working_from": workingFrom,
        "reason": reason, // 'shift_start', 'lunch', 'tea', 'shift_end'
      };

      // 6) Check if online - attendance only works with internet
      if (!isOnline) {
        setState(() {
          isLoading = false;
          pendingClockType = null;
          pendingClockTime = null;
          if (type == 'in') clockInTime = null;
          if (type == 'out') clockOutTime = null;
          isTodayMarked = false;
        });
        await _saveTodayToPrefs();
        _showSnack(
          "No internet connection. Please connect to internet to mark attendance.",
        );
        return;
      }

      // 7) Submit attendance to server
      try {
        final res = await http
            .post(Uri.parse("$baseUrl/clock.php"), body: body)
            .timeout(const Duration(seconds: 10));

        if (res.statusCode == 200) {
          final data = jsonDecode(res.body);

          if (data['status'] == 'success') {
            // ✅ SUCCESS - Confirm the clock in/out
            setState(() {
              isLoading = false;
              pendingClockType = null;
              pendingClockTime = null;
              isTodayMarked = true; // Now confirmed
              isTodaySynced = true;
              todayPunchesCount++; // Increment punch count
              lastPunchType = type; // Update last punch type
              lastPunchReason = reason; // Update last punch reason

              // Update clock in/out times for display
              if (type == 'in') {
                clockInTime = timeStr;
              }
              if (type == 'out') {
                clockOutTime = timeStr; // Update to latest clock out
              }
            });

            // Trigger immediate UI update for progress circle after clock in
            if (type == 'in') {
              // Force a rebuild to show the progress circle
              if (mounted) {
                setState(() {});
              }
            }
            await _saveTodayToPrefs();

            // Refresh today's attendance to get updated punch count and accurate times from server
            _fetchTodayAttendance();

            _showSnack("Attendance marked ($type)");
            return;
          } else {
            // Server rejected (device / geofence)
            setState(() {
              isLoading = false;
              pendingClockType = null;
              pendingClockTime = null;
              if (type == 'in') clockInTime = null;
              if (type == 'out') clockOutTime = null;
              isTodayMarked = false;
            });
            await _saveTodayToPrefs();
            _showSnack(data['msg'] ?? "Server error. Please try again.");
          }
        } else {
          setState(() {
            isLoading = false;
            pendingClockType = null;
            pendingClockTime = null;
            if (type == 'in') clockInTime = null;
            if (type == 'out') clockOutTime = null;
            isTodayMarked = false;
          });
          await _saveTodayToPrefs();
          _showSnack("Server error (${res.statusCode}). Please try again.");
        }
      } catch (e) {
        setState(() {
          isLoading = false;
          pendingClockType = null;
          pendingClockTime = null;
          if (type == 'in') clockInTime = null;
          if (type == 'out') clockOutTime = null;
          isTodayMarked = false;
        });
        await _saveTodayToPrefs();
        _showSnack(
          "Network error. Please check your connection and try again.",
        );
      }
    } catch (e) {
      // Error occurred - revert everything
      setState(() {
        isLoading = false;
        pendingClockType = null;
        pendingClockTime = null;
        if (type == 'in') clockInTime = null;
        if (type == 'out') clockOutTime = null;
        isTodayMarked = false;
      });
      await _saveTodayToPrefs();
      _showSnack("Could not mark attendance. Please try again.");
    }
  }

  // Fetch today's attendance from server
  Future<void> _fetchTodayAttendance() async {
    try {
      final conn = await Connectivity().checkConnectivity();
      if (conn.contains(ConnectivityResult.none) || conn.isEmpty) return;

      final todayStr = DateTime.now().toIso8601String().substring(
        0,
        10,
      ); // YYYY-MM-DD
      final res = await http.get(
        Uri.parse(
          "$baseUrl/get_today_attendance.php?user_id=$userId&date=$todayStr",
        ),
      );

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);

        // Check if account is inactive
        if (data['inactive'] == true ||
            data['msg']?.toString().contains('inactive') == true) {
          // Account is inactive - logout and redirect to login
          await _logoutUser(
            "Account is inactive. Please contact administrator.",
          );
          return;
        }

        if (data['status'] == 'success') {
          setState(() {
            // Update UI with server data
            clockInTime = data['clock_in'];
            clockOutTime = data['clock_out'];
            todayPunchesCount = data['total_punches_today'] ?? 0;

            // Determine last punch type and reason from logs
            final logs = data['logs'] as List<dynamic>?;
            if (logs != null && logs.isNotEmpty) {
              // Store all logs for time calculation
              todayLogs = logs
                  .map(
                    (log) => {
                      'type': log['type'] as String,
                      'time': log['time'] as String,
                      'reason': log['reason'] as String? ?? 'shift_start',
                    },
                  )
                  .toList();

              final lastLog = logs.last;
              lastPunchType = lastLog['type'] as String?;
              lastPunchReason = lastLog['reason'] as String?;
            } else {
              todayLogs = [];
              lastPunchType = null;
              lastPunchReason = null;
            }

            // If we have attendance, mark as synced
            if (clockInTime != null || clockOutTime != null) {
              isTodaySynced = true;
              isTodayMarked = true;
            }
          });

          // Save to prefs for offline access
          await _saveTodayToPrefs();
        }
      }
    } catch (_) {
      // Ignore errors, use prefs data as fallback
    }
  }

  Future<void> _fetchUserShift() async {
    try {
      final conn = await Connectivity().checkConnectivity();
      if (conn.contains(ConnectivityResult.none) || conn.isEmpty) return;

      final res = await http.get(
        Uri.parse("$baseUrl/get_user_shift.php?user_id=$userId"),
      );

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);

        // Check if account is inactive
        if (data['inactive'] == true ||
            data['msg']?.toString().contains('inactive') == true) {
          // Account is inactive - logout and redirect to login
          await _logoutUser(
            "Account is inactive. Please contact administrator.",
          );
          return;
        }

        if (data['status'] == 'success') {
          setState(() {
            shiftName = data['shift_name'] ?? 'Office';
            shiftStartTime = data['start_time'] ?? '';
            shiftEndTime = data['end_time'] ?? '';
            totalPunches = data['total_punches'] ?? 4;
            earlyClockInBefore = data['early_clock_in_before'] ?? 0;
            lateMarkAfter = data['late_mark_after'] ?? 30;
            halfDayAfter = data['half_day_after'] ?? 270;
            lunchStartTime = data['lunch_start'];
            lunchEndTime = data['lunch_end'];
          });
        }
      }
    } catch (_) {
      // Set default if fetch fails
      setState(() {
        shiftName = 'Office';
      });
    }
  }

  // Logout user and clear session
  Future<void> _logoutUser(String message) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('is_logged_in', false);
    await prefs.remove('user_id');
    await prefs.remove('user_name');

    if (mounted) {
      _showSnack(message);
      Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(builder: (context) => const LoginScreen()),
        (route) => false, // Remove all previous routes
      );
    }
  }

  // Refresh all data (for pull-to-refresh)
  Future<void> _refreshData() async {
    await Future.wait([
      _fetchTodayAttendance(),
      _fetchUserShift(),
      _fetchOfficeLocation(),
    ]);
  }

  Future<void> _fetchOfficeLocation() async {
    try {
      final conn = await Connectivity().checkConnectivity();
      if (conn.contains(ConnectivityResult.none) || conn.isEmpty) return;

      final res = await http.get(Uri.parse("$baseUrl/get_office_location.php"));

      if (res.statusCode == 200) {
        final data = jsonDecode(res.body);
        if (data['status'] == 'success') {
          final prefs = await SharedPreferences.getInstance();

          // Parse lat, lng, radius (can be double or int from JSON)
          final lat = (data['lat'] is num)
              ? (data['lat'] as num).toDouble()
              : 0.0;
          final lng = (data['lng'] is num)
              ? (data['lng'] as num).toDouble()
              : 0.0;
          final radius = (data['radius'] is num)
              ? (data['radius'] as num).toDouble()
              : 150.0;

          await prefs.setDouble('office_lat', lat);
          await prefs.setDouble('office_lng', lng);
          await prefs.setDouble('office_radius', radius);

          // LocationHelper ko update karo
          await LocationHelper.loadFromPrefs();
        }
      }
    } catch (_) {
      // ignore errors silently, cached values use honge
    }
  }

  // --------- HELPER METHODS FOR NEW UI ----------

  String _formattedTodayFull() {
    final now = DateTime.now();
    const months = [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "May",
      "Jun",
      "Jul",
      "Aug",
      "Sep",
      "Oct",
      "Nov",
      "Dec",
    ];
    const weekdayNames = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
    final dayName = weekdayNames[now.weekday % 7];
    return "$dayName, ${now.day.toString().padLeft(2, '0')} ${months[now.month - 1]}";
  }

  // Calculate progress segments for visual display
  // Blue starts from shift start, pauses at lunch out, resumes after lunch in
  Map<String, double> _getProgressSegments() {
    final isOnLunchBreak = clockOutTime != null && lastPunchReason == 'lunch';

    if (shiftStartTime == null ||
        shiftStartTime!.isEmpty ||
        shiftEndTime == null ||
        shiftEndTime!.isEmpty ||
        clockInTime == null) {
      return {'beforeLunch': 0.0, 'lunch': 0.0, 'afterLunch': 0.0};
    }

    try {
      final now = DateTime.now();
      final today = DateTime(now.year, now.month, now.day);
      final startParts = shiftStartTime!.split(':');
      final endParts = shiftEndTime!.split(':');

      if (startParts.length < 2 || endParts.length < 2) {
        return {'beforeLunch': 0.0, 'lunch': 0.0, 'afterLunch': 0.0};
      }

      final startHour = int.parse(startParts[0]);
      final startMin = int.parse(startParts[1]);
      final endHour = int.parse(endParts[0]);
      final endMin = int.parse(endParts[1]);
      final shiftStart = today.add(
        Duration(hours: startHour, minutes: startMin),
      );
      final shiftEnd = today.add(Duration(hours: endHour, minutes: endMin));
      final totalShiftMinutes = shiftEnd.difference(shiftStart).inMinutes;

      // Parse clock-in time
      final clockInParts = clockInTime!.split(':');
      if (clockInParts.length < 2) {
        return {'beforeLunch': 0.0, 'lunch': 0.0, 'afterLunch': 0.0};
      }
      final clockInHour = int.parse(clockInParts[0]);
      final clockInMin = int.parse(clockInParts[1]);
      final clockInDateTime = today.add(
        Duration(hours: clockInHour, minutes: clockInMin),
      );

      // Find lunch break in logs
      DateTime? lunchOutTime;
      DateTime? lunchInTime;
      for (int i = 0; i < todayLogs.length; i++) {
        if (todayLogs[i]['type'] == 'out' &&
            todayLogs[i]['reason'] == 'lunch' &&
            i < todayLogs.length - 1 &&
            todayLogs[i + 1]['type'] == 'in') {
          try {
            lunchOutTime = DateTime.parse(todayLogs[i]['time']);
            lunchInTime = DateTime.parse(todayLogs[i + 1]['time']);
            break;
          } catch (e) {
            // Skip invalid times
          }
        }
      }

      double beforeLunchProgress = 0.0;
      double lunchProgress = 0.0;
      double afterLunchProgress = 0.0;

      // Progress starts from shift start time (not clock-in)
      // Blue segment represents elapsed time from shift start, updates in real-time
      if (lunchOutTime != null && lunchInTime != null) {
        // Has lunch break

        // Blue segment: Time from shift start to lunch out (frozen at lunch out)
        // Only show progress if clocked in before or at lunch out
        if (clockInDateTime.isBefore(lunchOutTime) ||
            clockInDateTime.isAtSameMomentAs(lunchOutTime)) {
          final elapsedToLunchOut = lunchOutTime
              .difference(shiftStart)
              .inMinutes;
          if (elapsedToLunchOut > 0) {
            beforeLunchProgress = elapsedToLunchOut / totalShiftMinutes;
          }
        }

        // Yellow segment: Lunch break - grows in real-time from lunch out to now (if still on lunch) or lunch in (if lunch ended)
        if (isOnLunchBreak) {
          // Still on lunch break - yellow grows from lunch out to now
          final lunchElapsed = now.difference(lunchOutTime).inMinutes;
          if (lunchElapsed > 0) {
            lunchProgress = lunchElapsed / totalShiftMinutes;
          }
        } else {
          // Lunch ended - yellow is frozen at lunch duration
          final lunchDuration = lunchInTime.difference(lunchOutTime).inMinutes;
          if (lunchDuration > 0) {
            lunchProgress = lunchDuration / totalShiftMinutes;
          }
        }

        // Blue segment: Work after lunch - grows in real-time from lunch in to now
        if (isOnLunchBreak) {
          // On lunch break - no work after lunch yet
          afterLunchProgress = 0.0;
        } else if (clockOutTime != null && lastPunchReason != 'lunch') {
          // Shift ended - blue after lunch is frozen at clock out
          final clockOutParts = clockOutTime!.split(':');
          if (clockOutParts.length >= 2) {
            final clockOutHour = int.parse(clockOutParts[0]);
            final clockOutMin = int.parse(clockOutParts[1]);
            final clockOutDateTime = today.add(
              Duration(hours: clockOutHour, minutes: clockOutMin),
            );
            final elapsedAfterLunch = clockOutDateTime
                .difference(lunchInTime)
                .inMinutes;
            if (elapsedAfterLunch > 0) {
              afterLunchProgress = elapsedAfterLunch / totalShiftMinutes;
            }
          }
        } else {
          // Still working after lunch - blue grows in real-time from lunch in to now
          final elapsedAfterLunch = now.difference(lunchInTime).inMinutes;
          if (elapsedAfterLunch > 0) {
            afterLunchProgress = elapsedAfterLunch / totalShiftMinutes;
          }
        }
      } else {
        // No lunch break - only blue segment fills from shift start
        if (isOnLunchBreak && clockOutTime != null) {
          // On lunch break but not in logs yet - blue frozen at clock out, yellow grows
          final clockOutParts = clockOutTime!.split(':');
          if (clockOutParts.length >= 2) {
            final clockOutHour = int.parse(clockOutParts[0]);
            final clockOutMin = int.parse(clockOutParts[1]);
            final clockOutDateTime = today.add(
              Duration(hours: clockOutHour, minutes: clockOutMin),
            );
            // Blue frozen at clock out
            if (clockInDateTime.isBefore(clockOutDateTime) ||
                clockInDateTime.isAtSameMomentAs(clockOutDateTime)) {
              final elapsedToClockOut = clockOutDateTime
                  .difference(shiftStart)
                  .inMinutes;
              if (elapsedToClockOut >= 0) {
                beforeLunchProgress = elapsedToClockOut / totalShiftMinutes;
              }
            }
            // Yellow grows from clock out to now
            final lunchElapsed = now.difference(clockOutDateTime).inMinutes;
            if (lunchElapsed > 0) {
              lunchProgress = lunchElapsed / totalShiftMinutes;
            }
          }
        } else {
          // No lunch - blue grows in real-time from shift start to now
          // Progress based on shift start, only if clocked in
          if (clockInDateTime.isBefore(now) ||
              clockInDateTime.isAtSameMomentAs(now)) {
            final elapsedMinutes = now.difference(shiftStart).inMinutes;
            if (elapsedMinutes >= 0) {
              beforeLunchProgress = elapsedMinutes / totalShiftMinutes;
            }
          }
        }
      }

      // Ensure values are within bounds
      beforeLunchProgress = beforeLunchProgress.clamp(0.0, 1.0);
      lunchProgress = lunchProgress.clamp(0.0, 1.0);
      afterLunchProgress = afterLunchProgress.clamp(0.0, 1.0);

      return {
        'beforeLunch': beforeLunchProgress,
        'lunch': lunchProgress,
        'afterLunch': afterLunchProgress,
      };
    } catch (e) {
      return {'beforeLunch': 0.0, 'lunch': 0.0, 'afterLunch': 0.0};
    }
  }

  double _shiftProgress() {
    // If clocked out for shift end (not lunch), show 100%
    // Only show 100% if clocked out AND last punch was not for lunch
    if (clockOutTime != null && lastPunchReason != 'lunch') {
      return 1.0;
    }

    // If clocked in (or clocked out for lunch - still in progress), check shift times
    // For lunch break, we're still in the middle of shift, so continue showing progress
    final isOnLunchBreak = clockOutTime != null && lastPunchReason == 'lunch';
    if (clockInTime != null && (clockOutTime == null || isOnLunchBreak)) {
      if (shiftStartTime == null ||
          shiftStartTime!.isEmpty ||
          shiftEndTime == null ||
          shiftEndTime!.isEmpty) {
        return 0.02; // Start at 2% when clocked in
      }
    } else {
      // Not clocked in yet
      if (shiftStartTime == null ||
          shiftStartTime!.isEmpty ||
          shiftEndTime == null ||
          shiftEndTime!.isEmpty) {
        return 0.0;
      }
    }

    try {
      // Parse shift start and end times
      final startParts = shiftStartTime!.split(':');
      final endParts = shiftEndTime!.split(':');

      if (startParts.length < 2 || endParts.length < 2) {
        // If parsing fails but clocked in (or on lunch break), show minimum progress
        final isOnLunchBreak =
            clockOutTime != null && lastPunchReason == 'lunch';
        if (clockInTime != null && (clockOutTime == null || isOnLunchBreak)) {
          return 0.02; // Start at 2% when clocked in
        }
        return 0.0;
      }

      final now = DateTime.now();
      final today = DateTime(now.year, now.month, now.day);

      final startHour = int.parse(startParts[0]);
      final startMin = int.parse(startParts[1]);
      final endHour = int.parse(endParts[0]);
      final endMin = int.parse(endParts[1]);

      final shiftStart = today.add(
        Duration(hours: startHour, minutes: startMin),
      );
      final shiftEnd = today.add(Duration(hours: endHour, minutes: endMin));

      // Handle next day shifts
      if (shiftEnd.isBefore(shiftStart)) {
        final shiftEndNextDay = shiftEnd.add(const Duration(days: 1));

        if (now.isBefore(shiftStart)) {
          // If clocked in (or on lunch break) but shift hasn't started, show minimum progress
          final isOnLunchBreak =
              clockOutTime != null && lastPunchReason == 'lunch';
          if (clockInTime != null && (clockOutTime == null || isOnLunchBreak)) {
            return 0.02; // Start at 2% when clocked in
          }
          return 0.0; // Shift hasn't started yet
        }

        if (now.isAfter(shiftEndNextDay)) {
          return 1.0; // Shift has ended
        }

        // Only calculate progress if user has clocked in (or is on lunch break)
        final isOnLunchBreak =
            clockOutTime != null && lastPunchReason == 'lunch';
        if (clockInTime == null) {
          return 0.0; // No progress if not clocked in
        }

        // Parse clock-in time to calculate actual worked time
        try {
          final clockInParts = clockInTime!.split(':');
          if (clockInParts.length >= 2) {
            final clockInHour = int.parse(clockInParts[0]);
            final clockInMin = int.parse(clockInParts[1]);
            final clockInDateTime = today.add(
              Duration(hours: clockInHour, minutes: clockInMin),
            );

            // Handle next day for clock-in if needed
            DateTime clockInDateTimeAdjusted = clockInDateTime;
            if (clockInDateTime.isBefore(shiftStart)) {
              clockInDateTimeAdjusted = clockInDateTime.add(
                const Duration(days: 1),
              );
            }

            // Calculate progress based on actual worked time
            final totalShiftMinutes = shiftEndNextDay
                .difference(shiftStart)
                .inMinutes;

            int workedMinutes;
            if (isOnLunchBreak && clockOutTime != null) {
              // On lunch break: calculate progress up to clock-out time (work done before lunch)
              final clockOutParts = clockOutTime!.split(':');
              if (clockOutParts.length >= 2) {
                final clockOutHour = int.parse(clockOutParts[0]);
                final clockOutMin = int.parse(clockOutParts[1]);
                final clockOutDateTime = today.add(
                  Duration(hours: clockOutHour, minutes: clockOutMin),
                );
                // Handle next day for clock-out if needed
                DateTime clockOutDateTimeAdjusted = clockOutDateTime;
                if (clockOutDateTime.isBefore(clockInDateTimeAdjusted)) {
                  clockOutDateTimeAdjusted = clockOutDateTime.add(
                    const Duration(days: 1),
                  );
                }
                workedMinutes = clockOutDateTimeAdjusted
                    .difference(clockInDateTimeAdjusted)
                    .inMinutes;
              } else {
                workedMinutes = now
                    .difference(clockInDateTimeAdjusted)
                    .inMinutes;
              }
            } else {
              // Not on lunch break: calculate from clock-in to now
              workedMinutes = now.difference(clockInDateTimeAdjusted).inMinutes;
            }

            // Ensure worked minutes is not negative
            if (workedMinutes < 0) {
              return 0.02; // Minimum 2% if clock-in time is in the future
            }

            double progress = workedMinutes / totalShiftMinutes;
            if (progress > 1.0) progress = 1.0;
            if (progress < 0.0) progress = 0.0;

            // Ensure minimum progress of 0.02 (2%) when clocked in
            if (progress < 0.02) {
              progress = 0.02;
            }

            return progress;
          }
        } catch (e) {
          // If parsing fails, return minimum progress
          return 0.02;
        }

        // Fallback: calculate from shift start if clock-in parsing fails
        final totalShiftMinutes = shiftEndNextDay
            .difference(shiftStart)
            .inMinutes;
        final elapsedMinutes = now.difference(shiftStart).inMinutes;

        double progress = elapsedMinutes / totalShiftMinutes;
        if (progress > 1.0) progress = 1.0;
        if (progress < 0.0) progress = 0.0;

        // Ensure minimum progress of 0.02 (2%)
        if (progress < 0.02) {
          progress = 0.02;
        }

        return progress;
      }

      // Normal same-day shift
      if (now.isBefore(shiftStart)) {
        // If clocked in (or on lunch break) but shift hasn't started, show minimum progress
        final isOnLunchBreak =
            clockOutTime != null && lastPunchReason == 'lunch';
        if (clockInTime != null && (clockOutTime == null || isOnLunchBreak)) {
          return 0.02; // Start at 2% when clocked in
        }
        return 0.0; // Shift hasn't started yet
      }

      if (now.isAfter(shiftEnd)) {
        return 1.0; // Shift has ended
      }

      // Only calculate progress if user has clocked in (or is on lunch break)
      final isOnLunchBreak = clockOutTime != null && lastPunchReason == 'lunch';
      if (clockInTime == null) {
        return 0.0; // No progress if not clocked in
      }

      // If on lunch break, calculate progress up to clock-out time (not current time)
      // Otherwise calculate from clock-in to now
      try {
        final clockInParts = clockInTime!.split(':');
        if (clockInParts.length >= 2) {
          final clockInHour = int.parse(clockInParts[0]);
          final clockInMin = int.parse(clockInParts[1]);
          final clockInDateTime = today.add(
            Duration(hours: clockInHour, minutes: clockInMin),
          );

          // Calculate progress based on actual worked time
          final totalShiftMinutes = shiftEnd.difference(shiftStart).inMinutes;

          int workedMinutes;
          if (isOnLunchBreak && clockOutTime != null) {
            // On lunch break: calculate progress up to clock-out time (work done before lunch)
            final clockOutParts = clockOutTime!.split(':');
            if (clockOutParts.length >= 2) {
              final clockOutHour = int.parse(clockOutParts[0]);
              final clockOutMin = int.parse(clockOutParts[1]);
              final clockOutDateTime = today.add(
                Duration(hours: clockOutHour, minutes: clockOutMin),
              );
              workedMinutes = clockOutDateTime
                  .difference(clockInDateTime)
                  .inMinutes;
            } else {
              workedMinutes = now.difference(clockInDateTime).inMinutes;
            }
          } else {
            // Not on lunch break: calculate from clock-in to now
            workedMinutes = now.difference(clockInDateTime).inMinutes;
          }

          // Ensure worked minutes is not negative
          if (workedMinutes < 0) {
            return 0.02; // Minimum 2% if clock-in time is in the future
          }

          double progress = workedMinutes / totalShiftMinutes;
          if (progress > 1.0) progress = 1.0;
          if (progress < 0.0) progress = 0.0;

          // Ensure minimum progress of 0.02 (2%) when clocked in
          if (progress < 0.02) {
            progress = 0.02;
          }

          return progress;
        }
      } catch (e) {
        // If parsing fails, return minimum progress
        return 0.02;
      }

      // Fallback: calculate from shift start if clock-in parsing fails
      final totalShiftMinutes = shiftEnd.difference(shiftStart).inMinutes;
      final elapsedMinutes = now.difference(shiftStart).inMinutes;

      double progress = elapsedMinutes / totalShiftMinutes;
      if (progress > 1.0) progress = 1.0;
      if (progress < 0.0) progress = 0.0;

      // Ensure minimum progress of 0.02 (2%)
      if (progress < 0.02) {
        progress = 0.02;
      }

      return progress;
    } catch (e) {
      // If parsing fails, return default based on clock in status
      if (clockInTime != null) {
        // Only show 100% if clocked out and NOT on lunch break
        if (clockOutTime != null && lastPunchReason != 'lunch') {
          return 1.0;
        }
        return 0.02; // Start at 2% when clocked in
      }
      return 0.0;
    }
  }

  // Build segmented progress circle showing blue (work), yellow (lunch), blue (work after lunch)
  Widget _buildSegmentedProgressCircle() {
    final segments = _getProgressSegments();
    final beforeLunch = segments['beforeLunch'] ?? 0.0;
    final lunch = segments['lunch'] ?? 0.0;
    final afterLunch = segments['afterLunch'] ?? 0.0;

    // If no lunch break, use simple progress
    if (lunch == 0) {
      return CircularProgressIndicator(
        value: _shiftProgress(),
        strokeWidth: 10,
        backgroundColor: const Color(0xFFE5E7EB),
        valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFF6366F1)),
      );
    }

    // Use CustomPaint to draw segmented circle with tooltip on yellow segment
    return Tooltip(
      message: lunch > 0 ? "Lunch" : "",
      preferBelow: false,
      child: CustomPaint(
        size: const Size(100, 100),
        painter: _SegmentedProgressPainter(
          beforeLunch: beforeLunch,
          lunch: lunch,
          afterLunch: afterLunch,
        ),
      ),
    );
  }

  // Calculate actual worked hours (excluding lunch break)
  Map<String, dynamic> _calculateWorkHours() {
    int totalWorkedMinutes = 0;
    int lunchBreakMinutes = 0;

    // If we have clockInTime but no logs yet, calculate from clockInTime directly
    if (todayLogs.isEmpty && clockInTime != null) {
      try {
        final now = DateTime.now();
        final today = DateTime(now.year, now.month, now.day);
        final clockInParts = clockInTime!.split(':');
        if (clockInParts.length >= 2) {
          final clockInHour = int.parse(clockInParts[0]);
          final clockInMin = int.parse(clockInParts[1]);
          final clockInDateTime = today.add(
            Duration(hours: clockInHour, minutes: clockInMin),
          );

          if (clockOutTime != null && lastPunchReason == 'lunch') {
            // On lunch break - calculate work up to lunch clock out
            final clockOutParts = clockOutTime!.split(':');
            if (clockOutParts.length >= 2) {
              final clockOutHour = int.parse(clockOutParts[0]);
              final clockOutMin = int.parse(clockOutParts[1]);
              final clockOutDateTime = today.add(
                Duration(hours: clockOutHour, minutes: clockOutMin),
              );
              final workBeforeLunch = clockOutDateTime
                  .difference(clockInDateTime)
                  .inMinutes;
              if (workBeforeLunch >= 0) {
                totalWorkedMinutes = workBeforeLunch;
              }
            }
          } else if (clockOutTime != null && lastPunchReason != 'lunch') {
            // Shift ended - calculate from clock in to clock out
            final clockOutParts = clockOutTime!.split(':');
            if (clockOutParts.length >= 2) {
              final clockOutHour = int.parse(clockOutParts[0]);
              final clockOutMin = int.parse(clockOutParts[1]);
              final clockOutDateTime = today.add(
                Duration(hours: clockOutHour, minutes: clockOutMin),
              );
              final workedMinutes = clockOutDateTime
                  .difference(clockInDateTime)
                  .inMinutes;
              if (workedMinutes >= 0) {
                totalWorkedMinutes = workedMinutes;
              }
            }
          } else {
            // Still working - calculate from clock in to now
            final currentSessionMinutes = now
                .difference(clockInDateTime)
                .inMinutes;
            if (currentSessionMinutes >= 0) {
              totalWorkedMinutes = currentSessionMinutes;
            }
          }
        }
      } catch (e) {
        // If parsing fails, return 0
      }
      return {
        'workedHours': totalWorkedMinutes ~/ 60,
        'workedMinutes': totalWorkedMinutes % 60,
        'lunchMinutes': lunchBreakMinutes,
      };
    }

    // If logs are empty but we have clockInTime, we already handled it above
    // This check is for when logs are empty and clockInTime is also null
    if (todayLogs.isEmpty) {
      // If clocked in but no logs, try to calculate from clockInTime
      if (clockInTime != null) {
        try {
          final now = DateTime.now();
          final today = DateTime(now.year, now.month, now.day);
          final clockInParts = clockInTime!.split(':');
          if (clockInParts.length >= 2) {
            final clockInHour = int.parse(clockInParts[0]);
            final clockInMin = int.parse(clockInParts[1]);
            final clockInDateTime = today.add(
              Duration(hours: clockInHour, minutes: clockInMin),
            );
            final currentSessionMinutes = now
                .difference(clockInDateTime)
                .inMinutes;
            if (currentSessionMinutes >= 0) {
              totalWorkedMinutes = currentSessionMinutes;
            }
          }
        } catch (e) {
          // If parsing fails, return 0
        }
        return {
          'workedHours': totalWorkedMinutes ~/ 60,
          'workedMinutes': totalWorkedMinutes % 60,
          'lunchMinutes': lunchBreakMinutes,
        };
      }
      return {'workedHours': 0, 'workedMinutes': 0, 'lunchMinutes': 0};
    }

    // Process logs in pairs: IN-OUT = work session, OUT-IN = break
    for (int i = 0; i < todayLogs.length; i++) {
      final log = todayLogs[i];

      try {
        if (log['type'] == 'in' && i < todayLogs.length - 1) {
          final nextLog = todayLogs[i + 1];
          if (nextLog['type'] == 'out') {
            // IN followed by OUT = work session
            final inTime = DateTime.parse(log['time']);
            final outTime = DateTime.parse(nextLog['time']);
            final duration = outTime.difference(inTime).inMinutes;

            // Only count as work if:
            // 1. Duration is positive
            // 2. OUT is not for lunch (lunch breaks are handled separately)
            if (duration > 0 && nextLog['reason'] != 'lunch') {
              totalWorkedMinutes += duration;
            }
          }
        } else if (log['type'] == 'out' && i < todayLogs.length - 1) {
          final nextLog = todayLogs[i + 1];
          if (nextLog['type'] == 'in') {
            // OUT followed by IN = break period
            final outTime = DateTime.parse(log['time']);
            final inTime = DateTime.parse(nextLog['time']);
            final breakDuration = inTime.difference(outTime).inMinutes;

            // Only count as lunch if ALL conditions are met:
            // 1. OUT reason is explicitly 'lunch' (not 'shift_start', 'tea', etc.)
            // 2. IN reason is 'lunch' (coming back from lunch)
            // 3. Break duration is positive (inTime is after outTime)
            // 4. Break duration is reasonable (between 15 minutes and 3 hours)
            if (log['reason'] == 'lunch' &&
                nextLog['reason'] == 'lunch' &&
                breakDuration > 0 &&
                breakDuration >= 15 &&
                breakDuration <= 180) {
              lunchBreakMinutes += breakDuration;
            }
          }
        }
      } catch (e) {
        // Skip invalid time parsing
      }
    }

    // Handle current in-progress session
    // Always prioritize clockInTime when available (most reliable and immediate)
    final isOnLunchBreak = clockOutTime != null && lastPunchReason == 'lunch';

    if (clockInTime != null && (clockOutTime == null || isOnLunchBreak)) {
      try {
        final now = DateTime.now();
        final today = DateTime(now.year, now.month, now.day);
        final clockInParts = clockInTime!.split(':');
        if (clockInParts.length >= 2) {
          final clockInHour = int.parse(clockInParts[0]);
          final clockInMin = int.parse(clockInParts[1]);
          final clockInDateTime = today.add(
            Duration(hours: clockInHour, minutes: clockInMin),
          );

          int currentSessionMinutes = 0;
          if (isOnLunchBreak && clockOutTime != null) {
            // On lunch break - calculate work up to lunch clock-out (frozen)
            final clockOutParts = clockOutTime!.split(':');
            if (clockOutParts.length >= 2) {
              final clockOutHour = int.parse(clockOutParts[0]);
              final clockOutMin = int.parse(clockOutParts[1]);
              final clockOutDateTime = today.add(
                Duration(hours: clockOutHour, minutes: clockOutMin),
              );
              currentSessionMinutes = clockOutDateTime
                  .difference(clockInDateTime)
                  .inMinutes;
            }
          } else {
            // Not on lunch break - calculate from clock-in to now
            // This handles both initial clock-in and post-lunch work
            currentSessionMinutes = now.difference(clockInDateTime).inMinutes;
          }

          if (currentSessionMinutes >= 0) {
            // Check if this session is already counted in logs
            bool alreadyCounted = false;
            if (todayLogs.isNotEmpty && lastPunchType == 'in') {
              try {
                final lastInLog = todayLogs.lastWhere(
                  (log) => log['type'] == 'in',
                  orElse: () => {},
                );
                if (lastInLog.isNotEmpty) {
                  final logTime = DateTime.parse(lastInLog['time']);
                  // If log time is very close to clockInTime, it's already counted
                  final timeDiff =
                      (logTime.difference(clockInDateTime).inMinutes).abs();
                  if (timeDiff < 5) {
                    // Already counted from logs, don't add again
                    alreadyCounted = true;
                  }
                }
              } catch (e) {
                // If parsing fails, add the time
              }
            }

            if (!alreadyCounted) {
              // Add current session time (even if 0, it will show 0h 0m correctly)
              totalWorkedMinutes += currentSessionMinutes;
            }
          }
        }
      } catch (e) {
        // If parsing fails, try to use logs as fallback
        if (lastPunchType == 'in' && todayLogs.isNotEmpty) {
          try {
            final lastInLog = todayLogs.lastWhere(
              (log) => log['type'] == 'in',
              orElse: () => {},
            );
            // After lunch, lastInLog reason will be 'lunch', but we still need to count it
            if (lastInLog.isNotEmpty) {
              final inTime = DateTime.parse(lastInLog['time']);
              final now = DateTime.now();
              final currentSessionMinutes = now.difference(inTime).inMinutes;
              if (currentSessionMinutes >= 0) {
                totalWorkedMinutes += currentSessionMinutes;
              }
            }
          } catch (e) {
            // Skip if error
          }
        }
      }
    } else if (lastPunchType == 'in' && todayLogs.isNotEmpty) {
      // Fallback: use logs if clockInTime is not available
      try {
        final lastInLog = todayLogs.lastWhere(
          (log) => log['type'] == 'in',
          orElse: () => {},
        );
        // Count post-lunch work even if reason is 'lunch' (lunch-in)
        if (lastInLog.isNotEmpty) {
          final inTime = DateTime.parse(lastInLog['time']);
          final now = DateTime.now();
          final currentSessionMinutes = now.difference(inTime).inMinutes;
          if (currentSessionMinutes >= 0) {
            totalWorkedMinutes += currentSessionMinutes;
          }
        }
      } catch (e) {
        // Skip if error
      }
    }

    return {
      'workedHours': totalWorkedMinutes ~/ 60,
      'workedMinutes': totalWorkedMinutes % 60,
      'lunchMinutes': lunchBreakMinutes,
    };
  }

  String _shiftDurationLabel() {
    // Show loading state
    if (isLoading && pendingClockType != null) {
      return pendingClockType == 'in' ? "Marking..." : "Marking...";
    }

    // Calculate elapsed time from shift start
    if (shiftStartTime != null && shiftStartTime!.isNotEmpty) {
      try {
        final startParts = shiftStartTime!.split(':');
        if (startParts.length >= 2) {
          final now = DateTime.now();
          final today = DateTime(now.year, now.month, now.day);

          final startHour = int.parse(startParts[0]);
          final startMin = int.parse(startParts[1]);
          final shiftStart = today.add(
            Duration(hours: startHour, minutes: startMin),
          );

          // Calculate elapsed time from shift start
          int elapsedMinutes = 0;
          if (now.isAfter(shiftStart)) {
            elapsedMinutes = now.difference(shiftStart).inMinutes;
          } else if (now.isBefore(shiftStart)) {
            // Shift hasn't started yet
            return "0h 00m\nNot Started";
          }

          final elapsedHours = elapsedMinutes ~/ 60;
          final elapsedMins = elapsedMinutes % 60;

          // Also calculate worked hours (excluding lunch) if clocked in
          if (clockInTime != null) {
            final workHours = _calculateWorkHours();
            final workedH = workHours['workedHours'] as int;
            final workedM = workHours['workedMinutes'] as int;

            if (clockOutTime != null) {
              // Completed - show worked hours only (lunch shown in tooltip on yellow segment)
              return "${workedH}h ${workedM}m";
            } else {
              // In progress - show actual worked time from clock in (not shift start)
              return "${workedH}h ${workedM}m";
            }
          } else {
            // Not clocked in yet - always show "Not Started"
            return "0h 00m\nNot Started";
          }
        }
      } catch (e) {
        // Fallback to worked hours if shift time parsing fails
      }
    }

    // Fallback: Show based on clock in/out
    if (clockInTime == null) return "0h 00m";

    // Calculate actual worked hours
    final workHours = _calculateWorkHours();
    final workedH = workHours['workedHours'] as int;
    final workedM = workHours['workedMinutes'] as int;

    if (clockOutTime == null) {
      // In progress - show worked hours only (lunch shown in tooltip on yellow segment)
      return "${workedH}h ${workedM}m\nIn progress";
    }

    // Completed - show worked hours only (lunch shown in tooltip on yellow segment)
    return "${workedH}h ${workedM}m";
  }

  _WeekDayInfo _weekDayInfo(int index) {
    final now = DateTime.now();
    final weekdayIndex = now.weekday % 7; // Sun = 0
    final startOfWeek = now.subtract(Duration(days: weekdayIndex));
    final date = startOfWeek.add(Duration(days: index));

    const weekdays = ["SUN", "MON", "TUE", "WED", "THU", "FRI", "SAT"];
    final label = weekdays[date.weekday % 7];
    final dateLabel =
        "${date.day.toString().padLeft(2, '0')} ${_monthShort(date.month)}";

    final isToday =
        date.day == now.day && date.month == now.month && date.year == now.year;

    return _WeekDayInfo(label, dateLabel, isToday);
  }

  String _monthShort(int m) {
    const months = [
      "JAN",
      "FEB",
      "MAR",
      "APR",
      "MAY",
      "JUN",
      "JUL",
      "AUG",
      "SEP",
      "OCT",
      "NOV",
      "DEC",
    ];
    return months[m - 1];
  }

  // Convert 24-hour time to Indian 12-hour format (AM/PM)
  // Format: "7:30 PM" or "10:00 AM" (no leading zero for hours)
  String _convertToIndianTime(String time24) {
    try {
      final parts = time24.split(':');
      if (parts.length >= 2) {
        int hour = int.parse(parts[0]);
        int minute = int.parse(parts[1]);

        String period = 'AM';
        if (hour >= 12) {
          period = 'PM';
          if (hour > 12) {
            hour -= 12;
          }
        }
        if (hour == 0) {
          hour = 12;
        }

        // Format: hour without leading zero, minute with leading zero
        return "$hour:${minute.toString().padLeft(2, '0')} $period";
      }
    } catch (e) {
      // If parsing fails, return original
    }
    return time24;
  }

  // Get shift display text with time in brackets (Indian format)
  String _getShiftDisplayText() {
    final name = shiftName ?? "Office";
    if (shiftStartTime != null &&
        shiftEndTime != null &&
        shiftStartTime!.isNotEmpty &&
        shiftEndTime!.isNotEmpty) {
      final startTimeIndian = _convertToIndianTime(shiftStartTime!);
      final endTimeIndian = _convertToIndianTime(shiftEndTime!);
      return "$name ($startTimeIndian - $endTimeIndian)";
    }
    return name;
  }

  // ---------------- NEW UI ----------------

  @override
  Widget build(BuildContext context) {
    final clockInLabel = clockInTime != null
        ? _convertToIndianTime(clockInTime!)
        : "Missing";
    final clockOutLabel = clockOutTime != null
        ? _convertToIndianTime(clockOutTime!)
        : "Missing";

    // Check if all punches are completed (based on total_punches limit)
    final isAllPunchesCompleted = todayPunchesCount >= totalPunches;

    // Determine if we should show Clock In or Clock Out based on last punch type
    // If last punch was 'in', show Clock Out. If last punch was 'out' or no punch, show Clock In
    final shouldShowClockIn = lastPunchType == null || lastPunchType == 'out';

    // Check if last clock out was for lunch break
    final isLunchBreakOut =
        lastPunchType == 'out' && lastPunchReason == 'lunch';

    String mainButtonText;
    VoidCallback? mainButtonOnTap;

    if (isAllPunchesCompleted) {
      // All punches completed - show disabled completed button
      mainButtonText = "Completed";
      mainButtonOnTap = null;
    } else if (isLunchBreakOut) {
      // Show "End Lunch" button if last clock out was for lunch
      mainButtonText = "End Lunch";
      mainButtonOnTap = isLoading
          ? null
          : () => handleClockWithReason('in', 'lunch');
    } else if (shouldShowClockIn) {
      // Show Clock In if: no clock in yet, or we've clocked out and can do more punches
      mainButtonText = "Clock In";
      mainButtonOnTap = isLoading ? null : () => handleClock('in');
    } else {
      // Show Clock Out if we're clocked in but haven't clocked out yet
      mainButtonText = "Clock Out";
      mainButtonOnTap = isLoading ? null : () => handleClock('out');
    }

    return Scaffold(
      backgroundColor: const Color(0xFFF4F5FB),
      appBar: AppBar(
        title: const Text("Attendance"),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () async {
              final navigator = Navigator.of(context);
              final prefs = await SharedPreferences.getInstance();
              await prefs.setBool('is_logged_in', false);
              if (!mounted) return;
              navigator.pushReplacement(
                MaterialPageRoute(builder: (context) => const LoginScreen()),
              );
            },
            tooltip: "Logout",
          ),
        ],
      ),
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: _refreshData,
          child: SingleChildScrollView(
            physics:
                const AlwaysScrollableScrollPhysics(), // Enable scroll even when content fits
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // GREETING
                const Text(
                  "Hello,",
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.w400),
                ),
                const SizedBox(height: 4),
                Text(
                  "${userName.toUpperCase()}!",
                  style: const TextStyle(
                    fontSize: 22,
                    letterSpacing: 0.5,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 16),

                // BIG SHIFT CARD
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(24),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.05),
                        blurRadius: 12,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // top row shift/date
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                "SHIFT TODAY",
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Colors.grey,
                                  letterSpacing: 0.5,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                _getShiftDisplayText(),
                                style: const TextStyle(
                                  fontSize: 14,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
                          ),
                          Row(
                            children: [
                              const Icon(
                                Icons.calendar_today_outlined,
                                size: 18,
                                color: Colors.grey,
                              ),
                              const SizedBox(width: 6),
                              Text(
                                _formattedTodayFull(),
                                style: const TextStyle(
                                  fontSize: 13,
                                  color: Colors.grey,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),

                      const SizedBox(height: 16),

                      // middle row circle + in/out
                      Row(
                        children: [
                          SizedBox(
                            height: 100,
                            width: 100,
                            child: Stack(
                              alignment: Alignment.center,
                              children: [
                                SizedBox(
                                  height: 100,
                                  width: 100,
                                  child: isLoading && pendingClockType != null
                                      ? CircularProgressIndicator(
                                          // Animated loading (no value = infinite animation)
                                          strokeWidth: 10,
                                          backgroundColor: const Color(
                                            0xFFE5E7EB,
                                          ),
                                          valueColor:
                                              const AlwaysStoppedAnimation<
                                                Color
                                              >(Color(0xFF6366F1)),
                                        )
                                      : _buildSegmentedProgressCircle(),
                                ),
                                Column(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Text(
                                      _shiftDurationLabel(),
                                      textAlign: TextAlign.center,
                                      style: const TextStyle(
                                        fontSize: 14,
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      clockOutTime != null
                                          ? "Completed"
                                          : "In Progress",
                                      style: const TextStyle(
                                        fontSize: 11,
                                        color: Colors.grey,
                                      ),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 20),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  "Today's Shift",
                                  style: TextStyle(
                                    fontSize: 14,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                                const SizedBox(height: 12),
                                Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        const Text(
                                          "CLOCK IN",
                                          style: TextStyle(
                                            fontSize: 12,
                                            color: Colors.grey,
                                          ),
                                        ),
                                        const SizedBox(height: 4),
                                        Text(
                                          clockInLabel,
                                          style: const TextStyle(
                                            fontSize: 16,
                                            fontWeight: FontWeight.w600,
                                          ),
                                        ),
                                      ],
                                    ),
                                    Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.end,
                                      children: [
                                        const Text(
                                          "CLOCK OUT",
                                          style: TextStyle(
                                            fontSize: 12,
                                            color: Colors.grey,
                                          ),
                                        ),
                                        const SizedBox(height: 4),
                                        Text(
                                          clockOutLabel,
                                          style: const TextStyle(
                                            fontSize: 16,
                                            fontWeight: FontWeight.w600,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),

                      const SizedBox(height: 16),

                      // main button
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          onPressed: mainButtonOnTap,
                          style: ElevatedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(vertical: 14),
                            backgroundColor: mainButtonOnTap == null
                                ? Colors.grey.shade300
                                : const Color(0xFFEF4444),
                            foregroundColor: mainButtonOnTap == null
                                ? Colors.grey.shade700
                                : Colors.white,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(14),
                            ),
                            elevation: 0,
                          ),
                          child: Text(
                            mainButtonText,
                            style: const TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 20),

                // QUICK ACTIONS
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: const [
                    _QuickAction(
                      icon: Icons.flight_takeoff,
                      label: "Apply\nLeave",
                    ),
                    _QuickAction(
                      icon: Icons.home_work_outlined,
                      label: "Apply\nWFH",
                    ),
                    _QuickAction(
                      icon: Icons.receipt_long_outlined,
                      label: "View\nPayslip",
                    ),
                    _QuickAction(
                      icon: Icons.confirmation_num_outlined,
                      label: "Raise\nTicket",
                    ),
                    _QuickAction(
                      icon: Icons.account_balance_wallet_outlined,
                      label: "Leave\nBalance",
                    ),
                  ],
                ),

                const SizedBox(height: 24),

                // WISH THEM
                const Text(
                  "Wish them",
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                ),
                const SizedBox(height: 12),
                SizedBox(
                  height: 80,
                  child: ListView(
                    scrollDirection: Axis.horizontal,
                    children: const [
                      _WishChip(label: "Nilesh", tag: "3 YRS", date: "09 Dec"),
                      _WishChip(label: "Yankit", tag: "NEW", date: "10 Dec"),
                      _WishChip(label: "Nilesh", tag: "B'DAY", date: "20 Dec"),
                      _WishChip(label: "Chand", tag: "1 YR", date: "01 Jan"),
                    ],
                  ),
                ),

                const SizedBox(height: 24),

                // WEEKLY TIME LOG
                const Text(
                  "Weekly Time Log",
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                ),
                const SizedBox(height: 12),
                SizedBox(
                  height: 90,
                  child: ListView.separated(
                    scrollDirection: Axis.horizontal,
                    itemBuilder: (context, index) {
                      final dayInfo = _weekDayInfo(index);
                      return _DayChip(
                        label: dayInfo.label,
                        date: dayInfo.dateLabel,
                        isToday: dayInfo.isToday,
                      );
                    },
                    separatorBuilder: (_, _) => const SizedBox(width: 8),
                    itemCount: 7,
                  ),
                ),

                const SizedBox(height: 8),

                if (isLoading) const LinearProgressIndicator(),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

// ---------- SMALL HELPER UI CLASSES ----------

class _QuickAction extends StatelessWidget {
  final IconData icon;
  final String label;
  const _QuickAction({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: Colors.white,
            shape: BoxShape.circle,
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.05),
                blurRadius: 6,
                offset: const Offset(0, 3),
              ),
            ],
          ),
          child: Icon(icon, size: 22),
        ),
        const SizedBox(height: 6),
        Text(
          label,
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 11),
        ),
      ],
    );
  }
}

class _WishChip extends StatelessWidget {
  final String label;
  final String tag;
  final String date;
  const _WishChip({required this.label, required this.tag, required this.date});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 70,
      margin: const EdgeInsets.only(right: 12),
      child: Column(
        children: [
          CircleAvatar(
            radius: 22,
            backgroundColor: const Color(0xFFE5E7EB),
            child: Text(
              label.isNotEmpty ? label[0].toUpperCase() : "?",
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
          ),
          const SizedBox(height: 4),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
            decoration: BoxDecoration(
              color: const Color(0xFF6366F1),
              borderRadius: BorderRadius.circular(999),
            ),
            child: Text(
              tag,
              style: const TextStyle(fontSize: 9, color: Colors.white),
            ),
          ),
          const SizedBox(height: 2),
          Text(date, style: const TextStyle(fontSize: 10, color: Colors.grey)),
        ],
      ),
    );
  }
}

class _DayChip extends StatelessWidget {
  final String label;
  final String date;
  final bool isToday;
  const _DayChip({
    required this.label,
    required this.date,
    required this.isToday,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 70,
      padding: const EdgeInsets.symmetric(vertical: 8),
      decoration: BoxDecoration(
        color: isToday ? const Color(0xFFE0E7FF) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isToday ? const Color(0xFF6366F1) : Colors.grey.shade300,
          width: 1,
        ),
      ),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color: isToday ? const Color(0xFF4F46E5) : Colors.black,
            ),
          ),
          Text(date, style: const TextStyle(fontSize: 10, color: Colors.grey)),
          Container(
            height: 4,
            width: 40,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(999),
              color: isToday ? const Color(0xFF22C55E) : Colors.grey.shade300,
            ),
          ),
        ],
      ),
    );
  }
}

class _WeekDayInfo {
  final String label;
  final String dateLabel;
  final bool isToday;
  _WeekDayInfo(this.label, this.dateLabel, this.isToday);
}

// ------------------- DEVICE HELPER (DEVICE-LOCK) -------------------

class DeviceHelper {
  static String? _cachedId;

  static Future<String> getDeviceId() async {
    if (_cachedId != null) return _cachedId!;

    final plugin = DeviceInfoPlugin();
    final info = await plugin.androidInfo;

    // Android 12+ and above: androidId removed
    String id = info.id;
    if (id.isEmpty) {
      id = info.fingerprint;
      if (id.isEmpty) {
        id = _randomId();
      }
    }

    _cachedId = id;
    return id;
  }

  static String _randomId() {
    final rand = Random();
    return List.generate(16, (_) => rand.nextInt(10)).join();
  }
}

// ------------------- LOCATION HELPER (GEOFENCE) -------------------

class LocationResult {
  final Position position;
  final bool inside;
  LocationResult(this.position, this.inside);
}

class LocationHelper {
  // OFFICE LOCATION (cached from server, fallback defaults)
  static double _officeLat = 20.420399;
  static double _officeLng = 72.870863;
  static double _radiusM = 150.0; // meters (default 150m)
  static bool _prefsLoaded = false;

  // Note: No caching - always use fresh GPS for security

  // SharedPreferences se load karo (app start pe)
  static Future<void> loadFromPrefs() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      _officeLat = prefs.getDouble('office_lat') ?? 20.420399;
      _officeLng = prefs.getDouble('office_lng') ?? 72.870863;
      _radiusM = prefs.getDouble('office_radius') ?? 150.0;
      _prefsLoaded = true;
    } catch (_) {
      // fallback to defaults
      _prefsLoaded = true;
    }
  }

  // Get fresh GPS location with optimized speed (always fresh, no cache)
  static Future<LocationResult?> getFreshLocationFast() async {
    bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      return null;
    }

    LocationPermission permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        return null;
      }
    }
    if (permission == LocationPermission.deniedForever) {
      return null;
    }

    // Load office location prefs if not loaded
    if (!_prefsLoaded) {
      await loadFromPrefs();
    }

    Position? pos;

    // Strategy: Start with medium accuracy (faster), fallback to low if needed
    try {
      // Try medium accuracy first (good balance of speed and accuracy)
      // Increased timeout to 6 seconds for better reliability
      pos = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.medium,
        timeLimit: const Duration(
          seconds: 6,
        ), // 6 second timeout for better reliability
      );
    } catch (e) {
      // If medium fails/times out, try low accuracy (faster but less accurate)
      // Increased timeout to 5 seconds for better reliability
      try {
        pos = await Geolocator.getCurrentPosition(
          desiredAccuracy: LocationAccuracy.low,
          timeLimit: const Duration(
            seconds: 5,
          ), // 5 second timeout for better reliability
        );
      } catch (_) {
        // If both fail, return null (don't use stale location)
        return null;
      }
    }

    // Check if inside office geofence
    final dist = Geolocator.distanceBetween(
      pos.latitude,
      pos.longitude,
      _officeLat,
      _officeLng,
    );

    final inside = dist <= _radiusM;
    return LocationResult(pos, inside);
  }

  // Legacy method (kept for backward compatibility but not using cache)
  static Future<LocationResult> getLocationAndCheck() async {
    final result = await getFreshLocationFast();
    if (result == null) {
      throw "Location unavailable";
    }
    return result;
  }

  // Optimized version with timeout
  static Future<LocationResult?> getLocationAndCheckWithTimeout({
    int timeoutSeconds = 5,
  }) async {
    try {
      return await getLocationAndCheck().timeout(
        Duration(seconds: timeoutSeconds),
      );
    } on TimeoutException {
      return null;
    } catch (_) {
      return null;
    }
  }
}

// ------------------- SEGMENTED PROGRESS PAINTER -------------------

class _SegmentedProgressPainter extends CustomPainter {
  final double beforeLunch;
  final double lunch;
  final double afterLunch;

  _SegmentedProgressPainter({
    required this.beforeLunch,
    required this.lunch,
    required this.afterLunch,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final radius = (size.width - 10) / 2;
    final strokeWidth = 10.0;

    // Draw background circle
    final backgroundPaint = Paint()
      ..color = const Color(0xFFE5E7EB)
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth
      ..strokeCap = StrokeCap.round;
    canvas.drawCircle(center, radius, backgroundPaint);

    // Draw blue segment: Work before lunch
    if (beforeLunch > 0) {
      final beforeLunchPaint = Paint()
        ..color = const Color(0xFF6366F1)
        ..style = PaintingStyle.stroke
        ..strokeWidth = strokeWidth
        ..strokeCap = StrokeCap.round;
      final beforeLunchSweep = 2 * 3.14159 * beforeLunch;
      canvas.drawArc(
        Rect.fromCircle(center: center, radius: radius),
        -3.14159 / 2, // Start from top (12 o'clock)
        beforeLunchSweep,
        false,
        beforeLunchPaint,
      );
    }

    // Draw yellow segment: Lunch break
    if (lunch > 0) {
      final lunchPaint = Paint()
        ..color = const Color(0xFFFFA500)
        ..style = PaintingStyle.stroke
        ..strokeWidth = strokeWidth
        ..strokeCap = StrokeCap.round;
      final lunchSweep = 2 * 3.14159 * lunch;
      final lunchStart = -3.14159 / 2 + (2 * 3.14159 * beforeLunch);
      canvas.drawArc(
        Rect.fromCircle(center: center, radius: radius),
        lunchStart,
        lunchSweep,
        false,
        lunchPaint,
      );
    }

    // Draw blue segment: Work after lunch
    if (afterLunch > 0) {
      final afterLunchPaint = Paint()
        ..color = const Color(0xFF6366F1)
        ..style = PaintingStyle.stroke
        ..strokeWidth = strokeWidth
        ..strokeCap = StrokeCap.round;
      final afterLunchSweep = 2 * 3.14159 * afterLunch;
      final afterLunchStart =
          -3.14159 / 2 + (2 * 3.14159 * (beforeLunch + lunch));
      canvas.drawArc(
        Rect.fromCircle(center: center, radius: radius),
        afterLunchStart,
        afterLunchSweep,
        false,
        afterLunchPaint,
      );
    }
  }

  @override
  bool shouldRepaint(_SegmentedProgressPainter oldDelegate) {
    return oldDelegate.beforeLunch != beforeLunch ||
        oldDelegate.lunch != lunch ||
        oldDelegate.afterLunch != afterLunch;
  }
}

// ------------------- LOCAL DB (OFFLINE STORAGE) -------------------

class LocalDB {
  static Database? _db;

  static Future<void> init() async {
    final dbPath = await getDatabasesPath();
    final path = p.join(dbPath, 'offline_attendance.db');

    _db = await openDatabase(
      path,
      version: 2,
      onCreate: (db, version) async {
        await db.execute('''
          CREATE TABLE offline_attendance (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            type TEXT,
            time TEXT,
            device_id TEXT,
            latitude REAL,
            longitude REAL,
            working_from TEXT,
            synced INTEGER
          )
        ''');
      },
      onUpgrade: (db, oldVersion, newVersion) async {
        if (oldVersion < 2) {
          await db.execute(
            'ALTER TABLE offline_attendance ADD COLUMN working_from TEXT',
          );
        }
      },
    );
  }

  static Future<void> saveOffline({
    required int userId,
    required String type,
    required String time,
    required String deviceId,
    required double lat,
    required double lng,
    required String workingFrom,
  }) async {
    await _db!.insert('offline_attendance', {
      'user_id': userId,
      'type': type,
      'time': time,
      'device_id': deviceId,
      'latitude': lat,
      'longitude': lng,
      'working_from': workingFrom,
      'synced': 0,
    });
  }

  static Future<List<Map<String, dynamic>>> getPending() async {
    return await _db!.query('offline_attendance', where: 'synced = 0');
  }

  static Future<void> markAllSynced() async {
    await _db!.update('offline_attendance', {'synced': 1}, where: 'synced = 0');
  }
}

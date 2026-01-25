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
import '../services/location_service.dart';
import '../widgets/bottom_banner.dart';
import '../widgets/wish_them_section.dart';
import '../widgets/fade_slide_transition.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:geolocator/geolocator.dart';
import '../services/geofence_service.dart';

class AttendanceScreen extends StatefulWidget {
  const AttendanceScreen({super.key});

  @override
  State<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends State<AttendanceScreen>
    with SingleTickerProviderStateMixin {
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
  bool _isOffline = false;
  late StreamSubscription<List<ConnectivityResult>> _connectivitySubscription;
  StreamSubscription? _geofenceSubscription;
  StreamSubscription? _geofenceStatusSubscription;
  String _autoAttendanceStatus = 'initializing';

  @override
  void initState() {
    super.initState();
    _loadUserData();
    _fetchWishes();
    _progressController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 800),
    );
    _progressAnimation = AlwaysStoppedAnimation(_progress);
    _progressController.addListener(() {
      setState(() {});
    });
    _checkInitialConnectivity();
    _connectivitySubscription = Connectivity().onConnectivityChanged.listen(
      _updateConnectionStatus,
    );

    // Listen to auto-attendance events from the background service
    _geofenceSubscription = AppGeofenceService.autoAttendanceEvents.listen((
      event,
    ) {
      if (mounted) {
        _loadAttendanceToday();
      }
    });

    _geofenceStatusSubscription = AppGeofenceService.serviceStatus.listen((
      status,
    ) {
      if (mounted) {
        setState(() {
          _autoAttendanceStatus = status;
        });
      }
    });
  }

  Future<void> _checkInitialConnectivity() async {
    final List<ConnectivityResult> results = await Connectivity()
        .checkConnectivity();
    _updateConnectionStatus(results);
  }

  void _updateConnectionStatus(List<ConnectivityResult> results) {
    if (mounted) {
      setState(() {
        _isOffline = results.contains(ConnectivityResult.none);
      });
      if (!_isOffline) {
        // Re-fetch data if connection restored
        _fetchWishes();
        _loadShiftDetails();
      }
    }
  }

  Future<void> _fetchWishes() async {
    setState(() {
      _wishesLoading = true;
    });
    final list = <WishUser>[];
    try {
      final res = await ClockService.getWishes(
        baseUri: kBaseUri,
        days: 7,
        timeout: const Duration(seconds: 10),
      );
      if (res['success'] == true && res['data'] is List) {
        for (final item in (res['data'] as List)) {
          if (item is Map) {
            final name = (item['name'] ?? '').toString();
            final years = item['years'] != null
                ? '${item['years']} YRS'
                : '1 YRS';
            final date = item['date'] is String
                ? DateTime.tryParse(item['date'])
                : null;
            final dateLabel = date != null
                ? '${date.day.toString().padLeft(2, '0')} ${_shortMonth(date.month)}'
                : (item['date']?.toString() ?? '');
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
            list.add(
              WishUser(
                name: name,
                photo: item['photo']?.toString(),
                years: years,
                date: dateLabel,
                type: wtype,
              ),
            );
          }
        }
      }
    } catch (_) {
      // ignore; we will show retry or empty state
    } finally {
      if (mounted)
        setState(() {
          _wishUsers = list;
          _wishesLoading = false;
        });
    }
  }

  String _shortMonth(int m) {
    const months = [
      'Jan',
      'Feb',
      'Mar',
      'Apr',
      'May',
      'Jun',
      'Jul',
      'Aug',
      'Sep',
      'Oct',
      'Nov',
      'Dec',
    ];
    return months[(m - 1).clamp(0, 11)];
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
    try {
      _connectivitySubscription.cancel();
    } catch (_) {}
    try {
      _geofenceSubscription?.cancel();
    } catch (_) {}
    try {
      _geofenceStatusSubscription?.cancel();
    } catch (_) {}
    super.dispose();
  }

  String _formattedToday() {
    final now = DateTime.now();
    const weekdays = [
      'Sunday',
      'Monday',
      'Tuesday',
      'Wednesday',
      'Thursday',
      'Friday',
      'Saturday',
    ];
    const months = [
      'Jan',
      'Feb',
      'Mar',
      'Apr',
      'May',
      'Jun',
      'Jul',
      'Aug',
      'Sep',
      'Oct',
      'Nov',
      'Dec',
    ];
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
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            child: const Text('Log out'),
          ),
        ],
      ),
    );

    if (doLogout == true) {
      final prefs = await SharedPreferences.getInstance();
      await prefs.clear();
      // Stop geofencing service on logout
      await AppGeofenceService().stop();
      if (!mounted) return;
      Navigator.of(
        context,
      ).pushReplacement(MaterialPageRoute(builder: (_) => const LoginScreen()));
    }
  }

  Future<void> _loadUserData() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      userName = prefs.getString('user_name') ?? 'Sachin Mandal';
      // Reverted: Prefer the stored employee id (DB PK) if available.
      // clock.php now handles the resolution correctly, so we should send the most specific ID we have.
      userId = prefs.getInt('employee_id') ?? prefs.getInt('user_id') ?? 0;
    });
    // load shift details after we have user id
    _loadShiftDetails();
  }

  Future<void> _loadShiftDetails() async {
    // Re-read SharedPreferences to get the freshest employee_id (avoid stale state).
    final prefs = await SharedPreferences.getInstance();
    final prefEmployeeId =
        prefs.getInt('employee_id') ?? prefs.getInt('user_id') ?? 0;
    // (production) do not log prefs
    if (prefEmployeeId <= 0) return;
    // Ensure local state reflects the persisted id
    setState(() {
      userId = prefEmployeeId;
    });

    final info = await ClockService.getUserShift(
      baseUri: kBaseUri,
      userId: prefEmployeeId,
    );
    if (!mounted) return;
    // do not store raw response in production

    if (info.success) {
      setState(() {
        shiftName = info.name.isNotEmpty ? info.name : shiftName;
        workingFrom = info.workingFrom.isNotEmpty
            ? info.workingFrom
            : workingFrom;
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
    // load today's punch status
    _loadAttendanceToday();
  }

  Future<void> _loadAttendanceToday() async {
    if (userId == null || userId == 0) return;

    final now = DateTime.now();
    final dateStr =
        "${now.year}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}";

    final res = await ClockService.getDayAttendance(
      baseUri: kBaseUri,
      userId: userId!,
      date: dateStr,
    );

    if (mounted && res['success'] == true) {
      setState(() {
        isClockedIn = (res['last_punch_type'] == 'in');

        // Helper to format HH:MM from API to local time format
        String? formatTimeString(String? timeStr) {
          if (timeStr == null) return null;
          final parts = timeStr.split(':');
          if (parts.length >= 2) {
            final h = int.tryParse(parts[0]) ?? 0;
            final m = int.tryParse(parts[1]) ?? 0;
            return TimeOfDay(hour: h, minute: m).format(context);
          }
          return timeStr;
        }

        lastClockIn = formatTimeString(res['clock_in']);
        lastClockOut = formatTimeString(res['clock_out']);

        // Update progress based on clocked status
        if (!isClockedIn && lastClockOut != null) {
          _progress = 1.0;
          _progressAnimation = AlwaysStoppedAnimation(_progress);
          _progressController.value = 1.0;
        } else if (isClockedIn) {
          _progress = 0.0;
          _progressAnimation = AlwaysStoppedAnimation(_progress);
          _progressController.reset();
        }
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

    // Step 1: Get device ID (required for every clock in/out)
    final deviceId = await LocationService.getDeviceId();
    if (!mounted) return;

    // Step 2: Check if device is registered
    final deviceStatus = await ClockService.checkDeviceStatus(
      baseUri: kBaseUri,
      userId: userId!,
      deviceId: deviceId,
    );
    if (!mounted) return;

    if (deviceStatus['success'] == true) {
      final status =
          deviceStatus['device_status'] as String? ?? 'not_registered';

      if (status == 'not_registered') {
        // Show registration prompt
        setState(() {
          isLoading = false;
        });
        await _showDeviceRegistrationDialog(deviceId);
        return;
      } else if (status == 'different_device') {
        // Different device registered - block
        setState(() {
          isLoading = false;
        });
        BottomBanner.show(
          context,
          deviceStatus['msg'] ??
              'This account is registered to a different device. Contact HR to reset.',
          success: false,
        );
        return;
      }
      // status == 'registered' - continue with clock
    } else {
      // API error - show message
      setState(() {
        isLoading = false;
      });
      BottomBanner.show(
        context,
        deviceStatus['error'] ?? 'Unable to verify device.',
        success: false,
      );
      return;
    }

    // Step 3: Validate geo-fence and get current location
    final geoResult = await LocationService.validateGeoFence(baseUri: kBaseUri);
    if (!mounted) return;

    if (!geoResult.isWithinFence) {
      setState(() {
        isLoading = false;
      });
      BottomBanner.show(
        context,
        geoResult.errorMessage ?? 'You must be at the office to clock in/out.',
        success: false,
      );
      return;
    }

    // Step 4: Proceed with clock in/out, passing device ID and GPS coordinates
    final now = DateTime.now();
    final result = await ClockService.clockInOut(
      baseUri: kBaseUri,
      userId: userId!,
      type: type,
      deviceId: deviceId,
      lat: geoResult.userLat,
      lng: geoResult.userLng,
    );

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
        _progressAnimation = Tween<double>(begin: _progress, end: 1.0).animate(
          CurvedAnimation(parent: _progressController, curve: Curves.easeInOut),
        );
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

  /// Show dialog to register this device
  Future<void> _showDeviceRegistrationDialog(String deviceId) async {
    final shouldRegister = await showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: const [
            Icon(Icons.phone_android, color: Colors.indigo),
            SizedBox(width: 8),
            Text('Register Device'),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'This device is not registered to your account.',
              style: TextStyle(fontSize: 15),
            ),
            const SizedBox(height: 12),
            const Text(
              'Tap "Register" to bind this device to your account. You will only be able to clock in/out from this device.',
              style: TextStyle(fontSize: 13, color: Colors.grey),
            ),
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: Colors.grey[100],
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                children: [
                  const Icon(Icons.fingerprint, size: 18, color: Colors.grey),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      deviceId.length > 20
                          ? '${deviceId.substring(0, 20)}...'
                          : deviceId,
                      style: const TextStyle(
                        fontSize: 12,
                        fontFamily: 'monospace',
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.indigo,
              foregroundColor: Colors.white,
            ),
            onPressed: () => Navigator.of(ctx).pop(true),
            child: const Text('Register'),
          ),
        ],
      ),
    );

    if (shouldRegister == true) {
      setState(() {
        isLoading = true;
      });

      final result = await ClockService.registerDevice(
        baseUri: kBaseUri,
        userId: userId!,
        deviceId: deviceId,
      );

      if (!mounted) return;
      setState(() {
        isLoading = false;
      });

      if (result['success'] == true) {
        BottomBanner.show(
          context,
          result['msg'] ?? 'Device registered successfully!',
          success: true,
        );
      } else {
        BottomBanner.show(
          context,
          result['error'] ?? 'Failed to register device',
          success: false,
        );
      }
    }
  }

  Future<void> _handleRefresh() async {
    await Future.wait([
      _fetchWishes(),
      _loadShiftDetails(),
      // The WeeklyLogCard handles its own internal state,
      // but we could trigger a rebuild or use a key if needed.
    ]);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: _handleRefresh,
          child: SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(16.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                FadeSlideTransition(
                  delay: const Duration(milliseconds: 0),
                  child: _buildGreetingSection(),
                ),
                const SizedBox(height: 16),
                FadeSlideTransition(
                  delay: const Duration(milliseconds: 100),
                  child: _buildShiftCard(),
                ),
                if (_autoAttendanceStatus ==
                        'BACKGROUND_PERMISSION_NEED_MANUAL' ||
                    _autoAttendanceStatus == 'permission_denied')
                  Padding(
                    padding: const EdgeInsets.only(top: 16),
                    child: _buildPermissionGuide(),
                  ),
                const SizedBox(height: 16),
                FadeSlideTransition(
                  delay: const Duration(milliseconds: 200),
                  child: _buildActionButtons(),
                ),
                const SizedBox(height: 16),
                FadeSlideTransition(
                  delay: const Duration(milliseconds: 300),
                  child: _buildWishesSection(),
                ),
                const SizedBox(height: 16),
                FadeSlideTransition(
                  delay: const Duration(milliseconds: 400),
                  child: WeeklyLogCard(
                    key: UniqueKey(), // Force refresh log card on pull
                    userId: userId ?? 0,
                    baseUri: kBaseUri,
                  ),
                ),
              ],
            ),
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
            const SizedBox(height: 4),
            _buildAutoAttendanceBadge(),
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
              child: Icon(
                Icons.notifications_outlined,
                color: Colors.grey[700],
                size: 20,
              ),
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

  Widget _buildAutoAttendanceBadge() {
    Color color = Colors.grey;
    String label = 'Auto-Attendance: Off';
    IconData icon = Icons.gps_off;

    switch (_autoAttendanceStatus) {
      case 'active':
        color = Colors.green;
        label = 'Auto-Attendance: Active';
        icon = Icons.gps_fixed;
        break;
      case 'permission_denied':
        color = Colors.orange;
        label = 'Auto-Attendance: Needs Permission';
        icon = Icons.location_off;
        break;
      case 'BACKGROUND_PERMISSION_NEED_MANUAL':
        color = Colors.red;
        label = 'Auto-Attendance: Manual action needed';
        icon = Icons.settings;
        break;
      case 'disabled_by_admin':
        color = Colors.grey;
        label = 'Auto-Attendance: Disabled';
        icon = Icons.block;
        break;
      case 'requesting_permissions':
      case 'starting_service':
      case 'initializing':
        color = Colors.blue;
        label = 'Auto-Attendance: Starting...';
        icon = Icons.refresh;
        break;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 12, color: color),
          const SizedBox(width: 4),
          Text(
            label,
            style: TextStyle(
              fontSize: 10,
              color: color,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPermissionGuide() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.red[50],
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.red[200]!),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.warning_amber_rounded, color: Colors.red),
              const SizedBox(width: 8),
              Text(
                'Background Action Required',
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  color: Colors.red[900],
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          const Text(
            'For automatic attendance on Android 15, please:',
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: Colors.black87,
            ),
          ),
          const SizedBox(height: 12),
          _guideItem('1. Click "Open Settings" below'),
          _guideItem('2. Go to "Permissions" > "Location"'),
          _guideItem('3. Select "Allow all the time"'),
          _guideItem('4. Go to "Battery" > Select "Unrestricted"'),
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () => Geolocator.openAppSettings(),
              icon: const Icon(Icons.settings, size: 18),
              label: const Text('Open Settings'),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.red,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                elevation: 0,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _guideItem(String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.check_circle_outline, size: 14, color: Colors.red[400]),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              text,
              style: const TextStyle(fontSize: 12, color: Colors.black87),
            ),
          ),
        ],
      ),
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
                        padding: EdgeInsets.all(
                          4.0,
                        ), // small touch target without extra visual spacing
                        child: Icon(
                          Icons.info_outline,
                          size: 16,
                          color: Colors.grey,
                        ),
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
                        onPressed: (isLoading || _isOffline)
                            ? null
                            : () =>
                                  _clockInOut(type: isClockedIn ? 'out' : 'in'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: isClockedIn
                              ? Colors.red
                              : const Color(0xFF4CAF50),
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
                                _isOffline
                                    ? 'Offline'
                                    : (isClockedIn ? 'Clock Out' : 'Clock In'),
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
                    BottomBanner.show(
                      context,
                      'User not found.',
                      success: false,
                    );
                    return;
                  }
                  final today = DateTime.now();
                  final dateStr =
                      '${today.year.toString().padLeft(4, '0')}-${today.month.toString().padLeft(2, '0')}-${today.day.toString().padLeft(2, '0')}';
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) =>
                          DayLogsScreen(userId: userId!, date: dateStr),
                    ),
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
    final durationMinutes =
        (shiftEnd.hour * 60 + shiftEnd.minute) -
        (shiftStart.hour * 60 + shiftStart.minute);
    final hours = (durationMinutes ~/ 60).abs();
    final minutes = (durationMinutes % 60).abs();

    final renderBox =
        _shiftInfoKey.currentContext?.findRenderObject() as RenderBox?;
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

    _shiftOverlayEntry = OverlayEntry(
      builder: (ctx) {
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
                      const Text(
                        'About Shift',
                        style: TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 6),
                      const SizedBox(height: 8),
                      const SizedBox(height: 8),
                      // Nicely formatted parsed fields
                      Text(
                        'Shift Name: ${shiftName.isNotEmpty ? shiftName : "-"}',
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        'Working From: ${workingFrom.isNotEmpty ? workingFrom : "-"}',
                        style: const TextStyle(color: Colors.white70),
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          const Icon(
                            Icons.play_arrow,
                            size: 14,
                            color: Colors.white70,
                          ),
                          const SizedBox(width: 6),
                          Text(
                            'Start: $start',
                            style: const TextStyle(color: Colors.white),
                          ),
                        ],
                      ),
                      const SizedBox(height: 6),
                      Row(
                        children: [
                          const Icon(
                            Icons.stop,
                            size: 14,
                            color: Colors.white70,
                          ),
                          const SizedBox(width: 6),
                          Text(
                            'End: $end',
                            style: const TextStyle(color: Colors.white),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'Duration: ${hours}h ${minutes}m',
                        style: const TextStyle(color: Colors.white70),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        );
      },
    );

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
                style: TextStyle(fontSize: 11, color: Colors.grey[500]),
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
              if (_isOffline) {
                BottomBanner.show(
                  context,
                  'You are offline. Please connect to internet to apply leave.',
                  success: false,
                );
                return;
              }
              if (userId == null || userId == 0) {
                BottomBanner.show(context, 'User not found.', success: false);
                return;
              }
              Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (_) => ApplyLeaveScreen(employeeId: userId!),
                ),
              );
            },
            child: _buildActionButton(Icons.flight_outlined, 'Apply\nLeave'),
          ),
        ),
        Expanded(
          child: GestureDetector(
            onTap: () {
              if (_isOffline) {
                BottomBanner.show(
                  context,
                  'You are offline. Please connect to internet to view payslip.',
                  success: false,
                );
                return;
              }
            },
            child: _buildActionButton(
              Icons.receipt_long_outlined,
              'View\nPayslip',
            ),
          ),
        ),
        Expanded(
          child: GestureDetector(
            onTap: () {
              if (_isOffline) {
                BottomBanner.show(
                  context,
                  'You are offline. Please connect to internet to raise ticket.',
                  success: false,
                );
                return;
              }
            },
            child: _buildActionButton(
              Icons.confirmation_number_outlined,
              'Raise\nTicket',
            ),
          ),
        ),
        Expanded(
          child: GestureDetector(
            onTap: () {
              if (_isOffline) {
                BottomBanner.show(
                  context,
                  'You are offline. Please connect to internet to view leave history.',
                  success: false,
                );
                return;
              }
              if (userId == null || userId == 0) {
                BottomBanner.show(context, 'User not found.', success: false);
                return;
              }
              Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (_) => LeaveHistoryScreen(employeeId: userId!),
                ),
              );
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
          style: const TextStyle(
            fontSize: 10,
            color: Colors.black87,
            height: 1.2,
            fontWeight: FontWeight.w500,
          ),
        ),
      ],
    );
  }

  Widget _buildWishesSection() {
    if (_wishesLoading) {
      return Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
        ),
        padding: const EdgeInsets.all(12),
        child: Row(
          children: const [
            SizedBox(
              width: 20,
              height: 20,
              child: CircularProgressIndicator(strokeWidth: 2),
            ),
            SizedBox(width: 12),
            Text('Loading wishes...', style: TextStyle(color: Colors.black54)),
          ],
        ),
      );
    }

    if (_wishUsers.isEmpty) {
      return Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
        ),
        padding: const EdgeInsets.all(12),
        child: const Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text('No wishes today', style: TextStyle(color: Colors.black54)),
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

import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'screens/login_screen.dart';
import 'screens/attendance_screen.dart';
import 'widgets/connectivity_wrapper.dart';
import 'services/geofence_service.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Early permission check for already logged-in users
  final prefs = await SharedPreferences.getInstance();
  final bool isLoggedIn = prefs.getBool('is_logged_in') ?? false;

  if (isLoggedIn) {
    // Check if essential permissions are missing
    // We don't want to show complex UI here, just trigger the request
    // AppGeofenceService().initialize() will handle the detailed UI in AttendanceScreen if needed,
    // but let's ensure the requests happen.
    await AppGeofenceService().initialize();
  }

  runApp(MyApp(isLoggedIn: isLoggedIn));
}

class MyApp extends StatelessWidget {
  final bool isLoggedIn;
  const MyApp({super.key, required this.isLoggedIn});

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
      builder: (context, child) => ConnectivityWrapper(child: child!),
      home: isLoggedIn ? const AttendanceScreen() : const LoginScreen(),
    );
  }
}

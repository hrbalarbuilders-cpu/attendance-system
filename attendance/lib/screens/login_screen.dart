import 'package:flutter/material.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'dart:async';
import 'attendance_screen.dart';
import 'package:attendance/config.dart';
import '../services/geofence_service.dart';
import '../services/location_service.dart';
import 'package:flutter/services.dart';
import 'dart:io';
import 'package:permission_handler/permission_handler.dart';

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

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _checkPermissionsAtStartup();
    });
  }

  Future<void> _checkPermissionsAtStartup() async {
    final missing = await LocationService.ensureAllPermissions();
    if (missing != null && mounted) {
      _showPermissionDialog(missing);
    }
  }

  void _showPermissionDialog(String missing) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: const Text('Permissions Required'),
        content: Text(
          'This app requires $missing to function.\n\n'
          '1. Application Info will open\n'
          '2. Tap "Permissions"\n'
          '3. Tap "Location"\n'
          '4. Select "Allow all the time"',
        ),
        actions: [
          TextButton(onPressed: () => exit(0), child: const Text('Exit App')),
          ElevatedButton(
            onPressed: () async {
              Navigator.pop(context);
              // Try to get permissions naturally
              final missing = await LocationService.ensureAllPermissions();
              if (missing != null && mounted) {
                // If still missing, FORCE open settings
                await openAppSettings();
                // Check again to show dialog if they return without fixing
                _checkPermissionsAtStartup();
              }
            },
            child: const Text('Try Again'),
          ),
        ],
      ),
    );
  }

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

    // Final permission check before login
    final missing = await LocationService.ensureAllPermissions();
    if (missing != null) {
      setState(() => _isLoading = false);
      _showPermissionDialog(missing);
      return;
    }

    try {
      final dynamic conn = await Connectivity().checkConnectivity();
      bool noConnection = false;
      if (conn is ConnectivityResult) {
        noConnection = conn == ConnectivityResult.none;
      } else if (conn is List<ConnectivityResult>) {
        noConnection = conn.contains(ConnectivityResult.none);
      }
      if (noConnection) {
        setState(() => _isLoading = false);
        _showSnack("No internet connection. Please connect to internet.");
        return;
      }

      final response = await http
          .post(
            kBaseUri.resolve('login.php'),
            body: {
              'email': _emailController.text.trim(),
              'password': _passwordController.text.trim(),
            },
          )
          .timeout(const Duration(seconds: 10));

      // Debug prints
      print('==== API DEBUG ====');
      print('URL: ${kBaseUri.resolve('login.php')}');
      print('Status: ${response.statusCode}');
      print('Body: ${response.body}');
      print('Body Length: ${response.body.length}');
      print(
        'First 20 bytes (hex): ${response.bodyBytes.take(20).map((b) => b.toRadixString(16).padLeft(2, '0')).join(' ')}',
      );
      print('==================');

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

          // Initialize Geofence service for background attendance
          await AppGeofenceService().initialize();

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

        // Try to extract a useful message from non-200 responses
        String message =
            "Server error (${response.statusCode}). Please try again.";
        try {
          final decoded = jsonDecode(response.body);
          if (decoded is Map && decoded['msg'] is String) {
            message = decoded['msg'] as String;
          }
        } catch (_) {
          // If body is not JSON, keep the generic message
        }

        _showSnack(message);
      }
    } on http.ClientException catch (e) {
      setState(() => _isLoading = false);
      _showSnack("HTTP error: \\${e.message}");
    } on FormatException catch (e) {
      setState(() => _isLoading = false);
      _showSnack("Invalid response format: \\${e.message}");
    } on TimeoutException {
      setState(() => _isLoading = false);
      _showSnack("Request timed out. Please try again.");
    } catch (e) {
      setState(() => _isLoading = false);
      _showSnack("Network error: \\${e.toString()}");
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
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                // Branding/logo
                Container(
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(24),
                    boxShadow: [
                      BoxShadow(
                        color: Color.fromRGBO(0, 0, 0, 0.05),
                        blurRadius: 16,
                        offset: const Offset(0, 8),
                      ),
                    ],
                  ),
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    children: [
                      Image.asset(
                        'assets/logo.png',
                        height: 64,
                        width: 64,
                        errorBuilder: (context, error, stackTrace) =>
                            const FlutterLogo(size: 64),
                      ),
                      const SizedBox(height: 12),
                      Text(
                        'Attendance System',
                        style: Theme.of(context).textTheme.titleLarge?.copyWith(
                          fontWeight: FontWeight.bold,
                          color: Colors.indigo[700],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 32),
                // ...existing code...
                Card(
                  elevation: 3,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 24,
                      vertical: 32,
                    ),
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Text(
                            'Sign in to your account',
                            style: Theme.of(context).textTheme.titleMedium
                                ?.copyWith(fontWeight: FontWeight.w600),
                            textAlign: TextAlign.center,
                          ),
                          const SizedBox(height: 24),
                          TextFormField(
                            controller: _emailController,
                            keyboardType: TextInputType.emailAddress,
                            decoration: InputDecoration(
                              labelText: 'Email',
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                              ),
                              prefixIcon: const Icon(Icons.email),
                              filled: true,
                              fillColor: Colors.grey[50],
                            ),
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return 'Please enter your email';
                              }
                              if (!RegExp(
                                r'^[^@]+@[^@]+\.[^@]+',
                              ).hasMatch(value)) {
                                return 'Enter a valid email';
                              }
                              return null;
                            },
                          ),
                          const SizedBox(height: 16),
                          TextFormField(
                            controller: _passwordController,
                            obscureText: _obscurePassword,
                            decoration: InputDecoration(
                              labelText: 'Password',
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                              ),
                              prefixIcon: const Icon(Icons.lock),
                              filled: true,
                              fillColor: Colors.grey[50],
                              suffixIcon: IconButton(
                                icon: Icon(
                                  _obscurePassword
                                      ? Icons.visibility
                                      : Icons.visibility_off,
                                ),
                                onPressed: () {
                                  setState(() {
                                    _obscurePassword = !_obscurePassword;
                                  });
                                },
                              ),
                            ),
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return 'Please enter your password';
                              }
                              if (value.length < 6) {
                                return 'Password must be at least 6 characters';
                              }
                              return null;
                            },
                          ),
                          const SizedBox(height: 24),
                          SizedBox(
                            height: 48,
                            child: ElevatedButton(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: Colors.indigo,
                                foregroundColor: Colors.white,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                elevation: 0,
                              ),
                              onPressed: _isLoading ? null : _handleLogin,
                              child: _isLoading
                                  ? const SizedBox(
                                      width: 24,
                                      height: 24,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                        color: Colors.white,
                                      ),
                                    )
                                  : const Text(
                                      'Login',
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
              ],
            ),
          ),
        ),
      ),
    );
  }
}

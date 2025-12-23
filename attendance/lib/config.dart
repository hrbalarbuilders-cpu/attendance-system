// Centralized configuration for API endpoints
// Supports overriding at build time with --dart-define=API_BASE_URL=... (compile-time constant)
const String kBaseUrl = String.fromEnvironment(
  'API_BASE_URL',
  defaultValue: 'http://192.168.1.132:8080/attendance/attendance_api',
);
final Uri kBaseUri = Uri.parse(kBaseUrl);

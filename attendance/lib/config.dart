// Centralized configuration for API endpoints
// Supports overriding at build time with --dart-define=API_BASE_URL=... (compile-time constant)
const String kBaseUrl = String.fromEnvironment(
  'API_BASE_URL',
  defaultValue: 'https://demoserver.alwaysdata.net/attendance_api/',
);
final Uri kBaseUri = Uri.parse(kBaseUrl);

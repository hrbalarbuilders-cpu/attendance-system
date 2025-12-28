# attendance

A new Flutter project.

## Getting Started

This project is a starting point for a Flutter application.

A few resources to get you started if this is your first Flutter project:

- [Lab: Write your first Flutter app](https://docs.flutter.dev/get-started/codelab)
- [Cookbook: Useful Flutter samples](https://docs.flutter.dev/cookbook)

For help getting started with Flutter development, view the
[online documentation](https://docs.flutter.dev/), which offers tutorials,
samples, guidance on mobile development, and a full API reference.

---

## Running with different API environments
This project supports overriding the API base URL at build time using `--dart-define`.

Examples:

- Run on a device/emulator with the default (dev) API:

  flutter run --target=lib/main.dart

- Run using the production API (no code changes):

  flutter run --target=lib/main.dart --dart-define=API_BASE_URL="https://demosoftware.kesug.com/attendance-system/attendance/attendance_api"

- Build release APK for production:

  flutter build apk --release --dart-define=API_BASE_URL="https://demosoftware.kesug.com/attendance-system/attendance/attendance_api"

### VS Code launch configurations
Use the **Run and Debug** panel and select **attendance (Dev API)** or **attendance (Prod API)** to launch with the appropriate configuration.

### PHP lint and tests

If you modify API PHP files, you can lint them locally (requires PHP CLI in PATH).

- Windows (PowerShell):

```
cd attendance
\.\scripts\lint-php.ps1
```

- Unix / Git Bash:

```
cd attendance
./scripts/lint-php.sh
```

There is also a basic PHPUnit test skeleton at `attendance_api/tests/GetUserShiftTest.php`. To run it you need PHPUnit installed (via Composer) and the server running. Example (install Composer dependencies first):

```
cd attendance/attendance_api
composer require --dev phpunit/phpunit
./vendor/bin/phpunit tests/GetUserShiftTest.php
```


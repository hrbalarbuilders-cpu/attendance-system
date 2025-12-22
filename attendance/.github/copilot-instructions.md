# Copilot & AI Agent Instructions for Attendance System

## Project Overview
- **Monorepo** for an employee attendance system with three main components:
  - **Flutter App** (`lib/`, `android/`, `ios/`, `windows/`, `linux/`, `macos/`, `web/`): Cross-platform client for employees.
  - **API Backend** (`attendance_api/`): PHP endpoints for data access and business logic.
  - **Admin Panel** (`admin/`): PHP web interface for HR/admins.
- **Database**: MySQL, schema in `attendance_db.sql`.

## Key Workflows
- **Flutter App**:
  - Main code: `lib/main.dart`
  - Add packages: `pubspec.yaml`
  - Build: `flutter build <platform>`
  - Test: `flutter test`
  - Platform configs: `android/`, `ios/`, `windows/`, etc.
- **API Backend**:
  - Endpoints: `attendance_api/*.php`
  - Local dev: Requires XAMPP (Apache + MySQL)
  - Access via `http://localhost/attendance_api/`
- **Admin Panel**:
  - Pages: `admin/*.php`
  - Local dev: Requires XAMPP
  - Access via `http://localhost/admin/`

## Data Flow
- Flutter app <-> API (PHP) <-> MySQL
- Admin panel <-> MySQL (direct, via PHP)
- API and Admin panel are NOT in GitHub; only Flutter app is versioned.

## Conventions & Patterns
- **Do not edit**: `build/`, `generated_*.cc`, `generated_*.h`, or any auto-generated files.
- **Database access**: Use `db.php` in both API and admin for MySQL connections.
- **API endpoints**: Stateless, expect JSON input/output.
- **Flutter**: State management and UI logic are in `main.dart` and related widgets.
- **PHP**: Minimal frameworks; procedural style, each file is a route or handler.

## Setup & Deployment
- See `FOLDER_STRUCTURE.md` and `SETUP_GUIDE.md` for full setup.
- XAMPP must be running for backend/admin.
- Import `attendance_db.sql` before first run.
- Only the Flutter app is deployed via GitHub; backend/admin must be copied manually.

## Examples
- **Add a new API endpoint**: Copy an existing file in `attendance_api/`, update logic, register route if needed.
- **Add a new admin page**: Copy an existing file in `admin/`, update UI/logic, link from navigation.
- **Add a new Flutter feature**: Add widget in `lib/main.dart`, update navigation/UI as needed.

## References
- [FOLDER_STRUCTURE.md](../FOLDER_STRUCTURE.md): Full directory and data flow diagrams.
- [README.md](../README.md): Flutter app basics.
- [attendance_db.sql](../attendance_db.sql): Database schema.

---
_Last updated: 2025-12-22_

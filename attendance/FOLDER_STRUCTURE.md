# 📁 Project Folder Structure Guide

Complete explanation of the Attendance App project structure.

---

## 🏗️ Complete Project Architecture

This project consists of **3 separate components** stored in different locations:

```
📦 Complete Attendance System
│
├── 📱 C:\attendance\attendance\          # Flutter Mobile App (In GitHub)
│   └── Flutter app code, UI, and logic
│
├── 🔌 C:\xampp\htdocs\attendance_api\    # API Backend (Not in GitHub)
│   └── PHP API endpoints for mobile app
│
└── 👨‍💼 C:\xampp\htdocs\admin\              # Admin Panel (Not in GitHub)
    └── PHP admin panel for management
```

---

## 📂 1. Flutter App - `C:\attendance\attendance\`

**Purpose**: Mobile application (Android/iOS) code  
**Status**: ✅ In GitHub repository  
**Location on Server**: Separate, deployed independently

### Directory Structure:

```
attendance/
├── 📱 lib/                      # Main Flutter application code
│   └── main.dart               # All app logic (2224 lines)
├── 🔧 android/                  # Android platform-specific files
├── 🍎 ios/                      # iOS platform-specific files
├── 🪟 windows/                  # Windows platform-specific files
├── 🐧 linux/                    # Linux platform-specific files
├── 🖥️ macos/                    # macOS platform-specific files
├── 🌐 web/                      # Web platform-specific files
├── 🧪 test/                     # Unit & widget tests
├── 📦 build/                    # Build outputs (auto-generated)
├── ⚙️ pubspec.yaml              # Project dependencies & config
├── 📄 README.md                 # Project documentation
└── 📋 SETUP_GUIDE.md            # Setup instructions
```

**Note**: The `admin/` folder in this directory might be old/unused. Actual admin panel is at `C:\xampp\htdocs\admin\`

---

## 📂 Detailed Folder Explanations

### 1. 📱 `lib/` - Main Application Code

**Location**: `lib/main.dart`

**Purpose**: Contains all your Flutter/Dart application code.

**Contents**:
- `main.dart` - **Main entry point** of the app (2224 lines)
  - Contains entire app logic:
    - UI screens (Login, Dashboard, Attendance)
    - State management
    - API calls to backend
    - Local database operations
    - GPS/Location services
    - Timer logic for real-time updates

**Note**: Currently everything is in one file. Consider splitting into:
- `screens/` - Different UI screens
- `models/` - Data models
- `services/` - API & database services
- `utils/` - Helper functions
- `widgets/` - Reusable widgets

---

---

## 📂 2. API Backend - `C:\xampp\htdocs\attendance_api\`

**Purpose**: PHP API endpoints that the Flutter app calls  
**Status**: ❌ Not in GitHub (separate project)  
**Location**: XAMPP htdocs folder (runs on localhost)

### Key API Files:

```
attendance_api/
├── db.php                       # Database connection
├── login.php                    # User login API
├── clock.php                    # Clock in/out API
├── get_today_attendance.php     # Get today's attendance
├── get_user_shift.php           # Get user shift details
├── sync.php                     # Sync attendance data
├── get_office_location.php      # Get office GPS coordinates
└── add_shift_end_to_reason_enum.sql  # Database migration
```

### API Endpoints Used by Flutter App:
- **Login**: `POST /attendance_api/login.php`
- **Clock In/Out**: `POST /attendance_api/clock.php`
- **Get Today's Attendance**: `GET /attendance_api/get_today_attendance.php`
- **Get Shift**: `GET /attendance_api/get_user_shift.php`
- **Sync Data**: `POST /attendance_api/sync.php`
- **Office Location**: `GET /attendance_api/get_office_location.php`

---

## 📂 3. Admin Panel - `C:\xampp\htdocs\admin\`

**Purpose**: Web-based admin panel for managing employees, shifts, attendance  
**Status**: ❌ Not in GitHub (separate project)  
**Location**: XAMPP htdocs folder (runs on localhost)

### Admin Panel Files:

```
admin/
├── db.php                       # Database connection
├── employees.php                # Employee management UI
├── add_employee.php             # Add new employee
├── edit_employee.php            # Edit employee details
├── delete_employee.php          # Delete employee API
├── employees_list.php           # List all employees API
├── toggle_employee_status.php   # Enable/disable employee API
├── attendance_tab.php           # Attendance management UI
├── get_attendance_details.php   # Get attendance data API
├── save_admin_attendance.php    # Save attendance from admin
├── departments.php              # Department management
├── departments_tab.php          # Departments UI tab
├── designations.php             # Designation management
├── shifts.php                   # Shift management
├── holidays.php                 # Holiday management
├── settings.php                 # App settings
└── attendance_db.sql            # Database schema
```

### Admin Panel Access:
- URL: `http://localhost/admin/`
- Used by administrators to:
  - Manage employees
  - Create/edit shifts
  - View attendance reports
  - Manage departments & designations
  - Set holidays
  - Configure app settings

---

### 3. 🔧 `android/` - Android Platform

**Purpose**: Android-specific configuration and code.

**Structure**:
```
android/
├── app/
│   ├── build.gradle.kts         # App build configuration
│   └── src/
│       ├── main/
│       │   ├── AndroidManifest.xml    # App permissions & config
│       │   ├── kotlin/                # Kotlin code (if any)
│       │   └── res/                   # Resources (icons, images)
│       ├── debug/                     # Debug configuration
│       └── profile/                   # Profile build config
├── build.gradle.kts             # Project-level build config
├── gradle.properties            # Gradle properties
└── settings.gradle.kts          # Project settings
```

**Permissions Used** (from AndroidManifest.xml):
- Internet
- Location (GPS)
- Network State

---

### 4. 🍎 `ios/` - iOS Platform

**Purpose**: iOS-specific configuration.

**Structure**:
```
ios/
├── Runner/                      # iOS app runner
│   ├── AppDelegate.swift        # iOS app delegate
│   ├── Info.plist              # iOS app configuration
│   └── Assets.xcassets/        # App icons & images
└── Runner.xcodeproj/           # Xcode project
```

**Note**: iOS build requires macOS and Xcode.

---

### 5. 🪟 `windows/` - Windows Platform

**Purpose**: Windows desktop app configuration.

**Structure**:
```
windows/
├── runner/                      # Windows app runner
│   ├── main.cpp                # Entry point
│   └── resources/              # App resources (icons)
└── CMakeLists.txt              # Build configuration
```

---

### 6. 🐧 `linux/` - Linux Platform

**Purpose**: Linux desktop app configuration.

**Structure**:
```
linux/
├── runner/                      # Linux app runner
└── CMakeLists.txt              # Build configuration
```

---

### 7. 🖥️ `macos/` - macOS Platform

**Purpose**: macOS desktop app configuration.

**Structure**:
```
macos/
├── Runner/                      # macOS app runner
│   └── AppDelegate.swift        # macOS app delegate
└── Runner.xcodeproj/           # Xcode project
```

---

### 8. 🌐 `web/` - Web Platform

**Purpose**: Web app configuration.

**Structure**:
```
web/
├── index.html                   # Main HTML file
├── manifest.json               # Web app manifest
└── icons/                      # Web app icons
```

---

### 9. ⚙️ Configuration Files

#### `pubspec.yaml`
**Purpose**: Project configuration and dependencies.

**Key Sections**:
- **name**: `attendance` - Project name
- **version**: `1.0.0+1` - App version
- **dependencies**: External packages used
  - `http` - API calls
  - `connectivity_plus` - Check internet connection
  - `geolocator` - GPS/Location services
  - `device_info_plus` - Device information
  - `sqflite` - Local SQLite database
  - `shared_preferences` - Store user preferences
- **dev_dependencies**: Development tools
  - `flutter_test` - Testing framework
  - `flutter_lints` - Code quality checks

#### `analysis_options.yaml`
**Purpose**: Linter rules and code analysis configuration.

#### `.gitignore`
**Purpose**: Files/folders to exclude from Git.

**Excludes**:
- `build/` - Build outputs
- `.dart_tool/` - Dart tool cache
- `android/.gradle/` - Gradle cache
- `ios/Pods/` - iOS dependencies
- `*.iml` - IDE files

---

### 10. 📦 `build/` - Build Outputs

**Purpose**: Auto-generated build files (compiled code, APKs, etc.)

**Note**: 
- ✅ **Auto-generated** - Don't edit manually
- ❌ **Not in Git** - Ignored via .gitignore
- 🗑️ **Safe to delete** - Will regenerate on next build

**Contains**:
- Compiled Dart code
- Platform-specific build artifacts
- APK/IPA files (when built)

---

### 11. 🧪 `test/` - Tests

**Purpose**: Unit tests and widget tests.

**Files**:
- `widget_test.dart` - Widget testing

**Run Tests**:
```bash
flutter test
```

---

### 12. 📄 Root Files

#### `README.md`
Project overview and basic documentation.

#### `SETUP_GUIDE.md`
Setup instructions for multiple machines.

#### `attendance_db.sql`
Database schema for attendance system.

#### `create_geo_settings_table.sql`
SQL script to create geolocation settings table.

#### `get_office_location.php`
PHP script to get office location coordinates.

---

## 🔄 Complete Data Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    USER (Employee)                          │
│              Uses Flutter Mobile App                        │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│         Flutter App (C:\attendance\attendance\)            │
│              lib/main.dart                                  │
│         - UI Screens                                        │
│         - State Management                                  │
│         - Local Storage (SQLite)                            │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       │ HTTP API Calls
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│      API Backend (C:\xampp\htdocs\attendance_api\)         │
│              - login.php                                    │
│              - clock.php                                    │
│              - get_today_attendance.php                     │
│              - get_user_shift.php                           │
│              - sync.php                                     │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       │ Database Queries
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│              MySQL Database                                 │
│         attendance_db.sql schema                            │
│         - employees table                                   │
│         - attendance_logs table                             │
│         - shifts table                                      │
│         - departments, designations, etc.                   │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       │ Read/Write
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│      Admin Panel (C:\xampp\htdocs\admin\)                  │
│         Web Interface for Administrators                    │
│         - Manage employees                                  │
│         - View attendance reports                           │
│         - Configure shifts & settings                       │
└─────────────────────────────────────────────────────────────┘
```

---

## 📊 Key Dependencies & Their Uses

| Package | Purpose |
|---------|---------|
| `http` | Make API calls to PHP backend |
| `sqflite` | Local SQLite database for offline storage |
| `shared_preferences` | Store user login, settings locally |
| `geolocator` | Get GPS location for attendance |
| `connectivity_plus` | Check internet connection |
| `device_info_plus` | Get device information (ID, model) |

---

## 🎯 Most Important Files to Edit

### For Flutter App Changes:
1. **`C:\attendance\attendance\lib\main.dart`** - Main app code (UI, logic, API calls)
2. **`C:\attendance\attendance\pubspec.yaml`** - Add/remove Flutter packages
3. **`C:\attendance\attendance\android\app\src\main\AndroidManifest.xml`** - Android permissions

### For API Backend Changes:
4. **`C:\xampp\htdocs\attendance_api\*.php`** - API endpoints
   - `clock.php` - Clock in/out logic
   - `login.php` - Authentication
   - `get_today_attendance.php` - Fetch attendance data

### For Admin Panel Changes:
5. **`C:\xampp\htdocs\admin\*.php`** - Admin panel pages
   - `employees.php` - Employee management
   - `shifts.php` - Shift configuration
   - `attendance_tab.php` - Attendance reports

---

## 💡 Recommended Folder Organization (Future)

Consider restructuring `lib/` for better organization:

```
lib/
├── main.dart                    # Entry point only
├── models/                      # Data models
│   ├── employee.dart
│   ├── attendance.dart
│   └── shift.dart
├── screens/                     # UI screens
│   ├── login_screen.dart
│   ├── dashboard_screen.dart
│   └── attendance_screen.dart
├── services/                    # Business logic
│   ├── api_service.dart
│   ├── database_service.dart
│   └── location_service.dart
├── widgets/                     # Reusable widgets
│   ├── progress_circle.dart
│   └── punch_button.dart
└── utils/                       # Helpers
    ├── constants.dart
    └── helpers.dart
```

---

## 🔍 Quick Reference

### Flutter App (GitHub):
- **Edit UI?** → `C:\attendance\attendance\lib\main.dart`
- **Add packages?** → `C:\attendance\attendance\pubspec.yaml`
- **Build files?** → `C:\attendance\attendance\build\` (auto-generated)
- **Tests?** → `C:\attendance\attendance\test\widget_test.dart`

### API Backend (Not in GitHub):
- **Edit API?** → `C:\xampp\htdocs\attendance_api\*.php`
- **Test API?** → `http://localhost/attendance_api/`

### Admin Panel (Not in GitHub):
- **Edit Admin?** → `C:\xampp\htdocs\admin\*.php`
- **Access Admin?** → `http://localhost/admin/`

### Database:
- **Schema?** → `C:\xampp\htdocs\admin\attendance_db.sql`
- **Database?** → MySQL (via XAMPP)

---

## 📝 Important Notes

### Project Organization:
- ✅ **Flutter App** (`C:\attendance\attendance\`) is in GitHub
- ❌ **API Backend** (`C:\xampp\htdocs\attendance_api\`) is NOT in GitHub (local only)
- ❌ **Admin Panel** (`C:\xampp\htdocs\admin\`) is NOT in GitHub (local only)

### Setup Requirements:
- **XAMPP** must be running for API and Admin Panel
- **Flutter SDK** required for mobile app development
- **MySQL Database** must be set up using `attendance_db.sql`

### Deployment:
- **Flutter App**: Build APK/IPA and deploy to devices
- **API Backend**: Deploy PHP files to web server
- **Admin Panel**: Deploy PHP files to web server (same or separate)

### For Multiple Machines:
- Only Flutter app code is synced via GitHub
- API and Admin panel need to be manually copied/synced
- Database should be on a shared server or synced separately

---

## 🚀 Setting Up on New Machine

### 1. Flutter App (From GitHub):
```bash
git clone https://github.com/sachinbalarbuilders-hue/app.git
cd app
flutter pub get
```

### 2. API Backend (Manual Copy):
- Copy `C:\xampp\htdocs\attendance_api\` to new machine
- Ensure XAMPP is installed
- Update database connection in `db.php`

### 3. Admin Panel (Manual Copy):
- Copy `C:\xampp\htdocs\admin\` to new machine
- Ensure XAMPP is installed
- Update database connection in `db.php`

### 4. Database:
- Import `attendance_db.sql` in MySQL
- Update connection strings in both API and Admin

---

Last Updated: Project structure clarification - 3 separate components


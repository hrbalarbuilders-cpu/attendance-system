---
description: Deploy attendance system to free hosting platforms
---

# Deployment Guide for Attendance System

This guide will help you deploy your PHP/MySQL backend, admin web panel, and Flutter mobile app to free platforms.

## Prerequisites

- PHP backend files ready
- MySQL database dump (`attendance_db.sql`)
- Flutter app configured with production API URL
- Free hosting account (InfinityFree or 000webhost recommended)

---

## Part 1: Deploy Backend API & Admin Panel

### Step 1: Sign up for Free Hosting

**Recommended: InfinityFree**
1. Go to https://infinityfree.net
2. Click "Sign Up Now"
3. Create account (use valid email)
4. Create a new hosting account
5. Choose a subdomain (e.g., `yourapp.rf.gd`) or use your own domain
6. Wait for account activation (usually takes a few minutes)

**Alternative: 000webhost**
1. Go to https://www.000webhost.com
2. Click "Free Sign Up"
3. Create account
4. Create a new website
5. Choose a subdomain

### Step 2: Access cPanel/File Manager

1. Login to your hosting control panel
2. Open **File Manager**
3. Navigate to `public_html` or `htdocs` directory

### Step 3: Upload Backend Files

**Files to upload from your project:**
```
attendance/attendance_api/        → Upload to: public_html/attendance_api/
attendance/admin/                 → Upload to: public_html/admin/
```

**Methods to upload:**
- **File Manager**: Use the upload button (for smaller files)
- **FTP Client**: Use FileZilla (recommended for larger projects)
  - Host: Your FTP hostname (found in cPanel)
  - Username: Your FTP username
  - Password: Your FTP password
  - Port: 21

### Step 4: Create MySQL Database

1. In cPanel, find **MySQL Databases**
2. Create a new database (e.g., `attendance_db`)
3. Create a database user with strong password
4. Add user to database with ALL PRIVILEGES
5. Note down:
   - Database name: `yourusername_attendance_db`
   - Database user: `yourusername_dbuser`
   - Database password: `your_password`
   - Database host: `localhost` (usually)

### Step 5: Import Database

1. In cPanel, open **phpMyAdmin**
2. Select your database
3. Click **Import** tab
4. Upload `attendance/attendance_db.sql`
5. Click **Go** to import

### Step 6: Configure Database Connection

1. Open File Manager
2. Navigate to `public_html/attendance_api/db.php`
3. Edit with these credentials:

```php
<?php
$host = 'localhost';  // Usually localhost
$dbname = 'yourusername_attendance_db';  // Your actual DB name
$username = 'yourusername_dbuser';       // Your actual DB user
$password = 'your_password';              // Your actual DB password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
```

### Step 7: Test API Endpoints

Open browser and test:
- `http://yoursubdomain.rf.gd/attendance_api/login.php`
- `http://yoursubdomain.rf.gd/admin/index.php`

If you see errors, check:
- Database credentials in `db.php`
- File permissions (should be 644 for files, 755 for folders)
- PHP version compatibility (check in cPanel)

### Step 8: Enable SSL (Optional but Recommended)

**For InfinityFree (using Cloudflare):**
1. Sign up at https://cloudflare.com (free)
2. Add your domain/subdomain
3. Update nameservers at InfinityFree
4. Enable SSL in Cloudflare (Flexible mode)

**For 000webhost:**
- SSL is already included, just enable it in settings

---

## Part 2: Deploy Flutter Mobile App

### Step 9: Update API Configuration

1. Open `attendance/lib/config.dart`
2. Update the production API URL:

```dart
const String apiBaseUrl = 'https://yoursubdomain.rf.gd/attendance_api';
```

### Step 10: Build Android APK

Open terminal in the attendance directory and run:

```bash
flutter clean
flutter pub get
flutter build apk --release --dart-define=API_BASE_URL="https://yoursubdomain.rf.gd/attendance_api"
```

APK will be generated at:
`attendance/build/app/outputs/flutter-apk/app-release.apk`

### Step 11: Distribute the App

**Option A: Firebase App Distribution (Recommended)**
1. Go to https://console.firebase.google.com
2. Create a new project or use existing
3. Go to App Distribution
4. Upload your APK
5. Add testers via email
6. They'll receive download link

**Option B: GitHub Releases**
1. Create a release on GitHub
2. Upload the APK as an asset
3. Share the release link

**Option C: Direct Distribution**
1. Upload APK to Google Drive
2. Share the link with users
3. Users need to enable "Install from Unknown Sources"

---

## Part 3: Testing & Troubleshooting

### Test Checklist

- [ ] API endpoints respond correctly
- [ ] Admin panel login works
- [ ] Mobile app can connect to API
- [ ] Login functionality works
- [ ] Attendance clock in/out works
- [ ] Leave applications work
- [ ] All database operations function properly

### Common Issues

**Issue: API returns 500 error**
- Check database credentials in `db.php`
- Check PHP error logs in cPanel
- Verify database is imported correctly

**Issue: CORS errors in mobile app**
- Add CORS headers to PHP files:
```php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");
```

**Issue: App can't connect to API**
- Ensure API_BASE_URL is correct
- Test API in browser first
- Check if SSL is properly configured

**Issue: Database connection failed**
- Verify database name includes hosting prefix
- Check database host (usually localhost)
- Ensure database user has proper privileges

---

## Part 4: Monitoring & Maintenance

### Regular Maintenance

1. **Backup Database**: Export from phpMyAdmin weekly
2. **Monitor Uptime**: Use free services like UptimeRobot
3. **Update Dependencies**: Keep Flutter and PHP packages updated
4. **Check Logs**: Review error logs in cPanel regularly

### Performance Tips

- Enable gzip compression in `.htaccess`
- Optimize images in admin panel
- Use database indexing for faster queries
- Implement caching for frequently accessed data

---

## Free Alternatives Summary

| Component | Platform | Features |
|-----------|----------|----------|
| **Backend + DB** | InfinityFree | Unlimited, Free SSL with Cloudflare |
| **Backend + DB** | 000webhost | 300MB, Free SSL, Easy setup |
| **Backend + DB** | Awardspace | 1GB, Good uptime |
| **App Distribution** | Firebase | Easy updates, Analytics |
| **App Distribution** | GitHub | Version control, Free |
| **App Store** | Google Play | $25 one-time (best for production) |

---

## Next Steps

1. Choose your hosting platform
2. Follow steps 1-8 to deploy backend
3. Follow steps 9-11 to build and distribute app
4. Test thoroughly
5. Share app with users

**Need help?** Check the troubleshooting section or review server logs for specific errors.

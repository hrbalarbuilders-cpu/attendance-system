<?php
// attendance_settings_tab.php
date_default_timezone_set('Asia/Kolkata');
include '../config/db.php';

$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

// Ensure settings table exists
$con->query("
  CREATE TABLE IF NOT EXISTS attendance_settings (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

$con->query("
  CREATE TABLE IF NOT EXISTS employee_devices (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    user_id INT NOT NULL, 
    device_id VARCHAR(255) NOT NULL, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    UNIQUE KEY(user_id, device_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Migrate once if needed (by checking if table empty vs original table)
$checkDevices = $con->query("SELECT COUNT(*) as c FROM employee_devices");
$devCount = ($checkDevices && $dc = $checkDevices->fetch_assoc()) ? (int) $dc['c'] : 0;
if ($devCount === 0) {
    $con->query("INSERT IGNORE INTO employee_devices (user_id, device_id) 
               SELECT user_id, device_id FROM employees 
               WHERE device_id IS NOT NULL AND device_id <> ''");
}

// Insert defaults if not exists
$con->query("INSERT IGNORE INTO attendance_settings (setting_key, setting_value) VALUES ('global_auto_attendance', '0')");
$con->query("INSERT IGNORE INTO attendance_settings (setting_key, setting_value) VALUES ('device_limit', '1')");
$con->query("INSERT IGNORE INTO attendance_settings (setting_key, setting_value) VALUES ('ip_restriction_enabled', '0')");
$con->query("INSERT IGNORE INTO attendance_settings (setting_key, setting_value) VALUES ('allowed_ips', '')");

$errors = [];
$success = '';

// Handle Settings Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance_settings'])) {
    $auto_attendance = isset($_POST['global_auto_attendance']) ? '1' : '0';
    $device_limit = isset($_POST['device_limit']) ? (int) $_POST['device_limit'] : 1;
    $ip_enabled = isset($_POST['ip_restriction_enabled']) ? '1' : '0';
    $allowed_ips = trim($_POST['allowed_ips'] ?? '');

    if ($device_limit < 1)
        $device_limit = 1;

    $con->begin_transaction();
    try {
        $stmt1 = $con->prepare("UPDATE attendance_settings SET setting_value = ? WHERE setting_key = 'global_auto_attendance'");
        $stmt1->bind_param("s", $auto_attendance);
        $stmt1->execute();

        $stmt2 = $con->prepare("UPDATE attendance_settings SET setting_value = ? WHERE setting_key = 'device_limit'");
        $limitStr = (string) $device_limit;
        $stmt2->bind_param("s", $limitStr);
        $stmt2->execute();

        $stmt3 = $con->prepare("UPDATE attendance_settings SET setting_value = ? WHERE setting_key = 'ip_restriction_enabled'");
        $stmt3->bind_param("s", $ip_enabled);
        $stmt3->execute();

        $stmt4 = $con->prepare("UPDATE attendance_settings SET setting_value = ? WHERE setting_key = 'allowed_ips'");
        $stmt4->bind_param("s", $allowed_ips);
        $stmt4->execute();

        $con->commit();
        $success = 'Attendance settings updated successfully.';
    } catch (Exception $e) {
        $con->rollback();
        $errors[] = 'Failed to update settings: ' . $e->getMessage();
    }
}

// Fetch current values
$settings = [];
$res = $con->query("SELECT setting_key, setting_value FROM attendance_settings");
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

if ($isAjax) {
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0">Attendance Rules</h3>
            <div class="text-muted small">Configure global attendance behavior for the mobile app</div>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="card settings-card shadow-sm border-0" style="border-radius:18px;">
        <div class="card-body p-4">
            <form id="attendanceSettingsForm">
                <input type="hidden" name="save_attendance_settings" value="1">

                <div class="mb-4">
                    <div class="form-check form-switch p-0 d-flex align-items-center justify-content-between">
                        <div>
                            <label class="form-check-label h6 mb-0" for="globalAutoAttendance">Enable Global
                                Auto-Attendance</label>
                            <div class="text-muted small">When ON, the app will automatically mark clock-in/out based on
                                geofences.</div>
                        </div>
                        <input class="form-check-input ms-3" type="checkbox" role="switch" id="globalAutoAttendance"
                            name="global_auto_attendance" style="width: 3.5em; height: 1.75em;" <?php echo ($settings['global_auto_attendance'] ?? '0') === '1' ? 'checked' : ''; ?>>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <label class="h6 mb-0" for="deviceLimit">Device based clock in (Device Limit)</label>
                            <div class="text-muted small">Specify how many unique devices an employee can use to record
                                attendance.</div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="input-group ms-auto" style="max-width: 120px;">
                                <input type="number" class="form-control text-center py-2" id="deviceLimit"
                                    name="device_limit" min="1" max="10" step="1" style="border-radius: 10px;"
                                    value="<?php echo htmlspecialchars($settings['device_limit'] ?? '1'); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4 opacity-50">

                <div class="mb-4">
                    <div class="form-check form-switch p-0 d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <label class="form-check-label h6 mb-0" for="ipRestrictionEnabled">IP Address
                                Restriction</label>
                            <div class="text-muted small">Only allow clock-in/out from specific IP addresses.</div>
                        </div>
                        <input class="form-check-input ms-3" type="checkbox" role="switch" id="ipRestrictionEnabled"
                            name="ip_restriction_enabled" style="width: 3.5em; height: 1.75em;" <?php echo ($settings['ip_restriction_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>>
                    </div>

                    <div id="ipListWrapper"
                        style="<?php echo ($settings['ip_restriction_enabled'] ?? '0') === '1' ? '' : 'display:none;'; ?>">
                        <label class="form-label small fw-bold text-muted mb-2">Allowed IP Addresses (Enter one per
                            line)</label>
                        <textarea class="form-control" name="allowed_ips" rows="3"
                            placeholder="e.g. 192.168.1.1&#10;203.0.113.5"
                            style="border-radius: 12px; font-family: monospace; font-size: 14px;"><?php echo htmlspecialchars($settings['allowed_ips'] ?? ''); ?></textarea>
                    </div>
                </div>

                <script>
                    document.getElementById('ipRestrictionEnabled').addEventListener('change', function () {
                        document.getElementById('ipListWrapper').style.display = this.checked ? 'block' : 'none';
                    });
                </script>

                <hr class="my-4 opacity-50">

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-dark px-4 py-2" style="border-radius: 10px;">Save Settings</button>
                </div>
            </form>
        </div>
    </div>

    </div>
    <?php
    exit;
}
?>
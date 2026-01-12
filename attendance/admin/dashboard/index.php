<?php
session_start();
date_default_timezone_set('Asia/Kolkata');

// Check if user is logged in
if (!isset($_SESSION['emp_id']) || $_SESSION['emp_id'] <= 0) {
    header('Location: ../login.php');
    exit;
}

include '../config/db.php';
include '../includes/header.php';

// Get employee from session
$employee_id = (int)$_SESSION['emp_id'];

// Get employee details if ID provided
$employee = null;
$todayClockIn = null;
$todayClockOut = null;
$clockStatus = 'out';
$empList = null;

if ($employee_id > 0) {
    $empStmt = $con->prepare("SELECT e.*, d.department_name, s.shift_name, s.start_time, s.end_time 
                              FROM employees e 
                              LEFT JOIN departments d ON d.id = e.department_id
                              LEFT JOIN shifts s ON s.id = e.shift_id
                              WHERE e.id = ? AND e.status = 1");
    $empStmt->bind_param("i", $employee_id);
    $empStmt->execute();
    $employee = $empStmt->get_result()->fetch_assoc();
    $empStmt->close();

    if ($employee) {
        $currentDate = date('Y-m-d');
        
        // Get today's first clock in
        $inStmt = $con->prepare("SELECT time, working_from FROM attendance_logs 
                                 WHERE user_id = ? AND DATE(time) = ? AND type = 'in' 
                                 ORDER BY time ASC LIMIT 1");
        $inStmt->bind_param("is", $employee_id, $currentDate);
        $inStmt->execute();
        $todayClockIn = $inStmt->get_result()->fetch_assoc();
        $inStmt->close();

        // Get today's last clock out
        $outStmt = $con->prepare("SELECT time FROM attendance_logs 
                                  WHERE user_id = ? AND DATE(time) = ? AND type = 'out' 
                                  ORDER BY time DESC LIMIT 1");
        $outStmt->bind_param("is", $employee_id, $currentDate);
        $outStmt->execute();
        $todayClockOut = $outStmt->get_result()->fetch_assoc();
        $outStmt->close();

        // Determine clock status
        $lastLogStmt = $con->prepare("SELECT type FROM attendance_logs 
                                      WHERE user_id = ? AND DATE(time) = ? 
                                      ORDER BY time DESC LIMIT 1");
        $lastLogStmt->bind_param("is", $employee_id, $currentDate);
        $lastLogStmt->execute();
        $lastLog = $lastLogStmt->get_result()->fetch_assoc();
        $lastLogStmt->close();
        
        $clockStatus = ($lastLog && $lastLog['type'] === 'in') ? 'in' : 'out';
    }
}

// If no employee found or no ID, fetch employee list for selector
if (!$employee) {
    $empList = $con->query("SELECT id, name, emp_code, profile_img FROM employees WHERE status = 1 ORDER BY name ASC");
}

$currentDay = date('l');
$currentDate = date('Y-m-d');
?>
<style>
  .dashboard-wrapper {
    background: #f8fafc;
    min-height: calc(100vh - 70px);
    padding: 24px 32px;
    margin-top: 70px;
  }

  .dashboard-header-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 32px;
  }

  .dashboard-title h1 {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 4px 0;
  }

  .dashboard-title p {
    font-size: 1rem;
    color: #64748b;
    margin: 0;
  }

  .clock-widget {
    display: flex;
    align-items: center;
    gap: 16px;
  }

  .time-display {
    text-align: right;
  }

  .time-display .current-time {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .time-display .current-time .time-icon {
    width: 24px;
    height: 24px;
    background: #e0f2fe;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
  }

  .time-display .clock-status {
    font-size: 0.85rem;
    color: #64748b;
  }

  .btn-clock {
    padding: 12px 28px;
    border-radius: 8px;
    border: none;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: all 0.2s;
  }

  .btn-clock-in {
    background: #22c55e;
    color: white;
  }

  .btn-clock-in:hover {
    background: #16a34a;
  }

  .btn-clock-out {
    background: #ef4444;
    color: white;
  }

  .btn-clock-out:hover {
    background: #dc2626;
  }

  .btn-settings {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #64748b;
    font-size: 1.2rem;
  }

  .btn-settings:hover {
    background: #f1f5f9;
  }

  /* Employee Selector Card */
  .employee-selector-card {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    max-width: 700px;
    margin: 40px auto;
  }

  .employee-selector-card h2 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 24px;
    text-align: center;
  }

  .employee-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 16px;
  }

  .employee-card {
    background: #f8fafc;
    border: 2px solid transparent;
    border-radius: 12px;
    padding: 20px 16px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
  }

  .employee-card:hover {
    border-color: #3b82f6;
    background: #eff6ff;
  }

  .employee-card .avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    color: white;
    font-size: 1.25rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    overflow: hidden;
  }

  .employee-card .avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .employee-card .name {
    font-size: 0.9rem;
    font-weight: 500;
    color: #1e293b;
  }

  .employee-card .code {
    font-size: 0.75rem;
    color: #64748b;
  }

  /* Stats Row */
  .stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
  }

  .stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  .stat-card .label {
    font-size: 0.85rem;
    color: #64748b;
    margin-bottom: 8px;
  }

  .stat-card .value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
  }

  .stat-card .value.success { color: #22c55e; }
  .stat-card .value.danger { color: #ef4444; }

  /* Activity Card */
  .activity-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  .activity-card h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 20px;
  }

  .activity-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
  }

  .activity-item:last-child {
    border-bottom: none;
  }

  .activity-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
  }

  .activity-icon.in { background: #dcfce7; }
  .activity-icon.out { background: #fee2e2; }

  .activity-info {
    flex: 1;
  }

  .activity-info .type {
    font-size: 0.9rem;
    font-weight: 500;
    color: #1e293b;
  }

  .activity-info .meta {
    font-size: 0.8rem;
    color: #64748b;
  }

  .activity-time {
    font-size: 0.85rem;
    font-weight: 500;
    color: #1e293b;
  }

  /* Toast */
  .toast-container {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9999;
  }

  .custom-toast {
    background: white;
    padding: 16px 24px;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 12px;
    animation: slideIn 0.3s ease;
  }

  .custom-toast.success { border-left: 4px solid #22c55e; }
  .custom-toast.error { border-left: 4px solid #ef4444; }

  @keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
  }
</style>

<div class="dashboard-wrapper">
<?php if (!$employee): ?>
  <!-- Employee Selector -->
  <div class="employee-selector-card">
    <h2>👋 Select Your Profile to Clock In</h2>
    <div class="employee-grid">
      <?php while ($emp = $empList->fetch_assoc()): ?>
        <a href="?emp_id=<?php echo $emp['id']; ?>" class="employee-card">
          <div class="avatar">
            <?php if (!empty($emp['profile_img'])): ?>
              <img src="<?php echo htmlspecialchars($emp['profile_img']); ?>" alt="">
            <?php else: ?>
              <?php echo strtoupper(substr($emp['name'], 0, 2)); ?>
            <?php endif; ?>
          </div>
          <div class="name"><?php echo htmlspecialchars($emp['name']); ?></div>
          <div class="code"><?php echo htmlspecialchars($emp['emp_code']); ?></div>
        </a>
      <?php endwhile; ?>
    </div>
  </div>
<?php else: ?>
  <!-- Dashboard Header -->
  <div class="dashboard-header-row">
    <div class="dashboard-title">
      <h1>Dashboard</h1>
      <p>Welcome back, <?php echo htmlspecialchars($employee['name']); ?>! Here's what's happening today.</p>
    </div>
    <div class="clock-widget">
      <div class="time-display">
        <div class="current-time">
          <span class="time-icon">🕐</span>
          <span id="liveTime"><?php echo date('h:i A'); ?></span>
          <span><?php echo $currentDay; ?></span>
        </div>
        <div class="clock-status">
          Clock In at <?php echo $todayClockIn ? date('h:i A', strtotime($todayClockIn['time'])) : '- -'; ?>
        </div>
      </div>
      
      <?php if ($clockStatus === 'out'): ?>
        <button class="btn-clock btn-clock-in" onclick="submitClock('in')">
          <span>▶</span> Clock In
        </button>
      <?php else: ?>
        <button class="btn-clock btn-clock-out" onclick="submitClock('out')">
          <span>⏹</span> Clock Out
        </button>
      <?php endif; ?>
      
      <button class="btn-settings" title="Settings">⚙️</button>
    </div>
  </div>

  <!-- Stats Row -->
  <div class="stats-row">
    <div class="stat-card">
      <div class="label">Today's Status</div>
      <div class="value <?php echo $todayClockIn ? 'success' : 'danger'; ?>">
        <?php echo $todayClockIn ? 'Present' : 'Not Clocked In'; ?>
      </div>
    </div>
    <div class="stat-card">
      <div class="label">Clock In Time</div>
      <div class="value"><?php echo $todayClockIn ? date('h:i A', strtotime($todayClockIn['time'])) : '--:--'; ?></div>
    </div>
    <div class="stat-card">
      <div class="label">Clock Out Time</div>
      <div class="value"><?php echo $todayClockOut ? date('h:i A', strtotime($todayClockOut['time'])) : '--:--'; ?></div>
    </div>
    <div class="stat-card">
      <div class="label">Working From</div>
      <div class="value"><?php echo $todayClockIn ? htmlspecialchars($todayClockIn['working_from'] ?? 'Office') : '--'; ?></div>
    </div>
  </div>

  <!-- Activity Card -->
  <div class="activity-card">
    <h3>📋 Today's Activity</h3>
    <?php
    $activityStmt = $con->prepare("SELECT * FROM attendance_logs 
                                   WHERE user_id = ? AND DATE(time) = ? 
                                   ORDER BY time DESC LIMIT 10");
    $activityStmt->bind_param("is", $employee_id, $currentDate);
    $activityStmt->execute();
    $activities = $activityStmt->get_result();
    ?>
    <?php if ($activities && $activities->num_rows > 0): ?>
      <?php while ($act = $activities->fetch_assoc()): ?>
        <div class="activity-item">
          <div class="activity-icon <?php echo $act['type']; ?>">
            <?php echo $act['type'] === 'in' ? '🟢' : '🔴'; ?>
          </div>
          <div class="activity-info">
            <div class="type">Clock <?php echo ucfirst($act['type']); ?></div>
            <div class="meta"><?php echo htmlspecialchars($act['working_from'] ?? 'Office'); ?> • <?php echo htmlspecialchars($act['reason'] ?? 'Shift'); ?></div>
          </div>
          <div class="activity-time"><?php echo date('h:i A', strtotime($act['time'])); ?></div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="activity-item">
        <div class="activity-info">
          <div class="meta">No activity recorded today</div>
        </div>
      </div>
    <?php endif; ?>
    <?php $activityStmt->close(); ?>
  </div>
<?php endif; ?>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<script>
  // Live clock
  function updateClock() {
    const now = new Date();
    let hours = now.getHours();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12 || 12;
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const el = document.getElementById('liveTime');
    if (el) el.textContent = `${hours}:${minutes} ${ampm}`;
  }
  setInterval(updateClock, 1000);

  // Toast notification
  function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `custom-toast ${type}`;
    toast.innerHTML = `<span>${type === 'success' ? '✅' : '❌'}</span><span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
  }

  // Submit clock in/out
  function submitClock(type) {
    const employeeId = <?php echo $employee_id ?: 0; ?>;
    
    if (!employeeId) {
      showToast('Please select an employee first', 'error');
      return;
    }

    // Get browser location first
    if (!navigator.geolocation) {
      showToast('Geolocation is not supported by your browser', 'error');
      return;
    }

    showToast('Getting your location...', 'success');

    navigator.geolocation.getCurrentPosition(
      function(position) {
        // Success - got location
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        
        const formData = new FormData();
        formData.append('user_id', employeeId);
        formData.append('type', type);
        formData.append('working_from', '<?php echo $employee ? addslashes($employee['default_working_from'] ?? 'Office') : 'Office'; ?>');
        formData.append('reason', type === 'in' ? 'shift_start' : 'shift_end');
        formData.append('lat', lat);
        formData.append('lng', lng);

        fetch('clock_web.php', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'success') {
            showToast(data.msg, 'success');
            setTimeout(() => location.reload(), 1000);
          } else {
            showToast(data.msg || 'Failed to record attendance', 'error');
          }
        })
        .catch(err => {
          showToast('Network error. Please try again.', 'error');
          console.error('Fetch error:', err);
        });
      },
      function(error) {
        // Error getting location
        switch(error.code) {
          case error.PERMISSION_DENIED:
            showToast('Location permission denied. Please enable location access.', 'error');
            break;
          case error.POSITION_UNAVAILABLE:
            showToast('Location information unavailable.', 'error');
            break;
          case error.TIMEOUT:
            showToast('Location request timed out.', 'error');
            break;
          default:
            showToast('Error getting location.', 'error');
        }
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
      }
    );
  }
</script>

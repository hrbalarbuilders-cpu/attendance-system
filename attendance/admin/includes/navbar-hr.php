<?php
// Shared HR navbar (included by HR pages)
// highlight the current page's pill
// We handle dynamic highlighting based on script name.
$current_page = basename($_SERVER['PHP_SELF']);

function hr_nav_class($target_page, $current_page)
{
  if ($current_page === $target_page) {
    return 'top-nav-pill active';
  }
  return 'top-nav-pill';
}
?>
<?php include_once __DIR__ . '/top-nav-styles.php'; ?>

<div class="top-nav-shell">
  <div class="top-nav-row">
    <div class="top-nav-viewport">
      <div class="top-nav-inner-row">
        <button type="button" class="top-nav-arrow" data-topnav-arrow="left" aria-label="Scroll left">‹</button>
        <div class="top-nav-wrapper">
          <a class="<?php echo hr_nav_class('employees.php', $current_page); ?>" href="employees.php"
            style="text-decoration:none;"><span class="icon">👥</span> Employees</a>
          <a class="<?php echo hr_nav_class('shift_roster.php', $current_page); ?>" href="shift_roster.php"
            style="text-decoration:none;"><span class="icon">🗓</span> Shift Roster</a>
          <a class="<?php echo hr_nav_class('attendance.php', $current_page); ?>" href="attendance.php"
            style="text-decoration:none;"><span class="icon">📅</span> Attendance</a>
          <a class="<?php echo hr_nav_class('leaves.php', $current_page); ?>" href="leaves.php"
            style="text-decoration:none;"><span class="icon">📝</span> Leaves</a>
          <a class="<?php echo hr_nav_class('departments.php', $current_page); ?>" href="departments.php"
            style="text-decoration:none;"><span class="icon">🛠</span> Department</a>
          <a class="<?php echo hr_nav_class('designations.php', $current_page); ?>" href="designations.php"
            style="text-decoration:none;"><span class="icon">👤</span> Designation</a>
          <a class="<?php echo hr_nav_class('holidays.php', $current_page); ?>" href="holidays.php"
            style="text-decoration:none;"><span class="icon">🎉</span> Holiday</a>
        </div>
        <button type="button" class="top-nav-arrow" data-topnav-arrow="right" aria-label="Scroll right">›</button>
      </div>
      <div class="top-nav-scrollbar" aria-hidden="true">
        <div class="top-nav-scrollbar-thumb"></div>
      </div>
    </div>
  </div>
</div>
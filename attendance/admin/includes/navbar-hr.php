<?php
// Shared HR navbar (included by HR pages)
?>
<?php include_once __DIR__ . '/top-nav-styles.php'; ?>

<div class="top-nav-shell">
  <div class="top-nav-row">
    <div class="top-nav-viewport">
      <div class="top-nav-inner-row">
        <button type="button" class="top-nav-arrow" data-topnav-arrow="left" aria-label="Scroll left">‹</button>
        <div class="top-nav-wrapper">
          <button class="top-nav-pill active" data-page="employees_list.php?ajax=1"><span class="icon">👥</span> Employees</button>
          <button class="top-nav-pill" data-page="shift_roster.php?ajax=1"><span class="icon">🗓</span> Shift Roster</button>
          <button class="top-nav-pill" data-page="attendance_tab.php?ajax=1"><span class="icon">📅</span> Attendance</button>
          <button class="top-nav-pill" data-page="leaves_tab.php?ajax=1"><span class="icon">📝</span> Leaves</button>
          <button class="top-nav-pill" data-page="departments.php?ajax=1"><span class="icon">🛠</span> Department</button>
          <button class="top-nav-pill" data-page="designations.php?ajax=1"><span class="icon">👤</span> Designation</button>
          <button class="top-nav-pill" data-page="holidays.php?ajax=1"><span class="icon">🎉</span> Holiday</button>
        </div>
        <button type="button" class="top-nav-arrow" data-topnav-arrow="right" aria-label="Scroll right">›</button>
      </div>
      <div class="top-nav-scrollbar" aria-hidden="true">
        <div class="top-nav-scrollbar-thumb"></div>
      </div>
    </div>
  </div>
</div>
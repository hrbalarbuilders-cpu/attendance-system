<?php
// Shared HR navbar (included by HR pages)
?>
<style>
.top-nav-wrapper {
  background: #ffffff;
  border-radius: 8px;
  padding: 6px 10px;
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08);
  display: inline-flex;
  gap: 16px;
  align-items: center;
}
.top-nav-wrapper .top-nav-pill {
  padding: 8px 20px;
  border-radius: 6px;
  border: none;
  background: transparent;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  color: #4b5563;
  cursor: pointer;
  text-decoration: none;
}
.top-nav-wrapper .top-nav-pill.active {
  background: #111827;
  color: #ffffff;
}
.top-nav-wrapper .top-nav-pill:hover {
  background: #111827;
  color: #ffffff;
}
.top-nav-wrapper .top-nav-pill .icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  border-radius: 4px;
  background: rgba(255,255,255,0.12);
  font-size: 0.9rem;
}
</style>

<div class="top-nav-wrapper">
  <button class="top-nav-pill active" data-page="employees_list.php?ajax=1"><span class="icon">👥</span> Employees</button>
  <button class="top-nav-pill" data-page="attendance_tab.php?ajax=1"><span class="icon">📅</span> Attendance</button>
  <button class="top-nav-pill" data-page="leaves_tab.php"><span class="icon">📝</span> Leaves</button>
  <button class="top-nav-pill" data-page="shifts.php?ajax=1"><span class="icon">📊</span> Shift Roster</button>
  <button class="top-nav-pill" data-page="departments.php?ajax=1"><span class="icon">🛠</span> Department</button>
  <button class="top-nav-pill" data-page="designations.php?ajax=1"><span class="icon">👤</span> Designation</button>
  <button class="top-nav-pill" data-page="holidays.php?ajax=1"><span class="icon">🎉</span> Holiday</button>
</div>
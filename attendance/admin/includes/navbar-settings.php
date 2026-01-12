<?php
// Shared Settings navbar (included by settings.php)
?>
<?php include_once __DIR__ . '/top-nav-styles.php'; ?>

<div class="top-nav-shell">
  <div class="top-nav-row">
    <div class="top-nav-viewport">
      <div class="top-nav-inner-row">
        <button type="button" class="top-nav-arrow" data-topnav-arrow="left" aria-label="Scroll left">‹</button>
        <div class="top-nav-wrapper">
          <button class="top-nav-pill active" data-page="shifts.php?ajax=1"><span class="icon">📊</span> Shift Master</button>
          <button class="top-nav-pill" data-page="location_settings.php?ajax=1"><span class="icon">📍</span> Location</button>
          <button class="top-nav-pill" data-page="leave_settings.php?ajax=1"><span class="icon">📝</span> Leaves</button>
          <button class="top-nav-pill" data-page="working_from_settings.php?ajax=1"><span class="icon">🏠</span> Working From</button>
        </div>
        <button type="button" class="top-nav-arrow" data-topnav-arrow="right" aria-label="Scroll right">›</button>
      </div>
      <div class="top-nav-scrollbar" aria-hidden="true">
        <div class="top-nav-scrollbar-thumb"></div>
      </div>
    </div>
  </div>
</div>

<?php
// Shared Sales navbar (for Leads, Clients, Sources etc.)
// highlight the current page's pill
$current = basename($_SERVER['PHP_SELF'] ?? '');
function nav_class($target, $current) {
  return $current === $target ? 'top-nav-pill active' : 'top-nav-pill';
}
?>
<?php include_once __DIR__ . '/top-nav-styles.php'; ?>

<div class="top-nav-shell">
  <div class="top-nav-row">
    <div class="top-nav-viewport">
      <div class="top-nav-inner-row">
        <button type="button" class="top-nav-arrow" data-topnav-arrow="left" aria-label="Scroll left">‹</button>
        <div class="top-nav-wrapper">
          <a class="<?php echo nav_class('leads.php', $current); ?>" href="leads.php"><span class="icon">📈</span> Leads</a>
          <a class="<?php echo nav_class('clients.php', $current); ?>" href="clients.php"><span class="icon">👥</span> Clients</a>
          <a class="<?php echo nav_class('sources.php', $current); ?>" href="sources.php"><span class="icon">📂</span> Source of Leads</a>
          <a class="<?php echo nav_class('looking_for.php', $current); ?>" href="looking_for.php"><span class="icon">🔎</span> Looking For</a>
          <a class="<?php echo nav_class('sales_persons.php', $current); ?>" href="sales_persons.php"><span class="icon">🧑‍💼</span> Sales Person</a>
        </div>
        <button type="button" class="top-nav-arrow" data-topnav-arrow="right" aria-label="Scroll right">›</button>
      </div>
      <div class="top-nav-scrollbar" aria-hidden="true">
        <div class="top-nav-scrollbar-thumb"></div>
      </div>
    </div>
  </div>
</div>
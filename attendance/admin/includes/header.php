<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
$isLoggedIn = isset($_SESSION['emp_id']) && $_SESSION['emp_id'] > 0;
$loggedInName = isset($_SESSION['emp_name']) ? $_SESSION['emp_name'] : '';
?>
<!-- Global Header with Apps Dropdown -->
<style>
  .header-fixed {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 2000;
    background: white;
    border-bottom: 1px solid #e5e7eb;
    padding: 12px 20px;
    box-sizing: border-box;
    overflow: visible;
    display: flex;
    align-items: center;
    gap: 20px;
    justify-content: flex-end;
  }
  /* Offset main page content so fixed header doesn't overlap navbars */
  .main-content-scroll {
    padding-top: 72px;
  }
  .apps-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    background: none;
    border: none;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    padding: 8px;
    border-radius: 6px;
    transition: background 0.2s;
  }
  .apps-btn:hover {
    background: #f3f4f6;
  }
  .menu-icon {
    width: 18px;
    height: 18px;
  }
  .apps-dropdown {
    display: none;
    position: absolute;
    width: 300px;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    z-index: 3000;
    padding: 0 0 18px 0;
    flex-direction: column;
    gap: 0;
    animation: fadeIn 0.18s;
  }
  .apps-dropdown.show {
    display: flex;
  }
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px);}
    to { opacity: 1; transform: translateY(0);}
  }
  .apps-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 18px 24px 0 24px;
  }
  .apps-header-icon {
    background: #ff6600;
    border-radius: 10px;
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .apps-title {
    font-size: 1.15rem;
    font-weight: 700;
    margin-bottom: 2px;
  }
  .apps-subtitle {
    font-size: 0.92rem;
    color: #888;
  }
  .apps-close {
    background: none;
    border: none;
    font-size: 2rem;
    color: #888;
    cursor: pointer;
    margin-left: auto;
    line-height: 1;
  }
  .apps-search {
    padding: 12px 24px 0 24px;
  }
  .apps-search-input {
    width: 100%;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    padding: 8px 14px;
    font-size: 1rem;
    outline: none;
    background: #f7f8fa;
    transition: border 0.2s;
  }
  .apps-search-input:focus {
    border: 1.5px solid #2563eb;
  }
  .header-fixed span {
    white-space: nowrap;
    overflow: visible;
    text-overflow: ellipsis;
  }
  .apps-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    padding: 12px 12px 0 12px;
  }
  .app-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    border-radius: 12px;
    padding: 10px 0 8px 0;
    transition: background 0.15s, box-shadow 0.15s;
  }
  .app-item:hover, .app-item:focus {
    background: #f3f4f6;
    box-shadow: 0 2px 8px rgba(37,99,235,0.08);
  }
  .app-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 2px;
  }
  .app-icon svg {
    width: 26px;
    height: 26px;
    display: block;
    stroke: #fff;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
    shape-rendering: geometricPrecision;
  }
  .app-icon svg .icon-fill {
    fill: #fff;
    stroke: none;
  }
  .app-name {
    font-size: 0.98rem;
    font-weight: 500;
    color: #222;
  }
</style>
<?php
// Compute web base path for admin folder so Apps links work regardless of document root.
// If the script path contains '/admin', use that as the root (handles nested folders like /attendance/admin/hr).
$script = str_replace('\\','/', $_SERVER['SCRIPT_NAME']);
$adminPos = strpos($script, '/admin');
if ($adminPos !== false) {
  $base = substr($script, 0, $adminPos + strlen('/admin'));
} else {
  $base = rtrim(dirname($script), '/');
}
?>
<div class="header-fixed">
  <button class="apps-btn" id="appsBtn">
    <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <rect x="3" y="3" width="7" height="7" rx="1"/>
      <rect x="14" y="3" width="7" height="7" rx="1"/>
      <rect x="14" y="14" width="7" height="7" rx="1"/>
      <rect x="3" y="14" width="7" height="7" rx="1"/>
    </svg>
    Apps
  </button>
  <?php if ($isLoggedIn): ?>
    <span style="font-size:0.9rem;color:#64748b;">👤 <?php echo htmlspecialchars($loggedInName); ?></span>
    <a href="<?php echo $base; ?>/logout.php" style="display:inline-block;padding:6px 14px;font-size:0.85rem;font-weight:500;color:#dc2626;background:#fff;border:1px solid #dc2626;border-radius:6px;text-decoration:none;transition:all 0.2s;" onmouseover="this.style.background='#dc2626';this.style.color='#fff';" onmouseout="this.style.background='#fff';this.style.color='#dc2626';">🚪 Logout</a>
  <?php else: ?>
    <a href="<?php echo $base; ?>/login.php" style="display:inline-block;padding:6px 14px;font-size:0.85rem;font-weight:500;color:#fff;background:#f59e0b;border:1px solid #f59e0b;border-radius:6px;text-decoration:none;transition:all 0.2s;" onmouseover="this.style.background='#d97706';" onmouseout="this.style.background='#f59e0b';">🔒 Login</a>
  <?php endif; ?>
  <span style="font-size:1.2rem;font-weight:600;color:#2563eb; margin-left:18px;">Setu Developers</span>
</div>
<!-- Apps Dropdown: must be outside header for absolute positioning -->
<div id="appsDropdown" class="apps-dropdown">
  <div class="apps-header">
    <div class="apps-header-icon">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
        <rect x="3" y="3" width="7" height="7" rx="1"/>
        <rect x="14" y="3" width="7" height="7" rx="1"/>
        <rect x="14" y="14" width="7" height="7" rx="1"/>
        <rect x="3" y="14" width="7" height="7" rx="1"/>
      </svg>
    </div>
    <div>
      <div class="apps-title">Applications</div>
      <div class="apps-subtitle">Choose your workspace</div>
    </div>
    <button class="apps-close" onclick="toggleDropdown()">×</button>
  </div>
  <div class="apps-search">
    <input type="text" class="apps-search-input" placeholder="Search apps..." onkeyup="filterApps(this.value)">
  </div>
  <div class="apps-grid" id="appsGrid">
    <div class="app-item" data-name="company" data-href="<?php echo $base; ?>/index.php" data-enabled="1">
      <div class="app-icon" style="background: #16a34a;">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M3 21V5a2 2 0 0 1 2-2h7v18" />
          <path d="M12 7h7a2 2 0 0 1 2 2v12" />
          <path d="M7 7h2M7 11h2M7 15h2" />
          <path d="M15 11h2M15 15h2" />
        </svg>
      </div>
      <div class="app-name">Company</div>
    </div>
    <div class="app-item" data-name="dashboard" data-href="<?php echo $base; ?>/dashboard/index.php" data-enabled="1">
      <div class="app-icon" style="background: #3b82f6;">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M4 19V5" />
          <path d="M4 19h16" />
          <path d="M8 17v-6" />
          <path d="M12 17V7" />
          <path d="M16 17v-4" />
        </svg>
      </div>
      <div class="app-name">Dashboard</div>
    </div>
    <div class="app-item" data-name="reception" data-href="<?php echo $base; ?>/index.php" data-enabled="1">
      <div class="app-icon" style="background: #2563eb;">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M6 17h12" />
          <path d="M8 17a4 4 0 1 1 8 0" />
          <path d="M12 5v2" />
          <path d="M10 7h4" />
        </svg>
      </div>
      <div class="app-name">Reception</div>
    </div>
    <div class="app-item" data-name="hr" data-href="<?php echo $base; ?>/hr/employees.php" data-enabled="1">
      <div class="app-icon" style="background: #eab308;">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M16 11a3 3 0 1 0-6 0 3 3 0 0 0 6 0Z" />
          <path d="M7 20a5 5 0 0 1 10 0" />
          <path d="M6 11a2 2 0 1 0-4 0 2 2 0 0 0 4 0Z" />
          <path d="M2.5 20a4.5 4.5 0 0 1 6.5-4" />
        </svg>
      </div>
      <div class="app-name">HR</div>
    </div>
    <div class="app-item" data-name="recruitment" data-href="<?php echo $base; ?>/index.php">
      <div class="app-icon" style="background: #a855f7;">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" />
          <path d="M14 3v5h5" />
          <path d="M9 13h6" />
          <path d="M9 17h4" />
        </svg>
      </div>
      <div class="app-name">Recruitment</div>
    </div>
    <div class="app-item" data-name="leads" data-href="<?php echo $base; ?>/leads/leads.php" data-enabled="1">
      <div class="app-icon" style="background: #06b6d4;">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M4 17l6-6 4 4 6-8" />
          <path d="M20 7v6h-6" />
        </svg>
      </div>
      <div class="app-name">Leads</div>
    </div>
    <div class="app-item" data-name="letters" data-href="<?php echo $base; ?>/index.php">
      <div class="app-icon" style="background: #14b8a6;">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M4 6h16v12H4z" />
          <path d="m4 7 8 6 8-6" />
        </svg>
      </div>
      <div class="app-name">Letters</div>
    </div>
    <div class="app-item" data-name="legal" data-href="<?php echo $base; ?>/index.php">
      <div class="app-icon" style="background: #8b5cf6;">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M12 3v18" />
          <path d="M7 6h10" />
          <path d="M6 6l-3 6h6z" />
          <path d="M18 6l-3 6h6z" />
          <path d="M9 20h6" />
        </svg>
      </div>
      <div class="app-name">Legal</div>
    </div>
    <div class="app-item" data-name="contractors" data-href="<?php echo $base; ?>/index.php">
      <div class="app-icon" style="background: #f97316;">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M20 20v-1a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v1" />
          <path d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" />
          <path d="M20 8l-2 2" />
          <path d="M22 10l-2-2" />
        </svg>
      </div>
      <div class="app-name">Contractors</div>
    </div>
  </div>
</div>
<script>
function toggleDropdown() {
  const dropdown = document.getElementById('appsDropdown');
  dropdown.classList.toggle('show');
}
document.addEventListener('DOMContentLoaded', function() {
  const appsBtn = document.getElementById('appsBtn');
  const dropdown = document.getElementById('appsDropdown');
  function positionDropdown() {
    // Temporarily show dropdown to measure width if hidden
    let wasHidden = !dropdown.classList.contains('show');
    if (wasHidden) {
      dropdown.style.visibility = 'hidden';
      dropdown.style.display = 'flex';
    }
    const rect = appsBtn.getBoundingClientRect();
    const dropdownWidth = dropdown.offsetWidth;
    let left = rect.left;
    // Default: align left edge with button
    // If dropdown overflows right edge, shift left so right edge is 12px from window edge
    if (left + dropdownWidth > window.innerWidth - 12) {
      left = window.innerWidth - dropdownWidth - 12;
      if (left < 12) left = 12; // Prevent from going off left edge
    }
    dropdown.style.left = left + 'px';
    // Move modal 20px further down
    dropdown.style.top = (rect.bottom + window.scrollY + 20) + 'px';
    // Hide again if it was hidden
    if (wasHidden) {
      dropdown.style.display = '';
      dropdown.style.visibility = '';
    }
  }
  appsBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    positionDropdown();
    toggleDropdown();
  });
  document.addEventListener('click', function(e) {
    if (!dropdown.contains(e.target) && !appsBtn.contains(e.target)) {
      dropdown.classList.remove('show');
    }
  });
  window.addEventListener('resize', function() {
    if (dropdown.classList.contains('show')) positionDropdown();
  });
  window.addEventListener('scroll', function() {
    if (dropdown.classList.contains('show')) positionDropdown();
  });
  document.querySelectorAll('.app-item').forEach(function(item) {
    item.addEventListener('click', function(e) {
      // Prefer explicit data-href if provided; check data-enabled flag for implemented apps
      const href = item.getAttribute('data-href');
      const enabled = item.getAttribute('data-enabled');
      if (href) {
        if (enabled === '1') {
          // Use assign to ensure navigation even if other handlers run
          window.location.assign(href);
          return;
        } else {
          alert('This app is not yet implemented.');
          return;
        }
      }
      // No explicit href: fallback to name mapping for a few known routes
      const name = item.getAttribute('data-name');
      if (name === 'hr') {
        window.location.assign('<?php echo $base; ?>/hr/employees.php');
      } else if (name === 'leads') {
        window.location.assign('<?php echo $base; ?>/leads/leads.php');
      } else if (name === 'dashboard') {
        window.location.assign('<?php echo $base; ?>/index.php');
      } else {
        alert('This app is not yet implemented.');
      }
    });
  });
});
function filterApps(search) {
  const items = document.querySelectorAll('.app-item');
  const query = search.toLowerCase();
  items.forEach(item => {
    const name = item.dataset.name.toLowerCase();
    if (name.includes(query)) {
      item.style.display = 'flex';
    } else {
      item.style.display = 'none';
    }
  });
}
</script>

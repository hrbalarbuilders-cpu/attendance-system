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
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(229, 231, 235, 0.5);
    padding: 0 24px;
    height: 64px;
    box-sizing: border-box;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
  }

  .header-left {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .header-brand {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
    letter-spacing: -0.02em;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .header-brand span {
    color: #2563eb;
  }

  .header-right {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .main-content-scroll {
    padding-top: 64px;
  }

  .apps-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(243, 244, 246, 0.8);
    border: 1px solid rgba(229, 231, 235, 0.6);
    font-size: 14px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    padding: 8px 14px;
    border-radius: 10px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .apps-btn:hover {
    background: #ffffff;
    border-color: #2563eb;
    color: #2563eb;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
    transform: translateY(-1px);
  }

  .header-user-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    background: #f8fafc;
    border-radius: 10px;
    border: 1px solid #f1f5f9;
    font-size: 0.875rem;
    font-weight: 500;
    color: #64748b;
  }

  .header-logout-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #ef4444;
    background: #fff;
    border: 1.5px solid #fee2e2;
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .header-logout-btn:hover {
    background: #ef4444;
    color: #fff;
    border-color: #ef4444;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15);
  }

  @media (max-width: 768px) {
    .header-fixed {
      padding: 0 16px;
    }

    .header-user-badge {
      display: none;
    }

    .header-brand {
      font-size: 1.1rem;
    }

    .header-brand .brand-full {
      display: none;
    }

    .header-brand::after {
      content: 'SD';
      color: #2563eb;
    }
  }

  .menu-icon {
    width: 18px;
    height: 18px;
  }

  .apps-dropdown {
    display: none;
    position: absolute;
    width: 320px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.15);
    z-index: 3000;
    padding: 0 0 20px 0;
    flex-direction: column;
    gap: 0;
    border: 1px solid rgba(229, 231, 235, 0.8);
    animation: slideDown 0.3s cubic-bezier(0, 0, 0.2, 1);
  }

  .apps-dropdown.show {
    display: flex;
  }

  @keyframes slideDown {
    from {
      opacity: 0;
      transform: translateY(10px) scale(0.95);
    }

    to {
      opacity: 1;
      transform: translateY(0) scale(1);
    }
  }

  .apps-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px 24px 0 24px;
  }

  .apps-header-icon {
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    border-radius: 12px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
  }

  .apps-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0px;
  }

  .apps-subtitle {
    font-size: 0.85rem;
    color: #64748b;
  }

  .apps-close {
    background: #f1f5f9;
    border: none;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    font-size: 1.25rem;
    color: #64748b;
    cursor: pointer;
    margin-left: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
  }

  .apps-close:hover {
    background: #e2e8f0;
    color: #0f172a;
  }

  .apps-search {
    padding: 16px 24px 8px 24px;
  }

  .apps-search-input {
    width: 100%;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    padding: 10px 16px;
    font-size: 0.95rem;
    outline: none;
    background: #f8fafc;
    transition: all 0.2s;
  }

  .apps-search-input:focus {
    background: #fff;
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
  }

  .header-fixed span {
    white-space: nowrap;
    overflow: visible;
    text-overflow: ellipsis;
  }

  .apps-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    padding: 12px 16px 0 16px;
  }

  .app-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    border-radius: 14px;
    padding: 12px 4px;
    transition: all 0.2s ease;
  }

  .app-item:hover,
  .app-item:focus {
    background: #f8fafc;
    transform: translateY(-2px);
  }

  .app-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 2px;
    transition: transform 0.2s;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  }

  .app-item:hover .app-icon {
    transform: scale(1.05);
  }

  .app-icon svg {
    width: 24px;
    height: 24px;
    display: block;
    stroke: #fff;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  .app-icon svg .icon-fill {
    fill: #fff;
    stroke: none;
  }

  .app-name {
    font-size: 0.85rem;
    font-weight: 600;
    color: #334155;
  }
</style>
<?php
// Compute web base path for admin folder so Apps links work regardless of document root.
// If the script path contains '/admin', use that as the root (handles nested folders like /attendance/admin/hr).
$script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$adminPos = strpos($script, '/admin');
if ($adminPos !== false) {
  $base = substr($script, 0, $adminPos + strlen('/admin'));
} else {
  $base = rtrim(dirname($script), '/');
}
?>
<div class="header-fixed">
  <div class="header-left">
    <a href="<?php echo $base; ?>/index.php" class="header-brand">
      <div
        style="background: #2563eb; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 900; font-size: 20px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);">
        S</div>
      <span class="brand-full">Setu Developers</span>
    </a>
  </div>

  <div class="header-right">
    <button class="apps-btn" id="appsBtn">
      <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <rect x="3" y="3" width="7" height="7" rx="1.5" />
        <rect x="14" y="3" width="7" height="7" rx="1.5" />
        <rect x="14" y="14" width="7" height="7" rx="1.5" />
        <rect x="3" y="14" width="7" height="7" rx="1.5" />
      </svg>
      Apps
    </button>

    <?php if ($isLoggedIn): ?>
      <div class="header-user-badge">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
          <path
            d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4Zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10Z" />
        </svg>
        <?php echo htmlspecialchars($loggedInName); ?>
      </div>
      <a href="<?php echo $base; ?>/logout.php" class="header-logout-btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
          <path fill-rule="evenodd"
            d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z" />
          <path fill-rule="evenodd"
            d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z" />
        </svg>
        Logout
      </a>
    <?php else: ?>
      <a href="<?php echo $base; ?>/login.php" class="header-logout-btn" style="color: #2563eb; border-color: #dbeafe;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
          <path
            d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z" />
        </svg>
        Login
      </a>
    <?php endif; ?>
  </div>
</div>
<!-- Apps Dropdown: must be outside header for absolute positioning -->
<div id="appsDropdown" class="apps-dropdown">
  <div class="apps-header">
    <div class="apps-header-icon">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
        <rect x="3" y="3" width="7" height="7" rx="1" />
        <rect x="14" y="3" width="7" height="7" rx="1" />
        <rect x="14" y="14" width="7" height="7" rx="1" />
        <rect x="3" y="14" width="7" height="7" rx="1" />
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
  document.addEventListener('DOMContentLoaded', function () {
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
    appsBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      positionDropdown();
      toggleDropdown();
    });
    document.addEventListener('click', function (e) {
      if (!dropdown.contains(e.target) && !appsBtn.contains(e.target)) {
        dropdown.classList.remove('show');
      }
    });
    window.addEventListener('resize', function () {
      if (dropdown.classList.contains('show')) positionDropdown();
    });
    window.addEventListener('scroll', function () {
      if (dropdown.classList.contains('show')) positionDropdown();
    });
    document.querySelectorAll('.app-item').forEach(function (item) {
      item.addEventListener('click', function (e) {
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
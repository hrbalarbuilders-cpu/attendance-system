<?php
// Shift Roster (Day-wise) - HR tab

include '../config/db.php';

$ajax = isset($_GET['ajax']) && $_GET['ajax'];
$view = isset($_GET['view']) ? strtolower(trim($_GET['view'])) : 'week';
if (!in_array($view, ['week', 'month'], true)) {
  $view = 'week';
}

$startParam = isset($_GET['start']) ? trim($_GET['start']) : '';
$baseTs = $startParam ? strtotime($startParam) : time();
if ($baseTs === false)
  $baseTs = time();

// Early return for AJAX after getting params but before rendering HTML blocks if possible? 
// Shift roster logic is mixed. Let's optimize queries first, then handle render.
// Actually, for consistency, we should check AJAX render at the end, BUT we can skip "Full Page Wrapper" logic.
// However, the main cost here is the loop over employees * days.
// Let's rely on the fact that existing code does `if ($ajax) { render...; exit; }` near line 396.
// But we can optimize the SQL queries.

if ($view === 'week') {
  // Sunday-based week (matches reference image)
  $dow = (int) date('w', $baseTs); // 0=Sun
  $rangeStartTs = strtotime('-' . $dow . ' days', $baseTs);
  $rangeEndTs = strtotime('+6 days', $rangeStartTs);
} else {
  $rangeStartTs = strtotime(date('Y-m-01', $baseTs));
  $rangeEndTs = strtotime(date('Y-m-t', $baseTs));
}

$rangeStart = date('Y-m-d', $rangeStartTs);
$rangeEnd = date('Y-m-d', $rangeEndTs);

$days = [];
$iter = new DateTime($rangeStart);
$iterEnd = new DateTime($rangeEnd);
$iterEnd->setTime(0, 0, 0);
while ($iter <= $iterEnd) {
  $days[] = [
    'date' => $iter->format('Y-m-d'),
    'dow' => $iter->format('l'),
    'day' => $iter->format('j'),
  ];
  $iter->modify('+1 day');
}

// Holidays in range
$holidaysByDate = [];
$holidaySql = "SELECT holiday_date, holiday_name FROM holidays WHERE holiday_date BETWEEN ? AND ?";
if ($stmt = $con->prepare($holidaySql)) {
  $stmt->bind_param('ss', $rangeStart, $rangeEnd);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $holidaysByDate[$row['holiday_date']] = $row['holiday_name'];
  }
  $stmt->close();
}

// Approved leaves in range (expanded to per-employee-per-date map)
$leavesByEmployeeDate = []; // [empId][Y-m-d] => label
$leaveSql = "
  SELECT la.user_id, la.from_date, la.to_date, lt.code, lt.name
  FROM leave_applications la
  JOIN leave_types lt ON lt.id = la.leave_type_id
  WHERE la.status = 'approved'
    AND la.to_date >= ?
    AND la.from_date <= ?
";
if ($stmt = $con->prepare($leaveSql)) {
  $stmt->bind_param('ss', $rangeStart, $rangeEnd);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $empId = (int) $row['user_id'];
    $from = $row['from_date'];
    $to = $row['to_date'];
    $label = !empty($row['code']) ? $row['code'] : ($row['name'] ?? 'Leave');

    $d = new DateTime(max($from, $rangeStart));
    $dEnd = new DateTime(min($to, $rangeEnd));
    $dEnd->setTime(0, 0, 0);
    while ($d <= $dEnd) {
      $dayKey = $d->format('Y-m-d');
      if (!isset($leavesByEmployeeDate[$empId]))
        $leavesByEmployeeDate[$empId] = [];
      $leavesByEmployeeDate[$empId][$dayKey] = $label;
      $d->modify('+1 day');
    }
  }
  $stmt->close();
}

// Employees
$employees = [];
$empSql = "
  SELECT e.user_id, e.name, e.weekoff_days, dsg.designation_name,
         s.shift_name, s.shift_color, s.start_time, s.end_time
  FROM employees e
  LEFT JOIN designations dsg ON dsg.id = e.designation_id
  LEFT JOIN shifts s ON s.id = e.shift_id
  WHERE e.status = 1
  ORDER BY e.name ASC
";
$res = $con->query($empSql);
if ($res) {
  while ($row = $res->fetch_assoc()) {
    $employees[] = $row;
  }
}

function formatShiftTime($timeStr)
{
  if (!$timeStr)
    return '';
  $ts = strtotime($timeStr);
  if ($ts === false)
    return '';
  return date('h:i A', $ts);
}

function initials($name)
{
  $name = trim((string) $name);
  if ($name === '')
    return '';
  $parts = preg_split('/\s+/', $name);
  $first = strtoupper(substr($parts[0] ?? '', 0, 1));
  $second = '';
  if (count($parts) > 1) {
    $second = strtoupper(substr($parts[1] ?? '', 0, 1));
  }
  return $first . $second;
}

function safeHexColor($color, $fallback = '#0d6efd')
{
  $color = trim((string) $color);
  if ($color === '')
    return $fallback;
  if (preg_match('/^#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})$/', $color))
    return $color;
  return $fallback;
}

function textColorForBg($hex)
{
  $hex = ltrim((string) $hex, '#');
  if (strlen($hex) === 3) {
    $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
  }
  if (strlen($hex) !== 6)
    return '#fff';
  $r = hexdec(substr($hex, 0, 2));
  $g = hexdec(substr($hex, 2, 2));
  $b = hexdec(substr($hex, 4, 2));
  // Perceived luminance
  $l = (0.299 * $r + 0.587 * $g + 0.114 * $b);
  return ($l > 160) ? '#111827' : '#ffffff';
}

$rangeLabel = '';
if ($view === 'week') {
  $rangeLabel = date('M j', $rangeStartTs) . ' – ' . date('M j, Y', $rangeEndTs);
} else {
  $rangeLabel = date('F Y', $rangeStartTs);
}

// For navigation buttons
$rangeStartIso = date('Y-m-d', $rangeStartTs);

?>

<?php
function renderShiftRosterContent($days, $employees, $holidaysByDate, $leavesByEmployeeDate, $rangeLabel, $view, $rangeStartIso)
{
  ?>
  <style>
    .roster-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
    }

    .roster-range {
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 600;
    }

    .roster-grid-wrap {
      overflow-x: auto;
    }

    .roster-table {
      min-width: 920px;
    }

    .roster-table th,
    .roster-table td {
      vertical-align: middle;
    }

    .roster-emp-col {
      min-width: 260px;
      position: sticky;
      left: 0;
      background: #fff;
      z-index: 2;
    }

    .roster-emp-col-head {
      position: sticky;
      left: 0;
      background: #fff;
      z-index: 3;
    }

    .roster-cell {
      min-width: 160px;
    }

    .roster-shift-pill {
      background: var(--bs-primary);
      color: #fff;
      border-radius: 10px;
      padding: 10px 12px;
      font-weight: 600;
      line-height: 1.1;
      display: inline-block;
      width: 100%;
    }

    .roster-shift-pill small {
      font-weight: 600;
      opacity: .95;
      display: block;
      margin-top: 6px;
    }

    .roster-muted-pill {
      background: var(--bs-light);
      border: 1px solid var(--bs-border-color);
      color: var(--bs-secondary);
      border-radius: 10px;
      padding: 10px 12px;
      font-weight: 600;
      line-height: 1.1;
      display: inline-block;
      width: 100%;
    }

    .roster-emp {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .roster-avatar {
      width: 42px;
      height: 42px;
      border-radius: 999px;
      background: #cbd5e1;
      color: #0f172a;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
    }
  </style>

  <div class="card card-main">
    <div class="card-header card-main-header py-3">
      <div class="roster-toolbar">
        <div class="roster-range">
          <button type="button" class="btn btn-light btn-sm" id="shiftRosterPrev" aria-label="Previous">&lsaquo;</button>
          <div id="shiftRosterRangeText"><?php echo htmlspecialchars($rangeLabel); ?></div>
          <button type="button" class="btn btn-light btn-sm" id="shiftRosterNext" aria-label="Next">&rsaquo;</button>
        </div>

        <div class="btn-group" role="group" aria-label="View Toggle">
          <button type="button" class="btn btn-outline-dark btn-sm" id="shiftRosterWeekBtn" data-view="week">Week</button>
          <button type="button" class="btn btn-outline-dark btn-sm" id="shiftRosterMonthBtn"
            data-view="month">Month</button>
        </div>
      </div>
    </div>

    <div class="card-body p-0">
      <div id="shiftRosterMeta" data-view="<?php echo htmlspecialchars($view); ?>"
        data-start="<?php echo htmlspecialchars($rangeStartIso); ?>"></div>
      <div class="roster-grid-wrap">
        <table class="table table-hover align-middle mb-0 roster-table">
          <thead>
            <tr>
              <th class="roster-emp-col-head">View by employees</th>
              <?php foreach ($days as $d): ?>
                <th class="text-nowrap">
                  <div class="fw-semibold"><?php echo htmlspecialchars($d['dow']); ?></div>
                  <div class="text-muted fs-5 fw-bold"><?php echo htmlspecialchars($d['day']); ?></div>
                </th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php if (count($employees) === 0): ?>
              <tr>
                <td colspan="<?php echo 1 + count($days); ?>" class="text-center text-muted py-4">No employees found.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($employees as $emp): ?>
                <?php
                $empId = (int) $emp['user_id'];
                $empWeekoffs = array_filter(array_map('trim', explode(',', (string) ($emp['weekoff_days'] ?? ''))));
                $shiftName = $emp['shift_name'] ?? '';
                $shiftBg = safeHexColor($emp['shift_color'] ?? '#0d6efd');
                $shiftFg = textColorForBg($shiftBg);
                $startT = formatShiftTime($emp['start_time'] ?? '');
                $endT = formatShiftTime($emp['end_time'] ?? '');
                $shiftTimeText = ($startT && $endT) ? ($startT . ' - ' . $endT) : '';
                ?>
                <tr>
                  <td class="roster-emp-col">
                    <div class="roster-emp">
                      <div class="roster-avatar"><?php echo htmlspecialchars(initials($emp['name'] ?? '')); ?></div>
                      <div>
                        <div class="fw-semibold"><?php echo htmlspecialchars($emp['name'] ?? ''); ?></div>
                        <div class="text-muted small"><?php echo htmlspecialchars($emp['designation_name'] ?? ''); ?>
                        </div>
                      </div>
                    </div>
                  </td>

                  <?php foreach ($days as $d): ?>
                    <?php
                    $dateKey = $d['date'];
                    $dowName = $d['dow'];

                    $holidayName = $holidaysByDate[$dateKey] ?? null;
                    $leaveLabel = $leavesByEmployeeDate[$empId][$dateKey] ?? null;
                    $isWeekoff = in_array($dowName, $empWeekoffs, true);

                    $pillTextTop = '';
                    $pillTextBottom = '';
                    $pillType = 'shift';

                    if ($holidayName) {
                      $pillType = 'muted';
                      $pillTextTop = $holidayName;
                      $pillTextBottom = 'Holiday';
                    } elseif ($leaveLabel) {
                      $pillType = 'muted';
                      $pillTextTop = $leaveLabel;
                      $pillTextBottom = 'Leave';
                    } elseif ($isWeekoff) {
                      $pillType = 'muted';
                      $pillTextTop = 'Week Off';
                      $pillTextBottom = '';
                    } elseif ($shiftName) {
                      $pillType = 'shift';
                      $pillTextTop = $shiftName;
                      $pillTextBottom = $shiftTimeText;
                    } else {
                      $pillType = 'muted';
                      $pillTextTop = '-';
                      $pillTextBottom = '';
                    }
                    ?>
                    <td class="roster-cell">
                      <?php if ($pillType === 'shift'): ?>
                        <div class="roster-shift-pill"
                          style="background: <?php echo htmlspecialchars($shiftBg); ?>; color: <?php echo htmlspecialchars($shiftFg); ?>;">
                          <div class="text-truncate">🕒 <?php echo htmlspecialchars($pillTextTop); ?></div>
                          <?php if ($pillTextBottom): ?><small><?php echo htmlspecialchars($pillTextBottom); ?></small><?php endif; ?>
                        </div>
                      <?php else: ?>
                        <div class="roster-muted-pill">
                          <div class="text-truncate"><?php echo htmlspecialchars($pillTextTop); ?></div>
                          <?php if ($pillTextBottom): ?><small><?php echo htmlspecialchars($pillTextBottom); ?></small><?php endif; ?>
                        </div>
                      <?php endif; ?>
                    </td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php
} // end renderShiftRosterContent

// If AJAX, just render
if ($ajax) {
  renderShiftRosterContent($days, $employees, $holidaysByDate, $leavesByEmployeeDate, $rangeLabel, $view, $rangeStartIso);
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Shift Roster</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="css/hr_dashboard.css" rel="stylesheet">
  <?php include_once __DIR__ . '/../includes/table-styles.php'; ?>
</head>

<body>
  <?php include_once '../includes/header.php'; ?>

  <div class="main-content-scroll">
    <div id="loaderOverlay" class="d-none">
      <div class="loader-spinner"></div>
    </div>

    <div class="container-fluid py-3 d-flex justify-content-center" style="padding-top:72px;">
      <div class="page-wrapper w-100">
        <div class="d-flex justify-content-center align-items-center gap-3 mb-4">
          <?php include_once __DIR__ . '/../includes/navbar-hr.php'; ?>
          <a href="settings.php" class="btn-round-icon" title="Settings">
            <i class="bi bi-gear-fill"></i>
          </a>
        </div>

        <div id="contentArea">
          <?php renderShiftRosterContent($days, $employees, $holidaysByDate, $leavesByEmployeeDate, $rangeLabel, $view, $rangeStartIso) ?>
        </div>
      </div>
    </div>

    <!-- Shared HR Modals -->
    <?php include __DIR__ . '/../includes/hr-modals.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/utils.js"></script>
    <script src="js/tab_handlers.js"></script>
    <script src="js/form_handlers.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        // Initial init for shift roster tab
        if (typeof initShiftRosterTabEvents === 'function') {
          initShiftRosterTabEvents();
        }
      });
    </script>
  </div>
</body>

</html>
<?php
// API: get_leave_balances.php
// Returns leave balances (available days) for leave types assigned to an employee
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

include 'db.php';

$emp_id = 0;
if (isset($_GET['employee_id'])) {
    $emp_id = (int)$_GET['employee_id'];
} elseif (isset($_GET['emp_id'])) {
    $emp_id = (int)$_GET['emp_id'];
}

if ($emp_id <= 0) {
    echo json_encode(['status' => 'error', 'msg' => 'Invalid employee ID']);
    exit;
}

try {
    // Get leave types assigned to this employee
    $q = "SELECT lt.id, lt.code, lt.name, lt.yearly_quota FROM leave_types lt
          JOIN leave_type_employees lte ON lt.id = lte.leave_type_id
          WHERE lte.employee_id = $emp_id AND lt.is_active = 1";
    $res = $con->query($q);
    $balances = [];
    // If this employee has no specific assignments, fall back to all active leave types
    if (!$res || $res->num_rows === 0) {
        $res = $con->query("SELECT id, code, name, yearly_quota FROM leave_types WHERE is_active = 1 ORDER BY name ASC");
    }
    while ($row = $res->fetch_assoc()) {
        $ltid = (int)$row['id'];
        $quota = (int)$row['yearly_quota'];
        // Calculate used days for approved leaves in the current year
        $yearStart = date('Y-01-01');
        $yearEnd = date('Y-12-31');
        // Calculate approved used days within the year using a straightforward query
        $usedSql = "SELECT COALESCE(SUM(DATEDIFF(LEAST(to_date, '$yearEnd'), GREATEST(from_date, '$yearStart')) + 1),0) AS used_days
                    FROM leave_applications
                    WHERE employee_id = $emp_id AND leave_type_id = $ltid AND status = 'approved' AND NOT (to_date < '$yearStart' OR from_date > '$yearEnd')";
        $uRes = $con->query($usedSql);
        $usedDays = 0;
        if ($uRes) {
            $r = $uRes->fetch_assoc();
            $usedDays = (int)$r['used_days'];
        }

        $available = max(0, $quota - $usedDays);
        $balances[] = [
            'id' => $ltid,
            'code' => $row['code'],
            'name' => $row['name'],
            'yearly_quota' => $quota,
            'used' => $usedDays,
            'available' => $available,
        ];
    }
    echo json_encode(['status' => 'success', 'balances' => $balances]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
$con->close();

<?php
// API: get_leave_types.php
// Returns leave types assigned to a particular employee
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

include 'db.php';

// Accept either `employee_id` (client expectation) or `emp_id` (existing server file)
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
    // Get leave_type_ids assigned to this employee
    $q = "SELECT leave_type_id FROM leave_type_employees WHERE employee_id = $emp_id";
    $res = $con->query($q);
    $leaveTypeIds = [];
    while ($row = $res->fetch_assoc()) {
        $leaveTypeIds[] = (int)$row['leave_type_id'];
    }
    if (empty($leaveTypeIds)) {
        echo json_encode(['status' => 'success', 'leave_types' => []]);
        $con->close();
        exit;
    }
    // Get leave type details
    $ids = implode(',', $leaveTypeIds);
    $q2 = "SELECT id, code, name FROM leave_types WHERE id IN ($ids) AND is_active = 1 ORDER BY name ASC";
    $res2 = $con->query($q2);
    if (!$res2) {
        echo json_encode(['status' => 'error', 'msg' => 'Query failed: ' . $con->error]);
        $con->close();
        exit;
    }
    $leaveTypes = [];
    while ($row2 = $res2->fetch_assoc()) {
        $leaveTypes[] = [
            'id' => (int)$row2['id'],
            'code' => $row2['code'],
            'name' => $row2['name'],
        ];
    }
    echo json_encode(['status' => 'success', 'leave_types' => $leaveTypes]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
$con->close();

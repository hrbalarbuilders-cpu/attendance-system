<?php
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

// How many days ahead to include (including today)
$windowDays = isset($_GET['days']) ? max(0, (int) $_GET['days']) : 7;

try {
    // Fetch active employees with DOB and joining_date (if any)
    $sql = "SELECT user_id, name, dob, joining_date, status FROM employees WHERE status = 1";
    $res = $con->query($sql);

    if (!$res) {
        echo json_encode([
            'status' => 'error',
            'msg' => 'DB query failed',
        ]);
        exit;
    }

    $today = new DateTime('today');
    $currentYear = (int) $today->format('Y');

    $events = [];

    while ($row = $res->fetch_assoc()) {
        $empId = (int) $row['user_id'];
        $name = $row['name'];

        // Birthday
        if (!empty($row['dob']) && $row['dob'] !== '0000-00-00') {
            $dob = DateTime::createFromFormat('Y-m-d', $row['dob']);
            if ($dob) {
                $mmdd = $dob->format('m-d');
                $occurrence = DateTime::createFromFormat('Y-m-d', $currentYear . '-' . $mmdd);
                if ($occurrence < $today) {
                    $occurrence->modify('+1 year');
                }
                $daysUntil = (int) $today->diff($occurrence)->format('%a');
                if ($daysUntil <= $windowDays) {
                    $events[] = [
                        'id' => $empId,
                        'name' => $name,
                        'type' => 'birthday',
                        'date' => $occurrence->format('Y-m-d'),
                        'days_until' => $daysUntil,
                    ];
                }
            }
        }

        // Work Anniversary
        if (!empty($row['joining_date']) && $row['joining_date'] !== '0000-00-00') {
            $jd = DateTime::createFromFormat('Y-m-d', $row['joining_date']);
            if ($jd) {
                $mmdd = $jd->format('m-d');
                $occurrence = DateTime::createFromFormat('Y-m-d', $currentYear . '-' . $mmdd);
                if ($occurrence < $today) {
                    $occurrence->modify('+1 year');
                }
                $daysUntil = (int) $today->diff($occurrence)->format('%a');
                if ($daysUntil <= $windowDays) {
                    // Years completed on upcoming anniversary
                    $years = (int) $occurrence->format('Y') - (int) $jd->format('Y');
                    if ($years < 0) {
                        $years = 0;
                    }
                    $events[] = [
                        'id' => $empId,
                        'name' => $name,
                        'type' => 'anniversary',
                        'date' => $occurrence->format('Y-m-d'),
                        'days_until' => $daysUntil,
                        'years' => $years,
                    ];
                }
            }
        }
    }

    // Sort by days_until, then by name
    usort($events, function ($a, $b) {
        if ($a['days_until'] === $b['days_until']) {
            return strcasecmp($a['name'], $b['name']);
        }
        return $a['days_until'] <=> $b['days_until'];
    });

    echo json_encode([
        'status' => 'success',
        'data' => $events,
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'msg' => 'Unexpected error',
    ]);
}

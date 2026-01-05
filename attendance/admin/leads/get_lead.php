<?php
header('Content-Type: application/json');
// suppress direct error display and convert any PHP errors to JSON for the client
ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();
register_shutdown_function(function(){
	$err = error_get_last();
	if ($err){
		http_response_code(500);
		// don't expose internal debug output to clients in production
		echo json_encode(['success'=>false,'message'=>'Server error']);
		exit;
	}
});
include_once __DIR__ . '/../config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id){ echo json_encode(['success'=>false,'message'=>'Missing id']); exit; }

// build a safe SELECT expression for source name depending on available columns
$hasName = false; $hasTitle = false;
$col = $con->query("SHOW COLUMNS FROM lead_sources LIKE 'name'"); if ($col && $col->num_rows) $hasName = true;
$col = $con->query("SHOW COLUMNS FROM lead_sources LIKE 'title'"); if ($col && $col->num_rows) $hasTitle = true;
$selectSourceExpr = '';
if ($hasName && $hasTitle) {
	$selectSourceExpr = "COALESCE(ls.name, ls.title) AS lead_source_name";
} elseif ($hasName) {
	$selectSourceExpr = "ls.name AS lead_source_name";
} elseif ($hasTitle) {
	$selectSourceExpr = "ls.title AS lead_source_name";
} else {
	$selectSourceExpr = "ls.id AS lead_source_name";
}

$sql = "SELECT l.*, " . $selectSourceExpr . " FROM leads l LEFT JOIN lead_sources ls ON l.lead_source_id = ls.id WHERE l.id = ? LIMIT 1";
$stmt = $con->prepare($sql);
if (!$stmt){ ob_end_clean(); echo json_encode(['success'=>false,'message'=>'DB prepare failed']); exit; }
$stmt->bind_param('i', $id);
if (!$stmt->execute()){
	// avoid leaking internal DB errors to clients
	ob_end_clean();
	echo json_encode(['success'=>false,'message'=>'Execute failed']);
	exit;
}
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row){ ob_end_clean(); echo json_encode(['success'=>false,'message'=>'Not found']); exit; }

// return data (do not include debug buffer)
ob_end_clean();
echo json_encode(['success'=>true,'data'=>$row]);

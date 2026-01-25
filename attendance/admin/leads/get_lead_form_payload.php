<?php
header('Content-Type: application/json');
// suppress direct error display and convert any PHP errors to JSON for the client
ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();
register_shutdown_function(function () {
	$err = error_get_last();
	if ($err) {
		http_response_code(500);
		echo json_encode(['success' => false, 'message' => 'Server error']);
		exit;
	}
});

function respond_json(int $statusCode, array $payload): void
{
	http_response_code($statusCode);
	ob_end_clean();
	echo json_encode($payload);
	exit;
}

include_once __DIR__ . '/../config/db.php';

if (!isset($con) || !($con instanceof mysqli)) {
	respond_json(500, ['success' => false, 'message' => 'DB connection not available']);
}
if (!empty($con->connect_error)) {
	respond_json(500, ['success' => false, 'message' => 'DB connection failed']);
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$lookingForIdParam = isset($_GET['looking_for_id']) ? intval($_GET['looking_for_id']) : 0;
$typeIdParam = isset($_GET['type_id']) ? intval($_GET['type_id']) : 0;
$includeLookupsParam = isset($_GET['include_lookups']) ? (string) $_GET['include_lookups'] : '';
$perPage = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 1000;

// For dependent-only requests (option 1), default to not fetching big lookup lists.
$dependentOnly = ($id <= 0) && (($lookingForIdParam > 0) || ($typeIdParam > 0));
$includeLookups = true;
if ($includeLookupsParam !== '') {
	$includeLookups = ($includeLookupsParam === '1' || strtolower($includeLookupsParam) === 'true');
} elseif ($dependentOnly) {
	$includeLookups = false;
}

$out = [
	'success' => true,
	'message' => 'OK',
	'sources' => [],
	'sales' => [],
	'lookings' => [],
	'lead' => null,
	'types' => [],
	'subtypes' => [],
	'types_for_looking_for_id' => null,
	'subtypes_for_type_id' => null
];

if ($includeLookups) {
	// --- sources ---
	$res = $con->query("SELECT * FROM lead_sources ORDER BY id DESC");
	if ($res && $res->num_rows) {
		while ($r = $res->fetch_assoc()) {
			if (!isset($r['name']) && isset($r['title']))
				$r['name'] = $r['title'];
			$out['sources'][] = [
				'id' => (int) ($r['id'] ?? 0),
				'name' => (string) ($r['name'] ?? ''),
				'status' => isset($r['status']) ? (string) $r['status'] : ''
			];
		}
	}

	// --- sales persons ---
	$tbl = $con->query("SHOW TABLES LIKE 'sales_persons'");
	if ($tbl && $tbl->num_rows) {
		$res = $con->query(
			"SELECT sp.id, sp.user_id, sp.status, e.name " .
			"FROM sales_persons sp JOIN employees e ON sp.user_id = e.user_id " .
			"ORDER BY sp.id DESC LIMIT " . (int) $perPage
		);
		if ($res && $res->num_rows) {
			while ($r = $res->fetch_assoc()) {
				$out['sales'][] = [
					'id' => (int) ($r['id'] ?? 0),
					'user_id' => (int) ($r['user_id'] ?? 0),
					'name' => (string) ($r['name'] ?? ''),
					'status' => isset($r['status']) ? (string) $r['status'] : ''
				];
			}
		}
	}

	// --- lookings ---
	$con->query("CREATE TABLE IF NOT EXISTS lead_looking_for (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), status VARCHAR(32) DEFAULT 'active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP)");
	$res = $con->query("SELECT id, name, status FROM lead_looking_for ORDER BY name ASC");
	if ($res && $res->num_rows) {
		while ($r = $res->fetch_assoc()) {
			$out['lookings'][] = $r;
		}
	}
}

// --- lead (optional) ---
if ($id > 0) {
	$hasName = false;
	$hasTitle = false;
	$col = $con->query("SHOW COLUMNS FROM lead_sources LIKE 'name'");
	if ($col && $col->num_rows)
		$hasName = true;
	$col = $con->query("SHOW COLUMNS FROM lead_sources LIKE 'title'");
	if ($col && $col->num_rows)
		$hasTitle = true;
	if ($hasName && $hasTitle) {
		$selectSourceExpr = "COALESCE(ls.name, ls.title) AS lead_source_name";
	} elseif ($hasName) {
		$selectSourceExpr = "ls.name AS lead_source_name";
	} elseif ($hasTitle) {
		$selectSourceExpr = "ls.title AS lead_source_name";
	} else {
		$selectSourceExpr = "ls.id AS lead_source_name";
	}

	$selectLookingFor = "'' AS looking_for_name";
	$selectLfTypeName = "'' AS looking_for_type_name";
	$tblLf = $con->query("SHOW TABLES LIKE 'lead_looking_for'");
	if ($tblLf && $tblLf->num_rows) {
		$selectLookingFor = "(SELECT name FROM lead_looking_for lf WHERE lf.id = l.looking_for_id LIMIT 1) AS looking_for_name";
	}
	$tblLfTypes = $con->query("SHOW TABLES LIKE 'lead_looking_for_types'");
	if ($tblLfTypes && $tblLfTypes->num_rows) {
		$selectLfTypeName = "(SELECT name FROM lead_looking_for_types t2 WHERE t2.id = l.looking_for_type_id LIMIT 1) AS looking_for_type_name";
	}

	$sql = "SELECT l.*, " . $selectSourceExpr . ", " . $selectLookingFor . ", " . $selectLfTypeName . " " .
		"FROM leads l LEFT JOIN lead_sources ls ON l.lead_source_id = ls.id WHERE l.id = ? LIMIT 1";
	$stmt = $con->prepare($sql);
	if (!$stmt) {
		respond_json(500, ['success' => false, 'message' => 'DB prepare failed']);
	}
	$stmt->bind_param('i', $id);
	if (!$stmt->execute()) {
		$stmt->close();
		respond_json(500, ['success' => false, 'message' => 'DB execute failed']);
	}
	$res = $stmt->get_result();
	$row = $res ? $res->fetch_assoc() : null;
	$stmt->close();
	if (!$row) {
		respond_json(404, ['success' => false, 'message' => 'Lead not found']);
	}
	$out['lead'] = $row;
}

// --- dependent lookups (types/subtypes) ---
// If editing, derive ids from the lead. Otherwise accept query params.
$lookingForId = 0;
$typeId = 0;
if ($out['lead'] && is_array($out['lead'])) {
	$lookingForId = isset($out['lead']['looking_for_id']) ? intval($out['lead']['looking_for_id']) : 0;
	$typeId = isset($out['lead']['looking_for_type_id']) ? intval($out['lead']['looking_for_type_id']) : 0;
} else {
	$lookingForId = $lookingForIdParam;
	$typeId = $typeIdParam;
}

if ($lookingForId > 0) {
	$out['types_for_looking_for_id'] = $lookingForId;
	$con->query("CREATE TABLE IF NOT EXISTS lead_looking_for_types (id INT AUTO_INCREMENT PRIMARY KEY, looking_for_id INT NOT NULL, name VARCHAR(255), FOREIGN KEY (looking_for_id) REFERENCES lead_looking_for(id) ON DELETE CASCADE)");
	$res = $con->query('SELECT id, name FROM lead_looking_for_types WHERE looking_for_id=' . (int) $lookingForId . ' ORDER BY id ASC');
	if ($res && $res->num_rows) {
		while ($r = $res->fetch_assoc()) {
			$out['types'][] = ['id' => (int) $r['id'], 'name' => $r['name']];
		}
	}
}

if ($typeId > 0) {
	$out['subtypes_for_type_id'] = $typeId;
	$con->query("CREATE TABLE IF NOT EXISTS lead_looking_for_type_subtypes (id INT AUTO_INCREMENT PRIMARY KEY, type_id INT NOT NULL, name VARCHAR(255), FOREIGN KEY (type_id) REFERENCES lead_looking_for_types(id) ON DELETE CASCADE)");
	$res = $con->query('SELECT id, name FROM lead_looking_for_type_subtypes WHERE type_id=' . (int) $typeId . ' ORDER BY id ASC');
	if ($res && $res->num_rows) {
		while ($r = $res->fetch_assoc()) {
			$out['subtypes'][] = ['id' => (int) $r['id'], 'name' => $r['name']];
		}
	}
}

ob_end_clean();
echo json_encode($out);

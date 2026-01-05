<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../config/db.php';



$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$contact_number = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$looking_for_id = isset($_POST['looking_for_id']) ? intval($_POST['looking_for_id']) : 0;
// new selections
$looking_for_type_id = isset($_POST['looking_for_type_id']) ? (int)$_POST['looking_for_type_id'] : null;
$looking_for_subtype_ids = '';
if (isset($_POST['looking_for_subtype_ids'])){
    // support both single hidden csv or array
    if (is_array($_POST['looking_for_subtype_ids'])) $looking_for_subtype_ids = implode(',', array_map('intval', $_POST['looking_for_subtype_ids']));
    else $looking_for_subtype_ids = trim((string)$_POST['looking_for_subtype_ids']);
}
$lead_source_id = isset($_POST['lead_source_id']) ? intval($_POST['lead_source_id']) : 0;
$sales_person = isset($_POST['sales_person']) ? trim($_POST['sales_person']) : '';
$profile = isset($_POST['profile']) ? trim($_POST['profile']) : '';
$pincode = isset($_POST['pincode']) ? trim($_POST['pincode']) : '';
$city = isset($_POST['city']) ? trim($_POST['city']) : '';
$state = isset($_POST['state']) ? trim($_POST['state']) : '';
$country = isset($_POST['country']) ? trim($_POST['country']) : '';
$reference = isset($_POST['reference']) ? trim($_POST['reference']) : '';
$purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : '';
$lead_status = isset($_POST['lead_status']) ? trim($_POST['lead_status']) : 'New';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

if ($name === ''){ echo json_encode(['success'=>false,'message'=>'Name is required']); exit; }

// ensure columns exist (safe alter if needed)
$con->query("ALTER TABLE leads ADD COLUMN IF NOT EXISTS looking_for_type_id INT NULL");
$con->query("ALTER TABLE leads ADD COLUMN IF NOT EXISTS looking_for_subtypes TEXT NULL");

$stmt = $con->prepare("INSERT INTO leads (name, contact_number, email, looking_for_id, looking_for_type_id, looking_for_subtypes, lead_source_id, sales_person, profile, pincode, city, state, country, reference, purpose, lead_status, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
if (!$stmt){ echo json_encode(['success'=>false,'message'=>'DB prepare failed']); exit; }
$types = 'sssii' . 's' . 'i' . 'ssssssssss';
$stmt->bind_param($types, $name, $contact_number, $email, $looking_for_id, $looking_for_type_id, $looking_for_subtype_ids, $lead_source_id, $sales_person, $profile, $pincode, $city, $state, $country, $reference, $purpose, $lead_status, $notes);
$ok = $stmt->execute();
$stmt->close();

if ($ok){
    echo json_encode(['success'=>true,'message'=>'Lead created']);
} else {
    echo json_encode(['success'=>false,'message'=>'Insert failed']);
}

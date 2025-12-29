<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../config/db.php';



$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$contact_number = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$looking_for_id = isset($_POST['looking_for_id']) ? intval($_POST['looking_for_id']) : 0;
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

$stmt = $con->prepare("INSERT INTO leads (name, contact_number, email, looking_for_id, lead_source_id, sales_person, profile, pincode, city, state, country, reference, purpose, lead_status, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
if (!$stmt){ echo json_encode(['success'=>false,'message'=>'DB prepare failed']); exit; }
$types = 'sssii' . 'ssssssssss';
$stmt->bind_param($types, $name, $contact_number, $email, $looking_for_id, $lead_source_id, $sales_person, $profile, $pincode, $city, $state, $country, $reference, $purpose, $lead_status, $notes);
$ok = $stmt->execute();
$stmt->close();

if ($ok){
    echo json_encode(['success'=>true,'message'=>'Lead created']);
} else {
    echo json_encode(['success'=>false,'message'=>'Insert failed']);
}

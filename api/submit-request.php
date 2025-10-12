<?php
header("Content-Type: application/json");
require_once "db_connection.php";

// Collect form data
$patient_name   = $_POST['patient_name'] ?? '';
$blood_type     = $_POST['blood_type'] ?? '';
$hospital_name  = $_POST['hospital_name'] ?? '';
$urgency_level  = $_POST['urgency_level'] ?? 'medium';
$contact_phone  = $_POST['contact_phone'] ?? '';
$description    = $_POST['description'] ?? '';
$requester_id   = 1; // TODO: Replace with logged-in user ID (session)

// Validate required fields
if (empty($patient_name) || empty($blood_type) || empty($hospital_name) || empty($contact_phone)) {
    echo json_encode(["success" => false, "message" => "Please fill all required fields"]);
    exit;
}

// Insert into database
$stmt = $conn->prepare("INSERT INTO blood_requests 
    (requester_id, patient_name, blood_type, hospital_name, urgency_level, contact_phone, description) 
    VALUES (?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("issssss", $requester_id, $patient_name, $blood_type, $hospital_name, $urgency_level, $contact_phone, $description);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Request submitted successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>

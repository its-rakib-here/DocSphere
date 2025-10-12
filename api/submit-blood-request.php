<?php


// ✅ Enable error reporting (logged only)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ✅ Custom error log file
$logFile = __DIR__ . '/error_log.txt';
function logError($message) {
    global $logFile;
    $date = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$date] $message\n", FILE_APPEND);
}

// ✅ Handle runtime and fatal errors
set_error_handler(fn($errno, $errstr, $errfile, $errline) =>
    logError("PHP Error [$errno]: $errstr in $errfile on line $errline")
);
set_exception_handler(function ($ex) {
    logError("Uncaught Exception: " . $ex->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error. Please try again later."
    ]);
    exit;
});

// ✅ Ensure clean JSON output only
ob_clean();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

// ✅ Include database connection
require_once "db_connection.php";

// ✅ Check DB connection
if ($conn->connect_error) {
    logError("Database connection failed: " . $conn->connect_error);
    echo json_encode(["success" => false, "message" => "Database connection failed."]);
    exit;
}

// ✅ Helper function
function safeTrim($v) { return is_null($v) ? '' : trim((string)$v); }

// ✅ Collect POST data
$requester_id      = 1; // Example user ID
$patient_name      = safeTrim($_POST['patient_name'] ?? '');
$blood_type        = safeTrim($_POST['blood_type'] ?? '');
$units_needed      = intval($_POST['units_needed'] ?? 0);
$urgency_level     = safeTrim($_POST['urgency_level'] ?? 'medium');
$hospital_name     = safeTrim($_POST['hospital_name'] ?? '');
$hospital_address  = safeTrim($_POST['hospital_address'] ?? '');
$district          = safeTrim($_POST['district'] ?? '');
$location_text     = safeTrim($_POST['location_text'] ?? '');
$latitude          = safeTrim($_POST['latitude'] ?? '');
$longitude         = safeTrim($_POST['longitude'] ?? '');
$contact_person    = safeTrim($_POST['contact_person'] ?? '');
$contact_phone     = safeTrim($_POST['contact_phone'] ?? '');
$needed_by_date    = safeTrim($_POST['needed_by_date'] ?? '');
$description       = safeTrim($_POST['description'] ?? '');
$status            = "active";

// ✅ Validation
if (
    !$patient_name || !$blood_type || !$hospital_name ||
    !$hospital_address || !$contact_person || !$contact_phone
) {
    echo json_encode(["success" => false, "message" => "Please fill in all required fields."]);
    exit;
}

// ✅ Insert query
$sql = "INSERT INTO blood_requests (
    requester_id, patient_name, blood_type, units_needed, urgency_level,
    hospital_name, hospital_address, district, location_text, latitude, longitude,
    contact_person, contact_phone, needed_by_date, description, status
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    logError("SQL prepare failed: " . $conn->error);
    echo json_encode(["success" => false, "message" => "Server error."]);
    exit;
}

$stmt->bind_param(
    "ississssssssssss",
    $requester_id,
    $patient_name,
    $blood_type,
    $units_needed,
    $urgency_level,
    $hospital_name,
    $hospital_address,
    $district,
    $location_text,
    $latitude,
    $longitude,
    $contact_person,
    $contact_phone,
    $needed_by_date,
    $description,
    $status
);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Blood request submitted successfully"]);
} else {
    logError("SQL execute failed: " . $stmt->error);
    echo json_encode(["success" => false, "message" => "Failed to save blood request."]);
}

$stmt->close();
$conn->close();
ob_end_flush(); // ✅ flush only JSON

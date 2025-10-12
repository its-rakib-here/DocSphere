<?php
// api/get-requests.php
ob_start(); // 🧹 buffer everything to clean stray spaces
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    while (ob_get_level()) ob_end_clean();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    while (ob_get_level()) ob_end_clean();
    echo json_encode(["success" => false, "message" => "Invalid method, use POST"]);
    exit;
}

require_once __DIR__ . "/db_connection.php";

// ✅ query
$sql = "SELECT id, patient_name, blood_type, urgency_level, hospital_name, contact_phone, description, created_at
        FROM blood_requests
        WHERE status='active'
        ORDER BY created_at DESC";

$result = $conn->query($sql);
$requests = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
} else {
    while (ob_get_level()) ob_end_clean();
    echo json_encode(["success" => false, "message" => "Query failed: ".$conn->error]);
    exit;
}

$conn->close();

// 🧹 send only JSON (no stray whitespace)
while (ob_get_level()) ob_end_clean();
echo json_encode([
    "success" => true,
    "requests" => $requests,
    "count" => count($requests)
]);
exit;
?>

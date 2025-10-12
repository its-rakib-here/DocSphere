<?php
// -------------------------------------------
// API: Get Recent Blood Requests (POST method)
// -------------------------------------------

// Output clean JSON
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    while (ob_get_level()) ob_end_clean();
    exit;
}

// Logging helper
$logFile = __DIR__ . '/error_log.txt';
function logError($m){ global $logFile; file_put_contents($logFile,'['.date('Y-m-d H:i:s')."] $m\n",FILE_APPEND); }

set_error_handler(function($no,$str,$file,$line){ logError("PHP Error [$no]: $str in $file:$line"); });
set_exception_handler(function($ex){
    logError("Uncaught: ".$ex->getMessage());
    while (ob_get_level()) ob_end_clean();
    echo json_encode(["success"=>false,"message"=>"Server error"]);
    exit;
});

require_once "db_connection.php";

if ($conn->connect_error ?? false) {
    while (ob_get_level()) ob_end_clean();
    echo json_encode(["success"=>false,"message"=>"Database connection failed"]);
    exit;
}

// Optional filter (you can send from JS if needed)
$limit = intval($_POST['limit'] ?? 10);
if ($limit <= 0 || $limit > 50) $limit = 10;

// Fetch the latest active requests
$sql = "
  SELECT
    id,
    patient_name,
    blood_type,
    units_needed,
    urgency_level,
    hospital_name,
    district,
    latitude,
    longitude,
    needed_by_date,
    created_at
  FROM blood_requests
  WHERE status = 'active'
  ORDER BY created_at DESC
  LIMIT ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $limit);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) $data[] = $row;

$stmt->close();
$conn->close();

// Clean output
while (ob_get_level()) ob_end_clean();
echo json_encode(["success" => true, "data" => $data]);

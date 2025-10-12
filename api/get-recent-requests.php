<?php
// api/get-recent-requests.php

// Buffer control: return clean JSON only
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  while (ob_get_level()) ob_end_clean();
  exit;
}

$logFile = __DIR__ . '/error_log.txt';
function logError($m){ global $logFile; file_put_contents($logFile,'['.date('Y-m-d H:i:s')."] $m\n",FILE_APPEND); }

set_error_handler(function($no,$str,$file,$line){ logError("PHP Error [$no]: $str in $file:$line"); });
set_exception_handler(function($ex){
  logError("Uncaught: ".$ex->getMessage());
  while (ob_get_level()) ob_end_clean();
  echo json_encode(["success"=>false,"message"=>"Server error"]);
  exit;
});

require_once __DIR__ . "/db_connection.php";
if ($conn->connect_error ?? false) {
  while (ob_get_level()) ob_end_clean();
  echo json_encode(["success"=>false,"message"=>"DB connection failed"]);
  exit;
}

// Adjust fields to match your table
// Assumes `created_at` exists and defaults to CURRENT_TIMESTAMP
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
  LIMIT 10
";

$res = $conn->query($sql);
$rows = [];
if ($res) {
  while ($r = $res->fetch_assoc()) { $rows[] = $r; }
} else {
  logError("Query failed: ".$conn->error);
}

$conn->close();
while (ob_get_level()) ob_end_clean();
echo json_encode(["success"=>true,"data"=>$rows]);

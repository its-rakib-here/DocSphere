<?php
// api/get-requests.php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// ✅ Handle preflight for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    while (ob_get_level()) ob_end_clean();
    exit;
}

// ✅ Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    while (ob_get_level()) ob_end_clean();
    echo json_encode(["success" => false, "message" => "Invalid method. Use POST."]);
    exit;
}

require_once "db_connection.php";

// ✅ Decode JSON body safely
$input = json_decode(file_get_contents("php://input"), true);
if (!is_array($input)) $input = [];

$urgency = $input['urgency'] ?? [];
$bloodTypes = $input['bloodTypes'] ?? [];
$radiusFilter = strtolower(trim($input['radius'] ?? 'all'));
$userLat = floatval($input['latitude'] ?? 0);
$userLng = floatval($input['longitude'] ?? 0);
$limit = intval($input['limit'] ?? 20);

$where = ["status = 'active'"];

// ✅ Urgency filter
if (!empty($urgency)) {
    $escaped = array_map(fn($u) => "'" . $conn->real_escape_string($u) . "'", $urgency);
    $where[] = "LOWER(urgency_level) IN (" . implode(",", $escaped) . ")";
}

// ✅ Blood type filter
if (!empty($bloodTypes)) {
    $escaped = array_map(fn($b) => "'" . $conn->real_escape_string($b) . "'", $bloodTypes);
    $where[] = "blood_type IN (" . implode(",", $escaped) . ")";
}

// ✅ Radius filter (safe ACOS with LEAST/GREATEST)
$distanceSql = "";
$orderBy = "created_at DESC";

if ($userLat && $userLng && $radiusFilter !== 'all') {
    $radiusKm = match ($radiusFilter) {
        'low' => 5,
        'medium' => 15,
        'high' => 30,
        default => 9999
    };

    $distanceExpr = "(
        6371 * ACOS(
            LEAST(
                GREATEST(
                    COS(RADIANS($userLat)) * COS(RADIANS(latitude)) *
                    COS(RADIANS(longitude) - RADIANS($userLng)) +
                    SIN(RADIANS($userLat)) * SIN(RADIANS(latitude)),
                -1),
            1)
        )
    )";

    $distanceSql = ", $distanceExpr AS distance_km";
    $where[] = "$distanceExpr <= $radiusKm";
    $orderBy = "distance_km ASC, created_at DESC";
}

$whereSql = implode(" AND ", $where);

// ✅ Final query
$sql = "
    SELECT 
        id, patient_name, blood_type, urgency_level, hospital_name, contact_phone, 
        description, latitude, longitude, created_at
        $distanceSql
    FROM blood_requests
    WHERE $whereSql
    ORDER BY $orderBy
    LIMIT $limit
";

// ✅ Execute query safely
$res = $conn->query($sql);
if (!$res) {
    while (ob_get_level()) ob_end_clean();
    echo json_encode([
        "success" => false,
        "message" => "Database query failed: " . $conn->error
    ]);
    exit;
}

$requests = [];
while ($r = $res->fetch_assoc()) {
    $requests[] = $r;
}

$conn->close();

// ✅ No results case
if (empty($requests)) {
    while (ob_get_level()) ob_end_clean();
    echo json_encode([
        "success" => true,
        "requests" => [],
        "count" => 0,
        "message" => "No matching requests found for your filters."
    ]);
    exit;
}

// ✅ Normal success response
while (ob_get_level()) ob_end_clean();
echo json_encode([
    "success" => true,
    "requests" => $requests,
    "count" => count($requests)
]);
exit;
?>

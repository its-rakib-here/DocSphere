<?php
// =============================
// Blood Community Stats API
// =============================

error_reporting(E_ALL);
ini_set("display_errors", 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once "db_connection.php";

$response = [
    "success" => false,
    "donors" => 0,
    "requests" => 0,
    "lives_saved" => 0,
];

try {
    // ✅ Check database connection
    if (!$conn || $conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // ✅ Count donors
    $donorSql = "SELECT COUNT(*) AS total FROM usersforblood WHERE is_available_donor = 1";
    $donorQuery = $conn->query($donorSql);
    if (!$donorQuery) throw new Exception("Donor query failed: " . $conn->error);
    $donorCount = (int)$donorQuery->fetch_assoc()['total'];

    // ✅ Count active requests
    $reqSql = "SELECT COUNT(*) AS total FROM blood_requests WHERE status = 'active'";
    $reqQuery = $conn->query($reqSql);
    if (!$reqQuery) throw new Exception("Request query failed: " . $conn->error);
    $reqCount = (int)$reqQuery->fetch_assoc()['total'];

    // ✅ Count fulfilled requests
    $fulfilledSql = "SELECT COUNT(*) AS total FROM blood_requests WHERE status = 'fulfilled'";
    $fulfilledQuery = $conn->query($fulfilledSql);
    if (!$fulfilledQuery) throw new Exception("Fulfilled query failed: " . $conn->error);
    $fulfilledCount = (int)$fulfilledQuery->fetch_assoc()['total'];

    // ✅ Calculate lives saved (example logic)
    $livesSaved = ($fulfilledCount * 3) + 1000;

    $response = [
        "success" => true,
        "donors" => $donorCount,
        "requests" => $reqCount,
        "lives_saved" => $livesSaved,
    ];

} catch (Exception $e) {
    $response["error"] = $e->getMessage();
}

echo json_encode($response);

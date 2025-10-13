<?php
// -------------------------------------------------------------
// Fetch All Doctors API
// -------------------------------------------------------------

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once "db_connection.php";

// Validate DB connection
if (!isset($conn) || !$conn) {
    echo json_encode(["success" => false, "message" => "Database connection failed."]);
    exit;
}

try {
    // Query all doctors
    $sql = "SELECT 
                d_id,
                name,
                workplace,
                gender,
                specialization,
                contact_number,
                license_number,
                email,
                user_type
            FROM doctors
            WHERE user_type = 'Doctor'
            ORDER BY name ASC";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $doctors = [];
    while ($row = $result->fetch_assoc()) {
        $doctors[] = [
            "id" => (int)$row['d_id'],
            "name" => $row['name'],
            "workplace" => $row['workplace'] ?? "N/A",
            "gender" => $row['gender'],
            "specialization" => $row['specialization'],
            "contact_number" => $row['contact_number'],
            "license_number" => $row['license_number'],
            "email" => $row['email'],
            "user_type" => $row['user_type']
        ];
    }

    echo json_encode([
        "success" => true,
        "count" => count($doctors),
        "doctors" => $doctors
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}

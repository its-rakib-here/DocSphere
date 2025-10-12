<?php
// ------------------------------------------
// Donor Registration API (Final with Logging)
// ------------------------------------------

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once "db_connection.php";

// ✅ Custom error log file
$logFile = __DIR__ . '/error_log.txt';
function logError($message) {
    global $logFile;
    $date = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$date] $message\n", FILE_APPEND);
}

// ✅ Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ✅ Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logError("Invalid method used: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

try {
    // ✅ Validate required fields
    $required = ['full_name', 'email', 'phone', 'blood_type', 'date_of_birth', 'gender', 'weight', 'address', 'city'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            logError("Missing field: $field");
            echo json_encode(["success" => false, "message" => "Please fill in all required fields."]);
            exit;
        }
    }

    // ✅ Sanitize inputs
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = preg_replace('/[^\d+]/', '', $_POST['phone']); // keep digits and +
    $blood_type = trim($_POST['blood_type']);
    $date_of_birth = trim($_POST['date_of_birth']);
    $gender = trim($_POST['gender']);
    $weight = floatval($_POST['weight']);
    $last_donation_date = !empty($_POST['last_donation_date']) ? trim($_POST['last_donation_date']) : NULL;
    $is_available_donor = (isset($_POST['is_available_donor']) && $_POST['is_available_donor'] == "1") ? 1 : 0;
    $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : NULL;
    $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : NULL;
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);

    // ✅ Validate Bangladeshi phone number
    if (!preg_match('/^(?:\+?88)?01[3-9]\d{8}$/', $phone)) {
        logError("Invalid phone number: $phone");
        echo json_encode(["success" => false, "message" => "Invalid Bangladeshi phone number."]);
        exit;
    }

    // ✅ Check duplicate (email or phone)
    $check = $conn->prepare("SELECT id FROM usersforblood WHERE email = ? OR phone = ? LIMIT 1");
    if (!$check) {
        logError("Prepare failed (check): " . $conn->error);
        echo json_encode(["success" => false, "message" => "Database error."]);
        exit;
    }

    $check->bind_param("ss", $email, $phone);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        logError("Duplicate donor found: $email / $phone");
        echo json_encode(["success" => false, "message" => "An account with this email or phone already exists."]);
        $check->close();
        $conn->close();
        exit;
    }
    $check->close();

    // ✅ Insert new donor
    $sql = "INSERT INTO usersforblood (
        email, full_name, phone, blood_type, date_of_birth, gender, weight, last_donation_date,
        is_available_donor, latitude, longitude, address, city, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        logError("Prepare failed (insert): " . $conn->error);
        echo json_encode(["success" => false, "message" => "Database error (prepare failed)."]);
        exit;
    }

    // ✅ Bind parameters
    $stmt->bind_param(
        "ssssssdiddsss",
        $email,
        $full_name,
        $phone,
        $blood_type,
        $date_of_birth,
        $gender,
        $weight,
        $last_donation_date,
        $is_available_donor,
        $latitude,
        $longitude,
        $address,
        $city
    );

    // ✅ Execute
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Donor registered successfully!"]);
    } else {
        logError("Insert failed: " . $stmt->error);
        echo json_encode(["success" => false, "message" => "Database insert failed."]);
    }

    $stmt->close();
    $conn->close();
} catch (Throwable $e) {
    logError("Exception: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Server error occurred."]);
}

ob_end_flush();
?>

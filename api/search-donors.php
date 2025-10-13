<?php
// ---------------------------------------------------
// Blood Donor Finder API (GET version)
// Table: usersforblood
// ---------------------------------------------------

// ✅ 1. Remove all previous output and buffering
while (ob_get_level()) {
    ob_end_clean();
}

// ✅ 2. Send correct headers
header_remove();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// ✅ 3. Include clean DB connection
require_once __DIR__ . "/db_connection.php";

// ✅ 4. Ensure DB connection is valid
if (!isset($conn) || !$conn) {
    echo json_encode(["success" => false, "message" => "Database not connected."]);
    exit;
}

// ✅ 5. Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ✅ 6. Only GET is allowed
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(["success" => false, "message" => "Invalid request method. Use GET."]);
    exit;
}

// ✅ 7. Logging helper (optional)
$logFile = __DIR__ . '/error_log.txt';
function logError($msg) {
    global $logFile;
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}

// ✅ 8. Read query parameters
$bloodType = $_GET['bloodType'] ?? '';
$radius = (float)($_GET['radius'] ?? 10);
$availability = isset($_GET['availability']) ? explode(',', $_GET['availability']) : [];

// Example static location (replace later with user’s real GPS)
$current_lat = 23.8103; // Dhaka
$current_lng = 90.4125; // Dhaka

try {
    // ✅ 9. Build base SQL query
    $sql = "SELECT id, full_name, blood_type, phone, last_donation_date, 
                   is_available_donor, latitude, longitude, city
            FROM usersforblood
            WHERE is_available_donor = 1";

    $params = [];
    $types = '';

    if (!empty($bloodType)) {
        $sql .= " AND blood_type = ?";
        $params[] = $bloodType;
        $types .= 's';
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("SQL prepare failed: " . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $donors = [];

    // ✅ 10. Process donor records
    while ($row = $result->fetch_assoc()) {
        // Calculate distance if coordinates are available
        $distance = null;
        if (!empty($row['latitude']) && !empty($row['longitude'])) {
            $distance = haversine(
                (float)$current_lat,
                (float)$current_lng,
                (float)$row['latitude'],
                (float)$row['longitude']
            );
        }

        // Skip donors outside given radius
        if ($distance !== null && $distance > $radius) continue;

        // Availability text
        $availableText = ($row['is_available_donor'] == 1)
            ? "Available Now"
            : "Unavailable";

        // Filter by availability if selected
        if (!empty($availability)) {
            $matched = false;
            foreach ($availability as $filter) {
                if (stripos($availableText, $filter) !== false) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) continue;
        }

        // Calculate last donation info
        $lastDonationText = "N/A";
        if (!empty($row['last_donation_date'])) {
            try {
                $lastDate = new DateTime($row['last_donation_date']);
                $now = new DateTime();
                $diff = $lastDate->diff($now);
                $months = ($diff->y * 12) + $diff->m;
                if ($months > 0) {
                    $lastDonationText = $months . " month" . ($months > 1 ? "s" : "") . " ago";
                } elseif ($diff->d > 0) {
                    $lastDonationText = $diff->d . " day" . ($diff->d > 1 ? "s" : "") . " ago";
                } else {
                    $lastDonationText = "Today";
                }
            } catch (Exception $e) {
                $lastDonationText = "Unknown";
            }
        }

        // Add donor to array
        $donors[] = [
            "id" => (int)$row['id'],
            "full_name" => $row['full_name'],
            "blood_type" => $row['blood_type'],
            "phone" => $row['phone'],
            "distance" => $distance !== null ? round($distance, 1) : null,
            "available" => (bool)$row['is_available_donor'],
            "availability" => $availableText,
            "last_donated" => $lastDonationText,
            "city" => $row['city']
        ];
    }

    // ✅ 11. Return clean JSON
    echo json_encode([
        "success" => true,
        "count" => count($donors),
        "donors" => $donors
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    logError("SearchDonors Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error."]);
    exit;
}

// ✅ 12. Distance function
function haversine($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2 +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

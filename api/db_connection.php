<?php
// ---------------------------------------------------
// Database Connection (Silent + Safe)
// ---------------------------------------------------

$host = "localhost";       // Database host
$user = "root";            // MySQL username
$pass = "";                // MySQL password
$db   = "docsphere_copy";  // Your database name

// Prevent accidental output
if (ob_get_level()) ob_end_clean();

// Disable automatic warnings (we’ll handle them manually)
mysqli_report(MYSQLI_REPORT_OFF);

// Create connection
$conn = @new mysqli($host, $user, $pass, $db);

// Check connection (no echo!)
if ($conn->connect_error) {
    error_log("❌ Database connection failed: " . $conn->connect_error);
    $conn = null;
    exit;
}

// Set UTF-8 charset
$conn->set_charset("utf8");

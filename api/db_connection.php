<!-- <?php
// db.php → Database connection file

$host = "localhost";     // Database host
$user = "root";          // MySQL username
$pass = "";              // MySQL password
$db   = "docsphere"; // Your database name

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]));
}else
{
    echo "hello";
}

$conn->set_charset("utf8");
?> -->

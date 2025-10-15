<?php
header('Content-Type: application/json');

$host = "localhost";
$db   = "docsphere";
$user = "rafi";
$pass = "test1234";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT d_id, name, specialization FROM doctors ORDER BY name");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

<?php
header('Content-Type: application/json');

$host = "localhost";
$db   = "docsphere";
$user = "rafi";
$pass = "test1234";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 1;
    if (!$doctor_id) {
        throw new Exception("doctor_id required");
    }

    $sql = "
        SELECT 
            u.u_id, 
            u.name,
            SUM(CASE 
                WHEN m.sender_type = 'user' 
                     AND m.receiver_type = 'doctor' 
                     AND m.receiver_id = :doc 
                     AND m.is_read = 0 
                THEN 1 ELSE 0 
            END) AS unread
        FROM messages m
        JOIN users u 
          ON u.u_id = m.sender_id AND m.sender_type = 'user'
        WHERE m.receiver_type = 'doctor' AND m.receiver_id = :doc
        GROUP BY u.u_id, u.name
        ORDER BY MAX(m.created_at) DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':doc' => $doctor_id]);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

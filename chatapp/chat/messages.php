<?php
header('Content-Type: application/json');

// MySQL connection
$host = "localhost";
$db   = "docsphere";
$user = "rafi";
$pass = "test1234";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sender_type   = $_GET['sender_type'] ?? null;
        $sender_id     = isset($_GET['sender_id']) ? (int)$_GET['sender_id'] : 1;
        $receiver_type = $_GET['receiver_type'] ?? null;
        $receiver_id   = isset($_GET['receiver_id']) ? (int)$_GET['receiver_id'] : 1;

        if (!$sender_type || !$sender_id || !$receiver_type || !$receiver_id) {
            throw new Exception("sender_type, sender_id, receiver_type, and receiver_id required");
        }

        // Fetch conversation between the two parties
        $stmt = $pdo->prepare("
            SELECT id, sender_type, sender_id, receiver_type, receiver_id, message, message_type, is_read, created_at 
            FROM messages 
            WHERE 
                (sender_type = :sender_type AND sender_id = :sender_id AND receiver_type = :receiver_type AND receiver_id = :receiver_id)
                OR
                (sender_type = :receiver_type AND sender_id = :receiver_id AND receiver_type = :sender_type AND receiver_id = :sender_id)
            ORDER BY created_at ASC, id ASC
        ");
        $stmt->execute([
            ':sender_type'   => $sender_type,
            ':sender_id'     => $sender_id,
            ':receiver_type' => $receiver_type,
            ':receiver_id'   => $receiver_id
        ]);

        // Mark received messages as read
        $mark = $pdo->prepare("
            UPDATE messages 
            SET is_read = 1 
            WHERE sender_type = :receiver_type 
              AND sender_id   = :receiver_id 
              AND receiver_type = :sender_type 
              AND receiver_id = :sender_id
        ");
        $mark->execute([
            ':sender_type'   => $sender_type,
            ':sender_id'     => $sender_id,
            ':receiver_type' => $receiver_type,
            ':receiver_id'   => $receiver_id
        ]);

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true) ?: [];

        $sender_type   = $payload['sender_type']   ?? '';
        $sender_id     = isset($payload['sender_id']) ? (int)$payload['sender_id'] : 1;
        $receiver_type = $payload['receiver_type'] ?? '';
        $receiver_id   = isset($payload['receiver_id']) ? (int)$payload['receiver_id'] : 1;
        $message       = trim($payload['message'] ?? '');
        $message_type  = $payload['message_type'] ?? 'text';

        if (!$sender_type || !$sender_id || !$receiver_type || !$receiver_id || $message === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payload']);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO messages (sender_type, sender_id, receiver_type, receiver_id, message, message_type, is_read, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$sender_type, $sender_id, $receiver_type, $receiver_id, $message, $message_type]);

        echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

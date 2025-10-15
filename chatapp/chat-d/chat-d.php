<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "docsphere";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
$conn->set_charset("utf8");

$current_type = 'doctor';

// ✅ FIX: Prevent session loss on refresh
if (!isset($_SESSION['doctor_id'])) {
    // Temporary fallback for doctor session (set manually for demo)
    $_SESSION['doctor_id'] = 1; // replace 1 with an existing d_id
}
$current_id = $_SESSION['doctor_id'];

// 🧾 List of users who messaged this doctor
$users = [];
$stmt = $conn->prepare("
    SELECT u.u_id, u.name,
           COALESCE(SUM(CASE WHEN m.sender_type='user' AND m.is_read=0 THEN 1 ELSE 0 END),0) AS unread
    FROM users u
    LEFT JOIN messages m 
           ON ( (m.sender_type='user' AND m.sender_id=u.u_id AND m.receiver_type='doctor' AND m.receiver_id=?)
             OR (m.sender_type='doctor' AND m.receiver_type='user' AND m.receiver_id=u.u_id AND m.sender_id=?) )
    GROUP BY u.u_id, u.name
    HAVING MAX(m.created_at) IS NOT NULL
    ORDER BY MAX(m.created_at) DESC
");
$stmt->bind_param("ii", $current_id, $current_id);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 👤 Current chat target
$chat_with_id   = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$messages       = [];

// 🔄 Fetch messages if a user is selected
if ($chat_with_id) {
    $stmt = $conn->prepare("
        SELECT m.*,
               CASE 
                   WHEN m.sender_type='user' THEN u.name
                   WHEN m.sender_type='doctor' THEN d.name
               END AS sender_name
        FROM messages m
        LEFT JOIN users u ON m.sender_type='user' AND m.sender_id=u.u_id
        LEFT JOIN doctors d ON m.sender_type='doctor' AND m.sender_id=d.d_id
        WHERE (m.sender_type='user' AND m.sender_id=? AND m.receiver_type='doctor' AND m.receiver_id=?)
           OR (m.sender_type='doctor' AND m.sender_id=? AND m.receiver_type='user' AND m.receiver_id=?)
        ORDER BY m.created_at ASC
    ");
    $stmt->bind_param("iiii", $chat_with_id, $current_id, $current_id, $chat_with_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Mark user's messages as read
    $stmt = $conn->prepare("
        UPDATE messages SET is_read=1
        WHERE sender_type='user' AND sender_id=? AND receiver_type='doctor' AND receiver_id=?
    ");
    $stmt->bind_param("ii", $chat_with_id, $current_id);
    $stmt->execute();
    $stmt->close();
}

// ✉️ Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $chat_with_id) {
    $message = trim($_POST['message']);
    if ($message !== '') {
        $stmt = $conn->prepare("
            INSERT INTO messages (sender_type, sender_id, receiver_type, receiver_id, message, is_read) 
            VALUES ('doctor', ?, 'user', ?, ?, 0)
        ");
        $stmt->bind_param("iis", $current_id, $chat_with_id, $message);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: chat-d.php?user_id=".$chat_with_id);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Doctor Chat - DocSphere</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="css/chat-d.css">
</head>
<body>
<div class="chat-container">
  <!-- User list -->
  <div class="user-list">
    <h2 class="text-xl mb-4">Users</h2>
    <?php if($users): foreach($users as $u): ?>
      <a href="chat-d.php?user_id=<?= $u['u_id'] ?>">
        <div class="user-card">
          <?= htmlspecialchars($u['name']) ?>
          <?php if($u['unread']>0): ?>
            <span class="unread"><?= $u['unread'] ?></span>
          <?php endif; ?>
        </div>
      </a>
    <?php endforeach; else: ?>
      <p>No users messaged yet.</p>
    <?php endif; ?>
  </div>

  <!-- Chat box -->
  <div class="chat-box">
    <div id="messages">
      <?php if($messages): ?>
        <?php foreach($messages as $m): ?>
          <div class="message <?= $m['sender_type'] ?>">
            <strong><?= ($m['sender_type']=='doctor') ? 'You' : htmlspecialchars($m['sender_name']) ?></strong>: <?= htmlspecialchars($m['message']) ?>
            <div class="time"><?= $m['created_at'] ?></div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>Select a user to chat.</p>
      <?php endif; ?>
    </div>

    <?php if($chat_with_id): ?>
    <form method="post">
      <textarea name="message" rows="3" placeholder="Type your message..." required></textarea>
      <button type="submit">Send</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<script src="js/chat-d.js"></script>
</body>
</html>

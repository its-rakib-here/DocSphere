<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "docsphere";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
$conn->set_charset("utf8");

$current_type = 'user';

// ✅ FIX: Prevent session loss on refresh
if (!isset($_SESSION['user_id'])) {
    // Temporary fallback for testing (set manually)
    $_SESSION['user_id'] = 1; // replace 1 with a real u_id
}
$current_id = $_SESSION['user_id'];

// Fetch all doctors
$list_sql = "SELECT d_id AS id, name, specialization, workplace, contact_number FROM doctors ORDER BY name";
$stmt_list = $conn->prepare($list_sql);
$stmt_list->execute();
$list_result = $stmt_list->get_result();
$doctors = $list_result->fetch_all(MYSQLI_ASSOC);
$stmt_list->close();

// Determine selected doctor
$chat_with_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;
$messages = [];

// Fetch messages if a doctor is selected
if ($chat_with_id) {
    $stmt = $conn->prepare("
        SELECT m.message, m.created_at, m.sender_type, m.sender_id,
               d.name AS sender_name
        FROM messages m
        LEFT JOIN doctors d ON m.sender_type='doctor' AND m.sender_id=d.d_id
        WHERE (m.sender_type='user' AND m.sender_id=? AND m.receiver_type='doctor' AND m.receiver_id=?)
           OR (m.sender_type='doctor' AND m.sender_id=? AND m.receiver_type='user' AND m.receiver_id=?)
        ORDER BY m.created_at ASC
    ");
    $stmt->bind_param("iiii", $current_id, $chat_with_id, $chat_with_id, $current_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $chat_with_id) {
    $msg = trim($_POST['message']);
    if ($msg !== '') {
        $stmt = $conn->prepare("
            INSERT INTO messages (sender_type, sender_id, receiver_type, receiver_id, message, is_read)
            VALUES ('user', ?, 'doctor', ?, ?, 0)
        ");
        $stmt->bind_param("iis", $current_id, $chat_with_id, $msg);
        $stmt->execute();
        $stmt->close();
        header("Location: chat.php?doctor_id=" . $chat_with_id);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Chat - DocSphere</title>
<link rel="stylesheet" href="css/chat.css">
</head>
<body>
<div class="container">
  <!-- Sidebar: all doctors -->
  <div class="sidebar">
    <h2>Doctors</h2>
    <?php foreach ($doctors as $doc): ?>
      <div class="doctor-card <?= ($chat_with_id == $doc['id']) ? 'selected' : '' ?>">
        <div class="name"><strong><?= htmlspecialchars($doc['name']) ?></strong></div>
        <div class="spec"><?= htmlspecialchars($doc['specialization'] ?? '') ?></div>
        <div class="workplace"><?= htmlspecialchars($doc['workplace'] ?? '') ?></div>
        <div class="contact"><?= htmlspecialchars($doc['contact_number'] ?? '') ?></div>
        <a href="chat.php?doctor_id=<?= $doc['id'] ?>">Chat</a>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Chat area -->
  <div class="chat-area">
    <div class="chat-box" id="chat-box">
      <?php if ($chat_with_id && $messages): ?>
        <?php foreach ($messages as $msg): ?>
          <div class="<?= ($msg['sender_type'] === 'user') ? 'msg-right' : 'msg-left' ?>">
            <strong><?= htmlspecialchars($msg['sender_name']) ?></strong>: <?= htmlspecialchars($msg['message']) ?><br>
            <small><?= $msg['created_at'] ?></small>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>Select a doctor from the left panel to start chatting.</p>
      <?php endif; ?>
    </div>
    <?php if ($chat_with_id): ?>
      <form method="post">
        <textarea name="message" placeholder="Type your message..." required></textarea>
        <button type="submit">Send</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<script src="js/chat.js"></script>
</body>
</html>

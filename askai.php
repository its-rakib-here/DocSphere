<?php
session_start();
require_once 'db.php';

// ✅ Unified Session Check (Handles patient, doctor, hospital)
$userType = strtolower(trim($_SESSION['user_type'] ?? ''));
$userId   = $_SESSION['user_id'] ?? $_SESSION['doctor_id'] ?? $_SESSION['hospital_id'] ?? null;

// Only allow patient to access this page
if (!$userId || $userType !== 'patient') {
    header("Location: login.php");
    exit;
}

$success = '';
$error = '';

// Gemini API details
$api_key = "AIzaSyBXq7Tn2uar7ZjSic-WXUYrbT1rfqwww5o";
$model = "gemini-2.5-flash"; // choose gemini-2.5-flash or gemini-2.5-pro

// Handle question submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ask_question'])) {
    $question = trim($_POST['question_text'] ?? '');

    if (!empty($question)) {
        // Prepare API call
        $url = "https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent?key=" . $api_key;

        $payload = json_encode([
            "contents" => [
                ["parts" => [["text" => $question]]]
            ]
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        $response = curl_exec($ch);
        curl_close($ch);

        $answer = "No response from AI.";
        if ($response) {
            $data = json_decode($response, true);
            $answer = $data['candidates'][0]['content']['parts'][0]['text'] ?? "Couldn't get an answer.";
        }

        // Save to database
        $stmt = $conn->prepare("INSERT INTO ai_questions (u_id, question_text, answer_text) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $userId, $question, $answer);
        $stmt->execute();
        $stmt->close();

        $success = "AI answered your question!";
    } else {
        $error = "Please enter a question.";
    }
}

// Fetch previous Q&A
$stmt = $conn->prepare("SELECT question_text, answer_text, asked_at FROM ai_questions WHERE u_id=? ORDER BY asked_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$qa_list = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ask AI | DocSphere</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', Tahoma, sans-serif; }
body { display:flex; min-height:100vh; background: linear-gradient(135deg, #E3E4FA, #8897BD, #2C497F); color:#333; }

/* Sidebar */
.sidebar {
  position: fixed; top:0; left:0; width:240px; height:100%;
  background: rgba(44, 73, 127, 0.95); backdrop-filter: blur(10px); padding:30px 15px;
  display:flex; flex-direction:column; align-items:center; box-shadow:4px 0 15px rgba(0,0,0,0.1); border-right:1px solid rgba(255,255,255,0.2); z-index:200;
}
.sidebar:hover { width:260px; }
.sidebar h2 { color:#E3E4FA; font-size:24px; margin-bottom:40px; font-weight:bold; letter-spacing:1px; }
.sidebar ul { list-style:none; width:100%; }
.sidebar ul li { margin:18px 0; display:flex; align-items:center; padding:12px 18px; border-radius:12px; cursor:pointer; transition:all 0.3s; color:#E3E4FA; font-weight:500; font-size:16px; }
.sidebar ul li:hover { background: linear-gradient(90deg, #8897BD, #E3E4FA); color:#2C497F; transform:translateX(5px); }
.sidebar ul li i { margin-right:14px; font-size:18px; transition: transform 0.3s; }
.sidebar ul li:hover i { transform:scale(1.2); }

/* Content */
.content { margin-left:240px; width:calc(100% - 240px); }

/* Navbar */
.navbar {
  position: fixed; top:0; left:240px; width:calc(100% - 240px); height:70px;
  display:flex; align-items:center; justify-content:space-between; padding:0 30px;
  background: linear-gradient(135deg, #8897BD, #E3E4FA); box-shadow:0 2px 8px rgba(0,0,0,0.08); z-index:100;
}
.navbar .logo img { width:45px; border-radius:8px; cursor:pointer; }
.navbar .brand-name { font-size:22px; font-weight:bold; background: linear-gradient(90deg,#2C497F,#8897BD); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
.navbar .nav-buttons { display:flex; gap:15px; align-items:center; }
.navbar .nav-buttons button { padding:8px 16px; border:none; border-radius:25px; background:linear-gradient(90deg,#2C497F,#8897BD); color:#fff; font-weight:600; cursor:pointer; transition:transform 0.2s; }
.navbar .nav-buttons button:hover { transform:scale(1.05); }
.navbar-search { display:flex; border:1px solid #ccc; border-radius:25px; overflow:hidden; }
.navbar-search input { border:none; padding:6px 12px; outline:none; width:160px; }
.navbar-search button { background:#2C497F; border:none; padding:6px 12px; color:#fff; cursor:pointer; }

/* Main container */
.main-container { padding:100px 20px 20px 20px; display:flex; flex-direction:column; align-items:center; gap:30px; width:100%; }

/* Ask Box */
.ask-box {
    background: #F0F3FF; color:#2C497F;
    padding:25px; border-radius:15px; width:600px; max-width:90%;
}
.ask-box h2 { text-align:center; margin-bottom:15px; }
.ask-box textarea {
    width:100%; height:120px; border-radius:10px; padding:10px; border:1px solid #8897BD; resize:none; outline:none; font-size:15px;
}
.ask-box button {
    width:100%; margin-top:10px; padding:12px; border:none; border-radius:10px; background:linear-gradient(90deg,#2C497F,#8897BD); color:white; font-size:16px; cursor:pointer;
}
.ask-box button:hover { opacity:0.9; }
.success { color:#4CAF50; text-align:center; font-weight:bold; }
.error { color:#FF6B6B; text-align:center; font-weight:bold; }

/* Q&A Section */
.qa-section {
    background: #F0F3FF; color:#2C497F; padding:25px; border-radius:15px; width:700px; max-width:95%;
}
.qa-section h3 { text-align:center; margin-bottom:20px; }
.qa-card { background: linear-gradient(145deg, #8897BD, #E3E4FA); padding:15px; border-radius:10px; margin-bottom:15px; }
.qa-card p { margin:8px 0; }
.qa-card strong { color:#2C497F; }
.timestamp { text-align:right; font-size:13px; opacity:0.8; }

@media (max-width:768px){
  .sidebar{display:none;}
  .content{margin-left:0;width:100%;}
  .navbar{left:0;width:100%;}
  .ask-box, .qa-section{width:95%;}
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <h2>Docsphere</h2>
  <ul>
    <li onclick="location.href='ahomepage.php'"><i class="fas fa-home"></i> Home</li>
    <li onclick="location.href='about.php'"><i class="fas fa-info-circle"></i> About</li>
    <li onclick="location.href='appointments.php'"><i class="fas fa-calendar-check"></i> Appointment</li>
    <li onclick="location.href='calendar.php'"><i class="fas fa-calendar-alt"></i> Calendar</li>
    <li onclick="location.href='contact.php'"><i class="fas fa-envelope"></i> Contact</li>
    <li onclick="location.href='chatapp/chat/chat.php'"><i class="fas fa-comments"></i> Chat</li>
  </ul>
</div>

<!-- Content -->
<div class="content">
  <div class="navbar">
    <div class="logo" onclick="location.href='ahomepage.php'"><img src="logo.png" alt="Docsphere Logo"></div>
    <div class="brand-name">Docsphere</div>
    <div class="nav-buttons">
      <div class="navbar-search">
        <input type="text" placeholder="Search...">
        <button>🔍</button>
      </div>
      <button onclick="location.href='user_profile.php'">Profile</button>
      <button onclick="location.href='logout.php'">Logout</button>
    </div>
  </div>

  <div class="main-container">
    <div class="ask-box">
        <h2>Ask AI Anything</h2>
        <?php if($success): ?><p class="success"><?= $success ?></p><?php endif; ?>
        <?php if($error): ?><p class="error"><?= $error ?></p><?php endif; ?>
        <form method="POST">
            <textarea name="question_text" placeholder="Type your question here..." required></textarea>
            <input type="hidden" name="ask_question" value="1">
            <button type="submit">Ask Now</button>
        </form>
    </div>

    <div class="qa-section">
        <h3>Your Previous Questions</h3>
        <?php if (count($qa_list) === 0): ?>
            <p style="text-align:center;">No questions asked yet.</p>
        <?php else: ?>
            <?php foreach ($qa_list as $qa): ?>
                <div class="qa-card">
                    <p><strong>Q:</strong> <?= htmlspecialchars($qa['question_text']) ?></p>
                    <p><strong>A:</strong> <?= nl2br(htmlspecialchars($qa['answer_text'])) ?></p>
                    <p class="timestamp"><?= htmlspecialchars($qa['asked_at']) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>

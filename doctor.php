<?php
// meet_dr_rahim.php
session_start();

// Validate login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: login.php");
    exit;
}

// Normalize user_type
$user_type = strtolower($_SESSION['user_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Meet Dr Rahim</title>

<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', Tahoma, sans-serif; }
body { display:flex; min-height:100vh; background: linear-gradient(135deg,#E3E4FA,#8897BD,#2C497F); color:#333; }

/* Sidebar */
.sidebar {
  position: fixed; top:0; left:0; width:240px; height:100%;
  background: rgba(44,73,127,0.95); backdrop-filter: blur(10px);
  padding:30px 15px; display:flex; flex-direction:column; align-items:center;
  box-shadow:4px 0 15px rgba(0,0,0,0.1); border-right:1px solid rgba(255,255,255,0.2); z-index:200; transition:width 0.3s;
}
.sidebar:hover { width:260px; }
.sidebar h2 { color:#E3E4FA; font-size:24px; margin-bottom:40px; font-weight:bold; letter-spacing:1px; }
.sidebar ul { list-style:none; width:100%; }
.sidebar ul li { margin:18px 0; display:flex; align-items:center; padding:12px 18px; border-radius:12px; cursor:pointer; transition:all 0.3s; color:#E3E4FA; font-weight:500; font-size:16px; }
.sidebar ul li:hover { background: linear-gradient(90deg,#8897BD,#E3E4FA); color:#2C497F; transform:translateX(5px); }
.sidebar ul li i { margin-right:14px; font-size:18px; transition: transform 0.3s; }
.sidebar ul li:hover i { transform: scale(1.2); }

/* Content area */
.content { margin-left:240px; width:calc(100% - 240px); }

/* Navbar */
.navbar {
  position: fixed; top:0; left:240px; width:calc(100% - 240px); height:70px;
  display:flex; align-items:center; justify-content:space-between; padding:0 30px;
  background: linear-gradient(135deg,#8897BD,#E3E4FA);
  box-shadow:0 2px 8px rgba(0,0,0,0.08); z-index:100;
}
.navbar .logo img { width:45px; border-radius:8px; cursor:pointer; }
.navbar .brand-name { font-size:22px; font-weight:bold; background: linear-gradient(90deg,#2C497F,#8897BD); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
.navbar .nav-buttons { display:flex; gap:15px; align-items:center; }
.navbar .nav-buttons button { padding:8px 16px; border:none; border-radius:25px; background: linear-gradient(90deg,#2C497F,#8897BD); color:#fff; font-weight:600; cursor:pointer; transition:transform 0.2s; }
.navbar .nav-buttons button:hover { transform:scale(1.05); }
.navbar-search { display:flex; border:1px solid #ccc; border-radius:25px; overflow:hidden; }
.navbar-search input { border:none; padding:6px 12px; outline:none; width:160px; }
.navbar-search button { background:#2C497F; border:none; padding:6px 12px; color:#fff; cursor:pointer; }

/* Main Content Card */
.main-content {
  padding:120px 20px 40px 20px;
  max-width:400px;
  margin:0 auto;
}
.card {
  background: linear-gradient(145deg,#8897BD,#E3E4FA);
  border-radius:15px;
  padding:30px 25px;
  box-shadow:0 10px 25px rgba(0,0,0,0.1);
  text-align:center;
}
.card h2 { margin-bottom:25px; font-size:24px; color:#2C497F; font-weight:bold; }
.card input[type="text"] {
  width:100%; padding:10px 15px; border-radius:8px; border:none; font-size:16px; margin-bottom:20px; outline:none; color:#0A2438; font-weight:600;
}
.card button {
  padding:10px 25px; font-size:16px; border-radius:8px; border:none; background: linear-gradient(90deg,#2C497F,#8897BD); color:white; cursor:pointer; font-weight:bold; transition:background-color 0.3s ease;
}
.card button:hover { background:#E3E4FA; color:#2C497F; }

/* Footer */
.footer { text-align:center; padding:50px 20px; background:#2C497F; color:#E3E4FA; border-radius:15px; margin:40px; }
.footer .social-icons { display:flex; justify-content:center; gap:20px; margin-bottom:20px; }
.footer .social-icons a { display:flex; align-items:center; justify-content:center; width:45px; height:45px; background:linear-gradient(135deg,#8897BD,#E3E4FA); border-radius:50%; transition:transform 0.3s; }
.footer .social-icons a:hover { transform:scale(1.2); }

@media(max-width:768px){
  .sidebar{display:none;}
  .content{margin-left:0;width:100%;}
  .navbar{left:0;width:100%;}
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
  <!-- Navbar -->
  <div class="navbar">
    <div class="logo"><img src="logo.png" alt="Docsphere Logo"></div>
    <div class="brand-name">Docsphere</div>
    <div class="nav-buttons">
      <div class="navbar-search">
        <input type="text" placeholder="Search..." />
        <button>🔍</button>
      </div>
      <button onclick="redirectProfile()">Profile</button>
      <button onclick="location.href='homepage.php'">Logout</button>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="card">
      <h2>Enter Meeting Link to Join</h2>
      <input type="text" id="meetingLink" placeholder="Paste your meeting link here" />
      <button onclick="joinMeeting()">Join</button>
    </div>
  </div>

  

<script>
function joinMeeting() {
  const link = document.getElementById('meetingLink').value.trim();
  if(link) window.open(link, '_blank');
  else alert('Please enter a meeting link.');
}

function redirectProfile() {
  var userType = <?php echo json_encode($user_type); ?>;
  switch(userType){
    case "patient": window.location.href="user_profile.php"; break;
    case "doctor": window.location.href="doctor_profile.php"; break;
    case "hospital": window.location.href="hospital_profile.php"; break;
    default: alert("Unknown user type!"); break;
  }
}
</script>

</body>
</html>

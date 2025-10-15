<?php
session_start();

// ✅ Validate login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: login.php");
    exit;
}

// Normalize user_type
$user_type = strtolower($_SESSION['user_type']);

// ✅ Database connection
require_once 'db.php';

// ✅ Fetch doctors
$doctors = [];
$sql = "SELECT d_id, name, specialization FROM doctors";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Docsphere Home Page</title>

<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }
body { background: linear-gradient(135deg, #E3E4FA, #8897BD, #2C497F); color: #333; display: flex; min-height: 100vh; }

/* Sidebar */
.sidebar {
    position: fixed;
    top: 0; left: 0;
    width: 240px; height: 100%;
    background: rgba(44,73,127,0.95);
    backdrop-filter: blur(10px);
    padding: 30px 15px;
    display: flex; flex-direction: column; align-items: center;
    box-shadow: 4px 0 15px rgba(0,0,0,0.1);
    border-right: 1px solid rgba(255,255,255,0.2);
    z-index: 200;
    transition: width 0.3s;
}
.sidebar:hover { width: 260px; }
.sidebar h2 { color: #E3E4FA; font-size: 24px; margin-bottom: 40px; font-weight: bold; letter-spacing: 1px; }
.sidebar ul { list-style: none; width: 100%; }
.sidebar ul li { margin: 18px 0; display: flex; align-items: center; padding: 12px 18px; border-radius: 12px; cursor: pointer; transition: all 0.3s; color: #E3E4FA; font-weight: 500; font-size: 16px; }
.sidebar ul li:hover { background: linear-gradient(90deg, #8897BD, #E3E4FA); color: #2C497F; transform: translateX(5px); }
.sidebar ul li i { margin-right: 14px; font-size: 18px; transition: transform 0.3s; }
.sidebar ul li:hover i { transform: scale(1.2); }


/* Content area */
.content { margin-left: 240px; width: calc(100% - 240px); }

/* Navbar */
.navbar {
  position: fixed;
  top: 0; left: 240px;
  width: calc(100% - 240px); height: 70px;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 30px;
  background: linear-gradient(135deg, #8897BD, #E3E4FA);
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  z-index: 100;
}
.navbar .logo img { width: 45px; border-radius: 8px; }
.navbar .brand-name { font-size: 22px; font-weight: bold; background: linear-gradient(90deg, #2C497F, #8897BD); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.navbar .nav-buttons { display: flex; gap: 15px; align-items: center; }
.navbar .nav-buttons button { padding: 8px 16px; border: none; border-radius: 25px; background: linear-gradient(90deg, #2C497F, #8897BD); color: #fff; font-weight: 600; cursor: pointer; transition: transform 0.2s; }
.navbar .nav-buttons button:hover { transform: scale(1.05); }
.navbar-search { display: flex; border: 1px solid #ccc; border-radius: 25px; overflow: hidden; }
.navbar-search input { border: none; padding: 6px 12px; outline: none; width: 160px; }
.navbar-search button { background: #2C497F; border: none; padding: 6px 12px; color: #fff; cursor: pointer; }

/* Sections */
.section { padding: 120px 40px 80px 40px; text-align: center; transition: all 1s ease; }
.section h1, .section h2 { margin-bottom: 20px; color: #2C497F; }
.section p { margin-bottom: 40px; color: #333; }

/* Hero Section */
#hero { min-height: 90vh; display: flex; flex-direction: column; justify-content: center; align-items: center; background: #E3E4FA; border-radius: 15px; margin: 40px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
.search-bar { display: flex; gap: 10px; }
.search-bar input { padding: 10px 15px; border-radius: 30px; border: 1px solid #8897BD; width: 300px; }
.search-bar button { padding: 8px 20px; border-radius: 30px; border: none; background: linear-gradient(90deg, #2C497F, #8897BD); color: #fff; cursor: pointer; }
/* Services Section */
#services { 
    padding: 80px 40px; 
    background: #F0F3FF; 
    border-radius: 15px; 
    margin: 40px; 
    box-shadow: 0 10px 25px rgba(0,0,0,0.08); 
}

.service-cards { 
    display: flex; 
    justify-content: center; 
    flex-wrap: wrap; 
    gap: 20px; 
}

/* Common service card style */
.service-card { 
    background: linear-gradient(145deg, #8897BD, #E3E4FA); 
    padding: 20px; 
    border-radius: 15px; 
    min-width: 140px; 
    text-align: center; 
    cursor: pointer; 
    transition: transform 0.3s, box-shadow 0.3s; 
    display: flex; 
    flex-direction: column; 
    align-items: center; 
    gap: 10px; 
    text-decoration: none; 
    color: inherit;        
}

.service-card img { 
    width: 50px; 
    height: 50px; 
}

.service-card span { 
    font-weight: bold; 
    color: #2C497F; 
}


/* Doctors Section */
#doctors-section { padding: 80px 40px; background: #E3E4FA; border-radius: 15px; margin: 40px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
.doctor-cards { display: flex; justify-content: center; flex-wrap: wrap; gap: 20px; }
.doctor-card { background: #8897BD; color: #fff; padding: 20px; border-radius: 15px; min-width: 150px; text-align: center; transition: transform 0.3s, box-shadow 0.3s; display: flex; flex-direction: column; align-items: center; gap: 10px; }
.doctor-card img { width: 80px; height: 80px; border-radius: 50%; }
.doctor-card .name { font-weight: bold; }
.doctor-card .spec { font-size: 14px; }
.doctor-card button { padding: 6px 12px; border-radius: 25px; border: none; background: #2C497F; color: #fff; cursor: pointer; }

/* Footer */
.footer { text-align: center; padding: 50px 20px; background: #2C497F; color: #E3E4FA; border-radius: 15px; margin: 40px; }
.footer .social-icons { display: flex; justify-content: center; gap: 20px; margin-bottom: 20px; }
.footer .social-icons a { display: flex; align-items: center; justify-content: center; width: 45px; height: 45px; background: linear-gradient(135deg, #8897BD, #E3E4FA); border-radius: 50%; transition: transform 0.3s; }
.footer .social-icons a:hover { transform: scale(1.2); }

@media (max-width: 768px) {
  .sidebar { display: none; }
  .content { margin-left: 0; width: 100%; }
  .navbar { left: 0; width: 100%; }
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
    <li><a href="calender.php"><i class="fas fa-calendar-alt"></i> Calendar</a></li>
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
        <input type="text" id="search-home" placeholder="Search...">
        <button>🔍</button>
      </div>
      <button onclick="redirectProfile()">Profile</button>
      <button onclick="location.href='homepage.php'">Logout</button>
    </div>
  </div>

  <!-- Hero Section -->
  <section class="section" id="hero">
    <h1>YOUR 24/7 MEDICAL PARTNER</h1>
    <p>A cutting-edge website that lets you consult medical mentors anytime, anywhere.</p>
    <div class="search-bar">
      <input type="text" id="askai-input" placeholder="Start your journey here...">
      <button onclick="redirectAskAI()">Ask AI</button>
    </div>
  </section>

  <!-- Services Section -->
  <section class="section" id="services">
    <h2>OUR SERVICES</h2>
    <div class="service-cards">
      
      <div class="service-card" onclick="location.href='risk_checker.php'"><img src="r.png" alt=""><span>Risk Checker</span></div>
      <a href="askai.php" class="service-card"><img src="h.png" alt=""><span>Health Chatbot</span></a>
      <a href="meetdoctors.php" class="service-card"><img src="b.png" alt=""><span>Smart Booking</span></a>
      <a href="blood/index.html" class="service-card"><img src="blood.png" alt=""><span>Blood</span></a>
    </div>
  </section>

  <!-- Doctors Section -->
  <section class="section" id="doctors-section">
    <h2><?php echo count($doctors); ?> EXPERT DOCTORS</h2>
    <div class="doctor-cards">
      <?php
      if (count($doctors) > 0) {
          $images = ['salam.png', '2ndor.png'];
          $i = 0;
          foreach ($doctors as $doctor) {
              $img = $images[$i % 2];
              echo "
              <div class='doctor-card'>
                  <img src='$img' alt='Doctor'>
                  <span class='name'>{$doctor['name']}</span>
                  <span class='spec'>{$doctor['specialization']}</span>
                  <button onclick=\"location.href='doctor_profile_user_view.php?d_id={$doctor['d_id']}'\">Profile</button>
              </div>";
              $i++;
          }
      } else {
          echo "<p>No doctors available at the moment.</p>";
      }
      ?>
    </div>
  </section>

  <!-- Footer -->
  <section class="section footer">
    <h2>DocSphere</h2>
    <p>Empowering healthcare with AI. Consult experts, track health, and stay informed anytime, anywhere.</p>
    <div class="social-icons">
      <a href="#"><i class="fab fa-facebook-f"></i></a>
      <a href="#"><i class="fab fa-instagram"></i></a>
      <a href="#"><i class="fab fa-twitter"></i></a>
      <a href="#"><i class="fab fa-linkedin-in"></i></a>
    </div>
    <p>© 2025 MediAi. All rights reserved.</p>
  </section>
</div>

<script>
// Ask AI redirect
function redirectAskAI() {
    const q = document.getElementById('askai-input').value.trim();
    if (q) window.location.href = `askai.php?q=${encodeURIComponent(q)}`;
}
function redirectProfile() {
    window.location.href = "profile.php";
}
// Profile button redirect
function redirectProfile() {
    var userType = <?php echo json_encode($user_type); ?>;
    switch(userType) {
        case "patient": window.location.href = "user_profile.php"; break;
        case "doctor": window.location.href = "doctor_profile.php"; break;
        case "hospital": window.location.href = "hospital_profile.php"; break;
        default: alert("Unknown user type!"); break;
    }
}
</script>

</body>
</html>

<?php
session_start();
require_once 'db.php';

// Redirect if not logged in
if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}

// ✅ Get doctor ID from query parameter
if (!isset($_GET['d_id']) || empty($_GET['d_id'])) {
    die("Invalid doctor ID.");
}
$doctor_id = intval($_GET['d_id']);

// ✅ Fetch doctor information from DB
$stmt = $conn->prepare("SELECT d_id, name, workplace, gender, specialization, contact_number, license_number, email 
                        FROM doctors WHERE d_id = ?");
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Doctor not found.");
}

$doctor = $result->fetch_assoc();
$stmt->close();

// ✅ Fetch doctor’s appointment slots
$stmt2 = $conn->prepare("SELECT slot_id, free_date, day_of_week, start_time, end_time, status 
                         FROM appointment_slots 
                         WHERE d_id = ? 
                         ORDER BY free_date, start_time");
$stmt2->bind_param('i', $doctor_id);
$stmt2->execute();
$result2 = $stmt2->get_result();
$slots = $result2->fetch_all(MYSQLI_ASSOC);
$stmt2->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?php echo htmlspecialchars($doctor['name']); ?> - Docsphere</title>

<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
* { margin:0; padding:0; box-sizing:border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }
body { background: linear-gradient(135deg, #E3E4FA, #8897BD, #2C497F); color: #333; display:flex; min-height:100vh; }

/* Sidebar */
.sidebar {
  position: fixed;
  top:0; left:0;
  width:240px; height:100%;
  background: rgba(44, 73, 127, 0.95);
  backdrop-filter: blur(10px);
  padding:30px 15px;
  display:flex; flex-direction:column; align-items:center;
  box-shadow:4px 0 15px rgba(0,0,0,0.1);
  border-right:1px solid rgba(255,255,255,0.2);
  z-index:200;
  transition: width 0.3s;
}
.sidebar:hover { width:260px; }
.sidebar h2 { color:#E3E4FA; font-size:24px; margin-bottom:40px; font-weight:bold; letter-spacing:1px; }
.sidebar ul { list-style:none; width:100%; }
.sidebar ul li { margin:18px 0; display:flex; align-items:center; padding:12px 18px; border-radius:12px; cursor:pointer; transition:all 0.3s; color:#E3E4FA; font-weight:500; font-size:16px; }
.sidebar ul li:hover { background: linear-gradient(90deg, #8897BD, #E3E4FA); color:#2C497F; transform:translateX(5px); }
.sidebar ul li i { margin-right:14px; font-size:18px; transition: transform 0.3s; }
.sidebar ul li:hover i { transform: scale(1.2); }

/* Content area */
.content { margin-left:240px; width:calc(100% - 240px); }

/* Navbar */
.navbar {
  position: fixed; top:0; left:240px;
  width: calc(100% - 240px); height:70px;
  display:flex; align-items:center; justify-content:space-between;
  padding:0 30px;
  background: linear-gradient(135deg, #8897BD, #E3E4FA);
  box-shadow:0 2px 8px rgba(0,0,0,0.08);
  z-index:100;
}
.navbar .logo img { width:45px; border-radius:8px; cursor:pointer; }
.navbar .brand-name { font-size:22px; font-weight:bold; background: linear-gradient(90deg, #2C497F, #8897BD); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
.navbar .nav-buttons { display:flex; gap:15px; align-items:center; }
.navbar .nav-buttons button { padding:8px 16px; border:none; border-radius:25px; background: linear-gradient(90deg, #2C497F, #8897BD); color:#fff; font-weight:600; cursor:pointer; transition: transform 0.2s; }
.navbar .nav-buttons button:hover { transform: scale(1.05); }
.navbar-search { display:flex; border:1px solid #ccc; border-radius:25px; overflow:hidden; }
.navbar-search input { border:none; padding:6px 12px; outline:none; width:160px; }
.navbar-search button { background:#2C497F; border:none; padding:6px 12px; color:#fff; cursor:pointer; }

/* Main Content */
.main-content { display:flex; flex-wrap:wrap; gap:30px; padding:100px 20px 20px 20px; justify-content:center; }

/* Profile Card */
.profile-info { background: #F0F3FF; color:#2C497F; padding:20px; border-radius:15px; width:300px; }
.profile-info h2 { text-align:center; margin-bottom:15px; }
.profile-info p { margin-bottom:8px; font-size:14px; }

/* Appointments */
.appointments { background:#F0F3FF; color:#2C497F; border-radius:15px; padding:20px; width:300px; max-height:500px; overflow-y:auto; }
.appointments h3 { text-align:center; margin-bottom:15px; }
.appointment-slot { background: linear-gradient(145deg, #8897BD, #E3E4FA); padding:15px; margin-bottom:10px; border-radius:10px; text-align:center; transition: transform 0.3s; cursor:pointer; }
.appointment-slot:hover { transform: translateY(-3px); box-shadow:0 4px 12px rgba(0,0,0,0.2); }

/* Scrollbar */
.appointments::-webkit-scrollbar { width:6px; }
.appointments::-webkit-scrollbar-thumb { background:#648FA8; border-radius:3px; }

@media (max-width:768px) {
  .sidebar { display:none; }
  .content { margin-left:0; width:100%; }
  .navbar { left:0; width:100%; }
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
    <li onclick="location.href='calender.php'"><i class="fas fa-calendar-alt"></i> Calendar</li>
    <li onclick="location.href='contact.php'"><i class="fas fa-envelope"></i> Contact</li>
    <li onclick="location.href='chatapp/chat/chat.php'"><i class="fas fa-comments"></i> Chat</li>
  </ul>
</div>

<!-- Content -->
<div class="content">
  <!-- Navbar -->
  <div class="navbar">
    <div class="logo" onclick="location.href='ahomepage.php'"><img src="logo.png" alt="Docsphere Logo"></div>
    <div class="brand-name">Docsphere</div>
    <div class="nav-buttons">
      <div class="navbar-search">
        <input type="text" placeholder="Search...">
        <button>🔍</button>
      </div>
      <button onclick="redirectProfile()">Profile</button>
      <button onclick="location.href='logout.php'">Logout</button>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Doctor Profile -->
    <div class="profile-info">
      <h2><?php echo htmlspecialchars($doctor['name']); ?></h2>
      <p><strong>Workplace:</strong> <?php echo htmlspecialchars($doctor['workplace']); ?></p>
      <p><strong>Gender:</strong> <?php echo htmlspecialchars($doctor['gender']); ?></p>
      <p><strong>Specialization:</strong> <?php echo htmlspecialchars($doctor['specialization']); ?></p>
      <p><strong>Contact No:</strong> <?php echo htmlspecialchars($doctor['contact_number']); ?></p>
      <p><strong>License No:</strong> <?php echo htmlspecialchars($doctor['license_number']); ?></p>
      <p><strong>Email:</strong> <?php echo htmlspecialchars($doctor['email']); ?></p>
    </div>

    <!-- Appointment Slots -->
    <div class="appointments">
      <h3>Available Appointments</h3>
      <?php if(count($slots) === 0): ?>
        <p style="text-align:center;">No slots available.</p>
      <?php else: ?>
        <?php foreach($slots as $slot): ?>
          <div class="appointment-slot">
            <?php echo htmlspecialchars($slot['day_of_week']); ?>, <?php echo htmlspecialchars($slot['free_date']); ?><br>
            <?php echo htmlspecialchars($slot['start_time']); ?> - <?php echo htmlspecialchars($slot['end_time']); ?><br>
            Status: <?php echo htmlspecialchars($slot['status']); ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function redirectProfile() {
    // Redirect based on user type
    var userType = <?php echo json_encode(strtolower($_SESSION['user_type'] ?? 'patient')); ?>;
    switch(userType) {
        case "patient": window.location.href="user_profile.php"; break;
        case "doctor": window.location.href="doctor_profile.php"; break;
        case "hospital": window.location.href="hospital_profile.php"; break;
        default: alert("Unknown user type!"); break;
    }
}
</script>

</body>
</html>

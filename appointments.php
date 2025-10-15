<?php
session_start();
require 'db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: homepage.php");
    exit;
}

// If logout is requested
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: homepage.php");
    exit;
}

// Fetch doctors
$doctorsResult = $conn->query("SELECT d_id, name, specialization FROM doctors");
$doctors = [];
if ($doctorsResult) {
    while ($row = $doctorsResult->fetch_assoc()) $doctors[] = $row;
}

// Handle AJAX requests for available dates or times
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    if ($_GET['action'] === 'get_dates' && isset($_GET['doctor_id'])) {
        $doctor_id = intval($_GET['doctor_id']);
        $stmt = $conn->prepare("SELECT DISTINCT free_date FROM appointment_slots WHERE d_id = ? AND status = 'free' ORDER BY free_date");
        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $dates = [];
        while ($row = $result->fetch_assoc()) $dates[] = $row['free_date'];
        echo json_encode($dates);
        exit;
    }

    if ($_GET['action'] === 'get_times' && isset($_GET['doctor_id'], $_GET['date'])) {
        $doctor_id = intval($_GET['doctor_id']);
        $date = $_GET['date'];
        $stmt = $conn->prepare("SELECT slot_id, start_time, end_time FROM appointment_slots 
                                WHERE d_id = ? AND free_date = ? AND status = 'free' ORDER BY start_time");
        $stmt->bind_param("is", $doctor_id, $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $times = [];
        while ($row = $result->fetch_assoc()) {
            $times[] = [
                'slot_id' => $row['slot_id'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time']
            ];
        }
        echo json_encode($times);
        exit;
    }
}

// Handle appointment confirmation (AJAX POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['slot_id'])) {
    $slot_id = intval($_POST['slot_id']);
    $user_id = intval($_SESSION['user_id']);

    // Save appointment with status 'pending'
    $stmt = $conn->prepare("INSERT INTO appointments (slot_id, user_id, status) VALUES (?, ?, 'pending')");
    $stmt->bind_param("ii", $slot_id, $user_id);
    $stmt->execute();

    // Mark slot as pending
    $conn->query("UPDATE appointment_slots SET status='pending' WHERE slot_id = $slot_id");

    echo json_encode(['success' => true]);
    exit;
}

// Get user type for profile redirect
$user_type = strtolower($_SESSION['user_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Appointment - Docsphere</title>

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
  background: rgba(44, 73, 127, 0.95);
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

/* Content */
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

/* Appointment Container */
.appointment-container {
    max-width: 650px;
    width: 90%;
    margin: 120px auto 60px auto;
    background: rgba(255,255,255,0.05);
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.4);
}
.appointment-container h2 { font-size: 26px; margin-bottom: 25px; color: #2C497F; }
.form-group { margin-bottom: 12px; }
input, select, textarea {
    width: 100%; padding: 8px 14px; border-radius: 20px; border: 1px solid #ccc;
    outline: none; background: transparent; color: #2C497F; font-size: 14px;
}
input::placeholder, textarea::placeholder { color: #8897BD; }
textarea { resize: vertical; height: 80px; }
.radio-group { display: flex; gap: 20px; margin: 12px 0; }
.radio-group label { cursor: pointer; color: #2C497F; }
button.confirm {
    width: 150px; padding: 8px; border-radius: 25px;
    border: none; background: linear-gradient(90deg, #2C497F, #8897BD);
    color: white; cursor: pointer; font-size: 14px; font-weight: bold;
    box-shadow: 0 4px 15px rgba(44,73,127,0.6);
    transition: transform 0.2s, box-shadow 0.3s;
}
button.confirm:hover { transform: scale(1.05); box-shadow: 0 6px 20px rgba(44,73,127,0.8); }

/* Responsive */
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
        <button onclick="redirectProfile()">Profile</button>
        <button onclick="location.href='appointments.php?logout=1'">Logout</button>
    </div>
  </div>

  <!-- Appointment Form -->
  <div class="appointment-container">
      <h2>Appointment Form</h2>
      <div class="form-group">
          <select id="doctor">
              <option value="">Select Doctor</option>
              <?php foreach($doctors as $doc): ?>
                  <option value="<?php echo $doc['d_id']; ?>">
                      <?php echo htmlspecialchars($doc['name'] . " - " . $doc['specialization']); ?>
                  </option>
              <?php endforeach; ?>
          </select>
      </div>

      <div class="form-group" style="display:flex; gap:10px;">
          <input type="number" placeholder="Age" id="age" min="0">
          <select id="gender">
              <option value="">Gender</option>
              <option>Male</option>
              <option>Female</option>
              <option>Other</option>
          </select>
      </div>

      <div class="form-group"><textarea placeholder="Initial Problem" id="problem"></textarea></div>

      <div class="form-group" style="display:flex; gap:10px;">
          <select id="date"><option value="">Select Date</option></select>
          <select id="time"><option value="">Select Time</option></select>
      </div>

      <h3>Consultancy Type</h3>
      <div class="radio-group">
          <label><input type="radio" name="consultancy" value="Video"> Video</label>
          <label><input type="radio" name="consultancy" value="Chamber"> Chamber</label>
      </div>

      <button class="confirm" id="confirmBtn">Confirm</button>
  </div>
</div>

<script>
// Profile redirect
function redirectProfile() {
    var userType = <?php echo json_encode($user_type); ?>;
    switch(userType) {
        case "patient": window.location.href = "user_profile.php"; break;
        case "doctor": window.location.href = "doctor_profile.php"; break;
        case "hospital": window.location.href = "hospital_profile.php"; break;
        default: alert("Unknown user type!"); break;
    }
}

// Dynamic date & time loading
const doctorSelect = document.getElementById('doctor');
const dateSelect = document.getElementById('date');
const timeSelect = document.getElementById('time');

doctorSelect.addEventListener('change', () => {
    dateSelect.innerHTML = '<option value="">Select Date</option>';
    timeSelect.innerHTML = '<option value="">Select Time</option>';
    if (!doctorSelect.value) return;

    fetch(`appointments.php?action=get_dates&doctor_id=${doctorSelect.value}`)
        .then(res => res.json())
        .then(dates => {
            dates.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d;
                opt.textContent = d;
                dateSelect.appendChild(opt);
            });
        });
});

dateSelect.addEventListener('change', () => {
    timeSelect.innerHTML = '<option value="">Select Time</option>';
    if (!dateSelect.value || !doctorSelect.value) return;

    fetch(`appointments.php?action=get_times&doctor_id=${doctorSelect.value}&date=${dateSelect.value}`)
        .then(res => res.json())
        .then(times => {
            times.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.slot_id;
                opt.textContent = `${t.start_time} - ${t.end_time}`;
                timeSelect.appendChild(opt);
            });
        });
});

// Confirm button
document.getElementById('confirmBtn').addEventListener('click', () => {
    const doctor = doctorSelect.value;
    const age = parseInt(document.getElementById('age').value);
    const gender = document.getElementById('gender').value;
    const problem = document.getElementById('problem').value.trim();
    const slot_id = timeSelect.value;
    const consultancy = document.querySelector('input[name="consultancy"]:checked');

    if (!doctor || !age || age < 0 || !gender || !problem || !slot_id || !consultancy) {
        showModal("⚠️ Please fill all fields correctly. Age cannot be negative.");
        return;
    }

    fetch('appointments.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ slot_id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) showModal("✅ Appointment Pending!!", true);
        else showModal("❌ Failed to confirm appointment.");
    });
});

// Modal
function showModal(message, redirect = false) {
    const modal = document.createElement('div');
    modal.style.position = 'fixed';
    modal.style.top = '0';
    modal.style.left = '0';
    modal.style.width = '100%';
    modal.style.height = '100%';
    modal.style.background = 'rgba(0,0,0,0.6)';
    modal.style.display = 'flex';
    modal.style.justifyContent = 'center';
    modal.style.alignItems = 'center';
    modal.style.zIndex = '1000';

    const box = document.createElement('div');
    box.style.background = 'linear-gradient(135deg, #0A2438, #1A5A7F)';
    box.style.padding = '30px';
    box.style.borderRadius = '15px';
    box.style.textAlign = 'center';
    box.style.color = 'white';
    box.style.boxShadow = '0 0 15px rgba(0,0,0,0.5)';
    box.innerHTML = `<p style="font-size:18px; margin-bottom:20px;">${message}</p>`;

    const btn = document.createElement('button');
    btn.textContent = 'OK';
    btn.style.padding = '8px 18px';
    btn.style.border = 'none';
    btn.style.borderRadius = '8px';
    btn.style.background = 'linear-gradient(90deg, #ff4b2b, #ff416c)';
    btn.style.color = 'white';
    btn.style.cursor = 'pointer';
    btn.style.fontSize = '16px';
    btn.onclick = () => {
        document.body.removeChild(modal);
        if (redirect) window.location.href = 'ahomepage.php';
    };

    box.appendChild(btn);
    modal.appendChild(box);
    document.body.appendChild(modal);
}
</script>

</body>
</html>

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
    while ($row = $doctorsResult->fetch_assoc()) {
        $doctors[] = $row;
    }
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Appointment - MediAi</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; }
html, body { width: 100%; height: 100%; background: linear-gradient(to bottom, #0A2438, #1A5A7F, #6F9DB5); color: white; }

/* Navbar */
.navbar {
    position: fixed; top: 0; left: 0; width: 100%; height: 70px;
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 20px; background-color: #0A2438; z-index: 25;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.navbar .logo img { width: 60px; height: 60px; border-radius: 12px; }
.navbar .brand-name {
    position: absolute; left: 50%; transform: translateX(-50%);
    font-size: 28px; font-weight: bold;
    font-family: 'Lucida Handwriting', cursive, sans-serif;
    background: linear-gradient(90deg, red, orange, yellow, green, blue, indigo, violet);
    background-size: 300% 300%;
    -webkit-background-clip: text; background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 2px 2px 6px rgba(0,0,0,0.7);
    animation: rainbow 6s ease infinite;
}
@keyframes rainbow {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}
.navbar .nav-buttons { display: flex; gap: 10px; align-items: center; }
.navbar .nav-buttons button {
    padding: 6px 12px; border-radius: 5px; border: none;
    background-color: #648FA8; color: white; cursor: pointer;
}
.navbar .nav-buttons button:hover { opacity: 0.8; }
.navbar-search {
    display: flex; align-items: center;
    background-color: #0A2438; border: 1px solid rgba(255,255,255,0.2);
    border-radius: 20px; overflow: hidden;
}
.navbar-search input {
    border: none; padding: 6px 10px; background: transparent;
    color: white; font-size: 14px; width: 150px;
}
.navbar-search input::placeholder { color: rgba(255,255,255,0.5); }
.navbar-search button {
    background: linear-gradient(to right, #6F9DB5, #A8DCF4);
    border: none; padding: 6px 10px; cursor: pointer;
    color: white; font-size: 14px;
}
.navbar .notification { font-size: 20px; cursor: pointer; margin-right: 5px; color: white; }
.navbar .notification:hover { color: #A8DCF4; }

/* Sidebar */
.hamburger { width: 30px; height: 25px; display: flex; flex-direction: column; justify-content: space-between; cursor: pointer; position: fixed; top: 80px; left: 20px; z-index: 20; }
.hamburger div { width: 100%; height: 4px; background: white; border-radius: 2px; }
.sidebar { position: fixed; top: 0; left: -160px; width: 160px; height: 100vh; background-color: #0A2438; padding: 120px 20px 20px 20px; display: flex; flex-direction: column; justify-content: space-between; transition: left 0.3s ease; z-index: 15; }
.sidebar.open { left: 0; }
.sidebar a, .sidebar button { color: white; background: none; border: none; text-align: left; font-size: 16px; cursor: pointer; margin-bottom: 10px; text-decoration: none; }
.sidebar a.active { text-decoration: underline; }
.sidebar button { padding: 6px 12px; border-radius: 5px; background-color: #648FA8; }
.sidebar a:hover, .sidebar button:hover { opacity: 0.8; }
.sidebar .middle { display: flex; flex-direction: column; gap: 15px; }
.sidebar .bottom { margin-top: auto; display: flex; flex-direction: column; gap: 15px; }

/* Appointment Form */
.appointment-container {
    max-width: 650px;
    width: 90%;
    position: absolute;
    top: 55%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(255,255,255,0.05);
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.4);
}
.appointment-container h2 { font-size: 26px; margin-bottom: 25px; }
.form-group { margin-bottom: 12px; }
input, select, textarea {
    width: 100%; padding: 8px 14px; border-radius: 20px; border: 1px solid #ccc;
    outline: none; background: transparent; color: white; font-size: 14px;
}
input::placeholder { color: rgba(255,255,255,0.6); }
select { background: #0A2438; color: white; }
textarea { resize: vertical; height: 80px; }
.radio-group { display: flex; gap: 20px; margin: 12px 0; }
.radio-group label { cursor: pointer; }

button.confirm {
    width: 150px; padding: 8px; border-radius: 25px;
    border: none; background: linear-gradient(90deg, #ff4b2b, #ff416c);
    color: white; cursor: pointer; font-size: 14px; font-weight: bold;
    box-shadow: 0 4px 15px rgba(255,65,108,0.6);
    transition: transform 0.2s, box-shadow 0.3s;
}
button.confirm:hover { transform: scale(1.05); box-shadow: 0 6px 20px rgba(255,65,108,0.8); }
</style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div class="logo"><img src="logo.png" alt="MediAi Logo"></div>
    <div class="brand-name">Docsphere</div>
    <div class="nav-buttons" id="nav-buttons">
        <div class="navbar-search"><input type="text" placeholder="Search..."><button>🔍</button></div>
        <span class="notification">🔔</span>
        <button onclick="location.href='appointments.php?logout=1'">Logout</button>
    </div>
</div>

<!-- Sidebar + Hamburger -->
<div class="hamburger" id="hamburger"><div></div><div></div><div></div></div>
<div class="sidebar" id="sidebar">
    <div class="middle">
        <a>About</a>
        <a>Contact</a>
        <a href="appointments.php" class="active">Appointment</a>
        <a>Community</a>
        <a href="calendar.html">Calendar</a>
        <a href="payment.html">Payment</a>
    </div>
    <div class="bottom">
        <a>Settings</a>
        <a>Get Help</a>
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

<script>
// Sidebar toggle
document.getElementById('hamburger').addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
});

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

// Confirm button functionality
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
        if (data.success) {
            showModal("✅ Appointment Pending!!", true);
        } else {
            showModal("❌ Failed to confirm appointment.");
        }
    });
});

// Custom modal popup
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

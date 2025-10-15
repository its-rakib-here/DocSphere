<?php
session_start();
require_once 'db.php';

// Redirect if not logged in or not a doctor
if (!isset($_SESSION['loggedin']) || $_SESSION['user_type'] !== 'doctor') {
    header("Location: login.php");
    exit;
}

$doctor_id = $_SESSION['doctor_id'];
$success = '';
$error = '';

// Fetch confirmed appointments for this doctor (gender & email removed)
$stmt = $conn->prepare("SELECT a.appointment_id, a.booking_date, u.name AS patient_name, u.contact_number, u.address, u.blood_group,
    s.free_date, s.day_of_week, s.start_time, s.end_time
    FROM appointments a
    JOIN appointment_slots s ON a.slot_id = s.slot_id
    JOIN users u ON a.user_id = u.u_id
    WHERE s.d_id=? AND a.status='confirmed'
    ORDER BY s.free_date, s.start_time");
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booked Appointments</title>
<style>
body { font-family: Arial, sans-serif; margin:0; padding:0; background: linear-gradient(to bottom, #0A2438, #1A5A7F, #6F9DB5); color: white; }

.navbar { 
    position: fixed; 
    top:0; 
    width: 100%; 
    height: 70px; 
    background:#0A2438; 
    display:flex; 
    justify-content: space-between; 
    align-items:center; 
    padding:0 5px; 
    z-index: 10; 
}

.navbar .brand-name { 
    font-size:28px; 
    font-weight:bold; 
    font-family:'Lucida Handwriting', cursive; 
    background: linear-gradient(90deg, red,orange,yellow,green,blue,indigo,violet); 
    background-clip:text; 
    -webkit-background-clip:text; 
    -webkit-text-fill-color:transparent; 
    animation: rainbow 6s ease infinite; 
}

@keyframes rainbow {0% {background-position:0% 50%;}50% {background-position:100% 50%;}100% {background-position:0% 50%;}}

.nav-buttons button { 
    padding:6px 12px; 
    border:none; 
    border-radius:5px; 
    background:#648FA8; 
    color:white; 
    cursor:pointer; 
    margin-left:2px; 
}

.nav-buttons button:hover{opacity:0.8;}

.main-content { 
    display:flex; 
    flex-direction:column; 
    align-items:center; 
    padding-top:100px; 
    gap:30px; 
    background: rgba(10,36,56,0.7); /* same as .appointments background */
    width:100%;
    min-height:100vh;
    padding-bottom:50px;
}

.appointments { 
    background: rgba(10,36,56,0.7); 
    border-radius:15px; 
    padding:20px; 
    width:700px; 
    max-width:95%; 
}
.appointments h2 { text-align:center; color:#A8DCF4; margin-bottom:15px; }
.appointment-slot { 
    background: linear-gradient(145deg, rgba(30,50,70,0.85), rgba(50,90,130,0.75)); 
    padding:15px; 
    margin-bottom:15px; 
    border-radius:10px; 
    text-align:left; 
    transition: transform 0.3s; 
}
.appointment-slot:hover { 
    transform:translateY(-3px); 
    background: linear-gradient(145deg, rgba(40,70,100,0.9), rgba(70,120,180,0.8)); 
}
.appointment-slot p { margin:5px 0; }
.success { color: #4CAF50; text-align:center; margin-bottom:10px; font-weight:bold; }
.error { color: #FF6B6B; text-align:center; margin-bottom:10px; font-weight:bold; }
</style>
</head>
<body>

<div class="navbar">
    <div class="brand-name">Docsphere</div>
    <div class="nav-buttons">
        <button onclick="location.href='doctor_profile.php'">Profile</button>
        <button onclick="location.href='appointment_slot.php'">Appointment Slots</button>
        <button onclick="location.href='logout.php'">Logout</button>
    </div>
</div>

<div class="main-content">
    <div class="appointments">
        <h2>Confirmed Appointments</h2>
        <?php if(count($appointments) === 0): ?>
            <p style="text-align:center;">No confirmed appointments yet.</p>
        <?php else: ?>
            <?php foreach($appointments as $appt): ?>
                <div class="appointment-slot">
                    <p><strong>Patient:</strong> <?php echo htmlspecialchars($appt['patient_name']); ?></p>
                    <p><strong>Blood Group:</strong> <?php echo htmlspecialchars($appt['blood_group'] ?? 'N/A'); ?></p>
                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($appt['contact_number']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($appt['address']); ?></p>
                    <p><strong>Booking Date:</strong> <?php echo htmlspecialchars($appt['booking_date']); ?></p>
                    <p><strong>Appointment:</strong> <?php echo htmlspecialchars($appt['day_of_week']); ?>, <?php echo htmlspecialchars($appt['free_date']); ?> (<?php echo htmlspecialchars($appt['start_time']); ?> - <?php echo htmlspecialchars($appt['end_time']); ?>)</p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

</body>
</html>

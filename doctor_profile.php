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

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profile_update'])) {
    $name = trim($_POST['name'] ?? '');
    $workplace = trim($_POST['workplace'] ?? '');
    $license = trim($_POST['license'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');

    try {
        $stmt = $conn->prepare("UPDATE doctors SET 
            name = COALESCE(NULLIF(?,''), name),
            workplace = COALESCE(NULLIF(?,''), workplace),
            license_number = COALESCE(NULLIF(?,''), license_number),
            contact_number = COALESCE(NULLIF(?,''), contact_number),
            email = COALESCE(NULLIF(?,''), email),
            specialization = COALESCE(NULLIF(?,''), specialization)
            WHERE d_id = ?"
        );
        $stmt->bind_param('ssssssi', $name, $workplace, $license, $contact, $email, $specialization, $doctor_id);
        $stmt->execute();
        $stmt->close();
        $success = "Profile updated successfully!";
    } catch (Exception $e) {
        error_log("Doctor profile update error: " . $e->getMessage());
        $error = "Failed to update profile. Please try again later.";
    }
}

// Handle confirm/cancel appointment actions via GET
if (isset($_GET['confirm_appointment'])) {
    $appointment_id = intval($_GET['confirm_appointment']);
    $stmt = $conn->prepare("UPDATE appointments a
        JOIN appointment_slots s ON a.slot_id = s.slot_id
        SET a.status='confirmed' 
        WHERE a.appointment_id=? AND s.d_id=?");
    $stmt->bind_param('ii', $appointment_id, $doctor_id);
    $stmt->execute();
    $stmt->close();
    $success = "Appointment confirmed!";
}

if (isset($_GET['cancel_appointment'])) {
    $appointment_id = intval($_GET['cancel_appointment']);
    $stmt = $conn->prepare("UPDATE appointments a
        JOIN appointment_slots s ON a.slot_id = s.slot_id
        SET a.status='cancelled' 
        WHERE a.appointment_id=? AND s.d_id=?");
    $stmt->bind_param('ii', $appointment_id, $doctor_id);
    $stmt->execute();
    $stmt->close();
    $success = "Appointment cancelled!";
}

// Fetch doctor info
$stmt = $conn->prepare("SELECT name, workplace, license_number, contact_number, email, specialization FROM doctors WHERE d_id = ?");
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();
$stmt->close();

// Fetch pending appointments for this doctor (gender removed)
$stmt = $conn->prepare("SELECT a.appointment_id, u.name AS patient_name, u.contact_number, u.email, u.address, s.free_date, s.day_of_week, s.start_time, s.end_time 
    FROM appointments a
    JOIN appointment_slots s ON a.slot_id = s.slot_id
    JOIN users u ON a.user_id = u.u_id
    WHERE s.d_id=? AND a.status='pending'
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
<title><?php echo htmlspecialchars($doctor['name']); ?> - Profile</title>
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

.main-content { display:flex; flex-direction:column; align-items:center; padding-top:100px; gap:30px; }

.profile-info { background: rgba(10,36,56,0.85); padding:30px; border-radius:15px; width: 600px; max-width:95%; }
.profile-info h2 { text-align:center; color:#A8DCF4; margin-bottom:15px; }
.profile-info p { font-size:16px; margin-bottom:10px; }
.profile-info button { margin-top:15px; padding:10px 20px; border-radius:5px; border:none; background:#648FA8; color:white; cursor:pointer; }
.profile-info button:hover { opacity:0.8; }

#editForm { display:none; margin-top:20px; }
#editForm input { width:100%; padding:8px; margin-bottom:10px; border-radius:5px; border:none; outline:none; }
#editForm button { width:100%; padding:10px; border-radius:5px; border:none; background:#4CAF50; color:white; cursor:pointer; }
#editForm button.cancel { background:#648FA8; margin-top:5px; }

.appointments { background: rgba(10,36,56,0.7); border-radius:15px; padding:20px; width:600px; max-width:95%; }
.appointments h3 { text-align:center; color:#A8DCF4; margin-bottom:15px; }
.appointment-slot { background: linear-gradient(145deg, rgba(30,50,70,0.85), rgba(50,90,130,0.75)); padding:15px; margin-bottom:15px; border-radius:10px; text-align:left; transition: transform 0.3s; }
.appointment-slot button { margin:5px 5px 0 0; padding:6px 12px; border:none; border-radius:5px; cursor:pointer; }
.appointment-slot .confirm-btn { background:#4CAF50; color:white; }
.appointment-slot .cancel-btn { background:#FFA500; color:white; }
.appointment-slot .confirm-cancel-btn { background:#FF4500; color:white; }
.appointment-slot:hover { transform:translateY(-3px); background: linear-gradient(145deg, rgba(40,70,100,0.9), rgba(70,120,180,0.8)); }
.success { color: #4CAF50; text-align:center; margin-bottom:10px; font-weight:bold; }
.error { color: #FF6B6B; text-align:center; margin-bottom:10px; font-weight:bold; }
</style>
</head>
<body>

<div class="navbar">
    <div class="brand-name">Docsphere</div>
    <div class="nav-buttons">
        <button onclick="location.href='chatapp/chat-d/chat-d.php'">Messages</button>
        <button onclick="location.href='booked_appointment.php'">Booked Appointments</button>
        <button onclick="location.href='appointment_slot.php'">Appointment Slots</button>
        <button onclick="location.href='logout.php'">Logout</button>
    </div>
</div>

<div class="main-content">
    <div class="profile-info">
        <h2><?php echo htmlspecialchars($doctor['name']); ?></h2>
        <?php if($success): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php elseif($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <div id="infoDisplay">
            <p><strong>Workplace:</strong> <?php echo htmlspecialchars($doctor['workplace']); ?></p>
            <p><strong>License No:</strong> <?php echo htmlspecialchars($doctor['license_number']); ?></p>
            <p><strong>Contact No:</strong> <?php echo htmlspecialchars($doctor['contact_number']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($doctor['email']); ?></p>
            <p><strong>Specialization:</strong> <?php echo htmlspecialchars($doctor['specialization']); ?></p>
            <button onclick="toggleEdit()">Edit Profile</button>
        </div>

        <form method="POST" id="editForm">
            <input type="hidden" name="profile_update" value="1">
            <input type="text" name="name" placeholder="Full Name" value="<?php echo htmlspecialchars($doctor['name']); ?>">
            <input type="text" name="workplace" placeholder="Workplace" value="<?php echo htmlspecialchars($doctor['workplace']); ?>">
            <input type="text" name="license" placeholder="License No" value="<?php echo htmlspecialchars($doctor['license_number']); ?>">
            <input type="text" name="contact" placeholder="Contact No" value="<?php echo htmlspecialchars($doctor['contact_number']); ?>">
            <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($doctor['email']); ?>">
            <input type="text" name="specialization" placeholder="Specialization" value="<?php echo htmlspecialchars($doctor['specialization']); ?>">
            <button type="submit">Save Changes</button>
            <button type="button" class="cancel" onclick="toggleEdit()">Cancel</button>
        </form>
    </div>

    <div class="appointments">
        <h3>Pending Patient Appointments</h3>
        <?php if(count($appointments) === 0): ?>
            <p style="text-align:center;">No pending appointments.</p>
        <?php else: ?>
            <?php foreach($appointments as $appt): ?>
                <div class="appointment-slot" id="appt-<?php echo $appt['appointment_id']; ?>">
                    <p><strong>Patient:</strong> <?php echo htmlspecialchars($appt['patient_name']); ?></p>
                    <p><strong>Contact:</strong> <?php echo htmlspecialchars($appt['contact_number']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($appt['email']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($appt['address']); ?></p>
                    <p><strong>Appointment:</strong> <?php echo htmlspecialchars($appt['day_of_week']); ?>, <?php echo htmlspecialchars($appt['free_date']); ?> (<?php echo htmlspecialchars($appt['start_time']); ?> - <?php echo htmlspecialchars($appt['end_time']); ?>)</p>
                    <button class="confirm-btn" onclick="confirmAppointment(<?php echo $appt['appointment_id']; ?>)">Confirm</button>
                    <button class="cancel-btn" onclick="cancelAppointment(<?php echo $appt['appointment_id']; ?>, this)">Cancel</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleEdit() {
    const info = document.getElementById('infoDisplay');
    const form = document.getElementById('editForm');
    if(info.style.display === 'none'){
        info.style.display = 'block';
        form.style.display = 'none';
    } else {
        info.style.display = 'none';
        form.style.display = 'block';
    }
}

function confirmAppointment(id) {
    if(confirm("Are you sure you want to confirm this appointment?")) {
        window.location.href = "?confirm_appointment=" + id;
    }
}

function cancelAppointment(id, button) {
    const slotDiv = button.parentElement;
    button.style.display = 'none';
    let confirmBtn = slotDiv.querySelector('.confirm-cancel-btn');
    if(!confirmBtn) {
        confirmBtn = document.createElement('button');
        confirmBtn.className = 'confirm-cancel-btn';
        confirmBtn.innerText = 'Confirm Cancellation';
        confirmBtn.onclick = function() {
            if(confirm("Are you sure you want to cancel this appointment?")) {
                window.location.href = "?cancel_appointment=" + id;
            }
        };
        slotDiv.appendChild(confirmBtn);
    }
}
</script>

</body>
</html>

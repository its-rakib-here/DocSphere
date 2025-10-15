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

// Handle slot submission (add new slot)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $free_date = trim($_POST['free_date'] ?? '');
    $day_of_week = trim($_POST['day_of_week'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');

    if ($start_time >= $end_time) {
        $error = "End time must be later than start time.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO appointment_slots (free_date, day_of_week, start_time, end_time, d_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssi', $free_date, $day_of_week, $start_time, $end_time, $doctor_id);
            $stmt->execute();
            $stmt->close();
            $success = "Appointment slot added successfully!";
        } catch (Exception $e) {
            error_log("Appointment slot insert error: " . $e->getMessage());
            $error = "Failed to add slot. Please try again later.";
        }
    }
}

// Handle delete/cancel slot
if (isset($_GET['delete_slot'])) {
    $slot_id = intval($_GET['delete_slot']);

    // Check if booked or free
    $stmtCheck = $conn->prepare("SELECT status FROM appointment_slots WHERE slot_id = ? AND d_id = ?");
    $stmtCheck->bind_param('ii', $slot_id, $doctor_id);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    $slot = $resultCheck->fetch_assoc();
    $stmtCheck->close();

    if ($slot['status'] === 'free') {
        // Delete free slot
        $stmt = $conn->prepare("DELETE FROM appointment_slots WHERE slot_id = ? AND d_id = ?");
        $stmt->bind_param('ii', $slot_id, $doctor_id);
        if ($stmt->execute()) {
            header("Location: appointment_slot.php?success=Slot deleted successfully!");
            exit;
        } else {
            $error = "Cannot delete slot.";
        }
        $stmt->close();
    } elseif ($slot['status'] === 'booked') {
        // Cancel booked slot
        $stmt = $conn->prepare("UPDATE appointment_slots SET status='cancelled' WHERE slot_id = ? AND d_id = ?");
        $stmt->bind_param('ii', $slot_id, $doctor_id);
        if ($stmt->execute()) {
            header("Location: appointment_slot.php?success=Appointment cancelled successfully!");
            exit;
        } else {
            $error = "Cannot cancel appointment.";
        }
        $stmt->close();
    }
}

// Handle edit slot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $slot_id = intval($_POST['slot_id']);
    $free_date = trim($_POST['free_date'] ?? '');
    $day_of_week = trim($_POST['day_of_week'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');

    if ($start_time >= $end_time) {
        $error = "End time must be later than start time.";
    } else {
        $stmt = $conn->prepare("UPDATE appointment_slots SET free_date=?, day_of_week=?, start_time=?, end_time=? WHERE slot_id=? AND d_id=?");
        $stmt->bind_param('ssssii', $free_date, $day_of_week, $start_time, $end_time, $slot_id, $doctor_id);
        if ($stmt->execute()) {
            header("Location: appointment_slot.php?success=Slot updated successfully!");
            exit;
        } else {
            $error = "Cannot update slot.";
        }
        $stmt->close();
    }
}

// Fetch existing slots for this doctor (excluding cancelled)
$stmt = $conn->prepare("SELECT slot_id, free_date, day_of_week, start_time, end_time, status FROM appointment_slots WHERE d_id = ? AND status != 'cancelled' ORDER BY free_date, start_time");
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$slots = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get success message from GET
if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Appointment Slots - Docsphere</title>
<style>
body { font-family: Arial, sans-serif; margin:0; padding:0; background: linear-gradient(to bottom, #0A2438, #1A5A7F, #6F9DB5); color: white; }
.navbar { position: fixed; top:0; width: 100%; height: 70px; background:#0A2438; display:flex; justify-content: space-between; align-items:center; padding:0 5px; z-index: 10; }
.navbar .brand-name { font-size:28px; font-weight:bold; font-family:'Lucida Handwriting', cursive; background: linear-gradient(90deg, red,orange,yellow,green,blue,indigo,violet); background-clip:text; -webkit-background-clip:text; -webkit-text-fill-color:transparent; animation: rainbow 6s ease infinite; }
@keyframes rainbow {0% {background-position:0% 50%;}50% {background-position:100% 50%;}100% {background-position:0% 50%;}}
.nav-buttons button { padding:6px 12px; border:none; border-radius:5px; background:#648FA8; color:white; cursor:pointer; margin-left:2px; }
.nav-buttons button:hover{opacity:0.8;}
.main-content { display:flex; flex-direction:column; align-items:center; padding-top:100px; gap:30px; }
.slot-form, .existing-slots { background: rgba(10,36,56,0.85); padding:30px; border-radius:15px; width: 600px; max-width:95%; }
.slot-form h2, .existing-slots h2 { text-align:center; color:#A8DCF4; margin-bottom:15px; }
.slot-form input, .slot-form select, .slot-form button { width:100%; padding:10px; margin-bottom:10px; border-radius:5px; border:none; outline:none; }
.slot-form button { background:#4CAF50; color:white; cursor:pointer; }
.slot-form button:hover { opacity:0.9; }
.slot-item { background: linear-gradient(145deg, rgba(30,50,70,0.85), rgba(50,90,130,0.75)); padding:15px; margin-bottom:15px; border-radius:10px; text-align:center; transition: transform 0.3s; position: relative; }
.slot-item:hover { transform:translateY(-3px); background: linear-gradient(145deg, rgba(40,70,100,0.9), rgba(70,120,180,0.8)); }
.slot-item button { margin:5px; padding:5px 10px; border:none; border-radius:5px; cursor:pointer; }
.edit-btn { background:#4CAF50; color:white; }
.delete-btn { background:#FF6B6B; color:white; }
.confirm-delete-btn { background:#FF4500; color:white; }
.cancel-btn { background:#FFA500; color:white; }
.success { color: #4CAF50; text-align:center; margin-bottom:10px; font-weight:bold; }
.error { color: #FF6B6B; text-align:center; margin-bottom:10px; font-weight:bold; }
</style>
</head>
<body>

<div class="navbar">
    <div class="brand-name">Docsphere</div>
    <div class="nav-buttons">
        <button onclick="location.href='doctor_profile.php'">Profile</button>
        <button onclick="location.href='logout.php'">Logout</button>
    </div>
</div>

<div class="main-content">

    <div class="slot-form">
        <h2>Add Available Slot</h2>
        <?php if($success): ?><p class="success"><?php echo $success; ?></p><?php endif; ?>
        <?php if($error): ?><p class="error"><?php echo $error; ?></p><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <label>Free Date:</label>
            <input type="date" name="free_date" id="free_date" required onchange="fillDay()">
            
            <label>Day of Week:</label>
            <select name="day_of_week" id="day_of_week" required>
                <option value="">Select Day</option>
                <option value="Monday">Monday</option>
                <option value="Tuesday">Tuesday</option>
                <option value="Wednesday">Wednesday</option>
                <option value="Thursday">Thursday</option>
                <option value="Friday">Friday</option>
                <option value="Saturday">Saturday</option>
                <option value="Sunday">Sunday</option>
            </select>
            
            <label>Start Time:</label>
            <input type="time" name="start_time" required>
            
            <label>End Time:</label>
            <input type="time" name="end_time" required>
            
            <button type="submit">Add Slot</button>
        </form>
    </div>

    <div class="existing-slots">
        <h2>Existing Slots</h2>
        <?php if(count($slots) === 0): ?>
            <p style="text-align:center;">No slots added yet.</p>
        <?php else: ?>
            <?php foreach($slots as $slot): ?>
                <div class="slot-item" id="slot-<?php echo $slot['slot_id']; ?>">
                    <strong><?php echo htmlspecialchars($slot['day_of_week']); ?>, <?php echo htmlspecialchars($slot['free_date']); ?></strong><br>
                    <?php echo htmlspecialchars($slot['start_time']); ?> - <?php echo htmlspecialchars($slot['end_time']); ?><br>
                    Status: <?php echo htmlspecialchars($slot['status']); ?><br>
                    
                    <?php if($slot['status'] === 'free'): ?>
                        <button class="edit-btn" onclick="editSlot(<?php echo $slot['slot_id']; ?>,'<?php echo $slot['free_date']; ?>','<?php echo $slot['day_of_week']; ?>','<?php echo $slot['start_time']; ?>','<?php echo $slot['end_time']; ?>')">Edit</button>
                        <button class="delete-btn" onclick="showConfirm(<?php echo $slot['slot_id']; ?>)">Delete</button>
                    <?php elseif($slot['status'] === 'booked'): ?>
                        <button class="cancel-btn" onclick="confirmAction('cancel', <?php echo $slot['slot_id']; ?>)">Cancel</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<script>
function fillDay() {
    const dateInput = document.getElementById('free_date').value;
    if(!dateInput) return;
    const date = new Date(dateInput);
    const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    const dayName = days[date.getDay()];
    document.getElementById('day_of_week').value = dayName;
}

function editSlot(id, date, day, start, end) {
    document.getElementById('free_date').value = date;
    document.getElementById('day_of_week').value = day;
    document.querySelector('input[name="start_time"]').value = start;
    document.querySelector('input[name="end_time"]').value = end;
    
    let form = document.querySelector('.slot-form form');
    if(!document.getElementById('slot_id')) {
        let hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'slot_id';
        hidden.id = 'slot_id';
        form.appendChild(hidden);
    }
    document.getElementById('slot_id').value = id;

    document.querySelector('input[name="action"]').value = 'edit';
    form.querySelector('button[type="submit"]').innerText = 'Update Slot';
}

function confirmAction(action, id) {
    let msg = action === 'delete' ? "Are you sure you want to delete this slot?" : "Are you sure you want to cancel this appointment?";
    if(confirm(msg)) {
        window.location.href = '?delete_slot=' + id;
    }
}

function showConfirm(id) {
    const slotDiv = document.getElementById('slot-' + id);
    const deleteBtn = slotDiv.querySelector('.delete-btn');
    deleteBtn.style.display = 'none';
    if(!slotDiv.querySelector('.confirm-delete-btn')) {
        const confirmBtn = document.createElement('button');
        confirmBtn.className = 'confirm-delete-btn';
        confirmBtn.innerText = 'Confirm Deletion';
        confirmBtn.onclick = function() {
            window.location.href = '?delete_slot=' + id;
        };
        slotDiv.appendChild(confirmBtn);
    }
}
</script>

</body>
</html>

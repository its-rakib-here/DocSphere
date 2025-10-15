<?php
// drp.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Diabetes Risk Predictor - Docsphere</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; }

/* Body background and color */
body { 
    background: linear-gradient(to bottom, #0A2438, #1A5A7F, #6F9DB5); 
    color: white; 
    min-height: 100vh;
}

/* Navbar */
.navbar {
    position: fixed; top: 0; left: 0; width: 100%; height: 70px;
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 20px; background-color: #0A2438; z-index: 25;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.navbar .logo { display: flex; align-items: center; gap: 10px; }
.navbar .logo img { width: 60px; height: 60px; border-radius: 12px; }
.navbar .brand-name {
    position: absolute; left: 50%; transform: translateX(-50%);
    font-size: 28px; font-weight: bold;
    font-family: 'Lucida Handwriting', cursive, sans-serif;
    background: linear-gradient(90deg, red, orange, yellow, green, blue, indigo, violet);
    background-size: 300% 300%;
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 2px 2px 6px rgba(0,0,0,0.7);
    animation: rainbow 6s ease infinite;
}
@keyframes rainbow { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
.navbar .nav-buttons { display: flex; gap: 10px; align-items: center; }
.navbar .nav-buttons button {
    padding: 6px 12px; border-radius: 5px; border: none;
    background-color: #648FA8; color: white; cursor: pointer;
}
.navbar .nav-buttons button:hover { opacity: 0.8; }

/* Hamburger */
.hamburger {
    width: 30px; height: 25px; display: flex; flex-direction: column; justify-content: space-between; cursor: pointer;
    position: fixed; top: 80px; left: 20px; z-index: 20;
}
.hamburger div { width: 100%; height: 4px; background: white; border-radius: 2px; }

/* Sidebar */
.sidebar {
    position: fixed; top: 0; left: -160px; width: 160px; height: 100vh;
    background-color: #0A2438; padding: 120px 20px 20px 20px;
    display: flex; flex-direction: column; justify-content: space-between;
    transition: left 0.3s ease; z-index: 15;
}
.sidebar.open { left: 0; }
.sidebar .middle, .sidebar .bottom { display: flex; flex-direction: column; gap: 15px; }
.sidebar a, .sidebar button { color: white; background: none; border: none; text-align: left; font-size: 16px; cursor: pointer; margin-bottom: 10px; }
.sidebar button { padding: 6px 12px; border-radius: 5px; background-color: #648FA8; }
.sidebar a:hover, .sidebar button:hover { opacity: 0.8; }

/* Sidebar icons always visible */
.sidebar-icons { position: fixed; top: 120px; left: 20px; display: flex; flex-direction: column; gap: 20px; z-index: 10; }
.sidebar-icons button {
    width: 50px; height: 50px; border-radius: 10px;
    background-color: #0A2438; color: white; font-size: 20px;
    display: flex; align-items: center; justify-content: center;
    border: 1px solid rgba(255,255,255,0.3); cursor: pointer;
}
.sidebar-icons button:hover { background-color: #648FA8; }

/* Main content */
.main-content { 
    display: flex; justify-content: center; 
    padding-top: 100px; /* starts below navbar */
}
.content-box {
    background: rgba(36,36,62,0.85); padding: 30px; border-radius: 12px; text-align: center; min-width: 280px;
}
.content-box h1 { font-size: 24px; margin-bottom: 15px; }
.content-box input, .content-box select {
    width: 220px; padding: 8px 12px; margin: 8px 0; border-radius: 8px; border: 1px solid white; background: transparent; color: white;
    font-size: 14px;
}
.content-box input::placeholder { color: gray; font-size: 14px; }
.toggle-group { margin: 12px 0; text-align: left; }
.toggle-group label { display: block; margin-bottom: 4px; color: white; font-weight: bold; font-size: 14px; }
.toggle-group .options { display: flex; gap: 15px; }
.toggle-group input { margin-right: 4px; }

/* Button */
.check-btn {
    padding: 8px 25px; margin-top: 15px; border-radius: 18px; border: none;
    background: linear-gradient(to right, #ff512f, #dd2476); color: white; font-size: 14px; font-weight: bold; cursor: pointer;
}
.check-btn:hover { opacity: 0.9; }
</style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div class="logo"><img src="logo.png" alt="Docsphere Logo"></div>
    <div class="brand-name">Docsphere</div>
    <div class="nav-buttons">
        <button>Profile</button>
        <button>Logout</button>
    </div>
</div>

<!-- Hamburger -->
<div class="hamburger" id="hamburger"><div></div><div></div><div></div></div>

<!-- Sidebar Icons -->
<div class="sidebar-icons">
    <button title="About">ℹ️</button>
    <button title="Appointment">📅</button>
    <button title="Community">👥</button>
    <button title="Calendar">🗓️</button>
    <button title="Payment">💳</button>
    <button title="Settings">⚙️</button>
    <button title="Get Help">❓</button>
</div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="middle">
        <a>About</a>
        <a>Contact</a>
        <a>Appointment</a>
        <a>Community</a>
        <a>Calendar</a>
        <a>Payment</a>
    </div>
    <div class="bottom">
        <a>Settings</a>
        <a>Get Help</a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="content-box">
        <h1>Diabetes Risk Predictor</h1>
        <input type="text" id="age" placeholder="Age">
        <select id="gender">
            <option value="">Select Gender</option>
            <option>Male</option>
            <option>Female</option>
        </select>

        <div class="toggle-group">
            <label>Family History of Diabetes</label>
            <div class="options">
                <label><input type="radio" name="family" value="Yes">Yes</label>
                <label><input type="radio" name="family" value="No">No</label>
            </div>
        </div>

        <div class="toggle-group">
            <label>High Blood Pressure</label>
            <div class="options">
                <label><input type="radio" name="bp" value="Yes">Yes</label>
                <label><input type="radio" name="bp" value="No">No</label>
            </div>
        </div>

        <div class="toggle-group">
            <label>Physically Inactive</label>
            <div class="options">
                <label><input type="radio" name="inactive" value="Yes">Yes</label>
                <label><input type="radio" name="inactive" value="No">No</label>
            </div>
        </div>

        <div class="toggle-group">
            <label>BMI &gt; 25</label>
            <div class="options">
                <label><input type="radio" name="bmi" value="Yes">Yes</label>
                <label><input type="radio" name="bmi" value="No">No</label>
            </div>
        </div>

        <button class="check-btn" id="checkBtn">Check Risk</button>
    </div>
</div>

<script>
// Sidebar toggle
const hamburger = document.getElementById('hamburger');
const sidebar = document.getElementById('sidebar');
hamburger.addEventListener('click', () => sidebar.classList.toggle('open'));

// Check Risk Logic
document.getElementById('checkBtn').addEventListener('click', () => {
    const age = parseInt(document.getElementById('age').value);
    const gender = document.getElementById('gender').value;
    const family = document.querySelector('input[name="family"]:checked');
    const bp = document.querySelector('input[name="bp"]:checked');
    const inactive = document.querySelector('input[name="inactive"]:checked');
    const bmi = document.querySelector('input[name="bmi"]:checked');

    if (!age || !gender || !family || !bp || !inactive || !bmi) {
        alert("Please complete all fields.");
        return;
    }

    let score = 0;
    if (age >= 60) score += 3;
    else if (age >= 50) score += 2;
    else if (age >= 40) score += 1;

    if (gender === "Male") score += 1;
    if (family.value === "Yes") score += 1;
    if (bp.value === "Yes") score += 1;
    if (inactive.value === "Yes") score += 1;
    if (bmi.value === "Yes") score += 1;

    const risk = score >= 5 ? "High Risk of Type 2 Diabetes" : "Low Risk";

    window.location.href = `drp_result.php?risk=${encodeURIComponent(risk)}&points=${score}`;
});
</script>

</body>
</html>

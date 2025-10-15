<?php
// risk_checker.php
// No backend logic added — this page is purely front-end HTML/CSS/JS
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Risk Checker - MediAi</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; }
html, body { width: 100%; height: 100%; overflow: hidden; background: linear-gradient(to bottom, #0A2438, #1A5A7F, #6F9DB5); color: white; }
a { text-decoration: none; color: inherit; cursor: pointer; }

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
.navbar .nav-buttons button { padding: 6px 12px; border-radius: 5px; border: none; background-color: #648FA8; color: white; cursor: pointer; }
.navbar .nav-buttons button:hover { opacity: 0.8; }

/* Hamburger */
.hamburger {
    width: 30px; height: 25px; display: flex; flex-direction: column; justify-content: space-between; cursor: pointer;
    position: fixed; top: 80px; left: 20px; z-index: 20;
}
.hamburger div { width: 100%; height: 4px; background: white; border-radius: 2px; }

/* Sidebar */
.sidebar {
    position: fixed; top: 0; left: -160px;
    width: 160px; height: 100vh; background-color: #0A2438; padding: 120px 20px 20px 20px;
    display: flex; flex-direction: column; justify-content: space-between; transition: left 0.3s ease; z-index: 15;
}
.sidebar.open { left: 0; }
.sidebar .middle, .sidebar .bottom { display: flex; flex-direction: column; gap: 15px; }
.sidebar a, .sidebar button { color: white; background: none; border: none; text-align: left; font-size: 16px; cursor: pointer; margin-bottom: 10px; }
.sidebar button { padding: 6px 12px; border-radius: 5px; background-color: #648FA8; }
.sidebar a:hover, .sidebar button:hover { opacity: 0.8; }

/* Sidebar icons */
.sidebar-icons {
    position: fixed; top: 120px; left: 20px; display: flex; flex-direction: column; gap: 20px; z-index: 10;
}
.sidebar-icons button {
    width: 50px; height: 50px; border-radius: 10px;
    background-color: #0A2438; color: white; font-size: 20px;
    display: flex; align-items: center; justify-content: center;
    border: 1px solid rgba(255,255,255,0.3); cursor: pointer;
}
.sidebar-icons button:hover { background-color: #648FA8; }

/* Risk Checker Cards */
.risk-container {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    gap: 30px;
    flex-wrap: wrap;
    justify-content: center;
}
.risk-card {
    background: linear-gradient(145deg, rgba(30,50,70,0.85), rgba(50,90,130,0.75));
    border-radius: 15px; padding: 20px; min-width: 180px; text-align: center;
    display: flex; flex-direction: column; align-items: center; cursor: pointer;
    transition: transform 0.3s, box-shadow 0.3s, background 0.3s;
}
.risk-card img { width: 80px; height: 80px; margin-bottom: 10px; }
.risk-card span { font-size: 16px; font-weight: bold; color: white; }
.risk-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 25px rgba(0,0,0,0.5);
    background: linear-gradient(145deg, rgba(40,70,100,0.95), rgba(70,120,180,0.85));
}
</style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div class="logo"><img src="logo.png" alt="MediAi Logo"></div>
    <div class="brand-name">Docsphere</div>
    <div class="nav-buttons">
        <div class="navbar-search"><input type="text" placeholder="Search..."><button>🔍</button></div>
        <span class="notification">🔔</span>
        <button>Logout</button>
    </div>
</div>

<!-- Hamburger -->
<div class="hamburger" id="hamburger"><div></div><div></div><div></div></div>

<!-- Sidebar Icons -->
<div class="sidebar-icons" id="sidebar-icons">
    <button title="About">ℹ️</button>
    <button title="Appointment">📅</button>
    <button title="Community">👥</button>
    <button title="Calendar" onclick="location.href='calendar.html'">🗓️</button>
    <button title="Payment" onclick="location.href='payment.html'">💳</button>
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
        <a href="calendar.html">Calendar</a>
        <a href="payment.html">Payment</a>
    </div>
    <div class="bottom">
        <a>Settings</a>
        <a>Get Help</a>
    </div>
</div>

<!-- Risk Checker Cards -->
<div class="risk-container">
    <div class="risk-card" onclick="location.href='crp.php'">
        <img src="cardio.png" alt="Cardiovascular">
        <span>Cardiovascular</span>
    </div>
    <div class="risk-card" onclick="location.href='drp.php'">
        <img src="diabetes.png" alt="Diabetes">
        <span>Diabetes</span>
    </div>
    <div class="risk-card" onclick="location.href='srp.php'">
        <img src="stroke.png" alt="Stroke">
        <span>Stroke</span>
    </div>
    <div class="risk-card" onclick="location.href='krp.php'">
        <img src="kidney.png" alt="Kidney Failure">
        <span>Kidney Failure</span>
    </div>
</div>

<script>
// Sidebar toggle
const hamburger = document.getElementById('hamburger');
const sidebar = document.getElementById('sidebar');
hamburger.addEventListener('click', () => {
    sidebar.classList.toggle('open');
});
</script>

</body>
</html>

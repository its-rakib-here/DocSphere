<?php
// crp.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cardiovascular Risk Predictor - Docsphere</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* === Keep your existing CSS === */
* { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; }
html, body { width: 100%; height: 100%; overflow: hidden; }
body { background: linear-gradient(to bottom, #0A2438, #1A5A7F, #6F9DB5); color: white; display: flex; height: 100vh; }
.navbar { position: fixed; top: 0; left: 0; width: 100%; height: 70px; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; background-color: #0A2438; z-index: 25; border-bottom: 1px solid rgba(255,255,255,0.1);}
.navbar .logo { display: flex; align-items: center; gap: 10px; }
.navbar .logo img { width: 60px; height: 60px; border-radius: 12px; }
.navbar .brand-name { position: absolute; left: 50%; transform: translateX(-50%); font-size: 28px; font-weight: bold; font-family: 'Lucida Handwriting', cursive, sans-serif; background: linear-gradient(90deg, red, orange, yellow, green, blue, indigo, violet); background-size: 300% 300%; -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; text-shadow: 2px 2px 6px rgba(0,0,0,0.7); animation: rainbow 6s ease infinite; }
@keyframes rainbow { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
.navbar .nav-buttons { display: flex; gap: 10px; align-items: center; }
.navbar .nav-buttons button { padding: 6px 12px; border-radius: 5px; border: none; background-color: #648FA8; color: white; cursor: pointer; }
.navbar .nav-buttons button:hover { opacity: 0.8; }
.navbar-search { display: flex; align-items: center; background-color: #0A2438; border: 1px solid rgba(255,255,255,0.2); border-radius: 20px; overflow: hidden; }
.navbar-search input { border: none; padding: 6px 10px; background: transparent; color: white; font-size: 14px; width: 150px; }
.navbar-search input::placeholder { color: rgba(255,255,255,0.5); }
.navbar-search button { background: linear-gradient(to right, #6F9DB5, #A8DCF4); border: none; padding: 6px 10px; cursor: pointer; color: white; font-size: 14px; }
.navbar .notification { font-size: 20px; cursor: pointer; margin-right: 5px; color: white; }
.navbar .notification:hover { color: #A8DCF4; }
.hamburger { width: 30px; height: 25px; display: flex; flex-direction: column; justify-content: space-between; cursor: pointer; position: fixed; top: 80px; left: 20px; z-index: 20; }
.hamburger div { width: 100%; height: 4px; background: white; border-radius: 2px; }
.sidebar { position: fixed; top: 0; left: -200px; width: 200px; height: 100vh; background-color: #0A2438; padding: 120px 20px 20px 20px; display: flex; flex-direction: column; justify-content: space-between; transition: left 0.3s ease; z-index: 15; }
.sidebar.open { left: 0; }
.sidebar .middle, .sidebar .bottom { display: flex; flex-direction: column; gap: 15px; }
.sidebar a, .sidebar button { color: white; background: none; border: none; text-align: left; font-size: 16px; cursor: pointer; margin-bottom: 10px; }
.sidebar button { padding: 6px 12px; border-radius: 5px; background-color: #648FA8; }
.sidebar a:hover, .sidebar button:hover { opacity: 0.8; }
.sidebar .bottom { margin-top: auto; display: flex; flex-direction: column; gap: 15px; }
.sidebar-icons { position: fixed; top: 120px; left: 20px; display: flex; flex-direction: column; gap: 20px; z-index: 10; }
.sidebar-icons button { width: 50px; height: 50px; border-radius: 10px; background-color: #0A2438; color: white; font-size: 20px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.3); cursor: pointer; }
.sidebar-icons button:hover { background-color: #648FA8; }
.main-content { margin-left: 200px; padding-top: 100px; flex: 1; display: flex; justify-content: center; align-items: center; height: 100vh; }
.container { max-width: 500px; padding: 30px; display: flex; flex-direction: column; gap: 15px; background-color: rgba(36,36,62,0.8); border-radius: 15px; }
.container input[type="number"], .container input[type="text"] { width: 100%; padding: 10px; border-radius: 10px; border: 1px solid white; background: transparent; color: white; outline: none; font-size: 14px; }
.container input[type="number"]::-webkit-inner-spin-button, .container input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
.container input::placeholder { color: rgba(255,255,255,0.6); }
.calc-btn { margin-top: 15px; padding: 10px 30px; border-radius: 20px; border: none; background: linear-gradient(to right, #ff512f, #dd2476); color: white; font-weight: bold; font-size: 16px; cursor: pointer; }
.calc-btn:hover { background: linear-gradient(to right, #ff9966, #ff5e62); }
</style>
</head>
<body>

<div class="navbar">
    <div class="logo"><img src="logo.png" alt="Docsphere Logo"></div>
    <div class="brand-name">Docsphere</div>
    <div class="nav-buttons">
        <div class="navbar-search"><input type="text" placeholder="Search..."><button>🔍</button></div>
        <span class="notification">🔔</span>
        <button onclick="alert('Signup')">Signup</button>
        <button onclick="alert('Login')">Login</button>
    </div>
</div>

<div class="hamburger" id="hamburger"><div></div><div></div><div></div></div>

<div class="sidebar-icons" id="sidebar-icons">
    <button title="About">ℹ️</button>
    <button title="Appointment">📅</button>
    <button title="Community">👥</button>
    <button title="Calendar">🗓️</button>
    <button title="Payment">💳</button>
    <button title="Settings">⚙️</button>
    <button title="Get Help">❓</button>
</div>

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

<div class="main-content">
    <div class="container">
        <h1>Cardiovascular Risk Predictor</h1>
        <input type="number" id="age" placeholder="Age">
        <input type="number" id="totalChol" placeholder="Total Cholesterol (mg/dL)">
        <input type="number" id="hdlChol" placeholder="HDL Cholesterol (mg/dL)">
        <input type="number" id="sysBP" placeholder="Systolic BP (mm Hg)">
        <input type="text" id="smoker" placeholder="Smoker? (yes/no)">
        <input type="text" id="diabetic" placeholder="Diabetic? (yes/no)">
        <button class="calc-btn" onclick="validateAndRedirect()">Calculate Risk</button>
    </div>
</div>

<script>
const hamburger = document.getElementById('hamburger');
const sidebar = document.getElementById('sidebar');
hamburger.addEventListener('click', () => { sidebar.classList.toggle('open'); });

function validateAndRedirect() {
    const age = document.getElementById("age").value.trim();
    const totalChol = document.getElementById("totalChol").value.trim();
    const hdlChol = document.getElementById("hdlChol").value.trim();
    const sysBP = document.getElementById("sysBP").value.trim();
    const smoker = document.getElementById("smoker").value.trim().toLowerCase();
    const diabetic = document.getElementById("diabetic").value.trim().toLowerCase();

    if(age === "" || totalChol === "" || hdlChol === "" || sysBP === "" || 
       (smoker !== "yes" && smoker !== "no") || (diabetic !== "yes" && diabetic !== "no")) {
        alert("Please fill all fields correctly. Use 'yes' or 'no' for smoker and diabetic.");
        return;
    }

    // Save inputs to localStorage
    localStorage.setItem("age", age);
    localStorage.setItem("totalChol", totalChol);
    localStorage.setItem("hdlChol", hdlChol);
    localStorage.setItem("sysBP", sysBP);
    localStorage.setItem("smoker", smoker);
    localStorage.setItem("diabetic", diabetic);

    // Redirect
    window.location.href = "crp_result.php";
}
</script>
</body>
</html>

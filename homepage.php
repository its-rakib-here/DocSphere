<?php
// (Optional) start a session if you want to track login status in future
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MediAi Home Page</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; }
html, body { width: 100%; height: 100%; overflow: hidden; }

body { background: linear-gradient(to bottom, #0A2438, #1A5A7F, #6F9DB5); color: white; }
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

/* Updated brand name */
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

/* Navbar Search */
.navbar-search {
    display: flex;
    align-items: center;
    background-color: #0A2438;
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 20px;
    overflow: hidden;
}
.navbar-search input {
    border: none;
    padding: 6px 10px;
    background: transparent;
    color: white;
    font-size: 14px;
    width: 150px;
}
.navbar-search input::placeholder { color: rgba(255,255,255,0.5); }
.navbar-search button {
    background: linear-gradient(to right, #6F9DB5, #A8DCF4);
    border: none;
    padding: 6px 10px;
    cursor: pointer;
    color: white;
    font-size: 14px;
}

/* Notification Icon */
.navbar .notification {
    font-size: 20px;
    cursor: pointer;
    margin-right: 5px;
    color: white;
}
.navbar .notification:hover { color: #A8DCF4; }

/* Hamburger Icon below navbar */
.hamburger {
    width: 30px; height: 25px; display: flex; flex-direction: column; justify-content: space-between; cursor: pointer;
    position: fixed; top: 80px; left: 20px; z-index: 20;
}
.hamburger div { width: 100%; height: 4px; background: white; border-radius: 2px; }

/* Sidebar */
.sidebar {
    position: fixed; top: 0; left: -160px;
    width: 160px;
    height: 100vh;
    background-color: #0A2438; 
    padding: 120px 20px 20px 20px;
    display: flex; flex-direction: column; justify-content: space-between;
    transition: left 0.3s ease; 
    z-index: 15;
}
.sidebar.open { left: 0; }
.sidebar .middle, .sidebar .bottom { display: flex; flex-direction: column; gap: 15px; }
.sidebar a, .sidebar button { 
    color: white; 
    background: none; 
    border: none; 
    text-align: left; 
    font-size: 16px; 
    cursor: pointer; 
    margin-bottom: 10px;
}
.sidebar button { 
    padding: 6px 12px; 
    border-radius: 5px; 
    background-color: #648FA8; 
}
.sidebar a:hover, .sidebar button:hover { opacity: 0.8; }
.sidebar .bottom { margin-top: auto; display: flex; flex-direction: column; gap: 15px; }

/* Icons shown before click */
.sidebar-icons {
    position: fixed; top: 120px; left: 20px;
    display: flex; flex-direction: column; gap: 20px; z-index: 10;
}
.sidebar-icons button {
    width: 50px; height: 50px; border-radius: 10px;
    background-color: #0A2438; color: white; font-size: 20px;
    display: flex; align-items: center; justify-content: center;
    border: 1px solid rgba(255,255,255,0.3); cursor: pointer;
}
.sidebar-icons button:hover { background-color: #648FA8; }

/* Sections */
.section { width: 100vw; height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 0 20px; text-align: center; transition: all 1s ease; opacity: 0; transform: translateY(50px); }
.active { opacity: 1; transform: translateY(0); }

/* Hero Section with background image */
#hero { background: url('back1.jpg') no-repeat center center/cover; position: relative; }
#hero::before {
    content: "";
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(10, 36, 56, 0.6);
}
#hero h1, #hero p, #hero .search-bar { position: relative; z-index: 1; }
#hero h1 { font-size: 28px; font-weight: bold; margin-bottom: 10px; }
#hero p { font-size: 16px; color: #D4DEE3; margin-bottom: 30px; }
.search-bar { display: flex; justify-content: center; gap: 10px; }
.search-bar input { width: 350px; padding: 10px 15px; border-radius: 30px; border: none; background-color: #0A2438; color: white; font-size: 15px; }
.search-bar button { padding: 8px 20px; border-radius: 30px; border: none; font-weight: bold; background: linear-gradient(to right, #6F9DB5, #A8DCF4); color: white; cursor: pointer; }

/* Services Section (Page 2) */
#services { background: url('back2.jpg') no-repeat center center/cover; position: relative; }
#services::before { content: ""; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(10,36,56,0.7); }
#services h2, #services .service-cards { position: relative; z-index: 1; }
.services h2 { font-size: 22px; font-weight: bold; color: white; margin-bottom: 20px; }
.service-cards { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; }
.service-card { display: flex; flex-direction: column; align-items: center; justify-content: center; background: linear-gradient(145deg, rgba(30,50,70,0.9), rgba(50,90,130,0.8)); padding: 20px; border-radius: 15px; min-width: 140px; cursor: pointer; transition: transform 0.3s, box-shadow 0.3s, background 0.3s; }
.service-card img { width: 50px; height: 50px; margin-bottom: 10px; }
.service-card span { font-size: 14px; color: white; font-weight: bold; }
.service-card:hover { transform: translateY(-5px); box-shadow: 0 12px 25px rgba(0,0,0,0.5); background: linear-gradient(145deg, rgba(40,70,100,0.95), rgba(70,120,180,0.85)); }

/* Doctors Section (Page 3) */
#doctors-section { background: url('back3.jpg') no-repeat center center/cover; position: relative; }
#doctors-section::before { content: ""; position: absolute; top:0; left:0; right:0; bottom:0; background: rgba(10,36,56,0.7); }
#doctors-section h2, #doctors-section .doctor-cards { position: relative; z-index: 1; }
.doctors-section h2 { font-size: 20px; font-weight: bold; color: #A8DCF4; margin-bottom: 20px; }
.doctor-cards { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; }
.doctor-card { background: linear-gradient(145deg, rgba(30,50,70,0.85), rgba(50,90,130,0.75)); border-radius: 15px; padding: 15px; min-width: 140px; text-align: center; display: flex; flex-direction: column; align-items: center; transition: transform 0.3s, box-shadow 0.3s, background 0.3s; }
.doctor-card img { width: 80px; height: 80px; border-radius: 50%; margin-bottom: 10px; }
.doctor-card .name { color: white; font-weight: bold; }
.doctor-card .spec { color: #D4DEE3; font-size: 12px; margin-bottom: 10px; }
.doctor-card button { padding: 6px 12px; border-radius: 5px; border: none; background-color: #648FA8; color: white; cursor: pointer; font-size: 12px; }
.doctor-card:hover { transform: translateY(-5px); box-shadow: 0 12px 25px rgba(0,0,0,0.5); background: linear-gradient(145deg, rgba(40,70,100,0.9), rgba(70,120,180,0.8)); }

/* Footer Styling */
.footer {
    background-color: #0A2438;
    color: #D4DEE3;
    text-align: center;
    padding: 50px 20px 30px 20px;
    position: relative;
    overflow: hidden;
}
.footer-content h2 {
    font-size: 28px;
    font-weight: bold;
    margin-bottom: 15px;
    color: #A8DCF4;
}
.footer-content p {
    font-size: 14px;
    line-height: 1.6;
    max-width: 600px;
    margin: 0 auto 20px auto;
}
.social-icons {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-bottom: 20px;
}
.social-icons a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #6F9DB5, #A8DCF4);
    border-radius: 50%;
    transition: transform 0.3s, box-shadow 0.3s;
}
.social-icons a:hover {
    transform: scale(1.2);
    box-shadow: 0 8px 20px rgba(168, 220, 244, 0.6);
}
.social-icons img {
    width: 22px;
    height: 22px;
}
.footer-bottom {
    font-size: 12px;
    color: rgba(212, 222, 227, 0.7);
}
</style>
</head>
<body>

<!-- Top Navbar -->
<div class="navbar">
    <div class="logo"><img src="logo.png" alt=" "></div>
    <div class="brand-name">Docsphere</div>
    <div class="nav-buttons">
        <div class="navbar-search"><input type="text" placeholder="Search..."><button>🔍</button></div>
        <span class="notification">🔔</span>
        <button onclick="location.href='signup.php'">Signup</button>
        <button onclick="location.href='login.php'">Login</button>
    </div>
</div>

<!-- Hamburger below navbar -->
<div class="hamburger" id="hamburger"><div></div><div></div><div></div></div>

<!-- Sidebar Icons visible by default -->
<div class="sidebar-icons" id="sidebar-icons">
    <button title="About">ℹ️</button>
    <button title="Appointment">📅</button>
    <button title="Community">👥</button>
    <button title="Calendar" onclick="location.href='calendar.php'">🗓️</button>
    <button title="Payment" onclick="location.href='payment.php'">💳</button>
    <button title="Settings">⚙️</button>
    <button title="Get Help">❓</button>
</div>

<!-- Sidebar full clickable -->
<div class="sidebar" id="sidebar">
    <div class="middle">
        <a>About</a>
        <a>Contact</a>
        <a>Appointment</a>
        <a>Community</a>
        <a href="calendar.php">Calendar</a>
        <a href="payment.php">Payment</a>
    </div>
    <div class="bottom">
        <a>Settings</a>
        <a>Get Help</a>
    </div>
</div>

<!-- Sections -->
<section class="section active" id="hero">
    <h1>YOUR 24/7 MEDICAL PARTNER</h1>
    <p>A cutting-edge website that lets you consult medical mentors...</p>
    <div class="search-bar"><input type="text" placeholder="Start your journey here..."><button>Ask Ai</button></div>
</section>

<section class="section services" id="services">
    <h2>OUR SERVICES</h2>
    <div class="service-cards">
        <div class="service-card"><img src="d.png" alt=""><span>Diagnosis Hub</span></div>
        <div class="service-card"><img src="r.png" alt=""><span>Risk Checker</span></div>
        <div class="service-card"><img src="h.png" alt=""><span>Health Chatbot</span></div>
        <div class="service-card"><img src="b.png" alt=""><span>Smart Booking</span></div>
        <div class="service-card"><img src="blood.png" alt=""><span>Blood</span></div>
    </div>
</section>

<section class="section doctors-section" id="doctors-section">
    <h2>171 EXPERT DOCTORS</h2>
    <div class="doctor-cards">
        <div class="doctor-card"><img src="salam.png" alt=""><span class="name">Dr Rahim</span><span class="spec">Specialist</span><button>Meet</button></div>
        <div class="doctor-card"><img src="salam.png" alt=""><span class="name">Dr Yasin</span><span class="spec">Specialist</span><button>Meet</button></div>
        <div class="doctor-card"><img src="salam.png" alt=""><span class="name">Dr Salam</span><span class="spec">Specialist</span><button>Meet</button></div>
        <div class="doctor-card"><img src="2ndor.png" alt=""><span class="name">Dr Rafi</span><span class="spec">Specialist</span><button>Meet</button></div>
        <div class="doctor-card"><img src="2ndor.png" alt=""><span class="name">Dr Arnab</span><span class="spec">Specialist</span><button>Meet</button></div>
    </div>
</section>

<!-- Footer Section -->
<section class="section footer" id="footer">
    <div class="footer-content">
        <h2>DocSphere</h2>
        <p>Empowering healthcare with AI. Consult experts, track health, and stay informed anytime, anywhere.</p>
        <div class="social-icons">
            <a href="https://www.facebook.com" target="_blank" class="social"><img src="facebook.png" alt="Facebook"></a>
            <a href="https://www.instagram.com" target="_blank" class="social"><img src="instagram.png" alt="Instagram"></a>
            <a href="https://www.twitter.com" target="_blank" class="social"><img src="twitter.png" alt="Twitter"></a>
            <a href="https://www.linkedin.com" target="_blank" class="social"><img src="linkedin.png" alt="LinkedIn"></a>
        </div>
        <p class="footer-bottom">© 2025 MediAi. All rights reserved.</p>
    </div>
</section>

<script>
// Sidebar toggle
const hamburger = document.getElementById('hamburger');
const sidebar = document.getElementById('sidebar');
hamburger.addEventListener('click', () => {
    sidebar.classList.toggle('open');
});

// Section scroll animation
const sections = document.querySelectorAll(".section");
let current = 0;
let isScrolling = false;

function showSection(index) {
    sections.forEach((s, i) => s.classList.remove("active"));
    sections[index].classList.add("active");
    sections[index].scrollIntoView({ behavior: "smooth" });
}

window.addEventListener("wheel", e => {
    if(isScrolling) return;
    isScrolling = true;
    setTimeout(() => isScrolling = false, 1000);

    if(e.deltaY > 0 && current < sections.length - 1) current++;
    else if(e.deltaY < 0 && current > 0) current--;

    showSection(current);
});
</script>

</body>
</html>

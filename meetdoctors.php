<?php
// ✅ Start PHP Section
require_once __DIR__ . '/db.php'; // adjust path according to your folder

// Fetch doctors data
$query = "SELECT d_id, name, specialization FROM doctors";
$result = mysqli_query($conn, $query);

$doctors = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $doctors[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Meet Our Doctors</title>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
    }

    body {
      background: linear-gradient(to bottom, #0A2438, #1A5A7F, #6F9DB5);
      color: white;
      min-height: 100vh;
      overflow-x: hidden;
    }

    /* Navbar */
    .navbar {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 70px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 20px;
      background-color: #0A2438;
      z-index: 25;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .navbar .logo {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .navbar .logo img {
      width: 60px;
      height: 60px;
      border-radius: 12px;
    }

    .navbar .brand-name {
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
      font-size: 28px;
      font-weight: bold;
      font-family: 'Lucida Handwriting', cursive, sans-serif;
      background: linear-gradient(90deg, red, orange, yellow, green, blue, indigo, violet);
      background-size: 300% 300%;
      -webkit-background-clip: text;
      background-clip: text;
      -webkit-text-fill-color: transparent;
      text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.7);
      animation: rainbow 6s ease infinite;
    }

    @keyframes rainbow {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    .navbar .nav-buttons {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .navbar .nav-buttons button {
      padding: 6px 12px;
      border-radius: 5px;
      border: none;
      background-color: #648FA8;
      color: white;
      cursor: pointer;
    }

    .navbar .nav-buttons button:hover {
      opacity: 0.8;
    }

    .navbar-search {
      display: flex;
      align-items: center;
      background-color: #0A2438;
      border: 1px solid rgba(255, 255, 255, 0.2);
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

    .navbar-search input::placeholder {
      color: rgba(255, 255, 255, 0.5);
    }

    .navbar-search button {
      background: linear-gradient(to right, #6F9DB5, #A8DCF4);
      border: none;
      padding: 6px 10px;
      cursor: pointer;
      color: white;
      font-size: 14px;
    }

    .notification {
      font-size: 20px;
      cursor: pointer;
      color: white;
    }

    .notification:hover {
      color: #A8DCF4;
    }

    /* Main Content */
    .main-content {
      padding: 120px 20px 40px 20px;
      max-width: 600px;
      margin: 0 auto;
    }

    .main-content h1 {
      font-size: 28px;
      margin-bottom: 30px;
      text-align: center;
      color: #b1dff4;
    }

    /* Scrollable Doctor List */
    .doctor-list {
      max-height: 400px;
      overflow-y: auto;
      scrollbar-width: thin;
      scrollbar-color: #648FA8 #0A2438;
      animation: scrollFade 1s ease;
    }

    @keyframes scrollFade {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .doctor-list::-webkit-scrollbar {
      width: 8px;
    }
    .doctor-list::-webkit-scrollbar-track {
      background: #0A2438;
    }
    .doctor-list::-webkit-scrollbar-thumb {
      background-color: #648FA8;
      border-radius: 4px;
    }

    .doctor-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #0A2438;
      padding: 12px 20px;
      border-radius: 10px;
      margin-bottom: 15px;
      gap: 15px;
      animation: slideIn 0.4s ease forwards;
      opacity: 0;
    }

    @keyframes slideIn {
      to { opacity: 1; transform: translateY(0); }
      from { opacity: 0; transform: translateY(10px); }
    }

    .doctor-info {
      display: flex;
      align-items: center;
      gap: 15px;
      flex: 1;
    }

    .doctor-info img {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #648FA8;
    }

    .doctor-name {
      font-size: 16px;
      font-weight: bold;
      color: white;
    }

    .doctor-specialization {
      font-size: 14px;
      color: #A8DCF4;
    }

    .doctor-row button {
      padding: 6px 12px;
      border: none;
      border-radius: 5px;
      background-color: #648FA8;
      color: white;
      cursor: pointer;
      font-size: 14px;
      white-space: nowrap;
    }

    .doctor-row button:hover {
      background-color: #A8DCF4;
      color: #0A2438;
    }
  </style>
</head>
<body>
  <!-- Top Navbar -->
  <div class="navbar">
    <div class="logo"><img src="logo.png" alt="MediAi Logo" /></div>
    <div class="brand-name">Docsphere</div>
    <div class="nav-buttons">
      <div class="navbar-search">
        <input type="text" placeholder="Search..." />
        <button>🔍</button>
      </div>
      <span class="notification">🔔</span>
      <button>Logout</button>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <h1>Meet Our Doctors</h1>

    <div class="doctor-list">
      <?php if (!empty($doctors)): ?>
        <?php foreach ($doctors as $doctor): ?>
          <div class="doctor-row">
            <div class="doctor-info">
              <img src="salam.png" alt="<?php echo htmlspecialchars($doctor['name']); ?>" />
              <div>
                <div class="doctor-name"><?php echo htmlspecialchars($doctor['name']); ?></div>
                <div class="doctor-specialization"><?php echo htmlspecialchars($doctor['specialization']); ?></div>
              </div>
            </div>
            <button onclick="location.href='doctor.php?id=<?php echo $doctor['d_id']; ?>'">Meet</button>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="text-align:center; color:#A8DCF4;">No doctors found in the database.</p>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // Optional smooth fade-in effect for multiple rows
    const rows = document.querySelectorAll('.doctor-row');
    rows.forEach((row, index) => {
      row.style.animationDelay = `${index * 0.1}s`;
    });
  </script>
</body>
</html>

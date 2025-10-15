<?php
// drpresult.php
require_once 'db.php'; // Database connection

$risk = $_GET['risk'] ?? '';
$points = $_GET['points'] ?? '';
$doctor = null;

// High Risk check
if (stripos($risk, 'High') !== false) {
    $sql = "SELECT name, workplace, contact_number FROM doctors WHERE specialization='Endocrinologist' ORDER BY RAND() LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $doctor = $result->fetch_assoc();
    } else {
        // Dummy doctor if none in DB
        $doctor = [
            'name' => 'Dr. Not Assigned',
            'workplace' => 'N/A',
            'contact_number' => 'N/A'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Diabetes Risk Result - Docsphere</title>
<style>
body { 
    background: linear-gradient(to bottom, #0f0c29, #302b63, #24243e); 
    color: white; 
    font-family: Arial, sans-serif; 
    display: flex; 
    justify-content: center; 
    align-items: center; 
    height: 100vh; 
    margin:0; 
}
.result-box { 
    background: rgba(36,36,62,0.85); 
    padding: 40px; 
    border-radius: 15px; 
    text-align: center; 
    min-width: 300px; 
}
.result-box h1 { font-size: 26px; margin-bottom: 15px; color: #ffab00; }
.result-box p { font-size: 18px; margin: 10px 0; }
.result-box button { 
    padding: 10px 20px; 
    border-radius: 10px; 
    border: none; 
    background-color: #648FA8; 
    color: white; 
    cursor: pointer; 
}
.result-box button:hover { opacity: 0.9; }
</style>
</head>
<body>

<div class="result-box">
    <?php if (stripos($risk, 'High') !== false): ?>
        <h1><?= htmlspecialchars($risk) ?></h1>
        <p>Total Points: <?= htmlspecialchars($points) ?></p>
        <p>Consult: <?= htmlspecialchars($doctor['name']) ?> (Endocrinologist)</p>
        <p>Workplace: <?= htmlspecialchars($doctor['workplace']) ?></p>
        <p>Contact: <?= htmlspecialchars($doctor['contact_number']) ?></p>
    <?php else: ?>
        <h1><?= htmlspecialchars($risk) ?></h1>
        <p>Total Points: <?= htmlspecialchars($points) ?></p>
        <p>You are currently out of risk. Maintain a healthy lifestyle!</p>
    <?php endif; ?>
    <button onclick="goBack()">Back</button>
</div>

<script>
function goBack() { window.history.back(); }
</script>

</body>
</html>

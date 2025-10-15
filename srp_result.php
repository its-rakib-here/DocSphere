<?php
// srpresult.php
require_once 'db.php'; // your database connection

// Fetch GET parameters
$c = isset($_GET['c']) ? intval($_GET['c']) : 0;
$h = isset($_GET['h']) ? intval($_GET['h']) : 0;
$d = isset($_GET['d']) ? intval($_GET['d']) : 0;
$s2 = isset($_GET['s2']) ? intval($_GET['s2']) : 0;
$v = isset($_GET['v']) ? intval($_GET['v']) : 0;
$age = isset($_GET['age']) ? intval($_GET['age']) : 0;
$gender = isset($_GET['gender']) ? $_GET['gender'] : 'male';

// Calculate score
$score = $c + $h + $d + $s2 + $v;
if($age >= 75) $score += 2;
elseif($age >= 65) $score += 1;
if($gender === "female") $score += 1;

// Determine risk
if($score <= 2) {
    $risk = "Low Risk";
    $message = "You are currently out of risk. Maintain a healthy lifestyle!";
    $doctor = null;
} elseif($score <= 5) {
    $risk = "Moderate Risk";
    $message = "Medium risk. Consider consulting a doctor for evaluation.";
    $doctor = null;
} else {
    $risk = "High Risk";
    $message = "<strong style='color:red;'>Danger! High stroke risk!</strong>";
    // Fetch a cardiologist for high risk
    $sql = "SELECT name, workplace, contact_number FROM doctors WHERE specialization='Cardiologist' ORDER BY RAND() LIMIT 1";
    $result = $conn->query($sql);
    if($result && $result->num_rows > 0) {
        $doctor = $result->fetch_assoc();
    } else {
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
<title>Stroke Risk Result - Docsphere</title>
<style>
body { font-family: Arial, sans-serif; background: linear-gradient(to bottom, #0A2438, #1A5A7F, #6F9DB5); color: white; display: flex; justify-content: center; align-items: center; height: 100vh; margin:0; }
.result-box { background: rgba(0,0,0,0.5); padding: 30px; border-radius: 15px; text-align: center; }
.result-box h2 { margin-bottom: 20px; }
.score { font-size: 24px; margin-bottom: 10px; }
.risk { font-size: 28px; font-weight: bold; color: #FFD700; }
.result-box button { margin-top:20px;padding:10px 20px;border:none;border-radius:10px;background:#648FA8;color:white;cursor:pointer; }
</style>
</head>
<body>
<div class="result-box">
    <h2>Stroke Risk Result</h2>
    <div class="score">Score: <?php echo $score; ?></div>
    <div class="risk"><?php echo $risk; ?></div>
    <p><?php echo $message; ?></p>
    <?php if($doctor): ?>
        <p>Recommended Cardiologist: <?php echo $doctor['name']; ?></p>
        <p>Workplace: <?php echo $doctor['workplace']; ?></p>
        <p>Contact: <?php echo $doctor['contact_number']; ?></p>
    <?php endif; ?>
    <button onclick="history.back()">Back</button>
</div>
</body>
</html>

<?php
// crpresult.php
require_once 'db.php'; // your database connection

$doctor = null;

// Fetch a Cardiologist only if high risk (we'll use JS to check points)
$sql = "SELECT name, workplace, contact_number FROM doctors WHERE specialization='Cardiologist' ORDER BY RAND() LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $doctor = $result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CRP Result - Docsphere</title>
<style>
body { font-family: Arial, sans-serif; background: linear-gradient(to bottom, #0A2438, #1A5A7F, #6F9DB5); color: white; display: flex; justify-content: center; align-items: center; height: 100vh; margin:0; }
.result-container { background: rgba(36,36,62,0.85); padding: 30px; border-radius: 15px; text-align: center; max-width: 400px; }
.result-container h1 { font-size: 24px; margin-bottom: 20px; color: #ffab00; }
.result-container p { font-size: 18px; margin: 10px 0; }
.result-container button { padding: 8px 20px; border-radius: 10px; border: none; background-color: #648FA8; color: white; cursor: pointer; margin-top: 20px; }
.result-container button:hover { opacity: 0.9; }
</style>
</head>
<body>

<div class="result-container">
    <h1>Cardiovascular Risk Result</h1>
    <p id="points"></p>
    <p id="category"></p>
    <div id="message"></div>

    <button onclick="window.history.back()">Back</button>
</div>

<script>
// Get input from localStorage
const age = parseInt(localStorage.getItem("age"));
const totalChol = parseInt(localStorage.getItem("totalChol"));
const hdlChol = parseInt(localStorage.getItem("hdlChol"));
const sysBP = parseInt(localStorage.getItem("sysBP"));
const smoker = localStorage.getItem("smoker");
const diabetic = localStorage.getItem("diabetic");

// Realistic scoring based on Framingham risk table approximation
let points = 0;

// Age points
if (age < 35) points += 0;
else if (age <= 39) points += 2;
else if (age <= 44) points += 5;
else if (age <= 49) points += 7;
else if (age <= 54) points += 9;
else if (age <= 59) points += 11;
else if (age <= 64) points += 13;
else points += 15;

// Total cholesterol points
if (totalChol < 160) points += 0;
else if (totalChol < 200) points += 1;
else if (totalChol < 240) points += 2;
else if (totalChol < 280) points += 3;
else points += 4;

// HDL cholesterol points
if (hdlChol >= 60) points -= 1;
else if (hdlChol < 40) points += 2;

// Systolic BP points
if (sysBP < 120) points += 0;
else if (sysBP < 130) points += 1;
else if (sysBP < 140) points += 2;
else if (sysBP < 160) points += 3;
else points += 4;

// Smoking points
if (smoker === "yes") points += 2;

// Diabetes points
if (diabetic === "yes") points += 2;

// Determine risk category
let category = "";
let riskPercent = 0;
let messageHTML = "";

if (points <= 8) {
    category = "Low Risk";
    riskPercent = "<8%";
    messageHTML = "<p>You are currently out of risk. Maintain a healthy lifestyle!</p>";
} else if (points <= 16) {
    category = "Moderate Risk";
    riskPercent = "9-16%";
    messageHTML = "<p>Medium risk. Consider consulting a cardiologist for evaluation.</p>";
} else {
    category = "High Risk";
    riskPercent = ">16%";
    messageHTML = "<p style='color:#ff4d4d; font-weight:bold;'>Danger! High cardiovascular risk!</p>";

    // Show cardiologist recommendation
    <?php if ($doctor): ?>
    messageHTML += "<p>Recommended Cardiologist: <?= addslashes($doctor['name']) ?></p>";
    messageHTML += "<p>Workplace: <?= addslashes($doctor['workplace']) ?></p>";
    messageHTML += "<p>Contact: <?= addslashes($doctor['contact_number']) ?></p>";
    <?php endif; ?>
}

// Show results
document.getElementById("points").innerText = "Total Points: " + points;
document.getElementById("category").innerText = "Risk Category: " + category + " (" + riskPercent + ")";
document.getElementById("message").innerHTML = messageHTML;
</script>

</body>
</html>

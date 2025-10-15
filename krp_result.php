<?php
// krp_result.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KFRP Result - Docsphere</title>
<style>
body { font-family: Arial, sans-serif; background: linear-gradient(to bottom, #0A2438, #1A5A7F, #6F9DB5); color: white; display:flex; flex-direction:column; align-items:center; justify-content:center; height:100vh; }
.result-box { background: rgba(0,0,0,0.3); padding:40px; border-radius:12px; text-align:center; }
.result-box h1 { margin-bottom:20px; font-size:22px; }
.result-box p { font-size:18px; margin:10px 0; }
button { margin-top:20px; padding:10px 20px; font-size:16px; border-radius:12px; border:none; background: linear-gradient(to right, #ff512f, #dd2476); color:white; cursor:pointer; }
button:hover { opacity:0.9; }
</style>
</head>
<body>
<div class="result-box">
    <h1>Kidney Failure Risk Result</h1>
    <p id="riskPercent"></p>
    <p id="riskLevel"></p>
    <button onclick="window.location.href='krp.php'">Go Back</button>
</div>

<script>
// Fetch result from localStorage
const riskPercent = localStorage.getItem('riskPercent');
const riskLevel = localStorage.getItem('riskLevel');

if(riskPercent && riskLevel) {
    document.getElementById('riskPercent').textContent = "Risk: " + riskPercent + "%";
    document.getElementById('riskLevel').textContent = "Level: " + riskLevel;
} else {
    document.getElementById('riskPercent').textContent = "No result found. Please fill the predictor form.";
    document.getElementById('riskLevel').textContent = "";
}
</script>
</body>
</html>

<?php
// srp.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stroke Risk Predictor - Docsphere</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; }
  body { background: linear-gradient(to bottom, #0A2438, #1A5A7F, #6F9DB5); color: white; display: flex; height: 100vh; }
  a { text-decoration: none; color: inherit; cursor: pointer; }
  input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
  input[type=number] { -moz-appearance: textfield; }

  .form-container {
    margin: auto;
    padding: 30px;
    background: rgba(0,0,0,0.5);
    border-radius: 15px;
    text-align: center;
  }
  .form-container h2 { margin-bottom: 20px; }
  .form-container label { display: inline-block; width: 150px; text-align: right; margin-right: 10px; }
  .form-container input { width: 80px; padding: 5px; border-radius: 5px; border: none; text-align: center; }
  .form-container .row { margin: 10px 0; }
  .form-container .gender { margin: 10px 0; }
  .form-container button {
    margin-top: 20px; padding: 10px 20px;
    border: none; border-radius: 20px;
    background: linear-gradient(to right, #6F9DB5, #A8DCF4);
    color: white; font-weight: bold; cursor: pointer;
  }
  .form-container button:hover { opacity: 0.8; }
  .error { color: #ff6b6b; margin-top: 10px; font-weight: bold; }
</style>
</head>
<body>

<div class="form-container">
  <h2>Stroke Risk Predictor</h2>
  <form method="GET" action="srp_result.php" id="srpForm">
    <div class="row"><label>C (CHF):</label><input type="number" name="c" placeholder="Enter value"></div>
    <div class="row"><label>H (Hypertension):</label><input type="number" name="h" placeholder="Enter value"></div>
    <div class="row"><label>D (Diabetes):</label><input type="number" name="d" placeholder="Enter value"></div>
    <div class="row"><label>S2 (Stroke/TIA):</label><input type="number" name="s2" placeholder="Enter value"></div>
    <div class="row"><label>V (Vascular):</label><input type="number" name="v" placeholder="Enter value"></div>
    <div class="row"><label>Age:</label><input type="number" name="age" placeholder="Enter age"></div>
    <div class="gender">
      <label>Gender:</label>
      <input type="radio" name="gender" value="female"> Female
      <input type="radio" name="gender" value="male"> Male
    </div>
    <button type="submit">Calculate Risk</button>
  </form>
</div>

</body>
</html>

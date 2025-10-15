<?php
session_start();
require_once 'db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$error = '';
$max_attempts = 5;
$lockout_time = 300; // 5 minutes

if (!isset($_SESSION['failed_attempts'])) {
    $_SESSION['failed_attempts'] = 0;
    $_SESSION['last_failed_time'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Lockout check
    if ($_SESSION['failed_attempts'] >= $max_attempts &&
        (time() - $_SESSION['last_failed_time']) < $lockout_time) {
        $remaining = $lockout_time - (time() - $_SESSION['last_failed_time']);
        $error = "Too many failed attempts. Try again after " . ceil($remaining / 60) . " minute(s).";
    } else {

        $identifier = trim($_POST['username'] ?? '');
        $password   = $_POST['password'] ?? '';
        $remember   = isset($_POST['remember']);

        if ($identifier === '' || $password === '') {
            $error = "Please fill in both username/email and password.";
        } else {
            try {
                $is_email = (bool)filter_var($identifier, FILTER_VALIDATE_EMAIL);

                $tables = [
                    ['table' => 'users',    'id' => 'u_id', 'pwd' => 'user_password',   'type' => 'patient'],
                    ['table' => 'doctors',  'id' => 'd_id', 'pwd' => 'doctor_password', 'type' => 'doctor'],
                    ['table' => 'hospitals','id' => 'h_id', 'pwd' => 'hospital_password','type' => 'hospital']
                ];

                $matches = [];

                foreach ($tables as $t) {
                    if ($is_email) {
                        $sql = "SELECT * FROM {$t['table']} WHERE email = ? LIMIT 1";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param('s', $identifier);
                    } else {
                        $sql = "SELECT * FROM {$t['table']} WHERE name = ? LIMIT 1";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param('s', $identifier);
                    }
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res && $res->num_rows > 0) {
                        $row = $res->fetch_assoc();
                        $row['_table'] = $t['table'];
                        $row['_id_col'] = $t['id'];
                        $row['_pwd_col'] = $t['pwd'];
                        $row['_type'] = $t['type'];
                        $matches[] = $row;
                    }
                    $stmt->close();
                }

                if (count($matches) === 0) {
                    $error = "Account not found. Please check your credentials.";
                    $_SESSION['failed_attempts']++;
                    $_SESSION['last_failed_time'] = time();
                } elseif (count($matches) > 1) {
                    $error = "Multiple accounts found for these credentials across user types. Contact admin.";
                } else {
                    $user = $matches[0];
                    $hash = $user[$user['_pwd_col']] ?? '';

                    if ($hash === '') {
                        $error = "Account found but password data is missing. Contact admin.";
                    } elseif (password_verify($password, $hash)) {
                        // Reset failed attempts
                        $_SESSION['failed_attempts'] = 0;
                        $_SESSION['last_failed_time'] = 0;

                        // Set session separately based on type
                        $_SESSION['loggedin'] = true;

                        if ($user['_type'] === 'patient') {
                            $_SESSION['user_id']   = $user[$user['_id_col']];
                            $_SESSION['user_name'] = $user['name'] ?? '';
                            $_SESSION['user_email']= $user['email'] ?? '';
                            $_SESSION['user_type'] = 'patient';
                            $redirect = 'ahomepage.php';
                        } elseif ($user['_type'] === 'doctor') {
                            $_SESSION['doctor_id']   = $user[$user['_id_col']];
                            $_SESSION['doctor_name'] = $user['name'] ?? '';
                            $_SESSION['doctor_email']= $user['email'] ?? '';
                            $_SESSION['user_type']   = 'doctor';
                            $redirect = 'doctor_profile.php';
                        }else {
                            $redirect = 'ahomepage.php';
                        }

                        // Remember me
                        if ($remember) {
                            setcookie('rememberme', $identifier, time() + 30*24*3600, '/');
                        } else {
                            if (isset($_COOKIE['rememberme'])) {
                                setcookie('rememberme', '', time() - 3600, '/');
                            }
                        }

                        header("Location: $redirect");
                        exit;
                    } else {
                        $error = "Invalid password.";
                        $_SESSION['failed_attempts']++;
                        $_SESSION['last_failed_time'] = time();
                    }
                }
            } catch (Exception $e) {
                error_log("Login error: " . $e->getMessage());
                $error = "An internal error occurred. Please try again later.";
            }
        }
    }
}

// Prefill username/email if present
$prefill = '';
if (!empty($_POST['username'])) {
    $prefill = htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8');
} elseif (!empty($_COOKIE['rememberme'])) {
    $prefill = htmlspecialchars($_COOKIE['rememberme'], ENT_QUOTES, 'UTF-8');
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>DocSphere - Login</title>
  <style>
    /* --- same CSS as before --- */
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #fff; }
    .container { display: flex; width: 100%; height: 100vh; }
    .left { flex: 1; background: url("loginimg.jpg") no-repeat center center/cover; display: flex; flex-direction: column; justify-content: space-between; align-items: center; padding: 60px 40px 40px 40px; text-align: center; position: relative; overflow: hidden; }
    .left h1 { font-size: 42px; font-weight: bold; text-shadow: 2px 2px 10px rgba(0,0,0,0.3); margin-bottom: 15px; opacity: 0; transform: translateY(20px); transition: opacity 1s ease, transform 1s ease; }
    .welcome-navie { color: navy; } .welcome-docsphere { color: rgb(255, 45, 99); }
    .left p { font-size: 16px; line-height: 1.6; color: black; max-width: 500px; opacity: 0; transform: translateY(20px); transition: opacity 1s ease 0.3s, transform 1s ease 0.3s; margin-bottom: 40px; }
    .rocket { width: 100px; height: 150px; position: relative; animation: float 3s ease-in-out infinite; margin-bottom: 20px; }
    .rocket-body { width: 50px; height: 100px; background: white; border-radius: 25px 25px 0 0; margin: 0 auto; position: relative; }
    .rocket-body::before { content: ""; width: 0; height: 0; border-left: 25px solid transparent; border-right: 25px solid transparent; border-bottom: 30px solid #ff2e63; position: absolute; top: -30px; left: 0; }
    .rocket-fire { width: 20px; height: 40px; background: linear-gradient(to bottom, #ffdd00, #ff2e63); border-radius: 50%; margin: 0 auto; animation: fire 0.5s infinite alternate; }
    @keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
    @keyframes fire { 0% { transform: scaleY(1); opacity: 1; } 100% { transform: scaleY(1.5); opacity: 0.6; } }
    .right { flex: 1; background: linear-gradient(135deg, #2b2467, #3c2f7d); display: flex; justify-content: center; align-items: center; padding: 40px; color: white; }
    .login-card { background: #463c8b; padding: 40px; border-radius: 15px; width: 100%; max-width: 350px; box-shadow: 0 8px 20px rgba(0,0,0,0.5); color: white; }
    .login-card h2 { margin-bottom: 15px; text-align: center; color: #ffffff; }
    .alert { padding: 10px 12px; border-radius: 6px; margin-bottom: 12px; font-size: 14px; }
    .alert.error { background: rgba(255, 80, 80, 0.15); color: #ffb3b3; border: 1px solid rgba(255,80,80,0.2); }
    form { width: 100%; }
    .input-box { display: flex; align-items: center; margin-bottom: 15px; background: #4938a0; border-radius: 5px; padding: 10px; border: 1px solid white; }
    .input-box .icon { margin-right: 10px; }
    .input-box input { border: none; outline: none; flex: 1; background: transparent; color: white; font-size: 14px; }
    .options { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 12px; }
    .options a { color: #d1d1ff; text-decoration: none; }
    .btn { width: 100%; padding: 10px; background: #2b2467; border: none; border-radius: 5px; color: white; font-weight: bold; cursor: pointer; transition: 0.3s; }
    .btn:hover { background: #1b1750; }
    .signup { margin-top: 15px; text-align: center; font-size: 14px; cursor: pointer; color: #d1d1ff; }
    label.remember { cursor: pointer; font-size: 13px; color: #e9e9ff; }
    .small-note { font-size: 12px; color: #e2e2ff; margin-top: 6px; text-align: center; }
  </style>
</head>
<body>
  <div class="container">
    <div class="left">
      <div>
        <h1 id="welcome-text">
          <span class="welcome-navie">WELCOME TO </span>
          <span class="welcome-docsphere">DOCSPHERE</span>
        </h1>
        <p id="welcome-subtext">
          Empowering your health journey with innovation and care.
          Stay connected, stay healthy, and let <b>DocSphere</b> be your trusted companion.
        </p>
      </div>
      <div class="rocket">
        <div class="rocket-body"></div>
        <div class="rocket-fire"></div>
      </div>
    </div>

    <div class="right">
      <div class="login-card">
        <h2>USER LOGIN</h2>

        <?php if ($error): ?>
          <div class="alert error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php" autocomplete="off" novalidate>
          <div class="input-box">
            <span class="icon">👤</span>
            <input type="text" name="username" placeholder="Email or Username" required value="<?php echo $prefill; ?>">
          </div>

          <div class="input-box">
            <span class="icon">🔒</span>
            <input type="password" name="password" placeholder="Password" required>
          </div>

          <div class="options">
            <label class="remember"><input type="checkbox" name="remember" <?php echo (!empty($_COOKIE['rememberme']) ? 'checked' : ''); ?>> Remember</label>
            <a href="forgot_password.php">Forgot Password</a>
          </div>

          <button type="submit" class="btn">Login</button>

          <p class="signup">Don't have an account? <a href="signup.php" style="color:#fff;text-decoration:underline;">Sign Up</a></p>
          <div class="small-note">Logging in will redirect to your dashboard on success.</div>
        </form>
      </div>
    </div>
  </div>

  <script>
    window.addEventListener('DOMContentLoaded', () => {
      const h1 = document.getElementById('welcome-text');
      const p = document.getElementById('welcome-subtext');
      setTimeout(() => { h1.style.opacity = '1'; h1.style.transform = 'translateY(0)'; }, 200);
      setTimeout(() => { p.style.opacity = '1'; p.style.transform = 'translateY(0)'; }, 500);
    });
  </script>
</body>
</html>

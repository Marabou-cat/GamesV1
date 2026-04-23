<?php
session_start();

// 1. Read database credentials from ../config.ini
$configFile = '../config.ini';

if (!file_exists($configFile)) {
    die("<div style='color: red; padding: 20px; text-align: center; font-family: sans-serif;'>
            <strong>Error:</strong> The configuration file (../config.ini) is missing!
         </div>");
}

$lines = file($configFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if (count($lines) < 2) {
    die("<div style='color: red; padding: 20px; text-align: center; font-family: sans-serif;'>
            <strong>Error:</strong> config.ini must have the database username on line 1 and password on line 2.
         </div>");
}

$db_user = trim($lines[0]); // Line 1: Username
$db_pass = trim($lines[1]); // Line 2: Password
$db_name = 'schoolexams';
$db_host = 'localhost';

// 2. Connect to the database
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    die("<div style='color: red; padding: 20px; text-align: center; font-family: sans-serif;'>
            <strong>Database connection failed.</strong><br>
            Please check that your credentials in ../config.ini are correct and your database is running.
         </div>");
}

$message = '';

// 3. Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? ''; 

    // REGISTER LOGIC (Saves plaintext password as requested for the sketch)
    if ($action === 'register') {
        $stmt = $pdo->prepare("INSERT INTO login (username, password) VALUES (?, ?)");
        try {
            $stmt->execute([$username, $password]);
            $message = "<span style='color: green;'>Account created! You can now log in. Go check phpMyAdmin to see your plaintext password.</span>";
        } catch (PDOException $e) {
            $message = "<span style='color: red;'>Error: Username is already taken.</span>";
        }
    } 
    // LOGIN LOGIC (Checks plaintext password)
    elseif ($action === 'login') {
        // Look for the exact username and plaintext password match
        $stmt = $pdo->prepare("SELECT * FROM login WHERE username = ? AND password = ?");
        $stmt->execute([$username, $password]);
        $userData = $stmt->fetch();

        if ($userData) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
        } else {
            $message = "<span style='color: red;'>Invalid username or password!</span>";
        }
    }
}

// 4. Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login Skiss</title>
    <style>
        body { font-family: sans-serif; background-color: #f4f4f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 300px; text-align: center; }
        input { width: 90%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 10px; font-weight: bold; }
        button:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .logout-btn { display: inline-block; margin-top: 20px; color: red; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
        
        <h1 style="color: #007bff;">this is a skiss</h1>
        <p>Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>!</p>
        <p><small>Your plaintext password matched the database perfectly.</small></p>
        <a href="?logout=1" class="logout-btn">Log out</a>

    <?php else: ?>

        <h2>Login Sketch</h2>
        
        <?php if ($message): ?>
            <p><?= $message ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="login">
            <input type="text" name="username" placeholder="Username" required>
            <input type="text" name="password" placeholder="Password (Plaintext)" required>
            <button type="submit">Log In</button>
        </form>

        <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">

        <p style="font-size: 14px; margin-bottom: 5px;">Don't have an account?</p>
        <form method="POST">
            <input type="hidden" name="action" value="register">
            <input type="text" name="username" placeholder="New Username" required>
            <input type="text" name="password" placeholder="New Password" required>
            <button type="submit" class="btn-secondary">Register Account</button>
        </form>

    <?php endif; ?>
</div>

</body>
</html>

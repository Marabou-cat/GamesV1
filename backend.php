<?php
session_start();
header('Content-Type: application/json');

// --- READ CONFIG FILE ---
$config_file = 'config.ini'; 

if (!file_exists($config_file)) {
    die(json_encode(["success" => false, "message" => "Server Error: Configuration file missing."]));
}

$lines = file($config_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if (count($lines) < 2) {
    die(json_encode(["success" => false, "message" => "Server Error: Invalid configuration file format."]));
}

$db_host = 'localhost';
$db_name = 'schoolexams';
$db_user = trim($lines[0]); 
$db_pass = trim($lines[1]); 

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["success" => false, "message" => "Database connection failed."]));
}

$action = $_POST['action'] ?? '';

// --- REGISTER ---
if ($action === 'register') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (strlen($username) < 3 || strlen($password) < 4) {
        echo json_encode(["success" => false, "message" => "Username >= 3 chars, Password >= 4 chars."]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Username already exists!"]);
        exit;
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, last_online) VALUES (?, ?, ?)");
    if ($stmt->execute([$username, $hashed, time() * 1000])) {
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['username'] = $username;
        echo json_encode(["success" => true, "message" => "Registration successful!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error creating account."]);
    }
    exit;
}

// --- LOGIN ---
if ($action === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        echo json_encode(["success" => true, "data" => $user]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid username or password."]);
    }
    exit;
}

// --- LOAD DATA ---
if ($action === 'load') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["success" => false, "message" => "Not logged in."]);
        exit;
    }

    // UPDATED: Added prestige_level and profile_pic
    $stmt = $pdo->prepare("SELECT coins, gems, playtime, owned_cursors, equipped_cursor, owned_pets, active_pet, pet_ages, last_online, sakura_coins, event_tasks, owned_chests, prestige_level, profile_pic FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(["success" => true, "data" => $data]);
    exit;
}

// --- SAVE DATA ---
if ($action === 'save') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["success" => false, "message" => "Not logged in."]);
        exit;
    }

    $coins = (int)$_POST['coins'];
    $gems = (int)$_POST['gems'];
    $playtime = (int)$_POST['playtime'];
    $owned_cursors = $_POST['owned_cursors'];
    $equipped_cursor = $_POST['equipped_cursor'];
    $owned_pets = $_POST['owned_pets'];
    $active_pet = $_POST['active_pet'];
    $pet_ages = $_POST['pet_ages'];
    $last_online = time() * 1000;
    
    $sakura_coins = (int)($_POST['sakura_coins'] ?? 0);
    $event_tasks = $_POST['event_tasks'] ?? '[]';
    $owned_chests = $_POST['owned_chests'] ?? '{}';
    
    // NEW PRESTIGE & PFP VARIABLES
    $prestige_level = (int)($_POST['prestige_level'] ?? 0);
    $profile_pic = $_POST['profile_pic'] ?? '';

    // UPDATED: Added prestige_level and profile_pic to the SQL
    $stmt = $pdo->prepare("UPDATE users SET coins=?, gems=?, playtime=?, owned_cursors=?, equipped_cursor=?, owned_pets=?, active_pet=?, pet_ages=?, last_online=?, sakura_coins=?, event_tasks=?, owned_chests=?, prestige_level=?, profile_pic=? WHERE id=?");
    $stmt->execute([
        $coins, $gems, $playtime, $owned_cursors, $equipped_cursor, $owned_pets, $active_pet, $pet_ages, $last_online, $sakura_coins, $event_tasks, $owned_chests, $prestige_level, $profile_pic, $_SESSION['user_id']
    ]);

    echo json_encode(["success" => true]);
    exit;
}

// --- GET LEADERBOARD ---
if ($action === 'get_leaderboard') {
    $stmt = $pdo->query("SELECT username, gems FROM users ORDER BY CAST(gems AS UNSIGNED) DESC LIMIT 100");
    $leaderboardData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $leaderboardData]);
    exit;
}

echo json_encode(["success" => false, "message" => "Invalid action."]);
?>

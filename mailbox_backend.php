<?php
session_start();
header('Content-Type: application/json');

$config_file = 'config.ini';
if (!file_exists($config_file)) die(json_encode(["success" => false, "message" => "Config missing."]));

$lines = file($config_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$db_user = trim($lines[0]); $db_pass = trim($lines[1]);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=schoolexams;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["success" => false, "message" => "Database connection failed."]));
}

if (!isset($_SESSION['user_id'])) die(json_encode(["success" => false, "message" => "Not logged in."]));

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$is_admin = ($username === 'Furry_Myrg'); // <-- Admin Check!

// --- LOAD MAILBOX ---
if ($action === 'load') {
    // Get user's claimed mail list
    $stmt = $pdo->prepare("SELECT claimed_mail, coins, gems FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $claimed = json_decode($user['claimed_mail'], true) ?: [];

    // Get all global mail
    $stmt = $pdo->query("SELECT * FROM global_mail ORDER BY created_at DESC");
    $mails = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "is_admin" => $is_admin,
        "coins" => (int)$user['coins'],
        "gems" => (int)$user['gems'],
        "claimed" => $claimed,
        "mails" => $mails
    ]);
    exit;
}

// --- SEND GLOBAL MAIL (ADMIN ONLY) ---
if ($action === 'send_mail') {
    if (!$is_admin) die(json_encode(["success" => false, "message" => "Unauthorized. Admin only."]));

    $title = trim($_POST['title'] ?? 'New Message');
    $message = trim($_POST['message'] ?? '');
    $reward_type = $_POST['reward_type'] ?? 'none';
    $reward_amount = (int)($_POST['reward_amount'] ?? 0);

    if (empty($message)) die(json_encode(["success" => false, "message" => "Message cannot be empty."]));

    $stmt = $pdo->prepare("INSERT INTO global_mail (sender, title, message, reward_type, reward_amount) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['Furry_Myrg', $title, $message, $reward_type, $reward_amount]);

    echo json_encode(["success" => true, "message" => "Global mail sent successfully!"]);
    exit;
}

// --- CLAIM REWARD ---
if ($action === 'claim') {
    $mail_id = (int)$_POST['mail_id'];

    try {
        $pdo->beginTransaction();

        // 1. Lock user row and get data
        $stmt = $pdo->prepare("SELECT coins, gems, claimed_mail FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $claimed = json_decode($user['claimed_mail'], true) ?: [];

        // 2. Check if already claimed
        if (in_array($mail_id, $claimed)) throw new Exception("You already claimed this reward!");

        // 3. Get the mail data
        $stmt = $pdo->prepare("SELECT * FROM global_mail WHERE id = ?");
        $stmt->execute([$mail_id]);
        $mail = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$mail) throw new Exception("Mail not found.");

        // 4. Apply Rewards
        $new_coins = (int)$user['coins'];
        $new_gems = (int)$user['gems'];

        if ($mail['reward_type'] === 'coins') $new_coins += (int)$mail['reward_amount'];
        if ($mail['reward_type'] === 'gems') $new_gems += (int)$mail['reward_amount'];

        // 5. Save claim record
        $claimed[] = $mail_id;

        $stmt = $pdo->prepare("UPDATE users SET coins = ?, gems = ?, claimed_mail = ? WHERE id = ?");
        $stmt->execute([$new_coins, $new_gems, json_encode($claimed), $user_id]);

        $pdo->commit();
        echo json_encode([
            "success" => true, 
            "message" => "Reward Claimed!", 
            "coins" => $new_coins, 
            "gems" => $new_gems,
            "claimed" => $claimed
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
    exit;
}
?>

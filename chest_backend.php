<?php
session_start();
header('Content-Type: application/json');

// --- READ CONFIG FILE ---
$config_file = 'config.ini';

if (!file_exists($config_file)) {
    die(json_encode(["success" => false, "message" => "Server Error: Configuration missing."]));
}

$lines = file($config_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$db_user = trim($lines[0]);
$db_pass = trim($lines[1]);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=schoolexams;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["success" => false, "message" => "Database connection failed."]));
}

if (!isset($_SESSION['user_id'])) die(json_encode(["success" => false, "message" => "Not logged in."]));

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

// --- DEFINE CHESTS & LOOT TABLES ---
$CHESTS = [
    "basic" => ["price" => 1000, "currency" => "coins"],
    "premium" => ["price" => 100, "currency" => "gems"]
];

// Helper to get user data
function getUser($pdo, $uid) {
    $stmt = $pdo->prepare("SELECT coins, gems, owned_chests, owned_cursors, owned_pets FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- LOAD INVENTORY ---
if ($action === 'load') {
    $user = getUser($pdo, $user_id);
    $chests = json_decode($user['owned_chests'], true) ?: ["basic" => 0, "premium" => 0];
    echo json_encode([
        "success" => true, 
        "coins" => (int)$user['coins'], 
        "gems" => (int)$user['gems'], 
        "chests" => $chests
    ]);
    exit;
}

// --- BUY CHEST ---
if ($action === 'buy') {
    $type = $_POST['chest_type'] ?? '';
    if (!isset($CHESTS[$type])) die(json_encode(["success" => false, "message" => "Invalid chest."]));

    try {
        $pdo->beginTransaction();
        $user = getUser($pdo, $user_id);
        
        $price = $CHESTS[$type]['price'];
        $currency = $CHESTS[$type]['currency'];
        $current_balance = (int)$user[$currency];

        if ($current_balance < $price) {
            throw new Exception("Not enough $currency!");
        }

        // Deduct currency
        $new_balance = $current_balance - $price;
        
        // Add Chest
        $chests = json_decode($user['owned_chests'], true) ?: ["basic" => 0, "premium" => 0];
        $chests[$type] = ($chests[$type] ?? 0) + 1;

        $stmt = $pdo->prepare("UPDATE users SET $currency = ?, owned_chests = ? WHERE id = ?");
        $stmt->execute([$new_balance, json_encode($chests), $user_id]);
        
        $pdo->commit();
        echo json_encode(["success" => true, "new_balance" => $new_balance, "currency" => $currency, "chests" => $chests]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
    exit;
}

// --- OPEN CHEST ---
if ($action === 'open') {
    $type = $_POST['chest_type'] ?? '';
    
    try {
        $pdo->beginTransaction();
        
        // Lock row for update
        $stmt = $pdo->prepare("SELECT owned_chests, owned_cursors, owned_pets FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $chests = json_decode($user['owned_chests'], true) ?: ["basic" => 0, "premium" => 0];
        
        if (!isset($chests[$type]) || $chests[$type] <= 0) {
            throw new Exception("You don't own any of these chests!");
        }
        
        // Remove 1 chest
        $chests[$type] -= 1;
        
        // --- RNG LOOT LOGIC ---
        $roll = mt_rand(1, 100);
        $reward_type = '';
        $reward_id = '';
        $reward_name = '';
        
        if ($type === 'basic') {
            // 70% chance for a basic cursor, 30% for a basic pet
            if ($roll <= 70) {
                $pool = ['m1' => 'Egg Twins', 'm2' => 'Gold Ingot', 'm3' => 'Cheesy Cursor', 'm4' => 'Sword Cursor', 'm5' => 'Pizza Slice'];
                $reward_type = 'cursor';
            } else {
                $pool = ['doge' => 'Pixel Doge', 'cat' => 'Cyber Kitty'];
                $reward_type = 'pet';
            }
        } else if ($type === 'premium') {
            // Premium: 50% Rare Pet, 40% Rare Cursor, 10% Mythic
            if ($roll <= 50) {
                $pool = ['frog' => 'Ninja Frog', 'panda' => 'Ghost Panda'];
                $reward_type = 'pet';
            } else if ($roll <= 90) {
                $pool = ['m6' => 'Sign Of Greed', 'prism' => 'Prism Wing'];
                $reward_type = 'cursor';
            } else {
                $pool = ['dragon' => 'Mythic Dragon (Cursor)', 'phoenix' => 'Mythic Phoenix (Pet)'];
                $reward_type = 'mythic';
            }
        }

        // Pick random item from selected pool
        $keys = array_keys($pool);
        $reward_id = $keys[array_rand($keys)];
        $reward_name = $pool[$reward_id];
        
        // Fix category for mythics
        if ($reward_type === 'mythic') {
            $reward_type = ($reward_id === 'dragon') ? 'cursor' : 'pet';
        }

        // Add to inventory
        $inv_column = ($reward_type === 'cursor') ? 'owned_cursors' : 'owned_pets';
        $inventory = json_decode($user[$inv_column], true) ?: [];
        
        $is_duplicate = in_array($reward_id, $inventory);
        if (!$is_duplicate) {
            $inventory[] = $reward_id;
        }

        // Save everything
        $stmt = $pdo->prepare("UPDATE users SET owned_chests = ?, $inv_column = ? WHERE id = ?");
        $stmt->execute([json_encode($chests), json_encode(array_values($inventory)), $user_id]);
        
        $pdo->commit();
        echo json_encode([
            "success" => true, 
            "chests" => $chests, 
            "reward" => [
                "id" => $reward_id,
                "name" => $reward_name,
                "type" => $reward_type,
                "is_duplicate" => $is_duplicate
            ]
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
    exit;
}
?>

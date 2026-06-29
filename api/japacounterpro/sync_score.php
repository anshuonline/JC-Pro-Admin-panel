<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config.php';

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (!$data || !isset($data['username'])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit();
}

$username = $conn->real_escape_string($data['username']);
$level = isset($data['level']) ? intval($data['level']) : 0;
$device_token = isset($data['device_token']) ? $conn->real_escape_string($data['device_token']) : null;
$is_private = isset($data['is_private']) && $data['is_private'] ? 1 : 0;

// Ensure is_private column exists
$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'is_private'");
if ($check_column && $check_column->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN is_private TINYINT(1) DEFAULT 0");
}

// Ensure ip_address column exists
$check_column_ip = $conn->query("SHOW COLUMNS FROM users LIKE 'ip_address'");
if ($check_column_ip && $check_column_ip->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN ip_address VARCHAR(45) NULL");
}

// Ensure has_gift column exists
$check_column_gift = $conn->query("SHOW COLUMNS FROM users LIKE 'has_gift'");
if ($check_column_gift && $check_column_gift->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN has_gift TINYINT(1) DEFAULT 0");
}

$user_ip = isset($_SERVER['REMOTE_ADDR']) ? $conn->real_escape_string($_SERVER['REMOTE_ADDR']) : null;

// Calendar sessions = SOURCE OF TRUTH from the app
// Each session = { "date": "2026-06-22", "count": 109 }
$sessions = isset($data['sessions']) ? $data['sessions'] : [];

// Find user
$user_res = $conn->query("SELECT id FROM users WHERE username = '$username' LIMIT 1");
if (!$user_res || $user_res->num_rows == 0) {
    // Auto-register user if missing
    $conn->query("INSERT IGNORE INTO users (username, device_token, level, total_counts, is_bot, ads_disabled, is_private, ip_address) VALUES ('$username', '$device_token', $level, 0, 0, 0, $is_private, '$user_ip')");
    $user_res = $conn->query("SELECT id FROM users WHERE username = '$username' LIMIT 1");
    if (!$user_res || $user_res->num_rows == 0) {
        echo json_encode(["success" => false, "message" => "User not found and could not be auto-registered"]);
        exit();
    }
}

$user_row = $user_res->fetch_assoc();
$user_id = $user_row['id'];

// Step 1: Update user level & device token (NOT total_counts — we don't trust client total)
$updates = "level = $level, ip_address = '$user_ip'";
if (isset($data['is_private'])) {
    $updates .= ", is_private = $is_private";
}
if ($device_token) {
    $updates .= ", device_token = '$device_token'";
}
$conn->query("UPDATE users SET $updates WHERE id = $user_id");

// Step 2: Calendar Sync — Merge safely to prevent wiping data on app reinstall
if (!empty($sessions)) {
    // We use GREATEST() to ensure that if the app is cleared and sends a smaller count, 
    // the server preserves its higher count.
    $stmt = $conn->prepare("INSERT INTO daily_counts (user_id, date, daily_count) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE daily_count = GREATEST(daily_count, VALUES(daily_count))");
    foreach ($sessions as $session) {
        if (isset($session['date']) && isset($session['count'])) {
            $date = $conn->real_escape_string($session['date']);
            $count = intval($session['count']);
            if ($count > 0) {
                $stmt->bind_param("isi", $user_id, $date, $count);
                $stmt->execute();
            }
        }
    }
    $stmt->close();
    
    // Update total_counts to match the safe merged server SUM
    $sum_res = $conn->query("SELECT SUM(daily_count) as t FROM daily_counts WHERE user_id = $user_id");
    $total = 0;
    if ($sum_res && $row = $sum_res->fetch_assoc()) {
        $total = (int)$row['t'];
    }
    $conn->query("UPDATE users SET total_counts = $total WHERE id = $user_id");
}

$is_premium = false;
$has_gift = false;
$premium_res = $conn->query("SELECT is_premium, has_gift FROM users WHERE id = $user_id");
if ($premium_res && $p_row = $premium_res->fetch_assoc()) {
    $is_premium = (bool)$p_row['is_premium'];
    $has_gift = (bool)$p_row['has_gift'];
}

echo json_encode(["success" => true, "message" => "Calendar synced", "is_premium" => $is_premium, "has_gift" => $has_gift]);
?>

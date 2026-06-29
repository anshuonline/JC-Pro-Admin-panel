<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['username']) || !isset($data['purchase_token'])) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

$username = $conn->real_escape_string($data['username']);
$purchase_token = $conn->real_escape_string($data['purchase_token']);

if (empty($purchase_token)) {
    echo json_encode(["success" => false, "message" => "Invalid purchase token"]);
    exit;
}

// Check if is_premium column exists
$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'is_premium'");
if ($check_column && $check_column->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN is_premium TINYINT(1) DEFAULT 0");
    $conn->query("ALTER TABLE users ADD COLUMN premium_since DATETIME DEFAULT NULL");
}

// Check if purchase_token column exists
$check_column2 = $conn->query("SHOW COLUMNS FROM users LIKE 'purchase_token'");
if ($check_column2 && $check_column2->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN purchase_token VARCHAR(255) UNIQUE DEFAULT NULL");
}

$stmt = $conn->prepare("UPDATE users SET is_premium = 1, premium_since = NOW() WHERE username = ?");
$stmt->bind_param("s", $username);

if ($stmt->execute()) {
    if ($stmt->affected_rows == 0) {
        $check = $conn->query("SELECT id FROM users WHERE username = '$username'");
        if ($check->num_rows == 0) {
             $conn->query("INSERT IGNORE INTO users (username, is_premium, premium_since) VALUES ('$username', 1, NOW())");
        }
    }
    echo json_encode(["success" => true, "message" => "User upgraded to premium"]);
} else {
    echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
}
?>

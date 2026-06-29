<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['username'])) {
    echo json_encode(["success" => false, "message" => "Missing username"]);
    exit;
}

$username = $conn->real_escape_string($data['username']);

// Check if is_premium column exists
$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'is_premium'");
if ($check_column && $check_column->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN is_premium TINYINT(1) DEFAULT 0");
    $conn->query("ALTER TABLE users ADD COLUMN premium_since DATETIME DEFAULT NULL");
}

$stmt = $conn->prepare("UPDATE users SET is_premium = 1, premium_since = NOW() WHERE username = ?");
$stmt->bind_param("s", $username);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "User upgraded to premium"]);
} else {
    echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
}
?>

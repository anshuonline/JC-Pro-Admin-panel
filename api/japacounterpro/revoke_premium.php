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

$stmt = $conn->prepare("UPDATE users SET is_premium = 0, premium_since = NULL WHERE username = ?");
$stmt->bind_param("s", $username);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Premium revoked"]);
} else {
    echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
}
?>

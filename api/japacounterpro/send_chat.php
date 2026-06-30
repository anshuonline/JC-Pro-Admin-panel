<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['google_uid']) || !isset($data['message'])) {
    echo json_encode(["success" => false, "message" => "Missing google_uid or message"]);
    exit;
}

$google_uid = $conn->real_escape_string($data['google_uid']);
$message = trim($data['message']);

if (empty($message)) {
    echo json_encode(["success" => false, "message" => "Message cannot be empty"]);
    exit;
}

if (strlen($message) > 1000) {
    echo json_encode(["success" => false, "message" => "Message is too long"]);
    exit;
}

// Check if user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE google_uid = ?");
$stmt->bind_param("s", $google_uid);
$stmt->execute();
if ($stmt->get_result()->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "User not found. Please log in again."]);
    exit;
}

// Insert message
$insert = $conn->prepare("INSERT INTO global_chat (google_uid, message) VALUES (?, ?)");
$insert->bind_param("ss", $google_uid, $message);

if ($insert->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to send message: " . $conn->error]);
}
?>

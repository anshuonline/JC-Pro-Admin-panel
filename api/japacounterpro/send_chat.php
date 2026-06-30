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

// Check if user exists and if they are banned
$stmt = $conn->prepare("SELECT id, is_chat_banned FROM users WHERE google_uid = ?");
$stmt->bind_param("s", $google_uid);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "User not found. Please log in again."]);
    exit;
}

$user = $res->fetch_assoc();
if ($user['is_chat_banned']) {
    echo json_encode(["success" => false, "message" => "You have been banned from global chat."]);
    exit;
}

// Fetch banned words and filter message
$banned_res = $conn->query("SELECT banned_words FROM app_settings LIMIT 1");
if ($banned_res && $banned_res->num_rows > 0) {
    $settings = $banned_res->fetch_assoc();
    $banned_words_str = trim($settings['banned_words']);
    if (!empty($banned_words_str)) {
        $words = array_map('trim', explode(',', $banned_words_str));
        foreach ($words as $word) {
            if (!empty($word)) {
                // Case-insensitive replacement with ***
                $pattern = '/' . preg_quote($word, '/') . '/i';
                $message = preg_replace($pattern, '***', $message);
            }
        }
    }
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

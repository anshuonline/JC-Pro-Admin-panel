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

$reply_to_id = isset($data['reply_to_id']) && is_numeric($data['reply_to_id']) ? (int)$data['reply_to_id'] : null;

// Insert message
if ($reply_to_id !== null) {
    $insert = $conn->prepare("INSERT INTO global_chat (google_uid, message, reply_to_id) VALUES (?, ?, ?)");
    $insert->bind_param("ssi", $google_uid, $message, $reply_to_id);
} else {
    $insert = $conn->prepare("INSERT INTO global_chat (google_uid, message) VALUES (?, ?)");
    $insert->bind_param("ss", $google_uid, $message);
}

if ($insert->execute()) {
    // Send FCM Notification if it's a reply
    if ($reply_to_id !== null) {
        $get_original_user = $conn->prepare("
            SELECT u.device_token, c.message 
            FROM global_chat c 
            JOIN users u ON c.google_uid COLLATE utf8mb4_unicode_ci = u.google_uid COLLATE utf8mb4_unicode_ci
            WHERE c.id = ?
        ");
        $get_original_user->bind_param("i", $reply_to_id);
        $get_original_user->execute();
        $res_user = $get_original_user->get_result();
        
        if ($res_user->num_rows > 0) {
            $row = $res_user->fetch_assoc();
            $device_token = $row['device_token'];
            if (!empty($device_token)) {
                $snippet = strlen($row['message']) > 20 ? substr($row['message'], 0, 20) . '...' : $row['message'];
                $title = $user['username'] . " replied to you";
                $body = "Global Chat: " . $message;
                send_fcm_notification($device_token, $title, $body);
            }
        }
    }

    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to send message: " . $conn->error]);
}
?>

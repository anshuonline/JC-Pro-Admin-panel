<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config.php';
require_once 'bot_config.php';

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
$stmt = $conn->prepare("SELECT id, is_chat_banned, chat_muted_until FROM users WHERE google_uid = ?");
$stmt->bind_param("s", $google_uid);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "User not found. Please log in again."]);
    exit;
}

$user = $res->fetch_assoc();
if ($user['is_chat_banned']) {
    echo json_encode(["success" => false, "message" => "You have been permanently banned from global chat."]);
    exit;
}

if (!empty($user['chat_muted_until'])) {
    $muted_until = strtotime($user['chat_muted_until']);
    if ($muted_until > time()) {
        $remaining = ceil(($muted_until - time()) / 60);
        $time_str = $remaining > 60 ? ceil($remaining/60) . " hours" : $remaining . " mins";
        echo json_encode(["success" => false, "message" => "You are muted from chat. Expires in $time_str."]);
        exit;
    }
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


    // Process Mentions
    preg_match_all('/@([a-zA-Z0-9_]+)/', $message, $matches);
    if (!empty($matches[1])) {
        $mentioned_usernames = array_unique($matches[1]);
        foreach ($mentioned_usernames as $m_user) {
            $m_user_safe = $conn->real_escape_string($m_user);
            
            if (strtolower($m_user_safe) === strtolower($user['username'])) continue;
            
            $get_m = $conn->query("SELECT device_token FROM users WHERE username = '$m_user_safe'");
            if ($get_m && $get_m->num_rows > 0) {
                $m_row = $get_m->fetch_assoc();
                $device_token = $m_row['device_token'];
                if (!empty($device_token)) {
                    $title = $user['username'] . " mentioned you";
                    $body = "Global Chat: " . $message;
                    send_fcm_notification($device_token, $title, $body);
                }
            }
        }
    }

    // Process Gemini Bot Mentions
    $bot_mention = '@' . BOT_USERNAME;
    if (stripos($message, $bot_mention) !== false) {
        $clean_msg = trim(str_ireplace($bot_mention, '', $message));
        if (empty($clean_msg)) $clean_msg = "Hello";
        
        $prompt = "You are " . BOT_USERNAME . ", a friendly spiritual guide in a meditation app (JapaCounter). You reply in Hinglish or English. Keep your responses short (max 2 sentences), funny, and helpful. User " . $user['username'] . " says: " . $clean_msg;
        
        $gemini_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . GEMINI_API_KEY;
        $postData = json_encode([
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ]
        ]);
        
        $ch = curl_init($gemini_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6); // Max 6 seconds
        $api_response = curl_exec($ch);
        curl_close($ch);
        
        if ($api_response) {
            $json_res = json_decode($api_response, true);
            if (isset($json_res['candidates'][0]['content']['parts'][0]['text'])) {
                $bot_reply = trim($json_res['candidates'][0]['content']['parts'][0]['text']);
                // Clean markdown formatting to look normal in chat
                $bot_reply = str_replace(['**', '*'], '', $bot_reply);
                
                $bot_uid = $conn->real_escape_string(BOT_GOOGLE_UID);
                $bot_reply_safe = $conn->real_escape_string($bot_reply);
                $new_msg_id = $insert->insert_id;
                
                $conn->query("INSERT INTO global_chat (google_uid, message, reply_to_id) VALUES ('$bot_uid', '$bot_reply_safe', $new_msg_id)");
            }
        }
    }

    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to send message: " . $conn->error]);
}
?>

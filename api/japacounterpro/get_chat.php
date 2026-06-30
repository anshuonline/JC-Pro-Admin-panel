<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config.php';

// Fetch last 100 messages
$sql = "SELECT c.id, c.message, c.created_at, c.google_uid, c.reply_to_id, 
               u.username, u.profile_picture, u.level, u.is_premium, u.is_mod,
               rc.message AS reply_message, ru.username AS reply_username
        FROM global_chat c
        JOIN users u ON c.google_uid COLLATE utf8mb4_unicode_ci = u.google_uid COLLATE utf8mb4_unicode_ci
        LEFT JOIN global_chat rc ON c.reply_to_id = rc.id
        LEFT JOIN users ru ON rc.google_uid COLLATE utf8mb4_unicode_ci = ru.google_uid COLLATE utf8mb4_unicode_ci
        ORDER BY c.id DESC LIMIT 20";
        
$res = $conn->query($sql);
if (!$res) {
    echo json_encode([
        "success" => false,
        "message" => "Database Error: " . $conn->error
    ]);
    exit;
}

$messages = [];

if ($res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        $messages[] = [
            "id" => (int)$row['id'],
            "username" => $row['username'],
            "google_uid" => $row['google_uid'],
            "message" => $row['message'],
            "profile_url" => $row['profile_picture'],
            "level" => (int)$row['level'],
            "is_premium" => (bool)$row['is_premium'],
            "is_mod" => (bool)($row['is_mod'] ?? false),
            "reply_to_id" => $row['reply_to_id'] ? (int)$row['reply_to_id'] : null,
            "reply_message" => $row['reply_message'] ? $row['reply_message'] : null,
            "reply_username" => $row['reply_username'] ? $row['reply_username'] : null,
            "timestamp" => strtotime($row['created_at']) * 1000 // Send in ms for Android
        ];
    }
}

// Reverse the array so it is ordered by oldest to newest for UI
$messages = array_reverse($messages);

echo json_encode([
    "success" => true,
    "data" => $messages
]);
?>

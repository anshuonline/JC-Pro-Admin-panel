<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config.php';

$user_status = null;
if (isset($_GET['uid']) && !empty($_GET['uid'])) {
    $uid = $conn->real_escape_string($_GET['uid']);
    $userRes = $conn->query("SELECT is_chat_banned, chat_muted_until FROM users WHERE google_uid = '$uid'");
    if ($userRes && $userRes->num_rows > 0) {
        $userRow = $userRes->fetch_assoc();
        $is_banned = (bool)$userRow['is_chat_banned'];
        $muted_until = $userRow['chat_muted_until'];
        
        if (!empty($muted_until) && strtotime($muted_until) <= time()) {
            $muted_until = null;
        }
        
        $user_status = [
            "is_banned" => $is_banned,
            "muted_until" => $muted_until ? strtotime($muted_until) * 1000 : null
        ];
    }
}

// Fetch last 100 messages
$sql = "SELECT c.id, c.message, c.created_at, c.google_uid, c.reply_to_id, 
               u.username, u.profile_picture, u.level, u.is_premium, u.is_mod,
               rc.message AS reply_message, ru.username AS reply_username
        FROM global_chat c
        JOIN users u ON c.google_uid COLLATE utf8mb4_unicode_ci = u.google_uid COLLATE utf8mb4_unicode_ci
        LEFT JOIN global_chat rc ON c.reply_to_id = rc.id
        LEFT JOIN users ru ON rc.google_uid COLLATE utf8mb4_unicode_ci = ru.google_uid COLLATE utf8mb4_unicode_ci
        ORDER BY c.id DESC LIMIT 100";
        
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

$response = [
    "success" => true,
    "data" => $messages
];

if ($user_status !== null) {
    $response["user_status"] = $user_status;
}

echo json_encode($response);
?>

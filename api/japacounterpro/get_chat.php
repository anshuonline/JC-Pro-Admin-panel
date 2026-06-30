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

// Fetch last 50 messages
$before_id = isset($_GET['before_id']) && is_numeric($_GET['before_id']) ? (int)$_GET['before_id'] : null;

$where_clause = "";
if ($before_id !== null) {
    $where_clause = "WHERE c.id < $before_id";
}

$sql = "SELECT c.id, c.message, UNIX_TIMESTAMP(c.created_at) as created_ts, c.google_uid, c.reply_to_id, 
               u.username, u.profile_picture, u.level, u.is_premium, u.is_mod,
               rc.message AS reply_message, ru.username AS reply_username
        FROM global_chat c
        JOIN users u ON c.google_uid COLLATE utf8mb4_unicode_ci = u.google_uid COLLATE utf8mb4_unicode_ci
        LEFT JOIN global_chat rc ON c.reply_to_id = rc.id
        LEFT JOIN users ru ON rc.google_uid COLLATE utf8mb4_unicode_ci = ru.google_uid COLLATE utf8mb4_unicode_ci
        $where_clause
        ORDER BY c.id DESC LIMIT 50";
        
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
            "timestamp" => (int)$row['created_ts'] * 1000 // Send in ms for Android
        ];
    }
}

// Fetch reactions for the loaded messages
if (!empty($messages)) {
    $msg_ids = array_map(function($m) { return $m['id']; }, $messages);
    $ids_str = implode(',', $msg_ids);
    
    $react_res = $conn->query("
        SELECT r.message_id, r.emoji, u.username 
        FROM message_reactions r 
        JOIN users u ON r.google_uid COLLATE utf8mb4_unicode_ci = u.google_uid COLLATE utf8mb4_unicode_ci 
        WHERE r.message_id IN ($ids_str)
    ");
    
    $reactions_map = [];
    if ($react_res && $react_res->num_rows > 0) {
        while ($r = $react_res->fetch_assoc()) {
            $m_id = $r['message_id'];
            $emoji = $r['emoji'];
            $username = $r['username'];
            
            if (!isset($reactions_map[$m_id])) {
                $reactions_map[$m_id] = [];
            }
            if (!isset($reactions_map[$m_id][$emoji])) {
                $reactions_map[$m_id][$emoji] = [];
            }
            $reactions_map[$m_id][$emoji][] = $username;
        }
    }
    
    foreach ($messages as &$m) {
        $m_id = $m['id'];
        $m['reactions'] = isset($reactions_map[$m_id]) ? (object)$reactions_map[$m_id] : new stdClass();
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

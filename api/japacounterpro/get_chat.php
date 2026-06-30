<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config.php';

// Fetch last 100 messages
$sql = "SELECT c.id, c.message, c.created_at, u.username, u.profile_picture, u.level, u.is_premium 
        FROM global_chat c
        JOIN users u ON c.google_uid = u.google_uid
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
            "message" => $row['message'],
            "profile_url" => $row['profile_picture'],
            "level" => (int)$row['level'],
            "is_premium" => (bool)$row['is_premium'],
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

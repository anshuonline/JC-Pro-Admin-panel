<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config.php';

// Ensure global_chat table exists
$check_table = $conn->query("SHOW TABLES LIKE 'global_chat'");
if ($check_table && $check_table->num_rows == 0) {
    $create_table = "CREATE TABLE global_chat (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        google_uid VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX(created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $conn->query($create_table);
}

// Ensure banned_words column exists in app_settings
$check_banned_words = $conn->query("SHOW COLUMNS FROM app_settings LIKE 'banned_words'");
if ($check_banned_words && $check_banned_words->num_rows == 0) {
    $conn->query("ALTER TABLE app_settings ADD COLUMN banned_words TEXT DEFAULT ''");
}

// Ensure is_chat_banned column exists in users
$check_chat_ban = $conn->query("SHOW COLUMNS FROM users LIKE 'is_chat_banned'");
if ($check_chat_ban && $check_chat_ban->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN is_chat_banned TINYINT(1) DEFAULT 0");
}

// Auto-insert virtual Admin user for chat
$check_admin = $conn->prepare("SELECT id FROM users WHERE google_uid = 'admin_uid'");
$check_admin->execute();
if ($check_admin->get_result()->num_rows == 0) {
    $conn->query("INSERT INTO users (username, google_uid, level, is_premium, profile_picture) 
                  VALUES ('Admin', 'admin_uid', 99, 1, 'https://ui-avatars.com/api/?name=Admin&background=0D8ABC&color=fff&bold=true')");
}

// Fetch last 100 messages
$sql = "SELECT c.id, c.message, c.created_at, u.username, u.profile_picture, u.level, u.is_premium 
        FROM global_chat c
        JOIN users u ON c.google_uid = u.google_uid
        ORDER BY c.id DESC LIMIT 100";
        
$res = $conn->query($sql);
$messages = [];

if ($res && $res->num_rows > 0) {
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

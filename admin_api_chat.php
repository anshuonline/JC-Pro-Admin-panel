<?php
require_once 'config.php';
check_auth();

header('Content-Type: application/json');

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

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

switch ($action) {
    case 'get_chats':
        $sql = "SELECT c.id, c.message, c.created_at, c.google_uid, u.username, u.profile_picture, u.level, u.is_premium, u.is_chat_banned 
                FROM global_chat c
                JOIN users u ON c.google_uid = u.google_uid
                ORDER BY c.id DESC LIMIT 100";
        $res = $conn->query($sql);
        $chats = [];
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $chats[] = $row;
            }
        }
        echo json_encode(['success' => true, 'data' => $chats]);
        break;

    case 'send_chat':
        $message = trim($_POST['message'] ?? '');
        if (empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Empty message']);
            exit;
        }
        $admin_uid = 'admin_uid';
        $insert = $conn->prepare("INSERT INTO global_chat (google_uid, message) VALUES (?, ?)");
        $insert->bind_param("ss", $admin_uid, $message);
        if ($insert->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        break;

    case 'delete_chat':
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $conn->query("DELETE FROM global_chat WHERE id = $id");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        }
        break;

    case 'ban_user':
        $uid = $conn->real_escape_string($_POST['google_uid'] ?? '');
        if (!empty($uid) && $uid !== 'admin_uid') {
            $conn->query("UPDATE users SET is_chat_banned = NOT is_chat_banned WHERE google_uid = '$uid'");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid UID']);
        }
        break;

    case 'get_banned_words':
        $res = $conn->query("SELECT banned_words FROM app_settings LIMIT 1");
        $words = "";
        if ($res && $res->num_rows > 0) {
            $words = $res->fetch_assoc()['banned_words'];
        }
        echo json_encode(['success' => true, 'words' => $words]);
        break;

    case 'update_banned_words':
        $words = $conn->real_escape_string($_POST['words'] ?? '');
        $conn->query("UPDATE app_settings SET banned_words = '$words'");
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?>

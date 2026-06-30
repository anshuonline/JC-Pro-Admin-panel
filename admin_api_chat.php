<?php
require_once 'config.php';
check_auth();

header('Content-Type: application/json');

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

switch ($action) {
    case 'get_chats':
        $sql = "SELECT c.id, c.message, c.created_at, c.google_uid, u.username, u.profile_picture, u.level, u.is_premium, u.is_chat_banned 
                FROM global_chat c
                JOIN users u ON c.google_uid = u.google_uid
                ORDER BY c.id DESC LIMIT 100";
        $res = $conn->query($sql);
        if (!$res) {
            echo json_encode(['success' => false, 'message' => 'Query Failed: ' . $conn->error]);
            exit;
        }
        $chats = [];
        if ($res->num_rows > 0) {
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

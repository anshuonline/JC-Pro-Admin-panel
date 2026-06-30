<?php
require_once 'config.php';
check_auth();

header('Content-Type: application/json');

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

switch ($action) {
    case 'get_chats':
        $sql = "SELECT c.id, c.message, c.created_at, c.google_uid, c.reply_to_id,
                       u.username, u.profile_picture, u.level, u.is_premium, u.is_chat_banned, u.chat_muted_until,
                       rc.message as reply_message, ru.username as reply_username 
                FROM global_chat c
                JOIN users u ON c.google_uid COLLATE utf8mb4_unicode_ci = u.google_uid COLLATE utf8mb4_unicode_ci
                LEFT JOIN global_chat rc ON c.reply_to_id = rc.id
                LEFT JOIN users ru ON rc.google_uid COLLATE utf8mb4_unicode_ci = ru.google_uid COLLATE utf8mb4_unicode_ci
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
        $reply_to_id = isset($_POST['reply_to_id']) && is_numeric($_POST['reply_to_id']) ? (int)$_POST['reply_to_id'] : null;
        
        if (empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Empty message']);
            exit;
        }
        $admin_uid = 'admin_uid';
        
        if ($reply_to_id !== null) {
            $insert = $conn->prepare("INSERT INTO global_chat (google_uid, message, reply_to_id) VALUES (?, ?, ?)");
            $insert->bind_param("ssi", $admin_uid, $message, $reply_to_id);
        } else {
            $insert = $conn->prepare("INSERT INTO global_chat (google_uid, message) VALUES (?, ?)");
            $insert->bind_param("ss", $admin_uid, $message);
        }
        
        if ($insert->execute()) {
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
                        $title = "Admin replied to you";
                        $body = "Global Chat: " . $message;
                        send_fcm_notification($device_token, $title, $body);
                    }
                }
            }
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
            $userRes = $conn->query("SELECT is_chat_banned, username FROM users WHERE google_uid = '$uid'");
            if ($userRes && $userRes->num_rows > 0) {
                $userRow = $userRes->fetch_assoc();
                $was_banned = $userRow['is_chat_banned'];
                $username = $userRow['username'];
                
                $conn->query("UPDATE users SET is_chat_banned = NOT is_chat_banned WHERE google_uid = '$uid'");
                
                if (!$was_banned) { // They just got banned
                    $sys_msg = $conn->real_escape_string("$username is permanently banned from global chat.");
                    $conn->query("INSERT INTO global_chat (google_uid, message) VALUES ('system', '$sys_msg')");
                }
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid UID']);
        }
        break;

    case 'mute_user':
        $uid = $conn->real_escape_string($_POST['google_uid'] ?? '');
        $duration = (int)($_POST['duration'] ?? 0); // duration in hours
        if (!empty($uid) && $uid !== 'admin_uid' && $duration > 0) {
            $userRes = $conn->query("SELECT username FROM users WHERE google_uid = '$uid'");
            if ($userRes && $userRes->num_rows > 0) {
                $username = $userRes->fetch_assoc()['username'];
                $muted_until = date('Y-m-d H:i:s', strtotime("+$duration hours"));
                
                $conn->query("UPDATE users SET chat_muted_until = '$muted_until' WHERE google_uid = '$uid'");
                
                $duration_str = $duration == 1 ? "1 hour" : ($duration == 24 ? "24 hours" : ($duration == 168 ? "7 days" : "$duration hours"));
                $sys_msg = $conn->real_escape_string("$username is muted from the chat for $duration_str.");
                $conn->query("INSERT INTO global_chat (google_uid, message) VALUES ('system', '$sys_msg')");
                
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        }
        break;

    case 'unmute_user':
        $uid = $conn->real_escape_string($_POST['google_uid'] ?? '');
        if (!empty($uid) && $uid !== 'admin_uid') {
            $userRes = $conn->query("SELECT username FROM users WHERE google_uid = '$uid'");
            if ($userRes && $userRes->num_rows > 0) {
                $username = $userRes->fetch_assoc()['username'];
                
                $conn->query("UPDATE users SET chat_muted_until = NULL WHERE google_uid = '$uid'");
                
                $sys_msg = $conn->real_escape_string("$username has been unmuted.");
                $conn->query("INSERT INTO global_chat (google_uid, message) VALUES ('system', '$sys_msg')");
                
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
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

    case 'get_restricted_users':
        $sql = "SELECT google_uid, username, profile_picture, is_chat_banned, chat_muted_until 
                FROM users 
                WHERE is_chat_banned = 1 OR chat_muted_until > NOW() 
                ORDER BY username ASC";
        $res = $conn->query($sql);
        $users = [];
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $users[] = $row;
            }
        }
        echo json_encode(['success' => true, 'data' => $users]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?>

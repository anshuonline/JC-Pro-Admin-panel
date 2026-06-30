<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config.php';

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!isset($data['google_uid']) || !isset($data['message_id']) || !isset($data['emoji'])) {
    echo json_encode(["success" => false, "message" => "Missing parameters"]);
    exit;
}

$google_uid = $conn->real_escape_string($data['google_uid']);
$message_id = (int)$data['message_id'];
$emoji = $conn->real_escape_string($data['emoji']);

// Validate user
$user_check = $conn->query("SELECT id FROM users WHERE google_uid = '$google_uid'");
if (!$user_check || $user_check->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit;
}

// Check if reaction already exists
$check_query = "SELECT id, emoji FROM message_reactions WHERE message_id = $message_id AND google_uid = '$google_uid'";
$res = $conn->query($check_query);

if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    if ($row['emoji'] === $emoji) {
        // User clicked same emoji, remove it (toggle off)
        $conn->query("DELETE FROM message_reactions WHERE id = " . $row['id']);
    } else {
        // User clicked a different emoji, update it
        $conn->query("UPDATE message_reactions SET emoji = '$emoji' WHERE id = " . $row['id']);
    }
} else {
    // Insert new reaction
    $conn->query("INSERT INTO message_reactions (message_id, google_uid, emoji) VALUES ($message_id, '$google_uid', '$emoji')");
}

echo json_encode(["success" => true]);
?>

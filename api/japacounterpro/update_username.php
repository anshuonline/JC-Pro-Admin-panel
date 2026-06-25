<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['old_username']) || !isset($data['new_username'])) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

$old_username = $conn->real_escape_string(trim($data['old_username']));
$new_username = $conn->real_escape_string(trim($data['new_username']));
$device_token = isset($data['device_token']) ? $conn->real_escape_string($data['device_token']) : '';
$google_uid = isset($data['google_uid']) ? $conn->real_escape_string($data['google_uid']) : '';

if (empty($new_username)) {
    echo json_encode(["success" => false, "message" => "New username cannot be empty"]);
    exit;
}

// 1. Check if new username is already taken
$check = $conn->query("SELECT id FROM users WHERE username = '$new_username' LIMIT 1");
if ($check && $check->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Username already taken"]);
    exit;
}

// 2. Find the user to update
if (empty($old_username)) {
    echo json_encode(["success" => true, "message" => "Username is available"]);
    exit;
}

$query = "SELECT id, google_uid, device_token FROM users WHERE username = '$old_username' LIMIT 1";
$res = $conn->query($query);

if ($res && $res->num_rows > 0) {
    $user = $res->fetch_assoc();
    $is_authorized = false;
    
    if (!empty($user['google_uid'])) {
        if ($user['google_uid'] === $google_uid) {
            $is_authorized = true;
        }
    } else {
        if ($user['device_token'] === $device_token) {
            $is_authorized = true;
        }
    }
    
    if ($is_authorized) {
        $conn->query("UPDATE users SET username = '$new_username' WHERE id = " . $user['id']);
        echo json_encode(["success" => true, "message" => "Username updated successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Unauthorized to change this username"]);
    }
} else {
    // Old username doesn't exist on server yet.
    echo json_encode(["success" => true, "message" => "Username available"]);
}

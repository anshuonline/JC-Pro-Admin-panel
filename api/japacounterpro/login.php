<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['google_uid']) || !isset($data['email'])) {
    echo json_encode(["success" => false, "message" => "Missing google_uid or email"]);
    exit;
}

$google_uid = $conn->real_escape_string($data['google_uid']);
$email = $conn->real_escape_string($data['email']);
$device_token = isset($data['device_token']) ? $conn->real_escape_string($data['device_token']) : '';
$profile_url = isset($data['profile_url']) ? $conn->real_escape_string($data['profile_url']) : null;

// Ensure profile_picture column exists
$check_column_pp = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_picture'");
if ($check_column_pp && $check_column_pp->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(500) DEFAULT NULL");
}

// Check if user exists by google_uid
$stmt = $conn->prepare("SELECT username, total_counts, level, is_premium, is_mod FROM users WHERE google_uid = ?");
$stmt->bind_param("s", $google_uid);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    // User exists
    $user = $res->fetch_assoc();
    
    // Update device token and profile picture if available
    if ($device_token !== '') {
        $update = $conn->prepare("UPDATE users SET device_token = ? WHERE google_uid = ?");
        $update->bind_param("ss", $device_token, $google_uid);
        $update->execute();
    }
    
    if ($profile_url !== null) {
        $update_pp = $conn->prepare("UPDATE users SET profile_picture = ? WHERE google_uid = ?");
        $update_pp->bind_param("ss", $profile_url, $google_uid);
        $update_pp->execute();
    }
    
    echo json_encode([
        "success" => true,
        "is_new_user" => false,
        "username" => $user['username'],
        "total_counts" => $user['total_counts'],
        "level" => $user['level'],
        "is_premium" => (bool)$user['is_premium'],
        "is_mod" => (bool)($user['is_mod'] ?? false)
    ]);
} else {
    // New Google user, auto-generate a username from email for now
    $base_username = explode('@', $email)[0];
    // Remove non-alphanumeric chars
    $base_username = preg_replace("/[^A-Za-z0-9]/", '', $base_username);
    $username = $base_username;
    
    // Ensure unique username
    $counter = 1;
    while (true) {
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        if ($check->get_result()->num_rows == 0) {
            break;
        }
        $username = $base_username . $counter;
        $counter++;
    }
    
    // Insert new user
    $insert = $conn->prepare("INSERT INTO users (username, google_uid, email, device_token, profile_picture) VALUES (?, ?, ?, ?, ?)");
    $insert->bind_param("sssss", $username, $google_uid, $email, $device_token, $profile_url);
    
    if ($insert->execute()) {
        echo json_encode([
            "success" => true,
            "is_new_user" => true,
            "username" => $username,
            "total_counts" => 0,
            "level" => 0,
            "is_premium" => false,
            "is_mod" => false
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
    }
}
?>

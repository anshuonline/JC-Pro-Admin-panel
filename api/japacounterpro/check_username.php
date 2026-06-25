<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

$username = isset($_GET['username']) ? trim($conn->real_escape_string($_GET['username'])) : '';
$device_token = isset($_GET['device_token']) ? $conn->real_escape_string($_GET['device_token']) : '';

if (empty($username)) {
    echo json_encode(["success" => false, "message" => "Username required"]);
    exit();
}

// Check if username already exists
$res = $conn->query("SELECT id FROM users WHERE username = '$username' LIMIT 1");

if ($res && $res->num_rows > 0) {
    // Username taken — return existing user's ID so app can log them in
    $row = $res->fetch_assoc();
    echo json_encode([
        "success" => true,
        "available" => false,
        "message" => "Username already taken",
        "user_id" => $row['id']
    ]);
} else {
    // Username available — CREATE the user now
    $stmt = $conn->prepare("INSERT INTO users (username, device_token, level, total_counts, is_bot, bot_mantra, ads_disabled, last_active, created_at) VALUES (?, ?, 1, 0, 0, 0, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
    $stmt->bind_param("ss", $username, $device_token);

    if ($stmt->execute()) {
        $new_id = $stmt->insert_id;
        $stmt->close();
        echo json_encode([
            "success" => true,
            "available" => true,
            "message" => "Username registered successfully",
            "user_id" => $new_id
        ]);
    } else {
        $stmt->close();
        echo json_encode([
            "success" => false,
            "available" => false,
            "message" => "DB error: " . $conn->error
        ]);
    }
}
?>

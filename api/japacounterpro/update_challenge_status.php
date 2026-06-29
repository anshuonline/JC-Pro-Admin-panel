<?php
// update_challenge_status.php
header('Content-Type: application/json');
require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username']) || !isset($data['challenge_id']) || !isset($data['status'])) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

$username = $conn->real_escape_string($data['username']);
$challenge_id = (int)$data['challenge_id'];
$status = $conn->real_escape_string($data['status']);
$device_token = isset($data['device_token']) ? $conn->real_escape_string($data['device_token']) : null;

// Validate status
$valid_statuses = ['active', 'failed', 'completed'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(["success" => false, "message" => "Invalid status. Must be active, failed, or completed."]);
    exit;
}

// Get user id and verify token using bind_result
$stmt = $conn->prepare("SELECT id, total_counts, device_token FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id, $user_total_counts, $db_token);
if (!$stmt->fetch()) {
    $user_id = null;
    $user_total_counts = 0;
    $db_token = null;
}
$stmt->close();

if (!$user_id) {
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit;
}

// Verify token if it exists in db
if ($db_token !== null && $db_token !== "") {
    if ($device_token !== $db_token) {
        echo json_encode(["success" => false, "message" => "Unauthorized device token. Cannot update challenges for this username."]);
        exit;
    }
} else {
    // Bind token if not exists
    if ($device_token !== null && $device_token !== "") {
        $conn->query("UPDATE users SET device_token = '$device_token' WHERE id = $user_id");
    }
}

// Normalize status values to match DB enum
if ($status === 'active') $status = 'accepted';
if ($status === 'failed') $status = 'rejected';

if ($status === 'accepted') {
    // When JOINING: Set progress = current total_counts so future diff is calculated correctly
    // This means: progress tracks ONLY counts done AFTER joining the challenge
    $sql = "INSERT INTO user_challenges (user_id, challenge_id, status, progress) 
            VALUES ($user_id, $challenge_id, 'accepted', 0)
            ON DUPLICATE KEY UPDATE status = 'accepted', progress = 0";
} else {
    // For other status changes (rejected/completed), just update status
    $sql = "INSERT INTO user_challenges (user_id, challenge_id, status, progress) 
            VALUES ($user_id, $challenge_id, '$status', 0)
            ON DUPLICATE KEY UPDATE status = '$status'";
}

if ($conn->query($sql) === TRUE) {
    echo json_encode(["success" => true, "message" => "Status updated to $status"]);
} else {
    echo json_encode(["success" => false, "message" => "Error: " . $conn->error]);
}

$conn->close();
?>

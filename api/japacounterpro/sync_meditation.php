<?php
// C:\xampp\htdocs\JC Pro Admin panel\api\japacounterpro\sync_meditation.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include config from two directories up
require_once '../../config.php';

// Get JSON raw POST data
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (!$data || !isset($data['username']) || !isset($data['sessions'])) {
    echo json_encode(["status" => "error", "message" => "Invalid request payload"]);
    exit();
}

$username = $conn->real_escape_string($data['username']);
$device_token = isset($data['device_token']) ? $conn->real_escape_string($data['device_token']) : '';

$user_res = $conn->query("SELECT id FROM users WHERE username = '$username' LIMIT 1");
if (!$user_res || $user_res->num_rows == 0) {
    // Auto-register user if missing
    $conn->query("INSERT IGNORE INTO users (username, device_token, level, total_counts, is_bot, ads_disabled) VALUES ('$username', '$device_token', 1, 0, 0, 0)");
    $user_res = $conn->query("SELECT id FROM users WHERE username = '$username' LIMIT 1");
    if (!$user_res || $user_res->num_rows == 0) {
        echo json_encode(["success" => false, "message" => "User not found and could not be auto-registered"]);
        exit();
    }
}

$sessions = $data['sessions']; // Array of {duration_seconds: int, date: string}

$successCount = 0;

foreach ($sessions as $session) {
    if (isset($session['duration_seconds']) && isset($session['date'])) {
        $duration = intval($session['duration_seconds']);
        $date = $conn->real_escape_string($session['date']);
        
        $stmt = $conn->prepare("INSERT INTO meditation_sessions (username, duration_seconds, date) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $username, $duration, $date);
        
        if ($stmt->execute()) {
            $successCount++;
        }
        $stmt->close();
    }
}

echo json_encode([
    "success" => true,
    "status" => "success", 
    "message" => "$successCount sessions synced",
    "synced_count" => $successCount
]);
?>

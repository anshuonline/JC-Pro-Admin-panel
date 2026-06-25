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
$sessions = $data['sessions']; // Array of {duration_seconds: int, date: string}

// Auto-register user if they don't exist
$conn->query("INSERT IGNORE INTO users (username) VALUES ('$username')");

$user_check = $conn->query("SELECT id FROM users WHERE username = '$username' LIMIT 1");
if (!$user_check || $user_check->num_rows == 0) {
    echo json_encode(["status" => "error", "message" => "Database error: Could not create user"]);
    exit();
}

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

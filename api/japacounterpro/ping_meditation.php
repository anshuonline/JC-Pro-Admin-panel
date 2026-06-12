<?php
// C:\xampp\htdocs\JC Pro Admin panel\api\japacounterpro\ping_meditation.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config.php';

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (!$data || !isset($data['username']) || !isset($data['action'])) {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit();
}

$username = $conn->real_escape_string($data['username']);
$action = $data['action']; // "start" or "stop"

if ($action === "start") {
    $expectedDuration = isset($data['expected_duration_seconds']) ? intval($data['expected_duration_seconds']) : 300;
    
    // Insert or update
    $stmt = $conn->prepare("INSERT INTO active_meditators (username, expected_duration_seconds, is_active) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE expected_duration_seconds = ?, is_active = 1, last_ping = CURRENT_TIMESTAMP");
    $stmt->bind_param("sii", $username, $expectedDuration, $expectedDuration);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(["status" => "success", "message" => "Ping started"]);
} else if ($action === "stop") {
    // Set is_active to 0
    $stmt = $conn->prepare("UPDATE active_meditators SET is_active = 0 WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(["status" => "success", "message" => "Ping stopped"]);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid action"]);
}
?>

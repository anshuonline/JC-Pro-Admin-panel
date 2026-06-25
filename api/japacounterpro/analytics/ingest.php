<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../../config.php';

// Set timezone
date_default_timezone_set('Asia/Kolkata');
$conn->query("SET time_zone = '+05:30'");

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['username'])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit();
}

$username = $conn->real_escape_string($data['username']);

// Ensure table exists just in case
$conn->query("CREATE TABLE IF NOT EXISTS analytics_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    event_type VARCHAR(50),
    count_value INT DEFAULT 0,
    timestamp BIGINT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$stmt = $conn->prepare("INSERT INTO analytics_events (username, event_type, count_value, timestamp) VALUES (?, ?, ?, ?)");

if (isset($data['events']) && is_array($data['events'])) {
    foreach ($data['events'] as $event) {
        if (isset($event['event_type']) && isset($event['timestamp'])) {
            $type = $conn->real_escape_string($event['event_type']);
            $val = isset($event['count_value']) ? intval($event['count_value']) : 0;
            $ts = $event['timestamp'];
            $stmt->bind_param("ssis", $username, $type, $val, $ts);
            $stmt->execute();
        }
    }
}
$stmt->close();

echo json_encode(["success" => true, "message" => "Events ingested"]);
?>

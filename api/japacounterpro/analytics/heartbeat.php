<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../../config.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['username'])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit();
}

$username = $conn->real_escape_string($data['username']);
$session_count = isset($data['session_count']) ? intval($data['session_count']) : 0;

// Create table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS live_sessions (
    username VARCHAR(50) PRIMARY KEY,
    session_count INT DEFAULT 0,
    started_at DATETIME,
    last_heartbeat DATETIME
)");

// Upsert heartbeat
$stmt = $conn->prepare("INSERT INTO live_sessions (username, session_count, started_at, last_heartbeat) VALUES (?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE session_count = VALUES(session_count), last_heartbeat = NOW()");
$stmt->bind_param("si", $username, $session_count);
$stmt->execute();
$stmt->close();

echo json_encode(["success" => true, "message" => "Heartbeat received"]);
?>

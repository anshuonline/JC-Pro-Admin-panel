<?php
// analytics/live.php — live users endpoint (public, no auth)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../config.php';

// Query users with heartbeat in last 30 seconds
$sql = "SELECT username, session_count, TIMESTAMPDIFF(SECOND, started_at, NOW()) AS duration_seconds
        FROM live_sessions
        WHERE last_heartbeat > NOW() - INTERVAL 30 SECOND
        ORDER BY session_count DESC";

$result = $conn->query($sql);

if (!$result) {
    $conn->close();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Query failed: " . $conn->error]);
    exit;
}

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = [
        "username"         => $row['username'],
        "session_count"    => (int)$row['session_count'],
        "duration_seconds" => (int)$row['duration_seconds']
    ];
}
$result->free();
$conn->close();

echo json_encode([
    "success"    => true,
    "live_count" => count($users),
    "users"      => $users
]);
?>

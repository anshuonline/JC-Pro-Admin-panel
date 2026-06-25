<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

require_once '../../config.php';

$announcements = [];
$res = $conn->query("SELECT id, title, message, type, created_at FROM announcements ORDER BY created_at DESC LIMIT 10");

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $announcements[] = $row;
    }
}

echo json_encode([
    "success" => true,
    "announcements" => $announcements
]);
?>

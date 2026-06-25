<?php
require_once '../../config.php';

header('Content-Type: application/json');

$pages = [];
$res = $conn->query("SELECT id, title, content, youtube_url FROM content_pages ORDER BY id ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $row['id'] = (int)$row['id'];
        $pages[] = $row;
    }
}

echo json_encode([
    "success" => true,
    "data" => $pages
]);
?>

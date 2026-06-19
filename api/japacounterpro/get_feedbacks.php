<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");

require_once '../../config.php';

// Fetch top 10 featured feedbacks to show on website
$feedbacks = [];
$res = $conn->query("SELECT name, overall_rating, likes_most, submitted_at FROM feedback WHERE is_featured = 1 AND likes_most != '' ORDER BY id DESC LIMIT 10");

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $feedbacks[] = [
            'name' => $row['name'] ?: 'Anonymous User',
            'rating' => (int)$row['overall_rating'],
            'comment' => $row['likes_most'],
            'date' => date('M d, Y', strtotime($row['submitted_at']))
        ];
    }
}

echo json_encode([
    'status' => 'success',
    'data' => $feedbacks
]);
?>

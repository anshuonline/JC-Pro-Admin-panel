<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

echo json_encode([
    "success" => true,
    "challenges" => []
]);
?>

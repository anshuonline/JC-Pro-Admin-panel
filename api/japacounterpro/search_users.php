<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config.php';

$query = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';

if (empty($query)) {
    echo json_encode(["success" => true, "data" => []]);
    exit;
}

// Search users matching the query
$sql = "SELECT google_uid, username, profile_picture FROM users 
        WHERE username LIKE '%$query%' 
        ORDER BY username ASC 
        LIMIT 10";
        
$res = $conn->query($sql);
$users = [];

if ($res && $res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        $users[] = [
            "google_uid" => $row['google_uid'],
            "username" => $row['username'],
            "profile_url" => $row['profile_picture']
        ];
    }
}

echo json_encode([
    "success" => true,
    "data" => $users
]);
?>

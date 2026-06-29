<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['username'])) {
    echo json_encode(["success" => false, "message" => "Missing username"]);
    exit;
}

$username = $conn->real_escape_string($data['username']);

// Verify if the user actually has a gift
$check_sql = "SELECT has_gift FROM users WHERE username = '$username' LIMIT 1";
$result = $conn->query($check_sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['has_gift'] == 1) {
        // Claim the gift
        $stmt = $conn->prepare("UPDATE users SET is_premium = 1, premium_since = NOW(), has_gift = 0 WHERE username = ?");
        $stmt->bind_param("s", $username);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Gift claimed successfully!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "No gift pending for this user"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "User not found"]);
}
?>

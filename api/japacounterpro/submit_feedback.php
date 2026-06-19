<?php
// C:\xampp\htdocs\JC Pro Admin panel\api\japacounterpro\submit_feedback.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// config.php is two levels up from this folder
require_once '../../config.php';

// Get JSON POST body
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "No JSON data provided"]);
    exit;
}

// Extract fields
$name = isset($data['name']) ? $data['name'] : '';
$email = isset($data['email']) ? $data['email'] : '';
$app_usage = isset($data['app_usage']) ? $data['app_usage'] : '';
$rating_accuracy = isset($data['rating_accuracy']) ? (int)$data['rating_accuracy'] : 0;
$rating_ui = isset($data['rating_ui']) ? (int)$data['rating_ui'] : 0;
$rating_sound = isset($data['rating_sound']) ? (int)$data['rating_sound'] : 0;
$rating_history = isset($data['rating_history']) ? (int)$data['rating_history'] : 0;
$likes_most = isset($data['likes_most']) ? $data['likes_most'] : '';
$improvements = isset($data['improvements']) ? $data['improvements'] : '';
$experienced_bugs = isset($data['experienced_bugs']) ? $data['experienced_bugs'] : 'No';
$bug_details = isset($data['bug_details']) ? $data['bug_details'] : '';
$overall_rating = isset($data['overall_rating']) ? (int)$data['overall_rating'] : 0;

if (empty($name)) {
    echo json_encode(["success" => false, "message" => "Name is required"]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO feedback (name, email, app_usage, rating_accuracy, rating_ui, rating_sound, rating_history, likes_most, improvements, experienced_bugs, bug_details, overall_rating) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssiiiissssi", $name, $email, $app_usage, $rating_accuracy, $rating_ui, $rating_sound, $rating_history, $likes_most, $improvements, $experienced_bugs, $bug_details, $overall_rating);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Feedback submitted successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>

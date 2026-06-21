<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config.php';

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (!$data || !isset($data['username']) || !isset($data['total_counts'])) {
    echo json_encode(["success" => false, "message" => "Invalid request payload"]);
    exit();
}

$username = $conn->real_escape_string($data['username']);
$total_counts = intval($data['total_counts']);
$level = isset($data['level']) ? intval($data['level']) : 1;
$device_token = isset($data['device_token']) ? $conn->real_escape_string($data['device_token']) : null;
$sessions = isset($data['sessions']) ? $data['sessions'] : [];

// Find user
$user_res = $conn->query("SELECT id FROM users WHERE username = '$username' LIMIT 1");
if ($user_res && $user_res->num_rows > 0) {
    $user_row = $user_res->fetch_assoc();
    $user_id = $user_row['id'];
    
    // Update user stats
    if ($device_token) {
        $conn->query("UPDATE users SET total_counts = $total_counts, level = $level, device_token = '$device_token' WHERE id = $user_id");
    } else {
        $conn->query("UPDATE users SET total_counts = $total_counts, level = $level WHERE id = $user_id");
    }
    
    // Only sync daily_counts if sessions array is provided
    if (!empty($sessions)) {
        // *** FIX: Delete ALL existing daily_counts for this user FIRST ***
        // This removes all stale/duplicate old data completely
        $conn->query("DELETE FROM daily_counts WHERE user_id = $user_id");
        
        // Now insert only the fresh, accurate sessions from the app
        $stmt = $conn->prepare("INSERT INTO daily_counts (user_id, date, daily_count) VALUES (?, ?, ?)");
        foreach ($sessions as $session) {
            if (isset($session['date']) && isset($session['count']) && intval($session['count']) > 0) {
                $date = $conn->real_escape_string($session['date']);
                $count = intval($session['count']);
                $stmt->bind_param("isi", $user_id, $date, $count);
                $stmt->execute();
            }
        }
        $stmt->close();
    }
    
    echo json_encode(["success" => true, "message" => "Score and calendar successfully synced"]);
} else {
    echo json_encode(["success" => false, "message" => "User not found"]);
}
?>

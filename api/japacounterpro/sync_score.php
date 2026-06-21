<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (!$data || !isset($data['username'])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit();
}

$username = $conn->real_escape_string($data['username']);
$level = isset($data['level']) ? intval($data['level']) : 0;
$device_token = isset($data['device_token']) ? $conn->real_escape_string($data['device_token']) : null;

// Calendar sessions = SOURCE OF TRUTH from the app
// Each session = { "date": "2026-06-22", "count": 109 }
$sessions = isset($data['sessions']) ? $data['sessions'] : [];

// Find user
$user_res = $conn->query("SELECT id FROM users WHERE username = '$username' LIMIT 1");
if (!$user_res || $user_res->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit();
}

$user_row = $user_res->fetch_assoc();
$user_id = $user_row['id'];

// Step 1: Update user level & device token (NOT total_counts — we don't trust client total)
$updates = "level = $level";
if ($device_token) {
    $updates .= ", device_token = '$device_token'";
}
$conn->query("UPDATE users SET $updates WHERE id = $user_id");

// Step 2: Calendar Sync — only if sessions are provided
// This is the ONLY way daily_counts gets written. Calendar is the source of truth.
if (!empty($sessions)) {
    // Fetch current challenge dates
    $cfg_res = $conn->query("SELECT challenge_start, challenge_end FROM leaderboard_config LIMIT 1");
    if ($cfg_res && $cfg_res->num_rows > 0) {
        $cfg_row = $cfg_res->fetch_assoc();
        $c_start = $conn->real_escape_string(date('Y-m-d', strtotime($cfg_row['challenge_start'])));
        $c_end = $conn->real_escape_string(date('Y-m-d', strtotime($cfg_row['challenge_end'])));
        
        // NUKE all existing daily_counts for this user ONLY within the current challenge period
        $conn->query("DELETE FROM daily_counts WHERE user_id = $user_id AND date >= '$c_start' AND date <= '$c_end'");
    } else {
        // Fallback if config is missing
        $conn->query("DELETE FROM daily_counts WHERE user_id = $user_id");
    }
    
    // Insert ONLY what Calendar says — BUT strictly filter to challenge dates
    // This prevents background SyncWorkers from polluting the database with out-of-bounds data
    $total = 0;
    $stmt = $conn->prepare("INSERT INTO daily_counts (user_id, date, daily_count) VALUES (?, ?, ?)");
    foreach ($sessions as $session) {
        if (isset($session['date']) && isset($session['count'])) {
            $date = $conn->real_escape_string($session['date']);
            $count = intval($session['count']);
            
            // Strictly check if the session date is within the active challenge period
            if ($count > 0 && $date >= $c_start && $date <= $c_end) {
                $stmt->bind_param("isi", $user_id, $date, $count);
                $stmt->execute();
                $total += $count;
            }
        }
    }
    $stmt->close();
    
    // Update total_counts to match Calendar SUM (server & calendar are now identical)
    $conn->query("UPDATE users SET total_counts = $total WHERE id = $user_id");
}

echo json_encode(["success" => true, "message" => "Calendar synced"]);
?>

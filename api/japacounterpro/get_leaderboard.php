<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config.php';

$username_query = isset($_GET['username']) ? $conn->real_escape_string($_GET['username']) : null;

// Fetch config
$status = 'ACTIVE';
$challenge_start = date('Y-m-01 00:00:00');
$challenge_end = date('Y-m-t 23:59:59');

$cfg_res = $conn->query("SELECT status, challenge_start, challenge_end FROM leaderboard_config LIMIT 1");
if ($cfg_res && $cfg_res->num_rows > 0) {
    $cfg_row = $cfg_res->fetch_assoc();
    $status = $cfg_row['status'];
    $challenge_start = $cfg_row['challenge_start'];
    $challenge_end = $cfg_row['challenge_end'];
}

$current_time = date('Y-m-d H:i:s');
$computed_status = 'ACTIVE';
if ($current_time < $challenge_start) {
    $computed_status = 'WAITING';
} else if ($current_time > $challenge_end) {
    $computed_status = 'RESULTS';
}

$leaderboard_data = [];
$user_rank = null;
$user_counts = null;
$user_level = null;

// Get the sum of daily counts within the challenge dates
$sql = "SELECT u.username, SUM(dc.daily_count) as total_counts, MAX(u.level) as level 
        FROM users u 
        JOIN daily_counts dc ON u.id = dc.user_id 
        WHERE dc.date >= DATE('$challenge_start') AND dc.date <= DATE('$challenge_end') AND u.is_bot = 0
        GROUP BY u.id 
        HAVING total_counts > 0
        ORDER BY total_counts DESC";
        
$res = $conn->query($sql);
$rank = 1;
if ($res && $res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        $leaderboard_data[] = [
            "rank" => $rank,
            "username" => $row['username'],
            "total_counts" => intval($row['total_counts']),
            "level" => intval($row['level'])
        ];
        
        if ($username_query && strtolower($row['username']) === strtolower($username_query)) {
            $user_rank = $rank;
            $user_counts = intval($row['total_counts']);
            $user_level = intval($row['level']);
        }
        
        $rank++;
    }
}

// Return top 100 for the array to keep payload small
$top_100 = array_slice($leaderboard_data, 0, 100);

echo json_encode([
    "success" => true,
    "status" => $computed_status,
    "challenge_start" => $challenge_start,
    "challenge_end" => $challenge_end,
    "data" => $top_100,
    "user_rank" => $user_rank,
    "user_counts" => $user_counts,
    "user_level" => $user_level
]);
?>

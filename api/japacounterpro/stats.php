<?php
// C:\xampp\htdocs\JC Pro Admin panel\api\japacounterpro\stats.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Require config two levels up
require_once '../../config.php';

// Initialize response
$response = [
    'total_users' => 0,
    'total_counts' => 0,
    'today_counts' => 0,
    'today_active_users' => 0,
    'live_users' => 0,
    'this_week_counts' => 0,
    'this_month_counts' => 0,
    'this_year_counts' => 0
];

// Total users & Total lifetime counts
$res = $conn->query("SELECT COUNT(id) as cnt, SUM(total_counts) as total FROM users");
if ($res && $row = $res->fetch_assoc()) {
    $response['total_users'] = (int)$row['cnt'];
    $response['total_counts'] = (int)$row['total'];
}

// Today stats
$today = date('Y-m-d');
$res = $conn->query("SELECT SUM(daily_count) as today_cnt, COUNT(DISTINCT user_id) as active_users FROM daily_counts WHERE date = '$today'");
if ($res && $row = $res->fetch_assoc()) {
    $response['today_counts'] = (int)$row['today_cnt'];
    $response['today_active_users'] = (int)$row['active_users'];
}

// Live Users (Heartbeat within last/next minute)
$res = $conn->query("SELECT COUNT(id) as live FROM live_sessions WHERE last_heartbeat >= CURRENT_TIMESTAMP");
if ($res && $row = $res->fetch_assoc()) {
    $response['live_users'] = (int)$row['live'];
}

// This Week counts
$res = $conn->query("SELECT SUM(daily_count) as week_cnt FROM daily_counts WHERE YEARWEEK(date, 1) = YEARWEEK('$today', 1)");
if ($res && $row = $res->fetch_assoc()) {
    $response['this_week_counts'] = (int)$row['week_cnt'];
}

// This Month counts
$res = $conn->query("SELECT SUM(daily_count) as month_cnt FROM daily_counts WHERE MONTH(date) = MONTH('$today') AND YEAR(date) = YEAR('$today')");
if ($res && $row = $res->fetch_assoc()) {
    $response['this_month_counts'] = (int)$row['month_cnt'];
}

// This Year counts
$res = $conn->query("SELECT SUM(daily_count) as year_cnt FROM daily_counts WHERE YEAR(date) = YEAR('$today')");
if ($res && $row = $res->fetch_assoc()) {
    $response['this_year_counts'] = (int)$row['year_cnt'];
}

echo json_encode($response);
?>

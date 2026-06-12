<?php
// C:\xampp\htdocs\JC Pro Admin panel\api\japacounterpro\get_meditation_stats.php
header('Content-Type: application/json');

require_once '../../config.php';

// Active users: last_ping within 2 minutes and is_active = 1
$active_res = $conn->query("SELECT username, expected_duration_seconds FROM active_meditators WHERE is_active = 1 AND last_ping >= NOW() - INTERVAL 2 MINUTE");
$active_users = [];
if ($active_res) {
    while($row = $active_res->fetch_assoc()) {
        $active_users[] = $row;
    }
}

// Total duration (hours)
$res_total = $conn->query("SELECT SUM(duration_seconds) as total_seconds FROM meditation_sessions");
$total_hours = 0;
if ($res_total && $row = $res_total->fetch_assoc()) {
    $total_hours = round(($row['total_seconds'] ?? 0) / 3600, 2);
}

// Total unique people
$res_people = $conn->query("SELECT COUNT(DISTINCT username) as total_people FROM meditation_sessions");
$total_people = 0;
if ($res_people && $row = $res_people->fetch_assoc()) {
    $total_people = $row['total_people'] ?? 0;
}

// Max meditation time (single session)
$res_max = $conn->query("SELECT MAX(duration_seconds) as max_seconds FROM meditation_sessions");
$max_minutes = 0;
if ($res_max && $row = $res_max->fetch_assoc()) {
    $max_minutes = round(($row['max_seconds'] ?? 0) / 60, 1);
}

echo json_encode([
    "active_count" => count($active_users),
    "active_users" => $active_users,
    "total_hours" => $total_hours,
    "total_people" => $total_people,
    "max_minutes" => $max_minutes
]);
?>

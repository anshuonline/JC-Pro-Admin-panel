<?php
// analytics/aggregate.php — cron aggregation endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../config.php';

// Set PHP Timezone to India
date_default_timezone_set('Asia/Kolkata');
// Set MySQL Timezone to India
$conn->query("SET time_zone = '+05:30'");

$counts = ["daily" => 0, "weekly" => 0, "monthly" => 0, "yearly" => 0];
$errors = [];
$today  = date('Y-m-d');

// Helper: upsert into aggregated_stats
function upsertStat($conn, $period_type, $period_key, $total_counts, $active_users, $new_users, $avg_counts) {
    $stmt = $conn->prepare(
        "INSERT INTO aggregated_stats (period_type, period_key, total_counts, active_users, new_users, avg_counts_per_user)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           total_counts = VALUES(total_counts),
           active_users = VALUES(active_users),
           new_users = VALUES(new_users),
           avg_counts_per_user = VALUES(avg_counts_per_user),
           updated_at = CURRENT_TIMESTAMP"
    );
    if (!$stmt) return false;
    $stmt->bind_param("ssiiid", $period_type, $period_key, $total_counts, $active_users, $new_users, $avg_counts);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// ──────────────────────────────────────
// DAILY aggregation (last 30 days)
// ──────────────────────────────────────
for ($i = 0; $i < 30; $i++) {
    $d = date('Y-m-d', strtotime("-{$i} days"));

    $stmt = $conn->prepare(
        "SELECT IFNULL(SUM(daily_count), 0), COUNT(DISTINCT user_id)
         FROM daily_counts WHERE date = ?"
    );
    $stmt->bind_param("s", $d);
    $stmt->execute();
    $stmt->bind_result($day_total, $day_active);
    $stmt->fetch();
    $stmt->close();

    // New users that day (requires created_at column)
    $new_u = 0;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) = ?");
    if ($stmt) {
        $stmt->bind_param("s", $d);
        $stmt->execute();
        $stmt->bind_result($new_u);
        $stmt->fetch();
        $stmt->close();
    }

    $avg = ($day_active > 0) ? round($day_total / $day_active, 2) : 0;

    if (upsertStat($conn, 'daily', $d, (int)$day_total, (int)$day_active, (int)$new_u, $avg)) {
        $counts['daily']++;
    }
}

// ──────────────────────────────────────
// WEEKLY aggregation (last 12 ISO weeks)
// ──────────────────────────────────────
for ($i = 0; $i < 12; $i++) {
    $ref_date = date('Y-m-d', strtotime("-{$i} weeks"));
    $year = date('o', strtotime($ref_date)); // ISO year
    $week = date('W', strtotime($ref_date)); // ISO week
    $period_key = $year . '-W' . $week;

    // Week start (Monday) and end (Sunday)
    $week_start = date('Y-m-d', strtotime("{$year}-W{$week}-1"));
    $week_end   = date('Y-m-d', strtotime("{$year}-W{$week}-7"));

    $stmt = $conn->prepare(
        "SELECT IFNULL(SUM(daily_count), 0), COUNT(DISTINCT user_id)
         FROM daily_counts WHERE date BETWEEN ? AND ?"
    );
    $stmt->bind_param("ss", $week_start, $week_end);
    $stmt->execute();
    $stmt->bind_result($wk_total, $wk_active);
    $stmt->fetch();
    $stmt->close();

    $new_u = 0;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) BETWEEN ? AND ?");
    if ($stmt) {
        $stmt->bind_param("ss", $week_start, $week_end);
        $stmt->execute();
        $stmt->bind_result($new_u);
        $stmt->fetch();
        $stmt->close();
    }

    $avg = ($wk_active > 0) ? round($wk_total / $wk_active, 2) : 0;

    if (upsertStat($conn, 'weekly', $period_key, (int)$wk_total, (int)$wk_active, (int)$new_u, $avg)) {
        $counts['weekly']++;
    }
}

// ──────────────────────────────────────
// MONTHLY aggregation (last 12 months)
// ──────────────────────────────────────
for ($i = 0; $i < 12; $i++) {
    $ref_date   = date('Y-m-01', strtotime("-{$i} months"));
    $period_key = date('Y-m', strtotime($ref_date));
    $month_start = $ref_date;
    $month_end   = date('Y-m-t', strtotime($ref_date));

    $stmt = $conn->prepare(
        "SELECT IFNULL(SUM(daily_count), 0), COUNT(DISTINCT user_id)
         FROM daily_counts WHERE date BETWEEN ? AND ?"
    );
    $stmt->bind_param("ss", $month_start, $month_end);
    $stmt->execute();
    $stmt->bind_result($mo_total, $mo_active);
    $stmt->fetch();
    $stmt->close();

    $new_u = 0;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) BETWEEN ? AND ?");
    if ($stmt) {
        $stmt->bind_param("ss", $month_start, $month_end);
        $stmt->execute();
        $stmt->bind_result($new_u);
        $stmt->fetch();
        $stmt->close();
    }

    $avg = ($mo_active > 0) ? round($mo_total / $mo_active, 2) : 0;

    if (upsertStat($conn, 'monthly', $period_key, (int)$mo_total, (int)$mo_active, (int)$new_u, $avg)) {
        $counts['monthly']++;
    }
}

// ──────────────────────────────────────
// YEARLY aggregation (current year)
// ──────────────────────────────────────
$year_key   = date('Y');
$year_start = date('Y-01-01');
$year_end   = date('Y-12-31');

$stmt = $conn->prepare(
    "SELECT IFNULL(SUM(daily_count), 0), COUNT(DISTINCT user_id)
     FROM daily_counts WHERE date BETWEEN ? AND ?"
);
$stmt->bind_param("ss", $year_start, $year_end);
$stmt->execute();
$stmt->bind_result($yr_total, $yr_active);
$stmt->fetch();
$stmt->close();

$new_u = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) BETWEEN ? AND ?");
if ($stmt) {
    $stmt->bind_param("ss", $year_start, $year_end);
    $stmt->execute();
    $stmt->bind_result($new_u);
    $stmt->fetch();
    $stmt->close();
}

$avg = ($yr_active > 0) ? round($yr_total / $yr_active, 2) : 0;

if (upsertStat($conn, 'yearly', $year_key, (int)$yr_total, (int)$yr_active, (int)$new_u, $avg)) {
    $counts['yearly']++;
}

// ──────────────────────────────────────
// CLEANUP
// ──────────────────────────────────────

// Delete analytics_events older than 90 days
$cleanup_events = $conn->query("DELETE FROM analytics_events WHERE created_at < NOW() - INTERVAL 90 DAY");
$deleted_events = $conn->affected_rows;

// Delete stale live_sessions (no heartbeat in 5 minutes)
$cleanup_sessions = $conn->query("DELETE FROM live_sessions WHERE last_heartbeat < NOW() - INTERVAL 5 MINUTE");
$deleted_sessions = $conn->affected_rows;

$conn->close();

echo json_encode([
    "success"    => true,
    "aggregated" => $counts,
    "cleanup"    => [
        "events_deleted"   => $deleted_events,
        "sessions_deleted" => $deleted_sessions
    ]
]);
?>

<?php
// analytics/stats.php — main dashboard data endpoint
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

$today = date('Y-m-d');

// ──────────────────────────────────────
// OVERVIEW
// ──────────────────────────────────────

// Total users
$r = $conn->query("SELECT COUNT(*) AS cnt FROM users");
$total_users = ($r && $row = $r->fetch_assoc()) ? (int)$row['cnt'] : 0;

// Total counts (all time)
$r = $conn->query("SELECT IFNULL(SUM(total_counts), 0) AS cnt FROM users");
$total_counts = ($r && $row = $r->fetch_assoc()) ? (int)$row['cnt'] : 0;

// Today counts
$stmt = $conn->prepare("SELECT IFNULL(SUM(daily_count), 0) AS cnt FROM daily_counts WHERE date = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$stmt->bind_result($today_counts);
$stmt->fetch();
$stmt->close();

// Today active users
$stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) AS cnt FROM daily_counts WHERE date = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$stmt->bind_result($today_active_users);
$stmt->fetch();
$stmt->close();

// Live users (heartbeat in last 30 seconds)
$r = $conn->query("SELECT COUNT(*) AS cnt FROM live_sessions WHERE last_heartbeat > NOW() - INTERVAL 30 SECOND");
$live_users = ($r && $row = $r->fetch_assoc()) ? (int)$row['cnt'] : 0;

// This week counts (Monday-based ISO week)
$week_start = date('Y-m-d', strtotime('monday this week'));
$stmt = $conn->prepare("SELECT IFNULL(SUM(daily_count), 0) AS cnt FROM daily_counts WHERE date >= ?");
$stmt->bind_param("s", $week_start);
$stmt->execute();
$stmt->bind_result($this_week_counts);
$stmt->fetch();
$stmt->close();

// This month counts
$month_start = date('Y-m-01');
$stmt = $conn->prepare("SELECT IFNULL(SUM(daily_count), 0) AS cnt FROM daily_counts WHERE date >= ?");
$stmt->bind_param("s", $month_start);
$stmt->execute();
$stmt->bind_result($this_month_counts);
$stmt->fetch();
$stmt->close();

// This year counts
$year_start = date('Y-01-01');
$stmt = $conn->prepare("SELECT IFNULL(SUM(daily_count), 0) AS cnt FROM daily_counts WHERE date >= ?");
$stmt->bind_param("s", $year_start);
$stmt->execute();
$stmt->bind_result($this_year_counts);
$stmt->fetch();
$stmt->close();

$overview = [
    "total_users"        => $total_users,
    "total_counts"       => $total_counts,
    "today_counts"       => (int)$today_counts,
    "today_active_users" => (int)$today_active_users,
    "live_users"         => $live_users,
    "this_week_counts"   => (int)$this_week_counts,
    "this_month_counts"  => (int)$this_month_counts,
    "this_year_counts"   => (int)$this_year_counts
];

// ──────────────────────────────────────
// DAILY TREND (last 30 days from daily_counts)
// ──────────────────────────────────────
$thirty_days_ago = date('Y-m-d', strtotime('-29 days'));
$stmt = $conn->prepare(
    "SELECT date, SUM(daily_count) AS counts, COUNT(DISTINCT user_id) AS active_users
     FROM daily_counts
     WHERE date >= ?
     GROUP BY date
     ORDER BY date ASC"
);
$stmt->bind_param("s", $thirty_days_ago);
$stmt->execute();
$stmt->bind_result($d_date, $d_counts, $d_active);

$daily_trend_map = [];
while ($stmt->fetch()) {
    $daily_trend_map[$d_date] = [
        "date"         => $d_date,
        "counts"       => (int)$d_counts,
        "active_users" => (int)$d_active
    ];
}
$stmt->close();

// Fill in missing dates with zeros
$daily_trend = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    if (isset($daily_trend_map[$d])) {
        $daily_trend[] = $daily_trend_map[$d];
    } else {
        $daily_trend[] = ["date" => $d, "counts" => 0, "active_users" => 0];
    }
}

// ──────────────────────────────────────
// WEEKLY TREND (last 12 weeks)
// ──────────────────────────────────────
$weekly_trend = [];

// Try aggregated_stats first
$r = $conn->query(
    "SELECT period_key, total_counts, active_users
     FROM aggregated_stats
     WHERE period_type = 'weekly'
     ORDER BY period_key DESC
     LIMIT 12"
);

if ($r && $r->num_rows > 0) {
    while ($row = $r->fetch_assoc()) {
        $weekly_trend[] = [
            "week"         => $row['period_key'],
            "counts"       => (int)$row['total_counts'],
            "active_users" => (int)$row['active_users']
        ];
    }
    $weekly_trend = array_reverse($weekly_trend); // chronological order
} else {
    // Fallback: compute from daily_counts
    $twelve_weeks_ago = date('Y-m-d', strtotime('-12 weeks'));
    $stmt = $conn->prepare(
        "SELECT CONCAT(YEAR(date), '-W', LPAD(WEEK(date, 3), 2, '0')) AS wk,
                SUM(daily_count) AS counts,
                COUNT(DISTINCT user_id) AS active_users
         FROM daily_counts
         WHERE date >= ?
         GROUP BY wk
         ORDER BY wk ASC"
    );
    $stmt->bind_param("s", $twelve_weeks_ago);
    $stmt->execute();
    $stmt->bind_result($wk, $wk_counts, $wk_active);
    while ($stmt->fetch()) {
        $weekly_trend[] = [
            "week"         => $wk,
            "counts"       => (int)$wk_counts,
            "active_users" => (int)$wk_active
        ];
    }
    $stmt->close();
}

// ──────────────────────────────────────
// MONTHLY TREND (last 12 months)
// ──────────────────────────────────────
$monthly_trend = [];

// Try aggregated_stats first
$r = $conn->query(
    "SELECT period_key, total_counts, active_users
     FROM aggregated_stats
     WHERE period_type = 'monthly'
     ORDER BY period_key DESC
     LIMIT 12"
);

if ($r && $r->num_rows > 0) {
    while ($row = $r->fetch_assoc()) {
        $monthly_trend[] = [
            "month"        => $row['period_key'],
            "counts"       => (int)$row['total_counts'],
            "active_users" => (int)$row['active_users']
        ];
    }
    $monthly_trend = array_reverse($monthly_trend);
} else {
    // Fallback: compute from daily_counts
    $twelve_months_ago = date('Y-m-d', strtotime('-12 months'));
    $stmt = $conn->prepare(
        "SELECT DATE_FORMAT(date, '%Y-%m') AS mo,
                SUM(daily_count) AS counts,
                COUNT(DISTINCT user_id) AS active_users
         FROM daily_counts
         WHERE date >= ?
         GROUP BY mo
         ORDER BY mo ASC"
    );
    $stmt->bind_param("s", $twelve_months_ago);
    $stmt->execute();
    $stmt->bind_result($mo, $mo_counts, $mo_active);
    while ($stmt->fetch()) {
        $monthly_trend[] = [
            "month"        => $mo,
            "counts"       => (int)$mo_counts,
            "active_users" => (int)$mo_active
        ];
    }
    $stmt->close();
}

// ──────────────────────────────────────
// TOP USERS TODAY (top 10)
// ──────────────────────────────────────
$top_users_today = [];
$stmt = $conn->prepare(
    "SELECT u.username, dc.daily_count AS today_count, u.total_counts
     FROM daily_counts dc
     JOIN users u ON dc.user_id = u.id
     WHERE dc.date = ?
     ORDER BY dc.daily_count DESC
     LIMIT 10"
);
$stmt->bind_param("s", $today);
$stmt->execute();
$stmt->bind_result($tu_username, $tu_today, $tu_total);
while ($stmt->fetch()) {
    $top_users_today[] = [
        "username"     => $tu_username,
        "today_count"  => (int)$tu_today,
        "total_counts" => (int)$tu_total
    ];
}
$stmt->close();

$conn->close();

// ──────────────────────────────────────
// RESPONSE
// ──────────────────────────────────────
echo json_encode([
    "success"        => true,
    "overview"       => $overview,
    "daily_trend"    => $daily_trend,
    "weekly_trend"   => $weekly_trend,
    "monthly_trend"  => $monthly_trend,
    "top_users_today" => $top_users_today
]);
?>

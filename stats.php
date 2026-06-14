<?php
// stats.php - Analytics & Live Stats Dashboard
require_once 'config.php';

// Set PHP Timezone to India
date_default_timezone_set('Asia/Kolkata');
// Set MySQL Timezone to India
$conn->query("SET time_zone = '+05:30'");

check_auth();

// ====== FETCH ALL STATS ======

// API base URL for live data (Hostinger server)
$api_base = "https://hypecrews.com/api/japacounterpro/";

// --- Overview Stats ---
$stats = [
    'total_users' => 0,
    'total_counts' => 0,
    'today_counts' => 0,
    'today_active_users' => 0,
    'this_week_counts' => 0,
    'this_month_counts' => 0,
    'this_year_counts' => 0,
];

// Total users & counts
$res = $conn->query("SELECT COUNT(*) as u_count, SUM(total_counts) as t_counts FROM users");
if ($res && $row = $res->fetch_assoc()) {
    $stats['total_users'] = (int)($row['u_count'] ?? 0);
    $stats['total_counts'] = (int)($row['t_counts'] ?? 0);
}

// Today's counts & active users
$today = date('Y-m-d');
$res = $conn->query("SELECT SUM(daily_count) as today_sum, COUNT(DISTINCT user_id) as active_users FROM daily_counts WHERE date = '$today'");
if ($res && $row = $res->fetch_assoc()) {
    $stats['today_counts'] = (int)($row['today_sum'] ?? 0);
    $stats['today_active_users'] = (int)($row['active_users'] ?? 0);
}

// This week (since Monday)
$monday = date('Y-m-d', strtotime('monday this week'));
$res = $conn->query("SELECT SUM(daily_count) as week_sum FROM daily_counts WHERE date >= '$monday'");
if ($res && $row = $res->fetch_assoc()) {
    $stats['this_week_counts'] = (int)($row['week_sum'] ?? 0);
}

// This month
$first_of_month = date('Y-m-01');
$res = $conn->query("SELECT SUM(daily_count) as month_sum FROM daily_counts WHERE date >= '$first_of_month'");
if ($res && $row = $res->fetch_assoc()) {
    $stats['this_month_counts'] = (int)($row['month_sum'] ?? 0);
}

// This year
$first_of_year = date('Y-01-01');
$res = $conn->query("SELECT SUM(daily_count) as year_sum FROM daily_counts WHERE date >= '$first_of_year'");
if ($res && $row = $res->fetch_assoc()) {
    $stats['this_year_counts'] = (int)($row['year_sum'] ?? 0);
}

// --- Daily Trend (Last 30 days) ---
$daily_trend = [];
$res = $conn->query("SELECT date, SUM(daily_count) as counts, COUNT(DISTINCT user_id) as active_users 
                      FROM daily_counts 
                      WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                      GROUP BY date 
                      ORDER BY date ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $daily_trend[] = $row;
    }
}

// --- Weekly Trend (Last 12 weeks) ---
$weekly_trend = [];
$res = $conn->query("SELECT YEARWEEK(date, 1) as yw, 
                             MIN(date) as week_start,
                             SUM(daily_count) as counts, 
                             COUNT(DISTINCT user_id) as active_users 
                      FROM daily_counts 
                      WHERE date >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK) 
                      GROUP BY YEARWEEK(date, 1)
                      ORDER BY yw ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $weekly_trend[] = [
            'week' => 'W' . substr($row['yw'], 4),
            'week_start' => $row['week_start'],
            'counts' => (int)$row['counts'],
            'active_users' => (int)$row['active_users']
        ];
    }
}

// --- Monthly Trend (Last 12 months) ---
$monthly_trend = [];
$res = $conn->query("SELECT DATE_FORMAT(date, '%Y-%m') as month, 
                             SUM(daily_count) as counts, 
                             COUNT(DISTINCT user_id) as active_users 
                      FROM daily_counts 
                      WHERE date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) 
                      GROUP BY DATE_FORMAT(date, '%Y-%m')
                      ORDER BY month ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $monthly_trend[] = $row;
    }
}

// --- Top Users Today ---
$top_users = [];
$res = $conn->query("SELECT u.username, dc.daily_count as today_count, u.total_counts, u.last_active
                      FROM daily_counts dc 
                      JOIN users u ON dc.user_id = u.id 
                      WHERE dc.date = '$today' 
                      ORDER BY dc.daily_count DESC 
                      LIMIT 10");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $top_users[] = $row;
    }
}

// --- Live Users (from live_sessions table if exists) ---
$live_users = [];
$live_count = 0;
$live_table_exists = $conn->query("SHOW TABLES LIKE 'live_sessions'");
if ($live_table_exists && $live_table_exists->num_rows > 0) {
    $res = $conn->query("SELECT username, session_count, 
                                TIMESTAMPDIFF(SECOND, started_at, NOW()) as duration_seconds
                         FROM live_sessions 
                         WHERE last_heartbeat > NOW() - INTERVAL 30 SECOND
                         ORDER BY session_count DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $live_users[] = $row;
        }
        $live_count = count($live_users);
    }
}

include 'includes/header.php';
?>

<style>
    .stat-card-gradient {
        position: relative;
        overflow: hidden;
    }
    .stat-card-gradient::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
    }
    .stat-card-gradient.blue::before { background: linear-gradient(90deg, #3b82f6, #06b6d4); }
    .stat-card-gradient.green::before { background: linear-gradient(90deg, #22c55e, #10b981); }
    .stat-card-gradient.orange::before { background: linear-gradient(90deg, #f59e0b, #ef4444); }
    .stat-card-gradient.purple::before { background: linear-gradient(90deg, #8b5cf6, #ec4899); }
    .stat-card-gradient.teal::before { background: linear-gradient(90deg, #06b6d4, #3b82f6); }
    
    .live-pulse {
        animation: livePulse 2s ease-in-out infinite;
    }
    @keyframes livePulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    .fade-in {
        animation: fadeIn 0.5s ease-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .chart-container {
        position: relative;
        height: 300px;
    }
</style>

<!-- Page Title with Live Indicator -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Analytics & Live Stats</h1>
        <p class="text-sm text-slate-500 mt-0.5">Real-time data from your JapaCounter app</p>
    </div>
    <div class="flex items-center gap-2 bg-green-50 border border-green-200 px-3 py-1.5 rounded-full">
        <span class="w-2 h-2 bg-green-500 rounded-full live-pulse"></span>
        <span class="text-xs font-semibold text-green-700">Live · Auto-refreshing</span>
    </div>
</div>

<!-- Stat Cards Row 1 -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Total Users -->
    <div class="stat-card-gradient blue bg-white/60 backdrop-blur-xl rounded-2xl p-5 border border-white shadow-sm hover:shadow-md transition-all">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Total Users</p>
                <p class="text-3xl font-extrabold text-slate-800 mt-1" id="stat-total-users"><?php echo number_format($stats['total_users']); ?></p>
            </div>
            <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-users text-orange-600"></i>
            </div>
        </div>
        <p class="text-xs text-slate-500 mt-2">All time registered</p>
    </div>

    <!-- Today's Counts -->
    <div class="stat-card-gradient green bg-white/60 backdrop-blur-xl rounded-2xl p-5 border border-white shadow-sm hover:shadow-md transition-all">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Today's Jaap</p>
                <p class="text-3xl font-extrabold text-green-600 mt-1" id="stat-today-counts"><?php echo number_format($stats['today_counts']); ?></p>
            </div>
            <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-om text-green-600"></i>
            </div>
        </div>
        <p class="text-xs text-slate-500 mt-2"><span id="stat-today-users"><?php echo $stats['today_active_users']; ?></span> active users today</p>
    </div>

    <!-- This Week -->
    <div class="stat-card-gradient orange bg-white/60 backdrop-blur-xl rounded-2xl p-5 border border-white shadow-sm hover:shadow-md transition-all">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">This Week</p>
                <p class="text-3xl font-extrabold text-amber-600 mt-1" id="stat-week-counts"><?php echo number_format($stats['this_week_counts']); ?></p>
            </div>
            <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-calendar-week text-amber-600"></i>
            </div>
        </div>
        <p class="text-xs text-slate-500 mt-2">Since Monday</p>
    </div>

    <!-- Live Users -->
    <div class="stat-card-gradient purple bg-white/60 backdrop-blur-xl rounded-2xl p-5 border border-white shadow-sm hover:shadow-md transition-all">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Live Now</p>
                <p class="text-3xl font-extrabold text-slate-600 mt-1" id="stat-live-count"><?php echo $live_count; ?></p>
            </div>
            <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center">
                <span class="w-3 h-3 bg-green-500 rounded-full live-pulse"></span>
            </div>
        </div>
        <p class="text-xs text-slate-500 mt-2">Counting right now</p>
    </div>
</div>

<!-- Period Summary Row -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white/60 backdrop-blur-xl rounded-2xl p-5 border border-white shadow-sm text-center">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">This Month</p>
        <p class="text-2xl font-extrabold text-orange-600 mt-1"><?php echo number_format($stats['this_month_counts']); ?></p>
    </div>
    <div class="bg-white/60 backdrop-blur-xl rounded-2xl p-5 border border-white shadow-sm text-center">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">This Year</p>
        <p class="text-2xl font-extrabold text-slate-600 mt-1"><?php echo number_format($stats['this_year_counts']); ?></p>
    </div>
    <div class="bg-white/60 backdrop-blur-xl rounded-2xl p-5 border border-white shadow-sm text-center">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">All Time</p>
        <p class="text-2xl font-extrabold text-slate-800 mt-1"><?php echo number_format($stats['total_counts']); ?></p>
    </div>
</div>

<!-- Charts + Live Activity Row -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Daily Chart (2 cols) -->
    <div class="lg:col-span-2 bg-white/60 backdrop-blur-xl rounded-2xl p-6 border border-white shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-800"><i class="fa-solid fa-chart-column text-orange-500 mr-2"></i>Daily Jaap Counts</h3>
            <div class="flex gap-1 bg-slate-100 rounded-lg p-0.5">
                <button class="range-btn text-xs font-semibold px-3 py-1 rounded-md bg-orange-500 text-white" data-range="7">7D</button>
                <button class="range-btn text-xs font-semibold px-3 py-1 rounded-md text-slate-600 hover:bg-white" data-range="14">14D</button>
                <button class="range-btn text-xs font-semibold px-3 py-1 rounded-md text-slate-600 hover:bg-white" data-range="30">30D</button>
            </div>
        </div>
        <div class="chart-container">
            <canvas id="dailyChart"></canvas>
        </div>
    </div>

    <!-- Live Activity (1 col) -->
    <div class="bg-white/60 backdrop-blur-xl rounded-2xl p-6 border border-white shadow-sm">
        <h3 class="text-lg font-bold text-slate-800 mb-4">
            <span class="w-2 h-2 bg-green-500 rounded-full live-pulse inline-block mr-2"></span>
            Live Activity
        </h3>
        <div id="liveActivityList" class="space-y-3 max-h-[280px] overflow-y-auto">
            <?php if (empty($live_users)): ?>
                <div class="text-center py-8 text-slate-400">
                    <i class="fa-solid fa-om text-4xl mb-2 block opacity-30"></i>
                    <p class="text-sm">No one is counting right now</p>
                </div>
            <?php else: ?>
                <?php foreach ($live_users as $lu): ?>
                <div class="flex items-center gap-3 p-3 bg-green-50/60 border border-green-100 rounded-xl fade-in">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-orange-500 to-indigo-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                        <?php echo strtoupper(substr($lu['username'], 0, 1)); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-800 truncate"><?php echo htmlspecialchars($lu['username']); ?></p>
                        <p class="text-xs text-slate-500"><?php echo gmdate("H:i:s", (int)$lu['duration_seconds']); ?> active</p>
                    </div>
                    <span class="text-lg font-extrabold text-green-600"><?php echo number_format((int)$lu['session_count']); ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Weekly & Monthly Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white/60 backdrop-blur-xl rounded-2xl p-6 border border-white shadow-sm">
        <h3 class="text-lg font-bold text-slate-800 mb-4"><i class="fa-solid fa-chart-line text-slate-500 mr-2"></i>Weekly Trend</h3>
        <div class="chart-container">
            <canvas id="weeklyChart"></canvas>
        </div>
    </div>
    <div class="bg-white/60 backdrop-blur-xl rounded-2xl p-6 border border-white shadow-sm">
        <h3 class="text-lg font-bold text-slate-800 mb-4"><i class="fa-solid fa-chart-area text-amber-500 mr-2"></i>Monthly Trend</h3>
        <div class="chart-container">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>
</div>

<!-- Top Users List -->
<div class="bg-white/60 backdrop-blur-xl border border-white rounded-2xl shadow-sm overflow-hidden mb-6">
    <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-white/40">
        <h3 class="text-lg font-bold text-slate-800"><i class="fa-solid fa-trophy text-amber-500 mr-2"></i>Top Users Today</h3>
        <span class="text-xs font-semibold text-slate-500"><?php echo date('d M Y'); ?></span>
    </div>
    <div class="p-4" id="topUsersBody">
        <?php if (empty($top_users)): ?>
            <div class="py-8 text-center text-slate-400 font-medium">No activity today yet</div>
        <?php else: ?>
            <div class="flex flex-col gap-3">
                <?php foreach ($top_users as $i => $u): 
                    $rank = $i + 1;
                    $rankEmoji = $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : $rank));
                    $isLive = false;
                    foreach ($live_users as $lu) {
                        if ($lu['username'] === $u['username']) { $isLive = true; break; }
                    }
                ?>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-white/80 rounded-2xl border border-slate-100/50 shadow-sm hover:shadow-md transition-all gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-8 text-center text-xl font-bold text-slate-400"><?php echo $rankEmoji; ?></div>
                        <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-orange-500 to-indigo-500 text-white flex items-center justify-center text-lg font-bold shadow-inner flex-shrink-0">
                            <?php echo strtoupper(substr($u['username'], 0, 1)); ?>
                        </div>
                        <div>
                            <div class="font-bold text-slate-800 text-base"><?php echo htmlspecialchars($u['username']); ?></div>
                            <div class="text-xs font-medium text-slate-500">All Time: <span class="font-mono text-slate-700"><?php echo number_format((int)$u['total_counts']); ?></span></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between sm:justify-end gap-6 sm:w-auto w-full pl-12 sm:pl-0">
                        <div class="text-left sm:text-right">
                            <div class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-green-500 to-teal-400">
                                <?php echo number_format((int)$u['today_count']); ?>
                            </div>
                            <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Today</div>
                        </div>
                        <div class="w-24 text-right">
                            <?php if ($isLive): ?>
                                <span class="inline-flex items-center justify-center gap-1.5 bg-green-100/80 text-green-700 text-xs font-bold px-3 py-1.5 rounded-full border border-green-200/50 w-full">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full live-pulse"></span> Live
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center justify-center gap-1.5 bg-slate-100/80 text-slate-500 text-xs font-bold px-3 py-1.5 rounded-full border border-slate-200/50 w-full">
                                    Offline
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
// ====== CHART DATA FROM PHP ======
const dailyTrend = <?php echo json_encode($daily_trend); ?>;
const weeklyTrend = <?php echo json_encode($weekly_trend); ?>;
const monthlyTrend = <?php echo json_encode($monthly_trend); ?>;

let currentRange = 7;
const chartFont = { family: 'Inter, sans-serif' };

const defaultOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: 'rgba(15, 23, 42, 0.9)',
            titleFont: { ...chartFont, weight: '600', size: 13 },
            bodyFont: { ...chartFont, size: 12 },
            padding: 12,
            cornerRadius: 10,
            callbacks: {
                label: ctx => new Intl.NumberFormat('en-IN').format(ctx.raw) + ' counts'
            }
        }
    },
    scales: {
        x: {
            grid: { color: 'rgba(0,0,0,0.04)' },
            ticks: { color: '#94a3b8', font: { ...chartFont, size: 11 }, maxRotation: 0 }
        },
        y: {
            grid: { color: 'rgba(0,0,0,0.04)' },
            ticks: {
                color: '#94a3b8',
                font: { ...chartFont, size: 11 },
                callback: val => {
                    if (val >= 100000) return (val/100000).toFixed(1) + 'L';
                    if (val >= 1000) return (val/1000).toFixed(0) + 'K';
                    return val;
                }
            },
            beginAtZero: true
        }
    },
    animation: { duration: 800, easing: 'easeOutQuart' }
};

// --- Daily Chart ---
const dailyCtx = document.getElementById('dailyChart').getContext('2d');
const dailyGradient = dailyCtx.createLinearGradient(0, 0, 0, 300);
dailyGradient.addColorStop(0, 'rgba(59, 130, 246, 0.5)');
dailyGradient.addColorStop(1, 'rgba(59, 130, 246, 0.02)');

const dailyChart = new Chart(dailyCtx, {
    type: 'bar',
    data: { labels: [], datasets: [{
        data: [],
        backgroundColor: dailyGradient,
        borderColor: 'rgba(59, 130, 246, 0.8)',
        borderWidth: 1,
        borderRadius: 6,
        borderSkipped: false
    }]},
    options: { ...defaultOptions }
});

function updateDailyChart(range) {
    const sliced = window.appDailyTrend.slice(-range);
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    dailyChart.data.labels = sliced.map(d => {
        const dt = new Date(d.date);
        return dt.getDate() + ' ' + months[dt.getMonth()];
    });
    dailyChart.data.datasets[0].data = sliced.map(d => parseInt(d.counts) || 0);
    dailyChart.update('active');
}

// Range buttons
document.querySelectorAll('.range-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault(); // Prevent any default action
        document.querySelectorAll('.range-btn').forEach(b => {
            b.classList.remove('bg-orange-500', 'text-white');
            b.classList.add('text-slate-600');
        });
        this.classList.add('bg-orange-500', 'text-white');
        this.classList.remove('text-slate-600');
        updateDailyChart(parseInt(this.dataset.range));
    });
});

// --- Weekly Chart (Last 7 Days with Day Names) ---
const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
const weeklyGradient = weeklyCtx.createLinearGradient(0, 0, 0, 300);
weeklyGradient.addColorStop(0, 'rgba(139, 92, 246, 0.3)');
weeklyGradient.addColorStop(1, 'rgba(139, 92, 246, 0)');

const weeklyChart = new Chart(weeklyCtx, {
    type: 'line',
    data: {
        labels: [],
        datasets: [{
            data: [],
            borderColor: '#8b5cf6',
            backgroundColor: weeklyGradient,
            borderWidth: 2.5,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#8b5cf6',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: { ...defaultOptions }
});

function updateWeeklyChart() {
    const sliced = window.appDailyTrend.slice(-7);
    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    weeklyChart.data.labels = sliced.map(d => {
        const dt = new Date(d.date);
        return dayNames[dt.getDay()];
    });
    weeklyChart.data.datasets[0].data = sliced.map(d => parseInt(d.counts) || 0);
    weeklyChart.update('active');
}

// --- Monthly Chart ---
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
const monthlyGradient = monthlyCtx.createLinearGradient(0, 0, 0, 300);
monthlyGradient.addColorStop(0, 'rgba(245, 158, 11, 0.3)');
monthlyGradient.addColorStop(1, 'rgba(245, 158, 11, 0)');

const monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

const monthlyChart = new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: [],
        datasets: [{
            data: [],
            borderColor: '#f59e0b',
            backgroundColor: monthlyGradient,
            borderWidth: 2.5,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#f59e0b',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: { ...defaultOptions }
});

function updateMonthlyChart() {
    monthlyChart.data.labels = window.appMonthlyTrend.map(d => {
        const [y, m] = (d.month || d.period_key).split('-');
        return monthNames[parseInt(m)-1] + ' ' + y;
    });
    monthlyChart.data.datasets[0].data = window.appMonthlyTrend.map(d => parseInt(d.counts) || 0);
    monthlyChart.update('active');
}

// Global variables to hold data
window.appDailyTrend = dailyTrend;
window.appMonthlyTrend = monthlyTrend;

// Initial chart rendering
updateDailyChart(7);
updateWeeklyChart();
updateMonthlyChart();

// ====== AUTO-REFRESH LIVE DATA ======
const API_LIVE = '<?php echo $api_base; ?>analytics/live.php';

async function refreshLiveUsers() {
    try {
        const res = await fetch(API_LIVE);
        const data = await res.json();
        if (data.success) {
            document.getElementById('stat-live-count').textContent = data.live_count || 0;
            const list = document.getElementById('liveActivityList');
            if (!data.users || data.users.length === 0) {
                list.innerHTML = `<div class="text-center py-8 text-slate-400">
                    <i class="fa-solid fa-om text-4xl mb-2 block opacity-30"></i>
                    <p class="text-sm">No one is counting right now</p>
                </div>`;
            } else {
                list.innerHTML = data.users.slice(0, 100).map(u => `
                    <div class="flex items-center gap-3 p-3 bg-green-50/60 border border-green-100 rounded-xl fade-in">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-orange-500 to-indigo-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                            ${u.username.charAt(0).toUpperCase()}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800 truncate">${u.username}</p>
                            <p class="text-xs text-slate-500">${formatDuration(u.duration_seconds || 0)} active</p>
                        </div>
                        <span class="text-lg font-extrabold text-green-600">${parseInt(u.session_count || 0).toLocaleString('en-IN')}</span>
                    </div>
                `).join('');
            }
        }
    } catch (e) {
        console.log('Live refresh failed:', e);
    }
}

function formatDuration(s) {
    s = parseInt(s);
    if (s < 60) return s + 's';
    if (s < 3600) return Math.floor(s/60) + 'm ' + (s%60) + 's';
    return Math.floor(s/3600) + 'h ' + Math.floor((s%3600)/60) + 'm';
}

// Refresh live users every 5 seconds
setInterval(refreshLiveUsers, 5000);

// ====== BACKGROUND FULL STATS REFRESH ======
const API_STATS = '<?php echo $api_base; ?>analytics/stats.php';

async function refreshFullStats() {
    try {
        const res = await fetch(API_STATS);
        const data = await res.json();
        if (data.success && data.overview) {
            // Update Overview Cards
            document.getElementById('stat-total-users').textContent = new Intl.NumberFormat('en-IN').format(data.overview.total_users || 0);
            document.getElementById('stat-today-counts').textContent = new Intl.NumberFormat('en-IN').format(data.overview.today_counts || 0);
            document.getElementById('stat-today-users').textContent = data.overview.today_active_users || 0;
            document.getElementById('stat-week-counts').textContent = new Intl.NumberFormat('en-IN').format(data.overview.this_week_counts || 0);
            
            // Update Period Summary
            const summaries = document.querySelectorAll('.grid-cols-3 .text-2xl');
            if (summaries.length >= 3) {
                summaries[0].textContent = new Intl.NumberFormat('en-IN').format(data.overview.this_month_counts || 0);
                summaries[1].textContent = new Intl.NumberFormat('en-IN').format(data.overview.this_year_counts || 0);
                summaries[2].textContent = new Intl.NumberFormat('en-IN').format(data.overview.total_counts || 0);
            }

            // Update Charts Data
            window.appDailyTrend = data.daily_trend || window.appDailyTrend;
            window.appMonthlyTrend = data.monthly_trend || window.appMonthlyTrend;
            
            // Refresh Charts
            const activeRangeBtn = document.querySelector('.range-btn.bg-orange-500');
            const range = activeRangeBtn ? parseInt(activeRangeBtn.dataset.range) : 7;
            updateDailyChart(range);
            updateWeeklyChart();
            updateMonthlyChart();

            // Update Top Users List
            const tbody = document.getElementById('topUsersBody');
            if (data.top_users_today && data.top_users_today.length > 0) {
                const listHtml = data.top_users_today.map((u, i) => {
                    const rank = i + 1;
                    const rankEmoji = rank === 1 ? '🥇' : (rank === 2 ? '🥈' : (rank === 3 ? '🥉' : rank));
                    let isLive = false;
                    const liveCountEl = document.getElementById('liveActivityList');
                    if (liveCountEl && liveCountEl.innerHTML.includes(u.username)) isLive = true;
                    
                    const statusBadge = isLive 
                        ? `<span class="inline-flex items-center justify-center gap-1.5 bg-green-100/80 text-green-700 text-xs font-bold px-3 py-1.5 rounded-full border border-green-200/50 w-full"><span class="w-1.5 h-1.5 bg-green-500 rounded-full live-pulse"></span> Live</span>`
                        : `<span class="inline-flex items-center justify-center gap-1.5 bg-slate-100/80 text-slate-500 text-xs font-bold px-3 py-1.5 rounded-full border border-slate-200/50 w-full">Offline</span>`;
                        
                    return `
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-white/80 rounded-2xl border border-slate-100/50 shadow-sm hover:shadow-md transition-all gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-8 text-center text-xl font-bold text-slate-400">${rankEmoji}</div>
                            <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-orange-500 to-indigo-500 text-white flex items-center justify-center text-lg font-bold shadow-inner flex-shrink-0">
                                ${u.username.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <div class="font-bold text-slate-800 text-base">${u.username}</div>
                                <div class="text-xs font-medium text-slate-500">All Time: <span class="font-mono text-slate-700">${new Intl.NumberFormat('en-IN').format(u.total_counts)}</span></div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between sm:justify-end gap-6 sm:w-auto w-full pl-12 sm:pl-0">
                            <div class="text-left sm:text-right">
                                <div class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-green-500 to-teal-400">
                                    ${new Intl.NumberFormat('en-IN').format(u.today_count)}
                                </div>
                                <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Today</div>
                            </div>
                            <div class="w-24 text-right">
                                ${statusBadge}
                            </div>
                        </div>
                    </div>`;
                }).join('');
                tbody.innerHTML = `<div class="flex flex-col gap-3">${listHtml}</div>`;
            } else {
                tbody.innerHTML = `<div class="py-8 text-center text-slate-400 font-medium">No activity today yet</div>`;
            }
        }
    } catch (e) {
        console.log('Background stats refresh failed:', e);
    }
}

// Fetch fresh data in the background every 5 seconds (no page reload) to make the whole panel live
setInterval(refreshFullStats, 5000);
</script>

<?php include 'includes/footer.php'; ?>


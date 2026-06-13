<?php
// stats.php - Analytics & Live Stats Dashboard
require_once 'config.php';
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
            <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-users text-blue-600"></i>
            </div>
        </div>
        <p class="text-xs text-slate-500 mt-2">All time registered</p>
    </div>

    <!-- Today's Counts -->
    <div class="stat-card-gradient green bg-white/60 backdrop-blur-xl rounded-2xl p-5 border border-white shadow-sm hover:shadow-md transition-all">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Today's Jaap</p>
                <p class="text-3xl font-extrabold text-emerald-600 mt-1" id="stat-today-counts"><?php echo number_format($stats['today_counts']); ?></p>
            </div>
            <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-om text-emerald-600"></i>
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
                <p class="text-3xl font-extrabold text-purple-600 mt-1" id="stat-live-count"><?php echo $live_count; ?></p>
            </div>
            <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
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
        <p class="text-2xl font-extrabold text-blue-600 mt-1"><?php echo number_format($stats['this_month_counts']); ?></p>
    </div>
    <div class="bg-white/60 backdrop-blur-xl rounded-2xl p-5 border border-white shadow-sm text-center">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">This Year</p>
        <p class="text-2xl font-extrabold text-purple-600 mt-1"><?php echo number_format($stats['this_year_counts']); ?></p>
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
            <h3 class="text-lg font-bold text-slate-800"><i class="fa-solid fa-chart-column text-blue-500 mr-2"></i>Daily Jaap Counts</h3>
            <div class="flex gap-1 bg-slate-100 rounded-lg p-0.5">
                <button class="range-btn text-xs font-semibold px-3 py-1 rounded-md bg-blue-500 text-white" data-range="7">7D</button>
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
                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-blue-500 to-indigo-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
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
        <h3 class="text-lg font-bold text-slate-800 mb-4"><i class="fa-solid fa-chart-line text-purple-500 mr-2"></i>Weekly Trend</h3>
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

<!-- Top Users Table -->
<div class="bg-white/60 backdrop-blur-xl border border-white rounded-2xl shadow-sm overflow-hidden mb-6">
    <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-white/40">
        <h3 class="text-lg font-bold text-slate-800"><i class="fa-solid fa-trophy text-amber-500 mr-2"></i>Top Users Today</h3>
        <span class="text-xs font-semibold text-slate-500"><?php echo date('d M Y'); ?></span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="text-xs uppercase bg-slate-50/50 text-slate-500 border-b border-slate-100">
                <tr>
                    <th class="px-6 py-4 font-semibold">Rank</th>
                    <th class="px-6 py-4 font-semibold">Username</th>
                    <th class="px-6 py-4 font-semibold">Today's Count</th>
                    <th class="px-6 py-4 font-semibold">Total All Time</th>
                    <th class="px-6 py-4 font-semibold">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100/60" id="topUsersBody">
                <?php if (empty($top_users)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-slate-400">No activity today yet</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($top_users as $i => $u): 
                        $rank = $i + 1;
                        $rankEmoji = $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : $rank));
                        $isLive = false;
                        foreach ($live_users as $lu) {
                            if ($lu['username'] === $u['username']) { $isLive = true; break; }
                        }
                    ?>
                    <tr class="hover:bg-white/60 transition-colors">
                        <td class="px-6 py-4 font-bold text-slate-500"><?php echo $rankEmoji; ?></td>
                        <td class="px-6 py-4 font-semibold text-slate-800 flex items-center">
                            <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-3 text-xs font-bold">
                                <?php echo strtoupper(substr($u['username'], 0, 1)); ?>
                            </div>
                            <?php echo htmlspecialchars($u['username']); ?>
                        </td>
                        <td class="px-6 py-4 font-mono font-bold text-emerald-600"><?php echo number_format((int)$u['today_count']); ?></td>
                        <td class="px-6 py-4 font-mono font-medium text-slate-700"><?php echo number_format((int)$u['total_counts']); ?></td>
                        <td class="px-6 py-4">
                            <?php if ($isLive): ?>
                                <span class="inline-flex items-center gap-1.5 bg-green-100 text-green-700 text-xs font-bold px-2.5 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full live-pulse"></span> Live
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-500 text-xs font-bold px-2.5 py-1 rounded-full">
                                    ● Offline
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
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
    const sliced = dailyTrend.slice(-range);
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    dailyChart.data.labels = sliced.map(d => {
        const dt = new Date(d.date);
        return dt.getDate() + ' ' + months[dt.getMonth()];
    });
    dailyChart.data.datasets[0].data = sliced.map(d => parseInt(d.counts) || 0);
    dailyChart.update('active');
}
updateDailyChart(7);

// Range buttons
document.querySelectorAll('.range-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.range-btn').forEach(b => {
            b.classList.remove('bg-blue-500', 'text-white');
            b.classList.add('text-slate-600');
        });
        this.classList.add('bg-blue-500', 'text-white');
        this.classList.remove('text-slate-600');
        updateDailyChart(parseInt(this.dataset.range));
    });
});

// --- Weekly Chart ---
const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
const weeklyGradient = weeklyCtx.createLinearGradient(0, 0, 0, 300);
weeklyGradient.addColorStop(0, 'rgba(139, 92, 246, 0.3)');
weeklyGradient.addColorStop(1, 'rgba(139, 92, 246, 0)');

new Chart(weeklyCtx, {
    type: 'line',
    data: {
        labels: weeklyTrend.map(d => d.week),
        datasets: [{
            data: weeklyTrend.map(d => parseInt(d.counts) || 0),
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

// --- Monthly Chart ---
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
const monthlyGradient = monthlyCtx.createLinearGradient(0, 0, 0, 300);
monthlyGradient.addColorStop(0, 'rgba(245, 158, 11, 0.3)');
monthlyGradient.addColorStop(1, 'rgba(245, 158, 11, 0)');

const monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: monthlyTrend.map(d => {
            const [y, m] = d.month.split('-');
            return monthNames[parseInt(m)-1] + ' ' + y.slice(-2);
        }),
        datasets: [{
            data: monthlyTrend.map(d => parseInt(d.counts) || 0),
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
                list.innerHTML = data.users.map(u => `
                    <div class="flex items-center gap-3 p-3 bg-green-50/60 border border-green-100 rounded-xl fade-in">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-blue-500 to-indigo-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
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

// Auto-reload full page every 60 seconds for fresh stats
setInterval(() => { window.location.reload(); }, 60000);
</script>

<?php include 'includes/footer.php'; ?>

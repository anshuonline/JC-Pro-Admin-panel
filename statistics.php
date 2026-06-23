<?php
// statistics.php - Audience Statistics & Deep Insights
require_once 'config.php';
date_default_timezone_set('Asia/Kolkata');
$conn->query("SET time_zone = '+05:30'");
check_auth();

// ====== EXISTING AUDIENCE FETCHING ======
$new_users_daily = [];
$res = $conn->query("SELECT DATE(created_at) as d, COUNT(id) as c 
                     FROM users 
                     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                     GROUP BY DATE(created_at) ORDER BY d ASC");
if ($res) while ($row = $res->fetch_assoc()) $new_users_daily[] = $row;

$new_users_weekly = [];
$res = $conn->query("SELECT YEARWEEK(created_at, 1) as yw, MIN(DATE(created_at)) as d, COUNT(id) as c 
                     FROM users 
                     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK) 
                     GROUP BY YEARWEEK(created_at, 1) ORDER BY yw ASC");
if ($res) while ($row = $res->fetch_assoc()) $new_users_weekly[] = $row;

$new_users_monthly = [];
$res = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(id) as c 
                     FROM users 
                     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) 
                     GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY ym ASC");
if ($res) while ($row = $res->fetch_assoc()) $new_users_monthly[] = $row;

$active_daily = [];
$res = $conn->query("SELECT date as d, COUNT(DISTINCT user_id) as c 
                     FROM daily_counts 
                     WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                     GROUP BY date ORDER BY date ASC");
if ($res) while ($row = $res->fetch_assoc()) $active_daily[] = $row;

$active_weekly = [];
$res = $conn->query("SELECT YEARWEEK(date, 1) as yw, MIN(date) as d, COUNT(DISTINCT user_id) as c 
                     FROM daily_counts 
                     WHERE date >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK) 
                     GROUP BY YEARWEEK(date, 1) ORDER BY yw ASC");
if ($res) while ($row = $res->fetch_assoc()) $active_weekly[] = $row;

$active_monthly = [];
$res = $conn->query("SELECT DATE_FORMAT(date, '%Y-%m') as ym, COUNT(DISTINCT user_id) as c 
                     FROM daily_counts 
                     WHERE date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) 
                     GROUP BY DATE_FORMAT(date, '%Y-%m') ORDER BY ym ASC");
if ($res) while ($row = $res->fetch_assoc()) $active_monthly[] = $row;


// ====== ADVANCED ANALYSIS METRICS ======

// 1. App Stickiness (DAU/MAU Ratio)
$dau = 0; $mau = 0;
$res = $conn->query("SELECT COUNT(DISTINCT user_id) as c FROM daily_counts WHERE date = CURDATE()");
if ($res && $row = $res->fetch_assoc()) $dau = (int)$row['c'];

$res = $conn->query("SELECT COUNT(DISTINCT user_id) as c FROM daily_counts WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
if ($res && $row = $res->fetch_assoc()) $mau = (int)$row['c'];

$stickiness = ($mau > 0) ? round(($dau / $mau) * 100, 1) : 0;

// 2. Peak Chanting Hours (from analytics_events)
$peak_hours = array_fill(0, 24, 0);
$res = $conn->query("SELECT HOUR(created_at) as h, COUNT(*) as c 
                     FROM analytics_events 
                     WHERE event_type IN ('count_tap', 'app_open') 
                     GROUP BY HOUR(created_at)");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $peak_hours[(int)$row['h']] = (int)$row['c'];
    }
}

// 3. User Retention Rate (7-Day)
$cohort_size = 0;
$retention_count = 0;
// Users registered exactly 7 days ago
$res = $conn->query("SELECT id FROM users WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
if ($res && $res->num_rows > 0) {
    $cohort_size = $res->num_rows;
    $ids = [];
    while ($row = $res->fetch_assoc()) $ids[] = $row['id'];
    $ids_str = implode(',', $ids);
    
    // How many of them are active today?
    $res2 = $conn->query("SELECT COUNT(DISTINCT user_id) as c FROM daily_counts WHERE date = CURDATE() AND user_id IN ($ids_str)");
    if ($res2 && $row2 = $res2->fetch_assoc()) {
        $retention_count = (int)$row2['c'];
    }
}
$retention_rate = ($cohort_size > 0) ? round(($retention_count / $cohort_size) * 100, 1) : 0;

include 'includes/header.php';
?>

<style>
    .chart-container { position: relative; height: 350px; width: 100%; }
    .small-chart { position: relative; height: 200px; width: 100%; }
    .filter-btn { transition: all 0.2s; }
</style>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Audience Statistics & Deep Insights</h1>
    <p class="text-sm text-slate-500 mt-0.5">See detailed reports about your app's growth, retention, and activity patterns.</p>
</div>

<!-- ================== ADVANCED INSIGHTS ROW ================== -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    
    <!-- Stickiness Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col items-center justify-center text-center">
        <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-2">App Stickiness (DAU/MAU)</h3>
        <div class="relative w-32 h-32 flex items-center justify-center">
            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                <path class="text-slate-100" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                <path class="text-indigo-500" stroke-dasharray="<?php echo $stickiness; ?>, 100" stroke-width="3" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
            </svg>
            <div class="absolute text-2xl font-black text-slate-800"><?php echo $stickiness; ?>%</div>
        </div>
        <p class="text-xs text-slate-500 mt-4">Percentage of monthly users who use the app daily. >20% is excellent.</p>
    </div>

    <!-- Retention Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col items-center justify-center text-center">
        <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-2">7-Day Retention Rate</h3>
        <div class="relative w-32 h-32 flex items-center justify-center">
            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                <path class="text-slate-100" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                <path class="text-emerald-500" stroke-dasharray="<?php echo $retention_rate; ?>, 100" stroke-width="3" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
            </svg>
            <div class="absolute text-2xl font-black text-slate-800"><?php echo $retention_rate; ?>%</div>
        </div>
        <p class="text-xs text-slate-500 mt-4">Users who installed exactly 7 days ago and are active today: <?php echo $retention_count; ?>/<?php echo $cohort_size; ?></p>
    </div>

    <!-- Peak Hours Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-4 text-center">Peak Chanting Hours</h3>
        <div class="small-chart">
            <canvas id="peakHoursChart"></canvas>
        </div>
    </div>
</div>


<!-- ================== TIME SERIES CHARTS ================== -->

<!-- NEW USERS CHART CARD -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm mb-8 overflow-hidden">
    <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-lg font-bold text-slate-800">Installed Audience (First Time Users)</h2>
            <p class="text-xs text-slate-500 mt-1">Unique new users who registered in the app</p>
        </div>
        <div class="flex gap-2 bg-slate-100 p-1 rounded-lg">
            <button class="filter-btn new-filter active bg-blue-500 text-white text-xs font-semibold px-4 py-1.5 rounded-md" data-view="daily">Daily</button>
            <button class="filter-btn new-filter text-xs font-semibold px-4 py-1.5 rounded-md text-slate-600" data-view="weekly">Weekly</button>
            <button class="filter-btn new-filter text-xs font-semibold px-4 py-1.5 rounded-md text-slate-600" data-view="monthly">Monthly</button>
        </div>
    </div>
    <div class="p-6">
        <div class="chart-container">
            <canvas id="newUsersChart"></canvas>
        </div>
    </div>
</div>

<!-- ACTIVE USERS CHART CARD -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm mb-8 overflow-hidden">
    <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-lg font-bold text-slate-800">Live Active Users (DAU / WAU / MAU)</h2>
            <p class="text-xs text-slate-500 mt-1">Unique users who were active per interval</p>
        </div>
        <div class="flex gap-2 bg-slate-100 p-1 rounded-lg">
            <button class="filter-btn active-filter active bg-emerald-500 text-white text-xs font-semibold px-4 py-1.5 rounded-md" data-view="daily">Daily</button>
            <button class="filter-btn active-filter text-xs font-semibold px-4 py-1.5 rounded-md text-slate-600" data-view="weekly">Weekly</button>
            <button class="filter-btn active-filter text-xs font-semibold px-4 py-1.5 rounded-md text-slate-600" data-view="monthly">Monthly</button>
        </div>
    </div>
    <div class="p-6">
        <div class="chart-container">
            <canvas id="activeUsersChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
// Peak Hours Chart
const peakData = <?php echo json_encode(array_values($peak_hours)); ?>;
const peakLabels = ['12AM','1','2','3','4','5','6','7','8','9','10','11','12PM','1','2','3','4','5','6','7','8','9','10','11'];

const peakCtx = document.getElementById('peakHoursChart').getContext('2d');
new Chart(peakCtx, {
    type: 'bar',
    data: {
        labels: peakLabels,
        datasets: [{
            data: peakData,
            backgroundColor: 'rgba(245, 158, 11, 0.8)',
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 9 }, maxRotation: 0 } },
            y: { display: false }
        }
    }
});

// Data injected from PHP
const dataStore = {
    new: {
        daily: <?php echo json_encode($new_users_daily); ?>,
        weekly: <?php echo json_encode($new_users_weekly); ?>,
        monthly: <?php echo json_encode($new_users_monthly); ?>
    },
    active: {
        daily: <?php echo json_encode($active_daily); ?>,
        weekly: <?php echo json_encode($active_weekly); ?>,
        monthly: <?php echo json_encode($active_monthly); ?>
    }
};

const chartFont = { family: 'Inter, sans-serif' };
const monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

// Helper to format labels
function formatLabel(row, view) {
    if (view === 'daily') {
        const dt = new Date(row.d);
        return dt.getDate() + ' ' + monthNames[dt.getMonth()];
    } else if (view === 'weekly') {
        const dt = new Date(row.d);
        return 'Wk of ' + dt.getDate() + ' ' + monthNames[dt.getMonth()];
    } else {
        const [y, m] = row.ym.split('-');
        return monthNames[parseInt(m)-1] + ' ' + y;
    }
}

// Chart Configurations
function createChartConfig(colorLine, colorBg) {
    return {
        type: 'line',
        data: { labels: [], datasets: [{
            label: 'Users',
            data: [],
            borderColor: colorLine,
            backgroundColor: colorBg,
            borderWidth: 2,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: colorLine,
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: true,
            tension: 0.3
        }]},
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleFont: { ...chartFont, weight: '600', size: 13 },
                    bodyFont: { ...chartFont, size: 12 },
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: false
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { ...chartFont, size: 11 } } },
                y: { grid: { color: 'rgba(0,0,0,0.04)' }, border: { dash: [4, 4] }, ticks: { color: '#94a3b8', font: { ...chartFont, size: 11 }, beginAtZero: true } }
            }
        }
    };
}

// Initialize Charts
const newCtx = document.getElementById('newUsersChart').getContext('2d');
const newGrad = newCtx.createLinearGradient(0,0,0,350);
newGrad.addColorStop(0, 'rgba(59, 130, 246, 0.2)');
newGrad.addColorStop(1, 'rgba(59, 130, 246, 0)');
const newChart = new Chart(newCtx, createChartConfig('#3b82f6', newGrad));

const actCtx = document.getElementById('activeUsersChart').getContext('2d');
const actGrad = actCtx.createLinearGradient(0,0,0,350);
actGrad.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
actGrad.addColorStop(1, 'rgba(16, 185, 129, 0)');
const actChart = new Chart(actCtx, createChartConfig('#10b981', actGrad));

// Update Chart Function
function updateChart(chartInstance, type, view) {
    const dataset = dataStore[type][view] || [];
    chartInstance.data.labels = dataset.map(row => formatLabel(row, view));
    chartInstance.data.datasets[0].data = dataset.map(row => parseInt(row.c) || 0);
    chartInstance.update();
}

// Initial Render
updateChart(newChart, 'new', 'daily');
updateChart(actChart, 'active', 'daily');

// Event Listeners for Filters
document.querySelectorAll('.new-filter').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.new-filter').forEach(b => {
            b.classList.remove('active', 'bg-blue-500', 'text-white');
            b.classList.add('text-slate-600');
        });
        this.classList.remove('text-slate-600');
        this.classList.add('active', 'bg-blue-500', 'text-white');
        updateChart(newChart, 'new', this.dataset.view);
    });
});

document.querySelectorAll('.active-filter').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.active-filter').forEach(b => {
            b.classList.remove('active', 'bg-emerald-500', 'text-white');
            b.classList.add('text-slate-600');
        });
        this.classList.remove('text-slate-600');
        this.classList.add('active', 'bg-emerald-500', 'text-white');
        updateChart(actChart, 'active', this.dataset.view);
    });
});
</script>

<?php include 'includes/footer.php'; ?>

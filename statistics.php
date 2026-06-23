<?php
// statistics.php - Audience Statistics
require_once 'config.php';
date_default_timezone_set('Asia/Kolkata');
$conn->query("SET time_zone = '+05:30'");
check_auth();

// Fetch New Users (First Time Users) Data
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


// Fetch Active Users Data (DAU, WAU, MAU)
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

include 'includes/header.php';
?>

<style>
    .chart-container { position: relative; height: 350px; width: 100%; }
    .filter-btn { transition: all 0.2s; }
    .filter-btn.active { background-color: #3b82f6; color: white; border-color: #3b82f6; }
</style>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Audience Statistics</h1>
    <p class="text-sm text-slate-500 mt-0.5">See detailed reports about your app's growth and active users.</p>
</div>

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
            <button class="filter-btn active-filter active bg-blue-500 text-white text-xs font-semibold px-4 py-1.5 rounded-md" data-view="daily">Daily</button>
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
            b.classList.remove('active', 'bg-blue-500', 'text-white');
            b.classList.add('text-slate-600');
        });
        this.classList.remove('text-slate-600');
        this.classList.add('active', 'bg-blue-500', 'text-white');
        updateChart(actChart, 'active', this.dataset.view);
    });
});
</script>

<?php include 'includes/footer.php'; ?>

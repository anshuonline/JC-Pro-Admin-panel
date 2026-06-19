<?php
// F:\APPS\JC Pro Admin panel\dashboard.php
require_once 'config.php';
check_auth();

// Fetch statistics
$stats = [
    'users' => 0,
    'total_counts' => 0,
    'challenges' => 0,
    'pages' => 0,
    'today_counts' => 0
];

// Total Users and Counts
$res = $conn->query("SELECT COUNT(*) as u_count, SUM(total_counts) as t_counts FROM users");
if ($res && $row = $res->fetch_assoc()) {
    $stats['users'] = $row['u_count'] ?: 0;
    $stats['total_counts'] = $row['t_counts'] ?: 0;
}

// Total Challenges
$res = $conn->query("SELECT COUNT(*) as c_count FROM challenges");
if ($res && $row = $res->fetch_assoc()) {
    $stats['challenges'] = $row['c_count'] ?: 0;
}

// Total Content Pages
$res = $conn->query("SELECT COUNT(*) as p_count FROM content_pages");
if ($res && $row = $res->fetch_assoc()) {
    $stats['pages'] = $row['p_count'] ?: 0;
}

// Today's total counts across all users
$today = date('Y-m-d');
$res = $conn->query("SELECT SUM(daily_count) as today_sum FROM daily_counts WHERE date = '$today'");
if ($res && $row = $res->fetch_assoc()) {
    $stats['today_counts'] = $row['today_sum'] ?: 0;
}

// Recent Users
$recent_users = [];
$res = $conn->query("SELECT username, total_counts, level, last_active FROM users ORDER BY last_active DESC LIMIT 5");
if ($res) {
    while($row = $res->fetch_assoc()) {
        $recent_users[] = $row;
    }
}

if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');
    echo json_encode([
        'stats' => $stats,
        'recent_users' => $recent_users
    ]);
    exit;
}

include 'includes/header.php';
?>

<style>
/* 90s Classic Theme Override */
body.theme-90s {
    background: #008080 !important;
    font-family: 'Tahoma', 'MS Sans Serif', sans-serif !important;
}
body.theme-90s header {
    background: #c0c0c0 !important;
    border-bottom: 2px solid #000000 !important;
}
body.theme-90s aside {
    background: #c0c0c0 !important;
    border-right: 2px solid #000000 !important;
}
body.theme-90s .bg-white, body.theme-90s .bg-slate-50, body.theme-90s .bg-slate-100 {
    background: #c0c0c0 !important;
    border: 2px solid !important;
    border-color: #ffffff #808080 #808080 #ffffff !important;
    box-shadow: none !important;
    border-radius: 0 !important;
}
body.theme-90s .text-slate-800, body.theme-90s .text-slate-600, body.theme-90s .text-slate-500 {
    color: #000000 !important;
}
body.theme-90s .text-orange-500, body.theme-90s .text-orange-600, body.theme-90s .text-green-500, body.theme-90s .text-green-600 {
    color: #000080 !important;
}
body.theme-90s table {
    background: #ffffff !important;
    border: 2px solid #000000 !important;
    border-collapse: collapse;
}
body.theme-90s th {
    background: #c0c0c0 !important;
    border: 2px solid !important;
    border-color: #ffffff #808080 #808080 #ffffff !important;
    color: #000000 !important;
}
body.theme-90s td {
    background: #ffffff !important;
    border: 1px solid #c0c0c0 !important;
}
body.theme-90s .rounded-lg, body.theme-90s .rounded-xl, body.theme-90s .rounded-2xl, body.theme-90s .rounded-full {
    border-radius: 0 !important;
}
body.theme-90s .border-t-4 {
    border-top: 2px solid #ffffff !important;
}
.btn-90s-toggle {
    transition: all 0.2s;
}
body.theme-90s .btn-90s-toggle {
    background: #c0c0c0 !important;
    border: 2px solid !important;
    border-color: #ffffff #808080 #808080 #ffffff !important;
    color: #000000 !important;
    font-weight: bold;
    border-radius: 0 !important;
    padding: 4px 12px;
}
body.theme-90s .btn-90s-toggle:active {
    border-color: #808080 #ffffff #ffffff #808080 !important;
}
</style>

<div class="flex justify-end mb-6">
    <button id="themeToggleBtn" class="btn-90s-toggle bg-slate-800 text-white px-4 py-2 rounded-lg shadow-sm text-sm font-semibold hover:bg-slate-700">
        <i class="fa-solid fa-desktop mr-2"></i>Switch to 90s Look
    </button>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-6 mb-8">
    <!-- Stat Card 1 -->
    <div class="bg-white rounded-lg p-6 border-t-4 border-orange-500 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
            <i class="fa-solid fa-users text-6xl text-orange-500"></i>
        </div>
        <h3 class="text-slate-500 text-sm font-semibold mb-1">Total Users</h3>
        <p class="text-3xl font-bold text-slate-800" id="dash-users"><?php echo formatNumberShort($stats['users']); ?></p>
        <div class="mt-4 flex items-center text-sm font-medium">
            <span class="text-orange-600 flex items-center"><i class="fa-solid fa-arrow-trend-up mr-1.5"></i> Registered</span>
        </div>
    </div>

    <!-- Stat Card 2 -->
    <div class="bg-white rounded-lg p-6 border-t-4 border-green-600 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
            <i class="fa-solid fa-om text-6xl text-green-500"></i>
        </div>
        <h3 class="text-slate-500 text-sm font-semibold mb-1">Total Jaap Count</h3>
        <p class="text-3xl font-bold text-slate-800" id="dash-total"><?php echo formatNumberShort($stats['total_counts']); ?></p>
        <div class="mt-4 flex items-center text-sm font-medium">
            <span class="text-green-600 flex items-center"><i class="fa-solid fa-calendar-day mr-1.5"></i> <span id="dash-today" class="mx-1"><?php echo formatNumberShort($stats['today_counts']); ?></span> Today</span>
        </div>
    </div>


    <!-- Stat Card 4 -->
    <div class="bg-white rounded-lg p-6 border-t-4 border-slate-500 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
            <i class="fa-solid fa-file-lines text-6xl text-slate-500"></i>
        </div>
        <h3 class="text-slate-500 text-sm font-semibold mb-1">Content Pages</h3>
        <p class="text-3xl font-bold text-slate-800" id="dash-pages"><?php echo formatNumberShort($stats['pages']); ?></p>
        <div class="mt-4 flex items-center text-sm font-medium">
            <a href="content.php" class="text-slate-600 hover:text-purple-700 transition-colors">Manage Content <i class="fa-solid fa-arrow-right ml-1 text-xs"></i></a>
        </div>
    </div>
</div>

<div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
        <h3 class="text-lg font-bold text-slate-800">Recently Active Users</h3>
        <a href="users.php" class="text-sm font-semibold text-orange-600 hover:text-blue-700 transition-colors">View All</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="text-xs uppercase bg-slate-100 text-slate-600 border-b border-slate-200">
                <tr>
                    <th scope="col" class="px-6 py-4 font-semibold">Username</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Level</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Total Counts</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Last Active</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200" id="dash-recent-users">
                <?php if(empty($recent_users)): ?>
                <tr>
                    <td colspan="4" class="px-6 py-6 text-center text-slate-500">No users found.</td>
                </tr>
                <?php else: ?>
                    <?php foreach($recent_users as $user): ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 font-semibold text-slate-800 flex items-center">
                            <div class="w-9 h-9 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center mr-3 font-bold border border-blue-200/50 shadow-sm">
                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                            </div>
                            <?php echo htmlspecialchars($user['username']); ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="bg-slate-100 text-slate-600 py-1 px-3 rounded-full text-xs font-bold border border-slate-200/60">
                                Lvl <?php echo $user['level']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 font-mono font-medium text-slate-700">
                            <?php echo formatNumberShort($user['total_counts']); ?>
                        </td>
                        <td class="px-6 py-4 text-slate-500 text-xs font-medium">
                            <?php echo date('M d, Y H:i', strtotime($user['last_active'])); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
async function refreshDashboard() {
    try {
        const res = await fetch('dashboard.php?ajax=1');
        const data = await res.json();
        if (data.stats) {
            document.getElementById('dash-users').textContent = formatNumberShort(data.stats.users || 0);
            document.getElementById('dash-total').textContent = formatNumberShort(data.stats.total_counts || 0);
            document.getElementById('dash-today').textContent = formatNumberShort(data.stats.today_counts || 0);
            document.getElementById('dash-pages').textContent = formatNumberShort(data.stats.pages || 0);
        }
        if (data.recent_users) {
            const tbody = document.getElementById('dash-recent-users');
            if (data.recent_users.length > 0) {
                tbody.innerHTML = data.recent_users.map(user => {
                    const firstChar = user.username ? user.username.charAt(0).toUpperCase() : '?';
                    const dateObj = new Date(user.last_active);
                    const formattedDate = dateObj.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false });
                    
                    return `
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 font-semibold text-slate-800 flex items-center">
                            <div class="w-9 h-9 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center mr-3 font-bold border border-blue-200/50 shadow-sm">
                                ${firstChar}
                            </div>
                            ${user.username}
                        </td>
                        <td class="px-6 py-4">
                            <span class="bg-slate-100 text-slate-600 py-1 px-3 rounded-full text-xs font-bold border border-slate-200/60">
                                Lvl ${user.level}
                            </span>
                        </td>
                        <td class="px-6 py-4 font-mono font-medium text-slate-700">
                            ${formatNumberShort(user.total_counts || 0)}
                        </td>
                        <td class="px-6 py-4 text-slate-500 text-xs font-medium">
                            ${formattedDate}
                        </td>
                    </tr>`;
                }).join('');
            } else {
                tbody.innerHTML = `<tr><td colspan="4" class="px-6 py-6 text-center text-slate-500">No users found.</td></tr>`;
            }
        }
    } catch (e) {
        console.error('Dashboard live refresh failed:', e);
    }
}

// Theme toggling logic
const themeToggleBtn = document.getElementById('themeToggleBtn');
let is90sTheme = localStorage.getItem('theme-90s') === 'true';

function updateTheme() {
    if (is90sTheme) {
        document.body.classList.add('theme-90s');
        themeToggleBtn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles mr-2"></i>Switch to New Look';
    } else {
        document.body.classList.remove('theme-90s');
        themeToggleBtn.innerHTML = '<i class="fa-solid fa-desktop mr-2"></i>Switch to 90s Look';
    }
}

themeToggleBtn.addEventListener('click', () => {
    is90sTheme = !is90sTheme;
    localStorage.setItem('theme-90s', is90sTheme);
    updateTheme();
});

// Init theme on load
updateTheme();

// Fetch fresh data in the background every 5 seconds (no page reload)
setInterval(refreshDashboard, 5000);
</script>

<?php include 'includes/footer.php'; ?>


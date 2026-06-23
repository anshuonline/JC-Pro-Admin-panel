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
/* ==================================================
   ULTRA-MODERN AMOLED GLASSMORPHISM THEME
   ================================================== */
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap');

body.amoled-theme {
    background-color: #030303 !important;
    /* Deep space background with subtle neon mesh gradients */
    background-image: 
        radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.12) 0px, transparent 50%),
        radial-gradient(at 100% 0%, rgba(236, 72, 153, 0.12) 0px, transparent 50%),
        radial-gradient(at 50% 100%, rgba(234, 88, 12, 0.1) 0px, transparent 50%) !important;
    background-attachment: fixed !important;
    color: #ffffff !important;
    font-family: 'Outfit', 'Inter', sans-serif !important;
}

/* Override Header & Sidebar to blend with background */
body.amoled-theme header, 
body.amoled-theme aside,
body.amoled-theme .bg-white\/60,
body.amoled-theme .bg-white {
    background-color: rgba(10, 10, 10, 0.7) !important;
    border-color: rgba(255, 255, 255, 0.05) !important;
    backdrop-filter: blur(20px) !important;
    -webkit-backdrop-filter: blur(20px) !important;
}

/* Next-Gen Glassmorphism Cards */
body.amoled-theme .mui-card {
    background: linear-gradient(145deg, rgba(30,30,30,0.6) 0%, rgba(15,15,15,0.8) 100%) !important;
    backdrop-filter: blur(16px) !important;
    -webkit-backdrop-filter: blur(16px) !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-radius: 24px !important;
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.4) !important;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
body.amoled-theme .mui-card:hover {
    transform: translateY(-6px) scale(1.02);
    box-shadow: 0 15px 45px 0 rgba(0, 0, 0, 0.6) !important;
    border-color: rgba(255, 255, 255, 0.2) !important;
}

/* Vibrant Typography */
body.amoled-theme .text-slate-800,
body.amoled-theme h1, body.amoled-theme h2, body.amoled-theme h3 {
    color: #ffffff !important;
    letter-spacing: -0.5px !important;
}
body.amoled-theme .text-slate-500, 
body.amoled-theme .text-slate-600 {
    color: #94a3b8 !important;
}

/* Neon Glowing Icons & Accents */
body.amoled-theme .text-orange-500 { color: #f97316 !important; filter: drop-shadow(0 0 12px rgba(249,115,22,0.4)); }
body.amoled-theme .text-green-500 { color: #10b981 !important; filter: drop-shadow(0 0 12px rgba(16,185,129,0.4)); }
body.amoled-theme .text-blue-500 { color: #3b82f6 !important; filter: drop-shadow(0 0 12px rgba(59,130,246,0.4)); }
body.amoled-theme .text-purple-500 { color: #a855f7 !important; filter: drop-shadow(0 0 12px rgba(168,85,247,0.4)); }

/* Premium Table Overrides */
body.amoled-theme table th {
    background-color: transparent !important;
    color: #94a3b8 !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: 0.75rem;
}
body.amoled-theme table td {
    border-bottom: 1px solid rgba(255, 255, 255, 0.03) !important;
}
body.amoled-theme tr.hover\:bg-slate-50:hover {
    background-color: rgba(255, 255, 255, 0.03) !important;
}

/* Smooth Badges / Chips */
body.amoled-theme .bg-slate-100 {
    background-color: rgba(255, 255, 255, 0.05) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: #e2e8f0 !important;
}
body.amoled-theme .bg-orange-100 { background-color: rgba(249, 115, 22, 0.15) !important; }
body.amoled-theme .bg-green-100 { background-color: rgba(16, 185, 129, 0.15) !important; }

/* Gradient Headers */
body.amoled-theme h1.text-2xl {
    background: linear-gradient(to right, #ffffff, #a5b4fc);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-weight: 800;
}
</style>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Dashboard</h1>
        <p class="text-sm text-slate-500 mt-0.5">Overview of your application stats</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-6 mb-8">
    <!-- Stat Card 1 -->
    <div class="mui-card bg-white p-6 relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="fa-solid fa-users text-6xl text-orange-500"></i>
        </div>
        <h3 class="text-slate-500 text-sm font-semibold mb-1 uppercase tracking-wider">Total Users</h3>
        <p class="text-4xl font-bold text-slate-800 tracking-tight" id="dash-users"><?php echo formatNumberShort($stats['users']); ?></p>
        <div class="mt-4 flex items-center text-sm font-medium">
            <span class="text-orange-500 flex items-center bg-orange-100 px-2 py-1 rounded-md text-xs"><i class="fa-solid fa-arrow-trend-up mr-1.5"></i> Registered</span>
        </div>
    </div>

    <!-- Stat Card 2 -->
    <div class="mui-card bg-white p-6 relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="fa-solid fa-om text-6xl text-green-500"></i>
        </div>
        <h3 class="text-slate-500 text-sm font-semibold mb-1 uppercase tracking-wider">Total Jaap Count</h3>
        <p class="text-4xl font-bold text-slate-800 tracking-tight" id="dash-total"><?php echo formatNumberShort($stats['total_counts']); ?></p>
        <div class="mt-4 flex items-center text-sm font-medium">
            <span class="text-green-500 flex items-center bg-green-100 px-2 py-1 rounded-md text-xs"><i class="fa-solid fa-calendar-day mr-1.5"></i> <span id="dash-today" class="mx-1"><?php echo formatNumberShort($stats['today_counts']); ?></span> Today</span>
        </div>
    </div>

    <!-- Stat Card 3 -->
    <div class="mui-card bg-white p-6 relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="fa-solid fa-file-lines text-6xl text-blue-500"></i>
        </div>
        <h3 class="text-slate-500 text-sm font-semibold mb-1 uppercase tracking-wider">Content Pages</h3>
        <p class="text-4xl font-bold text-slate-800 tracking-tight" id="dash-pages"><?php echo formatNumberShort($stats['pages']); ?></p>
        <div class="mt-4 flex items-center text-sm font-medium">
            <a href="content.php" class="text-blue-500 hover:text-blue-400 transition-colors flex items-center bg-blue-100/10 px-2 py-1 rounded-md text-xs">Manage Content <i class="fa-solid fa-arrow-right ml-1"></i></a>
        </div>
    </div>
</div>

<div class="mui-card bg-white overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-200 flex justify-between items-center bg-transparent">
        <h3 class="text-lg font-bold text-slate-800 tracking-wide">Recently Active Users</h3>
        <a href="users.php" class="text-sm font-bold text-indigo-500 hover:text-indigo-400 transition-colors uppercase tracking-wider">View All</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="text-xs uppercase bg-slate-50 text-slate-500 tracking-wider">
                <tr>
                    <th scope="col" class="px-6 py-4 font-semibold">Username</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Level</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Total Counts</th>
                    <th scope="col" class="px-6 py-4 font-semibold text-right">Last Active</th>
                </tr>
            </thead>
            <tbody id="dash-recent-users">
                <?php if(empty($recent_users)): ?>
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-slate-500">No users found.</td>
                </tr>
                <?php else: ?>
                    <?php foreach($recent_users as $user): ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 font-semibold text-slate-800 flex items-center">
                            <div class="w-10 h-10 rounded-full bg-orange-100 text-orange-500 flex items-center justify-center mr-4 font-bold text-lg shadow-sm">
                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                            </div>
                            <?php echo htmlspecialchars($user['username']); ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="bg-slate-100 text-slate-600 py-1.5 px-3 rounded-md text-xs font-bold tracking-wide">
                                Lvl <?php echo $user['level']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 font-mono font-medium text-slate-700 text-base">
                            <?php echo formatNumberShort($user['total_counts']); ?>
                        </td>
                        <td class="px-6 py-4 text-slate-500 text-xs font-medium text-right">
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
                            <div class="w-10 h-10 rounded-full bg-orange-100 text-orange-500 flex items-center justify-center mr-4 font-bold text-lg shadow-sm">
                                ${firstChar}
                            </div>
                            ${user.username}
                        </td>
                        <td class="px-6 py-4">
                            <span class="bg-slate-100 text-slate-600 py-1.5 px-3 rounded-md text-xs font-bold tracking-wide">
                                Lvl ${user.level}
                            </span>
                        </td>
                        <td class="px-6 py-4 font-mono font-medium text-slate-700 text-base">
                            ${formatNumberShort(user.total_counts || 0)}
                        </td>
                        <td class="px-6 py-4 text-slate-500 text-xs font-medium text-right">
                            ${formattedDate}
                        </td>
                    </tr>`;
                }).join('');
            } else {
                tbody.innerHTML = `<tr><td colspan="4" class="px-6 py-8 text-center text-slate-500">No users found.</td></tr>`;
            }
        }
    } catch (e) {
        console.error('Dashboard live refresh failed:', e);
    }
}

// Force AMOLED Theme permanently on Dashboard
document.body.classList.add('amoled-theme');

// Fetch fresh data in the background every 5 seconds
setInterval(refreshDashboard, 5000);
</script>

<?php include 'includes/footer.php'; ?>


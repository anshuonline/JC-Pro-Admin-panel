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

include 'includes/header.php';
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Stat Card 1 -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 shadow-sm relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="fa-solid fa-users text-6xl text-indigo-500"></i>
        </div>
        <h3 class="text-gray-400 text-sm font-medium mb-1">Total Users</h3>
        <p class="text-3xl font-bold text-white"><?php echo number_format($stats['users']); ?></p>
        <div class="mt-4 flex items-center text-sm">
            <span class="text-indigo-400 flex items-center"><i class="fa-solid fa-arrow-trend-up mr-1"></i> Registered</span>
        </div>
    </div>

    <!-- Stat Card 2 -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 shadow-sm relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="fa-solid fa-om text-6xl text-green-500"></i>
        </div>
        <h3 class="text-gray-400 text-sm font-medium mb-1">Total Mantras Counted</h3>
        <p class="text-3xl font-bold text-white"><?php echo number_format($stats['total_counts']); ?></p>
        <div class="mt-4 flex items-center text-sm">
            <span class="text-green-400 flex items-center"><i class="fa-solid fa-calendar-day mr-1"></i> <?php echo number_format($stats['today_counts']); ?> Today</span>
        </div>
    </div>

    <!-- Stat Card 3 -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 shadow-sm relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="fa-solid fa-trophy text-6xl text-yellow-500"></i>
        </div>
        <h3 class="text-gray-400 text-sm font-medium mb-1">Active Challenges</h3>
        <p class="text-3xl font-bold text-white"><?php echo number_format($stats['challenges']); ?></p>
        <div class="mt-4 flex items-center text-sm">
            <a href="challenges.php" class="text-yellow-400 hover:text-yellow-300">View Challenges <i class="fa-solid fa-arrow-right ml-1 text-xs"></i></a>
        </div>
    </div>

    <!-- Stat Card 4 -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 shadow-sm relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="fa-solid fa-file-lines text-6xl text-purple-500"></i>
        </div>
        <h3 class="text-gray-400 text-sm font-medium mb-1">Content Pages</h3>
        <p class="text-3xl font-bold text-white"><?php echo number_format($stats['pages']); ?></p>
        <div class="mt-4 flex items-center text-sm">
            <a href="content.php" class="text-purple-400 hover:text-purple-300">Manage Content <i class="fa-solid fa-arrow-right ml-1 text-xs"></i></a>
        </div>
    </div>
</div>

<div class="bg-gray-800 border border-gray-700 rounded-xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center">
        <h3 class="text-lg font-medium text-white">Recently Active Users</h3>
        <a href="users.php" class="text-sm text-indigo-400 hover:text-indigo-300">View All</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-400">
            <thead class="text-xs uppercase bg-gray-700/50 text-gray-300">
                <tr>
                    <th scope="col" class="px-6 py-3">Username</th>
                    <th scope="col" class="px-6 py-3">Level</th>
                    <th scope="col" class="px-6 py-3">Total Counts</th>
                    <th scope="col" class="px-6 py-3">Last Active</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($recent_users)): ?>
                <tr class="border-b border-gray-700 hover:bg-gray-700/30">
                    <td colspan="4" class="px-6 py-4 text-center">No users found.</td>
                </tr>
                <?php else: ?>
                    <?php foreach($recent_users as $user): ?>
                    <tr class="border-b border-gray-700 hover:bg-gray-700/30 transition-colors">
                        <td class="px-6 py-4 font-medium text-white flex items-center">
                            <div class="w-8 h-8 rounded-full bg-indigo-500/20 text-indigo-400 flex items-center justify-center mr-3 font-bold">
                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                            </div>
                            <?php echo htmlspecialchars($user['username']); ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="bg-gray-700 text-gray-300 py-1 px-2.5 rounded-full text-xs font-semibold border border-gray-600">
                                Lvl <?php echo $user['level']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 font-mono">
                            <?php echo number_format($user['total_counts']); ?>
                        </td>
                        <td class="px-6 py-4 text-gray-500">
                            <?php echo date('M d, Y H:i', strtotime($user['last_active'])); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

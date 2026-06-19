<?php
require_once 'config.php';
check_auth();

// Fetch current config
$status = 'ACTIVE';
$challenge_start = date('Y-m-01 00:00:00');
$challenge_end = date('Y-m-t 23:59:59');

try {
    $cfg_res = $conn->query("SELECT status, challenge_start, challenge_end FROM leaderboard_config LIMIT 1");
    if ($cfg_res && $cfg_res->num_rows > 0) {
        $cfg_row = $cfg_res->fetch_assoc();
        $status = $cfg_row['status'];
        $challenge_start = $cfg_row['challenge_start'];
        $challenge_end = $cfg_row['challenge_end'];
    }
} catch (Throwable $e) {
    // Try to add column if it doesn't exist
    try {
        $conn->query("ALTER TABLE leaderboard_config ADD COLUMN challenge_end datetime NOT NULL DEFAULT '2099-12-31 23:59:59'");
    } catch (Throwable $e1) {}
    
    // Table doesn't exist yet, auto-create it safely
    try {
        $conn->query("CREATE TABLE IF NOT EXISTS `leaderboard_config` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `status` enum('ACTIVE','RESULTS','WAITING') NOT NULL DEFAULT 'ACTIVE',
            `challenge_start` datetime NOT NULL,
            `challenge_end` datetime NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $conn->query("INSERT IGNORE INTO leaderboard_config (id, status, challenge_start, challenge_end) VALUES (1, 'ACTIVE', '$challenge_start', '$challenge_end')");
    } catch (Throwable $e2) {
        // Suppress
    }
}

// Compute actual display status
$current_time = date('Y-m-d H:i:s');
$computed_status = 'ACTIVE';
if ($current_time < $challenge_start) {
    $computed_status = 'WAITING';
} else if ($current_time > $challenge_end) {
    $computed_status = 'RESULTS';
}

// Handle Update Dates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_dates'])) {
    $new_start = $conn->real_escape_string($_POST['challenge_start']);
    $new_end = $conn->real_escape_string($_POST['challenge_end']);
    
    // Determine status
    $new_status = 'ACTIVE';
    $cur_time = date('Y-m-d H:i:s');
    if ($cur_time < $new_start) $new_status = 'WAITING';
    if ($cur_time > $new_end) $new_status = 'RESULTS';
    $db_status = ($new_status === 'WAITING') ? 'RESULTS' : $new_status;

    // Update config
    $conn->query("UPDATE leaderboard_config SET status = '$db_status', challenge_start = '$new_start', challenge_end = '$new_end' WHERE id = 1");
    if ($conn->affected_rows === 0) {
        $conn->query("INSERT IGNORE INTO leaderboard_config (id, status, challenge_start, challenge_end) VALUES (1, '$db_status', '$new_start', '$new_end')");
    }
    
    $_SESSION['success'] = "Challenge Dates have been successfully updated!";
    header("Location: leaderboard_manager.php");
    exit();
}

// Handle Wipe Scores
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wipe_scores'])) {
    // Wipe previous scores and analytics history
    $conn->query("UPDATE users SET total_counts = 0, level = 1");
    $conn->query("TRUNCATE TABLE daily_counts");
    
    $_SESSION['success'] = "All User Scores and Analytics have been successfully wiped to 0.";
    header("Location: leaderboard_manager.php");
    exit();
}

// Fetch Top 100 Users
$leaderboard_data = [];
$leaderboard_res = $conn->query("SELECT username, total_counts, level FROM users WHERE total_counts > 0 ORDER BY total_counts DESC LIMIT 100");
if ($leaderboard_res && $leaderboard_res->num_rows > 0) {
    while($row = $leaderboard_res->fetch_assoc()) {
        $leaderboard_data[] = $row;
    }
}

include 'includes/header.php';
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-slate-800">Monthly Leaderboard Manager</h2>
    <p class="text-slate-500">Manage the monthly challenge dates, view current status, and reset scores.</p>
</div>

<?php if(isset($_SESSION['success'])): ?>
<div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg shadow-sm" role="alert">
    <p class="font-bold">Success</p>
    <p><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Current Status Card -->
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <h3 class="text-lg font-bold text-slate-800 mb-4">Current Status</h3>
        <div class="mb-6">
            <?php if ($computed_status === 'ACTIVE'): ?>
                <span class="bg-green-100 text-green-800 text-sm font-bold px-3 py-1 rounded-full"><i class="fa-solid fa-circle-play mr-1"></i> ACTIVE TRACKING</span>
            <?php elseif ($computed_status === 'WAITING'): ?>
                <span class="bg-yellow-100 text-yellow-800 text-sm font-bold px-3 py-1 rounded-full"><i class="fa-solid fa-clock mr-1"></i> WAITING TO START</span>
            <?php else: ?>
                <span class="bg-red-100 text-red-800 text-sm font-bold px-3 py-1 rounded-full"><i class="fa-solid fa-lock mr-1"></i> RESULTS PHASE (LOCKED)</span>
            <?php endif; ?>
        </div>
        
        <div class="space-y-3">
            <p class="text-slate-600">
                <i class="fa-solid fa-play text-slate-400 w-5"></i> 
                Starts On: <strong><?php echo date('M d, Y h:i A', strtotime($challenge_start)); ?></strong>
            </p>
            <p class="text-slate-600">
                <i class="fa-solid fa-stop text-slate-400 w-5"></i> 
                Ends On: <strong><?php echo date('M d, Y h:i A', strtotime($challenge_end)); ?></strong>
            </p>
        </div>
    </div>

    <!-- Wipe Scores Card -->
    <div class="bg-white rounded-lg shadow-sm border border-red-200 p-6">
        <h3 class="text-lg font-bold text-red-600 mb-2"><i class="fa-solid fa-triangle-exclamation mr-2"></i> Danger Zone</h3>
        <p class="text-slate-600 mb-6">Wiping scores will reset every user's total counts to 0 and clear all analytics charts. Do this right before a new challenge starts.</p>
        
        <form method="POST" onsubmit="return confirm('WARNING: Are you sure you want to WIPE all user scores and analytics? This cannot be undone!');">
            <button type="submit" name="wipe_scores" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg shadow-sm transition-colors w-full sm:w-auto">
                <i class="fa-solid fa-skull-crossbones mr-2"></i> Wipe All Scores to 0
            </button>
        </form>
    </div>
</div>

<!-- Update Challenge Dates Form -->
<div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-8">
    <h3 class="text-lg font-bold text-slate-800 mb-4"><i class="fa-solid fa-calendar-days mr-2 text-blue-500"></i> Set Challenge Schedule</h3>
    <form method="POST" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Challenge Start Date & Time</label>
                <input type="datetime-local" name="challenge_start" value="<?php echo date('Y-m-d\TH:i', strtotime($challenge_start)); ?>" required class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Challenge End Date & Time</label>
                <input type="datetime-local" name="challenge_end" value="<?php echo date('Y-m-d\TH:i', strtotime($challenge_end)); ?>" required class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
        <div class="pt-4">
            <button type="submit" name="update_dates" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-sm transition-colors">
                <i class="fa-solid fa-save mr-2"></i> Save Schedule
            </button>
        </div>
    </form>
</div>

<div class="bg-slate-50 rounded-lg border border-slate-200 p-6 mb-8">
    <h3 class="text-lg font-bold text-slate-800 mb-4"><i class="fa-solid fa-circle-info mr-2 text-blue-500"></i> How the schedule works</h3>
    <ul class="list-disc list-inside text-slate-600 space-y-2">
        <li><strong>Waiting:</strong> If the current time is <em>before</em> the Start Date, the challenge is locked. Users cannot submit scores for the challenge yet.</li>
        <li><strong>Active Tracking:</strong> Once the Start Date passes, tracking begins automatically. App users will sync their scores seamlessly.</li>
        <li><strong>Results Phase:</strong> Once the End Date passes, the leaderboard automatically locks itself. The final winners are displayed, and no new scores can be submitted.</li>
        <li><strong>Wiping Scores:</strong> Setting the dates does <strong>NOT</strong> reset user scores! You must manually click the red "Wipe All Scores" button to clear the slate for a new challenge.</li>
    </ul>
</div>

<!-- Leaderboard Rankings Table -->
<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden mb-8">
    <div class="p-6 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-bold text-slate-800"><i class="fa-solid fa-trophy mr-2 text-yellow-500"></i> Current Rankings (Top 100)</h3>
            <p class="text-sm text-slate-500">Live preview of the global leaderboard.</p>
        </div>
        <span class="bg-blue-100 text-blue-800 text-xs font-bold px-3 py-1 rounded-full"><?php echo count($leaderboard_data); ?> Active Users</span>
    </div>
    
    <?php if (count($leaderboard_data) > 0): ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-100 text-slate-600 text-sm border-b border-slate-200">
                    <th class="px-6 py-3 font-bold">Rank</th>
                    <th class="px-6 py-3 font-bold">Devotee</th>
                    <th class="px-6 py-3 font-bold">Level</th>
                    <th class="px-6 py-3 font-bold text-right">Total Counts</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                <?php 
                $rank = 1;
                foreach($leaderboard_data as $user): 
                    $bg_class = ($rank % 2 == 0) ? 'bg-slate-50' : 'bg-white';
                    
                    // Highlight top 3
                    $rank_badge = "#" . $rank;
                    $text_class = "text-slate-700";
                    if ($rank === 1) { $rank_badge = "🥇 1st"; $text_class = "text-yellow-600 font-bold"; }
                    if ($rank === 2) { $rank_badge = "🥈 2nd"; $text_class = "text-slate-500 font-bold"; }
                    if ($rank === 3) { $rank_badge = "🥉 3rd"; $text_class = "text-orange-600 font-bold"; }
                ?>
                <tr class="<?php echo $bg_class; ?> border-b border-slate-100 hover:bg-blue-50 transition-colors">
                    <td class="px-6 py-4 <?php echo $text_class; ?>"><?php echo $rank_badge; ?></td>
                    <td class="px-6 py-4 font-bold text-slate-800">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold mr-3">
                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                            </div>
                            <?php echo htmlspecialchars($user['username']); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-slate-600">Level <?php echo $user['level']; ?></td>
                    <td class="px-6 py-4 font-black text-right text-slate-800"><?php echo formatNumberShort($user['total_counts']); ?></td>
                </tr>
                <?php 
                    $rank++;
                endforeach; 
                ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="p-8 text-center">
        <i class="fa-solid fa-ghost text-4xl text-slate-300 mb-3"></i>
        <p class="text-slate-500 text-lg font-medium">The leaderboard is empty.</p>
        <p class="text-slate-400 text-sm">No users have synced scores above 0 yet.</p>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

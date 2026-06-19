<?php
// F:\APPS\JC Pro Admin panel\users.php
require_once 'config.php';
check_auth();

// Handle ad toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_ads') {
    $user_id = (int)$_POST['user_id'];
    $current_status = (int)$_POST['current_status'];
    $new_status = $current_status === 1 ? 0 : 1;
    $conn->query("UPDATE users SET ads_disabled = $new_status WHERE id = $user_id");
}

// Search and Pagination
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where_clause = "";
if ($search) {
    $search_esc = $conn->real_escape_string($search);
    if (strpos(trim($search), '#') === 0) {
        // Strict ID search (e.g., "#15")
        $id_search = (int)substr(trim($search), 1);
        $where_clause = "WHERE id = " . $id_search;
    } elseif (is_numeric(trim($search))) {
        // Both Username and ID
        $where_clause = "WHERE username LIKE '%$search_esc%' OR id = " . (int)trim($search);
    } else {
        // Only Username
        $where_clause = "WHERE username LIKE '%$search_esc%'";
    }
}

// Total rows
$total_res = $conn->query("SELECT COUNT(*) as cnt FROM users $where_clause");
$total_rows = $total_res->fetch_assoc()['cnt'];
$total_pages = ceil($total_rows / $per_page);

// Fetch users
$users = [];
$res = $conn->query("SELECT * FROM users $where_clause ORDER BY id DESC LIMIT $per_page OFFSET $offset");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $users[] = $row;
    }
}

include 'includes/header.php';
?>

<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Users</h1>
        <p class="text-slate-500 text-sm mt-1 font-medium">Manage registered users in the app.</p>
    </div>
    
    <form method="GET" action="users.php" class="relative w-full md:w-64">
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
            <i class="fa-solid fa-search text-slate-400"></i>
        </div>
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
            class="block w-full pl-10 pr-3 py-2.5 border border-slate-200/60 rounded-xl bg-white/60 backdrop-blur-md text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white transition-all shadow-[0_2px_10px_rgb(0,0,0,0.02)]"
            placeholder="Search username...">
    </form>
</div>

<?php if (isset($msg)): ?>
    <div class="bg-green-500/10 border border-green-500 text-green-500 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">
        <i class="fa-solid fa-check-circle"></i>
        <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<?php if (isset($err)): ?>
    <div class="bg-red-500/10 border border-red-500 text-red-500 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?php echo htmlspecialchars($err); ?>
    </div>
<?php endif; ?>

<?php if(empty($users)): ?>
    <div class="bg-white/60 backdrop-blur-xl border border-white rounded-2xl shadow-[0_4px_24px_rgb(0,0,0,0.02)] p-12 text-center text-slate-500 w-full">
        <i class="fa-solid fa-users-slash text-4xl mb-3 block opacity-30"></i>
        <span class="font-medium">No users found matching your criteria.</span>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php foreach($users as $user): ?>
            <div class="bg-white/80 backdrop-blur-xl border border-slate-200/60 shadow-sm rounded-2xl p-6 transition-all hover:shadow-lg hover:-translate-y-1 group">
                <div class="flex justify-between items-start mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center font-bold text-xl shadow-inner group-hover:scale-105 transition-transform">
                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800 text-lg leading-tight truncate max-w-[120px]" title="<?php echo htmlspecialchars($user['username']); ?>">
                                <?php echo htmlspecialchars($user['username']); ?>
                            </h3>
                            <p class="text-[11px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">ID: #<?php echo $user['id']; ?></p>
                        </div>
                    </div>
                    <span class="bg-slate-100 text-slate-600 py-1 px-2.5 rounded-full text-xs font-bold border border-slate-200/60">
                        Lvl <?php echo $user['level']; ?>
                    </span>
                </div>
                
                <div class="grid grid-cols-2 gap-3 mb-5">
                    <div class="bg-slate-50/70 rounded-xl p-3 border border-slate-100/80">
                        <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider mb-1 flex items-center gap-1.5"><i class="fa-solid fa-calculator text-slate-300"></i> Counts</p>
                        <p class="font-mono font-bold text-slate-700 text-lg"><?php echo formatNumberShort($user['total_counts']); ?></p>
                    </div>
                    <div class="bg-slate-50/70 rounded-xl p-3 border border-slate-100/80">
                        <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider mb-1 flex items-center gap-1.5"><i class="fa-solid fa-clock-rotate-left text-slate-300"></i> Active</p>
                        <p class="font-semibold text-slate-600 text-xs mt-1"><?php echo date('M d, Y', strtotime($user['last_active'])); ?></p>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-4 mt-auto">
                    <form method="POST" action="" class="w-full">
                        <input type="hidden" name="action" value="toggle_ads">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <?php $ads_disabled = isset($user['ads_disabled']) ? $user['ads_disabled'] : 0; ?>
                        <input type="hidden" name="current_status" value="<?php echo $ads_disabled; ?>">
                        
                        <button type="submit" class="w-full py-2.5 text-xs font-bold rounded-xl border transition-all shadow-sm flex items-center justify-center gap-2 <?php echo $ads_disabled ? 'bg-red-50 text-red-600 border-red-200 hover:bg-red-100' : 'bg-emerald-50 text-emerald-600 border-emerald-200 hover:bg-emerald-100'; ?>">
                            <i class="fa-solid <?php echo $ads_disabled ? 'fa-ban' : 'fa-check-circle'; ?>"></i>
                            <?php echo $ads_disabled ? 'Ads Disabled for User' : 'Ads Enabled for User'; ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="px-6 py-4 border-t border-slate-100/60 bg-white/40 flex items-center justify-between">
        <span class="text-sm text-slate-500 font-medium">
            Showing <span class="font-bold text-slate-800"><?php echo $offset + 1; ?></span> to 
            <span class="font-bold text-slate-800"><?php echo min($offset + $per_page, $total_rows); ?></span> of 
            <span class="font-bold text-slate-800"><?php echo $total_rows; ?></span> entries
        </span>
        <div class="flex items-center space-x-2">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="px-3.5 py-2 bg-white border border-slate-200 text-slate-700 rounded-xl hover:bg-slate-50 hover:text-blue-600 text-sm font-semibold transition-colors shadow-sm">Prev</a>
            <?php endif; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="px-3.5 py-2 bg-white border border-slate-200 text-slate-700 rounded-xl hover:bg-slate-50 hover:text-blue-600 text-sm font-semibold transition-colors shadow-sm">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

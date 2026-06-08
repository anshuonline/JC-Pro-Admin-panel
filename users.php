<?php
// F:\APPS\JC Pro Admin panel\users.php
require_once 'config.php';
check_auth();

// Handle Delete User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $id = intval($_POST['delete_user_id']);
    // Due to ON DELETE CASCADE in db, this will also delete daily_counts and user_challenges
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $msg = "User deleted successfully.";
    } else {
        $err = "Error deleting user.";
    }
    $stmt->close();
}

// Search and Pagination
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where_clause = "";
if ($search) {
    $search_esc = $conn->real_escape_string($search);
    $where_clause = "WHERE username LIKE '%$search_esc%'";
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

<div class="bg-white/60 backdrop-blur-xl border border-white rounded-2xl shadow-[0_4px_24px_rgb(0,0,0,0.02)] overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="text-xs uppercase bg-slate-50/50 text-slate-500 border-b border-slate-100">
                <tr>
                    <th scope="col" class="px-6 py-4 font-semibold">ID</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Username</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Level</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Total Counts</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Last Active</th>
                    <th scope="col" class="px-6 py-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100/60">
                <?php if(empty($users)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-slate-500">
                        <i class="fa-solid fa-users-slash text-4xl mb-3 block opacity-30"></i>
                        <span class="font-medium">No users found matching your criteria.</span>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach($users as $user): ?>
                    <tr class="hover:bg-white/60 transition-colors">
                        <td class="px-6 py-4 font-medium text-slate-500"><?php echo $user['id']; ?></td>
                        <td class="px-6 py-4 font-semibold text-slate-800 flex items-center">
                            <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-3 font-bold border border-blue-200/50 shadow-sm">
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
                            <?php echo number_format($user['total_counts']); ?>
                        </td>
                        <td class="px-6 py-4 text-slate-500 text-xs font-medium">
                            <?php echo date('M d, Y H:i', strtotime($user['last_active'])); ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <form method="POST" action="users.php" onsubmit="return confirm('Are you sure you want to delete this user? All their data will be lost.');" class="inline">
                                <input type="hidden" name="delete_user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="text-red-500 hover:text-red-600 bg-red-50 hover:bg-red-100 p-2.5 rounded-lg transition-colors border border-transparent hover:border-red-200 shadow-sm" title="Delete User">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
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

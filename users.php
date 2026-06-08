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
        <h1 class="text-2xl font-bold text-white">Users</h1>
        <p class="text-gray-400 text-sm mt-1">Manage registered users in the app.</p>
    </div>
    
    <form method="GET" action="users.php" class="relative w-full md:w-64">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <i class="fa-solid fa-search text-gray-500"></i>
        </div>
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
            class="block w-full pl-10 pr-3 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
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

<div class="bg-gray-800 border border-gray-700 rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-400">
            <thead class="text-xs uppercase bg-gray-700/50 text-gray-300">
                <tr>
                    <th scope="col" class="px-6 py-3">ID</th>
                    <th scope="col" class="px-6 py-3">Username</th>
                    <th scope="col" class="px-6 py-3">Level</th>
                    <th scope="col" class="px-6 py-3">Total Counts</th>
                    <th scope="col" class="px-6 py-3">Last Active</th>
                    <th scope="col" class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($users)): ?>
                <tr class="border-b border-gray-700">
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <i class="fa-solid fa-users-slash text-4xl mb-3 block opacity-50"></i>
                        No users found matching your criteria.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach($users as $user): ?>
                    <tr class="border-b border-gray-700 hover:bg-gray-700/30 transition-colors">
                        <td class="px-6 py-4"><?php echo $user['id']; ?></td>
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
                        <td class="px-6 py-4 text-right">
                            <form method="POST" action="users.php" onsubmit="return confirm('Are you sure you want to delete this user? All their data will be lost.');" class="inline">
                                <input type="hidden" name="delete_user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="text-red-400 hover:text-red-300 bg-red-400/10 hover:bg-red-400/20 p-2 rounded transition-colors" title="Delete User">
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
    <div class="px-6 py-4 border-t border-gray-700 flex items-center justify-between">
        <span class="text-sm text-gray-400">
            Showing <span class="font-medium text-white"><?php echo $offset + 1; ?></span> to 
            <span class="font-medium text-white"><?php echo min($offset + $per_page, $total_rows); ?></span> of 
            <span class="font-medium text-white"><?php echo $total_rows; ?></span> entries
        </span>
        <div class="flex items-center space-x-2">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="px-3 py-1.5 bg-gray-700 text-white rounded-lg hover:bg-gray-600 text-sm">Prev</a>
            <?php endif; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="px-3 py-1.5 bg-gray-700 text-white rounded-lg hover:bg-gray-600 text-sm">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

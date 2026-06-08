<?php
// F:\APPS\JC Pro Admin panel\challenges.php
require_once 'config.php';
check_auth();

$msg = '';
$err = '';

// Handle Create Challenge
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $target_count = (int)$_POST['target_count'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $visibility_end_date = $_POST['visibility_end_date'];
    
    $stmt = $conn->prepare("INSERT INTO challenges (title, description, target_count, start_date, end_date, visibility_end_date, created_by_admin) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("ssisss", $title, $description, $target_count, $start_date, $end_date, $visibility_end_date);
    if ($stmt->execute()) {
        $msg = "Challenge created successfully.";
    } else {
        $err = "Error creating challenge.";
    }
    $stmt->close();
}

// Handle Delete Challenge
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = intval($_POST['challenge_id']);
    $stmt = $conn->prepare("DELETE FROM challenges WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $msg = "Challenge deleted successfully.";
    } else {
        $err = "Error deleting challenge.";
    }
    $stmt->close();
}

// Fetch challenges
$challenges = [];
$res = $conn->query("SELECT * FROM challenges ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $challenges[] = $row;
    }
}

include 'includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-white">Challenges</h1>
    <p class="text-gray-400 text-sm mt-1">Create and manage app challenges.</p>
</div>

<?php if ($msg): ?>
    <div class="bg-green-500/10 border border-green-500 text-green-500 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">
        <i class="fa-solid fa-check-circle"></i>
        <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<?php if ($err): ?>
    <div class="bg-red-500/10 border border-red-500 text-red-500 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?php echo htmlspecialchars($err); ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Create Challenge Form -->
    <div class="lg:col-span-1">
        <div class="bg-gray-800 border border-gray-700 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-medium text-white mb-4 border-b border-gray-700 pb-3">Create New Challenge</h3>
            <form method="POST" action="challenges.php" class="space-y-4">
                <input type="hidden" name="action" value="create">
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Title</label>
                    <input type="text" name="title" required
                        class="block w-full px-3 py-2 border border-gray-600 rounded-lg bg-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Description</label>
                    <textarea name="description" rows="3"
                        class="block w-full px-3 py-2 border border-gray-600 rounded-lg bg-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Target Count</label>
                    <input type="number" name="target_count" required min="1"
                        class="block w-full px-3 py-2 border border-gray-600 rounded-lg bg-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Start Date</label>
                    <input type="date" name="start_date" required
                        class="block w-full px-3 py-2 border border-gray-600 rounded-lg bg-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">End Date</label>
                    <input type="date" name="end_date" required
                        class="block w-full px-3 py-2 border border-gray-600 rounded-lg bg-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Visibility End Date</label>
                    <input type="date" name="visibility_end_date" required
                        class="block w-full px-3 py-2 border border-gray-600 rounded-lg bg-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 focus:ring-offset-gray-900 transition-colors">
                    Create Challenge
                </button>
            </form>
        </div>
    </div>
    
    <!-- Challenges List -->
    <div class="lg:col-span-2">
        <div class="bg-gray-800 border border-gray-700 rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-700">
                <h3 class="text-lg font-medium text-white">Existing Challenges</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-400">
                    <thead class="text-xs uppercase bg-gray-700/50 text-gray-300">
                        <tr>
                            <th scope="col" class="px-6 py-3">Challenge</th>
                            <th scope="col" class="px-6 py-3">Target</th>
                            <th scope="col" class="px-6 py-3">Duration</th>
                            <th scope="col" class="px-6 py-3">Creator</th>
                            <th scope="col" class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($challenges)): ?>
                        <tr class="border-b border-gray-700">
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                <i class="fa-solid fa-trophy text-4xl mb-3 block opacity-50"></i>
                                No challenges found.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach($challenges as $challenge): ?>
                            <tr class="border-b border-gray-700 hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-white"><?php echo htmlspecialchars($challenge['title']); ?></div>
                                    <div class="text-xs text-gray-500 mt-1 line-clamp-1" title="<?php echo htmlspecialchars($challenge['description']); ?>">
                                        <?php echo htmlspecialchars($challenge['description'] ?: 'No description'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-mono text-indigo-400 font-bold">
                                    <?php echo number_format($challenge['target_count']); ?>
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    <div class="text-gray-300"><?php echo date('M d', strtotime($challenge['start_date'])); ?> - <?php echo date('M d', strtotime($challenge['end_date'])); ?></div>
                                    <div class="text-gray-500 mt-0.5">Vis: <?php echo date('M d', strtotime($challenge['visibility_end_date'])); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($challenge['created_by_admin']): ?>
                                        <span class="bg-indigo-500/20 text-indigo-400 py-1 px-2 rounded text-xs border border-indigo-500/30">Admin</span>
                                    <?php else: ?>
                                        <span class="bg-gray-700 text-gray-300 py-1 px-2 rounded text-xs border border-gray-600">User</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form method="POST" action="challenges.php" onsubmit="return confirm('Delete this challenge?');" class="inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="challenge_id" value="<?php echo $challenge['id']; ?>">
                                        <button type="submit" class="text-red-400 hover:text-red-300 bg-red-400/10 hover:bg-red-400/20 p-2 rounded transition-colors" title="Delete">
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
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

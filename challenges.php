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

// Fetch challenges with joined counts
$challenges = [];
$res = $conn->query("SELECT c.*, 
    (SELECT COUNT(*) FROM user_challenges WHERE challenge_id = c.id) as joined_count,
    (SELECT COUNT(*) FROM user_challenges WHERE challenge_id = c.id AND status = 'completed') as completed_count
    FROM challenges c ORDER BY c.id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $challenges[] = $row;
    }
}

include 'includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Challenges</h1>
    <p class="text-slate-500 text-sm mt-1 font-medium">Create and manage app challenges.</p>
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
        <div class="bg-white/60 backdrop-blur-xl border border-white rounded-2xl shadow-[0_4px_24px_rgb(0,0,0,0.02)] p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-3">Create New Challenge</h3>
            <form method="POST" action="challenges.php" class="space-y-4">
                <input type="hidden" name="action" value="create">
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Title</label>
                    <input type="text" name="title" required
                        class="block w-full px-3.5 py-2.5 border border-slate-200/60 rounded-xl bg-white/50 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white shadow-sm transition-all">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Description</label>
                    <textarea name="description" rows="3"
                        class="block w-full px-3.5 py-2.5 border border-slate-200/60 rounded-xl bg-white/50 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white shadow-sm transition-all"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Target Count</label>
                    <input type="number" name="target_count" required min="1"
                        class="block w-full px-3.5 py-2.5 border border-slate-200/60 rounded-xl bg-white/50 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white shadow-sm transition-all">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Start Date</label>
                    <input type="date" name="start_date" required
                        class="block w-full px-3.5 py-2.5 border border-slate-200/60 rounded-xl bg-white/50 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white shadow-sm transition-all">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">End Date</label>
                    <input type="date" name="end_date" required
                        class="block w-full px-3.5 py-2.5 border border-slate-200/60 rounded-xl bg-white/50 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white shadow-sm transition-all">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Visibility End Date</label>
                    <input type="date" name="visibility_end_date" required
                        class="block w-full px-3.5 py-2.5 border border-slate-200/60 rounded-xl bg-white/50 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white shadow-sm transition-all">
                </div>
                
                <button type="submit" class="w-full mt-2 flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all hover:shadow-md">
                    Create Challenge
                </button>
            </form>
        </div>
    </div>
    
    <!-- Challenges List -->
    <div class="lg:col-span-2">
        <div class="bg-white/60 backdrop-blur-xl border border-white rounded-2xl shadow-[0_4px_24px_rgb(0,0,0,0.02)] overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 bg-white/40">
                <h3 class="text-lg font-bold text-slate-800">Existing Challenges</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="text-xs uppercase bg-slate-50/50 text-slate-500 border-b border-slate-100">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-semibold">Challenge</th>
                            <th scope="col" class="px-6 py-4 font-semibold">Target</th>
                            <th scope="col" class="px-6 py-4 font-semibold">Duration</th>
                            <th scope="col" class="px-6 py-4 font-semibold">Creator</th>
                            <th scope="col" class="px-6 py-4 font-semibold text-center">Participants</th>
                            <th scope="col" class="px-6 py-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/60">
                        <?php if(empty($challenges)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-500">
                                <i class="fa-solid fa-trophy text-4xl mb-3 block opacity-30"></i>
                                <span class="font-medium">No challenges found.</span>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach($challenges as $challenge): ?>
                            <tr class="hover:bg-white/60 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-800"><?php echo htmlspecialchars($challenge['title']); ?></div>
                                    <div class="text-xs text-slate-500 mt-1 line-clamp-1 font-medium" title="<?php echo htmlspecialchars($challenge['description']); ?>">
                                        <?php echo htmlspecialchars($challenge['description'] ?: 'No description'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-mono text-blue-600 font-bold">
                                    <?php echo number_format($challenge['target_count']); ?>
                                </td>
                                <td class="px-6 py-4 text-xs font-medium">
                                    <div class="text-slate-700"><?php echo date('M d', strtotime($challenge['start_date'])); ?> - <?php echo date('M d', strtotime($challenge['end_date'])); ?></div>
                                    <div class="text-slate-500 mt-0.5">Vis: <?php echo date('M d', strtotime($challenge['visibility_end_date'])); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($challenge['created_by_admin']): ?>
                                        <span class="bg-blue-50 text-blue-700 py-1 px-2.5 rounded-md text-xs font-bold border border-blue-200/50 shadow-sm">Admin</span>
                                    <?php else: ?>
                                        <span class="bg-slate-100 text-slate-600 py-1 px-2.5 rounded-md text-xs font-bold border border-slate-200/60 shadow-sm">User</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="inline-flex items-center gap-1.5 bg-indigo-50/50 px-3 py-1.5 rounded-lg border border-indigo-100/50">
                                        <i class="fa-solid fa-users text-indigo-400 text-xs"></i>
                                        <span class="font-bold text-indigo-700"><?php echo number_format($challenge['joined_count'] ?? 0); ?></span>
                                    </div>
                                    <?php if(($challenge['completed_count'] ?? 0) > 0): ?>
                                    <div class="text-[10px] font-medium text-emerald-600 mt-1 flex items-center justify-center gap-1">
                                        <i class="fa-solid fa-check-circle"></i>
                                        <?php echo number_format($challenge['completed_count']); ?> finished
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form method="POST" action="challenges.php" onsubmit="return confirm('Delete this challenge?');" class="inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="challenge_id" value="<?php echo $challenge['id']; ?>">
                                        <button type="submit" class="text-red-500 hover:text-red-600 bg-red-50 hover:bg-red-100 p-2.5 rounded-lg transition-colors border border-transparent hover:border-red-200 shadow-sm" title="Delete">
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

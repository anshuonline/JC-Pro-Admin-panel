<?php
// challenges.php
require_once 'config.php';
check_auth();

$msg = '';
$err = '';

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
    <p class="text-slate-500 text-sm mt-1 font-medium">View and manage community challenges created by users.</p>
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

<!-- Challenges List — Full Width -->
<div class="bg-white/60 backdrop-blur-xl border border-white rounded-2xl shadow-[0_4px_24px_rgb(0,0,0,0.02)] overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-100 bg-white/40 flex items-center justify-between">
        <h3 class="text-lg font-bold text-slate-800">All Challenges</h3>
        <span class="text-sm text-slate-500 font-medium"><?php echo count($challenges); ?> total</span>
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
                                <span class="bg-emerald-50 text-emerald-700 py-1 px-2.5 rounded-md text-xs font-bold border border-emerald-200/50 shadow-sm">User</span>
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

<?php include 'includes/footer.php'; ?>

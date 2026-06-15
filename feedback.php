<?php
// C:\xampp\htdocs\JC Pro Admin panel\feedback.php
require_once 'config.php';
check_auth();

// Search and Pagination
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where_clause = "";
if ($search) {
    $search_esc = $conn->real_escape_string($search);
    $where_clause = "WHERE name LIKE '%$search_esc%' OR email LIKE '%$search_esc%'";
}

// Fetch feedback
$feedback = [];
$res = $conn->query("SELECT * FROM feedback $where_clause ORDER BY id DESC LIMIT $per_page OFFSET $offset");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $feedback[] = $row;
    }
}

// Total rows
$total_res = $conn->query("SELECT COUNT(*) as cnt FROM feedback $where_clause");
$total_rows = $total_res->fetch_assoc()['cnt'];
$total_pages = ceil($total_rows / $per_page);

include 'includes/header.php';
?>

<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">App Feedback</h1>
        <p class="text-slate-500 text-sm mt-1 font-medium">View user feedback and bug reports.</p>
    </div>
    
    <form method="GET" action="feedback.php" class="relative w-full md:w-64">
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
            <i class="fa-solid fa-search text-slate-400 text-sm"></i>
        </div>
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search feedback..." class="bg-white border border-slate-200 text-slate-900 text-sm rounded-xl focus:ring-orange-500 focus:border-orange-500 block w-full pl-10 p-2.5 shadow-sm">
    </form>
</div>

<div class="bg-white/60 backdrop-blur-xl border border-slate-200/60 shadow-[0_8px_30px_rgb(0,0,0,0.04)] rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-slate-600">
            <thead class="text-xs text-slate-500 uppercase bg-slate-50/80 border-b border-slate-200/60 font-semibold tracking-wider">
                <tr>
                    <th scope="col" class="px-6 py-4">Date</th>
                    <th scope="col" class="px-6 py-4">User</th>
                    <th scope="col" class="px-6 py-4">Ratings (Acc/UI/Snd/His)</th>
                    <th scope="col" class="px-6 py-4 max-w-xs">Liked Most</th>
                    <th scope="col" class="px-6 py-4 max-w-xs">Improvements</th>
                    <th scope="col" class="px-6 py-4">Bugs</th>
                    <th scope="col" class="px-6 py-4">Overall</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100/80">
                <?php if (count($feedback) > 0): ?>
                    <?php foreach ($feedback as $f): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-slate-800">
                                <?php echo date('d M Y', strtotime($f['submitted_at'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-800"><?php echo htmlspecialchars($f['name'] ?: 'Anonymous'); ?></div>
                                <div class="text-xs text-slate-400"><?php echo htmlspecialchars($f['email']); ?></div>
                                <div class="text-xs text-orange-600 font-medium mt-0.5"><?php echo htmlspecialchars($f['app_usage']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-slate-600">
                                <?php echo "{$f['rating_accuracy']}/{$f['rating_ui']}/{$f['rating_sound']}/{$f['rating_history']}"; ?>
                            </td>
                            <td class="px-6 py-4 max-w-xs truncate" title="<?php echo htmlspecialchars($f['likes_most']); ?>">
                                <?php echo htmlspecialchars($f['likes_most']); ?>
                            </td>
                            <td class="px-6 py-4 max-w-xs truncate" title="<?php echo htmlspecialchars($f['improvements']); ?>">
                                <?php echo htmlspecialchars($f['improvements']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($f['experienced_bugs'] === 'Yes'): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-700">Yes</span>
                                    <div class="text-xs text-slate-500 mt-1 max-w-[150px] truncate" title="<?php echo htmlspecialchars($f['bug_details']); ?>">
                                        <?php echo htmlspecialchars($f['bug_details']); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-700">No</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center font-bold text-slate-800">
                                    <i class="fa-solid fa-star text-orange-400 mr-1.5"></i>
                                    <?php echo $f['overall_rating']; ?>/5
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-slate-500">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fa-regular fa-folder-open text-4xl text-slate-300 mb-3"></i>
                                <p class="text-base font-medium text-slate-600">No feedback found</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/30">
        <span class="text-sm text-slate-500">
            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_rows); ?> of <?php echo $total_rows; ?> entries
        </span>
        <div class="inline-flex rounded-lg shadow-sm">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-l-lg hover:bg-slate-50 hover:text-orange-600 transition-colors">Previous</a>
            <?php else: ?>
                <span class="px-3 py-2 text-sm font-medium text-slate-300 bg-slate-50 border border-slate-200 rounded-l-lg cursor-not-allowed">Previous</span>
            <?php endif; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-r-lg hover:bg-slate-50 hover:text-orange-600 transition-colors -ml-px">Next</a>
            <?php else: ?>
                <span class="px-3 py-2 text-sm font-medium text-slate-300 bg-slate-50 border border-slate-200 rounded-r-lg cursor-not-allowed -ml-px">Next</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

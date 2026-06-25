<?php
// F:\APPS\JC Pro Admin panel\admin_logs.php
require_once 'config.php';
require_once 'includes/admin_logger.php';
check_auth();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

$total_res = $conn->query("SELECT COUNT(*) as cnt FROM admin_logs");
$total_rows = $total_res->fetch_assoc()['cnt'];
$total_pages = ceil($total_rows / $per_page);

$logs = [];
$res = $conn->query("SELECT * FROM admin_logs ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $logs[] = $row;
    }
}

include 'includes/header.php';
?>

<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Admin Logs</h1>
        <p class="text-sm text-slate-500 mt-1 font-medium">Track admin logins, logouts, and IP activity.</p>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500 font-semibold">
                    <th class="py-4 px-6">Date & Time</th>
                    <th class="py-4 px-6">Admin User</th>
                    <th class="py-4 px-6">Action</th>
                    <th class="py-4 px-6">IP Address</th>
                    <th class="py-4 px-6">Location</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slate-400">No logs found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-4 px-6 font-medium text-slate-700 whitespace-nowrap">
                                <?php echo date('d M Y, h:i A', strtotime($log['created_at'])); ?>
                            </td>
                            <td class="py-4 px-6 font-bold text-slate-800">
                                <?php echo htmlspecialchars($log['admin_username']); ?>
                            </td>
                            <td class="py-4 px-6">
                                <?php if ($log['action'] === 'LOGIN'): ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-green-50 text-green-700 border border-green-200/60">
                                        <i class="fa-solid fa-arrow-right-to-bracket"></i> LOGIN
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200/60">
                                        <i class="fa-solid fa-arrow-right-from-bracket"></i> LOGOUT
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-6 font-mono text-slate-600 text-xs">
                                <?php echo htmlspecialchars($log['ip_address']); ?>
                            </td>
                            <td class="py-4 px-6 text-slate-600">
                                <?php 
                                    if ($log['city'] !== 'Unknown' && $log['state'] !== 'Unknown') {
                                        echo htmlspecialchars($log['city'] . ', ' . $log['state']);
                                    } else {
                                        echo '<span class="text-slate-400 italic">Unknown</span>';
                                    }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($total_pages > 1): ?>
    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between">
        <span class="text-sm text-slate-500 font-medium">
            Showing <span class="font-bold text-slate-800"><?php echo $offset + 1; ?></span> to 
            <span class="font-bold text-slate-800"><?php echo min($offset + $per_page, $total_rows); ?></span> of 
            <span class="font-bold text-slate-800"><?php echo $total_rows; ?></span> entries
        </span>
        <div class="flex items-center space-x-2">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="px-3 py-1.5 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 text-sm font-semibold transition-colors shadow-sm">Prev</a>
            <?php endif; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="px-3 py-1.5 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 text-sm font-semibold transition-colors shadow-sm">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

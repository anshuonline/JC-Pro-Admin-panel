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

<div class="space-y-6">
    <?php if (count($feedback) > 0): ?>
        
        <!-- Bulk Actions Bar -->
        <div class="flex items-center justify-between bg-slate-50 border border-slate-200 rounded-xl p-3 mb-4">
            <div class="flex items-center gap-3 pl-2">
                <input type="checkbox" id="selectAll" class="w-5 h-5 text-orange-600 rounded border-slate-300 focus:ring-orange-500 cursor-pointer">
                <label for="selectAll" class="text-sm font-semibold text-slate-700 cursor-pointer select-none">Select All on Page</label>
            </div>
            <button id="bulkDeleteBtn" onclick="deleteSelected()" disabled class="opacity-50 cursor-not-allowed bg-red-50 text-red-600 hover:bg-red-100 px-4 py-1.5 rounded-lg font-semibold text-sm border border-red-200 transition-colors flex items-center gap-2">
                <i class="fa-solid fa-trash-can"></i> Delete (<span id="selectedCount">0</span>)
            </button>
        </div>

        <?php foreach ($feedback as $f): ?>
            <div class="feedback-card bg-white/80 backdrop-blur-xl border border-slate-200/60 shadow-sm rounded-2xl p-6 transition-all hover:shadow-md" data-id="<?php echo $f['id']; ?>">
                <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-4">
                    <div class="flex items-start gap-4">
                        <div class="pt-3">
                            <input type="checkbox" value="<?php echo $f['id']; ?>" class="feedback-checkbox w-5 h-5 text-orange-600 rounded border-slate-300 focus:ring-orange-500 cursor-pointer">
                        </div>
                        <div class="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 font-bold text-xl shrink-0">
                            <?php echo strtoupper(substr($f['name'] ?: 'A', 0, 1)); ?>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-800 tracking-tight flex items-center gap-2">
                                <?php echo htmlspecialchars($f['name'] ?: 'Anonymous'); ?>
                                <span class="bg-slate-100 text-slate-500 text-[10px] uppercase tracking-wider font-bold px-2 py-0.5 rounded-full">
                                    <?php echo htmlspecialchars($f['app_usage']); ?> User
                                </span>
                            </h3>
                            <p class="text-sm text-slate-500"><?php echo htmlspecialchars($f['email']); ?></p>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <div class="text-xs font-semibold text-slate-400 bg-slate-50 px-3 py-1 rounded-full border border-slate-100">
                            <i class="fa-regular fa-clock mr-1"></i> <?php echo date('d M Y, h:i A', strtotime($f['submitted_at'])); ?>
                        </div>
                        <div class="flex items-center gap-1 bg-orange-50 px-3 py-1 rounded-full border border-orange-100">
                            <i class="fa-solid fa-star text-orange-500 text-sm"></i>
                            <span class="font-bold text-orange-700"><?php echo $f['overall_rating']; ?>/5</span>
                        </div>
                        <div class="flex gap-2 mt-1">
                            <button onclick="toggleFeatured(<?php echo $f['id']; ?>, this)" class="flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full border transition-all <?php echo isset($f['is_featured']) && $f['is_featured'] ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-slate-50 text-slate-500 border-slate-200 hover:bg-slate-100'; ?>">
                                <i class="<?php echo isset($f['is_featured']) && $f['is_featured'] ? 'fa-solid' : 'fa-regular'; ?> fa-eye text-xs"></i>
                                <span><?php echo isset($f['is_featured']) && $f['is_featured'] ? 'Featured' : 'Hide'; ?></span>
                            </button>
                            <button onclick="deleteFeedback(<?php echo $f['id']; ?>)" class="flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full border transition-all bg-red-50 text-red-600 border-red-200 hover:bg-red-100">
                                <i class="fa-regular fa-trash-can text-xs"></i>
                                <span>Delete</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pt-4 border-t border-slate-100 mt-4">
                    <!-- Feature Ratings -->
                    <div class="col-span-1 bg-slate-50/50 rounded-xl p-4 border border-slate-100">
                        <h4 class="text-xs font-bold uppercase text-slate-400 mb-3 tracking-wider"><i class="fa-solid fa-sliders text-slate-300 mr-1.5"></i> Feature Ratings</h4>
                        <div class="space-y-2.5">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-slate-600 font-medium">Counter Accuracy</span>
                                <span class="font-bold text-slate-800"><?php echo $f['rating_accuracy']; ?>/5</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-slate-600 font-medium">UI Design</span>
                                <span class="font-bold text-slate-800"><?php echo $f['rating_ui']; ?>/5</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-slate-600 font-medium">Sound/Vibration</span>
                                <span class="font-bold text-slate-800"><?php echo $f['rating_sound']; ?>/5</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-slate-600 font-medium">History/Analytics</span>
                                <span class="font-bold text-slate-800"><?php echo $f['rating_history']; ?>/5</span>
                            </div>
                        </div>
                    </div>

                    <!-- Text Feedback -->
                    <div class="col-span-1 lg:col-span-2 flex flex-col gap-4">
                        <div class="bg-emerald-50/50 rounded-xl p-4 border border-emerald-100">
                            <h4 class="text-xs font-bold uppercase text-emerald-600 mb-2 tracking-wider"><i class="fa-solid fa-heart text-emerald-400 mr-1.5"></i> Liked Most</h4>
                            <p class="text-sm text-slate-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($f['likes_most'])); ?></p>
                        </div>
                        
                        <div class="bg-blue-50/50 rounded-xl p-4 border border-blue-100">
                            <h4 class="text-xs font-bold uppercase text-blue-600 mb-2 tracking-wider"><i class="fa-solid fa-lightbulb text-blue-400 mr-1.5"></i> Suggestions</h4>
                            <p class="text-sm text-slate-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($f['improvements'])); ?></p>
                        </div>

                        <?php if ($f['experienced_bugs'] === 'Yes'): ?>
                        <div class="bg-red-50/50 rounded-xl p-4 border border-red-100">
                            <h4 class="text-xs font-bold uppercase text-red-600 mb-2 tracking-wider"><i class="fa-solid fa-bug text-red-400 mr-1.5"></i> Bug Reported</h4>
                            <p class="text-sm text-slate-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($f['bug_details'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="bg-white border border-slate-200 rounded-2xl p-12 flex flex-col items-center justify-center">
            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                <i class="fa-regular fa-folder-open text-2xl text-slate-400"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-1">No Feedback Yet</h3>
            <p class="text-slate-500 text-sm">When users submit feedback, it will appear here as cards.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<div class="mt-8 flex items-center justify-between">
    <span class="text-sm text-slate-500 font-medium">
        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_rows); ?> of <?php echo $total_rows; ?> entries
    </span>
    <div class="inline-flex rounded-xl shadow-sm">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page-1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="px-4 py-2.5 text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-l-xl hover:bg-slate-50 hover:text-orange-600 transition-colors">Previous</a>
        <?php else: ?>
            <span class="px-4 py-2.5 text-sm font-semibold text-slate-300 bg-slate-50 border border-slate-200 rounded-l-xl cursor-not-allowed">Previous</span>
        <?php endif; ?>
        
        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page+1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="px-4 py-2.5 text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-r-xl hover:bg-slate-50 hover:text-orange-600 transition-colors -ml-px">Next</a>
        <?php else: ?>
            <span class="px-4 py-2.5 text-sm font-semibold text-slate-300 bg-slate-50 border border-slate-200 rounded-r-xl cursor-not-allowed -ml-px">Next</span>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<script>
function toggleFeatured(id, btn) {
    const formData = new FormData();
    formData.append('id', id);
    
    btn.disabled = true;
    fetch('toggle_feedback.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        if (data.status === 'success') {
            const isFeatured = data.is_featured;
            const icon = btn.querySelector('i');
            const span = btn.querySelector('span');
            
            if (isFeatured) {
                btn.className = 'flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full border transition-all bg-indigo-50 text-indigo-700 border-indigo-200';
                icon.className = 'fa-solid fa-eye text-xs';
                span.textContent = 'Featured';
            } else {
                btn.className = 'flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full border transition-all bg-slate-50 text-slate-500 border-slate-200 hover:bg-slate-100';
                icon.className = 'fa-regular fa-eye text-xs';
                span.textContent = 'Hide';
            }
        } else {
            alert('Failed to update status.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        console.error(err);
        alert('An error occurred.');
    });
}

// Selection Logic
const selectAll = document.getElementById('selectAll');
const checkboxes = document.querySelectorAll('.feedback-checkbox');
const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
const selectedCount = document.getElementById('selectedCount');

function updateBulkDeleteBtn() {
    const checked = document.querySelectorAll('.feedback-checkbox:checked').length;
    selectedCount.textContent = checked;
    if (checked > 0) {
        bulkDeleteBtn.disabled = false;
        bulkDeleteBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    } else {
        bulkDeleteBtn.disabled = true;
        bulkDeleteBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }
    selectAll.checked = checked === checkboxes.length && checkboxes.length > 0;
}

if (selectAll) {
    selectAll.addEventListener('change', (e) => {
        checkboxes.forEach(cb => cb.checked = e.target.checked);
        updateBulkDeleteBtn();
    });
}

checkboxes.forEach(cb => {
    cb.addEventListener('change', updateBulkDeleteBtn);
});

// Single Delete
function deleteFeedback(id) {
    if (confirm('Are you sure you want to delete this feedback? This cannot be undone.')) {
        executeDelete([id]);
    }
}

// Bulk Delete
function deleteSelected() {
    const selected = Array.from(document.querySelectorAll('.feedback-checkbox:checked')).map(cb => cb.value);
    if (selected.length === 0) return;
    
    if (confirm(`Are you sure you want to delete ${selected.length} selected feedback(s)? This cannot be undone.`)) {
        executeDelete(selected);
    }
}

function executeDelete(ids) {
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('ids', JSON.stringify(ids));

    fetch('delete_feedback.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            ids.forEach(id => {
                const card = document.querySelector(`.feedback-card[data-id="${id}"]`);
                if (card) {
                    card.style.opacity = '0';
                    setTimeout(() => card.remove(), 300);
                }
            });
            setTimeout(() => location.reload(), 300);
        } else {
            alert(data.message || 'Error deleting feedback.');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Network error occurred.');
    });
}
</script>

<?php include 'includes/footer.php'; ?>

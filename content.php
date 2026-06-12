<?php
// F:\APPS\JC Pro Admin panel\content.php
require_once 'config.php';
check_auth();

$msg = '';
$err = '';

// Handle Create or Edit Content
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content_text']); // renamed from content to avoid confusion
    $youtube_url = $conn->real_escape_string($_POST['youtube_url']);
    $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;

    if ($page_id > 0) {
        // Update
        $stmt = $conn->prepare("UPDATE content_pages SET title=?, content=?, youtube_url=? WHERE id=?");
        $stmt->bind_param("sssi", $title, $content, $youtube_url, $page_id);
        if ($stmt->execute()) {
            $msg = "Page updated successfully.";
        } else {
            $err = "Error updating page.";
        }
        $stmt->close();
    } else {
        // Create
        $stmt = $conn->prepare("INSERT INTO content_pages (title, content, youtube_url) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $content, $youtube_url);
        if ($stmt->execute()) {
            $msg = "Page created successfully.";
        } else {
            $err = "Error creating page.";
        }
        $stmt->close();
    }
}

// Handle Delete Content
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = intval($_POST['page_id']);
    $stmt = $conn->prepare("DELETE FROM content_pages WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $msg = "Page deleted successfully.";
    } else {
        $err = "Error deleting page.";
    }
    $stmt->close();
}

// Fetch pages
$pages = [];
$res = $conn->query("SELECT * FROM content_pages ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $pages[] = $row;
    }
}

include 'includes/header.php';
?>

<div class="mb-6 flex justify-between items-end">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Content Pages</h1>
        <p class="text-slate-500 text-sm mt-1 font-medium">Manage dynamic content for the app.</p>
    </div>
    <button onclick="openForm()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm hover:shadow-md">
        <i class="fa-solid fa-plus mr-2"></i> New Page
    </button>
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

<!-- Edit/Create Form (Hidden by default) -->
<div id="pageFormContainer" class="hidden bg-white/60 backdrop-blur-xl border border-white rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-6 mb-8 relative">
    <button onclick="closeForm()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-800 transition-colors">
        <i class="fa-solid fa-times"></i>
    </button>
    <h3 id="formTitle" class="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-3">Create New Page</h3>
    <form method="POST" action="content.php" class="space-y-4">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="page_id" id="page_id" value="0">
        
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Title</label>
            <input type="text" name="title" id="pageTitleInput" required
                class="block w-full px-3.5 py-2.5 border border-slate-200/60 rounded-xl bg-white/50 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white shadow-sm transition-all">
        </div>
        
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">YouTube URL (Optional)</label>
            <input type="url" name="youtube_url" id="pageYoutubeInput"
                class="block w-full px-3.5 py-2.5 border border-slate-200/60 rounded-xl bg-white/50 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white shadow-sm transition-all"
                placeholder="https://youtube.com/watch?v=...">
        </div>
        
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">HTML Content</label>
            <textarea name="content_text" id="pageContentInput" rows="6" required
                class="block w-full px-3.5 py-2.5 border border-slate-200/60 rounded-xl bg-white/50 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white shadow-sm transition-all font-mono text-sm"
                placeholder="<h1>Heading</h1><p>Paragraph</p>"></textarea>
            <p class="text-xs text-slate-500 mt-1 font-medium">You can use basic HTML tags for formatting.</p>
        </div>
        
        <div class="flex justify-end gap-3 mt-6">
            <button type="button" onclick="closeForm()" class="px-4 py-2 border border-slate-200 bg-white rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors shadow-sm">
                Cancel
            </button>
            <button type="submit" class="px-4 py-2 border border-transparent rounded-xl shadow-sm text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                Save Page
            </button>
        </div>
    </form>
</div>

<!-- Pages List -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if(empty($pages)): ?>
        <div class="col-span-full bg-white/60 backdrop-blur-xl border border-white rounded-2xl p-8 text-center text-slate-500 shadow-[0_4px_24px_rgb(0,0,0,0.02)]">
            <i class="fa-solid fa-file-lines text-5xl mb-4 block opacity-30"></i>
            <p class="font-medium">No content pages found. Click 'New Page' to create one.</p>
        </div>
    <?php else: ?>
        <?php foreach($pages as $page): ?>
        <div class="bg-white/60 backdrop-blur-xl border border-white rounded-2xl shadow-[0_4px_24px_rgb(0,0,0,0.02)] overflow-hidden flex flex-col hover:shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-all group">
            <div class="p-6 flex-1">
                <div class="flex justify-between items-start mb-3">
                    <h3 class="text-lg font-bold text-slate-800 line-clamp-1" title="<?php echo htmlspecialchars($page['title']); ?>">
                        <?php echo htmlspecialchars($page['title']); ?>
                    </h3>
                    <span class="text-xs text-slate-600 font-bold bg-slate-100 border border-slate-200/60 px-2 py-1 rounded-md">ID: <?php echo $page['id']; ?></span>
                </div>
                
                <?php if ($page['youtube_url']): ?>
                <a href="<?php echo htmlspecialchars($page['youtube_url']); ?>" target="_blank" class="inline-flex items-center text-xs font-semibold text-red-600 hover:text-red-700 mb-3 bg-red-50 border border-red-100 px-2.5 py-1 rounded-md transition-colors">
                    <i class="fa-brands fa-youtube mr-1.5"></i> Video Attached
                </a>
                <?php endif; ?>
                
                <div class="text-sm text-slate-600 line-clamp-3 overflow-hidden text-ellipsis mb-4 mt-2">
                    <?php echo strip_tags($page['content']); ?>
                </div>
            </div>
            
            <div class="px-6 py-4 border-t border-slate-100 bg-white/40 flex justify-between items-center transition-opacity">
                <span class="text-xs font-medium text-slate-500"><i class="fa-regular fa-clock mr-1.5"></i> <?php echo date('M d, Y', strtotime($page['created_at'])); ?></span>
                <div class="flex gap-2">
                    <button onclick="editPage(<?php echo htmlspecialchars(json_encode($page), ENT_QUOTES, 'UTF-8'); ?>)" class="text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-100 p-2 rounded-lg transition-colors shadow-sm" title="Edit">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <form method="POST" action="content.php" onsubmit="return confirm('Delete this page?');" class="inline">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="page_id" value="<?php echo $page['id']; ?>">
                        <button type="submit" class="text-red-500 hover:text-red-600 bg-red-50 hover:bg-red-100 border border-red-100 p-2 rounded-lg transition-colors shadow-sm" title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    function openForm() {
        document.getElementById('pageFormContainer').classList.remove('hidden');
        document.getElementById('formTitle').textContent = 'Create New Page';
        document.getElementById('page_id').value = '0';
        document.getElementById('pageTitleInput').value = '';
        document.getElementById('pageYoutubeInput').value = '';
        document.getElementById('pageContentInput').value = '';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function closeForm() {
        document.getElementById('pageFormContainer').classList.add('hidden');
    }

    function editPage(page) {
        document.getElementById('pageFormContainer').classList.remove('hidden');
        document.getElementById('formTitle').textContent = 'Edit Page';
        document.getElementById('page_id').value = page.id;
        document.getElementById('pageTitleInput').value = page.title;
        document.getElementById('pageYoutubeInput').value = page.youtube_url || '';
        document.getElementById('pageContentInput').value = page.content;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
</script>

<?php include 'includes/footer.php'; ?>

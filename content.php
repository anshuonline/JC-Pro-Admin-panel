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
        <h1 class="text-2xl font-bold text-white">Content Pages</h1>
        <p class="text-gray-400 text-sm mt-1">Manage dynamic content for the app.</p>
    </div>
    <button onclick="openForm()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">
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
<div id="pageFormContainer" class="hidden bg-gray-800 border border-gray-700 rounded-xl shadow-sm p-6 mb-8 relative">
    <button onclick="closeForm()" class="absolute top-4 right-4 text-gray-400 hover:text-white">
        <i class="fa-solid fa-times"></i>
    </button>
    <h3 id="formTitle" class="text-lg font-medium text-white mb-4 border-b border-gray-700 pb-3">Create New Page</h3>
    <form method="POST" action="content.php" class="space-y-4">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="page_id" id="page_id" value="0">
        
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Title</label>
            <input type="text" name="title" id="pageTitleInput" required
                class="block w-full px-3 py-2 border border-gray-600 rounded-lg bg-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">YouTube URL (Optional)</label>
            <input type="url" name="youtube_url" id="pageYoutubeInput"
                class="block w-full px-3 py-2 border border-gray-600 rounded-lg bg-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="https://youtube.com/watch?v=...">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">HTML Content</label>
            <textarea name="content_text" id="pageContentInput" rows="6" required
                class="block w-full px-3 py-2 border border-gray-600 rounded-lg bg-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono text-sm"
                placeholder="<h1>Heading</h1><p>Paragraph</p>"></textarea>
            <p class="text-xs text-gray-500 mt-1">You can use basic HTML tags for formatting.</p>
        </div>
        
        <div class="flex justify-end gap-3">
            <button type="button" onclick="closeForm()" class="px-4 py-2 border border-gray-600 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 transition-colors">
                Cancel
            </button>
            <button type="submit" class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                Save Page
            </button>
        </div>
    </form>
</div>

<!-- Pages List -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if(empty($pages)): ?>
        <div class="col-span-full bg-gray-800 border border-gray-700 rounded-xl p-8 text-center text-gray-500">
            <i class="fa-solid fa-file-lines text-5xl mb-4 block opacity-30"></i>
            <p>No content pages found. Click 'New Page' to create one.</p>
        </div>
    <?php else: ?>
        <?php foreach($pages as $page): ?>
        <div class="bg-gray-800 border border-gray-700 rounded-xl shadow-sm overflow-hidden flex flex-col hover:border-gray-600 transition-colors group">
            <div class="p-5 flex-1">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="text-lg font-bold text-white line-clamp-1" title="<?php echo htmlspecialchars($page['title']); ?>">
                        <?php echo htmlspecialchars($page['title']); ?>
                    </h3>
                    <span class="text-xs text-gray-500 bg-gray-700 px-2 py-1 rounded-md">ID: <?php echo $page['id']; ?></span>
                </div>
                
                <?php if ($page['youtube_url']): ?>
                <a href="<?php echo htmlspecialchars($page['youtube_url']); ?>" target="_blank" class="inline-flex items-center text-xs text-red-400 hover:text-red-300 mb-3 bg-red-400/10 px-2 py-1 rounded">
                    <i class="fa-brands fa-youtube mr-1.5"></i> Video Attached
                </a>
                <?php endif; ?>
                
                <div class="text-sm text-gray-400 line-clamp-3 overflow-hidden text-ellipsis mb-4 mt-2">
                    <?php echo strip_tags($page['content']); ?>
                </div>
            </div>
            
            <div class="px-5 py-3 border-t border-gray-700 bg-gray-800/50 flex justify-between items-center opacity-70 group-hover:opacity-100 transition-opacity">
                <span class="text-xs text-gray-500"><i class="fa-regular fa-clock mr-1"></i> <?php echo date('M d, Y', strtotime($page['created_at'])); ?></span>
                <div class="flex gap-2">
                    <button onclick='editPage(<?php echo json_encode($page); ?>)' class="text-indigo-400 hover:text-indigo-300 bg-indigo-400/10 hover:bg-indigo-400/20 p-1.5 rounded transition-colors" title="Edit">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <form method="POST" action="content.php" onsubmit="return confirm('Delete this page?');" class="inline">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="page_id" value="<?php echo $page['id']; ?>">
                        <button type="submit" class="text-red-400 hover:text-red-300 bg-red-400/10 hover:bg-red-400/20 p-1.5 rounded transition-colors" title="Delete">
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

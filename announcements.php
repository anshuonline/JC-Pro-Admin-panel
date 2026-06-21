<?php
require_once 'config.php';
check_auth();

$success_msg = '';
$error_msg = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add' || $_POST['action'] == 'edit') {
            $title = $conn->real_escape_string($_POST['title']);
            $message = $conn->real_escape_string($_POST['message']);
            $type = $conn->real_escape_string($_POST['type']);
            
            if ($_POST['action'] == 'add') {
                date_default_timezone_set('Asia/Kolkata');
                $created_at = date('Y-m-d H:i:s');
                $sql = "INSERT INTO announcements (title, message, type, created_at) VALUES ('$title', '$message', '$type', '$created_at')";
                if ($conn->query($sql)) {
                    $success_msg = "Announcement added successfully.";
                } else {
                    $error_msg = "Error adding announcement: " . $conn->error;
                }
            } else if ($_POST['action'] == 'edit') {
                $id = (int)$_POST['id'];
                $sql = "UPDATE announcements SET title='$title', message='$message', type='$type' WHERE id=$id";
                if ($conn->query($sql)) {
                    $success_msg = "Announcement updated successfully.";
                } else {
                    $error_msg = "Error updating announcement: " . $conn->error;
                }
            }
        } else if ($_POST['action'] == 'delete') {
            $id = (int)$_POST['id'];
            $sql = "DELETE FROM announcements WHERE id=$id";
            if ($conn->query($sql)) {
                $success_msg = "Announcement deleted successfully.";
            } else {
                $error_msg = "Error deleting announcement: " . $conn->error;
            }
        }
    }
}

// Fetch all announcements
$announcements = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
?>
<?php include 'includes/header.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('pageTitle').innerText = 'System Status & Announcements';
    });
</script>

<div class="max-w-7xl mx-auto">
                <div class="mb-8">
                    <h1 class="text-2xl md:text-3xl font-bold text-slate-800 tracking-tight">System Status & Announcements</h1>
                    <p class="text-slate-500 mt-1 text-sm md:text-base">Push updates directly to users' devices (shows up even offline).</p>
                </div>

                <?php if ($success_msg): ?>
                    <div class="bg-emerald-50 text-emerald-700 p-4 rounded-xl mb-6 border border-emerald-200 flex items-center">
                        <i class="fa-solid fa-check-circle mr-3 text-emerald-500 text-xl"></i>
                        <span class="font-medium"><?php echo $success_msg; ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($error_msg): ?>
                    <div class="bg-red-50 text-red-700 p-4 rounded-xl mb-6 border border-red-200 flex items-center">
                        <i class="fa-solid fa-circle-exclamation mr-3 text-red-500 text-xl"></i>
                        <span class="font-medium"><?php echo $error_msg; ?></span>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Form -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sticky top-6">
                            <h2 class="text-lg font-bold text-slate-800 mb-4" id="formTitle">Add New Announcement</h2>
                            <form method="POST" id="announcementForm">
                                <input type="hidden" name="action" value="add" id="formAction">
                                <input type="hidden" name="id" value="" id="formId">
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-semibold text-slate-700 mb-1">Type</label>
                                    <select name="type" id="typeInput" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 outline-none transition-all" required>
                                        <option value="info">Info (Blue)</option>
                                        <option value="warning">Warning (Yellow)</option>
                                        <option value="outage">Outage/Critical (Red)</option>
                                        <option value="success">Success/Update (Green)</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-semibold text-slate-700 mb-1">Title</label>
                                    <input type="text" name="title" id="titleInput" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 outline-none transition-all" placeholder="e.g. Server Maintenance" required>
                                </div>

                                <div class="mb-6">
                                    <label class="block text-sm font-semibold text-slate-700 mb-1">Message</label>
                                    <textarea name="message" id="messageInput" rows="8" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 outline-none transition-all resize-y min-h-[120px]" placeholder="Enter details..." required></textarea>
                                    <p class="text-xs text-slate-500 mt-2"><i class="fa-solid fa-circle-info mr-1 text-blue-400"></i>Type <code class="bg-slate-100 text-pink-600 px-1 py-0.5 rounded font-bold">{user}</code> in your message. The app will automatically replace it with the user's name (e.g., Dear {user}, ...)</p>
                                </div>

                                <div class="flex gap-3">
                                    <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-900 text-white font-bold py-3 px-4 rounded-xl transition-colors shadow-sm">
                                        Save
                                    </button>
                                    <button type="button" id="cancelEditBtn" class="hidden px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-colors" onclick="resetForm()">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- List -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                                <h3 class="font-bold text-slate-800 text-lg">Active Announcements</h3>
                                <span class="bg-orange-100 text-orange-700 text-xs font-bold px-2.5 py-1 rounded-full">Visible to Users</span>
                            </div>
                            <div class="p-0">
                                <?php if ($announcements && $announcements->num_rows > 0): ?>
                                    <div class="divide-y divide-slate-100">
                                        <?php while ($row = $announcements->fetch_assoc()): 
                                            $icon = 'fa-circle-info';
                                            $color = 'text-blue-500';
                                            $bg = 'bg-blue-50';
                                            if ($row['type'] == 'warning') { $icon = 'fa-triangle-exclamation'; $color = 'text-yellow-500'; $bg = 'bg-yellow-50'; }
                                            if ($row['type'] == 'outage') { $icon = 'fa-circle-xmark'; $color = 'text-red-500'; $bg = 'bg-red-50'; }
                                            if ($row['type'] == 'success') { $icon = 'fa-check-circle'; $color = 'text-emerald-500'; $bg = 'bg-emerald-50'; }
                                        ?>
                                            <div class="p-6 hover:bg-slate-50 transition-colors flex items-start gap-4">
                                                <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 <?php echo $bg; ?>">
                                                    <i class="fa-solid <?php echo $icon; ?> <?php echo $color; ?> text-lg"></i>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center justify-between mb-1">
                                                        <h4 class="text-base font-bold text-slate-800 truncate"><?php echo htmlspecialchars($row['title']); ?></h4>
                                                        <span class="text-xs font-medium text-slate-400 whitespace-nowrap ml-2">
                                                            <?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?>
                                                        </span>
                                                    </div>
                                                    <p class="text-sm text-slate-600 mb-3 leading-relaxed"><?php echo nl2br(htmlspecialchars($row['message'])); ?></p>
                                                    <div class="flex gap-2">
                                                        <?php 
                                                            $safeTitle = htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8');
                                                            $safeMessage = htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8');
                                                        ?>
                                                        <button onclick="editAnnouncement(<?php echo $row['id']; ?>, this.dataset.title, this.dataset.message, '<?php echo $row['type']; ?>')" 
                                                                data-title="<?php echo $safeTitle; ?>"
                                                                data-message="<?php echo $safeMessage; ?>"
                                                                class="text-xs font-bold text-slate-500 hover:text-blue-600 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-blue-50 transition-colors">
                                                            <i class="fa-solid fa-pen mr-1.5"></i>Edit
                                                        </button>
                                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                            <button type="submit" class="text-xs font-bold text-slate-500 hover:text-red-600 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-red-50 transition-colors">
                                                                <i class="fa-solid fa-trash mr-1.5"></i>Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="p-12 text-center">
                                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                            <i class="fa-regular fa-bell-slash text-slate-300 text-2xl"></i>
                                        </div>
                                        <h3 class="text-lg font-bold text-slate-700 mb-1">No Announcements</h3>
                                        <p class="text-slate-500 text-sm">Add one from the form to push it to users.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function editAnnouncement(id, title, message, type) {
            document.getElementById('formTitle').innerText = 'Edit Announcement';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('formId').value = id;
            document.getElementById('titleInput').value = title;
            document.getElementById('messageInput').value = message;
            document.getElementById('typeInput').value = type;
            document.getElementById('cancelEditBtn').classList.remove('hidden');
            window.scrollTo({top: 0, behavior: 'smooth'});
        }

        function resetForm() {
            document.getElementById('formTitle').innerText = 'Add New Announcement';
            document.getElementById('formAction').value = 'add';
            document.getElementById('formId').value = '';
            document.getElementById('announcementForm').reset();
            document.getElementById('cancelEditBtn').classList.add('hidden');
        }
    </script>
</div>

<?php include 'includes/footer.php'; ?>

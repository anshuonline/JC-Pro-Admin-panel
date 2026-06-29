<?php
// F:\APPS\JC Pro Admin panel\users.php
require_once 'config.php';
check_auth();

// Handle ad toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_ads') {
    $user_id = (int)$_POST['user_id'];
    $current_status = (int)$_POST['current_status'];
    $new_status = $current_status === 1 ? 0 : 1;
    $conn->query("UPDATE users SET ads_disabled = $new_status WHERE id = $user_id");
}

// Handle username update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_username') {
    $user_id = (int)$_POST['user_id'];
    $new_username = $conn->real_escape_string(trim($_POST['new_username']));
    if (!empty($new_username)) {
        $conn->query("UPDATE users SET username = '$new_username' WHERE id = $user_id");
        $msg = "Username updated successfully.";
    } else {
        $err = "Username cannot be empty.";
    }
}

// Handle link email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'link_email') {
    $user_id = (int)$_POST['user_id'];
    $email_to_link = $conn->real_escape_string(trim($_POST['email_to_link']));
    
    if (!empty($email_to_link)) {
        // Find the new account created by Google Login with this email
        $res = $conn->query("SELECT google_uid, email, id FROM users WHERE email = '$email_to_link' AND id != $user_id LIMIT 1");
        if ($res && $res->num_rows > 0) {
            $new_acc = $res->fetch_assoc();
            $g_uid = $conn->real_escape_string($new_acc['google_uid']);
            $new_id = $new_acc['id'];
            
            // Nullify the new account's google_uid first to avoid UNIQUE constraint violation
            $conn->query("UPDATE users SET google_uid = NULL WHERE id = $new_id");
            
            // Update the old account with the google_uid and email
            $conn->query("UPDATE users SET google_uid = '$g_uid', email = '$email_to_link' WHERE id = $user_id");
            
            // Delete the new empty account
            $conn->query("DELETE FROM users WHERE id = $new_id");
            
            $msg = "Account successfully linked to $email_to_link!";
        } else {
            $err = "Could not find a Google account with that email. Make sure the user has logged in with Google at least once.";
        }
    } else {
        $err = "Email cannot be empty.";
    }
}

// Handle unlink email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'unlink_email') {
    $user_id = (int)$_POST['user_id'];
    $conn->query("UPDATE users SET google_uid = NULL, email = NULL WHERE id = $user_id");
    $msg = "Google account unlinked successfully.";
}

// Handle cancel premium
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_premium') {
    $user_id = (int)$_POST['user_id'];
    $conn->query("UPDATE users SET is_premium = 0, premium_since = NULL WHERE id = $user_id");
    $msg = "Premium subscription cancelled successfully.";
}

// Handle gift premium
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'gift_premium') {
    $user_id = (int)$_POST['user_id'];
    // Ensure has_gift column exists
    $check_column_gift = $conn->query("SHOW COLUMNS FROM users LIKE 'has_gift'");
    if ($check_column_gift && $check_column_gift->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN has_gift TINYINT(1) DEFAULT 0");
    }
    $conn->query("UPDATE users SET has_gift = 1 WHERE id = $user_id");
    $msg = "Premium gifted successfully! User can claim it from their app settings.";
}

// Search and Pagination
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where_conditions = [];

if ($filter === 'premium') {
    $where_conditions[] = "is_premium = 1";
} elseif ($filter === 'free') {
    $where_conditions[] = "is_premium = 0";
}

if ($search) {
    $search_esc = $conn->real_escape_string($search);
    if (strpos(trim($search), '#') === 0) {
        $id_search = (int)substr(trim($search), 1);
        $where_conditions[] = "id = " . $id_search;
    } elseif (is_numeric(trim($search))) {
        $where_conditions[] = "(username = '$search_esc' OR id = " . (int)trim($search) . ")";
    } else {
        $where_conditions[] = "username = '$search_esc'";
    }
}

$where_clause = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";

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

<div class="bg-[#000000] min-h-[calc(100vh-6rem)] rounded-[2.5rem] p-6 md:p-8 -mt-4 -mx-2 md:-mx-4 shadow-[0_0_60px_rgba(0,0,0,0.8)] text-white relative overflow-hidden border border-white/5">
    <!-- Subtle background glows for AMOLED feel -->
    <div class="absolute top-0 left-1/4 w-96 h-96 bg-blue-600/10 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-purple-600/10 rounded-full blur-[120px] pointer-events-none"></div>

    <div class="relative z-10">
        <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div>
                <h1 class="text-3xl font-bold text-white tracking-tight">Users</h1>
                <p class="text-gray-400 text-sm mt-1.5 font-medium">Manage registered users in the app.</p>
            </div>
            
            <div class="flex flex-col md:flex-row items-center gap-4">
                <div class="flex bg-white/5 rounded-2xl p-1 border border-white/10">
                    <a href="?search=<?php echo urlencode($search); ?>&filter=all" class="px-4 py-2 rounded-xl text-sm font-medium transition-all <?php echo $filter === 'all' ? 'bg-white/10 text-white' : 'text-gray-400 hover:text-white'; ?>">All</a>
                    <a href="?search=<?php echo urlencode($search); ?>&filter=premium" class="px-4 py-2 rounded-xl text-sm font-medium transition-all flex items-center gap-2 <?php echo $filter === 'premium' ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'text-gray-400 hover:text-white'; ?>"><i class="fa-solid fa-crown text-[10px]"></i> Premium</a>
                    <a href="?search=<?php echo urlencode($search); ?>&filter=free" class="px-4 py-2 rounded-xl text-sm font-medium transition-all <?php echo $filter === 'free' ? 'bg-white/10 text-white' : 'text-gray-400 hover:text-white'; ?>">Free</a>
                </div>
                <form method="GET" action="users.php" class="relative w-full md:w-72 group">
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fa-solid fa-search text-gray-500 group-focus-within:text-blue-400 transition-colors"></i>
                    </div>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                        class="block w-full pl-11 pr-4 py-3 border border-white/10 rounded-2xl bg-white/5 backdrop-blur-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 focus:bg-white/10 transition-all duration-300 shadow-lg"
                        placeholder="Search username or #ID...">
                </form>
            </div>
        </div>

        <?php if (isset($msg)): ?>
            <div class="bg-[#064e3b]/40 backdrop-blur-md border border-[#059669]/30 text-[#34d399] px-5 py-4 rounded-2xl mb-8 text-sm flex items-center gap-3 shadow-lg">
                <i class="fa-solid fa-check-circle text-lg"></i>
                <span class="font-medium"><?php echo htmlspecialchars($msg); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($err)): ?>
            <div class="bg-[#7f1d1d]/40 backdrop-blur-md border border-[#dc2626]/30 text-[#f87171] px-5 py-4 rounded-2xl mb-8 text-sm flex items-center gap-3 shadow-lg">
                <i class="fa-solid fa-circle-exclamation text-lg"></i>
                <span class="font-medium"><?php echo htmlspecialchars($err); ?></span>
            </div>
        <?php endif; ?>

        <?php if(empty($users)): ?>
            <div class="bg-white/5 backdrop-blur-2xl border border-white/10 rounded-[2rem] p-16 text-center text-gray-400 w-full shadow-2xl flex flex-col items-center justify-center">
                <div class="w-20 h-20 bg-white/5 rounded-full flex items-center justify-center mb-5 border border-white/10">
                    <i class="fa-solid fa-users-slash text-3xl opacity-50"></i>
                </div>
                <span class="font-medium text-lg">No users found matching your criteria.</span>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php foreach($users as $user): ?>
                    <div class="bg-[#0a0a0a]/80 backdrop-blur-3xl border border-white/5 rounded-[2rem] p-6 transition-all duration-300 hover:border-white/20 hover:bg-[#111111] hover:shadow-[0_8px_40px_rgba(0,0,0,0.8)] group flex flex-col relative overflow-hidden">
                        <!-- Card subtle glow -->
                        <div class="absolute -top-10 -right-10 w-32 h-32 bg-blue-500/5 rounded-full blur-[30px] group-hover:bg-blue-500/10 transition-colors"></div>
                        
                        <div class="flex justify-between items-start mb-6 relative z-10">
                            <div class="flex items-center gap-3.5">
                                <div class="w-12 h-12 rounded-[1.2rem] bg-gradient-to-br from-blue-600 to-indigo-700 text-white flex items-center justify-center font-bold text-xl shadow-[0_4px_15px_rgba(59,130,246,0.3)] group-hover:scale-105 transition-transform duration-300">
                                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-semibold text-white text-[17px] leading-tight truncate max-w-[120px] tracking-tight" title="<?php echo htmlspecialchars($user['username']); ?>">
                                            <?php echo htmlspecialchars($user['username']); ?>
                                        </h3>
                                        <?php if (isset($user['is_premium']) && $user['is_premium']): ?>
                                            <i class="fa-solid fa-crown text-amber-400 text-sm drop-shadow-[0_0_8px_rgba(251,191,36,0.8)] ml-1" title="Premium User since <?php echo htmlspecialchars($user['premium_since'] ?? 'N/A'); ?>"></i>
                                        <?php endif; ?>
                                        <button type="button" onclick="editUsername(<?php echo $user['id']; ?>, '<?php echo addslashes(htmlspecialchars($user['username'])); ?>')" class="text-gray-500 hover:text-white focus:outline-none transition-colors ml-1" title="Edit Username">
                                            <i class="fa-solid fa-pen text-[10px]"></i>
                                        </button>
                                    </div>
                                    <p class="text-[11px] text-gray-500 font-medium uppercase tracking-wider mt-1">
                                        ID: #<?php echo $user['id']; ?> 
                                        <?php if(!empty($user['ip_address'])): ?>
                                            <span class="text-gray-700 mx-1">&bull;</span><span class="normal-case opacity-70">IP: <?php echo htmlspecialchars($user['ip_address']); ?></span>
                                        <?php endif; ?>
                                        <?php if(isset($user['is_premium']) && $user['is_premium'] && !empty($user['premium_since'])): ?>
                                            <span class="text-gray-700 mx-1">&bull;</span><span class="normal-case text-amber-500/80 font-bold">Premium: <?php echo date('M d, Y', strtotime($user['premium_since'])); ?></span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <span class="bg-white/10 text-gray-300 py-1 px-3 rounded-full text-[10px] font-bold tracking-wider border border-white/5 backdrop-blur-md">
                                LVL <?php echo $user['level']; ?>
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3 mb-6 relative z-10">
                            <div class="bg-white/5 rounded-[1.25rem] p-3.5 border border-white/5 transition-colors group-hover:bg-white/10">
                                <p class="text-[10px] uppercase font-bold text-gray-500 tracking-wider mb-1.5 flex items-center gap-1.5"><i class="fa-solid fa-calculator text-gray-600"></i> Counts</p>
                                <p class="font-mono font-medium text-white text-lg tracking-tight"><?php echo formatNumberShort($user['total_counts']); ?></p>
                            </div>
                            <div class="bg-white/5 rounded-[1.25rem] p-3.5 border border-white/5 transition-colors group-hover:bg-white/10">
                                <p class="text-[10px] uppercase font-bold text-gray-500 tracking-wider mb-1.5 flex items-center gap-1.5"><i class="fa-solid fa-clock-rotate-left text-gray-600"></i> Active</p>
                                <p class="font-medium text-gray-300 text-xs mt-1.5"><?php echo date('M d, Y', strtotime($user['last_active'])); ?></p>
                            </div>
                        </div>

                        <div class="border-t border-white/5 pt-5 mt-auto relative z-10">
                            <div class="flex flex-col gap-2.5">
                                <?php if (isset($user['is_premium']) && $user['is_premium']): ?>
                                <form method="POST" action="" class="w-full mb-1">
                                    <input type="hidden" name="action" value="cancel_premium">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" onclick="return confirm('Are you sure you want to cancel this user\'s premium subscription?');" class="w-full py-2 text-[10px] font-semibold rounded-[1.25rem] border transition-all duration-300 bg-amber-500/10 text-amber-400 border-amber-500/20 hover:bg-amber-500/20 flex items-center justify-center gap-2">
                                        <i class="fa-solid fa-crown text-amber-400/70"></i> Cancel Premium
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <?php if (isset($user['is_premium']) && $user['is_premium']): ?>
                                    <div class="w-full py-3 text-xs font-semibold rounded-[1.25rem] border flex items-center justify-center gap-2 bg-[#3f1616]/40 text-[#f87171] border-[#7f1d1d]/50">
                                        <i class="fa-solid fa-ban"></i>
                                        Ads Disabled (Premium)
                                    </div>
                                <?php else: ?>
                                    <div class="flex gap-2 w-full mb-1">
                                        <form method="POST" action="" class="w-1/2">
                                            <input type="hidden" name="action" value="toggle_ads">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <?php $ads_disabled = isset($user['ads_disabled']) ? $user['ads_disabled'] : 0; ?>
                                            <input type="hidden" name="current_status" value="<?php echo $ads_disabled; ?>">
                                            <button type="submit" class="w-full py-2.5 text-[10px] font-semibold rounded-[1.25rem] border transition-all duration-300 active:scale-[0.98] flex items-center justify-center gap-1.5 <?php echo $ads_disabled ? 'bg-[#3f1616]/40 text-[#f87171] border-[#7f1d1d]/50 hover:bg-[#7f1d1d]/40' : 'bg-[#064e3b]/30 text-[#34d399] border-[#059669]/30 hover:bg-[#064e3b]/60'; ?>">
                                                <i class="fa-solid <?php echo $ads_disabled ? 'fa-ban' : 'fa-check-circle'; ?>"></i>
                                                <?php echo $ads_disabled ? 'Ads Disabled' : 'Ads Enabled'; ?>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" action="" class="w-1/2">
                                            <input type="hidden" name="action" value="gift_premium">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <?php $has_gift = isset($user['has_gift']) ? $user['has_gift'] : 0; ?>
                                            <button type="submit" <?php echo $has_gift ? 'disabled' : ''; ?> onclick="return confirm('Gift Premium to this user?');" class="w-full py-2.5 text-[10px] font-semibold rounded-[1.25rem] border transition-all duration-300 active:scale-[0.98] flex items-center justify-center gap-1.5 <?php echo $has_gift ? 'bg-purple-500/10 text-purple-400 border-purple-500/20 opacity-50 cursor-not-allowed' : 'bg-purple-600/20 text-purple-400 border-purple-500/30 hover:bg-purple-600/30'; ?>">
                                                <i class="fa-solid fa-gift"></i>
                                                <?php echo $has_gift ? 'Gift Pending' : 'Gift Premium'; ?>
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>

                                <?php if (empty($user['google_uid'])): ?>
                                <button type="button" onclick="linkEmail(<?php echo $user['id']; ?>)" class="w-full py-3 text-xs font-semibold rounded-[1.25rem] border transition-all duration-300 active:scale-[0.98] flex items-center justify-center gap-2 bg-[#0f172a]/80 text-[#60a5fa] border-[#1e3a8a]/50 hover:bg-[#1e3a8a]/60">
                                    <i class="fa-brands fa-google"></i>
                                    Link Google Email
                                </button>
                                <?php else: ?>
                                <div class="flex items-center gap-2 w-full">
                                    <div class="flex-1 py-3 text-[11px] font-medium rounded-[1.25rem] border flex items-center justify-center gap-2 bg-white/5 text-gray-400 border-white/10 overflow-hidden px-3 backdrop-blur-sm">
                                        <i class="fa-solid fa-link shrink-0 text-gray-500"></i>
                                        <span class="truncate"><?php echo htmlspecialchars($user['email']); ?></span>
                                    </div>
                                    <button type="button" onclick="unlinkEmail(<?php echo $user['id']; ?>, '<?php echo addslashes(htmlspecialchars($user['email'])); ?>')" class="py-3 px-4 text-xs font-semibold rounded-[1.25rem] border bg-[#3f1616]/40 text-[#f87171] border-[#7f1d1d]/50 hover:bg-[#7f1d1d]/40 transition-all duration-300 active:scale-[0.92] focus:outline-none" title="Unlink Account">
                                        <i class="fa-solid fa-link-slash"></i>
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="px-6 py-5 mt-8 rounded-[2rem] border border-white/5 bg-white/5 backdrop-blur-xl flex flex-col md:flex-row items-center justify-between gap-4 shadow-xl">
            <span class="text-sm text-gray-400 font-medium">
                Showing <span class="font-bold text-white"><?php echo $offset + 1; ?></span> to 
                <span class="font-bold text-white"><?php echo min($offset + $per_page, $total_rows); ?></span> of 
                <span class="font-bold text-white"><?php echo $total_rows; ?></span> entries
            </span>
            <div class="flex items-center space-x-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="px-5 py-2.5 bg-white/10 border border-white/10 text-white rounded-2xl hover:bg-white/20 text-sm font-semibold transition-all duration-300 active:scale-95 backdrop-blur-md">Prev</a>
                <?php endif; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="px-5 py-2.5 bg-white/10 border border-white/10 text-white rounded-2xl hover:bg-white/20 text-sm font-semibold transition-all duration-300 active:scale-95 backdrop-blur-md">Next</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Hidden Form for Username Update -->
<form id="updateUsernameForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="update_username">
    <input type="hidden" name="user_id" id="update_user_id" value="">
    <input type="hidden" name="new_username" id="update_new_username" value="">
</form>

<!-- Hidden Form for Link Email -->
<form id="linkEmailForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="link_email">
    <input type="hidden" name="user_id" id="link_user_id" value="">
    <input type="hidden" name="email_to_link" id="link_email_to_link" value="">
</form>

<!-- Hidden Form for Unlink Email -->
<form id="unlinkEmailForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="unlink_email">
    <input type="hidden" name="user_id" id="unlink_user_id" value="">
</form>

<script>
function editUsername(userId, currentUsername) {
    const newUsername = prompt("Enter new username for user #" + userId + ":", currentUsername);
    if (newUsername !== null && newUsername.trim() !== "" && newUsername.trim() !== currentUsername) {
        document.getElementById('update_user_id').value = userId;
        document.getElementById('update_new_username').value = newUsername.trim();
        document.getElementById('updateUsernameForm').submit();
    }
}

function linkEmail(userId) {
    const emailToLink = prompt("Enter the Google Email that the user logged in with:\n(This will link their old account to their new Google login)", "");
    if (emailToLink !== null && emailToLink.trim() !== "") {
        document.getElementById('link_user_id').value = userId;
        document.getElementById('link_email_to_link').value = emailToLink.trim();
        document.getElementById('linkEmailForm').submit();
    }
}

function unlinkEmail(userId, email) {
    if (confirm("Are you sure you want to unlink the Google account (" + email + ") from user #" + userId + "?\n\nThey will no longer be able to log in to this account using Google.")) {
        document.getElementById('unlink_user_id').value = userId;
        document.getElementById('unlinkEmailForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>

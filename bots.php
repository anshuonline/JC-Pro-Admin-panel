<?php
// F:\APPS\JC Pro Admin panel\bots.php
require_once 'config.php';
check_auth();

// Check Bot Password
if (isset($_POST['bot_password']) && $_POST['bot_password'] === 'bots1234') {
    $_SESSION['bots_unlocked'] = true;
}
if (isset($_GET['lock_bots'])) {
    unset($_SESSION['bots_unlocked']);
}

if (!isset($_SESSION['bots_unlocked']) || $_SESSION['bots_unlocked'] !== true) {
    include 'includes/header.php';
    ?>
    <div class="flex items-center justify-center min-h-[60vh]">
        <div class="bg-white/60 backdrop-blur-xl p-8 rounded-2xl shadow-lg border border-white max-w-md w-full text-center">
            <i class="fa-solid fa-robot text-4xl text-indigo-500 mb-4"></i>
            <h2 class="text-2xl font-bold text-slate-800 mb-2">Bot Management Locked</h2>
            <p class="text-slate-500 text-sm mb-6">Please enter the security password to manage bots.</p>
            <form method="POST" action="bots.php">
                <input type="password" name="bot_password" required placeholder="Enter Password" class="w-full px-4 py-3 border border-slate-200/60 rounded-xl mb-4 bg-white/60 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all text-center">
                <button type="submit" class="w-full bg-indigo-500 hover:bg-indigo-600 text-white font-bold py-3 rounded-xl transition-all shadow-md shadow-indigo-500/20">Unlock Access</button>
            </form>
        </div>
    </div>
    <?php
    include 'includes/footer.php';
    exit;
}

$msg = '';
$err = '';

// Handle Delete All Bots
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_all') {
    $conn->query("DELETE FROM daily_counts WHERE user_id IN (SELECT id FROM users WHERE is_bot = 1)");
    $conn->query("DELETE FROM live_sessions WHERE user_id IN (SELECT id FROM users WHERE is_bot = 1)");
    $conn->query("DELETE FROM users WHERE is_bot = 1");
    $msg = "All bots deleted successfully!";
}

// Handle Fix Bot Names
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fix_names') {
    $conn->query("UPDATE users SET username = REPLACE(username, '_', ' ') WHERE is_bot = 1");
    $msg = "Bot names fixed successfully!";
}

// Handle Bot Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_bot') {
    $bot_name = trim($_POST['bot_name']);
    if (empty($bot_name)) {
        $err = "Bot name cannot be empty.";
    } else {
        $bot_name_esc = $conn->real_escape_string($bot_name);
        // Check if username exists
        $check = $conn->query("SELECT id FROM users WHERE username = '$bot_name_esc'");
        if ($check && $check->num_rows > 0) {
            $err = "Username already exists.";
        } else {
            // Insert bot
            $bot_mantra = isset($_POST['bot_mantra']) ? trim($_POST['bot_mantra']) : '';
            $bot_mantra_esc = $conn->real_escape_string($bot_mantra);
            $mantra_val = !empty($bot_mantra_esc) ? "'$bot_mantra_esc'" : "NULL";
            
            $insert = $conn->query("INSERT INTO users (username, device_token, level, total_counts, is_bot, bot_mantra, last_active) VALUES ('$bot_name_esc', 'bot_device', 1, 0, 1, $mantra_val, CURRENT_TIMESTAMP)");
            if ($insert) {
                $msg = "Bot '$bot_name' created successfully.";
            } else {
                $err = "Failed to create bot: " . $conn->error;
            }
        }
    }
}

// Handle Bot Edit Name
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_bot') {
    $bot_id = (int)$_POST['bot_id'];
    $new_name = trim($_POST['new_name']);
    if (!empty($new_name)) {
        $new_name_esc = $conn->real_escape_string($new_name);
        // Check if exists
        $check = $conn->query("SELECT id FROM users WHERE username = '$new_name_esc' AND id != $bot_id");
        if ($check && $check->num_rows > 0) {
            $err = "Username already in use.";
        } else {
            $conn->query("UPDATE users SET username = '$new_name_esc' WHERE id = $bot_id AND is_bot = 1");
            $msg = "Bot name updated.";
        }
    }
}

// Handle Bot Deletion
if (isset($_GET['delete'])) {
    $bot_id = (int)$_GET['delete'];
    // Delete only if it's a bot
    $conn->query("DELETE FROM users WHERE id = $bot_id AND is_bot = 1");
    if ($conn->affected_rows > 0) {
        $msg = "Bot deleted successfully.";
    }
}

// Search and Pagination
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where_clause = "WHERE is_bot = 1";
if ($search) {
    $search_esc = $conn->real_escape_string($search);
    $where_clause .= " AND username LIKE '%$search_esc%'";
}

// Total rows
$total_res = $conn->query("SELECT COUNT(*) as cnt FROM users $where_clause");
$total_rows = $total_res->fetch_assoc()['cnt'];
$total_pages = ceil($total_rows / $per_page);

// Fetch bots
$bots = [];
$res = $conn->query("SELECT * FROM users $where_clause ORDER BY id DESC LIMIT $per_page OFFSET $offset");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $bots[] = $row;
    }
}

include 'includes/header.php';
?>

<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Manage Bots <span class="bg-indigo-100 text-indigo-700 text-sm px-3 py-1 rounded-full ml-2 align-middle font-semibold border border-indigo-200"><?php echo $total_rows; ?> Total</span></h1>
        <p class="text-slate-500 text-sm mt-1 font-medium">Create and manage AI bots that simulate real users on the leaderboard.</p>
    </div>
    
    <form method="GET" action="bots.php" class="relative w-full md:w-64">
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
            <i class="fa-solid fa-search text-slate-400"></i>
        </div>
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
            class="block w-full pl-10 pr-3 py-2.5 border border-slate-200/60 rounded-xl bg-white/60 backdrop-blur-md text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white transition-all shadow-[0_2px_10px_rgb(0,0,0,0.02)]"
            placeholder="Search bots...">
    </form>
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

  <div class="mb-8 grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Create Bot Form -->
      <div class="bg-white/60 backdrop-blur-xl border border-white rounded-2xl shadow-[0_4px_24px_rgb(0,0,0,0.02)] overflow-hidden p-6">
          <h2 class="text-lg font-bold text-slate-800 mb-4">Create New Bot</h2>
          <form method="POST" action="bots.php" class="flex flex-col gap-4">
              <input type="hidden" name="action" value="add_bot">
              <input type="text" name="bot_name" required placeholder="Enter Bot Name (e.g. Rahul_Das)" class="block w-full px-4 py-2.5 border border-slate-200/60 rounded-xl bg-white/60 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-orange-500/50 transition-all">
              <input type="text" name="bot_mantra" placeholder="Mantra Name (e.g. Hare Krishna)" class="block w-full px-4 py-2.5 border border-slate-200/60 rounded-xl bg-white/60 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-orange-500/50 transition-all">
              <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2.5 rounded-xl font-bold shadow-md shadow-orange-500/20 transition-all flex items-center justify-center gap-2">
                  <i class="fa-solid fa-robot"></i> Generate Bot
              </button>
          </form>
      </div>
      
      <!-- Quick Actions -->
      <div class="bg-white/60 backdrop-blur-xl border border-white rounded-2xl shadow-[0_4px_24px_rgb(0,0,0,0.02)] overflow-hidden p-6 flex flex-col justify-center">
          <h2 class="text-lg font-bold text-slate-800 mb-4">Bot Operations</h2>
          <div class="flex flex-col gap-3">
              <form method="POST" action="bots.php" onsubmit="return confirm('Are you sure you want to remove underscores from all bot names?');">
                  <input type="hidden" name="action" value="fix_names">
                  <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white px-6 py-2.5 rounded-xl font-bold transition-all flex items-center justify-center gap-2">
                      <i class="fa-solid fa-wand-magic-sparkles"></i> Fix Bot Names (Remove _ )
                  </button>
              </form>
              
              <form method="POST" action="bots.php" onsubmit="return confirm('WARNING: This will permanently delete ALL bots from the database! Are you sure?');">
                  <input type="hidden" name="action" value="delete_all">
                  <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white px-6 py-2.5 rounded-xl font-bold shadow-md shadow-red-500/20 transition-all flex items-center justify-center gap-2">
                      <i class="fa-solid fa-trash-can"></i> Delete All Bots
                  </button>
              </form>
              
              <a href="bots.php?lock_bots=1" class="w-full bg-slate-200 hover:bg-slate-300 text-slate-700 px-6 py-2.5 rounded-xl font-bold transition-all flex items-center justify-center gap-2 text-center">
                  <i class="fa-solid fa-lock"></i> Lock Bot Page
              </a>
          </div>
      </div>
  </div>
  
  <!-- Bots Table -->
<div class="bg-white/60 backdrop-blur-xl border border-white rounded-2xl shadow-[0_4px_24px_rgb(0,0,0,0.02)] overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="text-xs uppercase bg-slate-50/50 text-slate-500 border-b border-slate-100">
                <tr>
                    <th scope="col" class="px-6 py-4 font-semibold">Bot ID</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Username</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Level</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Total Counts</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Last Active</th>
                    <th scope="col" class="px-6 py-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100/60">
                <?php if(empty($bots)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-slate-500">
                        <i class="fa-solid fa-robot text-4xl mb-3 block opacity-30"></i>
                        <span class="font-medium">No bots found. Create one above!</span>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach($bots as $bot): ?>
                    <tr class="hover:bg-white/60 transition-colors">
                        <td class="px-6 py-4 font-medium text-slate-500"><?php echo $bot['id']; ?></td>
                        <td class="px-6 py-4 font-semibold text-slate-800 flex items-center">
                            <div class="w-9 h-9 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center mr-3 font-bold border border-orange-200/50 shadow-sm">
                                <i class="fa-solid fa-robot text-sm"></i>
                            </div>
                            <!-- Edit name form inline -->
                            <form method="POST" action="bots.php" class="flex items-center gap-2">
                                <input type="hidden" name="action" value="edit_bot">
                                <input type="hidden" name="bot_id" value="<?php echo $bot['id']; ?>">
                                <input type="text" name="new_name" value="<?php echo htmlspecialchars($bot['username']); ?>" class="w-32 md:w-auto px-2 py-1 text-sm border border-slate-200 rounded focus:outline-none focus:border-orange-500">
                                <button type="submit" class="text-xs bg-slate-100 hover:bg-slate-200 px-2 py-1 rounded text-slate-600"><i class="fa-solid fa-save"></i></button>
                            </form>
                        </td>
                        <td class="px-6 py-4">
                            <span class="bg-slate-100 text-slate-600 py-1 px-3 rounded-full text-xs font-bold border border-slate-200/60">
                                Lvl <?php echo $bot['level']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 font-mono font-medium text-green-600">
                            <?php echo number_format($bot['total_counts']); ?>
                        </td>
                        <td class="px-6 py-4 text-slate-500 text-xs font-medium">
                            <?php echo date('M d, Y H:i', strtotime($bot['last_active'])); ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="?delete=<?php echo $bot['id']; ?>" onclick="return confirm('Are you sure you want to delete this bot?');" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition-colors font-medium text-xs">
                                <i class="fa-solid fa-trash mr-1"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="px-6 py-4 border-t border-slate-100/60 bg-white/40 flex items-center justify-between">
        <span class="text-sm text-slate-500 font-medium">
            Showing <span class="font-bold text-slate-800"><?php echo $offset + 1; ?></span> to 
            <span class="font-bold text-slate-800"><?php echo min($offset + $per_page, $total_rows); ?></span> of 
            <span class="font-bold text-slate-800"><?php echo $total_rows; ?></span> entries
        </span>
        <div class="flex items-center space-x-2">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="px-3.5 py-2 bg-white border border-slate-200 text-slate-700 rounded-xl hover:bg-slate-50 hover:text-blue-600 text-sm font-semibold transition-colors shadow-sm">Prev</a>
            <?php endif; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="px-3.5 py-2 bg-white border border-slate-200 text-slate-700 rounded-xl hover:bg-slate-50 hover:text-blue-600 text-sm font-semibold transition-colors shadow-sm">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>


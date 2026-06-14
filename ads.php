<?php
require_once 'config.php';
check_auth();

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ads_enabled = isset($_POST['ads_enabled']) ? 1 : 0;
    $interstitial = $conn->real_escape_string($_POST['interstitial']);
    $rewarded = $conn->real_escape_string($_POST['rewarded']);
    $app_open = $conn->real_escape_string($_POST['app_open']);

    $update_query = "UPDATE app_settings SET 
        ads_enabled = $ads_enabled,
        admob_interstitial_id = '$interstitial',
        admob_rewarded_id = '$rewarded',
        admob_app_open_id = '$app_open'
        WHERE id = 1";

    if ($conn->query($update_query)) {
        $message = '<div class="mb-4 p-4 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-3"><i class="fa-solid fa-circle-check"></i> Ad settings updated successfully!</div>';
    } else {
        $message = '<div class="mb-4 p-4 rounded-xl bg-red-50 text-red-700 border border-red-200 flex items-center gap-3"><i class="fa-solid fa-triangle-exclamation"></i> Error updating settings: ' . $conn->error . '</div>';
    }
}

// Fetch current settings
$settings = ['ads_enabled' => 1, 'admob_interstitial_id' => '', 'admob_rewarded_id' => '', 'admob_app_open_id' => ''];
$res = $conn->query("SELECT * FROM app_settings WHERE id = 1");
if ($res && $res->num_rows > 0) {
    $settings = $res->fetch_assoc();
}

include 'includes/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Ads Management</h1>
    <p class="text-slate-500 text-sm mt-1.5 font-medium">Configure AdMob IDs and toggle ads globally across the app.</p>
</div>

<?php echo $message; ?>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200/60 p-6 md:p-8 max-w-3xl">
    <form method="POST" action="">
        
        <!-- Global Toggle -->
        <div class="flex items-center justify-between p-5 bg-slate-50 rounded-xl border border-slate-200 mb-8">
            <div>
                <h3 class="text-base font-semibold text-slate-900">Global Ads Master Switch</h3>
                <p class="text-sm text-slate-500 mt-1">Turn off to completely disable all ads for all users immediately.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="ads_enabled" class="sr-only peer" <?php echo $settings['ads_enabled'] ? 'checked' : ''; ?>>
                <div class="w-14 h-7 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-orange-500"></div>
            </label>
        </div>

        <div class="space-y-6">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Interstitial Ad Unit ID</label>
                <input type="text" name="interstitial" value="<?php echo htmlspecialchars($settings['admob_interstitial_id']); ?>" required
                    class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:bg-white focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all font-mono text-sm"
                    placeholder="ca-app-pub-xxxxxxxxxxxxxxxx/xxxxxxxxxx">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Rewarded Ad Unit ID</label>
                <input type="text" name="rewarded" value="<?php echo htmlspecialchars($settings['admob_rewarded_id']); ?>" required
                    class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:bg-white focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all font-mono text-sm"
                    placeholder="ca-app-pub-xxxxxxxxxxxxxxxx/xxxxxxxxxx">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">App Open Ad Unit ID</label>
                <input type="text" name="app_open" value="<?php echo htmlspecialchars($settings['admob_app_open_id']); ?>" required
                    class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:bg-white focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all font-mono text-sm"
                    placeholder="ca-app-pub-xxxxxxxxxxxxxxxx/xxxxxxxxxx">
            </div>
        </div>

        <div class="mt-10">
            <button type="submit" class="w-full md:w-auto px-8 py-3 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl transition-all shadow-md shadow-slate-900/10 flex items-center justify-center gap-2">
                <i class="fa-solid fa-save"></i> Save Ad Settings
            </button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>

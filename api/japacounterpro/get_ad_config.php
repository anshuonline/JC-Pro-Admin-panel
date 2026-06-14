<?php
// F:\APPS\JC Pro Admin panel\api\japacounterpro\get_ad_config.php
require_once '../../config.php';

header('Content-Type: application/json');

$response = array(
    'success' => false,
    'global_ads_enabled' => true,
    'user_ads_disabled' => false,
    'interstitial_id' => 'ca-app-pub-3940256099942544/1033173712',
    'rewarded_id' => 'ca-app-pub-3940256099942544/5354046379',
    'app_open_id' => 'ca-app-pub-3940256099942544/9257395921'
);

// Fetch global settings
$res = $conn->query("SELECT * FROM app_settings WHERE id = 1");
if ($res && $res->num_rows > 0) {
    $settings = $res->fetch_assoc();
    $response['global_ads_enabled'] = (bool)$settings['ads_enabled'];
    
    // Only send the real IDs if they are configured and not empty
    if (!empty($settings['admob_interstitial_id'])) {
        $response['interstitial_id'] = $settings['admob_interstitial_id'];
    }
    if (!empty($settings['admob_rewarded_id'])) {
        $response['rewarded_id'] = $settings['admob_rewarded_id'];
    }
    if (!empty($settings['admob_app_open_id'])) {
        $response['app_open_id'] = $settings['admob_app_open_id'];
    }
}

// Check user specific setting
if (isset($_GET['username']) && !empty($_GET['username'])) {
    $username = $conn->real_escape_string($_GET['username']);
    $user_res = $conn->query("SELECT ads_disabled FROM users WHERE username = '$username'");
    if ($user_res && $user_res->num_rows > 0) {
        $user_data = $user_res->fetch_assoc();
        $response['user_ads_disabled'] = (bool)$user_data['ads_disabled'];
    }
}

$response['success'] = true;
echo json_encode($response);
?>

<?php
// F:\APPS\JC Pro Admin panel\includes\admin_logger.php

// Auto-create admin_logs table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_username VARCHAR(50) NOT NULL,
    action VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Ensure action column is wide enough
$conn->query("ALTER TABLE admin_logs MODIFY action VARCHAR(255) NOT NULL");

if (!function_exists('log_admin_action')) {
    function log_admin_action($conn, $username, $action) {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown';
        $city = 'Unknown';
        $state = 'Unknown';
        
        // Fetch location details from IP ONLY on LOGIN to save API requests
        if ($action === 'LOGIN' && $ip !== 'Unknown' && $ip !== '::1' && $ip !== '127.0.0.1') {
            $ch = curl_init("http://ip-api.com/json/$ip");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            $res = curl_exec($ch);
            curl_close($ch);
            if ($res) {
                $data = json_decode($res, true);
                if (isset($data['status']) && $data['status'] === 'success') {
                    $city = $data['city'] ?? 'Unknown';
                    $state = $data['regionName'] ?? 'Unknown';
                }
            }
        } else {
            // For other actions, try to get the last known location for this admin to avoid API calls
            $username_esc = $conn->real_escape_string($username);
            $last_loc = $conn->query("SELECT city, state FROM admin_logs WHERE admin_username = '$username_esc' AND city != 'Unknown' ORDER BY id DESC LIMIT 1");
            if ($last_loc && $last_loc->num_rows > 0) {
                $loc_row = $last_loc->fetch_assoc();
                $city = $loc_row['city'];
                $state = $loc_row['state'];
            }
        }
        
        $username_esc = $conn->real_escape_string($username);
        $action_esc = $conn->real_escape_string($action);
        $ip_esc = $conn->real_escape_string($ip);
        $city_esc = $conn->real_escape_string($city);
        $state_esc = $conn->real_escape_string($state);
        
        $conn->query("INSERT INTO admin_logs (admin_username, action, ip_address, city, state) 
                      VALUES ('$username_esc', '$action_esc', '$ip_esc', '$city_esc', '$state_esc')");
    }
}

// Automatically log all POST requests made by the admin
$current_admin = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : (isset($_SESSION['admin_logged_in']) ? 'admin' : null);

if ($current_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $page_name = basename($_SERVER['PHP_SELF']);
    $action_detail = isset($_POST['action']) ? $_POST['action'] : 'Submitted form';
    log_admin_action($conn, $current_admin, "Action ($action_detail) on $page_name");
}
?>

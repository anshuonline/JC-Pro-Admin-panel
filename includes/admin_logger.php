<?php
// F:\APPS\JC Pro Admin panel\includes\admin_logger.php

// Auto-create admin_logs table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_username VARCHAR(50) NOT NULL,
    action VARCHAR(20) NOT NULL,
    ip_address VARCHAR(45) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

if (!function_exists('log_admin_action')) {
    function log_admin_action($conn, $username, $action) {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown';
        $city = 'Unknown';
        $state = 'Unknown';
        
        // Fetch location details from IP
        if ($ip !== 'Unknown' && $ip !== '::1' && $ip !== '127.0.0.1') {
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
?>

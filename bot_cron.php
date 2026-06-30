<?php
// F:\APPS\JC Pro Admin panel\bot_cron.php

// Do not require authentication for cron script, but include DB config
require_once 'config.php';

// A simple security key (pass ?key=secret123 to execute)
$secret_key = "secret123";

if (!isset($_GET['key']) || $_GET['key'] !== $secret_key) {
    die("Unauthorized");
}

set_time_limit(0);
ignore_user_abort(true);

echo "Starting Bot Routine...<br>";

if (file_exists('bots_paused.txt')) {
    echo "Routine aborted: Bots are currently PAUSED.<br>";
    exit;
}

$limit = 4000;
if (file_exists('bot_limit.txt')) {
    $val = (int)file_get_contents('bot_limit.txt');
    if ($val > 0) $limit = $val;
}

// 1. Get all bots (Optimized to prevent Hostinger suspension: avoids slow RAND() on 50k rows)
$bots_res = $conn->query("SELECT id, username, total_counts FROM users WHERE is_bot = 1 ORDER BY last_active ASC LIMIT $limit");

if ($bots_res && $bots_res->num_rows > 0) {
    $today = date('Y-m-d');
    
    while ($bot = $bots_res->fetch_assoc()) {
        $bot_id = $bot['id'];
        $bot_name = $bot['username'];
        $current_total = $bot['total_counts'];
        
        // Check current daily count for this bot
        $daily_res = $conn->query("SELECT daily_count FROM daily_counts WHERE user_id = $bot_id AND date = '$today'");
        $current_daily = 0;
        
        if ($daily_res && $daily_res->num_rows > 0) {
            $row = $daily_res->fetch_assoc();
            $current_daily = $row['daily_count'];
        }
        
                // Add a random amount of counts (between 5 and 25 per run)
                // Assuming cron runs every 15-30 mins, 5-25 is realistic for a short session
                $increment = rand(50, 150);
                
                
                $new_daily = $current_daily + $increment;
                $new_total = $current_total + $increment;
                
                // Calculate new level based on simple math (e.g., level = total_counts / 1000 + 1)
                $new_level = max(1, floor($new_total / 1000) + 1);
                
                // Update users table: Set new totals, new level, and update last_active to CURRENT_TIMESTAMP
                $conn->query("UPDATE users SET total_counts = $new_total, level = $new_level, last_active = CURRENT_TIMESTAMP WHERE id = $bot_id");
                
                // Update daily_counts
                if ($daily_res && $daily_res->num_rows > 0) {
                    $conn->query("UPDATE daily_counts SET daily_count = $new_daily WHERE user_id = $bot_id AND date = '$today'");
                } else {
                    $conn->query("INSERT INTO daily_counts (user_id, date, daily_count) VALUES ($bot_id, '$today', $new_daily)");
                }
                
                // Update live_sessions to show on Live Activity feed
                $random_duration = rand(60, 600); // Between 1 and 10 minutes active
                $conn->query("INSERT INTO live_sessions (user_id, username, session_count, last_heartbeat, started_at) 
                              VALUES ($bot_id, '$bot_name', $increment, CURRENT_TIMESTAMP + INTERVAL 35 SECOND, CURRENT_TIMESTAMP - INTERVAL $random_duration SECOND)
                              ON DUPLICATE KEY UPDATE 
                              last_heartbeat = CURRENT_TIMESTAMP + INTERVAL 35 SECOND, 
                              session_count = session_count + $increment");
                
                echo "Bot: $bot_name - Chanted $increment times. Total Today: $new_daily<br>";

    }
} else {
    echo "No bots found.<br>";
}

// ==========================================
// BACKGROUND AUTOMATIC CLEANUP TASKS
// ==========================================
// 1. Delete analytics_events older than 60 days
$conn->query("DELETE FROM analytics_events WHERE created_at < NOW() - INTERVAL 60 DAY");
// 2. Delete dead live_sessions older than 10 minutes
$conn->query("DELETE FROM live_sessions WHERE last_heartbeat < NOW() - INTERVAL 10 MINUTE");
// 3. Delete daily_counts older than 60 days (User request to save DB space)
$conn->query("DELETE FROM daily_counts WHERE date < DATE_SUB(CURDATE(), INTERVAL 60 DAY)");

// ==========================================
// BACKGROUND AI CHAT SIMULATION
// ==========================================
// Ensure all bots have a google_uid so they can chat in global_chat
$conn->query("UPDATE users SET google_uid = CONCAT('bot_', id) WHERE is_bot = 1 AND (google_uid IS NULL OR google_uid = '')");

// 60% chance to simulate a chat between two bots per cron run
if (rand(1, 100) <= 60) {
    require_once 'api/japacounterpro/bot_config.php';
    if (defined('GEMINI_API_KEY')) {
        $chat_bots = $conn->query("SELECT google_uid, username FROM users WHERE is_bot = 1 ORDER BY RAND() LIMIT 2");
        if ($chat_bots && $chat_bots->num_rows == 2) {
            $bot1 = $chat_bots->fetch_assoc();
            $bot2 = $chat_bots->fetch_assoc();
            
            $prompt = "You are two friends, {$bot1['username']} and {$bot2['username']}, casually chatting on a meditation app (JapaCounter) global chat. Generate a short 2-message conversation in Hinglish discussing chanting, daily life, or greeting each other. Keep it very natural and max 1 sentence each. Format exactly like this with no other text or markdown:\n{$bot1['username']}: <message>\n{$bot2['username']}: <message>";
            
            $gemini_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . GEMINI_API_KEY;
            $postData = json_encode([
                "contents" => [
                    [
                        "parts" => [
                            ["text" => $prompt]
                        ]
                    ]
                ]
            ]);
            
            $ch = curl_init($gemini_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $api_response = curl_exec($ch);
            curl_close($ch);
            
            if ($api_response) {
                $json_res = json_decode($api_response, true);
                if (isset($json_res['candidates'][0]['content']['parts'][0]['text'])) {
                    $chat_text = trim($json_res['candidates'][0]['content']['parts'][0]['text']);
                    $lines = explode("\n", $chat_text);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (empty($line)) continue;
                        
                        $bot_uid = null;
                        $msg = "";
                        
                        // Check which bot is speaking (case insensitive)
                        if (stripos($line, $bot1['username'] . ":") === 0) {
                            $bot_uid = $bot1['google_uid'];
                            $msg = trim(substr($line, strlen($bot1['username']) + 1));
                        } elseif (stripos($line, $bot2['username'] . ":") === 0) {
                            $bot_uid = $bot2['google_uid'];
                            $msg = trim(substr($line, strlen($bot2['username']) + 1));
                        }
                        
                        if ($bot_uid && !empty($msg)) {
                            // Strip any bold markdown ** just in case
                            $msg = str_replace("**", "", $msg);
                            $msg_safe = $conn->real_escape_string($msg);
                            $conn->query("INSERT INTO global_chat (google_uid, message) VALUES ('$bot_uid', '$msg_safe')");
                            sleep(rand(3, 7)); // Realistic typing delay between messages
                        }
                    }
                    echo "AI Chat Simulated between {$bot1['username']} and {$bot2['username']}.<br>";
                }
            }
        }
    }
}


// ==========================================
// BACKGROUND IP LOCATION SYNC
// ==========================================
echo "<br>Starting IP Location Sync...<br>";

// Ensure city and state columns exist
$check_city = $conn->query("SHOW COLUMNS FROM users LIKE 'city'");
if ($check_city && $check_city->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN city VARCHAR(100) NULL, ADD COLUMN state VARCHAR(100) NULL");
}

// Get up to 100 unique IPs that don't have a city yet
$ip_res = $conn->query("SELECT DISTINCT ip_address FROM users WHERE ip_address IS NOT NULL AND ip_address != '' AND city IS NULL LIMIT 100");
if ($ip_res && $ip_res->num_rows > 0) {
    $ips_to_sync = [];
    while ($row = $ip_res->fetch_assoc()) {
        $ips_to_sync[] = $row['ip_address'];
    }
    
    // Call ip-api.com batch endpoint
    $ch = curl_init('http://ip-api.com/batch');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($ips_to_sync));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $locations = json_decode($response, true);
        if (is_array($locations)) {
            foreach ($locations as $loc) {
                if (isset($loc['query']) && $loc['status'] === 'success') {
                    $ip = $conn->real_escape_string($loc['query']);
                    $city = isset($loc['city']) ? $conn->real_escape_string($loc['city']) : 'Unknown';
                    $state = isset($loc['regionName']) ? $conn->real_escape_string($loc['regionName']) : 'Unknown';
                    
                    // Update all users with this IP
                    $conn->query("UPDATE users SET city = '$city', state = '$state' WHERE ip_address = '$ip' AND city IS NULL");
                } else if (isset($loc['query']) && $loc['status'] === 'fail') {
                    $ip = $conn->real_escape_string($loc['query']);
                    $conn->query("UPDATE users SET city = 'Unknown', state = 'Unknown' WHERE ip_address = '$ip' AND city IS NULL");
                }
            }
            echo "Synced locations for " . count($locations) . " IPs.<br>";
        }
    }
} else {
    echo "No new IPs to sync.<br>";
}

echo "Routine and Cleanups Completed.";
?>





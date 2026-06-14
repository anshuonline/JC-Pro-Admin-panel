<?php
// F:\APPS\JC Pro Admin panel\bot_cron.php

// Do not require authentication for cron script, but include DB config
require_once 'config.php';

// A simple security key (pass ?key=secret123 to execute)
$secret_key = "secret123";

if (!isset($_GET['key']) || $_GET['key'] !== $secret_key) {
    die("Unauthorized");
}

echo "Starting Bot Routine...<br>";

// 1. Get all bots
$bots_res = $conn->query("SELECT id, username, total_counts FROM users WHERE is_bot = 1");

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
        
        // Max limit of 500 per day to keep it realistic
        if ($current_daily < 500) {
            // Randomly decide if this bot is active this run (e.g. 50% chance)
            if (rand(1, 100) > 50) {
                // Add a random amount of counts (between 5 and 25 per run)
                // Assuming cron runs every 15-30 mins, 5-25 is realistic for a short session
                $increment = rand(5, 25);
                
                // If adding increment exceeds 500, cap it
                if ($current_daily + $increment > 500) {
                    $increment = 500 - $current_daily;
                }
                
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
                              VALUES ($bot_id, '$bot_name', $increment, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP - INTERVAL $random_duration SECOND)
                              ON DUPLICATE KEY UPDATE 
                              last_heartbeat = CURRENT_TIMESTAMP, 
                              session_count = session_count + $increment");
                
                echo "Bot: $bot_name - Chanted $increment times. Total Today: $new_daily<br>";
            } else {
                echo "Bot: $bot_name - Resting right now.<br>";
            }
        } else {
            echo "Bot: $bot_name - Already reached daily target of 500.<br>";
        }
    }
} else {
    echo "No bots found.";
}

echo "Routine Completed.";
?>

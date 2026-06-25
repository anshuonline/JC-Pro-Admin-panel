<?php
header('Content-Type: application/json');
require_once '../../config.php';

$tests = [];

// Test 1: DB Connection
$tests['db_connected'] = $conn ? true : false;
$tests['db_error'] = $conn ? null : mysqli_connect_error();

// Test 2: users table exists?
$r = $conn->query("SHOW TABLES LIKE 'users'");
$tests['users_table_exists'] = $r && $r->num_rows > 0;

// Test 3: daily_counts table exists?
$r2 = $conn->query("SHOW TABLES LIKE 'daily_counts'");
$tests['daily_counts_table_exists'] = $r2 && $r2->num_rows > 0;

// Test 4: daily_counts UNIQUE KEY exists?
$r3 = $conn->query("SHOW INDEX FROM daily_counts WHERE Key_name = 'idx_user_date'");
$tests['unique_key_exists'] = $r3 && $r3->num_rows > 0;

// Test 5: Try inserting a test user
$testUser = 'DEBUG_TEST_' . time();
$res = $conn->query("INSERT IGNORE INTO users (username, device_token, level, total_counts, is_bot, bot_mantra, ads_disabled, last_active, created_at) VALUES ('$testUser', '', 1, 0, 0, 0, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
$tests['test_user_insert_success'] = $res ? true : false;
$tests['test_user_insert_error'] = $res ? null : $conn->error;
$tests['test_user_id'] = $res ? $conn->insert_id : null;

// Test 6: Clean up test user
if ($res) {
    $conn->query("DELETE FROM users WHERE username = '$testUser'");
}

// Test 7: users table columns
$r4 = $conn->query("SHOW COLUMNS FROM users");
$cols = [];
while ($row = $r4->fetch_assoc()) {
    $cols[] = $row['Field'] . ' (' . $row['Type'] . ') ' . ($row['Null'] === 'NO' && $row['Default'] === null && $row['Extra'] !== 'auto_increment' ? '⚠️ NOT NULL NO DEFAULT' : 'OK');
}
$tests['users_columns'] = $cols;

echo json_encode($tests, JSON_PRETTY_PRINT);
?>

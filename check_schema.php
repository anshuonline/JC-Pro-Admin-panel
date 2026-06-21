<?php
require_once 'config.php';
$r = $conn->query('SHOW CREATE TABLE live_sessions');
if ($r) {
    print_r($r->fetch_assoc());
} else {
    echo "No live_sessions table.\n";
}
$r = $conn->query('SHOW CREATE TABLE daily_counts');
if ($r) {
    print_r($r->fetch_assoc());
} else {
    echo "No daily_counts table.\n";
}
// Clean up duplicates script test
$r = $conn->query('SELECT COUNT(*) as c FROM live_sessions');
echo "live_sessions count: " . $r->fetch_assoc()['c'] . "\n";
$r = $conn->query('SELECT COUNT(*) as c FROM daily_counts');
echo "daily_counts count: " . $r->fetch_assoc()['c'] . "\n";
?>

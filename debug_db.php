<?php
require_once 'config.php';
$r1 = $conn->query("SHOW CREATE TABLE users");
echo "USERS SCHEMA:\n";
print_r($r1->fetch_assoc());

$r2 = $conn->query("SHOW CREATE TABLE daily_counts");
echo "\nDAILY COUNTS SCHEMA:\n";
print_r($r2->fetch_assoc());
?>

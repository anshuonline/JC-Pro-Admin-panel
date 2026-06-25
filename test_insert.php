<?php
require_once 'config.php';

$username = "TestUserDebug123";
$res = $conn->query("INSERT INTO users (username) VALUES ('$username')");

if (!$res) {
    echo "ERROR: " . $conn->error;
} else {
    echo "SUCCESS";
}
?>

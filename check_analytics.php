<?php
require_once 'config.php';
$r = $conn->query("SHOW CREATE TABLE analytics_events");
if ($r) {
    print_r($r->fetch_assoc());
} else {
    echo "Table not found";
}

<?php
require_once 'config.php';
$conn->query("ALTER TABLE feedback ADD COLUMN is_featured TINYINT(1) DEFAULT 0");
echo "Column added or already exists.";
?>

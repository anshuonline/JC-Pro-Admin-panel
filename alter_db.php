<?php
require_once 'config.php';

try {
    $check_col = $conn->query("SHOW COLUMNS FROM feedback LIKE 'is_featured'");
    if ($check_col && $check_col->num_rows == 0) {
        $conn->query("ALTER TABLE feedback ADD COLUMN is_featured TINYINT(1) DEFAULT 0");
        echo "Column 'is_featured' added successfully.";
    } else {
        echo "Column 'is_featured' already exists.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

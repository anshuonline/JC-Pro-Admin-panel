<?php
require_once 'config.php';
check_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    
    // Check current status
    $res = $conn->query("SELECT is_featured FROM feedback WHERE id = $id");
    if ($res && $row = $res->fetch_assoc()) {
        $new_status = $row['is_featured'] ? 0 : 1;
        $conn->query("UPDATE feedback SET is_featured = $new_status WHERE id = $id");
        
        echo json_encode(['status' => 'success', 'is_featured' => $new_status]);
        exit();
    }
}
echo json_encode(['status' => 'error']);
?>

<?php
// C:\xampp\htdocs\JC Pro Admin panel\delete_feedback.php
require_once 'config.php';
check_auth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$action = $_POST['action'] ?? '';
$ids_json = $_POST['ids'] ?? '[]';
$ids = json_decode($ids_json, true);

if ($action === 'delete' && is_array($ids) && count($ids) > 0) {
    // Sanitize IDs
    $clean_ids = [];
    foreach ($ids as $id) {
        $id = (int)$id;
        if ($id > 0) {
            $clean_ids[] = $id;
        }
    }
    
    if (count($clean_ids) > 0) {
        $ids_str = implode(',', $clean_ids);
        
        $sql = "DELETE FROM feedback WHERE id IN ($ids_str)";
        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'message' => 'Feedback deleted successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No valid IDs provided.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters.']);
}
?>

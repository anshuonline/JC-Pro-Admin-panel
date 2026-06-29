<?php
require_once 'config.php';

header('Content-Type: application/json');

$response = array(
    'success' => false,
    'update_available' => false
);

// Fetch the latest active update
$sql = "SELECT version_code, version_name, apk_url, release_notes, is_mandatory FROM app_updates ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $update = $result->fetch_assoc();
    $response['success'] = true;
    $response['update_available'] = true;
    $response['update_info'] = array(
        'version_code' => (int)$update['version_code'],
        'version_name' => $update['version_name'],
        'apk_url' => $update['apk_url'],
        'release_notes' => $update['release_notes'],
        'is_mandatory' => (bool)$update['is_mandatory']
    );
} else {
    // No updates in table
    $response['success'] = true;
    $response['update_available'] = false;
}

echo json_encode($response);
$conn->close();
?>

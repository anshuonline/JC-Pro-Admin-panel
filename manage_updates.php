<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_update'])) {
    $version_code = (int)$_POST['version_code'];
    $version_name = $conn->real_escape_string($_POST['version_name']);
    $apk_url = $conn->real_escape_string($_POST['apk_url']);
    $release_notes = $conn->real_escape_string($_POST['release_notes']);
    $is_mandatory = isset($_POST['is_mandatory']) ? 1 : 0;

    $sql = "INSERT INTO app_updates (version_code, version_name, apk_url, release_notes, is_mandatory) 
            VALUES ($version_code, '$version_name', '$apk_url', '$release_notes', $is_mandatory)";
            
    if ($conn->query($sql) === TRUE) {
        $message = "<div style='color: green; margin-bottom: 15px;'>Update pushed successfully! Users will now see this update.</div>";
    } else {
        $message = "<div style='color: red; margin-bottom: 15px;'>Error: " . $conn->error . "</div>";
    }
}

// Fetch current latest update
$latest_update = null;
$result = $conn->query("SELECT * FROM app_updates ORDER BY id DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $latest_update = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage App Updates - JapaCounter Pro</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; color: #555; }
        input[type="text"], input[type="number"], textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        textarea { height: 100px; resize: vertical; }
        .checkbox-group { display: flex; align-items: center; margin-bottom: 20px; }
        .checkbox-group input { margin-right: 10px; width: auto; }
        button { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #0056b3; }
        .current-update { background-color: #e9ecef; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #007bff; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <a href="index.php" class="back-link">&larr; Back to Dashboard</a>
    <h1>Manage App Updates</h1>
    
    <?php echo $message; ?>

    <div class="current-update">
        <h3>Current Live Update</h3>
        <?php if ($latest_update): ?>
            <p><strong>Version Code:</strong> <?php echo htmlspecialchars($latest_update['version_code']); ?></p>
            <p><strong>Version Name:</strong> <?php echo htmlspecialchars($latest_update['version_name']); ?></p>
            <p><strong>APK URL:</strong> <a href="<?php echo htmlspecialchars($latest_update['apk_url']); ?>" target="_blank">Link</a></p>
            <p><strong>Mandatory:</strong> <?php echo $latest_update['is_mandatory'] ? '<span style="color:red;font-weight:bold;">YES</span>' : 'No'; ?></p>
            <p><strong>Notes:</strong><br><?php echo nl2br(htmlspecialchars($latest_update['release_notes'])); ?></p>
            <p><em>Pushed on: <?php echo $latest_update['created_at']; ?></em></p>
        <?php else: ?>
            <p>No updates pushed yet.</p>
        <?php endif; ?>
    </div>

    <h3>Push New Update</h3>
    <p>Fill this form to notify all users about a new version. They will see the update screen in the app.</p>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="version_code">New Version Code (Number, e.g. 15):</label>
            <input type="number" id="version_code" name="version_code" required placeholder="Must be higher than current BuildConfig.VERSION_CODE">
        </div>
        
        <div class="form-group">
            <label for="version_name">New Version Name (Text, e.g. 1.2.5):</label>
            <input type="text" id="version_name" name="version_name" required placeholder="e.g. 1.2.5">
        </div>
        
        <div class="form-group">
            <label for="apk_url">Direct APK Download URL:</label>
            <input type="url" id="apk_url" name="apk_url" required placeholder="https://yourserver.com/JapaCounterPro.apk">
        </div>
        
        <div class="form-group">
            <label for="release_notes">Release Notes / What's New:</label>
            <textarea id="release_notes" name="release_notes" placeholder="- Bug fixes&#10;- New UI..."></textarea>
        </div>
        
        <div class="checkbox-group">
            <input type="checkbox" id="is_mandatory" name="is_mandatory" value="1" checked>
            <label for="is_mandatory" style="margin: 0;">Force Update (Mandatory)? Users cannot skip it.</label>
        </div>
        
        <button type="submit" name="submit_update">Push Update to All Users</button>
    </form>
</div>

</body>
</html>

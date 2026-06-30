<?php
// Gemini AI Setup for Bot
define('GEMINI_API_KEY', 'AQ.Ab8RN' . '6I8MVIkX57DhhSL2' . 'guwUK8-07kQz9E' . 'MzuJu2cWgBkqxWQ');
define('BOT_USERNAME', 'JapaGuru');
define('BOT_GOOGLE_UID', 'gemini_ai_bot');
define('BOT_PROFILE_PICTURE', 'https://ui-avatars.com/api/?name=Japa+Guru&background=0D8ABC&color=fff');

// Ensure Bot User exists in DB
if (isset($conn)) {
    $bot_uid = $conn->real_escape_string(BOT_GOOGLE_UID);
    $bot_user = $conn->real_escape_string(BOT_USERNAME);
    $bot_pic = $conn->real_escape_string(BOT_PROFILE_PICTURE);
    $conn->query("INSERT IGNORE INTO users (google_uid, username, email, profile_picture, level, is_premium) VALUES ('$bot_uid', '$bot_user', 'bot@japacounter.pro', '$bot_pic', 99, 1)");
}
?>

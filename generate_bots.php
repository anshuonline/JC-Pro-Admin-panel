<?php
require_once 'config.php';

$bot_count = 4000;
$inserted = 0;

echo "Starting to generate $bot_count bots...<br>";

// Insert in chunks for better performance
$chunk_size = 500;
$chunks = ceil($bot_count / $chunk_size);

for ($c = 0; $c < $chunks; $c++) {
    $sql = "INSERT INTO users (username, device_token, level, total_counts, is_bot, bot_mantra, last_active) VALUES ";
    $values = [];
    
    $start = $c * $chunk_size;
    $end = min($bot_count, $start + $chunk_size);
    
    for ($i = $start; $i < $end; $i++) {
        $rand_num = rand(10000, 99999);
        $bot_name = "Devotee_" . $rand_num;
        $values[] = "('$bot_name', 'bot_device_bulk', 1, 0, 1, 'Hare Krishna', CURRENT_TIMESTAMP)";
    }
    
    $sql .= implode(", ", $values);
    
    if ($conn->query($sql)) {
        $inserted += ($end - $start);
        echo "Inserted $inserted bots...<br>";
    } else {
        echo "Error: " . $conn->error . "<br>";
        break;
    }
}

echo "<br><b>Successfully generated $inserted new bots!</b>";
?>

<?php
require_once "config.php";

// Automatically delete orders older than 7 days
$conn->query("DELETE FROM order_lists WHERE created_at < NOW() - INTERVAL 7 DAY");

// Optional: log it
file_put_contents("cron_log.txt", "[" . date("Y-m-d H:i:s") . "] Old orders deleted\n", FILE_APPEND);
?>

<?php
// F:\APPS\JC Pro Admin panel\logout.php
session_start();
require_once 'config.php';

if (isset($_SESSION['admin_username'])) {
    log_admin_action($conn, $_SESSION['admin_username'], 'LOGOUT');
}

session_destroy();
header("Location: index.php");
exit();
?>

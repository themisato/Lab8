<?php
// admin_logout.php - Выход из админки
session_start();
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_login']);
header('Location: admin_login.php');
exit;
?>
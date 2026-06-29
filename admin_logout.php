<?php
// admin_logout.php - Выход из админки
session_start();

// Очищаем данные администратора
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_login']);

// Перенаправляем на страницу входа в админку
header('Location: admin_login.php');
exit;
?>
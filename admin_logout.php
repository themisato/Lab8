<?php
// admin_logout.php - Выход из админки
// Настройки сессии ДО session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_only_cookies', 1);
ini_set('session.gc_maxlifetime', 3600);

session_start();

// Очищаем данные администратора
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_login']);

// Перенаправляем на страницу входа в админку
header('Location: admin_login.php');
exit;
?>
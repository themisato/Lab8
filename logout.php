<?php
// logout.php - Выход из системы (для пользователей)
// Настройки сессии ДО session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_only_cookies', 1);
ini_set('session.gc_maxlifetime', 3600);

session_start();

// Очищаем все сессионные данные
$_SESSION = array();

// Удаляем куки сессии
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Уничтожаем сессию
session_destroy();

// Перенаправляем на главную
header('Location: index.php');
exit;
?>
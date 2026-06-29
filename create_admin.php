<?php
// create_admin.php - Создание администратора
// Настройки сессии ДО session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_only_cookies', 1);
ini_set('session.gc_maxlifetime', 3600);

session_start();
require_once 'config.php';

$login = 'admin';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Проверяем существование таблицы admin с префиксом
    $check = $pdo->query("SHOW TABLES LIKE '" . table('admin') . "'");
    if ($check->rowCount() == 0) {
        $pdo->exec("CREATE TABLE " . table('admin') . " (
            id INT AUTO_INCREMENT PRIMARY KEY,
            login VARCHAR(50) UNIQUE,
            password_hash VARCHAR(255)
        )");
        echo "✅ Таблица " . table('admin') . " создана<br>";
    }
    
    // Проверяем, есть ли уже администратор
    $check_admin = $pdo->prepare("SELECT COUNT(*) FROM " . table('admin') . " WHERE login = ?");
    $check_admin->execute([$login]);
    $exists = $check_admin->fetchColumn();
    
    if ($exists > 0) {
        echo "⚠️ Администратор с логином '<strong>$login</strong>' уже существует<br>";
        echo "Логин: <strong>$login</strong><br>";
        echo "Пароль: <strong>$password</strong><br>";
        echo "<a href='admin_login.php'>Войти в админку</a>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO " . table('admin') . " (login, password_hash) VALUES (?, ?)");
        $stmt->execute([$login, $hash]);
        echo "✅ Администратор создан!<br>";
        echo "Логин: <strong>$login</strong><br>";
        echo "Пароль: <strong>$password</strong><br>";
        echo "<a href='admin_login.php'>Войти в админку</a>";
    }
    
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo "⚠️ Администратор уже существует<br>";
        echo "Логин: <strong>$login</strong><br>";
        echo "Пароль: <strong>$password</strong><br>";
        echo "<a href='admin_login.php'>Войти в админку</a>";
    } else {
        echo "❌ Ошибка: " . $e->getMessage();
    }
}
?>
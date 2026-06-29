<?php
// create_admin.php - Создание администратора
require_once 'config.php';

$login = 'admin';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $check = $pdo->query("SHOW TABLES LIKE 'admin'");
    if ($check->rowCount() == 0) {
        $pdo->exec("CREATE TABLE admin (
            id INT AUTO_INCREMENT PRIMARY KEY,
            login VARCHAR(50) UNIQUE,
            password_hash VARCHAR(255)
        )");
        echo "✅ Таблица admin создана<br>";
    }
    $stmt = $pdo->prepare("INSERT INTO admin (login, password_hash) VALUES (?, ?)");
    $stmt->execute([$login, $hash]);
    echo "✅ Администратор создан!<br>";
    echo "Логин: <strong>$login</strong><br>";
    echo "Пароль: <strong>$password</strong><br>";
    echo "<a href='admin_login.php'>Войти в админку</a>";
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
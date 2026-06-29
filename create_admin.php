<?php
// create_admin.php - Создание администратора в БД
require_once 'config.php';

$login = 'admin';
$password = 'admin123';
$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $check = $pdo->query("SHOW TABLES LIKE 'admin'");
    if ($check->rowCount() == 0) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `admin` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `login` VARCHAR(50) UNIQUE NOT NULL,
                `password_hash` VARCHAR(255) NOT NULL
            )
        ");
        echo "✅ Таблица 'admin' создана<br>";
    }
    
    $stmt = $pdo->prepare("INSERT INTO admin (login, password_hash) VALUES (?, ?)");
    $stmt->execute([$login, $password_hash]);
    
    echo "✅ Администратор создан!<br>";
    echo "🔑 Логин: <strong>$login</strong><br>";
    echo "🔒 Пароль: <strong>$password</strong><br>";
    echo "<br><a href='admin_login.php'>Перейти к входу в админку</a>";
    
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo "⚠️ Администратор с логином '$login' уже существует!<br>";
        echo "Пароль: <strong>$password</strong><br>";
        echo "<br><a href='admin_login.php'>Перейти к входу в админку</a>";
    } else {
        echo "❌ Ошибка: " . $e->getMessage();
    }
}
?>
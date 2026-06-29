<?php
require_once 'config.php';

// Генерируем правильный хеш для пароля admin123 через PHP
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Пароль: " . $password . "<br>";
echo "Сгенерированный хеш: " . $hash . "<br><br>";

// Удаляем старого админа
try {
    $pdo->exec("DELETE FROM " . table('admin') . " WHERE login = 'admin'");
    echo "✅ Старый admin удален<br>";
} catch (PDOException $e) {
    echo "⚠️ Ошибка удаления: " . $e->getMessage() . "<br>";
}

// Создаем нового с правильным хешем
try {
    $stmt = $pdo->prepare("INSERT INTO " . table('admin') . " (login, password_hash) VALUES (?, ?)");
    $stmt->execute(['admin', $hash]);
    echo "✅ Новый admin создан<br><br>";
} catch (PDOException $e) {
    echo "❌ Ошибка создания: " . $e->getMessage() . "<br>";
}

// Проверяем
$stmt = $pdo->query("SELECT * FROM " . table('admin') . " WHERE login = 'admin'");
$admin = $stmt->fetch();

if ($admin) {
    echo "📋 Данные в БД:<br>";
    echo "ID: " . $admin['id'] . "<br>";
    echo "Login: " . $admin['login'] . "<br>";
    echo "Hash: " . $admin['password_hash'] . "<br><br>";
    
    if (password_verify($password, $admin['password_hash'])) {
        echo "✅✅✅ ПАРОЛЬ ВЕРНЫЙ! Теперь можно войти с логином 'admin' и паролем 'admin123'<br>";
        echo "<a href='admin_login.php' style='display:inline-block;padding:10px 20px;background:green;color:white;text-decoration:none;border-radius:5px;'>Перейти к входу</a>";
    } else {
        echo "❌ Пароль все еще неверный<br>";
    }
}
?>
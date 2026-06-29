<?php
require_once 'config.php';

// Проверяем подключение
echo "✅ Подключение к БД: OK<br>";

// Проверяем таблицу admin
$stmt = $pdo->query("SELECT * FROM " . table('admin'));
$admins = $stmt->fetchAll();

echo "<pre>";
print_r($admins);
echo "</pre>";

// Проверяем хеш пароля
if (!empty($admins)) {
    $password = 'admin123';
    $hash = $admins[0]['password_hash'];
    
    echo "Пароль: admin123<br>";
    echo "Хеш из БД: " . $hash . "<br>";
    
    if (password_verify($password, $hash)) {
        echo "✅ Пароль верный!<br>";
    } else {
        echo "❌ Пароль НЕ верный!<br>";
    }
}
?>
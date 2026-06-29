<?php
// config.php - НЕТ session_start()!

// ===== ЗАГОЛОВКИ БЕЗОПАСНОСТИ =====
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// ===== ПОДКЛЮЧЕНИЕ К БД =====
$host = 'localhost';
$dbname = 'u82686';
$username = 'u82686';
$password = '8078259';

// Префикс для таблиц (чтобы не пересекаться с другими заданиями)
define('DB_PREFIX', 'lab8_');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("DB Connection Error: " . $e->getMessage());
    die("Извините, временные технические проблемы. Попробуйте позже.");
}

define('SECRET_KEY', 'your-secret-key-here-change-it-2026');

// ===== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ =====
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Функция для получения имени таблицы с префиксом
function table($name) {
    return DB_PREFIX . $name;
}
?>
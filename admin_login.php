<?php
// admin_login.php - Вход в админку
// Настройки сессии ДО session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_only_cookies', 1);
ini_set('session.gc_maxlifetime', 3600);

session_start();
require_once 'config.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin.php');
    exit;
}

$error = '';
$stmt = $pdo->query("SELECT login, password_hash FROM " . table('admin') . " LIMIT 1");
$admin_data = $stmt->fetch();

if (!$admin_data) {
    die("❌ Администратор не найден. Создайте через create_admin.php");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if ($login === $admin_data['login'] && password_verify($password, $admin_data['password_hash'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_login'] = $login;
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Неверный логин или пароль';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админку | Nod-Krai</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #0a1929;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .login-container {
            background: #1a2a4a;
            padding: 40px;
            border-radius: 20px;
            max-width: 400px;
            width: 100%;
            border: 1px solid rgba(64, 201, 255, 0.2);
            box-shadow: 0 8px 30px rgba(0,0,0,0.4);
        }
        .login-container h2 {
            color: #b87333;
            text-align: center;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .login-container .admin-icon {
            text-align: center;
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }
        .login-error {
            background: rgba(255, 107, 107, 0.15);
            color: #ff6b6b;
            padding: 0.75rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            text-align: center;
            border: 1px solid rgba(255, 107, 107, 0.2);
        }
        .login-container .form-group {
            margin-bottom: 15px;
        }
        .login-container .form-group label {
            color: #bae7ff;
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .login-container .form-group input {
            width: 100%;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid rgba(64, 201, 255, 0.2);
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        .login-container .form-group input:focus {
            border-color: #40c9ff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(64, 201, 255, 0.1);
        }
        .login-container .form-group input::placeholder {
            color: rgba(255,255,255,0.3);
        }
        .login-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(45deg, #8B6914, #b87333);
            color: #fff;
            border: none;
            border-radius: 40px;
            font-weight: bold;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(184, 115, 51, 0.3);
        }
        .login-container .hint {
            text-align: center;
            color: #666;
            font-size: 0.8rem;
            margin-top: 15px;
        }
        .login-container .back-link {
            text-align: center;
            margin-top: 10px;
        }
        .login-container .back-link a {
            color: #40c9ff;
            text-decoration: none;
            transition: all 0.3s;
        }
        .login-container .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="admin-icon">👑</div>
        <h2>Админ-панель</h2>
        
        <?php if ($error): ?>
            <div class="login-error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="login">Логин</label>
                <input type="text" id="login" name="login" value="admin" required placeholder="Введите логин">
            </div>
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" value="admin123" required placeholder="Введите пароль">
            </div>
            <button type="submit" class="login-btn">👑 Войти</button>
        </form>
        
        <div class="hint">Логин: admin | Пароль: admin123</div>
        <div class="back-link">
            <a href="index.php"><i class="fas fa-arrow-left"></i> На главную</a>
        </div>
    </div>
</body>
</html>
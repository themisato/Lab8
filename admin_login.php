<?php
// admin_login.php - Вход в админ-панель
require_once 'config.php';
session_start();

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin.php');
    exit;
}

$error = '';

$stmt = $pdo->query("SELECT login, password_hash FROM admin LIMIT 1");
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
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админку | Nod-Krai</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Полностью переопределяем стили для страницы входа в админку */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #0a1929;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .admin-login-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin-top: 60px;
        }
        
        .login-container {
            max-width: 420px;
            width: 100%;
            background: #1a2a4a;
            padding: 2.5rem;
            border-radius: 24px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.4);
            border: 1px solid rgba(64, 201, 255, 0.2);
        }
        
        .login-container .logo-icon {
            text-align: center;
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }
        
        .login-container h2 {
            color: #b87333;
            text-align: center;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }
        
        .login-container .subtitle {
            text-align: center;
            color: #bae7ff;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            opacity: 0.7;
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
            margin-bottom: 1.2rem;
        }
        
        .login-container .form-group label {
            display: block;
            font-weight: 600;
            color: #bae7ff;
            margin-bottom: 0.3rem;
            font-size: 0.95rem;
        }
        
        .login-container .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(255,255,255,0.08);
            border: 2px solid rgba(64, 201, 255, 0.2);
            border-radius: 12px;
            font-size: 1rem;
            color: #fff;
            transition: all 0.3s;
        }
        
        .login-container .form-group input:focus {
            border-color: #b87333;
            outline: none;
            box-shadow: 0 0 0 3px rgba(184, 115, 51, 0.2);
        }
        
        .login-container .form-group input::placeholder {
            color: rgba(255,255,255,0.3);
        }
        
        .login-btn {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(45deg, #8B6914, #b87333);
            color: white;
            border: none;
            border-radius: 40px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(184, 115, 51, 0.3);
        }
        
        .info-text {
            text-align: center;
            color: #666;
            font-size: 0.85rem;
            margin-top: 1rem;
            padding: 0.5rem;
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
        }
        
        .info-text strong {
            color: #b87333;
        }
        
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-link a {
            color: #40c9ff;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .back-link a:hover {
            color: #80d8ff;
            text-decoration: underline;
        }
        
        /* Скрываем header и footer на этой странице */
        .main-header,
        .main-footer {
            display: none !important;
        }
    </style>
</head>
<body>
    <div class="admin-login-wrapper">
        <div class="login-container">
            <div class="logo-icon">👑</div>
            <h2>Админ-панель</h2>
            <p class="subtitle">Вход для администраторов</p>
            
            <?php if ($error): ?>
                <div class="login-error">❌ <?php echo h($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <label for="login">Логин</label>
                <div class="form-group">
                    
                    <input type="text" id="login" name="login" required placeholder="Введите логин" value="admin">
                </div>
                <label for="password">Пароль</label>
                <div class="form-group">
                    
                    <input type="password" id="password" name="password" required placeholder="Введите пароль" value="admin123">
                </div>
                
                <button type="submit" class="login-btn"> Войти в админку</button>
            </form>
            
            <div class="info-text">
                Логин: <strong>admin</strong> | Пароль: <strong>admin123</strong>
            </div>
            
            <div class="back-link">
                <a href="index.html">← Вернуться на главную</a>
            </div>
        </div>
    </div>
</body>
</html>
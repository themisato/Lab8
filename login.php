<?php
// login.php - Страница входа
require_once 'config.php';
session_start();

// Если уже авторизован - перенаправляем на главную
if (isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($login) || empty($password)) {
        $error = 'Введите логин и пароль.';
    } else {
        $stmt = $pdo->prepare("SELECT id, full_name, password_hash FROM applications WHERE login = :login");
        $stmt->execute([':login' => $login]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            header('Location: index.html');
            exit;
        } else {
            $error = 'Неверный логин или пароль.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход | Nod-Krai</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-container {
            max-width: 420px;
            margin: 120px auto 60px;
            background: #1a2a4a;
            padding: 2.5rem;
            border-radius: 24px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.4);
            border: 1px solid rgba(64, 201, 255, 0.2);
        }
        .login-container h2 {
            color: #40c9ff;
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 2rem;
        }
        .login-container .logo-icon {
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
            border-color: #40c9ff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(64, 201, 255, 0.2);
        }
        .login-container .form-group input::placeholder {
            color: rgba(255,255,255,0.3);
        }
        .login-btn {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(45deg, #1a5fb4, #40c9ff);
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
            box-shadow: 0 6px 20px rgba(64, 201, 255, 0.3);
        }
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #bae7ff;
        }
        .register-link a {
            color: #40c9ff;
            text-decoration: none;
            font-weight: bold;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="container header-container">
            <a href="index.html" class="logo">Нод-край</a>
            <nav class="main-nav" id="mainNav">
                <ul>
                    <li><a href="index.html#home">Главная</a></li>
                    <li><a href="catalog.html">Персонажи</a></li>
                    <li><a href="list.php">📋 Анкеты</a></li>
                    <li><a href="admin.php">🔧 Админка</a></li>
                    <!-- ТОЛЬКО ОДНА КНОПКА - ВХОД -->
                    <li><a href="login.php" style="background:linear-gradient(45deg,#1a5fb4,#40c9ff);color:white;padding:6px 20px;border-radius:30px;font-weight:bold;font-size:0.95rem;transition:all 0.3s;">🔐 Войти</a></li>
                </ul>
            </nav>
            <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="login-container">
                <div class="logo-icon">🔐</div>
                <h2>Вход в систему</h2>
                
                <?php if ($error): ?>
                    <div class="login-error">❌ <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="login">Логин</label>
                        <input type="text" id="login" name="login" required placeholder="Введите логин">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <input type="password" id="password" name="password" required placeholder="Введите пароль">
                    </div>
                    
                    <button type="submit" class="login-btn">🔑 Войти</button>
                </form>
                
                <div class="register-link">
                    <p>Нет аккаунта? <a href="index.html">Заполните анкету</a></p>
                </div>
            </div>
        </div>
    </main>

    <footer class="main-footer">
        <div class="container">
            <p>Nod-Krai &copy; Фанатский проект по Genshin Impact</p>
            <p class="disclaimer">Все права принадлежат HoYoverse</p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
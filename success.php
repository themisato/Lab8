<?php
// success.php - Страница успешного сохранения
// Настройки сессии ДО session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_only_cookies', 1);
ini_set('session.gc_maxlifetime', 3600);

session_start();
require_once 'config.php';

$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';

$id = $_GET['id'] ?? '';
$login = $_GET['login'] ?? '';
$password = $_GET['password'] ?? '';

foreach (['full_name', 'phone', 'email', 'birth_date', 'gender', 'languages', 'biography', 'contract_accepted'] as $field) {
    setcookie("error_$field", "", time() - 3600, '/');
}

$application = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM " . table('applications') . " WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $application = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Успешно! | Nod-Krai</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .success-container {
            max-width: 700px;
            margin: 120px auto 60px;
            background: #1a2a4a;
            padding: 2.5rem;
            border-radius: 24px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.4);
            text-align: center;
            border: 1px solid rgba(64, 201, 255, 0.2);
        }
        .success-container h2 {
            color: #40c9ff;
            font-size: 2.2rem;
            margin-bottom: 1rem;
        }
        .success-icon {
            font-size: 4rem;
            color: #4caf50;
            margin-bottom: 1rem;
        }
        .success-box {
            background: rgba(76, 175, 80, 0.15);
            padding: 1.5rem;
            border-radius: 16px;
            margin: 1.5rem 0;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }
        .credentials-box {
            background: rgba(255, 152, 0, 0.15);
            border: 2px dashed #ff9800;
            padding: 1.5rem;
            border-radius: 16px;
            margin: 1.5rem 0;
        }
        .credentials-box .login-cred {
            font-size: 1.2rem;
            font-weight: bold;
            color: #ffb74d;
            margin: 0.5rem 0;
        }
        .credentials-box .login-cred strong {
            color: #ffcc80;
            background: rgba(0,0,0,0.3);
            padding: 0.2rem 0.8rem;
            border-radius: 8px;
            font-family: monospace;
        }
        .success-details {
            background: rgba(255,255,255,0.05);
            padding: 1rem;
            border-radius: 12px;
            margin-top: 1rem;
            text-align: left;
        }
        .success-details p {
            margin: 0.5rem 0;
            color: #bae7ff;
        }
        .success-details strong {
            color: #40c9ff;
            display: inline-block;
            width: 120px;
        }
        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 1.5rem;
        }
        .btn-group .btn {
            min-width: 180px;
        }
        .warning-text {
            color: #ff6b6b;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="container header-container">
            <a href="index.php" class="logo">Нод-край</a>
            <?php include 'nav.php'; ?>
            <button class="menu-toggle" id="menuToggle" aria-label="Меню">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="success-container">
                <div class="success-icon">🎉</div>
                <h2>Анкета успешно сохранена!</h2>
                
                <div class="success-box">
                    <p style="color: #81c784; font-size: 1.1rem;">
                        <i class="fas fa-check-circle"></i> Ваши данные приняты и сохранены в системе
                    </p>
                    <?php if ($id): ?>
                        <p style="color: #bae7ff; margin-top: 0.5rem;">
                            ID записи: <strong style="color: #40c9ff;">#<?php echo h($id); ?></strong>
                        </p>
                    <?php endif; ?>
                </div>

                <?php if ($login && $password): ?>
                <div class="credentials-box">
                    <h3 style="color: #ffb74d; margin-bottom: 0.5rem;">
                        <i class="fas fa-key"></i> Ваши данные для входа
                    </h3>
                    <p class="login-cred">🔑 Логин: <strong><?php echo h($login); ?></strong></p>
                    <p class="login-cred">🔒 Пароль: <strong><?php echo h($password); ?></strong></p>
                    <p class="warning-text">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Пароль отображается только один раз! Сохраните его.
                    </p>
                </div>
                <?php endif; ?>

                <?php if ($application): ?>
                <div class="success-details">
                    <p><strong>ФИО:</strong> <?php echo h($application['full_name']); ?></p>
                    <p><strong>Телефон:</strong> <?php echo h($application['phone']); ?></p>
                    <p><strong>Email:</strong> <?php echo h($application['email']); ?></p>
                    <p><strong>Дата рождения:</strong> <?php echo date('d.m.Y', strtotime($application['birth_date'])); ?></p>
                    <p><strong>Пол:</strong> <?php echo $application['gender'] == 'male' ? 'Мужской' : 'Женский'; ?></p>
                </div>
                <?php endif; ?>

                <div class="btn-group">
                    <a href="index.php" class="btn">📝 Новая анкета</a>
                    <a href="list.php" class="btn" style="background: linear-gradient(45deg, #1a5fb4, #26a0da);">📋 Все анкеты</a>
                    <a href="login.php" class="btn" style="background: linear-gradient(45deg, #b87333, #d4954a);">🔐 Войти</a>
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
<?php
// nav.php - Общая навигация для всех страниц
// Этот файл подключается в начале каждой страницы после session_start()

// Проверяем авторизацию пользователя
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
?>
<nav class="main-nav" id="mainNav">
    <ul>
        <li><a href="index.php#home">Главная</a></li>
        <li class="dropdown">
            <a href="index.php#characters-block">
                Персонажи <i class="fas fa-chevron-down"></i>
            </a>
            <ul class="dropdown-menu">
                <li><a href="index.php#characters-block">Все персонажи</a></li>
                <li><a href="catalog.php">Галерея персонажей</a></li>
            </ul>
        </li>
        <li><a href="index.php#about-game">О игре</a></li>
        <li><a href="index.php#about-region">О регионе</a></li>
        <li><a href="index.php#contact">Связаться</a></li>
        <li><a href="list.php">📋 Анкеты</a></li>
        <li><a href="admin.php">🔧 Админка</a></li>
        
        <!-- ЕДИНАЯ КНОПКА ВХОДА/ВЫХОДА -->
        <?php if ($is_logged_in): ?>
            <li style="display:flex;align-items:center;gap:8px;margin-left:15px;background:rgba(64,201,255,0.1);padding:4px 14px 4px 18px;border-radius:30px;border:1px solid rgba(64,201,255,0.15);">
                <span style="color:#40c9ff;font-weight:600;font-size:0.9rem;">
                    👤 <?php echo htmlspecialchars($user_name); ?>
                </span>
                <a href="logout.php" style="color:#ff6b6b;text-decoration:none;padding:3px 12px;border:1px solid rgba(255,107,107,0.3);border-radius:20px;font-size:0.75rem;transition:all 0.3s;">
                    Выйти
                </a>
            </li>
        <?php else: ?>
            <li>
                <a href="login.php" style="background:linear-gradient(45deg,#1a5fb4,#40c9ff);color:white;padding:6px 20px;border-radius:30px;font-weight:bold;font-size:0.95rem;transition:all 0.3s;">
                    🔐 Войти
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>
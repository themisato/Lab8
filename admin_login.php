<?php
// admin_login.php - Вход в админку
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
<html>
<head><title>Вход в админку</title></head>
<body style="background:#0a1929;display:flex;justify-content:center;align-items:center;min-height:100vh;font-family:Arial;">
    <div style="background:#1a2a4a;padding:40px;border-radius:20px;max-width:400px;width:100%;border:1px solid rgba(64,201,255,0.2);">
        <h2 style="color:#b87333;text-align:center;">👑 Админ-панель</h2>
        <?php if ($error): ?>
            <p style="color:#ff6b6b;text-align:center;">❌ <?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST">
            <div style="margin-bottom:15px;">
                <label style="color:#bae7ff;display:block;margin-bottom:5px;">Логин</label>
                <input type="text" name="login" value="admin" style="width:100%;padding:10px;border-radius:10px;border:1px solid rgba(64,201,255,0.2);background:rgba(255,255,255,0.05);color:#fff;">
            </div>
            <div style="margin-bottom:20px;">
                <label style="color:#bae7ff;display:block;margin-bottom:5px;">Пароль</label>
                <input type="password" name="password" value="admin123" style="width:100%;padding:10px;border-radius:10px;border:1px solid rgba(64,201,255,0.2);background:rgba(255,255,255,0.05);color:#fff;">
            </div>
            <button type="submit" style="width:100%;padding:12px;background:linear-gradient(45deg,#8B6914,#b87333);color:#fff;border:none;border-radius:40px;font-weight:bold;cursor:pointer;">👑 Войти</button>
        </form>
        <p style="text-align:center;color:#666;font-size:0.8rem;margin-top:15px;">Логин: admin | Пароль: admin123</p>
        <p style="text-align:center;margin-top:10px;"><a href="index.html" style="color:#40c9ff;">← На главную</a></p>
    </div>
</body>
</html>
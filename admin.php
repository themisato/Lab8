<?php
// admin.php - Админ-панель
require_once 'config.php';
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

$messages = [];

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $pdo->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM applications WHERE id = ?")->execute([$id]);
        $messages[] = '<div class="success-message">✅ Анкета #' . $id . ' успешно удалена</div>';
    } catch (PDOException $e) {
        error_log("Delete error: " . $e->getMessage());
        $messages[] = '<div class="error-message">❌ Ошибка удаления</div>';
    }
}

$edit_id = 0;
$edit_values = [];
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_values = $stmt->fetch();
    
    if ($edit_values) {
        $lang_stmt = $pdo->prepare("
            SELECT pl.name 
            FROM application_languages al 
            JOIN programming_languages pl ON al.language_id = pl.id 
            WHERE al.application_id = ?
        ");
        $lang_stmt->execute([$edit_id]);
        $edit_values['languages'] = $lang_stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = (int)$_POST['edit_id'];
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $biography = trim($_POST['biography'] ?? '');
    $contract_accepted = isset($_POST['contract_accepted']) ? 1 : 0;
    $languages = $_POST['languages'] ?? [];
    
    if (empty($full_name) || empty($email) || empty($phone) || empty($birth_date) || empty($gender)) {
        $messages[] = '<div class="error-message">❌ Заполните все обязательные поля</div>';
    } else {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("UPDATE applications SET 
                full_name = ?, phone = ?, email = ?, birth_date = ?, 
                gender = ?, biography = ?, contract_accepted = ?
                WHERE id = ?");
            $stmt->execute([$full_name, $phone, $email, $birth_date, $gender, $biography, $contract_accepted, $id]);
            
            $pdo->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$id]);
            
            $lang_map = [];
            $stmt = $pdo->query("SELECT id, name FROM programming_languages");
            while ($row = $stmt->fetch()) {
                $lang_map[$row['name']] = $row['id'];
            }
            $stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($languages as $lang_name) {
                if (isset($lang_map[$lang_name])) {
                    $stmt->execute([$id, $lang_map[$lang_name]]);
                }
            }
            
            $pdo->commit();
            $messages[] = '<div class="success-message">✅ Анкета #' . $id . ' успешно обновлена</div>';
            $edit_id = 0;
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Edit error: " . $e->getMessage());
            $messages[] = '<div class="error-message">❌ Ошибка обновления</div>';
        }
    }
}

$applications = [];
$stmt = $pdo->query("
    SELECT a.*, GROUP_CONCAT(pl.name SEPARATOR ', ') AS languages_list
    FROM applications a
    LEFT JOIN application_languages al ON a.id = al.application_id
    LEFT JOIN programming_languages pl ON al.language_id = pl.id
    GROUP BY a.id
    ORDER BY a.id DESC
");
$applications = $stmt->fetchAll();

$stats = [];
$stmt = $pdo->query("
    SELECT pl.name, COUNT(DISTINCT al.application_id) AS count
    FROM programming_languages pl
    LEFT JOIN application_languages al ON pl.id = al.language_id
    GROUP BY pl.id
    ORDER BY count DESC
");
$stats = $stmt->fetchAll();

$total_users = count($applications);
$all_languages = $pdo->query("SELECT name FROM programming_languages ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель | Nod-Krai</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 100px auto 40px;
            padding: 0 20px;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #1a2a4a;
            padding: 1rem 2rem;
            border-radius: 20px;
            border: 1px solid rgba(64, 201, 255, 0.2);
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .admin-header h1 {
            color: #40c9ff;
            font-size: 1.8rem;
        }
        .admin-header .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #bae7ff;
        }
        .admin-header .logout-link {
            color: #ff6b6b;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border: 1px solid rgba(255, 107, 107, 0.3);
            border-radius: 20px;
            transition: all 0.3s;
        }
        .admin-header .logout-link:hover {
            background: rgba(255, 107, 107, 0.1);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stats-card {
            background: #1a2a4a;
            padding: 1rem;
            border-radius: 16px;
            text-align: center;
            border: 1px solid rgba(64, 201, 255, 0.1);
        }
        .stats-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: #40c9ff;
        }
        .stats-card .label {
            color: #bae7ff;
            font-size: 0.85rem;
            margin-top: 0.3rem;
        }
        .table-wrapper {
            overflow-x: auto;
            background: #1a2a4a;
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid rgba(64, 201, 255, 0.1);
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }
        .admin-table th {
            background: rgba(64, 201, 255, 0.1);
            color: #40c9ff;
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 2px solid rgba(64, 201, 255, 0.2);
        }
        .admin-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            color: #bae7ff;
            vertical-align: middle;
        }
        .admin-table tr:hover td {
            background: rgba(64, 201, 255, 0.05);
        }
        .badge {
            display: inline-block;
            background: rgba(64, 201, 255, 0.15);
            color: #40c9ff;
            padding: 0.15rem 0.6rem;
            border-radius: 12px;
            font-size: 0.7rem;
            margin: 0.1rem;
        }
        .gender-male { color: #4fc3f7; font-weight: bold; }
        .gender-female { color: #f06292; font-weight: bold; }
        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: nowrap;
        }
        .btn-edit, .btn-delete {
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            text-decoration: none;
            font-size: 0.75rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-edit {
            background: rgba(76, 175, 80, 0.2);
            color: #81c784;
        }
        .btn-edit:hover {
            background: rgba(76, 175, 80, 0.3);
        }
        .btn-delete {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
        }
        .btn-delete:hover {
            background: rgba(255, 107, 107, 0.3);
        }
        .success-message {
            background: rgba(76, 175, 80, 0.15);
            color: #81c784;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            margin: 0.5rem 0;
            border: 1px solid rgba(76, 175, 80, 0.2);
        }
        .error-message {
            background: rgba(255, 107, 107, 0.15);
            color: #ff6b6b;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            margin: 0.5rem 0;
            border: 1px solid rgba(255, 107, 107, 0.2);
        }
        .btn-back {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(45deg, #1a5fb4, #26a0da);
            color: white;
            text-decoration: none;
            border-radius: 40px;
            transition: all 0.3s;
        }
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(64, 201, 255, 0.3);
        }
        .btn-stats {
            background: linear-gradient(45deg, #b87333, #d4954a) !important;
        }
        .edit-form {
            background: #1a2a4a;
            padding: 1.5rem;
            border-radius: 16px;
            border: 1px solid rgba(64, 201, 255, 0.2);
            margin-bottom: 2rem;
        }
        .edit-form .form-group {
            margin-bottom: 1rem;
        }
        .edit-form .form-group label {
            display: block;
            color: #bae7ff;
            font-weight: 600;
            margin-bottom: 0.3rem;
        }
        .edit-form .form-group input,
        .edit-form .form-group select,
        .edit-form .form-group textarea {
            width: 100%;
            padding: 0.6rem 0.8rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(64, 201, 255, 0.2);
            border-radius: 8px;
            color: #fff;
            font-size: 0.95rem;
        }
        .edit-form .form-group input:focus,
        .edit-form .form-group select:focus,
        .edit-form .form-group textarea:focus {
            border-color: #40c9ff;
            outline: none;
        }
        .edit-form .form-group select[multiple] {
            min-height: 100px;
        }
        .edit-form .radio-group {
            display: flex;
            gap: 1.5rem;
            padding: 0.5rem 0;
        }
        .edit-form .radio-group label {
            font-weight: normal;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            cursor: pointer;
        }
        .edit-form .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0;
        }
        .edit-form .checkbox-group label {
            font-weight: normal;
            margin-bottom: 0;
        }
        .edit-form .btn-save {
            padding: 0.75rem 2rem;
            background: linear-gradient(45deg, #4caf50, #388e3c);
            color: white;
            border: none;
            border-radius: 40px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        .edit-form .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.3);
        }
        .edit-form .btn-cancel {
            display: inline-block;
            margin-left: 1rem;
            padding: 0.75rem 1.5rem;
            background: rgba(255,255,255,0.1);
            color: #bae7ff;
            text-decoration: none;
            border-radius: 40px;
            transition: all 0.3s;
        }
        .edit-form .btn-cancel:hover {
            background: rgba(255,255,255,0.2);
        }
        .required { color: #ff6b6b; }
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
                    <li><a href="list.php">Анкеты</a></li>
                    <li><a href="admin.php" class="active">Админка</a></li>
                </ul>
            </nav>
            <button class="menu-toggle" id="menuToggle" aria-label="Меню">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <main>
        <div class="container admin-container">
            <div class="admin-header">
                <h1>🔧 Админ-панель</h1>
                <div class="user-info">
                    <span>👤 <?php echo h($_SESSION['admin_login'] ?? 'admin'); ?></span>
                    <a href="admin_logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Выйти</a>
                </div>
            </div>

            <?php if (!empty($messages)): ?>
                <?php foreach ($messages as $msg): ?>
                    <?php echo $msg; ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stats-card">
                    <div class="number"><?php echo $total_users; ?></div>
                    <div class="label">👤 Всего пользователей</div>
                </div>
                <?php foreach (array_slice($stats, 0, 4) as $s): ?>
                <div class="stats-card">
                    <div class="number"><?php echo $s['count']; ?></div>
                    <div class="label">🌐 <?php echo h($s['name']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($edit_id > 0 && $edit_values): ?>
            <div class="edit-form">
                <h2 style="color: #40c9ff; margin-bottom: 1rem;">✏️ Редактирование анкеты #<?php echo $edit_id; ?></h2>
                <form method="POST" action="">
                    <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
                    
                    <div class="form-group">
                        <label>ФИО <span class="required">*</span></label>
                        <input type="text" name="full_name" value="<?php echo h($edit_values['full_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Телефон <span class="required">*</span></label>
                        <input type="tel" name="phone" value="<?php echo h($edit_values['phone']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" name="email" value="<?php echo h($edit_values['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Дата рождения <span class="required">*</span></label>
                        <input type="date" name="birth_date" value="<?php echo h($edit_values['birth_date']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Пол <span class="required">*</span></label>
                        <div class="radio-group">
                            <label><input type="radio" name="gender" value="male" <?php echo $edit_values['gender'] == 'male' ? 'checked' : ''; ?>> Мужской</label>
                            <label><input type="radio" name="gender" value="female" <?php echo $edit_values['gender'] == 'female' ? 'checked' : ''; ?>> Женский</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Любимые языки <span class="required">*</span></label>
                        <select name="languages[]" multiple>
                            <?php foreach ($all_languages as $lang): ?>
                                <option value="<?php echo h($lang); ?>" <?php echo in_array($lang, $edit_values['languages'] ?? []) ? 'selected' : ''; ?>>
                                    <?php echo h($lang); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Биография</label>
                        <textarea name="biography" rows="3"><?php echo h($edit_values['biography'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="contract_accepted" value="1" <?php echo $edit_values['contract_accepted'] ? 'checked' : ''; ?>>
                            <label>Согласен с контрактом <span class="required">*</span></label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Сохранить</button>
                    <a href="admin.php" class="btn-cancel">Отмена</a>
                </form>
            </div>
            <?php endif; ?>

            <div class="table-wrapper">
                <h2 style="color: #40c9ff; margin-bottom: 1rem;">📋 Все анкеты</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ФИО</th>
                            <th>Телефон</th>
                            <th>Email</th>
                            <th>Дата</th>
                            <th>Пол</th>
                            <th>Языки</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($applications)): ?>
                            <tr><td colspan="8" style="text-align:center; color:#666; padding:2rem;">Нет анкет</td></tr>
                        <?php else: ?>
                            <?php foreach ($applications as $app): ?>
                            <tr>
                                <td><?php echo $app['id']; ?></td>
                                <td><?php echo h($app['full_name']); ?></td>
                                <td><?php echo h($app['phone']); ?></td>
                                <td><?php echo h($app['email']); ?></td>
                                <td><?php echo date('d.m.Y', strtotime($app['birth_date'])); ?></td>
                                <td class="<?php echo $app['gender'] == 'male' ? 'gender-male' : 'gender-female'; ?>">
                                    <?php echo $app['gender'] == 'male' ? '♂ Мужской' : '♀ Женский'; ?>
                                </td>
                                <td>
                                    <?php 
                                    $langs = explode(', ', $app['languages_list'] ?? '');
                                    foreach ($langs as $lang):
                                        if (trim($lang)):
                                    ?>
                                        <span class="badge"><?php echo h(trim($lang)); ?></span>
                                    <?php endif; endforeach; ?>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="admin.php?edit=<?php echo $app['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                        <a href="admin.php?delete=<?php echo $app['id']; ?>" class="btn-delete" onclick="return confirm('Удалить анкету #<?php echo $app['id']; ?>?')"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="text-align:center; margin-top: 2rem;">
                <a href="admin_stats.php" class="btn-back btn-stats"><i class="fas fa-chart-bar"></i> Подробная статистика</a>
                <a href="index.html" class="btn-back"><i class="fas fa-arrow-left"></i> На главную</a>
                <a href="list.php" class="btn-back"><i class="fas fa-list"></i> Список анкет</a>
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
<?php
// list.php - Список всех анкет
// Настройки сессии ДО session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_only_cookies', 1);
ini_set('session.gc_maxlifetime', 3600);

session_start();
require_once 'config.php';

$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';

$sql = "SELECT a.*, 
        GROUP_CONCAT(pl.name ORDER BY pl.name SEPARATOR ', ') as languages
        FROM " . table('applications') . " a
        LEFT JOIN " . table('application_languages') . " al ON a.id = al.application_id
        LEFT JOIN " . table('programming_languages') . " pl ON al.language_id = pl.id
        GROUP BY a.id
        ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$applications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список анкет | Nod-Krai</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .list-container {
            max-width: 1200px;
            margin: 100px auto 40px;
            padding: 0 20px;
        }
        .list-container h1 {
            color: #40c9ff;
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .list-container .subtitle {
            text-align: center;
            color: #bae7ff;
            margin-bottom: 2rem;
        }
        .stats-badge {
            display: inline-block;
            background: rgba(64, 201, 255, 0.15);
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            color: #40c9ff;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(64, 201, 255, 0.2);
        }
        .table-wrapper {
            overflow-x: auto;
            background: #1a2a4a;
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid rgba(64, 201, 255, 0.1);
        }
        .applications-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }
        .applications-table th {
            background: rgba(64, 201, 255, 0.1);
            color: #40c9ff;
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 2px solid rgba(64, 201, 255, 0.2);
        }
        .applications-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            color: #bae7ff;
            vertical-align: middle;
        }
        .applications-table tr:hover td {
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
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #bae7ff;
        }
        .empty-state .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #40c9ff;
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
        .btn-admin {
            background: linear-gradient(45deg, #b87333, #d4954a) !important;
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
        <div class="container list-container">
            <h1>📋 Список анкет</h1>
            <p class="subtitle">Все сохранённые анкеты пользователей</p>
            
            <div style="text-align:center; margin-bottom:1.5rem;">
                <span class="stats-badge">
                    <i class="fas fa-users"></i> Всего анкет: <strong><?php echo count($applications); ?></strong>
                </span>
            </div>

            <?php if (empty($applications)): ?>
                <div class="empty-state">
                    <div class="icon">📭</div>
                    <p>Пока нет ни одной сохранённой анкеты.</p>
                    <a href="index.php" class="btn-back">📝 Заполнить анкету</a>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="applications-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ФИО</th>
                                <th>Телефон</th>
                                <th>Email</th>
                                <th>Дата рождения</th>
                                <th>Пол</th>
                                <th>Языки</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td><?php echo h($app['id']); ?></td>
                                    <td><?php echo h($app['full_name']); ?></td>
                                    <td><?php echo h($app['phone']); ?></td>
                                    <td><?php echo h($app['email']); ?></td>
                                    <td><?php echo date('d.m.Y', strtotime($app['birth_date'])); ?></td>
                                    <td class="<?php echo $app['gender'] == 'male' ? 'gender-male' : 'gender-female'; ?>">
                                        <?php echo $app['gender'] == 'male' ? '♂ Мужской' : '♀ Женский'; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $langs = explode(', ', $app['languages'] ?? '');
                                        foreach ($langs as $lang):
                                            if (trim($lang)):
                                        ?>
                                            <span class="badge"><?php echo h(trim($lang)); ?></span>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        if (empty($app['languages'])):
                                            echo '<span style="color:#666;">— не выбрано —</span>';
                                        endif;
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div style="text-align:center; margin-top: 2rem;">
                <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> На главную</a>
                <a href="admin.php" class="btn-back btn-admin"><i class="fas fa-crown"></i> Админ-панель</a>
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
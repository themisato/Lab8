<?php
// admin_stats.php - Статистика для администратора
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

$stats = [];
$stmt = $pdo->query("
    SELECT pl.name, 
           COUNT(DISTINCT al.application_id) AS count,
           ROUND(COUNT(DISTINCT al.application_id) * 100.0 / (SELECT COUNT(*) FROM applications), 2) AS percentage
    FROM programming_languages pl
    LEFT JOIN application_languages al ON pl.id = al.language_id
    GROUP BY pl.id
    ORDER BY count DESC
");
$stats = $stmt->fetchAll();

$total_users = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();

$gender_stats = $pdo->query("SELECT gender, COUNT(*) FROM applications GROUP BY gender")->fetchAll();
$male_count = 0;
$female_count = 0;
foreach ($gender_stats as $g) {
    if ($g['gender'] == 'male') $male_count = $g['count'];
    if ($g['gender'] == 'female') $female_count = $g['count'];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статистика | Nod-Krai</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stats-container {
            max-width: 900px;
            margin: 100px auto 40px;
            padding: 0 20px;
        }
        .stats-container h1 {
            color: #40c9ff;
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .stats-container .subtitle {
            text-align: center;
            color: #bae7ff;
            margin-bottom: 2rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: #1a2a4a;
            padding: 1.5rem;
            border-radius: 16px;
            text-align: center;
            border: 1px solid rgba(64, 201, 255, 0.1);
        }
        .stat-card .number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #40c9ff;
        }
        .stat-card .label {
            color: #bae7ff;
            margin-top: 0.3rem;
        }
        .chart-container {
            background: #1a2a4a;
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid rgba(64, 201, 255, 0.1);
            margin-bottom: 2rem;
        }
        .chart-container h2 {
            color: #40c9ff;
            margin-bottom: 1.5rem;
        }
        .lang-bar {
            display: flex;
            align-items: center;
            margin-bottom: 0.8rem;
        }
        .lang-bar .lang-name {
            width: 120px;
            font-weight: 600;
            color: #bae7ff;
            flex-shrink: 0;
        }
        .lang-bar .bar-track {
            flex: 1;
            height: 30px;
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }
        .lang-bar .bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #1a5fb4, #40c9ff);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 10px;
            color: white;
            font-size: 0.8rem;
            font-weight: bold;
            transition: width 1s ease;
        }
        .lang-bar .bar-count {
            width: 60px;
            text-align: right;
            font-weight: bold;
            color: #40c9ff;
            flex-shrink: 0;
            margin-left: 10px;
        }
        .gender-chart {
            display: flex;
            gap: 2rem;
            justify-content: center;
            margin-top: 1rem;
            flex-wrap: wrap;
        }
        .gender-item {
            text-align: center;
        }
        .gender-item .circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            color: white;
            margin: 0 auto 0.5rem;
        }
        .gender-item .circle.male {
            background: linear-gradient(135deg, #1a5fb4, #4fc3f7);
        }
        .gender-item .circle.female {
            background: linear-gradient(135deg, #b87333, #f06292);
        }
        .gender-item .gender-label {
            color: #bae7ff;
            font-weight: 600;
        }
        .gender-item .percentage {
            color: #666;
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
                    <li><a href="admin.php">Админка</a></li>
                </ul>
            </nav>
            <button class="menu-toggle" id="menuToggle" aria-label="Меню">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <main>
        <div class="container stats-container">
            <h1>📊 Статистика</h1>
            <p class="subtitle">Аналитика по анкетам и языкам программирования</p>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="number"><?php echo $total_users; ?></div>
                    <div class="label">👤 Всего пользователей</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $male_count; ?></div>
                    <div class="label">♂ Мужчины</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $female_count; ?></div>
                    <div class="label">♀ Женщины</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo count($stats); ?></div>
                    <div class="label">🌐 Языков</div>
                </div>
            </div>

            <div class="chart-container">
                <h2>👫 Статистика по полу</h2>
                <div class="gender-chart">
                    <div class="gender-item">
                        <div class="circle male"><?php echo $male_count; ?></div>
                        <div class="gender-label">♂ Мужчины</div>
                        <div class="percentage"><?php echo $total_users > 0 ? round($male_count * 100 / $total_users, 1) : 0; ?>%</div>
                    </div>
                    <div class="gender-item">
                        <div class="circle female"><?php echo $female_count; ?></div>
                        <div class="gender-label">♀ Женщины</div>
                        <div class="percentage"><?php echo $total_users > 0 ? round($female_count * 100 / $total_users, 1) : 0; ?>%</div>
                    </div>
                </div>
            </div>

            <div class="chart-container">
                <h2>🌐 Популярность языков программирования</h2>
                <?php 
                $max_count = !empty($stats) ? max(array_column($stats, 'count')) : 1;
                if ($max_count == 0) $max_count = 1;
                foreach ($stats as $s): 
                ?>
                    <div class="lang-bar">
                        <div class="lang-name"><?php echo h($s['name']); ?></div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?php echo ($s['count'] / $max_count) * 100; ?>%;">
                                <?php echo $s['count']; ?>
                            </div>
                        </div>
                        <div class="bar-count"><?php echo $s['count']; ?></div>
                    </div>
                <?php endforeach; ?>
                <div style="margin-top:1rem; color:#666; text-align:center;">* Всего пользователей: <?php echo $total_users; ?></div>
            </div>

            <div style="text-align:center;">
                <a href="admin.php" class="btn-back"><i class="fas fa-arrow-left"></i> Назад в админку</a>
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
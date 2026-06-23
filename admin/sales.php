<?php
require '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Получаем продажи по категориям и месяцам
$salesData = $pdo->query("
    SELECT 
        c.name as category_name,
        DATE_FORMAT(o.created_at, '%Y-%m') as month,
        SUM(oi.quantity) as total_quantity,
        SUM(oi.quantity * oi.price_at_purchase) as total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status != 'new'
    GROUP BY c.name, DATE_FORMAT(o.created_at, '%Y-%m')
    ORDER BY month DESC, c.name
")->fetchAll();

$userName = $_SESSION['login'] ?? 'Администратор';

// Группируем данные для графика
$chartData = [];
foreach ($salesData as $row) {
    $chartData[$row['month']][$row['category_name']] = (float)$row['total_revenue'];
}
$months = array_unique(array_column($salesData, 'month'));
$categories = array_unique(array_column($salesData, 'category_name'));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Продажи — TEAGReen</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .admin-wrapper { display: flex; min-height: 100vh; background: #f9f6f0; }
        .admin-sidebar {
            width: 260px; background: white; box-shadow: 2px 0 15px rgba(0,0,0,0.06);
            padding: 30px 20px; position: fixed; top: 0; left: 0; bottom: 0;
            overflow-y: auto; z-index: 100;
        }
        .admin-sidebar .logo {
            font-size: 28px; font-weight: 700; color: #2d5a27;
            text-decoration: none; display: block; margin-bottom: 35px;
        }
        .admin-sidebar .logo span { color: var(--accent); }
        .admin-sidebar .user-info {
            padding: 15px 16px; background: #f5f0e8;
            border-radius: 12px; margin-bottom: 25px;
        }
        .admin-sidebar .user-info .name { font-weight: 600; color: var(--accent); }
        .admin-sidebar .user-info .role { font-size: 13px; color: #888; }
        .admin-sidebar .menu { list-style: none; padding: 0; }
        .admin-sidebar .menu li { margin-bottom: 3px; }
        .admin-sidebar .menu a {
            display: flex; align-items: center; gap: 12px; padding: 12px 16px;
            border-radius: 12px; text-decoration: none; color: #555;
            transition: 0.3s; font-weight: 500;
        }
        .admin-sidebar .menu a:hover { background: #f5f0e8; color: var(--accent); }
        .admin-sidebar .menu a.active { background: var(--accent); color: white; }
        .admin-sidebar .menu a .icon { font-size: 20px; width: 28px; }
        .admin-sidebar .logout-link {
            margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;
        }
        .admin-sidebar .logout-link a { color: #dc2626 !important; }
        .admin-sidebar .logout-link a:hover { background: #fee2e2 !important; }
        .admin-main {
            margin-left: 260px; padding: 30px 40px;
            width: calc(100% - 260px); box-sizing: border-box;
            overflow-x: hidden;
        }
        .page-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 30px; flex-wrap: wrap; gap: 15px;
        }
        .page-header h1 { font-size: 28px; color: var(--accent); margin: 0; }
        .chart-container {
            background: white; padding: 30px; border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04); margin-bottom: 30px;
        }
        .chart-wrapper {
            position: relative;
            height: 400px;
            width: 100%;
        }
        @media (max-width: 768px) {
            .admin-sidebar { width: 200px; }
            .admin-main { margin-left: 200px; padding: 20px; }
        }
        @media (max-width: 480px) {
            .admin-sidebar { width: 60px; }
            .admin-sidebar .logo, .admin-sidebar .user-info, .admin-sidebar .menu a span { display: none; }
            .admin-main { margin-left: 60px; padding: 15px; }
            .chart-wrapper { height: 300px; }
        }
    </style>
</head>
<body>
    <aside class="admin-sidebar">
        <a href="../index.php" class="logo">TEAG<span>Reen</span></a>
        <div class="user-info">
            <div class="name"><?= htmlspecialchars($userName) ?></div>
            <div class="role">Администратор</div>
        </div>
        <ul class="menu">
            <li><a href="index.php"><span class="icon">📊</span><span>Главная</span></a></li>
            <li><a href="products.php"><span class="icon">📦</span><span>Товары</span></a></li>
            <li><a href="categories.php"><span class="icon">📂</span><span>Категории</span></a></li>
            <li><a href="orders.php"><span class="icon">📋</span><span>Заказы</span></a></li>
            <li><a href="pickup_points.php"><span class="icon">📍</span><span>Пункты выдачи</span></a></li>
            <li><a href="sales.php" class="active"><span class="icon">📈</span><span>Продажи</span></a></li>
            <li><a href="add_product.php"><span class="icon">➕</span><span>Добавить товар</span></a></li>
        </ul>
        <div class="logout-link">
            <a href="../logout.php"><span class="icon">🚪</span><span>Выйти</span></a>
        </div>
    </aside>

    <main class="admin-main">
        <div class="page-header">
            <h1>📈 Продажи по категориям</h1>
        </div>

        <div class="chart-container">
            <h2 style="margin-bottom: 20px; color: var(--accent);">Выручка по месяцам (₽)</h2>
            <div class="chart-wrapper">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <?php if (empty($salesData)): ?>
            <div style="background: white; padding: 40px; border-radius: 16px; text-align: center;">
                <p style="font-size: 18px; color: #888;">Нет данных о продажах</p>
            </div>
        <?php endif; ?>
    </main>

    <script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    const data = {
        labels: <?= json_encode(array_map(function($m) {
            $parts = explode('-', $m);
            return $parts[1] . '.' . $parts[0];
        }, array_values($months))) ?>,
        datasets: <?= json_encode(array_values(array_map(function($cat) use ($chartData, $months) {
            return [
                'label' => $cat,
                'data' => array_map(function($month) use ($chartData, $cat) {
                    return $chartData[$month][$cat] ?? 0;
                }, $months),
                'backgroundColor' => 'rgba(' . (rand(100,200)) . ',' . (rand(100,200)) . ',' . (rand(100,200)) . ', 0.7)',
                'borderColor' => 'rgba(' . (rand(50,150)) . ',' . (rand(50,150)) . ',' . (rand(50,150)) . ', 1)',
                'borderWidth' => 2
            ];
        }, $categories))) ?>
    };

    new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('ru-RU') + ' ₽';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toLocaleString('ru-RU') + ' ₽';
                        }
                    }
                }
            }
        }
    });
    </script>
</body>
</html>
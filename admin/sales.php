<?php
require '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: ../login.php"); exit; }

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

$chartData = [];
foreach ($salesData as $row) {
    $chartData[$row['month']][$row['category_name']] = (float)$row['total_revenue'];
}
$months = array_values(array_unique(array_column($salesData, 'month')));
$categories = array_values(array_unique(array_column($salesData, 'category_name')));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Продажи — TEAGReen</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f9f6f0; overflow-x: hidden; }

/* === БУРГЕР И КНОПКИ === */
.admin-burger {
    display: none;
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1001;
    background: #58355F;
    color: white;
    border: none;
    padding: 12px 16px;
    border-radius: 10px;
    cursor: pointer;
    font-size: 24px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.mobile-home-btn {
    display: none;
    position: fixed;
    top: 15px;
    right: 15px;
    z-index: 1001;
    background: white;
    color: #58355F;
    border: 2px solid #58355F;
    padding: 10px 18px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.admin-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 999;
}

/* === САЙДБАР === */
.admin-sidebar {
    width: 280px;
    background: white;
    box-shadow: 2px 0 20px rgba(0,0,0,0.1);
    padding: 30px 20px;
    position: fixed;
    top: 0; left: 0; bottom: 0;
    overflow-y: auto;
    z-index: 1000;
    transition: transform 0.3s ease;
}
.admin-sidebar .logo {
    font-size: 28px;
    font-weight: 700;
    color: #2d5a27;
    text-decoration: none;
    display: block;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f5f0e8;
}
.admin-sidebar .logo span { color: #58355F; }
.admin-sidebar .user-info {
    padding: 15px;
    background: #f5f0e8;
    border-radius: 12px;
    margin-bottom: 25px;
}
.admin-sidebar .user-info .name { font-weight: 600; color: #58355F; margin-bottom: 4px; }
.admin-sidebar .user-info .role { font-size: 13px; color: #888; }
.admin-sidebar .menu { list-style: none; padding: 0; margin: 0; }
.admin-sidebar .menu li { margin-bottom: 5px; }
.admin-sidebar .menu a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 12px;
    text-decoration: none;
    color: #555;
    transition: all 0.3s;
    font-weight: 500;
    font-size: 15px;
}
.admin-sidebar .menu a:hover { background: #f5f0e8; color: #58355F; }
.admin-sidebar .menu a.active { background: #58355F; color: white; }
.admin-sidebar .menu a .icon { font-size: 20px; width: 28px; text-align: center; }
.admin-sidebar .logout-link {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 2px solid #f5f0e8;
}
.admin-sidebar .logout-link a { color: #dc2626 !important; }

/* === КОНТЕНТ === */
.admin-main {
    margin-left: 280px;
    padding: 30px 40px;
    min-height: 100vh;
    transition: margin-left 0.3s ease;
}
.page-header { margin-bottom: 30px; }
.page-header h1 { font-size: 32px; color: #58355F; }
.chart-container {
    background: white;
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    margin-bottom: 30px;
}
.chart-container h2 {
    margin-bottom: 20px;
    color: #58355F;
    font-size: 20px;
}
.chart-wrapper {
    position: relative;
    height: 400px;
    width: 100%;
}
.btn {
    display: inline-block;
    padding: 12px 30px;
    background: #58355F;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    transition: 0.3s;
    cursor: pointer;
}
.btn:hover { background: #472a4a; }
.btn-outline {
    background: transparent;
    color: #58355F;
    border: 2px solid #58355F;
}
.btn-outline:hover { background: #58355F; color: white; }

/* === АДАПТИВНОСТЬ === */
@media (max-width: 768px) {
    .admin-burger, .mobile-home-btn { display: block; }
    .admin-sidebar { transform: translateX(-100%); }
    .admin-sidebar.active { transform: translateX(0); }
    .admin-main { margin-left: 0; padding: 80px 20px 30px; }
    .page-header h1 { font-size: 24px; }
    .chart-wrapper { height: 300px; }
}
@media (max-width: 480px) {
    .admin-sidebar { width: 260px; }
    .admin-main { padding: 80px 15px 20px; }
    .chart-container { padding: 20px; }
    .chart-wrapper { height: 250px; }
}
</style>
</head>
<body>
<button class="admin-burger" id="adminBurger">☰</button>
<a href="../index.php" class="mobile-home-btn">🏠 На главную</a>
<div class="admin-overlay" id="adminOverlay"></div>

<aside class="admin-sidebar" id="adminSidebar">
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
        <h2>Выручка по месяцам (₽)</h2>
        <div class="chart-wrapper">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <?php if (empty($salesData)): ?>
        <div style="background: white; padding: 40px; border-radius: 16px; text-align: center;">
            <p style="font-size: 18px; color: #888;">📊 Нет данных о продажах</p>
        </div>
    <?php endif; ?>

    <div style="margin-top: 30px;">
        <a href="index.php" class="btn btn-outline">← Вернуться на главную</a>
    </div>
</main>

<!-- СНАЧАЛА БУРГЕР (отдельно от Chart.js, чтобы не блокировался) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var burger = document.getElementById('adminBurger');
    var sidebar = document.getElementById('adminSidebar');
    var overlay = document.getElementById('adminOverlay');
    
    if (burger && sidebar && overlay) {
        burger.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
        sidebar.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});
</script>

<!-- Chart.js загружается ОТДЕЛЬНО -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- ГРАФИК (в отдельном скрипте, чтобы ошибка не ломала бургер) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    try {
        var canvas = document.getElementById('salesChart');
        if (!canvas) return;
        
        var ctx = canvas.getContext('2d');
        var labels = <?= json_encode(array_map(function($m) { 
            $parts = explode('-', $m); 
            return $parts[1] . '.' . $parts[0]; 
        }, $months)) ?>;
        
        var datasets = <?= json_encode(array_values(array_map(function($cat) use ($chartData, $months) {
            return [
                'label' => $cat,
                'data' => array_map(function($month) use ($chartData, $cat) { 
                    return $chartData[$month][$cat] ?? 0; 
                }, $months),
                'backgroundColor' => 'rgba(' . rand(100,200) . ',' . rand(100,200) . ',' . rand(100,200) . ', 0.7)',
                'borderColor' => 'rgba(' . rand(50,150) . ',' . rand(50,150) . ',' . rand(50,150) . ', 1)',
                'borderWidth' => 2
            ];
        }, $categories))) ?>;
        
        if (typeof Chart !== 'undefined' && labels.length > 0) {
            new Chart(ctx, {
                type: 'bar',
                data: { labels: labels, datasets: datasets },
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
        }
    } catch(e) {
        console.error('Ошибка графика:', e);
    }
});
</script>
</body>
</html>
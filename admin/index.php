<?php
require '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Статистика
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$newOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'new'")->fetchColumn();

// Последние 5 заказов
$recentOrders = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 5")->fetchAll();

// Последние 5 товаров
$recentProducts = $pdo->query("SELECT * FROM products ORDER BY id DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель — TEAGReen</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-body { background: #f9f6f0; min-height: 100vh; display: flex; }
        .admin-sidebar { width: 250px; background: white; box-shadow: 2px 0 10px rgba(0,0,0,0.05); padding: 30px 20px; position: fixed; top: 0; left: 0; bottom: 0; overflow-y: auto; z-index: 100; }
        .admin-sidebar .logo { font-size: 24px; font-weight: bold; color: #2d5a27; text-decoration: none; display: block; margin-bottom: 30px; }
        .admin-sidebar .logo span { color: var(--accent); }
        .admin-sidebar .menu { list-style: none; padding: 0; }
        .admin-sidebar .menu li { margin-bottom: 5px; }
        .admin-sidebar .menu a { display: block; padding: 12px 16px; border-radius: 12px; text-decoration: none; color: #333; transition: 0.3s; font-weight: 500; }
        .admin-sidebar .menu a:hover { background: #f5f0e8; color: var(--accent); }
        .admin-sidebar .menu a.active { background: var(--accent); color: white; }
        .admin-sidebar .menu a .icon { margin-right: 12px; }
        .admin-sidebar .logout-link { margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
        .admin-sidebar .logout-link a { color: #dc2626 !important; }
        .admin-sidebar .logout-link a:hover { background: #fee2e2 !important; }
        .admin-main { margin-left: 250px; padding: 30px; width: 100%; }
        .admin-main h1 { font-size: 32px; color: var(--accent); margin-bottom: 10px; }
        .admin-main .welcome { color: #666; margin-bottom: 30px; }
        .admin-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 25px; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .stat-card .number { font-size: 32px; font-weight: 700; color: var(--accent); }
        .stat-card .label { color: #666; font-size: 14px; margin-top: 5px; }
        .stat-card .badge-new { display: inline-block; background: #e74c3c; color: white; padding: 2px 12px; border-radius: 12px; font-size: 12px; margin-top: 5px; }
        .admin-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; margin-bottom: 40px; }
        .admin-card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .admin-card h3 { font-size: 18px; color: var(--accent); margin-bottom: 15px; }
        .admin-card .item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f5f0e8; }
        .admin-card .item:last-child { border-bottom: none; }
        .admin-card .item .name { color: #333; }
        .admin-card .item .value { color: #666; font-size: 14px; }
        .admin-card .btn-admin { display: inline-block; padding: 10px 25px; background: var(--accent); color: white; text-decoration: none; border-radius: 12px; transition: 0.3s; font-size: 14px; border: none; cursor: pointer; margin-top: 10px; }
        .admin-card .btn-admin:hover { background: #472a4a; }
        .admin-card .btn-admin-outline { background: transparent; color: var(--accent); border: 2px solid var(--accent); }
        .admin-card .btn-admin-outline:hover { background: var(--accent); color: white; }
        .admin-card .status-badge { display: inline-block; padding: 3px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .status-new { background: #fee2e2; color: #dc2626; }
        .status-processing { background: #fef3c7; color: #d97706; }
        .status-ready { background: #dbeafe; color: #2563eb; }
        .status-completed { background: #dcfce7; color: #16a34a; }
        @media (max-width: 992px) {
            .admin-stats { grid-template-columns: repeat(2, 1fr); }
            .admin-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .admin-sidebar { width: 200px; padding: 20px 15px; }
            .admin-main { margin-left: 200px; padding: 20px; }
            .admin-stats { grid-template-columns: 1fr 1fr; }
            .admin-sidebar .logo { font-size: 20px; }
            .admin-sidebar .menu a { font-size: 14px; padding: 10px 12px; }
        }
        @media (max-width: 480px) {
            .admin-sidebar { width: 60px; padding: 15px 10px; }
            .admin-sidebar .logo { font-size: 0; }
            .admin-sidebar .logo::after { content: "TE"; font-size: 20px; font-weight: bold; color: #2d5a27; }
            .admin-sidebar .menu a span { display: none; }
            .admin-sidebar .menu a .icon { margin-right: 0; font-size: 20px; }
            .admin-main { margin-left: 60px; padding: 15px; }
            .admin-stats { grid-template-columns: 1fr 1fr; gap: 10px; }
            .stat-card .number { font-size: 24px; }
            .admin-main h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
    
    <!-- Сайдбар -->
    <aside class="admin-sidebar">
        <a href="../index.php" class="logo">TEAG<span>Reen</span></a>
        <ul class="menu">
            <li><a href="index.php" class="active"><span class="icon">📊</span> <span>Главная</span></a></li>
            <li><a href="products.php"><span class="icon">📦</span> <span>Товары</span></a></li>
            <li><a href="categories.php"><span class="icon">📂</span> <span>Категории</span></a></li>
            <li><a href="orders.php"><span class="icon">📋</span> <span>Заказы</span></a></li>
            <li><a href="pickup_points.php"><span class="icon">📍</span> <span>Пункты выдачи</span></a></li>
            <li><a href="add_product.php"><span class="icon">➕</span> <span>Добавить товар</span></a></li>
        </ul>
        <div class="logout-link">
            <a href="../logout.php"><span class="icon">🚪</span> <span>Выйти</span></a>
        </div>
    </aside>
    
    <!-- Основной контент -->
    <main class="admin-main">
        <h1>👋 Добро пожаловать, Администратор!</h1>
        <p class="welcome">Управляйте магазином TEAGReen</p>
        
        <!-- Статистика -->
        <div class="admin-stats">
            <div class="stat-card">
                <div class="number"><?= $totalProducts ?></div>
                <div class="label">Товаров</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $totalCategories ?></div>
                <div class="label">Категорий</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $totalOrders ?></div>
                <div class="label">Заказов</div>
                <?php if ($newOrders > 0): ?>
                    <div class="badge-new">+<?= $newOrders ?> новых</div>
                <?php endif; ?>
            </div>
            <div class="stat-card">
                <div class="number"><?= $totalUsers ?></div>
                <div class="label">Пользователей</div>
            </div>
        </div>
        
        <!-- Быстрые действия -->
        <div class="admin-grid">
            <div class="admin-card">
                <h3>Последние товары</h3>
                <?php foreach ($recentProducts as $product): ?>
                <div class="item">
                    <span class="name"><?= htmlspecialchars($product['name']) ?></span>
                    <span class="value"><?= number_format($product['price'], 0, '.', ' ') ?> ₽</span>
                </div>
                <?php endforeach; ?>
                <a href="products.php" class="btn-admin">Все товары →</a>
            </div>
            
            <div class="admin-card">
                <h3>Последние заказы</h3>
                <?php foreach ($recentOrders as $order): ?>
                <div class="item">
                    <span class="name">Заказ #<?= $order['id'] ?></span>
                    <span class="value">
                        <span class="status-badge status-<?= $order['status'] ?>">
                            <?php
                            $statuses = ['new' => 'Новый', 'processing' => 'В обработке', 'ready' => 'Готов', 'completed' => 'Выполнен'];
                            echo $statuses[$order['status']] ?? $order['status'];
                            ?>
                        </span>
                    </span>
                </div>
                <?php endforeach; ?>
                <a href="orders.php" class="btn-admin">Все заказы →</a>
            </div>
        </div>
        
        <!-- Быстрые ссылки -->
        <div class="admin-grid">
            <div class="admin-card">
                <h3>➕ Добавить товар</h3>
                <p style="color: #666; font-size: 14px;">Добавьте новый товар в каталог</p>
                <a href="add_product.php" class="btn-admin">Добавить</a>
            </div>
            <div class="admin-card">
                <h3>Управление категориями</h3>
                <p style="color: #666; font-size: 14px;">Создавайте и редактируйте категории</p>
                <a href="categories.php" class="btn-admin btn-admin-outline">Перейти</a>
            </div>
        </div>
    </main>
    
</body>
</html>
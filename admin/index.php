<?php
require '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$recentOrders = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 5")->fetchAll();
$recentProducts = $pdo->query("SELECT * FROM products ORDER BY id DESC LIMIT 5")->fetchAll();
$userName = $_SESSION['login'] ?? 'Администратор';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель — TEAGReen</title>
    <link rel="stylesheet" href="../css/style.css">
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
        .page-header .date { color: #888; font-size: 14px; }
        .cards-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 25px; margin-bottom: 35px;
        }
        .card-block {
            background: white; border-radius: 16px; padding: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }
        .card-block h3 {
            font-size: 16px; color: var(--accent);
            margin: 0 0 16px 0; font-weight: 600;
        }
        .card-block .item {
            display: flex; justify-content: space-between;
            padding: 10px 0; border-bottom: 1px solid #f5f0e8;
        }
        .card-block .item:last-child { border-bottom: none; }
        .card-block .item .name { color: #333; }
        .card-block .item .value { color: #888; font-size: 14px; }
        .btn-admin {
            display: inline-block; padding: 10px 24px; background: var(--accent);
            color: white; border: none; border-radius: 10px; font-size: 14px;
            font-weight: 600; text-decoration: none; transition: 0.3s; cursor: pointer;
        }
        .btn-admin:hover { background: #472a4a; }
        .btn-admin-outline {
            background: transparent; color: var(--accent);
            border: 2px solid var(--accent);
        }
        .btn-admin-outline:hover { background: var(--accent); color: white; }
        .status-badge {
            display: inline-block; padding: 3px 12px;
            border-radius: 20px; font-size: 12px; font-weight: 600;
        }
        .status-new { background: #fee2e2; color: #dc2626; }
        .status-processing { background: #fef3c7; color: #d97706; }
        .status-ready { background: #dbeafe; color: #2563eb; }
        .status-completed { background: #dcfce7; color: #16a34a; }
        @media (max-width: 992px) { .cards-grid { grid-template-columns: 1fr; } }
        @media (max-width: 768px) {
            .admin-sidebar { width: 200px; padding: 20px 15px; }
            .admin-main { margin-left: 200px; padding: 20px; }
            .admin-sidebar .logo { font-size: 22px; }
        }
        @media (max-width: 480px) {
            .admin-sidebar { width: 60px; padding: 15px 10px; }
            .admin-sidebar .logo { font-size: 0; }
            .admin-sidebar .logo::after { content: "TE"; font-size: 20px; font-weight: bold; color: #2d5a27; }
            .admin-sidebar .menu a span { display: none; }
            .admin-sidebar .menu a .icon { margin-right: 0; font-size: 20px; }
            .admin-main { margin-left: 60px; padding: 15px; }
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
            <li><a href="index.php" class="active"><span class="icon"></span> <span>Главная</span></a></li>
            <li><a href="products.php"><span class="icon">📦</span> <span>Товары</span></a></li>
            <li><a href="categories.php"><span class="icon">📂</span> <span>Категории</span></a></li>
            <li><a href="orders.php"><span class="icon">📋</span> <span>Заказы</span></a></li>
            <li><a href="pickup_points.php"><span class="icon">📍</span> <span>Пункты выдачи</span></a></li>
            <li><a href="sales.php"><span class="icon">📈</span> <span>Продажи</span></a></li>
            <li><a href="add_product.php"><span class="icon">➕</span> <span>Добавить товар</span></a></li>
        </ul>

        <div class="logout-link">
            <a href="../logout.php"><span class="icon">🚪</span> <span>Выйти</span></a>
        </div>
    </aside>

    <main class="admin-main">
        <div class="page-header">
            <h1> Добро пожаловать!</h1>
            <span class="date"><?= date('d.m.Y H:i') ?></span>
        </div>

        <div class="cards-grid">
            <div class="card-block">
                <h3>📦 Последние товары</h3>
                <?php if (empty($recentProducts)): ?>
                    <p style="color: #888; font-size: 14px;">Товаров пока нет</p>
                <?php else: ?>
                    <?php foreach ($recentProducts as $product): ?>
                    <div class="item">
                        <span class="name"><?= htmlspecialchars($product['name']) ?></span>
                        <span class="value"><?= number_format($product['price'], 0, '.', ' ') ?> ₽</span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <a href="products.php" class="btn-admin" style="margin-top: 12px;">Все товары →</a>
            </div>

            <div class="card-block">
                <h3>📋 Последние заказы</h3>
                <?php if (empty($recentOrders)): ?>
                    <p style="color: #888; font-size: 14px;">Заказов пока нет</p>
                <?php else: ?>
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
                <?php endif; ?>
                <a href="orders.php" class="btn-admin" style="margin-top: 12px;">Все заказы →</a>
            </div>
        </div>

        <div class="cards-grid">
            <div class="card-block">
                <h3>➕ Добавить товар</h3>
                <p style="color: #666; font-size: 14px;">Добавьте новый товар в каталог</p>
                <a href="add_product.php" class="btn-admin">Добавить</a>
            </div>
            <div class="card-block">
                <h3>📂 Управление категориями</h3>
                <p style="color: #666; font-size: 14px;">Создавайте и редактируйте категории</p>
                <a href="categories.php" class="btn-admin btn-admin-outline">Перейти</a>
            </div>
        </div>
    </main>
</body>
</html>
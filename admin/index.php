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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Админ-панель — TEAGReen</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f9f6f0;
            overflow-x: hidden;
        }
        
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
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
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
            top: 0; left: 0;
            bottom: 0;
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
        
        .admin-sidebar .user-info .name {
            font-weight: 600;
            color: #58355F;
            margin-bottom: 4px;
        }
        
        .admin-sidebar .user-info .role {
            font-size: 13px;
            color: #888;
        }
        
        .admin-sidebar .menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .admin-sidebar .menu li {
            margin-bottom: 5px;
        }
        
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
        
        .admin-sidebar .menu a:hover {
            background: #f5f0e8;
            color: #58355F;
        }
        
        .admin-sidebar .menu a.active {
            background: #58355F;
            color: white;
        }
        
        .admin-sidebar .menu a .icon {
            font-size: 20px;
            width: 28px;
            text-align: center;
        }
        
        .admin-sidebar .logout-link {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f5f0e8;
        }
        
        .admin-sidebar .logout-link a {
            color: #dc2626 !important;
        }
        
        /* === ОСНОВНОЙ КОНТЕНТ === */
        .admin-main {
            margin-left: 280px;
            padding: 30px 40px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .page-header h1 {
            font-size: 32px;
            color: #58355F;
            margin: 0;
        }
        
        .page-header .date {
            color: #888;
            font-size: 14px;
        }
        
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 35px;
        }
        
        .card-block {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        
        .card-block h3 {
            font-size: 16px;
            color: #58355F;
            margin: 0 0 15px 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-block .item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f5f0e8;
        }
        
        .card-block .item:last-child {
            border-bottom: none;
        }
        
        .card-block .item .name {
            color: #333;
            font-weight: 500;
        }
        
        .card-block .item .value {
            color: #888;
            font-size: 14px;
            font-weight: 500;
        }
        
        .card-block p {
            color: #666;
            font-size: 14px;
            margin: 0 0 15px 0;
            line-height: 1.5;
        }
        
        .btn-admin {
            display: block;
            padding: 12px 24px;
            background: #58355F;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            margin-top: 15px;
        }
        
        .btn-admin:hover {
            background: #472a4a;
            transform: translateY(-2px);
        }
        
        .btn-admin-outline {
            background: transparent;
            color: #58355F;
            border: 2px solid #58355F;
        }
        
        .btn-admin-outline:hover {
            background: #58355F;
            color: white;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-new { background: #fee2e2; color: #dc2626; }
        .status-processing { background: #fef3c7; color: #d97706; }
        .status-ready { background: #dbeafe; color: #2563eb; }
        .status-completed { background: #dcfce7; color: #16a34a; }
        
        /* === МОБИЛЬНЫЙ АДАПТИВ === */
        @media (max-width: 992px) {
            .cards-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .admin-burger,
            .mobile-home-btn {
                display: block;
            }
            
            .admin-sidebar {
                transform: translateX(-100%);
            }
            
            .admin-sidebar.active {
                transform: translateX(0);
            }
            
            .admin-main {
                margin-left: 0;
                padding: 80px 20px 30px;
            }
            
            .page-header h1 {
                font-size: 24px;
            }
            
            .card-block {
                padding: 20px;
            }
            
            .card-block h3 {
                font-size: 15px;
            }
        }
        
        @media (max-width: 480px) {
            .admin-sidebar {
                width: 260px;
            }
            
            .admin-main {
                padding: 80px 15px 20px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .page-header h1 {
                font-size: 22px;
            }
            
            .page-header .date {
                font-size: 12px;
            }
            
            .card-block {
                padding: 18px;
            }
            
            .card-block h3 {
                font-size: 14px;
            }
            
            .card-block .item {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .btn-admin {
                padding: 14px 20px;
                font-size: 14px;
            }
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
            <li><a href="index.php" class="active"><span class="icon">📊</span><span>Главная</span></a></li>
            <li><a href="products.php"><span class="icon">📦</span><span>Товары</span></a></li>
            <li><a href="categories.php"><span class="icon">📂</span><span>Категории</span></a></li>
            <li><a href="orders.php"><span class="icon">📋</span><span>Заказы</span></a></li>
            <li><a href="pickup_points.php"><span class="icon">📍</span><span>Пункты выдачи</span></a></li>
            <li><a href="sales.php"><span class="icon">📈</span><span>Продажи</span></a></li>
            <li><a href="add_product.php"><span class="icon">➕</span><span>Добавить товар</span></a></li>
        </ul>
        <div class="logout-link">
            <a href="../logout.php"><span class="icon">🚪</span><span>Выйти</span></a>
        </div>
    </aside>

    <main class="admin-main">
        <div class="page-header">
            <h1>👋 Добро пожаловать!</h1>
            <span class="date"><?= date('d.m.Y H:i') ?></span>
        </div>

        <div class="cards-grid">
            <div class="card-block">
                <h3>📦 Последние товары</h3>
                <?php if (empty($recentProducts)): ?>
                    <p>Товаров пока нет</p>
                <?php else: ?>
                    <?php foreach ($recentProducts as $product): ?>
                    <div class="item">
                        <span class="name"><?= htmlspecialchars($product['name']) ?></span>
                        <span class="value"><?= number_format($product['price'], 0, '.', ' ') ?> ₽</span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <a href="products.php" class="btn-admin">Все товары →</a>
            </div>

            <div class="card-block">
                <h3>📋 Последние заказы</h3>
                <?php if (empty($recentOrders)): ?>
                    <p>Заказов пока нет</p>
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
                <a href="orders.php" class="btn-admin">Все заказы →</a>
            </div>
        </div>

        <div class="cards-grid">
            <div class="card-block">
                <h3>➕ Добавить товар</h3>
                <p>Добавьте новый товар в каталог</p>
                <a href="add_product.php" class="btn-admin">Добавить</a>
            </div>
            <div class="card-block">
                <h3>📂 Управление категориями</h3>
                <p>Создавайте и редактируйте категории</p>
                <a href="categories.php" class="btn-admin btn-admin-outline">Перейти</a>
            </div>
        </div>
    </main>

    <script>
    const burger = document.getElementById('adminBurger');
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('adminOverlay');
    
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
    </script>
</body>
</html>
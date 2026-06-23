<?php
require '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Доступ запрещен.");
}

// Обработка удаления
if (isset($_GET['delete'])) {
    $pickupId = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM pickup_points WHERE id = ?")->execute([$pickupId]);
    header("Location: pickup_points.php");
    exit;
}

// Обработка добавления
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['address'])) {
    $address = trim($_POST['address']);
    $pdo->prepare("INSERT INTO pickup_points (address) VALUES (?)")->execute([$address]);
    header("Location: pickup_points.php");
    exit;
}

// Получаем все пункты выдачи
$pickupPoints = $pdo->query("SELECT * FROM pickup_points ORDER BY id")->fetchAll();
$userName = $_SESSION['login'] ?? 'Администратор';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пунктами выдачи</title>
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
        .admin-container { max-width: 800px; margin: 0 auto; }
        .page-header h1 { font-size: 32px; color: var(--accent); margin: 0 0 25px 0; }
        .add-form { 
            margin-bottom: 30px; padding: 25px; 
            background: #f5f0e8; border-radius: 12px;
        }
        .add-form-inner { display: flex; gap: 15px; flex-wrap: wrap; }
        .add-form input { 
            flex: 1; min-width: 250px; padding: 12px 16px; 
            border: 2px solid #e0e0e0; border-radius: 10px;
            font-size: 15px;
        }
        .add-form input:focus { outline: none; border-color: #86A88F; }
        .btn-add { 
            background: #16a34a; color: white; padding: 12px 24px; 
            border: none; border-radius: 10px; cursor: pointer;
            font-size: 15px; font-weight: 600; transition: 0.3s; white-space: nowrap;
        }
        .btn-add:hover { background: #15803d; transform: translateY(-2px); }
        .pickup-list { margin-top: 20px; }
        .pickup-item { 
            display: flex; justify-content: space-between; align-items: center; 
            padding: 18px 20px; background: white; border-radius: 12px;
            margin-bottom: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .pickup-item strong { color: var(--accent); }
        .btn-delete { 
            background: #dc2626; color: white; padding: 8px 16px; 
            text-decoration: none; border-radius: 8px; font-weight: 600;
            transition: 0.3s;
        }
        .btn-delete:hover { background: #991b1b; transform: translateY(-2px); }
        .empty-state {
            text-align: center; padding: 40px; background: white;
            border-radius: 12px; color: #888;
        }
        .back-link {
            display: inline-flex; align-items: center; gap: 8px;
            margin-top: 25px; color: #666; text-decoration: none;
            transition: 0.3s; font-weight: 500;
        }
        .back-link:hover { color: var(--accent); }
        @media (max-width: 768px) {
            .admin-sidebar { width: 200px; }
            .admin-main { margin-left: 200px; padding: 20px; }
        }
        @media (max-width: 480px) {
            .admin-sidebar { width: 60px; }
            .admin-sidebar .logo, .admin-sidebar .user-info, .admin-sidebar .menu a span { display: none; }
            .admin-main { margin-left: 60px; padding: 15px; }
            .add-form-inner { flex-direction: column; }
            .add-form input { width: 100%; }
            .btn-add { width: 100%; }
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
            <li><a href="pickup_points.php" class="active"><span class="icon">📍</span><span>Пункты выдачи</span></a></li>
            <li><a href="sales.php"><span class="icon">📈</span><span>Продажи</span></a></li>
            <li><a href="add_product.php"><span class="icon">➕</span><span>Добавить товар</span></a></li>
        </ul>
        <div class="logout-link">
            <a href="../logout.php"><span class="icon">🚪</span><span>Выйти</span></a>
        </div>
    </aside>

    <main class="admin-main">
        <div class="admin-container">
            <div class="page-header">
                <h1>📍 Управление пунктами выдачи</h1>
            </div>
            
            <div class="add-form">
                <form method="POST" class="add-form-inner">
                    <input type="text" name="address" placeholder="Адрес пункта выдачи" required>
                    <button type="submit" class="btn-add">➕ Добавить пункт</button>
                </form>
            </div>
            
            <div class="pickup-list">
                <?php if (empty($pickupPoints)): ?>
                    <div class="empty-state">
                        <p>📍 Пункты выдачи не добавлены</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pickupPoints as $point): ?>
                    <div class="pickup-item">
                        <span><strong>ID <?= $point['id'] ?>:</strong> <?= htmlspecialchars($point['address']) ?></span>
                        <a href="?delete=<?= $point['id'] ?>" class="btn-delete" onclick="return confirm('Удалить пункт выдачи?')">🗑️ Удалить</a>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <a href="index.php" class="back-link">← Вернуться к главной</a>
        </div>
    </main>
</body>
</html>
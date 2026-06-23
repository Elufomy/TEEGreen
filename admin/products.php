<?php
require '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Удаление товара
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    header("Location: products.php");
    exit;
}

// Получаем все товары с категориями
$products = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.id DESC
")->fetchAll();

$userName = $_SESSION['login'] ?? 'Администратор';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Товары — TEAGReen</title>
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
        .table-wrapper {
            background: white; border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04); overflow-x: auto;
        }
        .data-table { width: 100%; border-collapse: collapse; min-width: 900px; }
        .data-table th {
            background: #F5F2E1; color: var(--accent); padding: 15px;
            text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase;
        }
        .data-table td {
            padding: 15px; border-bottom: 1px solid #f5f0e8; vertical-align: middle;
        }
        .data-table tr:hover { background: #fafafa; }
        .product-img {
            width: 60px; height: 60px; object-fit: cover;
            border-radius: 10px; border: 2px solid #f5f0e8;
        }
        .badge-new {
            display: inline-block; background: #e74c3c; color: white;
            padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
        }
        .stock-ok { color: #16a34a; font-weight: 600; }
        .stock-low { color: #d97706; font-weight: 600; }
        .stock-out { color: #dc2626; font-weight: 600; }
        .btn {
            display: inline-block; padding: 8px 18px; background: var(--accent);
            color: white; border: none; border-radius: 8px; font-size: 13px;
            font-weight: 600; text-decoration: none; transition: 0.3s; cursor: pointer;
        }
        .btn:hover { background: #472a4a; }
        .btn-outline {
            background: transparent; color: var(--accent);
            border: 2px solid var(--accent);
        }
        .btn-outline:hover { background: var(--accent); color: white; }
        .btn-danger { background: #dc2626; }
        .btn-danger:hover { background: #991b1b; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .actions { display: flex; gap: 8px; }
        @media (max-width: 768px) {
            .admin-sidebar { width: 200px; padding: 20px 15px; }
            .admin-main { margin-left: 200px; padding: 20px; }
        }
        @media (max-width: 480px) {
            .admin-sidebar { width: 60px; }
            .admin-sidebar .logo, .admin-sidebar .user-info, .admin-sidebar .menu a span { display: none; }
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
            <li><a href="index.php"><span class="icon">📊</span><span>Главная</span></a></li>
            <li><a href="products.php" class="active"><span class="icon">📦</span><span>Товары</span></a></li>
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
            <h1>📦 Все товары</h1>
            <a href="add_product.php" class="btn">➕ Добавить товар</a>
        </div>

        <?php if (empty($products)): ?>
            <div style="background: white; padding: 40px; border-radius: 16px; text-align: center;">
                <p style="font-size: 18px; color: #888; margin-bottom: 20px;">Товаров пока нет</p>
                <a href="add_product.php" class="btn">Добавить первый товар</a>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Фото</th>
                            <th>Название</th>
                            <th>Категория</th>
                            <th>Цена</th>
                            <th>Остаток</th>
                            <th>Новинка</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <?php if (!empty($p['image_path'])): ?>
                                    <img src="../<?= htmlspecialchars($p['image_path']) ?>" 
                                         alt="<?= htmlspecialchars($p['name']) ?>" class="product-img">
                                <?php else: ?>
                                    <div class="product-img" style="background: #f5f0e8; display: flex; align-items: center; justify-content: center;">📷</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                            <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                            <td><strong><?= number_format($p['price'], 0, '.', ' ') ?> ₽</strong></td>
                            <td>
                                <?php if ($p['stock'] > 10): ?>
                                    <span class="stock-ok">✓ <?= $p['stock'] ?> шт.</span>
                                <?php elseif ($p['stock'] > 0): ?>
                                    <span class="stock-low">⚠ <?= $p['stock'] ?> шт.</span>
                                <?php else: ?>
                                    <span class="stock-out">✗ Нет</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $p['is_new'] ? '<span class="badge-new">NEW</span>' : '—' ?></td>
                            <td class="actions">
                                <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline">✏️</a>
                                <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Удалить товар?')">🗑️</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
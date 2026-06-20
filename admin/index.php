<?php
require '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Доступ запрещен. <a href='../login.php'>Войти</a>");
}


$stmt = $pdo->query("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.id DESC
");
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель - Товары</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-container { max-width: 1200px; margin: 40px auto; padding: 20px; }
        .admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .admin-table th, .admin-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .admin-table th { background: #f5f0e8; }
        .admin-table img { max-width: 80px; height: auto; }
        .btn-edit { background: #4CAF50; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; }
        .btn-delete { background: #f44336; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; }
        .btn-add { background: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1>Управление товарами</h1>
        <p>
            <a href="add_product.php" class="btn-add">+ Добавить новый товар</a>
            <a href="categories.php" class="btn-add" style="background: #9C27B0;">Управление категориями</a>
            <a href="pickup_points.php" class="btn-add" style="background: #FF9800;">Пункты выдачи</a>
        </p>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Картинка</th>
                    <th>Название</th>
                    <th>Категория</th>
                    <th>Цена</th>
                    <th>Остаток</th>
                    <th>Новинка</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= $product['id'] ?></td>
                    <td>
                        <?php if (!empty($product['image_path'])): ?>
                            <img src="../<?= htmlspecialchars($product['image_path']) ?>" alt="">
                        <?php else: ?>
                            <em>Нет картинки</em>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($product['name']) ?></td>
                    <td><?= htmlspecialchars($product['category_name'] ?? 'Без категории') ?></td>
                    <td><?= number_format($product['price'], 2, '.', ' ') ?> ₽</td>
                    <td><?= $product['stock'] ?> шт.</td>
                    <td><?= $product['is_new'] ? '✅ Да' : '❌ Нет' ?></td>
                    <td>
                        <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn-edit">Изменить</a>
                        <a href="delete_product.php?id=<?= $product['id'] ?>" class="btn-delete" onclick="return confirm('Удалить товар?')">Удалить</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <p style="margin-top: 30px;"><a href="../index.php">← Вернуться на сайт</a></p>
    </div>
</body>
</html>
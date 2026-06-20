<?php
require '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Доступ запрещен.");
}

// Обработка удаления
if (isset($_GET['delete'])) {
    $catId = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$catId]);
    header("Location: categories.php");
    exit;
}

// Обработка добавления
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['name'])) {
    $name = trim($_POST['name']);
    $pdo->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$name]);
    header("Location: categories.php");
    exit;
}

// Получаем все категории
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление категориями</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-container { max-width: 800px; margin: 40px auto; padding: 20px; }
        .category-list { margin-top: 20px; }
        .category-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; border: 1px solid #ddd; margin-bottom: 10px; border-radius: 4px; }
        .btn-delete { background: #f44336; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; }
        .add-form { margin-bottom: 30px; padding: 20px; background: #f5f0e8; border-radius: 8px; }
        .add-form input { padding: 8px; width: 300px; border: 1px solid #ddd; border-radius: 4px; }
        .btn-add { background: #4CAF50; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1>Управление категориями</h1>
        
        <div class="add-form">
            <form method="POST">
                <input type="text" name="name" placeholder="Название новой категории" required>
                <button type="submit" class="btn-add">Добавить категорию</button>
            </form>
        </div>
        
        <div class="category-list">
            <?php foreach ($categories as $cat): ?>
            <div class="category-item">
                <span><strong>ID <?= $cat['id'] ?>:</strong> <?= htmlspecialchars($cat['name']) ?></span>
                <a href="?delete=<?= $cat['id'] ?>" class="btn-delete" onclick="return confirm('Удалить категорию?')">Удалить</a>
            </div>
            <?php endforeach; ?>
        </div>
        
        <p style="margin-top: 30px;"><a href="index.php">← Вернуться к товарам</a></p>
    </div>
</body>
</html>
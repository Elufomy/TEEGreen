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
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление пунктами выдачи</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-container { max-width: 800px; margin: 40px auto; padding: 20px; }
        .pickup-list { margin-top: 20px; }
        .pickup-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; border: 1px solid #ddd; margin-bottom: 10px; border-radius: 4px; }
        .btn-delete { background: #f44336; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; }
        .add-form { margin-bottom: 30px; padding: 20px; background: #f5f0e8; border-radius: 8px; }
        .add-form input { padding: 8px; width: 400px; border: 1px solid #ddd; border-radius: 4px; }
        .btn-add { background: #4CAF50; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1>Управление пунктами выдачи</h1>
        
        <div class="add-form">
            <form method="POST">
                <input type="text" name="address" placeholder="Адрес пункта выдачи" required>
                <button type="submit" class="btn-add">Добавить пункт</button>
            </form>
        </div>
        
        <div class="pickup-list">
            <?php if (empty($pickupPoints)): ?>
                <p>Пункты выдачи не добавлены.</p>
            <?php else: ?>
                <?php foreach ($pickupPoints as $point): ?>
                <div class="pickup-item">
                    <span><strong>ID <?= $point['id'] ?>:</strong> <?= htmlspecialchars($point['address']) ?></span>
                    <a href="?delete=<?= $point['id'] ?>" class="btn-delete" onclick="return confirm('Удалить пункт выдачи?')">Удалить</a>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <p style="margin-top: 30px;"><a href="index.php">← Вернуться к товарам</a></p>
    </div>
</body>
</html>
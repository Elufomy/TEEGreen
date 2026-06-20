<?php
require '../includes/db.php';

// Проверка: только админ!
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Доступ запрещен.");
}

// Получаем ID товара из URL
$productId = (int)($_GET['id'] ?? 0);

// Получаем текущие данные товара
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    die("Товар не найден.");
}

// Получаем категории для выпадающего списка
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$error = '';

// Обработка формы сохранения
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $category_id = (int)$_POST['category_id'];
    $is_new = isset($_POST['is_new']) ? 1 : 0;
    
    $imagePath = $product['image_path']; // По умолчанию оставляем старую картинку

    // Обработка загрузки НОВОЙ картинки
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($ext, $allowed)) {
            $newFilename = uniqid('product_') . '.' . $ext;
            $destination = $uploadDir . $newFilename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                $imagePath = 'uploads/' . $newFilename; // Новый путь для БД
                
                // Удаляем старую картинку, если она была
                if (!empty($product['image_path']) && file_exists('../' . $product['image_path'])) {
                    unlink('../' . $product['image_path']);
                }
            } else {
                $error = "Ошибка при загрузке файла";
            }
        } else {
            $error = "Разрешены только файлы: " . implode(', ', $allowed);
        }
    }
    
    if (empty($error) && !empty($name)) {
        $stmt = $pdo->prepare("
            UPDATE products 
            SET name = ?, description = ?, price = ?, stock = ?, category_id = ?, is_new = ?, image_path = ? 
            WHERE id = ?
        ");
        $stmt->execute([$name, $description, $price, $stock, $category_id, $is_new, $imagePath, $productId]);
        
        header("Location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать товар</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-container { max-width: 800px; margin: 40px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"], 
        .form-group input[type="number"], 
        .form-group textarea, 
        .form-group select { 
            width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; 
        }
        .form-group textarea { min-height: 100px; }
        .btn-submit { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .current-image { margin-top: 10px; max-width: 200px; border-radius: 8px; }
        .error { color: red; margin-bottom: 15px; background: #ffebee; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1>Редактировать товар: <?= htmlspecialchars($product['name']) ?></h1>
        
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        
        <!-- ВАЖНО: enctype="multipart/form-data" нужен для загрузки файлов -->
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Название товара *</label>
                <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Описание</label>
                <textarea name="description"><?= htmlspecialchars($product['description']) ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Категория *</label>
                <select name="category_id" required>
                    <option value="">-- Выберите категорию --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $product['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Цена (₽) *</label>
                <input type="number" name="price" step="0.01" min="0" value="<?= $product['price'] ?>" required>
            </div>
            
            <div class="form-group">
                <label>Остаток на складе (шт.) *</label>
                <input type="number" name="stock" min="0" value="<?= $product['stock'] ?>" required>
            </div>
            
            <div class="form-group">
                <label>Картинка товара</label>
                <?php if (!empty($product['image_path'])): ?>
                    <br>
                    <img src="../<?= htmlspecialchars($product['image_path']) ?>" alt="Текущая картинка" class="current-image">
                    <p style="font-size: 12px; color: #666;">Загрузите новый файл, чтобы заменить её</p>
                <?php endif; ?>
                <input type="file" name="image" accept="image/*" style="margin-top: 10px;">
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_new" value="1" <?= $product['is_new'] ? 'checked' : '' ?>>
                    Отметить как новинку
                </label>
            </div>
            
            <button type="submit" class="btn-submit">Сохранить изменения</button>
            <a href="index.php" style="margin-left: 15px;">Отмена</a>
        </form>
    </div>
</body>
</html>
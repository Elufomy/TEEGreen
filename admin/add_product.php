<?php
require '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Доступ запрещен.");
}

$error = '';
$success = '';

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $category_id = (int)$_POST['category_id'];
    $is_new = isset($_POST['is_new']) ? 1 : 0;
    
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($ext, $allowed)) {
            $error = "Разрешены только файлы: " . implode(', ', $allowed);
        } else {
            $newFilename = uniqid('product_') . '.' . $ext;
            $destination = $uploadDir . $newFilename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                $imagePath = 'uploads/' . $newFilename; // Относительный путь для БД
            } else {
                $error = "Ошибка при загрузке файла";
            }
        }
    }
    
    if (empty($error) && !empty($name)) {
        $stmt = $pdo->prepare("
            INSERT INTO products (name, description, price, stock, category_id, is_new, image_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $description, $price, $stock, $category_id, $is_new, $imagePath]);
        
        header("Location: index.php?success=added");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить товар</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-container { max-width: 800px; margin: 40px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea, .form-group select { 
            width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; 
        }
        .form-group textarea { min-height: 100px; }
        .btn-submit { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: red; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1>Добавить новый товар</h1>
        
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Название товара *</label>
                <input type="text" name="name" required>
            </div>
            
            <div class="form-group">
                <label>Описание</label>
                <textarea name="description"></textarea>
            </div>
            
            <div class="form-group">
                <label>Категория *</label>
                <select name="category_id" required>
                    <option value="">-- Выберите категорию --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Цена (₽) *</label>
                <input type="number" name="price" step="0.01" min="0" required>
            </div>
            
            <div class="form-group">
                <label>Остаток на складе (шт.) *</label>
                <input type="number" name="stock" min="0" required>
            </div>
            
            <div class="form-group">
                <label>Картинка товара</label>
                <input type="file" name="image" accept="image/*">
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_new" value="1">
                    Отметить как новинку
                </label>
            </div>
            
            <button type="submit" class="btn-submit">Добавить товар</button>
            <a href="index.php" style="margin-left: 15px;">Отмена</a>
        </form>
    </div>
</body>
</html>
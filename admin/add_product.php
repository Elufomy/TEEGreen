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
                $imagePath = 'uploads/' . $newFilename;
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

$userName = $_SESSION['login'] ?? 'Администратор';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить товар</title>
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
        .page-header h1 { font-size: 32px; color: var(--accent); margin: 0 0 10px 0; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px; }
        .form-group input, .form-group textarea, .form-group select { 
            width: 100%; padding: 12px 16px; border: 2px solid #e0e0e0; border-radius: 10px;
            font-size: 15px; transition: border-color 0.3s; box-sizing: border-box;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none; border-color: #86A88F;
        }
        .form-group textarea { min-height: 120px; resize: vertical; }
        .error { 
            background: #f8d7da; color: #721c24; padding: 15px; 
            border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #dc3545;
        }
        .form-actions { display: flex; gap: 15px; margin-top: 25px; }
        .btn-submit { 
            background: #16a34a; color: white; padding: 12px 30px; border: none; 
            border-radius: 10px; cursor: pointer; font-size: 15px; font-weight: 600;
            transition: 0.3s;
        }
        .btn-submit:hover { background: #15803d; transform: translateY(-2px); }
        .btn-cancel {
            background: transparent; color: #666; padding: 12px 30px;
            border: 2px solid #ddd; border-radius: 10px; text-decoration: none;
            transition: 0.3s; font-weight: 600;
        }
        .btn-cancel:hover { background: #f5f5f5; color: #333; }
        .checkbox-group {
            display: flex; align-items: center; gap: 10px; padding: 15px;
            background: #f9f6f0; border-radius: 10px;
        }
        .checkbox-group input[type="checkbox"] { width: 20px; height: 20px; cursor: pointer; }
        .checkbox-group label { margin: 0; cursor: pointer; }
        @media (max-width: 768px) {
            .admin-sidebar { width: 200px; padding: 20px 15px; }
            .admin-main { margin-left: 200px; padding: 20px; }
        }
        @media (max-width: 480px) {
            .admin-sidebar { width: 60px; }
            .admin-sidebar .logo, .admin-sidebar .user-info, .admin-sidebar .menu a span { display: none; }
            .admin-main { margin-left: 60px; padding: 15px; }
            .form-actions { flex-direction: column; }
            .btn-submit, .btn-cancel { width: 100%; }
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
            <li><a href="pickup_points.php"><span class="icon">📍</span><span>Пункты выдачи</span></a></li>
            <li><a href="sales.php"><span class="icon">📈</span><span>Продажи</span></a></li>
            <li><a href="add_product.php" class="active"><span class="icon">➕</span><span>Добавить товар</span></a></li>
        </ul>
        <div class="logout-link">
            <a href="../logout.php"><span class="icon">🚪</span><span>Выйти</span></a>
        </div>
    </aside>

    <main class="admin-main">
        <div class="admin-container">
            <div class="page-header">
                <h1>Добавить новый товар</h1>
            </div>
            
            <?php if ($error): ?>
                <div class="error">❌ <?= htmlspecialchars($error) ?></div>
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
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_new" value="1" id="is_new">
                        <label for="is_new">🔥 Отметить как новинку</label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-submit">✅ Добавить товар</button>
                    <a href="index.php" class="btn-cancel">↩️ Отмена</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
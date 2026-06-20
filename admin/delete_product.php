<?php
require '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Доступ запрещен.");
}

$productId = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT image_path FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if ($product) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    
    if (!empty($product['image_path'])) {
        $filePath = '../' . $product['image_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}

header("Location: index.php");
exit;
?>
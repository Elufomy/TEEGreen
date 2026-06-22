<?php
require '../../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ запрещен']);
    exit;
}

$orderId = (int)($_POST['order_id'] ?? 0);
$newStatus = $_POST['status'] ?? '';

$allowedStatuses = ['new', 'processing', 'ready', 'completed'];

if ($orderId <= 0 || !in_array($newStatus, $allowedStatuses)) {
    echo json_encode(['error' => 'Неверные данные']);
    exit;
}

$stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
$stmt->execute([$newStatus, $orderId]);

echo json_encode(['success' => true]);
?>
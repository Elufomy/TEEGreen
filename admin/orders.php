<?php
require '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Доступ запрещен. <a href='../login.php'>Войти</a>");
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказы - Админ-панель</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-container { max-width: 1200px; margin: 40px auto; padding: 20px; }
        .admin-container h1 { color: var(--accent); margin-bottom: 10px; }
        .live-indicator { display: inline-block; width: 10px; height: 10px; background: #4CAF50; border-radius: 50%; margin-right: 8px; animation: pulse 2s infinite; }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.4; }
            100% { opacity: 1; }
        }
        .orders-list { display: flex; flex-direction: column; gap: 20px; margin-top: 30px; }
        .order-card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .order-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .order-number { font-size: 20px; font-weight: bold; color: var(--accent); }
        .order-date { color: #999; font-size: 14px; }
        .order-user { color: #666; font-size: 14px; }
        .order-pickup { color: #666; font-size: 14px; margin-bottom: 15px; }
        .order-items { margin-bottom: 20px; }
        .order-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #eee; font-size: 14px; }
        .order-item:last-child { border-bottom: none; }
        .order-total { font-size: 18px; font-weight: bold; text-align: right; margin-bottom: 20px; color: var(--accent); }
        .status-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
        .status-btn { padding: 8px 16px; border: 2px solid #ddd; background: white; border-radius: 20px; cursor: pointer; font-size: 14px; transition: all 0.2s; }
        .status-btn:hover { border-color: var(--accent); }
        .status-btn.active { background: var(--accent); color: white; border-color: var(--accent); }
        .status-new { background: #2196F3; color: white; }
        .status-processing { background: #FF9800; color: white; }
        .status-ready { background: #4CAF50; color: white; }
        .status-completed { background: #9E9E9E; color: white; }
        .empty-orders { text-align: center; padding: 50px; color: #999; background: white; border-radius: 20px; }
        .back-link { display: inline-block; margin-top: 30px; color: var(--accent); text-decoration: none; }
    </style>
</head>
<body style="background: #f9f6f0;">

    <div class="admin-container">
        <h1> Управление заказами</h1>
        <p><span class="live-indicator"></span>Live-режим: обновление каждые 5 секунд</p>
        
        <div id="orders-container" class="orders-list">
            <div class="empty-orders">Загрузка заказов...</div>
        </div>
        
        <a href="index.php" class="back-link">← Вернуться в админ-панель</a>
    </div>

    <script src="../js/js/jquery-4.0.0.min.js"></script>
    <script>
    // Названия статусов
    const statusNames = {
        'new': 'Новый',
        'processing': 'В обработке',
        'ready': 'Готов к выдаче',
        'completed': 'Выполнен'
    };

    // Загрузка заказов
    function loadOrders() {
        $.getJSON('api/get_orders.php', function(orders) {
            if (orders.length === 0) {
                $('#orders-container').html('<div class="empty-orders"> Заказов пока нет</div>');
                return;
            }

            let html = '';
            orders.forEach(function(order) {
                // Формируем список товаров
                let itemsHtml = '';
                order.items.forEach(function(item) {
                    itemsHtml += `
                        <div class="order-item">
                            <span>${item.name} × ${item.quantity}</span>
                            <span>${Number(item.price_at_purchase).toLocaleString('ru-RU')} ₽</span>
                        </div>
                    `;
                });

                // Формируем кнопки статусов
                let buttonsHtml = '';
                ['new', 'processing', 'ready', 'completed'].forEach(function(status) {
                    const isActive = order.status === status ? 'active' : '';
                    buttonsHtml += `
                        <button class="status-btn ${isActive}" 
                                onclick="changeStatus(${order.order_id}, '${status}')">
                            ${statusNames[status]}
                        </button>
                    `;
                });

                // Форматируем дату
                const date = new Date(order.created_at);
                const dateStr = date.toLocaleString('ru-RU', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                html += `
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <span class="order-number">Заказ #${order.order_id}</span>
                                <span class="order-date"> · ${dateStr}</span>
                            </div>
                            <div class="order-user">👤 ${order.user_login || 'Гость'}</div>
                        </div>
                        
                        <div class="order-pickup">📍 ${order.pickup_address}</div>
                        
                        <div class="order-items">
                            ${itemsHtml}
                        </div>
                        
                        <div class="order-total">
                            Итого: ${Number(order.total_price).toLocaleString('ru-RU')} ₽
                        </div>
                        
                        <div class="status-buttons">
                            ${buttonsHtml}
                        </div>
                    </div>
                `;
            });

            $('#orders-container').html(html);
        }).fail(function() {
            console.error('Ошибка загрузки заказов');
        });
    }

    // Смена статуса
    function changeStatus(orderId, newStatus) {
        $.post('api/change_status.php', { 
            order_id: orderId, 
            status: newStatus 
        }, function(response) {
            if (response.success) {
                // Сразу обновляем список
                loadOrders();
            } else {
                alert('Ошибка: ' + response.error);
            }
        }, 'json').fail(function() {
            alert('Ошибка соединения с сервером');
        });
    }

    // Загружаем заказы при открытии страницы
    loadOrders();

    // Live-режим: обновляем каждые 5 секунд
    setInterval(loadOrders, 5000);
    </script>
</body>
</html>
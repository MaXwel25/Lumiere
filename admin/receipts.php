<?php
// receipts.php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAdminAuth();

// фильтры
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$payment_status = $_GET['payment_status'] ?? '';
$payment_method = $_GET['payment_method'] ?? '';
$master_id = $_GET['master_id'] ?? '';

// пагинация
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 25;
$offset = ($page - 1) * $limit;

// формируем условия запроса (PostgreSQL синтаксис)
// Используем явное приведение к date и добавление целого числа дней
$where = "WHERE r.issued_at >= ? AND r.issued_at < ?::date + 1";
$params = [$start_date, $end_date];

if ($payment_status) {
    $where .= " AND r.payment_status = ?";
    $params[] = $payment_status;
}
if ($payment_method) {
    $where .= " AND r.payment_method = ?";
    $params[] = $payment_method;
}
if ($master_id) {
    $where .= " AND a.master_id = ?";
    $params[] = $master_id;
}

// получаем общее количество чеков
$count_query = "
    SELECT COUNT(*) as total 
    FROM receipts r
    JOIN appointments a ON r.appointment_id = a.id
    JOIN clients c ON a.client_id = c.id
    $where
";
$stmt = $db->prepare($count_query);
$stmt->execute($params);
$totalReceipts = $stmt->fetch()['total'];
$totalPages = ceil($totalReceipts / $limit);

// получаем чеки для текущей страницы
$query = "
    SELECT 
        r.*,
        a.appointment_date,
        a.start_time,
        a.status as appointment_status,
        c.full_name as client_name,
        c.phone as client_phone,
        m.full_name as master_name,
        s.name as service_name,
        s.price as service_price
    FROM receipts r
    JOIN appointments a ON r.appointment_id = a.id
    JOIN clients c ON a.client_id = c.id
    JOIN masters m ON a.master_id = m.id
    JOIN services s ON a.service_id = s.id
    $where
    ORDER BY r.issued_at DESC
    LIMIT ? OFFSET ?
";
// Добавляем параметры пагинации
$stmt = $db->prepare($query);
$stmt->execute(array_merge($params, [$limit, $offset]));
$receipts = $stmt->fetchAll();

// получаем мастеров для фильтра
$masters = $db->query("SELECT id, full_name FROM masters ORDER BY full_name")->fetchAll();

// обработка изменения статуса оплаты
if (isset($_GET['mark_paid'])) {
    $receipt_id = intval($_GET['mark_paid']);
    $stmt = $db->prepare("UPDATE receipts SET payment_status = 'paid', paid_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$receipt_id]);
    // редирект без параметров mark_paid
    unset($_GET['mark_paid']);
    header("Location: receipts.php?" . http_build_query($_GET));
    exit();
}

// обработка возврата
if (isset($_GET['mark_cancelled'])) {
    $receipt_id = intval($_GET['mark_cancelled']);
    $stmt = $db->prepare("UPDATE receipts SET payment_status = 'refunded' WHERE id = ?");
    $stmt->execute([$receipt_id]);
    unset($_GET['mark_cancelled']);
    header("Location: receipts.php?" . http_build_query($_GET));
    exit();
}

// статистика за период
$stats_query = "
    SELECT 
        COUNT(*) as total_count,
        COALESCE(SUM(r.final_amount), 0) as total_amount,
        COALESCE(AVG(r.final_amount), 0) as avg_amount,
        COUNT(*) FILTER (WHERE r.payment_status = 'paid') as paid_count,
        COALESCE(SUM(r.final_amount) FILTER (WHERE r.payment_status = 'paid'), 0) as paid_amount,
        COUNT(*) FILTER (WHERE r.payment_status = 'pending') as pending_count
    FROM receipts r
    JOIN appointments a ON r.appointment_id = a.id
    WHERE r.issued_at >= ? AND r.issued_at < ?::date + 1
";
$stmt = $db->prepare($stats_query);
$stmt->execute([$start_date, $end_date]);
$stats = $stmt->fetch();

// способы оплаты для фильтра
$payment_methods = $db->query("SELECT DISTINCT payment_method FROM receipts")->fetchAll();

// статусы оплаты для фильтра
$payment_statuses = [
    'pending' => 'Ожидает оплаты',
    'paid' => 'Оплачено',
    'refunded' => 'Возврат'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление чеками - Админ-панель</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* стили без изменений */
        .admin-container { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 250px; background: #2c3e50; color: white; padding: 20px 0; }
        .admin-content { flex: 1; padding: 20px; background: #f5f5f5; }
        .admin-logo { text-align: center; padding: 20px; border-bottom: 1px solid #34495e; }
        .admin-menu { list-style: none; padding: 0; }
        .admin-menu li { border-bottom: 1px solid #34495e; }
        .admin-menu a { display: block; padding: 15px 20px; color: #ecf0f1; text-decoration: none; transition: background 0.3s; }
        .admin-menu a:hover, .admin-menu a.active { background: #34495e; }
        .admin-menu i { width: 20px; margin-right: 10px; }
        .table-responsive { overflow-x: auto; background: white; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        table th { background: #f8f9fa; font-weight: 600; color: #2c3e50; }
        .btn { padding: 8px 16px; border-radius: 5px; text-decoration: none; font-weight: 600; cursor: pointer; border: none; transition: all 0.3s; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #2ecc71; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .pagination { display: flex; justify-content: center; margin-top: 20px; gap: 5px; }
        .pagination a, .pagination span { display: inline-block; padding: 8px 12px; background: white; border: 1px solid #ddd; border-radius: 5px; text-decoration: none; color: #333; }
        .pagination a:hover { background: #3498db; color: white; }
        .pagination .current { background: #3498db; color: white; border-color: #3498db; }
        .search-form { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #2ecc71; color: white; }
        .badge-danger { background: #e74c3c; color: white; }
        .badge-info { background: #3498db; color: white; }
        .badge-warning { background: #f39c12; color: white; }
        .action-buttons { display: flex; gap: 5px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 28px; font-weight: bold; color: #2c3e50; }
        .stat-label { font-size: 14px; color: #666; margin-top: 5px; }
        .amount { font-weight: bold; color: #27ae60; }
        .payment-cash { color: #27ae60; }
        .payment-card { color: #3498db; }
        .payment-online { color: #9b59b6; }
        .receipt-details { background: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 5px; }
        .filter-actions { display: flex; gap: 10px; margin-top: 10px; }
        .export-btn { background: #2ecc71; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; }
        .print-btn { background: #3498db; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <div class="admin-logo">
                <h2><i class="fas fa-cut"></i> Админ-панель</h2>
                <small>Парикмахерская "Lumiere"</small>
            </div>
            <ul class="admin-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Дашборд</a></li>
                <li><a href="appointments.php"><i class="fas fa-calendar-alt"></i> Записи</a></li>
                <li><a href="clients.php"><i class="fas fa-users"></i> Клиенты</a></li>
                <li><a href="masters.php"><i class="fas fa-user-tie"></i> Мастера</a></li>
                <li><a href="services.php"><i class="fas fa-concierge-bell"></i> Услуги</a></li>
                <li><a href="schedule.php"><i class="fas fa-clock"></i> Расписание</a></li>
                <li><a href="receipts.php" class="active"><i class="fas fa-receipt"></i> Чеки</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Выход</a></li>
            </ul>
        </div>

        <div class="admin-content">
            <h1><i class="fas fa-receipt"></i> Управление чеками</h1>

            <!-- фильтры -->
            <div class="search-form">
                <form method="GET">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date">Дата с</label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="form-group">
                            <label for="end_date">Дата по</label>
                            <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="form-group">
                            <label for="payment_status">Статус оплаты</label>
                            <select id="payment_status" name="payment_status">
                                <option value="">Все статусы</option>
                                <?php foreach ($payment_statuses as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo ($payment_status == $value) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="payment_method">Способ оплаты</label>
                            <select id="payment_method" name="payment_method">
                                <option value="">Все способы</option>
                                <?php foreach ($payment_methods as $method): ?>
                                    <option value="<?php echo $method['payment_method']; ?>" <?php echo ($payment_method == $method['payment_method']) ? 'selected' : ''; ?>>
                                        <?php 
                                        $method_labels = ['cash' => 'Наличные', 'card' => 'Карта', 'online' => 'Онлайн'];
                                        echo $method_labels[$method['payment_method']] ?? $method['payment_method'];
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="master_id">Мастер</label>
                            <select id="master_id" name="master_id">
                                <option value="">Все мастера</option>
                                <?php foreach ($masters as $master): ?>
                                    <option value="<?php echo $master['id']; ?>" <?php echo ($master_id == $master['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($master['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Применить фильтры</button>
                        <a href="receipts.php" class="btn btn-info"><i class="fas fa-redo"></i> Сбросить фильтры</a>
                        <button type="button" onclick="printReceipts()" class="print-btn"><i class="fas fa-print"></i> Печать</button>
                        <button type="button" onclick="exportToExcel()" class="export-btn"><i class="fas fa-file-excel"></i> Экспорт в Excel</button>
                    </div>
                </form>
            </div>

            <!-- статистика -->
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-number"><?php echo $stats['total_count']; ?></div><div class="stat-label">Всего чеков</div></div>
                <div class="stat-card"><div class="stat-number"><?php echo number_format($stats['total_amount'], 0, ',', ' '); ?> ₽</div><div class="stat-label">Общая сумма</div></div>
                <div class="stat-card"><div class="stat-number"><?php echo $stats['paid_count']; ?></div><div class="stat-label">Оплаченных</div></div>
                <div class="stat-card"><div class="stat-number"><?php echo number_format($stats['paid_amount'], 0, ',', ' '); ?> ₽</div><div class="stat-label">Оплаченная сумма</div></div>
                <div class="stat-card"><div class="stat-number"><?php echo number_format($stats['avg_amount'], 0, ',', ' '); ?> ₽</div><div class="stat-label">Средний чек</div></div>
                <div class="stat-card"><div class="stat-number"><?php echo $stats['pending_count']; ?></div><div class="stat-label">Ожидают оплаты</div></div>
            </div>

            <!-- таблица чеков -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID чека</th><th>Дата и время</th><th>Клиент</th><th>Мастер</th><th>Услуга</th><th>Сумма</th><th>Статус</th><th>Способ оплаты</th><th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($receipts)): ?>
                            <tr><td colspan="9" style="text-align: center;">Чеки не найдены</td></tr>
                        <?php else: ?>
                            <?php foreach ($receipts as $receipt): ?>
                            <tr>
                                <td>
                                    <strong>#<?php echo $receipt['id']; ?></strong>
                                    <div class="receipt-details"><small>Запись #<?php echo $receipt['appointment_id']; ?><br><?php echo date('d.m.Y', strtotime($receipt['appointment_date'])); ?> <?php echo date('H:i', strtotime($receipt['start_time'])); ?></small></div>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($receipt['issued_at'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($receipt['client_name']); ?></strong><div style="font-size:12px;color:#666;"><?php echo htmlspecialchars($receipt['client_phone']); ?></div></td>
                                <td><?php echo htmlspecialchars($receipt['master_name']); ?></td>
                                <td><?php echo htmlspecialchars($receipt['service_name']); ?><div style="font-size:12px;color:#666;">Цена: <?php echo number_format($receipt['service_price'], 0, ',', ' '); ?> ₽</div></td>
                                <td>
                                    <div class="amount"><?php echo number_format($receipt['final_amount'], 0, ',', ' '); ?> ₽</div>
                                    <?php if ($receipt['discount'] > 0): ?><div style="font-size:12px;color:#e74c3c;">Скидка: <?php echo number_format($receipt['discount'], 0, ',', ' '); ?> ₽</div><?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $status_badges = ['pending' => 'badge-warning', 'paid' => 'badge-success', 'refunded' => 'badge-danger'];
                                    $status_labels = ['pending' => 'Ожидает', 'paid' => 'Оплачено', 'refunded' => 'Возврат'];
                                    ?>
                                    <span class="badge <?php echo $status_badges[$receipt['payment_status']]; ?>"><?php echo $status_labels[$receipt['payment_status']]; ?></span>
                                    <?php if ($receipt['payment_status'] == 'paid' && $receipt['paid_at']): ?><div style="font-size:12px;color:#666;"><?php echo date('d.m.Y H:i', strtotime($receipt['paid_at'])); ?></div><?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $method_classes = ['cash' => 'payment-cash', 'card' => 'payment-card', 'online' => 'payment-online'];
                                    $method_labels = ['cash' => 'Наличные', 'card' => 'Карта', 'online' => 'Онлайн'];
                                    ?>
                                    <span class="<?php echo $method_classes[$receipt['payment_method']] ?? ''; ?>"><?php echo $method_labels[$receipt['payment_method']] ?? $receipt['payment_method']; ?></span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($receipt['payment_status'] == 'pending'): ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['mark_paid' => $receipt['id']])); ?>" class="btn btn-sm btn-success" onclick="return confirm('Отметить чек #<?php echo $receipt['id']; ?> как оплаченный?')" title="Оплатить"><i class="fas fa-check"></i></a>
                                        <?php endif; ?>
                                        <?php if ($receipt['payment_status'] == 'paid'): ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['mark_cancelled' => $receipt['id']])); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Отметить возврат?')" title="Возврат"><i class="fas fa-undo-alt"></i></a>
                                        <?php endif; ?>
                                        <button onclick="printReceipt(<?php echo $receipt['id']; ?>)" class="btn btn-sm btn-info" title="Печать"><i class="fas fa-print"></i></button>
                                        <button onclick="showReceiptDetails(<?php echo $receipt['id']; ?>)" class="btn btn-sm btn-primary" title="Детали"><i class="fas fa-eye"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- пагинация -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?><a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">&laquo; Назад</a><?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == $page): ?><span class="current"><?php echo $i; ?></span>
                    <?php else: ?><a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a><?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?><a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Вперед &raquo;</a><?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- статистика по методам оплаты -->
            <div style="margin-top:30px; background:white; padding:20px; border-radius:10px;">
                <h3>Статистика по методам оплаты</h3>
                <?php
                    $method_labels = ['cash' => 'Наличные', 'card' => 'Карта', 'online' => 'Онлайн'];
                ?>
                <?php
                $payment_stats_query = "
                    SELECT r.payment_method, COUNT(*) as count, COALESCE(SUM(r.final_amount), 0) as total_amount
                    FROM receipts r
                    JOIN appointments a ON r.appointment_id = a.id
                    WHERE r.issued_at >= ? AND r.issued_at < ?::date + 1 AND r.payment_status = 'paid'
                    GROUP BY r.payment_method
                ";
                $stmt = $db->prepare($payment_stats_query);
                $stmt->execute([$start_date, $end_date]);
                $payment_stats = $stmt->fetchAll();
                ?>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px,1fr)); gap:20px;">
                    <?php foreach ($payment_stats as $stat): ?>
                    <div style="background:#f8f9fa; padding:15px; border-radius:5px;">
                        <div style="display:flex; justify-content:space-between;">
                            <strong><?php echo $method_labels[$stat['payment_method']] ?? $stat['payment_method']; ?></strong>
                            <span class="badge badge-info"><?php echo $stat['count']; ?> чеков</span>
                        </div>
                        <div style="margin-top:10px; font-size:18px; color:#27ae60; font-weight:bold;"><?php echo number_format($stat['total_amount'], 0, ',', ' '); ?> ₽</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- модальное окно -->
    <div id="receiptModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:30px; border-radius:10px; width:90%; max-width:600px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px;"><h2>Детали чека</h2><button onclick="closeReceiptModal()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button></div>
            <div id="receiptDetails"></div>
            <div style="text-align:center; margin-top:20px;"><button onclick="closeReceiptModal()" class="btn btn-primary">Закрыть</button></div>
        </div>
    </div>

    <script>
    function printReceipts() { window.print(); }
    function exportToExcel() { const params = new URLSearchParams(window.location.search); params.set('export', 'excel'); window.location.href = 'export_receipts.php?' + params.toString(); }
    function printReceipt(receiptId) { window.open('print_receipt.php?id=' + receiptId, '_blank').focus(); }
    function showReceiptDetails(receiptId) {
        fetch('get_receipt_details.php?id=' + receiptId)
            .then(response => response.text())
            .then(html => { document.getElementById('receiptDetails').innerHTML = html; document.getElementById('receiptModal').style.display = 'block'; });
    }
    function closeReceiptModal() { document.getElementById('receiptModal').style.display = 'none'; }
    document.getElementById('receiptModal').addEventListener('click', function(e) { if (e.target === this) closeReceiptModal(); });
    </script>
</body>
</html>
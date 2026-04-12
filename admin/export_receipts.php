<?php
// export_receipts.php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAdminAuth();

// Устанавливаем заголовки для скачивания Excel файла
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="receipts_' . date('Y-m-d_H-i') . '.xls"');
header('Cache-Control: max-age=0');

// Получаем параметры фильтров (те же, что и в receipts.php)
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date']   ?? date('Y-m-t');
$payment_status = $_GET['payment_status'] ?? '';
$payment_method = $_GET['payment_method'] ?? '';
$master_id      = $_GET['master_id']      ?? '';
$search         = $_GET['search']         ?? '';

// Формируем условия запроса для PostgreSQL
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
if ($search) {
    $where .= " AND (c.full_name ILIKE ? OR c.phone ILIKE ? OR CAST(r.id AS TEXT) ILIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Получаем все чеки без пагинации
$query = "
    SELECT 
        r.id as receipt_id,
        TO_CHAR(r.issued_at, 'DD.MM.YYYY HH24:MI') as issued_date,
        c.full_name as client_name,
        c.phone as client_phone,
        m.full_name as master_name,
        s.name as service_name,
        s.price as service_price,
        r.discount,
        r.final_amount,
        CASE 
            WHEN r.payment_status = 'pending' THEN 'Ожидает оплаты'
            WHEN r.payment_status = 'paid' THEN 'Оплачено'
            WHEN r.payment_status = 'refunded' THEN 'Возврат'
            ELSE r.payment_status::text
        END as payment_status,
        CASE 
            WHEN r.payment_method = 'cash' THEN 'Наличные'
            WHEN r.payment_method = 'card' THEN 'Карта'
            WHEN r.payment_method = 'online' THEN 'Онлайн'
            ELSE r.payment_method::text
        END as payment_method,
        TO_CHAR(r.paid_at, 'DD.MM.YYYY HH24:MI') as paid_date,
        TO_CHAR(a.appointment_date, 'DD.MM.YYYY') as appointment_date,
        TO_CHAR(a.start_time, 'HH24:MI') as appointment_time,
        a.id as appointment_id
    FROM receipts r
    JOIN appointments a ON r.appointment_id = a.id
    JOIN clients c ON a.client_id = c.id
    JOIN masters m ON a.master_id = m.id
    JOIN services s ON a.service_id = s.id
    $where
    ORDER BY r.issued_at DESC
";

$stmt = $db->prepare($query);
$stmt->execute($params);
$receipts = $stmt->fetchAll();

// Вывод Excel (HTML таблица, совместимая с Excel)
echo '<!DOCTYPE html>';
echo '<html>';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<style>';
echo 'td, th { border: 1px solid #ddd; padding: 8px; text-align: left; }';
echo 'th { background-color: #f2f2f2; }';
echo '</style>';
echo '</head>';
echo '<body>';

echo '<h2>Чеки парикмахерской "Lumiere"</h2>';
echo '<p>Период: ' . date('d.m.Y', strtotime($start_date)) . ' - ' . date('d.m.Y', strtotime($end_date)) . '</p>';
echo '<p>Дата выгрузки: ' . date('d.m.Y H:i') . '</p>';

echo '<table>';
echo '<thead>';
echo '<tr>';
echo '<th>ID чека</th>';
echo '<th>Дата создания</th>';
echo '<th>Клиент</th>';
echo '<th>Телефон</th>';
echo '<th>Мастер</th>';
echo '<th>Услуга</th>';
echo '<th>Цена услуги</th>';
echo '<th>Скидка</th>';
echo '<th>Итоговая сумма</th>';
echo '<th>Статус оплаты</th>';
echo '<th>Способ оплаты</th>';
echo '<th>Дата оплаты</th>';
echo '<th>Дата записи</th>';
echo '<th>Время записи</th>';
echo '<th>ID записи</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

foreach ($receipts as $receipt) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($receipt['receipt_id']) . '</td>';
    echo '<td>' . htmlspecialchars($receipt['issued_date']) . '</td>';
    echo '<td>' . htmlspecialchars($receipt['client_name']) . '</td>';
    echo '<td>' . htmlspecialchars($receipt['client_phone']) . '</td>';
    echo '<td>' . htmlspecialchars($receipt['master_name']) . '</td>';
    echo '<td>' . htmlspecialchars($receipt['service_name']) . '</td>';
    echo '<td>' . number_format($receipt['service_price'], 0, ',', ' ') . ' ₽</td>';
    echo '<td>' . number_format($receipt['discount'], 0, ',', ' ') . ' ₽</td>';
    echo '<td>' . number_format($receipt['final_amount'], 0, ',', ' ') . ' ₽</td>';
    echo '<td>' . htmlspecialchars($receipt['payment_status']) . '</td>';
    echo '<td>' . htmlspecialchars($receipt['payment_method']) . '</td>';
    echo '<td>' . htmlspecialchars($receipt['paid_date']) . '</td>';
    echo '<td>' . htmlspecialchars($receipt['appointment_date']) . '</td>';
    echo '<td>' . htmlspecialchars($receipt['appointment_time']) . '</td>';
    echo '<td>' . htmlspecialchars($receipt['appointment_id']) . '</td>';
    echo '</tr>';
}

// Итоговая строка
$stats_query = "
    SELECT 
        COUNT(*) as total_count,
        COALESCE(SUM(r.final_amount), 0) as total_amount
    FROM receipts r
    JOIN appointments a ON r.appointment_id = a.id
    $where
";
$stmt = $db->prepare($stats_query);
$stmt->execute($params);
$stats = $stmt->fetch();

echo '<tr style="font-weight: bold; background-color: #e8f4f8;">';
echo '<td colspan="8">ИТОГО:</td>';
echo '<td>' . number_format($stats['total_amount'], 0, ',', ' ') . ' ₽</td>';
echo '<td colspan="2">Всего чеков: ' . $stats['total_count'] . '</td>';
echo '<td colspan="4"></td>';
echo '</tr>';

echo '</tbody>';
echo '</table>';

// Статистика по методам оплаты
echo '<br><br><br>';
echo '<h3>Статистика по методам оплаты</h3>';

$payment_stats_query = "
    SELECT 
        CASE 
            WHEN r.payment_method = 'cash' THEN 'Наличные'
            WHEN r.payment_method = 'card' THEN 'Карта'
            WHEN r.payment_method = 'online' THEN 'Онлайн'
            ELSE r.payment_method::text
        END as payment_method,
        COUNT(*) as count,
        COALESCE(SUM(r.final_amount), 0) as total_amount
    FROM receipts r
    JOIN appointments a ON r.appointment_id = a.id
    $where
    AND r.payment_status = 'paid'
    GROUP BY r.payment_method
";

$stmt = $db->prepare($payment_stats_query);
$stmt->execute($params);
$payment_stats = $stmt->fetchAll();

echo '<table>';
echo '<thead>';
echo '<tr>';
echo '<th>Способ оплаты</th>';
echo '<th>Количество чеков</th>';
echo '<th>Общая сумма</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

foreach ($payment_stats as $stat) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($stat['payment_method']) . '</td>';
    echo '<td>' . $stat['count'] . '</td>';
    echo '<td>' . number_format($stat['total_amount'], 0, ',', ' ') . ' ₽</td>';
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';

echo '</body>';
echo '</html>';
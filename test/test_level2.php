<?php
// test_level2.php – Интеграционные тесты уровня 2 (бизнес-логика)
// Запуск: php test_level2.php
// Все изменения откатываются (транзакция с rollback)

ob_start();

function getTestDBConnection(array $params) {
    try {
        $dsn = sprintf("pgsql:host=%s;port=%s;dbname=%s;options='--client_encoding=UTF8'",
            $params['host'], $params['port'], $params['dbname']);
        return new PDO($dsn, $params['user'], $params['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

$testConfig = [
    'host' => 'localhost',
    'port' => '5432',
    'dbname' => 'barbershop_test',
    'user' => 'maxwell25',
    'pass' => 'q1w2e3r4t5y6'
];

$db = getTestDBConnection($testConfig);
if (!$db) die("Не удалось подключиться к тестовой БД.\n");

// Проверяем наличие ключевых таблиц
$requiredTables = ['masters', 'services', 'work_schedule', 'clients', 'admins', 'appointments', 'receipts'];
foreach ($requiredTables as $table) {
    try {
        $db->query("SELECT 1 FROM $table LIMIT 1");
    } catch (PDOException $e) {
        die("Ошибка: таблица '$table' не найдена в тестовой БД. Создайте её через dbbarbershop.sql или вручную.\n");
    }
}

// Все операции внутри транзакции, которая будет отменена
$db->beginTransaction();

require_once 'includes/functions.php';
require_once 'includes/auth.php';

ob_clean();

/**
 * Вставка тестовых данных с получением реальных ID
 */
function insertTestData() {
    global $db;

    // Мастер
    $db->exec("INSERT INTO masters (full_name, phone, specialization, hourly_rate) VALUES ('Тестов Мастер', '79991112233', 'Тест', 1000)");
    $masterId = $db->lastInsertId();

    // Услуга
    $db->exec("INSERT INTO services (name, price, duration_min, category) VALUES ('Тестовая услуга', 500, 30, 'тест')");
    $serviceId = $db->lastInsertId();

    // График мастера
    $db->exec("INSERT INTO work_schedule (master_id, day_of_week, start_time, end_time) VALUES ($masterId, 1, '09:00', '18:00')");

    // Клиент
    $db->exec("INSERT INTO clients (full_name, phone, email, password_hash) VALUES ('Тестов Клиент', '79992223344', 'client@test.ru', '" . password_hash('clientpass', PASSWORD_DEFAULT) . "')");
    $clientId = $db->lastInsertId();

    // Админ (пароль adminpass)
    $db->exec("INSERT INTO admins (full_name, email, password_hash) VALUES ('Админ', 'admin@test.ru', '" . password_hash('adminpass', PASSWORD_DEFAULT) . "')");

    // Запись и чек
    $db->exec("INSERT INTO appointments (client_id, master_id, service_id, appointment_date, start_time, end_time, status) VALUES ($clientId, $masterId, $serviceId, '2026-04-20', '10:00', '10:30', 'scheduled')");
    $appointmentId = $db->lastInsertId();

    $db->exec("INSERT INTO receipts (appointment_id, total_amount, final_amount, payment_method, payment_status) VALUES ($appointmentId, 500, 500, 'cash', 'pending')");

    return [$appointmentId, $clientId, $masterId, $serviceId];
}

list($appointmentId, $clientId, $masterId, $serviceId) = insertTestData();

echo "=== ТЕСТИРОВАНИЕ МОДУЛЯ includes/auth.php ===\n";

// Админ
$_SESSION = [];
$result = adminLogin('admin@test.ru', 'adminpass');
echo "AUTH-01 Успешный вход админа: " . ($result['success'] === true ? "PASSED" : "FAILED") . "\n";
echo "AUTH-02 Сессия активна: " . (isAdminLoggedIn() ? "PASSED" : "FAILED") . "\n";

$_SESSION = [];
$result = adminLogin('admin@test.ru', 'wrongpass');
echo "AUTH-03 Неверный пароль: " . ($result['success'] === false ? "PASSED" : "FAILED") . "\n";

$_SESSION = [];
for ($i = 0; $i < 5; $i++) adminLogin('admin@test.ru', 'wrongpass');
$result = adminLogin('admin@test.ru', 'adminpass');
echo "AUTH-04 Блокировка: " .
    ($result['success'] === false && strpos($result['message'], 'Слишком много попыток') !== false ? "PASSED" : "FAILED") . "\n";

$_SESSION = [];
adminLogin('admin@test.ru', 'adminpass');
adminLogout();
echo "AUTH-05 Выход админа: " . (!isAdminLoggedIn() ? "PASSED" : "FAILED") . "\n";

// Клиент
$_SESSION = [];
$result = clientLogin($db, '79992223344');
echo "AUTH-06 Вход клиента: " . ($result['success'] === true ? "PASSED" : "FAILED") . "\n";
echo "AUTH-07 Сессия клиента: " . (isClientLoggedIn() ? "PASSED" : "FAILED") . "\n";
clientLogout();
echo "AUTH-08 Выход клиента: " . (!isClientLoggedIn() ? "PASSED" : "FAILED") . "\n";

$_SESSION = [];
$newId = quickClientAuth($db, 'Быстрый Клиент', '+79993334455');
echo "AUTH-09 Быстрая регистрация: " . ($newId !== false ? "PASSED" : "FAILED") . "\n";

echo "\n=== ТЕСТИРОВАНИЕ УПРАВЛЕНИЯ ЗАПИСЯМИ ===\n";
$_SESSION = [];
adminLogin('admin@test.ru', 'adminpass');

// Change status
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['change_status' => '1', 'id' => $appointmentId, 'status' => 'completed'];
ob_start();
require 'admin/appointments.php';
ob_end_clean();
$stmt = $db->prepare("SELECT status FROM appointments WHERE id = ?");
$stmt->execute([$appointmentId]);
echo "APPT-01 Change to completed: " . ($stmt->fetchColumn() === 'completed' ? "PASSED" : "FAILED") . "\n";

// Cancel
$_POST = [];
$_GET = ['action' => 'cancel', 'id' => $appointmentId];
ob_start();
require 'admin/appointments.php';
ob_end_clean();
$stmt = $db->prepare("SELECT status FROM appointments WHERE id = ?");
$stmt->execute([$appointmentId]);
echo "APPT-02 Cancel: " . ($stmt->fetchColumn() === 'cancelled' ? "PASSED" : "FAILED") . "\n";

// Reschedule
$_GET = ['action' => 'reschedule', 'id' => $appointmentId];
ob_start();
require 'admin/appointments.php';
ob_end_clean();
$stmt = $db->prepare("SELECT status FROM appointments WHERE id = ?");
$stmt->execute([$appointmentId]);
echo "APPT-03 Reschedule: " . ($stmt->fetchColumn() === 'scheduled' ? "PASSED" : "FAILED") . "\n";

echo "\n=== ТЕСТИРОВАНИЕ УПРАВЛЕНИЯ ЧЕКАМИ ===\n";
$stmt = $db->prepare("SELECT id FROM receipts WHERE appointment_id = ?");
$stmt->execute([$appointmentId]);
$receiptId = $stmt->fetchColumn();

$_GET = ['mark_paid' => $receiptId];
ob_start();
require 'admin/receipts.php';
ob_end_clean();
$stmt = $db->prepare("SELECT payment_status, paid_at FROM receipts WHERE id = ?");
$stmt->execute([$receiptId]);
$r = $stmt->fetch();
echo "RECPT-01 Pay: " . ($r['payment_status'] === 'paid' && $r['paid_at'] ? "PASSED" : "FAILED") . "\n";

$_GET = ['mark_cancelled' => $receiptId];
ob_start();
require 'admin/receipts.php';
ob_end_clean();
$stmt = $db->prepare("SELECT payment_status FROM receipts WHERE id = ?");
$stmt->execute([$receiptId]);
echo "RECPT-02 Refund: " . ($stmt->fetchColumn() === 'refunded' ? "PASSED" : "FAILED") . "\n";

$_GET = [];
$_POST = [];
$_SERVER['REQUEST_METHOD'] = 'GET';
$db->rollBack();

echo "\n=== ТЕСТИРОВАНИЕ ЗАВЕРШЕНО (ДАННЫЕ ВОССТАНОВЛЕНЫ) ===\n";
ob_end_flush();
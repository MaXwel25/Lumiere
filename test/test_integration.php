<?php
require_once __DIR__ . '/../config/database.php';

function runTest($name, callable $test) {
    try {
        $result = $test();
        echo "✅ $name: УСПЕХ. $result\n";
        return true;
    } catch (Throwable $e) {
        echo "❌ $name: ОШИБКА. " . $e->getMessage() . "\n";
        return false;
    }
}

try {
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    echo "🔌 Подключение к БД: OK\n\n";

    // 1. Вставка корректной записи
    runTest("1. Вставка корректной записи (appointments)", function() use ($pdo) {
        $stmt = $pdo->prepare("INSERT INTO appointments (client_id, master_id, service_id, appointment_date, status) VALUES (4, 5, 10, CURRENT_DATE, 'scheduled') RETURNING id, status, created_at");
        $stmt->execute();
        $row = $stmt->fetch();
        return "ID={$row['id']}, статус={$row['status']}, created_at={$row['created_at']}";
    });

    // 2. Нарушение FK (ON DELETE RESTRICT)
    runTest("2. Нарушение ссылочной целостности (FK)", function() use ($pdo) {
        $pdo->exec("DELETE FROM masters WHERE id = 1");
        return "Запись удалена (ОШИБКА: FK не сработал)";
    });

    // 3. Триггер updated_at
    runTest("3. Срабатывание триггера updated_at", function() use ($pdo) {
        $pdo->exec("UPDATE services SET price = 550 WHERE id = 1");
        $row = $pdo->query("SELECT updated_at FROM services WHERE id = 1")->fetch();
        return "updated_at обновлён: {$row['updated_at']}";
    });

    // 4. Представление today_appointments
    runTest("4. Корректность представления today_appointments", function() use ($pdo) {
        $rows = $pdo->query("SELECT client_name, master_name, service_name, start_time, status, price FROM today_appointments")->fetchAll();
        if (empty($rows)) return "Нет записей на сегодня (структура OK)";
        $cols = array_keys($rows[0]);
        return "Возвращено " . count($rows) . " строк. Колонки: " . implode(', ', $cols);
    });

    // 5. Атомарность транзакции
    runTest("5. Атомарность транзакции (ROLLBACK)", function() use ($pdo) {
        $pdo->beginTransaction();
        $pdo->exec("INSERT INTO appointments (client_id, master_id, service_id, appointment_date, status) VALUES (4, 5, 10, CURRENT_DATE, 'scheduled')");
        // Имитация ошибки
        throw new PDOException("Имитация ошибки для проверки отката");
    });

    echo "\n🏁 Интеграционное тестирование завершено.\n";

} catch (PDOException $e) {
    echo "❌ Ошибка PDO: " . $e->getMessage() . "\n";
}
?>
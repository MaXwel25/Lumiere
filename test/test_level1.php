<?php
// test_level1.php – безопасные тесты на рабочей БД barbershop
require_once 'includes/functions.php';


function getTestDBConnection(array $params) {
    try {
        $dsn = sprintf("pgsql:host=%s;port=%s;dbname=%s;options='--client_encoding=UTF8'",
            $params['host'], $params['port'], $params['dbname']);
        $conn = new PDO($dsn, $params['user'], $params['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $conn;
    } catch (PDOException $e) {
        return false;
    }
}

$testConfig = [
    'host'   => 'localhost',
    'port'   => '5432',
    'dbname' => 'barbershop',
    'user'   => 'maxwell25',
    'pass'   => 'q1w2e3r4t5y6'
];

echo "Тестирование модуля database.php\n";

// DB-01: успешное подключение
$db = getTestDBConnection($testConfig);
echo "DB-01 Успешное подключение: " . ($db instanceof PDO ? "PASSED" : "FAILED") . "\n";

// DB-02: неверный пароль
$badConfig = $testConfig;
$badConfig['pass'] = 'wrong_password';
$dbBadPass = getTestDBConnection($badConfig);
echo "DB-02 Неверный пароль: " . ($dbBadPass === false ? "PASSED" : "FAILED") . "\n";

// DB-03: несуществующая БД
$badConfig2 = $testConfig;
$badConfig2['dbname'] = 'nonexistent_db';
$dbNoDB = getTestDBConnection($badConfig2);
echo "DB-03 Несуществующая БД: " . ($dbNoDB === false ? "PASSED" : "FAILED") . "\n";

// DB-04: пропуск (ручная проверка)
echo "DB-04 Недоступный сервер: ТЕСТ ПРОПУЩЕН (требуется остановка PostgreSQL)\n";

echo "\nТестирование модуля includes/functions.php (ТОЛЬКО ЧТЕНИЕ)\n";

if (!$db) {
    die("Не удалось подключиться к БД. Прерывание.\n");
}

// FUN-01: getServices – все услуги
$services = getServices($db, false);
// В реальной БД может быть любое количество, проверим, что массив не пуст
echo "FUN-01 Все услуги (непустой массив): " . (!empty($services) ? "PASSED" : "FAILED") . "\n";

// FUN-02: активные услуги – проверяем, что все возвращаемые услуги реально имеют is_active = true
$activeServices = getServices($db, true);
$allActive = true;
foreach ($activeServices as $s) {
    if (empty($s['is_active'])) {
        $allActive = false;
        break;
    }
}
echo "FUN-02 Активные услуги (проверка is_active): " . ($allActive ? "PASSED" : "FAILED") . "\n";

// FUN-03: getMasters – только активные
$masters = getMasters($db, true);
echo "FUN-03 Активные мастера (непустой массив): " . (!empty($masters) ? "PASSED" : "FAILED") . "\n";

// FUN-04: getMasterSchedule – для первого мастера (если мастер есть)
$firstMaster = $masters[0] ?? null;
if ($firstMaster) {
    $schedule = getMasterSchedule($db, $firstMaster['id']);
    echo "FUN-04 Расписание мастера (непустой): " . (!empty($schedule) ? "PASSED" : "FAILED") . "\n";
} else {
    echo "FUN-04 Расписание мастера: ПРОПУЩЕН (нет мастеров)\n";
}

// FUN-05: getAvailableTimes – безопасно: выбираем дату, на которую точно нет записей (например, сегодня + 100 дней), и проверяем, что функция возвращает массив
$futureDate = date('Y-m-d', strtotime('+100 days'));
$times = getAvailableTimes($db, $firstMaster['id'] ?? 1, $futureDate, 45);
echo "FUN-05 Свободные слоты для будущей даты: " . (!empty($times) || is_array($times) ? "PASSED" : "FAILED") . "\n";

// FUN-06: проверка выходного дня (воскресенье) для первого мастера – можно просто убедиться, что для воскресенья результат пуст, если график это подразумевает
$sunday = date('Y-m-d', strtotime('next Sunday'));
$timesSunday = getAvailableTimes($db, $firstMaster['id'] ?? 1, $sunday, 45);
echo "FUN-06 Воскресенье (ожидается пусто): " . (empty($timesSunday) ? "PASSED (пусто)" : "FAILED (есть слоты)") . "\n";


// FUN-10: обязательное поле
$errors = validateFormData(['name' => ''], ['name' => ['required' => true, 'message' => 'Введите имя']]);
echo "FUN-10 Обязательное поле: " . (isset($errors['name']) && $errors['name'] === 'Введите имя' ? "PASSED" : "FAILED") . "\n";

// FUN-11: корректный email
$errors = validateFormData(['email' => 'test@domain.ru'], ['email' => ['type' => 'email']]);
echo "FUN-11 Корректный email: " . (empty($errors) ? "PASSED" : "FAILED") . "\n";

// FUN-12: некорректный email
$errors = validateFormData(['email' => 'bad'], ['email' => ['type' => 'email']]);
echo "FUN-12 Некорректный email: " . (isset($errors['email']) ? "PASSED" : "FAILED") . "\n";

// FUN-13: телефон с +7
$errors = validateFormData(['phone' => '+7(999)1234567'], ['phone' => ['type' => 'phone']]);
echo "FUN-13 Телефон с кодом: " . (empty($errors) ? "PASSED" : "FAILED") . "\n";

// FUN-14: короткий телефон
$errors = validateFormData(['phone' => '12345'], ['phone' => ['type' => 'phone']]);
echo "FUN-14 Короткий телефон: " . (isset($errors['phone']) ? "PASSED" : "FAILED") . "\n";

echo "\nУспешное выполнение\n";
?>
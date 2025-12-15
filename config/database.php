<?php
// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'barbershop');
define('DB_USER', 'maxwell25');
define('DB_PASS', 'q1w2e3r4t5y6');

// Создаем подключение
function getDBConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $conn;
    } catch(PDOException $e) {
        die("Ошибка подключения к БД: " . $e->getMessage());
    }
}

// Глобальная переменная для подключения
$db = getDBConnection();
?>
<?php
// database.php
// настройки базы данных PostgreSQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'barbershop');
define('DB_USER', 'maxwell25');
define('DB_PASS', 'q1w2e3r4t5y6');
define('DB_PORT', '5432'); // стандартный порт Psql

// создаем подключение
function getDBConnection() {
    try {
        // DSN для PostgreSQL
        $dsn = sprintf(
            "pgsql:host=%s;port=%s;dbname=%s;options='--client_encoding=UTF8'",
            DB_HOST,
            DB_PORT,
            DB_NAME
        );
        
        $conn = new PDO(
            $dsn,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // отключаем эмуляцию для безопасности
            ]
        );
        return $conn;
    } catch(PDOException $e) {
        die("Ошибка подключения к БД PostgreSQL: " . $e->getMessage());
    }
}

// глобальная переменная для подключения
$db = getDBConnection();
?>
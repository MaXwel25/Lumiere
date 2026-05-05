<?php
require_once 'config/database.php';

if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'barbershop');
    define('DB_USER', 'maxwell25');
    define('DB_PASS', 'q1w2e3r4t5y6');
    define('DB_PORT', '5432');
}

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, 
    PDO::ATTR_EMULATE_PREPARES => false,
];

$dsn = sprintf(
    "pgsql:host=%s;port=%s;dbname=%s;options='--client_encoding=UTF8'",
    DB_HOST,
    DB_PORT,
    DB_NAME
);

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    $stmt = $pdo->prepare("INSERT INTO appointments 
        (client_id, master_id, service_id, appointment_date, status) 
        VALUES (:cid, :mid, :sid, :date, :status)");
    
    $stmt->execute([
        ':cid' => 4,
        ':mid' => 6,
        ':sid' => 6,
        ':date' => '2026-05-10',
        ':status' => 'scheduled'
    ]);
    
    $lastId = $pdo->lastInsertId('appointments_id_seq');
    echo "Запись создана с ID: $lastId\n";
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo "Ошибка базы данных. Обратитесь к администратору.";
}
?>
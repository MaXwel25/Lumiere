<?php
// конфигурация админ-панели
session_start();

$admin_password = 'admin123';

// Функция проверки авторизации
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Функция для защиты страниц
function requireAdminAuth() {
    if (!isAdminLoggedIn()) {
        header('Location: /admin/login.php');
        exit();
    }
}
?>
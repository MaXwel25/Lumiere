<?php
// Конфигурация админ-панели
session_start();

// Пароль администратора (в реальном проекте нужно хешировать!)
$admin_password = 'admin123'; // Измените на свой пароль

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
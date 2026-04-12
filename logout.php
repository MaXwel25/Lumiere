<?php
// logout.php
session_start();
require_once 'includes/auth.php';

// Выход клиента
clientLogout();

// Перенаправление на главную страницу
header('Location: index.php');
exit;
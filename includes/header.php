<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
session_start();

$inProfileFolder = strpos($_SERVER['SCRIPT_NAME'], '/profile/') !== false;
$baseUrl = $inProfileFolder ? '../' : '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Парикмахерская "Lumiere" - Профессиональные услуги</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- верхняя панель контактов -->
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-content">
                <div class="contact-info">
                    <span><i class="fas fa-phone"></i> +7 (861) 123-45-67</span>
                    <span><i class="fas fa-clock"></i> Пн-Пт: 9:00-19:00, Сб-Вс: 10:00-18:00</span>
                </div>
                <div class="social-links">
                    <a href="#" aria-label="ВКонтакте"><i class="fab fa-vk"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="Telegram"><i class="fab fa-telegram"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- основная навигация -->
    <nav class="main-nav">
        <div class="container">
            <div class="nav-content">
                <a href="<?= $baseUrl ?>index.php" class="logo">
                    <div class="logo-icon"><i class="fas fa-cut"></i></div>
                    <div class="logo-text">
                        <span class="logo-title">Lumiere</span>
                        <span class="logo-subtitle">Парикмахерская премиум-класса</span>
                    </div>
                </a>

                <ul class="nav-menu">
                    <li><a href="<?= $baseUrl ?>index.php" <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : '' ?>><i class="fas fa-home"></i> Главная</a></li>
                    <li><a href="<?= $baseUrl ?>services.php"><i class="fas fa-scissors"></i> Услуги</a></li>
                    <li><a href="<?= $baseUrl ?>masters.php"><i class="fas fa-user-tie"></i> Мастера</a></li>
                    <li><a href="<?= $baseUrl ?>booking.php"><i class="fas fa-calendar-alt"></i> Запись</a></li>
                    <li><a href="<?= $baseUrl ?>contacts.php"><i class="fas fa-map-marker-alt"></i> Контакты</a></li>
                </ul>

                <div class="nav-actions">
                    <?php if (isClientLoggedIn()): ?>
                        <a href="<?= $baseUrl ?>profile/profile.php" class="btn btn-profile"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['client_name']) ?></a>
                        <a href="<?= $baseUrl ?>logout.php?type=client" class="btn btn-logout"><i class="fas fa-sign-out-alt"></i></a>
                    <?php else: ?>
                        <a href="<?= $baseUrl ?>profile/login.php" class="btn btn-login"><i class="fas fa-sign-in-alt"></i> Войти</a>
                    <?php endif; ?>
                </div>

                <button class="mobile-menu-toggle" id="mobileMenuToggle"><i class="fas fa-bars"></i></button>
            </div>
        </div>
    </nav>

    <div class="mobile-nav" id="mobileNav">
        <div class="mobile-nav-menu">
            <a href="<?= $baseUrl ?>index.php">Главная</a>
            <a href="<?= $baseUrl ?>services.php">Услуги</a>
            <a href="<?= $baseUrl ?>masters.php">Мастера</a>
            <a href="<?= $baseUrl ?>booking.php">Запись</a>
            <a href="<?= $baseUrl ?>contacts.php">Контакты</a>
            <?php if (isClientLoggedIn()): ?>
                <a href="<?= $baseUrl ?>profile/profile.php">Профиль</a>
                <a href="<?= $baseUrl ?>logout.php?type=client">Выйти</a>
            <?php else: ?>
                <a href="<?= $baseUrl ?>profile/login.php">Войти</a>
            <?php endif; ?>
        </div>
    </div>
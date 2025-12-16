<?php
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Парикмахерская "Lumiere" - Профессиональные услуги</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Верхняя панель контактов -->
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

    <!-- Основная навигация -->
    <nav class="main-nav">
        <div class="container">
            <div class="nav-content">
                <!-- Логотип -->
                <a href="index.php" class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-cut"></i>
                    </div>
                    <div class="logo-text">
                        <span class="logo-title">Lumiere</span>
                        <span class="logo-subtitle">Парикмахерская премиум-класса</span>
                    </div>
                </a>

                <!-- Меню навигации -->
                <ul class="nav-menu">
                    <li><a href="index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-home"></i> Главная
                    </a></li>
                    <li><a href="services.php" <?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-scissors"></i> Услуги
                    </a></li>
                    <li><a href="masters.php" <?php echo basename($_SERVER['PHP_SELF']) == 'masters.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-user-tie"></i> Мастера
                    </a></li>
                    <li><a href="booking.php" <?php echo basename($_SERVER['PHP_SELF']) == 'booking.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-calendar-alt"></i> Запись
                    </a></li>
                    <li><a href="contacts.php" <?php echo basename($_SERVER['PHP_SELF']) == 'contacts.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-map-marker-alt"></i> Контакты
                    </a></li>
                </ul>

                <!-- Кнопка для админки -->
                <div class="nav-actions">
                    <a href="http://localhost/admin/login.php" class="btn btn-admin">
                        <i class="fas fa-lock"></i> Админка
                    </a>
                </div>

                <!-- Кнопка мобильного меню -->
                <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Меню">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Мобильное меню -->
    <div class="mobile-nav" id="mobileNav">
        <div class="mobile-nav-menu">
            <a href="index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-home"></i> Главная
            </a>
            <a href="services.php" <?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-scissors"></i> Услуги
            </a>
            <a href="masters.php" <?php echo basename($_SERVER['PHP_SELF']) == 'masters.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-user-tie"></i> Мастера
            </a>
            <a href="booking.php" <?php echo basename($_SERVER['PHP_SELF']) == 'booking.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-calendar-alt"></i> Запись
            </a>
            <a href="contacts.php" <?php echo basename($_SERVER['PHP_SELF']) == 'contacts.php' ? 'class="active"' : ''; ?>>
                <i class="fas fa-map-marker-alt"></i> Контакты
            </a>
        </div>
        <div class="mobile-nav-actions">
            <a href="http://localhost/admin/login.php" class="btn btn-admin">
                <i class="fas fa-lock"></i> Админка
            </a>
        </div>
    </div>
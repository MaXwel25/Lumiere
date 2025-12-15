<?php
// header.php - Шапка сайта для публичной части
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Парикмахерская "Стиль" - Профессиональные услуги</title>
    
    <!-- Основные стили -->
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <!-- Иконки Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Шрифты Google -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    
    <style>
        /* Дополнительные стили для шапки */
        :root {
            --primary-color: #d4af37;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --text-color: #333;
            --light-gray: #f5f5f5;
            --dark-gray: #666;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
    </style>
</head>
<body>
    <!-- Верхняя панель -->
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-content">
                <div class="contact-info">
                    <span><i class="fas fa-phone"></i> +7 (861) 123-45-67</span>
                    <span><i class="fas fa-clock"></i> Пн-Пт: 9:00-19:00, Сб-Вс: 10:00-18:00</span>
                </div>
                <div class="social-links">
                    <a href="#"><i class="fab fa-vk"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-telegram"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Основная навигация -->
    <nav class="main-nav">
        <div class="container">
            <div class="nav-content">
                <!-- Логотип -->
                <div class="logo">
                    <a href="/index.php">
                        <div class="logo-icon">
                            <i class="fas fa-cut"></i>
                        </div>
                        <div class="logo-text">
                            <span class="logo-title">Парикмахерская "Стиль"</span>
                            <span class="logo-subtitle">Профессиональные услуги с 2010 года</span>
                        </div>
                    </a>
                </div>

                <!-- Меню навигации -->
                <ul class="nav-menu">
                    <li><a href="/index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i> Главная
                    </a></li>
                    <li><a href="/services.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : ''; ?>">
                        <i class="fas fa-concierge-bell"></i> Услуги
                    </a></li>
                    <li><a href="/masters.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'masters.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-tie"></i> Мастера
                    </a></li>
                    <li><a href="/booking.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'booking.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i> Онлайн запись
                    </a></li>
                    <li><a href="/contacts.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'contacts.php' ? 'active' : ''; ?>">
                        <i class="fas fa-map-marker-alt"></i> Контакты
                    </a></li>
                </ul>

                <!-- Кнопка записи -->
                <div class="nav-actions">
                    <a href="/booking.php" class="btn btn-primary">
                        <i class="fas fa-calendar-plus"></i> Записаться онлайн
                    </a>
                    <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                        <a href="/admin/dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-user-cog"></i> Админ-панель
                        </a>
                    <?php else: ?>
                        <a href="/admin/login.php" class="btn btn-secondary">
                            <i class="fas fa-lock"></i> Вход для администратора
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Мобильное меню -->
                <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Мобильное меню (скрыто по умолчанию) -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-header">
            <div class="logo">
                <i class="fas fa-cut"></i>
                <span>Парикмахерская "Стиль"</span>
            </div>
            <button class="close-menu" onclick="toggleMobileMenu()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <ul class="mobile-nav-menu">
            <li><a href="/index.php" onclick="toggleMobileMenu()">
                <i class="fas fa-home"></i> Главная
            </a></li>
            <li><a href="/services.php" onclick="toggleMobileMenu()">
                <i class="fas fa-concierge-bell"></i> Услуги
            </a></li>
            <li><a href="/masters.php" onclick="toggleMobileMenu()">
                <i class="fas fa-user-tie"></i> Мастера
            </a></li>
            <li><a href="/booking.php" onclick="toggleMobileMenu()">
                <i class="fas fa-calendar-alt"></i> Онлайн запись
            </a></li>
            <li><a href="/contacts.php" onclick="toggleMobileMenu()">
                <i class="fas fa-map-marker-alt"></i> Контакты
            </a></li>
            <li class="divider"></li>
            <li><a href="/admin/login.php" onclick="toggleMobileMenu()">
                <i class="fas fa-lock"></i> Вход для администратора
            </a></li>
        </ul>
        <div class="mobile-contact-info">
            <p><i class="fas fa-phone"></i> +7 (861) 123-45-67</p>
            <p><i class="fas fa-clock"></i> Пн-Пт: 9:00-19:00</p>
            <p><i class="fas fa-map-marker-alt"></i> г. Краснодар, ул. Красная, 100</p>
        </div>
    </div>

    <!-- Основной контент -->
    <main>
        <?php if (isset($page_title)): ?>
        <section class="page-header">
            <div class="container">
                <h1><?php echo $page_title; ?></h1>
                <?php if (isset($page_subtitle)): ?>
                    <p class="page-subtitle"><?php echo $page_subtitle; ?></p>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
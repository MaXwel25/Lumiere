<?php
// Старт сессии в самом начале
session_start();

// Подключаем конфигурацию БД
require_once 'config/database.php';

// Подключаем функции аутентификации и утилиты
require_once 'includes/auth.php';
require_once 'includes/functions.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Элитная парикмахерская "Стиль"</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
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
        
        /* Герой-секция */
        .hero {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                        url('https://images.unsplash.com/photo-1560066984-138dadb4c035?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 120px 20px;
            text-align: center;
        }
        
        .hero-content h1 {
            font-size: 3rem;
            margin-bottom: 20px;
            font-family: 'Playfair Display', serif;
        }
        
        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #b8941f;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #1a252f;
        }
        
        /* Общие стили секций */
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 50px;
            color: var(--secondary-color);
            font-family: 'Playfair Display', serif;
        }
        
        .text-center {
            text-align: center;
            margin-top: 40px;
        }
        
        /* Стили для услуг */
        .services-section {
            padding: 80px 0;
            background-color: var(--light-gray);
        }
        
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }
        
        .service-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .service-card:hover {
            transform: translateY(-10px);
        }
        
        .service-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .service-card h3 {
            margin-bottom: 15px;
            color: var(--secondary-color);
        }
        
        .service-price {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-weight: bold;
        }
        
        /* Стили для мастеров */
        .masters-section {
            padding: 80px 0;
        }
        
        .masters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .master-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            padding: 20px;
        }
        
        .master-photo img {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 5px solid var(--primary-color);
        }
        
        .master-card h3 {
            margin-bottom: 10px;
            color: var(--secondary-color);
        }
        
        .master-specialization {
            color: var(--primary-color);
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .master-experience {
            color: var(--dark-gray);
            margin-bottom: 15px;
        }
        
        /* Стили для расписания */
        .schedule-section {
            padding: 80px 0;
            background-color: var(--secondary-color);
            color: white;
        }
        
        .schedule {
            max-width: 500px;
            margin: 0 auto;
            background: rgba(255,255,255,0.1);
            padding: 30px;
            border-radius: 10px;
        }
        
        .schedule-item {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .schedule-item:last-child {
            border-bottom: none;
        }
        
        /* Стили для контактов */
        .contacts-section {
            padding: 80px 0;
            background-color: var(--light-gray);
        }
        
        .contacts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 50px;
        }
        
        .contact-info p {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .contact-info i {
            color: var(--primary-color);
            font-size: 1.2rem;
            width: 30px;
        }
        
        .contact-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .contact-form h3 {
            margin-bottom: 20px;
            color: var(--secondary-color);
        }
        
        .contact-form input,
        .contact-form textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
        }
        
        .contact-form textarea {
            resize: vertical;
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .services-grid,
            .masters-grid {
                grid-template-columns: 1fr;
            }
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
                    <a href="index.php">
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
                    <li><a href="index.php" class="active">
                        <i class="fas fa-home"></i> Главная
                    </a></li>
                    <li><a href="services.php">
                        <i class="fas fa-concierge-bell"></i> Услуги
                    </a></li>
                    <li><a href="masters.php">
                        <i class="fas fa-user-tie"></i> Мастера
                    </a></li>
                    <li><a href="booking.php">
                        <i class="fas fa-calendar-alt"></i> Онлайн запись
                    </a></li>
                    <li><a href="contacts.php">
                        <i class="fas fa-map-marker-alt"></i> Контакты
                    </a></li>
                </ul>

                <!-- Кнопка записи -->
                <div class="nav-actions">
                    <a href="booking.php" class="btn btn-primary">
                        <i class="fas fa-calendar-plus"></i> Записаться онлайн
                    </a>
                    <?php if (isAdminLoggedIn()): ?>
                        <a href="admin/dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-user-cog"></i> Админ-панель
                        </a>
                    <?php else: ?>
                        <a href="admin/login.php" class="btn btn-secondary">
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

    <!-- Герой-секция -->
    <section class="hero">
        <div class="hero-content">
            <h1>Профессиональные стрижки и укладки</h1>
            <p>Создаем ваш идеальный образ с 2010 года</p>
            <a href="booking.php" class="btn btn-primary">Записаться онлайн</a>
        </div>
    </section>

    <!-- Услуги -->
    <section class="services-section">
        <div class="container">
            <h2 class="section-title">Наши услуги</h2>
            <div class="services-grid">
                <?php
                $stmt = $db->query("SELECT * FROM services WHERE is_active = TRUE LIMIT 4");
                $services = $stmt->fetchAll();
                
                foreach ($services as $service) {
                ?>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-cut"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                    <p><?php echo htmlspecialchars($service['description']); ?></p>
                    <div class="service-price">
                        <span><?php echo number_format($service['price'], 0, ',', ' '); ?> ₽</span>
                        <span><?php echo $service['duration_min']; ?> мин</span>
                    </div>
                </div>
                <?php } ?>
            </div>
            <div class="text-center">
                <a href="services.php" class="btn btn-secondary">Все услуги</a>
            </div>
        </div>
    </section>

    <!-- Мастера -->
    <section class="masters-section">
        <div class="container">
            <h2 class="section-title">Наши мастера</h2>
            <div class="masters-grid">
                <?php
                $stmt = $db->query("SELECT * FROM masters WHERE is_active = TRUE LIMIT 3");
                $masters = $stmt->fetchAll();
                
                foreach ($masters as $master) {
                ?>
                <div class="master-card">
                    <div class="master-photo">
                        <img src="https://via.placeholder.com/300x300" alt="<?php echo htmlspecialchars($master['full_name']); ?>">
                    </div>
                    <h3><?php echo htmlspecialchars($master['full_name']); ?></h3>
                    <p class="master-specialization"><?php echo htmlspecialchars($master['specialization']); ?></p>
                    <p class="master-experience">Опыт: 5+ лет</p>
                    <div class="master-contacts">
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($master['phone']); ?></p>
                    </div>
                </div>
                <?php } ?>
            </div>
            <div class="text-center">
                <a href="masters.php" class="btn btn-secondary">Все мастера</a>
            </div>
        </div>
    </section>

    <!-- Расписание -->
    <section class="schedule-section">
        <div class="container">
            <h2 class="section-title">Часы работы</h2>
            <div class="schedule">
                <div class="schedule-item">
                    <span>Пн-Пт</span>
                    <span>9:00 - 19:00</span>
                </div>
                <div class="schedule-item">
                    <span>Суббота</span>
                    <span>10:00 - 18:00</span>
                </div>
                <div class="schedule-item">
                    <span>Воскресенье</span>
                    <span>10:00 - 16:00</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Контакты -->
    <section class="contacts-section">
        <div class="container">
            <h2 class="section-title">Контакты</h2>
            <div class="contacts-grid">
                <div class="contact-info">
                    <p><i class="fas fa-map-marker-alt"></i> г. Краснодар, ул. Красная, 100</p>
                    <p><i class="fas fa-phone"></i> +7 (861) 123-45-67</p>
                    <p><i class="fas fa-envelope"></i> info@barbershop-style.ru</p>
                </div>
                <div class="contact-form">
                    <h3>Остались вопросы?</h3>
                    <form action="send_message.php" method="POST">
                        <input type="text" name="name" placeholder="Ваше имя" required>
                        <input type="tel" name="phone" placeholder="Телефон" required>
                        <textarea name="message" placeholder="Ваш вопрос" rows="3"></textarea>
                        <button type="submit" class="btn btn-primary">Отправить</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Футер -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <!-- Информация о компании -->
                <div class="footer-section">
                    <div class="footer-logo">
                        <i class="fas fa-cut"></i>
                        <h3>Парикмахерская "Стиль"</h3>
                    </div>
                    <p>Профессиональные парикмахерские услуги с 2010 года. 
                       Мы создаем стиль и уверенность в каждом клиенте.</p>
                    <div class="footer-contact">
                        <p><i class="fas fa-phone"></i> +7 (861) 123-45-67</p>
                        <p><i class="fas fa-envelope"></i> info@barbershop-style.ru</p>
                        <p><i class="fas fa-map-marker-alt"></i> г. Краснодар, ул. Красная, 100</p>
                    </div>
                </div>

                <!-- Быстрые ссылки -->
                <div class="footer-section">
                    <h4>Быстрые ссылки</h4>
                    <ul class="footer-links">
                        <li><a href="index.php"><i class="fas fa-chevron-right"></i> Главная</a></li>
                        <li><a href="services.php"><i class="fas fa-chevron-right"></i> Наши услуги</a></li>
                        <li><a href="masters.php"><i class="fas fa-chevron-right"></i> Наши мастера</a></li>
                        <li><a href="booking.php"><i class="fas fa-chevron-right"></i> Онлайн запись</a></li>
                        <li><a href="contacts.php"><i class="fas fa-chevron-right"></i> Контакты</a></li>
                    </ul>
                </div>

                <!-- Услуги -->
                <div class="footer-section">
                    <h4>Наши услуги</h4>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Мужские стрижки</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Женские стрижки</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Окрашивание волос</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Уход за волосами</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Стрижка бороды</a></li>
                    </ul>
                </div>

                <!-- Часы работы -->
                <div class="footer-section">
                    <h4>Часы работы</h4>
                    <div class="working-hours">
                        <div class="hours-item">
                            <span>Понедельник - Пятница</span>
                            <span>9:00 - 19:00</span>
                        </div>
                        <div class="hours-item">
                            <span>Суббота</span>
                            <span>10:00 - 18:00</span>
                        </div>
                        <div class="hours-item">
                            <span>Воскресенье</span>
                            <span>10:00 - 16:00</span>
                        </div>
                    </div>
                    <div class="social-media">
                        <a href="#" class="social-icon"><i class="fab fa-vk"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-telegram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>

            <!-- Копирайт -->
            <div class="footer-bottom">
                <div class="copyright">
                    <p>&copy; 2025 Парикмахерская "Стиль". Все права защищены.</p>
                    <p>Разработано специально для автоматизации парикмахерской</p>
                </div>
                <div class="footer-links-bottom">
                    <a href="privacy.php">Политика конфиденциальности</a>
                    <a href="terms.php">Условия использования</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Функция для переключения мобильного меню
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobileMenu');
            if (mobileMenu) {
                mobileMenu.classList.toggle('active');
                document.body.classList.toggle('menu-open');
            }
        }

        // Закрыть мобильное меню при клике на ссылку
        document.querySelectorAll('.mobile-nav-menu a').forEach(link => {
            link.addEventListener('click', () => {
                toggleMobileMenu();
            });
        });
    </script>
</body>
</html>
<?php
require_once 'config/database.php';
require_once 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Элитная парикмахерская "Стиль"</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Навигация -->
    <?php include 'includes/header.php'; ?>

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

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/script.js"></script>
</body>
</html>
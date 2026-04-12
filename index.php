<?php
// index.php
require_once 'includes/header.php'; // здесь должно быть подключение к PostgreSQL через PDO ($db)

// получаем 4 популярные услуги для главной страницы
try {
    $stmt = $db->query("SELECT * FROM services WHERE is_active = TRUE ORDER BY price DESC LIMIT 4");
    $services = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Ошибка загрузки услуг: " . $e->getMessage());
}

// получаем 3 лучших мастера
try {
    $stmt = $db->query("SELECT * FROM masters WHERE is_active = TRUE LIMIT 3");
    $masters = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Ошибка загрузки мастеров: " . $e->getMessage());
}
?>
<!-- герой-секция -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1>Lumiere - Ваш идеальный образ</h1>
            <p>Профессиональная парикмахерская премиум-класса. Наши мастера создадут для вас идеальный образ с использованием только профессиональных средств.</p>
            <a href="booking.php" class="btn btn-primary">Записаться онлайн</a>
        </div>
    </div>
</section>

<!-- услуги -->
<section class="section services-section">
    <div class="container">
        <h2 class="section-title">Популярные услуги</h2>
        <div class="services-grid">
            <?php foreach ($services as $service): ?>
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-<?php echo ($service['category'] == 'стрижки') ? 'cut' : (($service['category'] == 'окрашивание') ? 'palette' : 'beard'); ?>"></i>
                </div>
                <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                <p>
                    <?php 
                    // Безопасная обработка возможно NULL-значения description
                    $desc = $service['description'] ?? '';
                    $short_desc = mb_substr($desc, 0, 100);
                    echo htmlspecialchars($short_desc);
                    if (mb_strlen($desc) > 100) echo '...';
                    ?>
                </p>
                <div class="service-price">
                    <span><?php echo number_format($service['price'], 0, ',', ' '); ?> ₽</span>
                    <span><i class="fas fa-clock"></i> <?php echo $service['duration_min']; ?> мин</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center">
            <a href="services.php" class="btn btn-secondary">Все услуги</a>
        </div>
    </div>
</section>

<!-- мастера -->
<section class="section masters-section">
    <div class="container">
        <h2 class="section-title">Наши мастера</h2>
        <div class="masters-grid">
            <?php foreach ($masters as $master): ?>
            <div class="master-card">
                <div class="master-photo">
                    <img src="photo/<?php echo htmlspecialchars($master['id']); ?>.jpg" 
                         alt="<?php echo htmlspecialchars($master['full_name']); ?>" 
                         loading="lazy"
                         onerror="this.onerror=null; this.src='https://via.placeholder.com/300x300?text=No+Photo&amp;bg=<?php echo urlencode('#2c3e50'); ?>&amp;color=<?php echo urlencode('#d4af37'); ?>';">
                </div>
                <div class="master-info">
                    <h3><?php echo htmlspecialchars($master['full_name']); ?></h3>
                    <span class="master-specialization"><?php echo htmlspecialchars($master['specialization']); ?></span>
                    <p class="master-experience">Профессиональный опыт более 8 лет</p>
                    <div class="master-contacts">
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($master['phone']); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center">
            <a href="masters.php" class="btn btn-secondary">Все мастера</a>
        </div>
    </div>
</section>

<!-- почему выбирают нас -->
<section class="section" style="background-color: var(--light-gray);">
    <div class="container">
        <h2 class="section-title">Почему выбирают нас</h2>
        <div class="services-grid">
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-medal"></i>
                </div>
                <h3>Опытные мастера</h3>
                <p>Наши специалисты регулярно проходят обучение и следят за последними тенденциями в индустрии красоты.</p>
            </div>
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h3>Индивидуальный подход</h3>
                <p>Мы внимательно выслушиваем каждого клиента и подбираем подходящий образ с учетом ваших пожеланий.</p>
            </div>
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-leaf"></i>
                </div>
                <h3>Экологичные средства</h3>
                <p>Используем только профессиональную косметику премиум-класса, безопасную для волос и кожи.</p>
            </div>
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-coffee"></i>
                </div>
                <h3>Комфортная атмосфера</h3>
                <p>Уютное пространство с удобными креслами, ароматным кофе и приятной музыкой для вашего отдыха.</p>
            </div>
        </div>
    </div>
</section>

<!-- связь с админкой -->
<section class="section" style="background-color: #2c3e50; color: white; text-align: center;">
    <div class="container">
        <h2 class="section-title" style="color: white;">Для сотрудников</h2>
        <p style="font-size: 1.2rem; margin-bottom: 30px;">Доступ к административной панели для управления записями, мастерами и услугами</p>
        <a href="http://localhost/admin/login.php" class="btn btn-admin" style="background-color: #d4af37; color: #2c3e50; font-size: 1.2rem; padding: 15px 40px;">
            <i class="fas fa-user-shield"></i> Войти в админ-панель
        </a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
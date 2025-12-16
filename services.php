<?php
require_once 'includes/header.php';

// Получаем все активные услуги
$stmt = $db->query("SELECT * FROM services WHERE is_active = TRUE ORDER BY category, price");
$services = $stmt->fetchAll();

// Группируем услуги по категориям
$servicesByCategory = [];
foreach ($services as $service) {
    $category = $service['category'] ?: 'Другие услуги';
    $servicesByCategory[$category][] = $service;
}
?>
<!-- Герой-секция -->
<section class="hero" style="background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1541938832687-46029622c3d9?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80'); background-size: cover; background-position: center;">
    <div class="container">
        <div class="hero-content">
            <h1>Наши услуги</h1>
            <p>Профессиональные услуги от мастеров с многолетним опытом работы. Мы используем только профессиональную косметику премиум-класса.</p>
        </div>
    </div>
</section>

<!-- Услуги по категориям -->
<section class="section services-section">
    <div class="container">
        <?php foreach ($servicesByCategory as $category => $categoryServices): ?>
        <div class="section">
            <h2 class="section-title"><?php echo htmlspecialchars($category); ?></h2>
            <div class="services-grid">
                <?php foreach ($categoryServices as $service): ?>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-<?php echo ($category == 'стрижки') ? 'cut' : (($category == 'окрашивание') ? 'palette' : 'beard'); ?>"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                    <p><?php echo htmlspecialchars($service['description']); ?></p>
                    <div class="service-price">
                        <span><?php echo number_format($service['price'], 0, ',', ' '); ?> ₽</span>
                        <span><i class="fas fa-clock"></i> <?php echo $service['duration_min']; ?> мин</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Преимущества -->
<section class="section" style="background-color: var(--secondary-color); color: white;">
    <div class="container">
        <h2 class="section-title" style="color: white;">Преимущества наших услуг</h2>
        <div class="services-grid">
            <div class="service-card" style="background-color: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);">
                <div class="service-icon" style="color: white;">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Гарантия качества</h3>
                <p>Мы гарантируем качество наших услуг и предоставляем бесплатную корректировку в течение 7 дней.</p>
            </div>
            <div class="service-card" style="background-color: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);">
                <div class="service-icon" style="color: white;">
                    <i class="fas fa-gift"></i>
                </div>
                <h3>Подарочные сертификаты</h3>
                <p>Подарите близким возможность получить профессиональные услуги в нашей парикмахерской.</p>
            </div>
            <div class="service-card" style="background-color: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);">
                <div class="service-icon" style="color: white;">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3>Напоминания о записи</h3>
                <p>Мы отправляем напоминания о предстоящих записях по SMS и телефону, чтобы вы ничего не забыли.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
<?php
require_once 'includes/header.php';

// получаем всех активных мастеров
$stmt = $db->query("SELECT * FROM masters WHERE is_active = TRUE ORDER BY full_name");
$masters = $stmt->fetchAll();
?>
<!-- герой-секция -->
<section class="hero" style="background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1581488417723-78f9bce2f099?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80'); background-size: cover; background-position: center;">
    <div class="container">
        <div class="hero-content">
            <h1>Наши мастера</h1>
            <p>Профессиональные мастера с многолетним опытом работы и регулярным обучением в лучших академиях красоты.</p>
        </div>
    </div>
</section>

<!-- мастера -->
<section class="section masters-section">
    <div class="container">
        <h2 class="section-title">Встречайте нашу команду</h2>
        <div class="masters-grid">
            <?php foreach ($masters as $master): ?>
            <div class="master-card">
                <div class="master-photo">
                    <img src="photo/<?php echo htmlspecialchars($master['id']); ?>.jpg" 
                         alt="<?php echo htmlspecialchars($master['full_name']); ?>" 
                         loading="lazy">
                </div>
                <div class="master-info">
                    <h3><?php echo htmlspecialchars($master['full_name']); ?></h3>
                    <span class="master-specialization"><?php echo htmlspecialchars($master['specialization']); ?></span>
                    <p class="master-experience">Стаж работы: <?php echo rand(3, 15); ?> лет</p>
                    <p><strong>Стоимость часа:</strong> <?php echo number_format($master['hourly_rate'] ?? 0, 0, ',', ' '); ?> ₽</p>
                    <div class="master-contacts">
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($master['phone']); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="section" style="text-align: center; margin-top: 40px;">
            <h3>Как мы работаем</h3>
            <p style="max-width: 800px; margin: 20px auto; font-size: 1.1rem;">
                Наши мастера проходят регулярное обучение и используют только профессиональные средства. 
                Каждый мастер имеет индивидуальный подход к клиенту и консультирует по всем вопросам 
                по уходу за волосами после процедуры.
            </p>
            <a href="booking.php" class="btn btn-primary" style="margin-top: 20px;">
                <i class="fas fa-calendar-alt"></i> Записаться к мастеру
            </a>
        </div>
    </div>
</section>

<!-- отзывы -->
<section class="section" style="background-color: var(--light-gray);">
    <div class="container">
        <h2 class="section-title">Отзывы наших клиентов</h2>
        <div class="services-grid">
            <div class="service-card">
                <div style="font-size: 2rem; color: var(--primary-color); margin-bottom: 15px;">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                </div>
                <p style="font-style: italic; margin-bottom: 15px;">"Иван - настоящий профессионал своего дела. Он не только сделал мне идеальную стрижку, но и дал полезные советы по уходу за моими волосами. Буду возвращаться!"</p>
                <strong>— Анна, 28 лет</strong>
            </div>
            <div class="service-card">
                <div style="font-size: 2rem; color: var(--primary-color); margin-bottom: 15px;">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                </div>
                <p style="font-style: italic; margin-bottom: 15px;">"Мария сделала мне окрашивание, и я в восторге от результата! Цвет получился именно таким, каким я хотела. Атмосфера в салоне очень уютная, обслуживание на высшем уровне."</p>
                <strong>— Дмитрий, 35 лет</strong>
            </div>
            <div class="service-card">
                <div style="font-size: 2rem; color: var(--primary-color); margin-bottom: 15px;">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                </div>
                <p style="font-style: italic; margin-bottom: 15px;">"Алексей - мастер своего дела. Сделал классную стрижку и оформление бороды. Рекомендую всем, кто ценит качество и профессионализм."</p>
                <strong>— Сергей, 42 года</strong>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
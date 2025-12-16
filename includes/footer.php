<!-- Футер -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-column">
                <h3>Lumiere</h3>
                <p style="color: #ccc; line-height: 1.8; margin-top: 15px;">
                    Парикмахерская премиум-класса с 2015 года. Наши мастера - признанные профессионалы индустрии красоты, работающие только с профессиональными средствами.
                </p>
                <div class="footer-social">
                    <a href="#"><i class="fab fa-vk"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-telegram"></i></a>
                </div>
            </div>
            <div class="footer-column">
                <h3>Навигация</h3>
                <ul class="footer-links">
                    <li><a href="index.php"><i class="fas fa-chevron-right"></i> Главная</a></li>
                    <li><a href="services.php"><i class="fas fa-chevron-right"></i> Услуги</a></li>
                    <li><a href="masters.php"><i class="fas fa-chevron-right"></i> Мастера</a></li>
                    <li><a href="booking.php"><i class="fas fa-chevron-right"></i> Онлайн запись</a></li>
                    <li><a href="contacts.php"><i class="fas fa-chevron-right"></i> Контакты</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Контакты</h3>
                <ul class="footer-links">
                    <li><a href="tel:+78611234567"><i class="fas fa-phone"></i> +7 (861) 123-45-67</a></li>
                    <li><a href="mailto:info@lumiere-style.ru"><i class="fas fa-envelope"></i> info@lumiere-style.ru</a></li>
                    <li><a href="https://maps.google.com" target="_blank"><i class="fas fa-map-marker-alt"></i> г. Краснодар, ул. Красная, 100</a></li>
                    <li><i class="fas fa-clock"></i> Пн-Пт: 9:00-19:00, Сб-Вс: 10:00-18:00</li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> Parikmaherskaya "Lumiere". Все права защищены.
        </div>
    </div>
</footer>

<script>
    // Мобильное меню
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mobileNav = document.getElementById('mobileNav');
        
        if (mobileMenuToggle && mobileNav) {
            mobileMenuToggle.addEventListener('click', function() {
                mobileNav.style.display = mobileNav.style.display === 'block' ? 'none' : 'block';
                document.body.style.overflow = mobileNav.style.display === 'block' ? 'hidden' : 'auto';
            });
            
            // Закрытие меню при клике на ссылку
            mobileNav.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', function() {
                    mobileNav.style.display = 'none';
                    document.body.style.overflow = 'auto';
                });
            });
            
            // Закрытие меню при клике вне его
            document.addEventListener('click', function(e) {
                if (mobileNav.style.display === 'block' && 
                    !mobileNav.contains(e.target) && 
                    !mobileMenuToggle.contains(e.target)) {
                    mobileNav.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });
        }
        
        // Адаптивная навигация
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992 && mobileNav) {
                mobileNav.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    });
</script>
</body>
</html>
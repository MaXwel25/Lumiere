<?php
// footer.php - Подвал сайта
?>
    </main>

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
                        <li><a href="/index.php"><i class="fas fa-chevron-right"></i> Главная</a></li>
                        <li><a href="/services.php"><i class="fas fa-chevron-right"></i> Наши услуги</a></li>
                        <li><a href="/masters.php"><i class="fas fa-chevron-right"></i> Наши мастера</a></li>
                        <li><a href="/booking.php"><i class="fas fa-chevron-right"></i> Онлайн запись</a></li>
                        <li><a href="/contacts.php"><i class="fas fa-chevron-right"></i> Контакты</a></li>
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
                    <a href="/privacy.php">Политика конфиденциальности</a>
                    <a href="/terms.php">Условия использования</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Кнопка "Наверх" -->
    <button class="scroll-to-top" onclick="scrollToTop()">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Основные скрипты -->
    <script>
        // Функция для переключения мобильного меню
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobileMenu');
            mobileMenu.classList.toggle('active');
            document.body.classList.toggle('menu-open');
        }

        // Функция для плавной прокрутки наверх
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Показать/скрыть кнопку "Наверх"
        window.addEventListener('scroll', function() {
            const scrollButton = document.querySelector('.scroll-to-top');
            if (window.scrollY > 300) {
                scrollButton.classList.add('visible');
            } else {
                scrollButton.classList.remove('visible');
            }
        });

        // Закрыть мобильное меню при клике на ссылку
        document.querySelectorAll('.mobile-nav-menu a').forEach(link => {
            link.addEventListener('click', () => {
                toggleMobileMenu();
            });
        });

        // Форматирование телефона в формах
        document.addEventListener('DOMContentLoaded', function() {
            const phoneInputs = document.querySelectorAll('input[type="tel"]');
            phoneInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 0) {
                        value = '+7' + value.substring(1, Math.min(value.length, 11));
                    }
                    e.target.value = value;
                });
            });
        });

        // Инициализация всех календарей Flatpickr
        if (typeof flatpickr !== 'undefined') {
            document.querySelectorAll('.datepicker').forEach(element => {
                flatpickr(element, {
                    locale: 'ru',
                    minDate: 'today',
                    dateFormat: 'Y-m-d',
                    disable: [
                        function(date) {
                            return date.getDay() === 0; // Отключаем воскресенье
                        }
                    ]
                });
            });
        }

        // Отображение уведомлений
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                    <span>${message}</span>
                </div>
                <button class="notification-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            document.body.appendChild(notification);
            
            // Автоматическое скрытие через 5 секунд
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }

        // AJAX-запросы
        async function makeRequest(url, method = 'GET', data = null) {
            try {
                const options = {
                    method: method,
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    }
                };
                
                if (data) {
                    options.body = new URLSearchParams(data).toString();
                }
                
                const response = await fetch(url, options);
                return await response.json();
            } catch (error) {
                console.error('Ошибка запроса:', error);
                showNotification('Ошибка соединения с сервером', 'error');
                return null;
            }
        }

        // Валидация форм
        function validateForm(form) {
            let isValid = true;
            const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('error');
                    isValid = false;
                    
                    // Добавляем сообщение об ошибке
                    let errorMessage = input.nextElementSibling;
                    if (!errorMessage || !errorMessage.classList.contains('error-message')) {
                        errorMessage = document.createElement('div');
                        errorMessage.className = 'error-message';
                        errorMessage.textContent = 'Это поле обязательно для заполнения';
                        input.parentNode.appendChild(errorMessage);
                    }
                } else {
                    input.classList.remove('error');
                    
                    // Удаляем сообщение об ошибке
                    const errorMessage = input.nextElementSibling;
                    if (errorMessage && errorMessage.classList.contains('error-message')) {
                        errorMessage.remove();
                    }
                }
            });
            
            return isValid;
        }
    </script>

    <!-- Дополнительные стили для скриптов -->
    <style>
        /* Стили для кнопки "Наверх" */
        .scroll-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .scroll-to-top.visible {
            opacity: 1;
            visibility: visible;
        }
        
        .scroll-to-top:hover {
            background: var(--secondary-color);
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        
        /* Стили для уведомлений */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-width: 300px;
            max-width: 400px;
            z-index: 9999;
            animation: slideIn 0.3s ease;
            border-left: 4px solid #2ecc71;
        }
        
        .notification-error {
            border-left-color: #e74c3c;
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }
        
        .notification-content i {
            font-size: 20px;
        }
        
        .notification-success .notification-content i {
            color: #2ecc71;
        }
        
        .notification-error .notification-content i {
            color: #e74c3c;
        }
        
        .notification-close {
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
        }
        
        .notification-close:hover {
            color: #333;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Стили для ошибок валидации */
        .error {
            border-color: #e74c3c !important;
            background: #fff8f8;
        }
        
        .error-message {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 5px;
        }
        
        /* Анимации */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease;
        }
        
        /* Загрузчик */
        .loader {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .notification {
                left: 20px;
                right: 20px;
                min-width: auto;
                max-width: none;
            }
            
            .scroll-to-top {
                bottom: 20px;
                right: 20px;
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
        }
    </style>
</body>
</html>
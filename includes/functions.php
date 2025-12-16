    <?php
    // functions.php - Общие функции для проекта

    // получить список услуг
    function getServices($db, $active_only = true) {
        $sql = "SELECT * FROM services";
        if ($active_only) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY category, price";
        
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    }

    // получить список мастеров
    function getMasters($db, $active_only = true) {
        $sql = "SELECT * FROM masters";
        if ($active_only) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY full_name";
        
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    }

    // получить расписание мастера
    function getMasterSchedule($db, $master_id) {
        $stmt = $db->prepare("
            SELECT * FROM work_schedule 
            WHERE master_id = ? 
            ORDER BY day_of_week
        ");
        $stmt->execute([$master_id]);
        return $stmt->fetchAll();
    }

    // получить свободное время мастера на определенную дату
    function getAvailableTimes($db, $master_id, $date, $service_duration = 60) {
        // проверяем, является ли день рабочим
        $day_of_week = date('N', strtotime($date));
        
        $stmt = $db->prepare("
            SELECT start_time, end_time, is_working_day 
            FROM work_schedule 
            WHERE master_id = ? AND day_of_week = ?
        ");
        $stmt->execute([$master_id, $day_of_week]);
        $schedule = $stmt->fetch();
        
        if (!$schedule || !$schedule['is_working_day']) {
            return []; // выходной день
        }
        
        // получаем занятое время
        $stmt = $db->prepare("
            SELECT start_time, end_time 
            FROM appointments 
            WHERE master_id = ? 
            AND appointment_date = ? 
            AND status IN ('scheduled', 'completed')
            ORDER BY start_time
        ");
        $stmt->execute([$master_id, $date]);
        $busy_times = $stmt->fetchAll();
        
        // генерируем доступное время
        $available_times = [];
        $interval = 30; // интервал в минутах
        $start = strtotime($schedule['start_time']);
        $end = strtotime($schedule['end_time']);
        
        for ($time = $start; $time <= $end - ($service_duration * 60); $time += ($interval * 60)) {
            $time_end = $time + ($service_duration * 60);
            $is_available = true;
            
            // проверяем пересечение с занятым временем
            foreach ($busy_times as $busy) {
                $busy_start = strtotime($busy['start_time']);
                $busy_end = strtotime($busy['end_time']);
                
                if (($time < $busy_end) && ($time_end > $busy_start)) {
                    $is_available = false;
                    break;
                }
            }
            
            if ($is_available) {
                $available_times[] = date('H:i', $time);
            }
        }
        
        return $available_times;
    }

    // Создать запись на услугу
    function createAppointment($db, $data) {
        try {
            $db->beginTransaction();
            
            // проверяем доступность времени
            $available_times = getAvailableTimes($db, $data['master_id'], $data['appointment_date'], $data['duration']);
            if (!in_array($data['start_time'], $available_times)) {
                throw new Exception('Выбранное время недоступно');
            }
            
            // если клиент не указан, создаем нового
            if (empty($data['client_id'])) {
                $stmt = $db->prepare("
                    INSERT INTO clients (full_name, phone, email) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([
                    $data['client_name'],
                    $data['client_phone'],
                    $data['client_email'] ?? null
                ]);
                $client_id = $db->lastInsertId();
            } else {
                $client_id = $data['client_id'];
            }
            
            // рассчитываем время окончания
            $end_time = date('H:i:s', strtotime($data['start_time']) + ($data['duration'] * 60));
            
            // создаем запись
            $stmt = $db->prepare("
                INSERT INTO appointments 
                (client_id, master_id, service_id, appointment_date, start_time, end_time, notes, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled')
            ");
            $stmt->execute([
                $client_id,
                $data['master_id'],
                $data['service_id'],
                $data['appointment_date'],
                $data['start_time'],
                $end_time,
                $data['notes'] ?? null
            ]);
            
            $appointment_id = $db->lastInsertId();
            
            // создаем чек
            $stmt = $db->prepare("
                INSERT INTO receipts (appointment_id, total_amount, discount, final_amount, payment_method) 
                SELECT ?, price, ?, price - ?, 'cash' 
                FROM services WHERE id = ?
            ");
            $stmt->execute([
                $appointment_id,
                $data['discount'] ?? 0,
                $data['discount'] ?? 0,
                $data['service_id']
            ]);
            
            $db->commit();
            
            return [
                'success' => true,
                'appointment_id' => $appointment_id,
                'client_id' => $client_id
            ];
            
        } catch (Exception $e) {
            $db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // отменить запись
    function cancelAppointment($db, $appointment_id) {
        $stmt = $db->prepare("
            UPDATE appointments 
            SET status = 'cancelled' 
            WHERE id = ? AND status = 'scheduled'
        ");
        return $stmt->execute([$appointment_id]);
    }

    // получить статистику
    function getStatistics($db, $period = 'today') {
        $statistics = [];
        
        switch ($period) {
            case 'today':
                $date_condition = "appointment_date = CURDATE()";
                break;
            case 'week':
                $date_condition = "appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $date_condition = "MONTH(appointment_date) = MONTH(CURDATE()) AND YEAR(appointment_date) = YEAR(CURDATE())";
                break;
            default:
                $date_condition = "1=1";
        }
        
        // количество записей
        $stmt = $db->query("
            SELECT COUNT(*) as count, status 
            FROM appointments 
            WHERE $date_condition
            GROUP BY status
        ");
        $statistics['appointments'] = $stmt->fetchAll();
        
        // выручка
        $stmt = $db->query("
            SELECT 
                COUNT(*) as count,
                SUM(r.final_amount) as total_amount,
                AVG(r.final_amount) as avg_amount
            FROM receipts r
            JOIN appointments a ON r.appointment_id = a.id
            WHERE r.payment_status = 'paid' AND $date_condition
        ");
        $statistics['revenue'] = $stmt->fetch();
        
        // популярные услуги
        $stmt = $db->query("
            SELECT 
                s.name,
                COUNT(a.id) as appointment_count
            FROM services s
            LEFT JOIN appointments a ON s.id = a.service_id AND $date_condition
            GROUP BY s.id
            ORDER BY appointment_count DESC
            LIMIT 5
        ");
        $statistics['popular_services'] = $stmt->fetchAll();
        
        // занятость мастеров
        $stmt = $db->query("
            SELECT 
                m.full_name,
                COUNT(a.id) as appointment_count
            FROM masters m
            LEFT JOIN appointments a ON m.id = a.master_id AND $date_condition
            GROUP BY m.id
            ORDER BY appointment_count DESC
        ");
        $statistics['masters_workload'] = $stmt->fetchAll();
        
        return $statistics;
    }

    // форматирование даты и времени
    function formatDateTime($date, $format = 'd.m.Y H:i') {
        if (empty($date)) return '';
        return date($format, strtotime($date));
    }

    // форматирование суммы
    function formatPrice($amount) {
        return number_format($amount, 0, ',', ' ') . ' ₽';
    }

    // получить название дня недели
    function getDayName($day_number) {
        $days = [
            1 => 'Понедельник',
            2 => 'Вторник',
            3 => 'Среда',
            4 => 'Четверг',
            5 => 'Пятница',
            6 => 'Суббота',
            7 => 'Воскресенье'
        ];
        return $days[$day_number] ?? 'Неизвестно';
    }

    // отправка email уведомления
    function sendEmailNotification($to, $subject, $message, $headers = null) {
        if (!$headers) {
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=utf-8\r\n";
            $headers .= "From: Парикмахерская \"Стиль\" <no-reply@barbershop-style.ru>\r\n";
        }
        
        // в реальном проекте используйте PHPMailer или аналогичную библиотеку
        // здесь упрощенная версия для демонстрации
        return mail($to, $subject, $message, $headers);
    }

    // отправка SMS уведомления (заглушка)
    function sendSmsNotification($phone, $message) {
        // в реальном проекте здесь будет интеграция с SMS-сервисом
        error_log("SMS to {$phone}: {$message}");
        return true;
    }

    // генерация уникального номера заказа
    function generateOrderNumber() {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    // валидация данных формы
    function validateFormData($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? '';
            
            // проверка на обязательность
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = $rule['message'] ?? "Поле обязательно для заполнения";
                continue;
            }
            
            // проверка email
            if ($rule['type'] === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = "Введите корректный email адрес";
            }
            
            // проверка телефона
            if ($rule['type'] === 'phone') {
                $clean_phone = preg_replace('/[^0-9]/', '', $value);
                if (strlen($clean_phone) < 10) {
                    $errors[$field] = "Введите корректный номер телефона";
                }
            }
            
            // проверка длины
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[$field] = "Минимальная длина: {$rule['min_length']} символов";
            }
            
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[$field] = "Максимальная длина: {$rule['max_length']} символов";
            }
        }
        
        return $errors;
    }

    // загрузка файла
    function uploadFile($file, $allowed_types = ['image/jpeg', 'image/png', 'image/gif'], $max_size = 2097152) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Ошибка загрузки файла'];
        }
        
        if ($file['size'] > $max_size) {
            return ['success' => false, 'message' => 'Файл слишком большой'];
        }
        
        $file_type = mime_content_type($file['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            return ['success' => false, 'message' => 'Недопустимый тип файла'];
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $upload_path = __DIR__ . '/../uploads/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            return ['success' => true, 'filename' => $filename];
        }
        
        return ['success' => false, 'message' => 'Ошибка при сохранении файла'];
    }

    // генерация QR-кода (заглушка)
    function generateQRCode($data) {
        $qr_data = urlencode($data);
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={$qr_data}";
    }

    // получить IP адрес пользователя
    function getUserIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    // логирование ошибок
    function logError($error, $context = []) {
        $log_file = __DIR__ . '/../logs/errors.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = getUserIP();
        
        $log_entry = "[{$timestamp}] IP: {$ip} - {$error}";
        if (!empty($context)) {
            $log_entry .= " - Context: " . json_encode($context);
        }
        $log_entry .= "\n";
        
        $log_dir = dirname($log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }

    // безопасный вывод данных
    function safeOutput($data) {
        if (is_array($data)) {
            return array_map('safeOutput', $data);
        }
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    // редирект с сообщением
    function redirectWithMessage($url, $message, $type = 'success') {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
        header("Location: $url");
        exit();
    }

    // показать flash сообщение
    function showFlashMessage() {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            $type = $_SESSION['flash_type'] ?? 'success';
            
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_type']);
            
            return "<div class='alert alert-{$type}'>{$message}</div>";
        }
        return '';
    }

    // генерация случайного пароля
    function generateRandomPassword($length = 8) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }

    // Проверка времени работы
    function isWorkingHours() {
        $current_hour = (int)date('H');
        $current_day = date('N'); // 1-7 (Понедельник-Воскресенье)
        
        if ($current_day >= 1 && $current_day <= 5) {
            // Пн-Пт: 9:00-19:00
            return $current_hour >= 9 && $current_hour < 19;
        } elseif ($current_day == 6) {
            // Суббота: 10:00-18:00
            return $current_hour >= 10 && $current_hour < 18;
        } else {
            // Воскресенье: 10:00-16:00
            return $current_hour >= 10 && $current_hour < 16;
        }
    }

    // получить ближайшую доступную дату
    function getNextAvailableDate($db, $master_id = null) {
        $date = date('Y-m-d');
        
        for ($i = 0; $i < 30; $i++) {
            $check_date = date('Y-m-d', strtotime("+$i days"));
            $day_of_week = date('N', strtotime($check_date));
            
            // проверяем, является ли день выходным по общему графику
            if ($day_of_week == 7) continue; // воскресенье
            
            if ($master_id) {
                // проверяем график конкретного мастера
                $stmt = $db->prepare("
                    SELECT is_working_day 
                    FROM work_schedule 
                    WHERE master_id = ? AND day_of_week = ?
                ");
                $stmt->execute([$master_id, $day_of_week]);
                $schedule = $stmt->fetch();
                
                if (!$schedule || !$schedule['is_working_day']) {
                    continue;
                }
            }
            
            return $check_date;
        }
        
        return null;
    }
    ?>
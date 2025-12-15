<?php
// auth.php - Функции аутентификации и авторизации

session_start();

// Конфигурация безопасности
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 минут в секундах

/**
 * Проверка, авторизован ли администратор
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Проверка, авторизован ли клиент
 */
function isClientLoggedIn() {
    return isset($_SESSION['client_id']) && $_SESSION['client_id'] > 0;
}

/**
 * Получить ID текущего клиента
 */
function getCurrentClientId() {
    return $_SESSION['client_id'] ?? null;
}

/**
 * Получить данные текущего клиента
 */
function getCurrentClientData($db) {
    if (!isClientLoggedIn()) {
        return null;
    }
    
    $client_id = getCurrentClientId();
    $stmt = $db->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    return $stmt->fetch();
}

/**
 * Вход администратора
 */
function adminLogin($password, $correct_password) {
    // Проверка блокировки
    if (isAdminLockedOut()) {
        return [
            'success' => false,
            'message' => 'Слишком много попыток входа. Попробуйте позже.'
        ];
    }
    
    if ($password === $correct_password) {
        // Успешный вход
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_login_time'] = time();
        $_SESSION['login_attempts'] = 0; // Сброс счетчика
        
        // Логирование входа
        logAdminLogin($_SERVER['REMOTE_ADDR'], true);
        
        return [
            'success' => true,
            'message' => 'Вход выполнен успешно'
        ];
    } else {
        // Неудачная попытка
        incrementLoginAttempts();
        
        // Логирование неудачной попытки
        logAdminLogin($_SERVER['REMOTE_ADDR'], false);
        
        $attempts_left = MAX_LOGIN_ATTEMPTS - ($_SESSION['login_attempts'] ?? 0);
        
        return [
            'success' => false,
            'message' => 'Неверный пароль. Осталось попыток: ' . $attempts_left
        ];
    }
}

/**
 * Вход клиента
 */
function clientLogin($db, $phone, $name = null) {
    // Поиск клиента по телефону
    $stmt = $db->prepare("SELECT * FROM clients WHERE phone = ?");
    $stmt->execute([$phone]);
    $client = $stmt->fetch();
    
    if ($client) {
        // Клиент найден
        $_SESSION['client_id'] = $client['id'];
        $_SESSION['client_name'] = $client['full_name'];
        $_SESSION['client_phone'] = $client['phone'];
        
        return [
            'success' => true,
            'message' => 'Вход выполнен успешно',
            'client' => $client
        ];
    } else if ($name) {
        // Создание нового клиента
        $stmt = $db->prepare("INSERT INTO clients (full_name, phone) VALUES (?, ?)");
        if ($stmt->execute([$name, $phone])) {
            $client_id = $db->lastInsertId();
            
            $_SESSION['client_id'] = $client_id;
            $_SESSION['client_name'] = $name;
            $_SESSION['client_phone'] = $phone;
            
            return [
                'success' => true,
                'message' => 'Новый клиент зарегистрирован',
                'client' => ['id' => $client_id, 'full_name' => $name, 'phone' => $phone]
            ];
        }
    }
    
    return [
        'success' => false,
        'message' => 'Не удалось выполнить вход'
    ];
}

/**
 * Быстрый вход/регистрация клиента
 */
function quickClientAuth($db, $name, $phone) {
    // Очистка и форматирование телефона
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) === 11 && $phone[0] === '8') {
        $phone = '7' . substr($phone, 1);
    }
    
    // Проверка существования клиента
    $stmt = $db->prepare("SELECT * FROM clients WHERE phone = ?");
    $stmt->execute([$phone]);
    $client = $stmt->fetch();
    
    if ($client) {
        // Обновление имени, если оно изменилось
        if ($client['full_name'] !== $name) {
            $stmt = $db->prepare("UPDATE clients SET full_name = ? WHERE id = ?");
            $stmt->execute([$name, $client['id']]);
        }
        
        $_SESSION['client_id'] = $client['id'];
        $_SESSION['client_name'] = $name;
        $_SESSION['client_phone'] = $phone;
        
        return $client['id'];
    } else {
        // Создание нового клиента
        $stmt = $db->prepare("INSERT INTO clients (full_name, phone) VALUES (?, ?)");
        if ($stmt->execute([$name, $phone])) {
            $client_id = $db->lastInsertId();
            
            $_SESSION['client_id'] = $client_id;
            $_SESSION['client_name'] = $name;
            $_SESSION['client_phone'] = $phone;
            
            return $client_id;
        }
    }
    
    return false;
}

/**
 * Выход администратора
 */
function adminLogout() {
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_login_time']);
    session_destroy();
}

/**
 * Выход клиента
 */
function clientLogout() {
    unset($_SESSION['client_id']);
    unset($_SESSION['client_name']);
    unset($_SESSION['client_phone']);
}

/**
 * Проверка блокировки администратора
 */
function isAdminLockedOut() {
    if (!isset($_SESSION['lockout_time'])) {
        return false;
    }
    
    $lockout_time = $_SESSION['lockout_time'];
    $current_time = time();
    
    if ($current_time - $lockout_time < LOCKOUT_TIME) {
        return true;
    }
    
    // Сброс блокировки, если время истекло
    unset($_SESSION['lockout_time']);
    $_SESSION['login_attempts'] = 0;
    
    return false;
}

/**
 * Увеличение счетчика попыток входа
 */
function incrementLoginAttempts() {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 1;
    } else {
        $_SESSION['login_attempts']++;
    }
    
    // Если превышено максимальное количество попыток - блокировка
    if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $_SESSION['lockout_time'] = time();
    }
}

/**
 * Логирование попыток входа администратора
 */
function logAdminLogin($ip, $success) {
    $log_file = __DIR__ . '/../logs/admin_login.log';
    $timestamp = date('Y-m-d H:i:s');
    $status = $success ? 'SUCCESS' : 'FAILED';
    $message = "[{$timestamp}] IP: {$ip} - {$status}\n";
    
    // Создание директории для логов, если её нет
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($log_file, $message, FILE_APPEND | LOCK_EX);
}

/**
 * Защита от CSRF
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Проверка прав доступа к админ-панели
 */
function requireAdminAuth() {
    if (!isAdminLoggedIn()) {
        header('Location: /admin/login.php');
        exit();
    }
    
    // Проверка времени сессии (максимум 2 часа)
    if (isset($_SESSION['admin_login_time'])) {
        $session_duration = time() - $_SESSION['admin_login_time'];
        if ($session_duration > 7200) { // 2 часа в секундах
            adminLogout();
            header('Location: /admin/login.php?session=expired');
            exit();
        }
    }
}

/**
 * Проверка прав доступа к клиентской части
 */
function requireClientAuth() {
    if (!isClientLoggedIn()) {
        header('Location: /booking.php?auth=required');
        exit();
    }
}

/**
 * Безопасное хеширование паролей
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Генерация безопасного токена
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Очистка входных данных
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitizeInput($value);
        }
        return $data;
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Проверка email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Проверка телефона
 */
function isValidPhone($phone) {
    // Российские номера: +7 XXX XXX XX XX или 8 XXX XXX XX XX
    $pattern = '/^(\+7|8)[\s\-]?\(?\d{3}\)?[\s\-]?\d{3}[\s\-]?\d{2}[\s\-]?\d{2}$/';
    return preg_match($pattern, $phone);
}

/**
 * Форматирование телефона
 */
function formatPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (strlen($phone) === 11) {
        if ($phone[0] === '8') {
            $phone = '7' . substr($phone, 1);
        }
        return '+7 (' . substr($phone, 1, 3) . ') ' . 
               substr($phone, 4, 3) . '-' . 
               substr($phone, 7, 2) . '-' . 
               substr($phone, 9, 2);
    }
    
    return $phone;
}

/**
 * Логирование действий
 */
function logAction($action, $details = null, $user_id = null, $user_type = 'admin') {
    $log_file = __DIR__ . '/../logs/actions.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $log_entry = "[{$timestamp}] {$user_type}:{$user_id} - {$action}";
    if ($details) {
        $log_entry .= " - " . (is_array($details) ? json_encode($details) : $details);
    }
    $log_entry .= " [IP: {$ip}, User-Agent: {$user_agent}]\n";
    
    // Создание директории для логов, если её нет
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Инициализация CSRF токена при старте сессии
if (empty($_SESSION['csrf_token'])) {
    generateCsrfToken();
}
?>
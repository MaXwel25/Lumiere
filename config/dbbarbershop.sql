-- Создание перечисляемых типов (ENUM)
CREATE TYPE appointment_status AS ENUM ('scheduled', 'completed', 'cancelled', 'no_show');
CREATE TYPE payment_method AS ENUM ('cash', 'card', 'online');
CREATE TYPE payment_status AS ENUM ('pending', 'paid', 'refunded');

-- Создание таблиц

CREATE TABLE IF NOT EXISTS clients (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    reset_token VARCHAR(255),
    reset_token_expires TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS masters (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    specialization VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    hourly_rate DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS services (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL CHECK (price > 0),
    duration_min INT NOT NULL CHECK (duration_min > 0),
    category VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS work_schedule (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    master_id INT NOT NULL,
    day_of_week SMALLINT NOT NULL CHECK (day_of_week BETWEEN 1 AND 7),
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_working_day BOOLEAN DEFAULT TRUE,
    CONSTRAINT fk_work_schedule_master FOREIGN KEY (master_id) REFERENCES masters(id) ON DELETE CASCADE,
    CONSTRAINT chk_time CHECK (start_time < end_time)
);

CREATE TABLE IF NOT EXISTS appointments (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    client_id INT NOT NULL,
    master_id INT NOT NULL,
    service_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    status appointment_status DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_appointments_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_appointments_master FOREIGN KEY (master_id) REFERENCES masters(id) ON DELETE CASCADE,
    CONSTRAINT fk_appointments_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    CONSTRAINT uk_master_time UNIQUE (master_id, appointment_date, start_time)
);

CREATE TABLE IF NOT EXISTS receipts (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    appointment_id INT NOT NULL UNIQUE,
    total_amount DECIMAL(10, 2) NOT NULL CHECK (total_amount >= 0),
    discount DECIMAL(10, 2) DEFAULT 0 CHECK (discount >= 0),
    final_amount DECIMAL(10, 2) NOT NULL CHECK (final_amount >= 0),
    payment_method payment_method NOT NULL,
    payment_status payment_status DEFAULT 'pending',
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at TIMESTAMP NULL,
    CONSTRAINT fk_receipts_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
);

-- Индексы
CREATE INDEX idx_clients_phone ON clients(phone);
CREATE INDEX idx_clients_email ON clients(email);
CREATE INDEX idx_clients_reset_token ON clients(reset_token);

CREATE INDEX idx_masters_phone ON masters(phone);
CREATE INDEX idx_masters_active ON masters(is_active);

CREATE INDEX idx_services_category ON services(category);
CREATE INDEX idx_services_active ON services(is_active);
CREATE INDEX idx_services_price ON services(price);

CREATE INDEX idx_work_schedule_master_day ON work_schedule(master_id, day_of_week);
CREATE INDEX idx_work_schedule_working_days ON work_schedule(is_working_day);

CREATE INDEX idx_appointments_datetime ON appointments(appointment_date, start_time);
CREATE INDEX idx_appointments_status ON appointments(status);
CREATE INDEX idx_appointments_client ON appointments(client_id);
CREATE INDEX idx_appointments_master ON appointments(master_id);

CREATE INDEX idx_receipts_payment_status ON receipts(payment_status);
CREATE INDEX idx_receipts_issued_at ON receipts(issued_at);

-- Триггеры для автоматического updated_at (эмуляция MySQL)
CREATE OR REPLACE FUNCTION func_update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_clients_updated_at BEFORE UPDATE ON clients
    FOR EACH ROW EXECUTE FUNCTION func_update_updated_at_column();
CREATE TRIGGER trg_masters_updated_at BEFORE UPDATE ON masters
    FOR EACH ROW EXECUTE FUNCTION func_update_updated_at_column();
CREATE TRIGGER trg_services_updated_at BEFORE UPDATE ON services
    FOR EACH ROW EXECUTE FUNCTION func_update_updated_at_column();
CREATE TRIGGER trg_appointments_updated_at BEFORE UPDATE ON appointments
    FOR EACH ROW EXECUTE FUNCTION func_update_updated_at_column();

-- Триггеры бизнес-логики

-- Расчет времени окончания
CREATE OR REPLACE FUNCTION func_calculate_appointment_end_time()
RETURNS TRIGGER AS $$
DECLARE
    service_duration INT;
BEGIN
    SELECT duration_min INTO service_duration FROM services WHERE id = NEW.service_id;
    NEW.end_time := NEW.start_time + (service_duration * INTERVAL '1 minute');
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER calculate_appointment_end_time
BEFORE INSERT ON appointments
FOR EACH ROW EXECUTE FUNCTION func_calculate_appointment_end_time();

-- Проверка занятости мастера
CREATE OR REPLACE FUNCTION func_check_master_availability()
RETURNS TRIGGER AS $$
DECLARE
    is_available BOOLEAN;
BEGIN
    SELECT NOT EXISTS (
        SELECT 1 FROM appointments 
        WHERE master_id = NEW.master_id 
        AND appointment_date = NEW.appointment_date 
        AND (
            (NEW.start_time >= start_time AND NEW.start_time < end_time) OR
            (NEW.end_time > start_time AND NEW.end_time <= end_time) OR
            (NEW.start_time <= start_time AND NEW.end_time >= end_time)
        )
        AND status != 'cancelled'
    ) INTO is_available;

    IF NOT is_available THEN
        RAISE EXCEPTION 'Мастер занят в указанное время';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER check_master_availability
BEFORE INSERT ON appointments
FOR EACH ROW EXECUTE FUNCTION func_check_master_availability();

-- Создание чека
CREATE OR REPLACE FUNCTION func_update_receipt_on_appointment_change()
RETURNS TRIGGER AS $$
DECLARE
    service_price DECIMAL(10, 2);
BEGIN
    IF OLD.status != 'completed' AND NEW.status = 'completed' THEN
        SELECT price INTO service_price FROM services WHERE id = NEW.service_id;
        
        INSERT INTO receipts (appointment_id, total_amount, final_amount, payment_method, payment_status, paid_at)
        VALUES (NEW.id, service_price, service_price, 'cash', 'paid', CURRENT_TIMESTAMP)
        ON CONFLICT (appointment_id) DO UPDATE
        SET
            total_amount = service_price,
            final_amount = service_price,
            payment_status = 'paid',
            paid_at = CURRENT_TIMESTAMP;
    END IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_receipt_on_appointment_change
AFTER UPDATE ON appointments
FOR EACH ROW EXECUTE FUNCTION func_update_receipt_on_appointment_change();

-- Представления (Views)
CREATE OR REPLACE VIEW today_appointments AS
SELECT
    a.id, c.full_name as client_name, m.full_name as master_name,
    s.name as service_name, a.appointment_date, a.start_time, a.end_time, a.status, s.price
FROM appointments a
JOIN clients c ON a.client_id = c.id
JOIN masters m ON a.master_id = m.id
JOIN services s ON a.service_id = s.id
WHERE a.appointment_date = CURRENT_DATE
ORDER BY a.start_time;

CREATE OR REPLACE VIEW master_schedule_view AS
SELECT m.full_name, ws.day_of_week, ws.start_time, ws.end_time, ws.is_working_day
FROM work_schedule ws
JOIN masters m ON ws.master_id = m.id
WHERE m.is_active = TRUE AND ws.is_working_day = TRUE
ORDER BY m.full_name, ws.day_of_week, ws.start_time;

CREATE OR REPLACE VIEW financial_report AS
SELECT
    DATE(r.issued_at) as date,
    COUNT(DISTINCT r.appointment_id) as appointments_count,
    SUM(r.final_amount) as total_income,
    AVG(r.final_amount) as avg_receipt
FROM receipts r
WHERE r.payment_status = 'paid'
GROUP BY DATE(r.issued_at)
ORDER BY DATE(r.issued_at) DESC;

-- Данные
INSERT INTO masters (full_name, phone, specialization, hourly_rate) VALUES
('Иванов Иван Иванович', '+79181234567', 'Мужские стрижки', 500.00),
('Петрова Мария Сергеевна', '+79182345678', 'Женские стрижки и окрашивание', 700.00),
('Сидоров Алексей Петрович', '+79183456789', 'Барберинг, бритье', 600.00);

INSERT INTO services (name, description, price, duration_min, category) VALUES
('Мужская стрижка', 'Стрижка с оформлением', 800.00, 45, 'стрижки'),
('Женская стрижка', 'Стрижка с укладкой', 1200.00, 60, 'стрижки'),
('Окрашивание волос', 'Полное окрашивание', 2500.00, 120, 'окрашивание'),
('Стрижка бороды', 'Формирование и оформление бороды', 500.00, 30, 'барберинг'),
('Детская стрижка', 'Стрижка для детей до 12 лет', 600.00, 40, 'стрижки');

INSERT INTO work_schedule (master_id, day_of_week, start_time, end_time)
SELECT m.id, d.day_num, '09:00:00', '18:00:00'
FROM masters m
CROSS JOIN (SELECT 1 as day_num UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) d;

-- Хеши сгенерированы через PHP password_hash('password', PASSWORD_DEFAULT)
INSERT INTO clients (full_name, phone, email, password_hash) VALUES
('Васильев Василий Васильевич', '+79184567890', 'vasily@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Смирнова Анна Игоревна', '+79185678901', 'anna@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Кузнецов Дмитрий Алексеевич', '+79186789012', 'dmitry@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Пользователь БД
CREATE ROLE maxwell25 WITH LOGIN PASSWORD 'q1w2e3r4t5y6';
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO maxwell25;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO maxwell25;
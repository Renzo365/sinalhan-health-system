-- =============================================================
-- Database: bhc_sinalhan_db
-- Web-Based Patient Management System
-- Barangay Sinalhan, Santa Rosa City, Laguna
-- =============================================================

CREATE DATABASE IF NOT EXISTS bhc_sinalhan_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE bhc_sinalhan_db;

-- =============================================================
-- Table: users
-- Stores all system user accounts (admin, staff, BHW)
-- =============================================================
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) DEFAULT NULL,
    contact_number VARCHAR(20) DEFAULT NULL,
    role ENUM('admin', 'staff', 'bhw') NOT NULL DEFAULT 'staff',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=active, 0=deactivated',
    last_login DATETIME DEFAULT NULL,
    last_login_ip VARCHAR(45) DEFAULT NULL,
    is_archived TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=archived, 0=active',
    two_fa_secret VARCHAR(32) DEFAULT NULL,
    two_fa_enabled TINYINT(1) DEFAULT 0,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=must change on login, 0=normal',
    theme VARCHAR(10) NOT NULL DEFAULT 'light',
    font_size VARCHAR(10) NOT NULL DEFAULT 'normal',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_is_active (is_active),
    INDEX idx_is_archived (is_archived)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Table: patients
-- Stores patient demographic and contact information
-- =============================================================
CREATE TABLE IF NOT EXISTS patients (
    patient_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) DEFAULT NULL,
    last_name VARCHAR(100) NOT NULL,
    suffix VARCHAR(10) DEFAULT NULL COMMENT 'Jr., Sr., III, etc.',
    birthdate DATE NOT NULL,
    sex ENUM('Male', 'Female') NOT NULL,
    civil_status ENUM('Single', 'Married', 'Widowed', 'Separated', 'Divorced') DEFAULT 'Single',
    contact_number VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL COMMENT 'Full address within barangay',
    purok VARCHAR(50) DEFAULT NULL COMMENT 'Purok/Zone within Barangay Sinalhan',
    emergency_contact_name VARCHAR(200) DEFAULT NULL,
    emergency_contact_number VARCHAR(20) DEFAULT NULL,
    medical_history TEXT DEFAULT NULL COMMENT 'Known pre-existing conditions',
    allergies TEXT DEFAULT NULL COMMENT 'Known allergies',
    is_archived TINYINT(1) NOT NULL DEFAULT 0,
    registered_by INT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (last_name, first_name),
    INDEX idx_purok (purok),
    INDEX idx_birthdate (birthdate),
    INDEX idx_is_archived (is_archived),
    CONSTRAINT fk_patients_registered_by 
        FOREIGN KEY (registered_by) REFERENCES users(user_id) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Table: service_types
-- Lookup table for service categories offered by the health center
-- =============================================================
CREATE TABLE IF NOT EXISTS service_types (
    service_id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL UNIQUE,
    prefix VARCHAR(10) DEFAULT NULL COMMENT 'Queue ticketing prefix e.g. GEN, PRE',
    description TEXT DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=active, 0=deactivated',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Table: health_records
-- Consultation records per patient visit
-- =============================================================
CREATE TABLE IF NOT EXISTS health_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    service_id INT DEFAULT NULL,
    visit_date DATE NOT NULL,
    chief_complaint TEXT DEFAULT NULL COMMENT 'Patient primary complaint',
    diagnosis TEXT DEFAULT NULL,
    treatment TEXT DEFAULT NULL COMMENT 'Treatment administered',
    prescription TEXT DEFAULT NULL COMMENT 'Medications prescribed',
    notes TEXT DEFAULT NULL COMMENT 'Additional clinical notes',
    attending_staff INT DEFAULT NULL COMMENT 'Staff who conducted consultation',
    is_archived TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_patient_id (patient_id),
    INDEX idx_visit_date (visit_date),
    INDEX idx_service_id (service_id),
    INDEX idx_is_archived (is_archived),
    CONSTRAINT fk_health_records_patient 
        FOREIGN KEY (patient_id) REFERENCES patients(patient_id) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_health_records_service 
        FOREIGN KEY (service_id) REFERENCES service_types(service_id) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_health_records_staff 
        FOREIGN KEY (attending_staff) REFERENCES users(user_id) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Table: vital_signs
-- Vital signs recorded per consultation visit (1:1 with health_records)
-- =============================================================
CREATE TABLE IF NOT EXISTS vital_signs (
    vital_id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL UNIQUE COMMENT '1:1 relationship with health_records',
    blood_pressure VARCHAR(20) DEFAULT NULL COMMENT 'Format: systolic/diastolic e.g. 120/80',
    temperature DECIMAL(4,1) DEFAULT NULL COMMENT 'Body temperature in Celsius',
    weight_kg DECIMAL(5,1) DEFAULT NULL COMMENT 'Weight in kilograms',
    height_cm DECIMAL(5,1) DEFAULT NULL COMMENT 'Height in centimeters',
    heart_rate INT DEFAULT NULL COMMENT 'Beats per minute',
    respiratory_rate INT DEFAULT NULL COMMENT 'Breaths per minute',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vital_signs_record 
        FOREIGN KEY (record_id) REFERENCES health_records(record_id) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Table: appointments
-- Scheduled appointments with status tracking
-- =============================================================
CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    service_id INT DEFAULT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME DEFAULT NULL,
    status ENUM('Scheduled', 'Completed', 'Cancelled', 'No-Show') NOT NULL DEFAULT 'Scheduled',
    reason TEXT DEFAULT NULL COMMENT 'Reason for visit / appointment purpose',
    notes TEXT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    is_archived TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_patient_id (patient_id),
    INDEX idx_appointment_date (appointment_date),
    INDEX idx_status (status),
    INDEX idx_service_id (service_id),
    INDEX idx_is_archived (is_archived),
    CONSTRAINT fk_appointments_patient 
        FOREIGN KEY (patient_id) REFERENCES patients(patient_id) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_appointments_service 
        FOREIGN KEY (service_id) REFERENCES service_types(service_id) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_appointments_created_by 
        FOREIGN KEY (created_by) REFERENCES users(user_id) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Table: queue
-- Daily walk-in patient queue with status tracking
-- =============================================================
CREATE TABLE IF NOT EXISTS queue (
    queue_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    service_id INT DEFAULT NULL,
    queue_date DATE NOT NULL DEFAULT (CURDATE()),
    queue_number INT NOT NULL COMMENT 'Daily sequential number, resets each day',
    status ENUM('Waiting', 'Serving', 'Served', 'No-Show') NOT NULL DEFAULT 'Waiting',
    assigned_by INT DEFAULT NULL,
    serving_time DATETIME DEFAULT NULL COMMENT 'When patient started being served',
    completed_time DATETIME DEFAULT NULL COMMENT 'When service was completed',
    is_archived TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_queue_date (queue_date),
    INDEX idx_status (status),
    INDEX idx_queue_number (queue_date, queue_number),
    INDEX idx_patient_id (patient_id),
    INDEX idx_is_archived (is_archived),
    CONSTRAINT fk_queue_patient 
        FOREIGN KEY (patient_id) REFERENCES patients(patient_id) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_queue_service 
        FOREIGN KEY (service_id) REFERENCES service_types(service_id) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_queue_assigned_by 
        FOREIGN KEY (assigned_by) REFERENCES users(user_id) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY uk_queue_daily (queue_date, queue_number) COMMENT 'Prevent duplicate queue numbers per day'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Table: activity_log
-- System-wide audit trail (write-only, read by admin only)
-- =============================================================
CREATE TABLE IF NOT EXISTS activity_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(255) NOT NULL COMMENT 'e.g. Registered patient, Updated appointment',
    module ENUM('Patient Records', 'Health Records', 'Appointment', 'Queue', 'Admin', 'Auth', 'System') NOT NULL,
    record_id INT DEFAULT NULL COMMENT 'ID of the affected record',
    details TEXT DEFAULT NULL COMMENT 'Additional context about the action',
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_module (module),
    INDEX idx_created_at (created_at),
    INDEX idx_record_id (record_id),
    CONSTRAINT fk_activity_log_user 
        FOREIGN KEY (user_id) REFERENCES users(user_id) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Table: system_settings
-- Stores system configuration settings key-value pairs
-- =============================================================
CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
    setting_value TEXT DEFAULT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Table: notifications
-- Stores user-specific and broadcast notifications
-- =============================================================
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL COMMENT 'Target user, NULL means global broadcast',
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'info' COMMENT 'info, success, warning, danger, security',
    is_read TINYINT(1) DEFAULT 0 COMMENT '0=unread, 1=read',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    CONSTRAINT fk_notifications_user 
        FOREIGN KEY (user_id) REFERENCES users(user_id) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- Seeding Default Admin Account (password: admin123)
-- =============================================================
INSERT INTO users (username, password_hash, first_name, last_name, email, role, is_active)
VALUES (
    'admin',
    '$2y$10$WxIDoYISakIbbwUXhOT5oOM1cNmuvMX2ihJYrDUY9mDLWP4qexfzi',
    'System',
    'Administrator',
    'admin@sinalhan-hc.local',
    'admin',
    1
) ON DUPLICATE KEY UPDATE username=username;

-- =============================================================
-- Seeding Sample Staff Account (password: staff123)
-- =============================================================
INSERT INTO users (username, password_hash, first_name, last_name, role, is_active)
VALUES (
    'staff01',
    '$2y$10$YtgldasJPzct9.7qs5.GvuOZm9.WkNJtsXXhnsdw3ZwGpHAq6qG6e',
    'Maria',
    'Santos',
    'staff',
    1
) ON DUPLICATE KEY UPDATE username=username;

-- =============================================================
-- Seeding Sample BHW Account (password: bhw123)
-- =============================================================
INSERT INTO users (username, password_hash, first_name, last_name, role, is_active)
VALUES (
    'bhw01',
    '$2y$10$P4J0U68WGvAruQeHIM4nvuBcaTVgx7lqGQrYcVCoR1MuUrRiRQ3m.',
    'Rosa',
    'Reyes',
    'bhw',
    1
) ON DUPLICATE KEY UPDATE username=username;

-- =============================================================
-- Seeding Service Types
-- =============================================================
INSERT INTO service_types (service_name, prefix, description, is_active) VALUES
('General Consultation', 'GEN', 'General medical consultation and check-up', 1),
('Prenatal Care', 'PRE', 'Prenatal check-up and maternal health services', 1),
('Immunization', 'IMM', 'Vaccination services for children and adults', 1),
('Family Planning', 'FAM', 'Family planning counseling and services', 1),
('Dental Services', 'DEN', 'Basic dental check-up and treatment', 1),
('TB DOTS', 'TBD', 'Tuberculosis Directly Observed Treatment, Short-Course', 1),
('Animal Bite Treatment', 'ABT', 'Anti-rabies vaccination and wound treatment', 1),
('Blood Pressure Monitoring', 'BPM', 'Routine blood pressure check and monitoring', 1),
('Nutrition Counseling', 'NUT', 'Nutritional assessment and dietary counseling', 1),
('Laboratory Request', 'LAB', 'Laboratory test requests and referrals', 1),
('Medical Certificate', 'MC', 'Issuance of medical certificates', 1),
('Wound Care', 'WND', 'Wound cleaning, dressing, and minor surgical care', 1)
ON DUPLICATE KEY UPDATE service_name=service_name;

-- =============================================================
-- Seeding Initial Activity Log Entry
-- =============================================================
INSERT INTO activity_log (user_id, action, module, details, ip_address)
VALUES (1, 'System initialized', 'System', 'Database seeded with initial data', '127.0.0.1');

-- =============================================================
-- Seeding Default System Settings
-- =============================================================
INSERT INTO system_settings (setting_key, setting_value) VALUES
('clinic_name', 'Barangay Sinalhan Health Center'),
('clinic_address', 'Barangay Sinalhan, Santa Rosa City, Laguna, Philippines'),
('clinic_contact', '049-508-XXXX'),
('clinic_email', 'info@sinalhan-hc.gov.ph'),
('clinic_logo', ''),
('require_2fa', '0'),
('session_lifetime_minutes', '30')
ON DUPLICATE KEY UPDATE setting_key=setting_key;

-- =============================================================
-- Stored Procedures Mappings
-- =============================================================

DELIMITER //

-- 1. sp_log_activity
-- Encapsulates adding entries to the system audit trail logs.
CREATE PROCEDURE sp_log_activity(
    IN p_user_id INT,
    IN p_action VARCHAR(255),
    IN p_module VARCHAR(50),
    IN p_record_id INT,
    IN p_details TEXT,
    IN p_ip_address VARCHAR(45)
)
BEGIN
    INSERT INTO activity_log (user_id, action, module, record_id, details, ip_address)
    VALUES (p_user_id, p_action, p_module, p_record_id, p_details, p_ip_address);
END //

-- 2. sp_assign_queue_ticket
-- Safely queries today's sequential ticket count, updates status, and generates a formatted ticket.
CREATE PROCEDURE sp_assign_queue_ticket(
    IN p_patient_id INT,
    IN p_service_id INT,
    IN p_assigned_by INT,
    OUT p_ticket_string VARCHAR(20),
    OUT p_queue_number INT
)
BEGIN
    DECLARE v_prefix VARCHAR(10);
    DECLARE v_next_num INT;
    DECLARE v_today DATE;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Failed to assign queue ticket.';
    END;

    SET v_today = CURDATE();

    START TRANSACTION;

    -- Verify patient exists and is active
    IF NOT EXISTS (SELECT 1 FROM patients WHERE patient_id = p_patient_id AND is_archived = 0) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Patient record not found or archived.';
    END IF;

    -- Idempotency Guard: Prevent duplicate active queue tickets for the same patient today
    IF EXISTS (
        SELECT 1 FROM queue 
        WHERE patient_id = p_patient_id 
          AND queue_date = v_today 
          AND status IN ('Waiting', 'Serving')
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Patient already has an active queue ticket today.';
    END IF;

    -- Fetch service type prefix
    SELECT COALESCE(prefix, 'Q') INTO v_prefix 
    FROM service_types 
    WHERE service_id = p_service_id AND is_active = 1;
    
    IF v_prefix IS NULL THEN
        SET v_prefix = 'Q';
    END IF;

    -- Calculate next sequential ticket number under row lock (concurrency-safe)
    SELECT COALESCE(MAX(queue_number), 0) + 1 INTO v_next_num
    FROM queue
    WHERE queue_date = v_today
    FOR UPDATE;

    -- Insert queue ticket
    INSERT INTO queue (patient_id, service_id, queue_date, queue_number, status, assigned_by)
    VALUES (p_patient_id, p_service_id, v_today, v_next_num, 'Waiting', p_assigned_by);

    -- Format outputs
    SET p_queue_number = v_next_num;
    SET p_ticket_string = CONCAT(v_prefix, '-', LPAD(v_next_num, 3, '0'));

    COMMIT;
END //

-- 3. sp_resolve_overdue_appointments
-- Transition scheduled appointments before today to 'No-Show' and logs it to audit trail in one batch transaction.
CREATE PROCEDURE sp_resolve_overdue_appointments(
    IN p_admin_id INT,
    OUT p_resolved_count INT
)
BEGIN
    DECLARE v_updated INT DEFAULT 0;
    DECLARE v_today DATE;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Failed to execute batch appointment resolution.';
    END;

    SET v_today = CURDATE();

    START TRANSACTION;

    -- Perform bulk transition
    UPDATE appointments 
    SET status = 'No-Show' 
    WHERE appointment_date < v_today 
      AND status = 'Scheduled' 
      AND is_archived = 0;
      
    SET v_updated = ROW_COUNT();

    -- Log to audit trail if records were updated
    IF v_updated > 0 THEN
        INSERT INTO activity_log (user_id, action, module, details) 
        VALUES (p_admin_id, 'Update', 'Appointment', CONCAT('Batch resolved ', v_updated, ' overdue appointments as No-Show.'));
    END IF;

    SET p_resolved_count = v_updated;

    COMMIT;
END //

DELIMITER ;

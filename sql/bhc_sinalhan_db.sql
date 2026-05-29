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
INSERT INTO service_types (service_name, description, is_active) VALUES
('General Consultation', 'General medical consultation and check-up', 1),
('Prenatal Care', 'Prenatal check-up and maternal health services', 1),
('Immunization', 'Vaccination services for children and adults', 1),
('Family Planning', 'Family planning counseling and services', 1),
('Dental Services', 'Basic dental check-up and treatment', 1),
('TB DOTS', 'Tuberculosis Directly Observed Treatment, Short-Course', 1),
('Animal Bite Treatment', 'Anti-rabies vaccination and wound treatment', 1),
('Blood Pressure Monitoring', 'Routine blood pressure check and monitoring', 1),
('Nutrition Counseling', 'Nutritional assessment and dietary counseling', 1),
('Laboratory Request', 'Laboratory test requests and referrals', 1),
('Medical Certificate', 'Issuance of medical certificates', 1),
('Wound Care', 'Wound cleaning, dressing, and minor surgical care', 1)
ON DUPLICATE KEY UPDATE service_name=service_name;

-- =============================================================
-- Seeding Initial Activity Log Entry
-- =============================================================
INSERT INTO activity_log (user_id, action, module, details, ip_address)
VALUES (1, 'System initialized', 'System', 'Database seeded with initial data', '127.0.0.1');

-- IoT Storage Monitoring System Database
-- MySQL / MariaDB

USE iot_storage_db;

-- ============================================
-- Table 1: sensor_data (Main data storage)
-- ============================================

CREATE TABLE IF NOT EXISTS sensor_data (
    id INT(11) NOT NULL AUTO_INCREMENT,
    timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    berat FLOAT DEFAULT NULL COMMENT 'Weight in grams',
    jumlah INT(11) DEFAULT NULL COMMENT 'Item quantity',
    jarak FLOAT DEFAULT NULL COMMENT 'Distance in cm',
    status VARCHAR(20) DEFAULT NULL COMMENT 'TERISI or KOSONG',
    rfid VARCHAR(50) DEFAULT NULL COMMENT 'RFID card UID',
    stock_level VARCHAR(20) DEFAULT NULL COMMENT 'LOW, MEDIUM, or FULL',
    PRIMARY KEY (id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_stock_level (stock_level),
    INDEX idx_rfid (rfid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Sensor data storage';

-- ============================================
-- Table 2: rfid_users (RFID card registration)
-- ============================================

CREATE TABLE IF NOT EXISTS rfid_users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    rfid_uid VARCHAR(50) NOT NULL UNIQUE COMMENT 'RFID card UID',
    user_name VARCHAR(100) DEFAULT NULL COMMENT 'User full name',
    user_email VARCHAR(100) DEFAULT NULL COMMENT 'User email',
    registered_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_scan DATETIME DEFAULT NULL COMMENT 'Last RFID scan time',
    PRIMARY KEY (id),
    UNIQUE KEY unique_rfid (rfid_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Registered RFID users';

-- ============================================
-- Table 3: system_logs (System event logging)
-- ============================================

CREATE TABLE IF NOT EXISTS system_logs (
    id INT(11) NOT NULL AUTO_INCREMENT,
    timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    log_type VARCHAR(50) DEFAULT NULL COMMENT 'INFO, ERROR, WARNING',
    message TEXT DEFAULT NULL COMMENT 'Log message',
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'ESP32 IP address',
    PRIMARY KEY (id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_log_type (log_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='System logs';

-- ============================================
-- Insert sample RFID users (Optional)
-- ============================================

INSERT INTO rfid_users (rfid_uid, user_name, user_email) VALUES
('A1B2C3D4', 'John Doe', 'john.doe@example.com'),
('12345678', 'Jane Smith', 'jane.smith@example.com'),
('AABBCCDD', 'Admin User', 'admin@example.com');

-- ============================================
-- Create views for easy querying
-- ============================================

CREATE OR REPLACE VIEW v_latest_readings AS
SELECT 
    id, timestamp, berat, jumlah, jarak, status, rfid, stock_level
FROM sensor_data
ORDER BY timestamp DESC
LIMIT 100;

CREATE OR REPLACE VIEW v_daily_stats AS
SELECT 
    DATE(timestamp) as date,
    COUNT(*) as total_readings,
    AVG(berat) as avg_weight,
    AVG(jumlah) as avg_quantity,
    AVG(jarak) as avg_distance,
    SUM(CASE WHEN status = 'TERISI' THEN 1 ELSE 0 END) as occupied_count,
    SUM(CASE WHEN status = 'KOSONG' THEN 1 ELSE 0 END) as empty_count
FROM sensor_data
GROUP BY DATE(timestamp)
ORDER BY date DESC;

SELECT 'Database iot_storage_db created successfully!' as message;

-- Add admin users table to existing database
USE iot_storage_db;

CREATE TABLE IF NOT EXISTS admin_users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL COMMENT 'Hashed password',
    full_name VARCHAR(100) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    created_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1 COMMENT '1=active, 0=disabled',
    PRIMARY KEY (id),
    UNIQUE KEY unique_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Admin user accounts';

-- Insert default admin account
-- Username: admin
-- Password: admin123 (CHANGE THIS AFTER FIRST LOGIN!)
INSERT INTO admin_users (username, password, full_name, email) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@example.com');

-- Create login log table
CREATE TABLE IF NOT EXISTS login_logs (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    login_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    success TINYINT(1) DEFAULT 1,
    PRIMARY KEY (id),
    INDEX idx_username (username),
    INDEX idx_login_time (login_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Login activity logs';

SELECT 'Admin login system tables created successfully!' as message;
SELECT 'Default login - Username: admin, Password: admin123' as credentials;
SELECT 'IMPORTANT: Change password after first login!' as warning;

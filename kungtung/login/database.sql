-- Kung Tung Liquor Shop - Database Setup (v3: Email OTP, phone number saved only)
-- Run this in phpMyAdmin (XAMPP) or via the MySQL command line

CREATE DATABASE IF NOT EXISTS kung_tung_liquor;
USE kung_tung_liquor;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone_number VARCHAR(20) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    address VARCHAR(255) NOT NULL,
    date_of_birth DATE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed one admin account directly in the database.
-- No register/login UI changes needed - the same login form works for
-- both roles, since process_login.php checks the "role" column after
-- authenticating and sends admins to admin_dashboard.php instead.
--
-- Login email:    admin@kungtungliquor.com
-- Login password: Admin@12345   <-- CHANGE THIS after your first login
--
-- (The password below is already bcrypt-hashed, the same format PHP's
-- password_hash() produces, so process_login.php can verify it as-is.)
INSERT INTO users (email, phone_number, full_name, address, date_of_birth, password_hash, role)
VALUES (
    'admin@kungtungliquor.com',
    '9827156133',
    'Shop Admin',
    'Kung Tung Liquor Shop HQ, Pokhara',
    '1990-01-01',
    '$2y$10$43IXLHWKDBmEWjnxt9qQ0.lJej1Ij4toIgDXSMRWOS7wPXNlHMwuO',
    'admin'
)
ON DUPLICATE KEY UPDATE role = 'admin';

-- If you're upgrading an existing database that already has a "users" table
-- from the phone-OTP version, run this instead of the CREATE TABLE above:
--
-- ALTER TABLE users ADD COLUMN email VARCHAR(150) NOT NULL UNIQUE AFTER id;
-- ALTER TABLE users DROP INDEX phone_number; -- phone no longer needs to be unique
-- ALTER TABLE users ADD COLUMN role ENUM('customer','admin') NOT NULL DEFAULT 'customer';
-- (then run the INSERT ... ON DUPLICATE KEY UPDATE statement above to seed the admin)

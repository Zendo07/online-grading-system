-- Email Verification and Password Reset Tables
-- Run this SQL file to add email verification functionality

USE online_grading_system;

-- Email Verifications Table
CREATE TABLE IF NOT EXISTS email_verifications (
    verification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    verification_code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_email (email),
    INDEX idx_code (verification_code),
    INDEX idx_expires (expires_at)
);

-- Password Reset Table
CREATE TABLE IF NOT EXISTS password_resets (
    reset_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    reset_code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_at DATETIME NULL,
    ip_address VARCHAR(45),
    INDEX idx_email (email),
    INDEX idx_code (reset_code),
    INDEX idx_expires (expires_at)
);

-- Add email_verified column to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS email_verified BOOLEAN DEFAULT FALSE AFTER email,
ADD COLUMN IF NOT EXISTS verification_token VARCHAR(64) NULL AFTER email_verified;

-- Update existing users to be verified (optional - for existing data)
-- UPDATE users SET email_verified = TRUE WHERE created_at < NOW();
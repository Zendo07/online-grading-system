-- database/update-profile-pic.sql
-- Add profile picture column to users table
ALTER TABLE users
  ADD COLUMN profile_picture VARCHAR(255) NULL AFTER full_name;

-- Add remarks column to grades table
ALTER TABLE grades
  ADD COLUMN remarks VARCHAR(255) NULL AFTER percentage;
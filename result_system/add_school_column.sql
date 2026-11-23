-- Run this ONCE in phpMyAdmin (SQL tab) if you already imported the old database.sql.
USE result_system;
ALTER TABLE students ADD COLUMN school VARCHAR(150) NOT NULL DEFAULT '' AFTER user_id;

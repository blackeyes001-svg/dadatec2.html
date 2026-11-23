-- Student Result Management System
-- Import this file in phpMyAdmin (Import tab) or paste it in the SQL tab.

CREATE DATABASE IF NOT EXISTS result_system
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE result_system;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','student') NOT NULL DEFAULT 'student',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE students (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  school VARCHAR(150) NOT NULL,
  matric_no VARCHAR(30) NOT NULL UNIQUE,
  department VARCHAR(100) NOT NULL,
  level SMALLINT UNSIGNED NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE courses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_code VARCHAR(15) NOT NULL UNIQUE,
  course_title VARCHAR(150) NOT NULL,
  unit TINYINT UNSIGNED NOT NULL
) ENGINE=InnoDB;

CREATE TABLE results (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  course_id INT UNSIGNED NOT NULL,
  score TINYINT UNSIGNED NOT NULL,
  grade CHAR(1) NOT NULL,
  UNIQUE KEY unique_result (student_id, course_id),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

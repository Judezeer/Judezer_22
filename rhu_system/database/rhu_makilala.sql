-- =====================================================================
-- Rural Health Unit of Makilala
-- Patient Appointment & Health Record System with
-- Medicine Inventory and Dispensing Monitoring
-- =====================================================================
-- Target: MySQL 5.7+ / MariaDB 10.3+ (XAMPP compatible)
-- Charset: utf8mb4 (full unicode)
-- =====================================================================

DROP DATABASE IF EXISTS `rhu_makilala`;
CREATE DATABASE `rhu_makilala`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;
USE `rhu_makilala`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+08:00";

-- ---------------------------------------------------------------------
-- users : system accounts (admin, nurse, pharmacist, patient)
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`     VARCHAR(50)  NOT NULL UNIQUE,
  `email`        VARCHAR(120) NOT NULL UNIQUE,
  `password`     VARCHAR(255) NOT NULL,
  `full_name`    VARCHAR(120) NOT NULL,
  `role`         ENUM('admin','nurse','pharmacist','patient') NOT NULL,
  `phone`        VARCHAR(30)  DEFAULT NULL,
  `photo`        VARCHAR(255) DEFAULT NULL,
  `status`       ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `remember_token` VARCHAR(100) DEFAULT NULL,
  `reset_token`  VARCHAR(100) DEFAULT NULL,
  `reset_expires` DATETIME    DEFAULT NULL,
  `last_login`   DATETIME     DEFAULT NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`role`),
  INDEX (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- patients : patient demographic & clinical baseline
-- ---------------------------------------------------------------------
CREATE TABLE `patients` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        INT UNSIGNED DEFAULT NULL,
  `patient_code`   VARCHAR(20)  NOT NULL UNIQUE,
  `first_name`     VARCHAR(60)  NOT NULL,
  `middle_name`    VARCHAR(60)  DEFAULT NULL,
  `last_name`      VARCHAR(60)  NOT NULL,
  `sex`            ENUM('male','female') NOT NULL,
  `birthdate`      DATE         NOT NULL,
  `civil_status`   ENUM('single','married','widowed','separated') NOT NULL DEFAULT 'single',
  `contact_no`     VARCHAR(30)  DEFAULT NULL,
  `email`          VARCHAR(120) DEFAULT NULL,
  `address`        VARCHAR(255) NOT NULL,
  `barangay`       VARCHAR(80)  DEFAULT NULL,
  `blood_type`     VARCHAR(5)   DEFAULT NULL,
  `allergies`      TEXT         DEFAULT NULL,
  `philhealth_no`  VARCHAR(40)  DEFAULT NULL,
  `emergency_name` VARCHAR(120) DEFAULT NULL,
  `emergency_no`   VARCHAR(30)  DEFAULT NULL,
  `photo`          VARCHAR(255) DEFAULT NULL,
  `created_by`     INT UNSIGNED DEFAULT NULL,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`last_name`,`first_name`),
  INDEX (`barangay`),
  FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- appointments
-- ---------------------------------------------------------------------
CREATE TABLE `appointments` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id`       INT UNSIGNED NOT NULL,
  `appointment_date` DATE         NOT NULL,
  `appointment_time` TIME         NOT NULL,
  `purpose`          VARCHAR(200) NOT NULL,
  `notes`            TEXT         DEFAULT NULL,
  `status`           ENUM('pending','approved','rejected','completed','cancelled','rescheduled') NOT NULL DEFAULT 'pending',
  `handled_by`       INT UNSIGNED DEFAULT NULL,
  `created_by`       INT UNSIGNED DEFAULT NULL,
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`appointment_date`),
  INDEX (`status`),
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`handled_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- health_records : consultations, diagnosis, treatment, vaccinations
-- ---------------------------------------------------------------------
CREATE TABLE `health_records` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id`     INT UNSIGNED NOT NULL,
  `appointment_id` INT UNSIGNED DEFAULT NULL,
  `record_type`    ENUM('consultation','vaccination','laboratory','followup') NOT NULL DEFAULT 'consultation',
  `visit_date`     DATE         NOT NULL,
  `bp`             VARCHAR(15)  DEFAULT NULL,
  `temperature`    VARCHAR(10)  DEFAULT NULL,
  `pulse`          VARCHAR(10)  DEFAULT NULL,
  `weight`         VARCHAR(10)  DEFAULT NULL,
  `height`         VARCHAR(10)  DEFAULT NULL,
  `chief_complaint` TEXT        DEFAULT NULL,
  `diagnosis`      TEXT         DEFAULT NULL,
  `treatment`      TEXT         DEFAULT NULL,
  `prescription`   TEXT         DEFAULT NULL,
  `vaccine`        VARCHAR(120) DEFAULT NULL,
  `remarks`        TEXT         DEFAULT NULL,
  `attended_by`    INT UNSIGNED DEFAULT NULL,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`visit_date`),
  INDEX (`record_type`),
  FOREIGN KEY (`patient_id`)     REFERENCES `patients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`appointment_id`) REFERENCES `appointments`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`attended_by`)    REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- medicines : master list
-- ---------------------------------------------------------------------
CREATE TABLE `medicines` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`             VARCHAR(30)  NOT NULL UNIQUE,
  `name`             VARCHAR(120) NOT NULL,
  `generic_name`     VARCHAR(120) DEFAULT NULL,
  `category`         VARCHAR(80)  DEFAULT NULL,
  `dosage_form`      VARCHAR(50)  DEFAULT NULL,   -- tablet, syrup, capsule
  `strength`         VARCHAR(50)  DEFAULT NULL,   -- 500mg, 250mg/5ml
  `unit`             VARCHAR(30)  DEFAULT 'piece',
  `description`      TEXT         DEFAULT NULL,
  `reorder_level`    INT UNSIGNED NOT NULL DEFAULT 20,
  `supplier`         VARCHAR(120) DEFAULT NULL,
  `status`           ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`category`),
  INDEX (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- medicine_batches : per-batch stock with expiration
-- ---------------------------------------------------------------------
CREATE TABLE `medicine_batches` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `medicine_id`    INT UNSIGNED NOT NULL,
  `batch_no`       VARCHAR(60)  NOT NULL,
  `quantity`       INT NOT NULL DEFAULT 0,
  `initial_qty`    INT NOT NULL DEFAULT 0,
  `expiration_date` DATE        NOT NULL,
  `received_date`  DATE         NOT NULL,
  `supplier`       VARCHAR(120) DEFAULT NULL,
  `remarks`        TEXT         DEFAULT NULL,
  `created_by`     INT UNSIGNED DEFAULT NULL,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`expiration_date`),
  INDEX (`medicine_id`,`expiration_date`),
  FOREIGN KEY (`medicine_id`) REFERENCES `medicines`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`)  REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- inventory_logs : every stock in/out/adjust
-- ---------------------------------------------------------------------
CREATE TABLE `inventory_logs` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `medicine_id`  INT UNSIGNED NOT NULL,
  `batch_id`     INT UNSIGNED DEFAULT NULL,
  `action`       ENUM('stock_in','stock_out','adjust','dispense','expire') NOT NULL,
  `quantity`     INT NOT NULL,
  `balance_after` INT NOT NULL DEFAULT 0,
  `reference`    VARCHAR(80)  DEFAULT NULL,
  `remarks`      TEXT         DEFAULT NULL,
  `performed_by` INT UNSIGNED DEFAULT NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`action`),
  INDEX (`created_at`),
  FOREIGN KEY (`medicine_id`)  REFERENCES `medicines`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`batch_id`)     REFERENCES `medicine_batches`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`performed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- dispensing : dispense header (like a receipt)
-- ---------------------------------------------------------------------
CREATE TABLE `dispensing` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `receipt_no`      VARCHAR(30)  NOT NULL UNIQUE,
  `patient_id`      INT UNSIGNED NOT NULL,
  `dispensed_by`    INT UNSIGNED DEFAULT NULL,
  `dispense_date`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes`           TEXT         DEFAULT NULL,
  `total_items`     INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`dispense_date`),
  FOREIGN KEY (`patient_id`)   REFERENCES `patients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`dispensed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- dispensing_items : line items
-- ---------------------------------------------------------------------
CREATE TABLE `dispensing_items` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dispensing_id`  INT UNSIGNED NOT NULL,
  `medicine_id`    INT UNSIGNED NOT NULL,
  `batch_id`       INT UNSIGNED DEFAULT NULL,
  `quantity`       INT NOT NULL,
  `dosage`         VARCHAR(120) DEFAULT NULL,
  `instructions`   VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`dispensing_id`) REFERENCES `dispensing`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`medicine_id`)   REFERENCES `medicines`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`batch_id`)      REFERENCES `medicine_batches`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- reports : saved / generated report metadata (optional history)
-- ---------------------------------------------------------------------
CREATE TABLE `reports` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(200) NOT NULL,
  `type`        ENUM('patients','appointments','inventory','dispensing') NOT NULL,
  `date_from`   DATE DEFAULT NULL,
  `date_to`     DATE DEFAULT NULL,
  `generated_by` INT UNSIGNED DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`generated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- audit_logs : trail of user actions
-- ---------------------------------------------------------------------
CREATE TABLE `audit_logs` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED DEFAULT NULL,
  `action`     VARCHAR(80)  NOT NULL,       -- login, logout, insert, update, delete, dispense...
  `module`     VARCHAR(80)  DEFAULT NULL,   -- patients, appointments, medicines...
  `description` VARCHAR(255) DEFAULT NULL,
  `ip_address` VARCHAR(45)  DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`action`),
  INDEX (`module`),
  INDEX (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- settings : system configuration key-value
-- ---------------------------------------------------------------------
CREATE TABLE `settings` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `skey`       VARCHAR(80)  NOT NULL UNIQUE,
  `svalue`     TEXT         DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- notifications : user-facing alerts
-- ---------------------------------------------------------------------
CREATE TABLE `notifications` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED DEFAULT NULL,   -- NULL => broadcast to role
  `role`       ENUM('admin','nurse','pharmacist','patient','all') DEFAULT 'all',
  `type`       VARCHAR(50)  NOT NULL,       -- low_stock, expired, appt_approved...
  `title`      VARCHAR(120) NOT NULL,
  `message`    VARCHAR(255) NOT NULL,
  `link`       VARCHAR(255) DEFAULT NULL,
  `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`user_id`,`is_read`),
  INDEX (`role`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SEED DATA (minimal: one account per role + core settings)
-- =====================================================================

-- ---------------------------------------------------------------------
-- Seed users with REAL, WORKING bcrypt hashes (verified with PHP 8.2).
-- Default credentials:
--   admin      / Admin@123
--   nurse      / Nurse@123
--   pharmacist / Pharma@123
--   patient    / Patient@123
-- If you ever need to reset them, run install.php from the browser.
-- ---------------------------------------------------------------------
INSERT INTO `users` (`username`,`email`,`password`,`full_name`,`role`,`phone`,`status`) VALUES
('admin','admin@rhu-makilala.gov.ph','$2y$12$kZTqgKYF6THF/5.yIVWnpeWaAyxjEINDRKQhVrF/./BxUWIM9m1kK','System Administrator','admin','09171234567','active'),
('nurse','nurse@rhu-makilala.gov.ph','$2y$12$9jIanQQ2xW3w4uoWkKsrgOUfYXXlcNWdXnMS1X8DJdGPCuExaHb8G','RHU Nurse','nurse','09181234567','active'),
('pharmacist','pharma@rhu-makilala.gov.ph','$2y$12$UsHS5aBwZCNzcZO1xLW6ZezWz1tpLN2AXx3AZshcUG/6C5DeRKjLS','RHU Pharmacist','pharmacist','09191234567','active'),
('patient','patient@rhu-makilala.gov.ph','$2y$12$MFjHjVAp043lZ8vymSn8ZOQH3t67qoTD9QsDAeZYJnVP3526o0nSq','Juan Dela Cruz','patient','09201234567','active');

-- Baseline patient linked to the sample patient user
INSERT INTO `patients`
  (`user_id`,`patient_code`,`first_name`,`last_name`,`sex`,`birthdate`,`civil_status`,`contact_no`,`email`,`address`,`barangay`,`blood_type`)
VALUES
  (4,'PT-000001','Juan','Dela Cruz','male','1990-05-14','single','09201234567','patient@rhu-makilala.gov.ph','Poblacion, Makilala','Poblacion','O+');

-- Sample notifications so every role's bell has something on first login.
INSERT INTO `notifications` (`user_id`,`role`,`type`,`title`,`message`,`link`,`is_read`) VALUES
(NULL,'admin','system','Welcome to RHU Makilala HMIS','Your health information system is ready. Start by adding users and medicines.','index.php?url=admin/users',0),
(NULL,'admin','system','Backup reminder','Consider scheduling weekly database backups from the Backup DB menu.','index.php?url=admin/backup',0),
(NULL,'nurse','system','Welcome, Nurse!','Register your first patient to get started.','index.php?url=nurse/patients',0),
(NULL,'pharmacist','system','Welcome, Pharmacist!','Add medicines and stock in the first batch to begin dispensing.','index.php?url=pharmacist/medicines',0),
(NULL,'patient','system','Welcome to RHU Makilala','You can now book appointments and view your medical records online.','index.php?url=patient/book',0);

-- Baseline settings
INSERT INTO `settings` (`skey`,`svalue`) VALUES
('site_name','RHU Makilala HMIS'),
('site_tagline','Rural Health Unit of Makilala — Patient, Appointment, Inventory & Dispensing System'),
('clinic_address','Municipality of Makilala, Cotabato'),
('clinic_contact','(064) 000-0000'),
('session_timeout','1800'),
('low_stock_default','20'),
('near_expiry_days','60');

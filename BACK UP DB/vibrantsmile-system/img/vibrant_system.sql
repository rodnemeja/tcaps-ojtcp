-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 24, 2025 at 04:06 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `vibrant_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('pending','scheduled','approved','completed','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `service_id` int(11) DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `reschedule_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `status`, `notes`, `created_at`, `updated_at`, `service_id`, `end_time`, `reschedule_count`) VALUES
(120, 55, 8, '2025-03-26', '10:30:00', 'completed', NULL, '2025-03-23 09:00:17', '2025-03-23 10:46:05', 2, '11:10:00', 4),
(121, 55, 8, '2025-03-26', '14:30:00', 'cancelled', NULL, '2025-03-23 13:07:40', '2025-03-23 13:21:24', 3, '15:15:00', 0),
(122, 52, 8, '2025-03-30', '13:30:00', 'pending', NULL, '2025-03-24 01:28:01', '2025-03-24 01:28:01', 2, '14:10:00', 0),
(123, 55, 8, '2025-03-26', '13:00:00', 'completed', '', '2025-03-24 02:16:41', '2025-03-24 02:24:30', 6, '13:30:00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `appointment_services`
--

CREATE TABLE `appointment_services` (
  `appointment_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `user_id`, `specialization`, `license_number`, `status`, `created_at`, `updated_at`) VALUES
(8, 98, 'Orthodontics', 'DN-3222222', 'active', '2025-03-17 11:09:11', '2025-03-18 11:09:01'),
(9, 129, 'Othodontics', 'DN-3223232', 'active', '2025-03-22 01:59:00', '2025-03-22 01:59:00');

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(6) NOT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verifications`
--

INSERT INTO `email_verifications` (`id`, `user_id`, `token`, `verified`, `created_at`) VALUES
(34, 126, '472341', 1, '2025-03-21 12:06:39'),
(35, 130, '685433', 1, '2025-03-23 05:35:17'),
(36, 131, '335437', 1, '2025-03-23 06:46:05');

-- --------------------------------------------------------

--
-- Table structure for table `family_codes`
--

CREATE TABLE `family_codes` (
  `id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `family_codes`
--

INSERT INTO `family_codes` (`id`, `code`, `name`, `created_by`, `created_at`) VALUES
(7, '399D7D', 'MEJA', 55, '2025-03-24 01:31:43'),
(9, '2C0D37', 'BRIONES', 53, '2025-03-24 01:37:30');

-- --------------------------------------------------------

--
-- Table structure for table `family_relationships`
--

CREATE TABLE `family_relationships` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `related_patient_id` int(11) NOT NULL,
  `relationship_type` enum('parent','child','spouse','sibling') NOT NULL,
  `is_emergency_contact` tinyint(1) DEFAULT 0,
  `is_guardian` tinyint(1) DEFAULT 0,
  `is_insurance_holder` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','partial','paid') DEFAULT 'pending',
  `payment_method` enum('cash','card','insurance') DEFAULT 'cash',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `appointment_id`, `invoice_number`, `total_amount`, `payment_status`, `payment_method`, `notes`, `created_at`, `updated_at`) VALUES
(72, 120, 'INV-20250323-B151', 800.00, 'paid', 'cash', NULL, '2025-03-23 10:47:56', '2025-03-23 10:47:56');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `service_id`, `quantity`, `unit_price`, `total_price`, `created_at`) VALUES
(125, 72, 2, 1, 800.00, 800.00, '2025-03-23 10:47:56');

-- --------------------------------------------------------

--
-- Table structure for table `medical_history`
--

CREATE TABLE `medical_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `has_allergies` tinyint(1) NOT NULL DEFAULT 0,
  `allergies_details` text DEFAULT NULL,
  `has_medications` tinyint(1) NOT NULL DEFAULT 0,
  `medications_details` text DEFAULT NULL,
  `medical_conditions` varchar(255) DEFAULT NULL,
  `other_conditions_details` text DEFAULT NULL,
  `additional_notes` text DEFAULT NULL,
  `encrypted_data` text DEFAULT NULL,
  `encryption_key_version` varchar(10) NOT NULL DEFAULT '1.0',
  `iv` varchar(32) NOT NULL,
  `auth_tag` varchar(32) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) NOT NULL,
  `last_accessed` timestamp NULL DEFAULT NULL,
  `access_count` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `created_by` (`created_by`),
  KEY `deleted_by` (`deleted_by`),
  KEY `idx_encryption` (`encryption_key_version`),
  CONSTRAINT `medical_history_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `medical_history_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `medical_history_ibfk_3` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_history`
--

INSERT INTO `medical_history` (`patient_id`, `has_allergies`, `allergies_details`, `has_medications`, `medications_details`, `medical_conditions`, `other_conditions_details`, `additional_notes`, `encrypted_data`, `encryption_key_version`, `iv`, `auth_tag`, `created_by`) VALUES
(53, 1, 'Allergy reported', 1, 'NO', 'allergy', '', '{\"dental_health_status\":\"\",\"oral_prophylaxis\":\"\",\"pregnancy_status\":\"no\",\"hospitalization\":\"no\",\"hospitalization_cause\":\"FISH\"}', 'Ow9wyhHZa5fIw3FDrJTZJKTDjCXrLtbqtBwcaj1CdzIillBW14RyNrl/uqVdDP7hdHKtQkPQOR45Rq0McEZ/FVAZbGLo7+lZnV1GR7LsROGAWeMQCKoE7xZNt5KA+U46i1q5VqGw3Q83T4UjiNS4Tsjp08ypdXMLyH7Fgy1xAqE9MNU3xs51GBHIypzegt8TU4o6/mknrjQEhGldfjzdeNNYNQvkBMZt6oTBCC/2eQJeaAGJvYdYRfXeyDfHjhzUgreakDMEwwq/CYvBQUqbW2S86W8/w8y0EqhJYPuhKgNMwXAcpfBvBdhADrOPhkOY4Dlc3EqimgHmjvyWOc45ET/oTLlp6R/8vx3J4F2T1lK5ZpLioQrNJ8106IBxbUl6RSD5Ej3Ea26zB0GJdLCfNA2VJQe6eU2opogQWC+5AXwh3q29fVrv/KS830tQvT4eyatq1rXb0d9ltUlWLfbTgH6ZMqUo1as3rUs1LJGaofyrT/d2dDg+XiJBP9+mnuipwJ/+1fM69VYD2Yj5DRkoVcPxD5HYUfvZXcE2JIswhLVCcRkMKk7e2o2VESdDpiI9IyPlgvJSZozf36FvQbsQ4fHQ5DbnMRvUmKLHIwnDurImZ1ihHA==', '1.0', 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p', 'b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6', 1);

-- --------------------------------------------------------

--
-- Table structure for table `medical_history_audit_log`
--

CREATE TABLE `medical_history_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `medical_history_id` int(11) NOT NULL,
  `action` enum('view','edit','delete','restore') NOT NULL,
  `action_by` int(11) NOT NULL,
  `action_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `changes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `medical_history_id` (`medical_history_id`),
  KEY `action_by` (`action_by`),
  CONSTRAINT `medical_history_audit_log_ibfk_1` FOREIGN KEY (`medical_history_id`) REFERENCES `medical_history` (`id`) ON DELETE CASCADE,
  CONSTRAINT `medical_history_audit_log_ibfk_2` FOREIGN KEY (`action_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `encryption_keys`
--

CREATE TABLE `encryption_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` varchar(10) NOT NULL,
  `key_data` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_used` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `version` (`version`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `encryption_keys_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Dumping data for table `encryption_keys`
--

INSERT INTO `encryption_keys` (`version`, `key_data`, `created_by`) VALUES
('1.0', 'cfdf7bb1293b22c7e5bff24c9503c713483570bda51708743e4ba05d5eae54a6', 1);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `from_user_id` int(11) DEFAULT NULL,
  `to_user_id` int(11) DEFAULT NULL,
  `from_user_role` enum('admin','doctor','patient','system') NOT NULL,
  `to_user_role` enum('admin','doctor','patient') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `from_user_id`, `to_user_id`, `from_user_role`, `to_user_role`, `message`, `is_read`, `timestamp`) VALUES
(67, 126, 1, 'patient', 'admin', 'HI PO', 1, '2025-03-22 01:43:54'),
(68, 126, 1, 'patient', 'admin', 'hi', 1, '2025-03-22 01:44:16'),
(69, 1, 126, 'admin', 'patient', 'HI', 1, '2025-03-22 01:44:36'),
(70, 1, 126, 'admin', 'patient', 'hahdahdhasd', 1, '2025-03-22 01:45:02'),
(71, 126, 1, 'patient', 'admin', 'Hello', 1, '2025-03-22 02:55:34'),
(72, 1, 126, 'admin', 'patient', 'Hi', 1, '2025-03-22 02:55:43'),
(73, 126, 1, 'patient', 'admin', 'Hi doc', 1, '2025-03-22 02:55:52'),
(74, 1, 126, 'admin', 'patient', 'sdsd', 1, '2025-03-22 02:55:58'),
(75, 126, 1, 'patient', 'admin', 'dasdasd', 1, '2025-03-22 03:02:39'),
(76, 1, 126, 'admin', 'patient', 'sdsd', 1, '2025-03-22 03:02:47'),
(77, 1, 126, 'admin', 'patient', 'dsds', 1, '2025-03-22 03:07:54'),
(78, 126, 1, 'patient', 'admin', 'dsdsd', 1, '2025-03-22 03:08:02'),
(79, 98, 1, 'doctor', 'admin', 'dsdsd', 1, '2025-03-23 01:44:33'),
(80, 126, 1, 'patient', 'admin', 'dsdsd', 1, '2025-03-23 03:03:04'),
(81, 98, 126, 'doctor', 'patient', 'hu', 1, '2025-03-23 06:26:37'),
(82, 98, 130, 'doctor', 'patient', 'HI', 1, '2025-03-23 06:28:08'),
(83, 98, 1, 'doctor', 'admin', 'HI', 1, '2025-03-23 11:16:17'),
(84, 1, 98, 'admin', 'doctor', 'hi', 1, '2025-03-23 11:16:26'),
(85, 1, 98, 'admin', 'doctor', 'hello', 1, '2025-03-23 11:18:49'),
(86, 1, 98, 'admin', 'doctor', 'jhh', 1, '2025-03-23 11:19:21'),
(87, 1, 98, 'admin', 'doctor', 'hhhh', 1, '2025-03-23 11:20:10'),
(88, 1, 98, 'admin', 'doctor', 'hrsdfgh', 1, '2025-03-23 11:20:23'),
(89, 1, 98, 'admin', 'doctor', 'hhh', 1, '2025-03-23 11:21:09'),
(90, 1, 98, 'admin', 'doctor', 'jjj', 1, '2025-03-23 11:21:14'),
(91, 1, 98, 'admin', 'doctor', 'kkk', 1, '2025-03-23 11:21:42'),
(92, 1, 98, 'admin', 'doctor', 'hhh', 1, '2025-03-23 11:23:22'),
(93, 1, 131, 'admin', 'patient', 'HI PO', 1, '2025-03-23 11:25:17'),
(94, 131, 1, 'patient', 'admin', 'hello din', 1, '2025-03-23 11:25:27'),
(95, 131, 1, 'patient', 'admin', 'dsda', 1, '2025-03-23 12:38:22'),
(96, 131, 8, 'patient', 'doctor', 'HELLO PO', 0, '2025-03-23 12:50:33');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expiry` datetime NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` enum('male','female') NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `region` varchar(50) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `zipcode` varchar(10) DEFAULT NULL,
  `family_code` varchar(10) DEFAULT NULL,
  `family_role` varchar(50) DEFAULT NULL,
  `is_minor` tinyint(1) DEFAULT 0,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_relationship` varchar(50) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `guardian_email` varchar(100) DEFAULT NULL,
  `guardian_consent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `user_id`, `date_of_birth`, `age`, `gender`, `address`, `region`, `city`, `barangay`, `zipcode`, `family_code`, `family_role`, `is_minor`, `guardian_name`, `guardian_relationship`, `guardian_phone`, `guardian_email`, `guardian_consent`, `created_at`, `updated_at`) VALUES
(52, 126, '2004-06-29', 20, 'male', 'Brgy. Simbol, Kabasalan (Zamboanga Sibugay), Zamboanga Peninsula 7005', 'REGION-IX', 'Kabasalan (Zamboanga Sibugay)', 'Simbol', '7005', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, '2025-03-21 12:06:39', '2025-03-24 01:36:41'),
(53, 127, '2005-03-21', 20, 'male', 'Sulo, Naga, Zamboanga Sibugay, Zamboanga Peninsula, 7004', '90000000', '098309000', '098309016', '7004', '2C0D37', 'Child', 0, NULL, NULL, NULL, NULL, 0, '2025-03-22 00:25:42', '2025-03-24 01:37:30'),
(54, 130, '2002-06-04', 22, 'male', 'Brgy. Poblacion, Kabasalan (Zamboanga Sibugay), Zamboanga Peninsula 7005', 'REGION-IX', 'Kabasalan (Zamboanga Sibugay)', 'Poblacion', '7005', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, '2025-03-23 05:35:17', '2025-03-24 01:36:37'),
(55, 131, '2003-06-24', 21, 'male', 'Brgy. Goling, Diplahan (Zamboanga Sibugay), Zamboanga Peninsula 7039', 'REGION-IX', 'Diplahan (Zamboanga Sibugay)', 'Goling', '7039', '399D7D', 'Parent', 0, NULL, NULL, NULL, NULL, 0, '2025-03-23 06:46:05', '2025-03-24 01:31:43');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method` enum('cash','card','insurance') NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reschedule_suggestions`
--

CREATE TABLE `reschedule_suggestions` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `suggested_date` date NOT NULL,
  `suggested_time` time NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','accepted','declined') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reschedule_suggestions`
--

INSERT INTO `reschedule_suggestions` (`id`, `appointment_id`, `doctor_id`, `patient_id`, `suggested_date`, `suggested_time`, `notes`, `status`, `created_at`) VALUES
(15, 120, 8, 55, '2025-03-25', '13:30:00', 'HAHAHHA', 'accepted', '2025-03-23 09:05:27'),
(16, 120, 8, 55, '2025-03-25', '10:00:00', 'asdsad', 'accepted', '2025-03-23 09:11:38'),
(17, 120, 8, 55, '2025-03-27', '14:00:00', 'EMERGENCY', 'accepted', '2025-03-23 09:21:35'),
(18, 120, 8, 55, '2025-03-26', '10:30:00', 'RS', 'accepted', '2025-03-23 10:02:28'),
(19, 120, 8, 55, '2025-03-26', '12:30:00', 'sdasd', 'declined', '2025-03-23 10:38:08');

-- --------------------------------------------------------

--
-- Table structure for table `scheduled_messages`
--

CREATE TABLE `scheduled_messages` (
  `id` int(11) NOT NULL,
  `from_user_id` int(11) NOT NULL,
  `from_user_role` varchar(50) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `recipients` text NOT NULL,
  `schedule_time` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scheduled_messages`
--

INSERT INTO `scheduled_messages` (`id`, `from_user_id`, `from_user_role`, `subject`, `message`, `recipients`, `schedule_time`, `created_at`) VALUES
(1, 1, 'admin', 'event', 'adto ta', '[{\"id\":\"98\",\"role\":\"doctor\"},{\"id\":\"97\",\"role\":\"patient\"},{\"id\":\"99\",\"role\":\"patient\"},{\"id\":\"100\",\"role\":\"patient\"},{\"id\":\"120\",\"role\":\"patient\"},{\"id\":\"125\",\"role\":\"patient\"}]', '2025-03-27 16:21:00', '2025-03-20 04:18:11'),
(2, 1, 'admin', 'dad', 'adasdasd', '[{\"id\":\"98\",\"role\":\"doctor\"},{\"id\":\"126\",\"role\":\"patient\"}]', '2025-03-25 15:38:00', '2025-03-21 15:34:53');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `duration` varchar(50) NOT NULL,
  `duration_format` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `duration`, `duration_format`, `price`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Tooth Extraction', 'Removal of damaged or problematic teeth', '60', '1 hours', 900.00, 'active', '2025-03-08 09:18:40', '2025-03-19 10:35:46'),
(2, 'Cleaning', 'Professional dental cleaning to remove plaque and tartar', '40', '30-40 minutes', 800.00, 'active', '2025-03-08 09:18:40', '2025-03-23 07:45:14'),
(3, 'Dental Filling (Pasta)', 'Dental filling to restore damaged or decayed teeth', '45', '45 minutes', 1000.00, 'active', '2025-03-08 09:18:40', '2025-03-19 10:39:36'),
(4, 'Denture', 'Removable replacement for missing teeth', '120', '2 hours', 5000.00, 'active', '2025-03-08 09:18:40', '2025-03-17 23:47:05'),
(5, 'Braces', 'Orthodontic treatment to align teeth', '120', '1-2 hours', 15000.00, 'active', '2025-03-08 09:18:40', '2025-03-23 02:49:22'),
(6, 'Retainer', 'Custom-made device to maintain teeth alignment', '30', '30 minutes', 3000.00, 'active', '2025-03-08 09:18:40', '2025-03-17 23:47:05'),
(7, 'Fixed Bridge', 'Non-removable prosthetic to replace missing teeth', '60', '1 hours', 10000.00, 'active', '2025-03-08 09:18:40', '2025-03-19 10:38:09'),
(8, 'Whitening', 'Professional teeth whitening treatment', '60', '1 hour', 1500.00, 'active', '2025-03-08 09:18:40', '2025-03-17 23:47:05'),
(9, 'Root Canal', 'Treatment for infected tooth pulp', '90', '1.5 hours', 3000.00, 'active', '2025-03-08 09:18:40', '2025-03-17 23:47:05'),
(10, 'Jacket Crown', 'Protective crown to cover a damaged tooth', '90', '1.5 hours', 7000.00, 'active', '2025-03-08 09:18:40', '2025-03-17 23:47:05'),
(20, 'Tooth Implant', 'Massive implant', '50', '30-50 minutes', 2000.00, 'active', '2025-03-22 01:23:19', '2025-03-22 01:23:19');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `position` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','doctor','patient') NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL DEFAULT '',
  `middle_name` varchar(50) NOT NULL DEFAULT '',
  `last_name` varchar(50) NOT NULL DEFAULT '',
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `active` tinyint(1) DEFAULT 1,
  `email_verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `email`, `first_name`, `middle_name`, `last_name`, `phone`, `created_at`, `updated_at`, `active`, `email_verified`) VALUES
(1, 'admin', '$2y$10$cGPskgO9eNX.uKJlWmko/ecU4KRw0p9oAozUpaQryazw9hX1gsMTC', 'admin', 'admin@example.com', 'Admin', '', 'Asoy', '1234567890', '2025-03-08 17:18:40', '2025-03-16 08:04:19', 1, 0),
(98, 'jc2', '$2y$10$EAwhCrJN1DLkpr7IDqsZ1eoAq92YMngBtI7zadFKQ3h/rdOLu1ru.', 'doctor', 'jcfebasoy@gmail.com', 'Jc Feb', '', 'Asoy', '09133334343', '2025-03-17 11:09:11', '2025-03-22 01:54:27', 1, 0),
(126, 'rod2', '$2y$10$GjwNVsnY9FYuiFKbCSxhMe/OQusEPGm4qVmQg0Z642Sv/EbNNV24q', 'patient', 'marroncrizzy@gmail.com', 'Rodne', 'O', 'Meja', '09355852789', '2025-03-21 12:06:39', '2025-03-21 12:06:59', 1, 1),
(127, 'josh2', '$2y$10$/Yhps/OraO3Jma6CWKKW9.G4YlWrQt/YgL6xxBu1SdtoWWZDKePMW', 'patient', 'brionesjoshua@gmail.com', 'Joshua', '', 'Briones', '09932223232', '2025-03-22 00:25:42', '2025-03-22 00:25:42', 1, 0),
(129, 'Nhadz1', '$2y$10$jmYbT89f/7jcEOGQNYktZu2V6v/bAXyX68zjL/zfht5uPsS8Nldzu', 'doctor', 'nhadz@gmail.com', 'Nhadz', '', 'Hajana', '0932323232332', '2025-03-22 01:59:00', '2025-03-22 01:59:00', 1, 0),
(130, 'mel1', '$2y$10$fZJqyTbXkDWidefGyjQBrelJmeOHhbtKds45sJjUyI.fz52P7D73y', 'patient', 'rodnemeja@gmail.com', 'Melvin', '', 'Daynata', '09932223232', '2025-03-23 05:35:17', '2025-03-23 05:36:05', 1, 1),
(131, 'rike', '$2y$10$dIhk3ofkwKTN6JN/LlbktOAWXP45WuF5OrJ7rwnjlw1CWniM2jsTm', 'patient', 'rikemarwim@gmail.com', 'Rike', 'Dinaya', 'Marwim', '09333333333', '2025-03-23 06:46:05', '2025-03-23 06:46:35', 1, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `appointment_services`
--
ALTER TABLE `appointment_services`
  ADD PRIMARY KEY (`appointment_id`,`service_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `family_codes`
--
ALTER TABLE `family_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `family_relationships`
--
ALTER TABLE `family_relationships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `related_patient_id` (`related_patient_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `appointment_id` (`
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 23, 2025 at 08:03 AM
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
(113, 52, 8, '2025-03-25', '10:00:00', 'cancelled', '', '2025-03-22 01:30:27', '2025-03-22 01:31:55', 9, '11:30:00', 0),
(114, 52, 8, '2025-03-25', '10:30:00', 'completed', NULL, '2025-03-22 01:34:06', '2025-03-22 01:37:59', 4, '12:30:00', 0),
(115, 52, 8, '2025-03-25', '13:00:00', 'approved', NULL, '2025-03-22 01:36:54', '2025-03-22 05:58:18', 6, '13:30:00', 0),
(116, 53, 8, '2025-03-25', '09:00:00', 'scheduled', '', '2025-03-22 01:47:28', '2025-03-22 01:50:31', 10, '10:30:00', 0),
(117, 52, 8, '2025-03-26', '15:00:00', 'cancelled', NULL, '2025-03-23 02:50:13', '2025-03-23 04:10:36', 5, '17:00:00', 0),
(118, 52, 8, '2025-03-26', '09:30:00', 'approved', '', '2025-03-23 05:18:58', '2025-03-23 05:18:58', 5, '11:30:00', 0),
(119, 54, 8, '2025-03-27', '15:00:00', 'pending', NULL, '2025-03-23 05:40:11', '2025-03-23 05:40:11', 2, '15:45:00', 0);

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
(1, 'JJQBSY', 'MEJA', 52, '2025-03-23 05:04:58');

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
(65, 114, '', 5000.00, 'paid', 'cash', NULL, '2025-03-22 01:41:40', '2025-03-22 01:41:40');

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
(123, 65, 4, 1, 5000.00, 5000.00, '2025-03-22 01:41:40');

-- --------------------------------------------------------

--
-- Table structure for table `medical_history`
--

CREATE TABLE `medical_history` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `has_allergies` tinyint(1) NOT NULL DEFAULT 0,
  `allergies_details` text DEFAULT NULL,
  `has_medications` tinyint(1) NOT NULL DEFAULT 0,
  `medications_details` text DEFAULT NULL,
  `medical_conditions` varchar(255) DEFAULT NULL,
  `other_conditions_details` text DEFAULT NULL,
  `additional_notes` text DEFAULT NULL,
  `encrypted_data` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_history`
--

INSERT INTO `medical_history` (`id`, `patient_id`, `has_allergies`, `allergies_details`, `has_medications`, `medications_details`, `medical_conditions`, `other_conditions_details`, `additional_notes`, `encrypted_data`, `created_at`, `updated_at`) VALUES
(13, 53, 1, 'Allergy reported', 1, 'NO', 'allergy', '', '{\"dental_health_status\":\"\",\"oral_prophylaxis\":\"\",\"pregnancy_status\":\"no\",\"hospitalization\":\"no\",\"hospitalization_cause\":\"FISH\"}', 'Ow9wyhHZa5fIw3FDrJTZJKTDjCXrLtbqtBwcaj1CdzIillBW14RyNrl/uqVdDP7hdHKtQkPQOR45Rq0McEZ/FVAZbGLo7+lZnV1GR7LsROGAWeMQCKoE7xZNt5KA+U46i1q5VqGw3Q83T4UjiNS4Tsjp08ypdXMLyH7Fgy1xAqE9MNU3xs51GBHIypzegt8TU4o6/mknrjQEhGldfjzdeNNYNQvkBMZt6oTBCC/2eQJeaAGJvYdYRfXeyDfHjhzUgreakDMEwwq/CYvBQUqbW2S86W8/w8y0EqhJYPuhKgNMwXAcpfBvBdhADrOPhkOY4Dlc3EqimgHmjvyWOc45ET/oTLlp6R/8vx3J4F2T1lK5ZpLioQrNJ8106IBxbUl6RSD5Ej3Ea26zB0GJdLCfNA2VJQe6eU2opogQWC+5AXwh3q29fVrv/KS830tQvT4eyatq1rXb0d9ltUlWLfbTgH6ZMqUo1as3rUs1LJGaofyrT/d2dDg+XiJBP9+mnuipwJ/+1fM69VYD2Yj5DRkoVcPxD5HYUfvZXcE2JIswhLVCcRkMKk7e2o2VESdDpiI9IyPlgvJSZozf36FvQbsQ4fHQ5DbnMRvUmKLHIwnDurImZ1ihHA==', '2025-03-22 00:25:42', '2025-03-22 00:25:42');

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
(81, 98, 126, 'doctor', 'patient', 'hu', 0, '2025-03-23 06:26:37'),
(82, 98, 130, 'doctor', 'patient', 'HI', 1, '2025-03-23 06:28:08');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `family_code` varchar(10) DEFAULT NULL,
  `family_role` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `user_id`, `date_of_birth`, `age`, `gender`, `address`, `region`, `city`, `barangay`, `zipcode`, `created_at`, `updated_at`, `family_code`, `family_role`) VALUES
(52, 126, '2004-06-29', 20, 'male', 'Brgy. Simbol, Kabasalan (Zamboanga Sibugay), Zamboanga Peninsula 7005', 'REGION-IX', 'Kabasalan (Zamboanga Sibugay)', 'Simbol', '7005', '2025-03-21 12:06:39', '2025-03-23 07:01:27', NULL, NULL),
(53, 127, '2005-03-21', 20, 'male', 'Sulo, Naga, Zamboanga Sibugay, Zamboanga Peninsula, 7004', '90000000', '098309000', '098309016', '7004', '2025-03-22 00:25:42', '2025-03-23 07:02:18', 'JJQBSY', 'Sibling'),
(54, 130, '2002-06-04', 22, 'male', 'Brgy. Poblacion, Kabasalan (Zamboanga Sibugay), Zamboanga Peninsula 7005', 'REGION-IX', 'Kabasalan (Zamboanga Sibugay)', 'Poblacion', '7005', '2025-03-23 05:35:17', '2025-03-23 05:52:24', 'JJQBSY', 'Child'),
(55, 131, '2003-06-24', 21, 'male', 'Brgy. Goling, Diplahan (Zamboanga Sibugay), Zamboanga Peninsula 7039', 'REGION-IX', 'Diplahan (Zamboanga Sibugay)', 'Goling', '7039', '2025-03-23 06:46:05', '2025-03-23 06:47:20', 'JJQBSY', 'Sibling');

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
(14, 115, 8, 52, '2025-03-26', '10:00:00', 'BUSY PO\\r\\n', 'declined', '2025-03-22 01:52:11');

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
(2, 'Cleaning', 'Professional dental cleaning to remove plaque and tartar', '45', '45 minutes', 800.00, 'active', '2025-03-08 09:18:40', '2025-03-17 23:47:05'),
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
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `medical_history`
--
ALTER TABLE `medical_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `from_user_id` (`from_user_id`),
  ADD KEY `to_user_id` (`to_user_id`),
  ADD KEY `from_user_role` (`from_user_role`),
  ADD KEY `to_user_role` (`to_user_role`),
  ADD KEY `is_read` (`is_read`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_ip_created` (`ip_address`,`created_at`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_family_code` (`family_code`),
  ADD KEY `idx_family_role` (`family_role`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `reschedule_suggestions`
--
ALTER TABLE `reschedule_suggestions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `scheduled_messages`
--
ALTER TABLE `scheduled_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `family_codes`
--
ALTER TABLE `family_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `family_relationships`
--
ALTER TABLE `family_relationships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

--
-- AUTO_INCREMENT for table `medical_history`
--
ALTER TABLE `medical_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reschedule_suggestions`
--
ALTER TABLE `reschedule_suggestions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `scheduled_messages`
--
ALTER TABLE `scheduled_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `appointment_services`
--
ALTER TABLE `appointment_services`
  ADD CONSTRAINT `appointment_services_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointment_services_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `family_codes`
--
ALTER TABLE `family_codes`
  ADD CONSTRAINT `family_codes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `family_relationships`
--
ALTER TABLE `family_relationships`
  ADD CONSTRAINT `family_relationships_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `family_relationships_ibfk_2` FOREIGN KEY (`related_patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_items_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medical_history`
--
ALTER TABLE `medical_history`
  ADD CONSTRAINT `medical_history_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reschedule_suggestions`
--
ALTER TABLE `reschedule_suggestions`
  ADD CONSTRAINT `reschedule_suggestions_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reschedule_suggestions_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reschedule_suggestions_ibfk_3` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 29, 2025 at 12:59 PM
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
(130, 61, 8, '2025-03-27', '11:30:00', 'completed', '', '2025-03-25 22:57:12', '2025-03-25 23:26:24', 1, '12:30:00', 0),
(132, 61, 8, '2025-03-28', '13:00:00', 'cancelled', '', '2025-03-27 10:00:07', '2025-03-27 13:07:41', 1, '14:00:00', 0),
(133, 61, 8, '2025-03-28', '11:30:00', 'completed', '', '2025-03-27 12:42:56', '2025-03-27 13:07:47', 1, '12:30:00', 0),
(134, 61, 8, '2025-03-28', '15:00:00', 'completed', '', '2025-03-27 13:00:06', '2025-03-29 11:07:19', 1, '16:00:00', 0),
(135, 61, 8, '2025-03-30', '15:00:00', 'completed', '', '2025-03-29 05:51:50', '2025-03-29 05:51:58', 1, '16:00:00', 0),
(137, 119, 8, '2025-04-01', '12:30:00', 'completed', '', '2025-03-29 11:08:09', '2025-03-29 11:10:26', 9, '17:30:00', 0),
(138, 117, 8, '2025-03-30', '10:00:00', 'approved', '', '2025-03-29 11:50:16', '2025-03-29 11:50:16', 1, '11:00:00', 0);

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
(8, 98, 'Orthodontics', 'DN-3222222', 'active', '2025-03-17 11:09:11', '2025-03-18 11:09:01');

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
(97, 461, '690217', 1, '2025-03-29 10:20:04'),
(99, 463, '234546', 1, '2025-03-29 11:02:54');

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
(14, 'EA3A1B', 'SANGKALIA', 61, '2025-03-26 13:56:53'),
(15, '5BD751', 'MEJA', 119, '2025-03-29 11:47:00');

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
(110, 135, 'INV-20250329-F10E', 900.00, 'paid', 'cash', NULL, '2025-03-29 05:53:44', '2025-03-29 05:53:44');

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
(163, 110, 1, 1, 900.00, 900.00, '2025-03-29 05:53:44');

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
(25, 61, 0, NULL, 0, '', '', '', '{\"dental_health_status\":\"\",\"oral_prophylaxis\":\"\",\"pregnancy_status\":\"no\",\"hospitalization\":\"no\",\"hospitalization_cause\":\"\"}', 'SOdvCr5qaQTabAK0hzpP0j8lqDMTz7CQpxFEJ4jhyYScQGEltJlPRe+CiqEZfI5pqI8NFRr59sXi6ywNIMCIFXpsjt3dw5rz71QZRDRiJB7IHnO5KwyH8g56xdQ1n9/rwUyV6oPKXhxFTFJL5pz7oaqAukQ0ivfr/khyuaCWxXKI+/9JRzQ+3PMFYEs367vQhMRmoMKxywzhvu0xFqvvm5uuJkH8bs7Ham9C9UMzR3AOJzualrlOFXbMxW3/C6rdL9593Wl5JISchCfR6si7lZZ+nLLlkJ5zmYb3CRWF5uwxxghmUfZk+1xHvF5GGa4+QiotuOdBaqxwyqEnnoAhnPQJrUC9kJAEu0l/qC6FJisGoNml8lW9fawgT/Dl0OD/L62la2d0j/dzMF7IfKQxSq01Tc/ZyFbV3zOe0pohvkjbiIPeCjoaYx4YzMfjoefipLfB3LqHzFZM9DafyNIGs0CA3N6/9eEgrZJ61tx2JbsA5JXTy+HUwgKEVcSMUlfIt/v/09bPMn5eAEEf3hoscoGFrGskaqJPX0ieMOJqzLXsRw18547M+0ph6IW8ZdYK9UjDQ04oTxmiGxMyAyQabNRBpALvOg==', '2025-03-25 22:55:33', '2025-03-25 22:55:33'),
(27, 119, 0, '', 0, '', '', '', '{\"dental_health_status\":\"\",\"oral_prophylaxis\":\"\",\"pregnancy_status\":\"no\",\"hospitalization\":\"no\",\"hospitalization_cause\":\"\",\"dental_issues\":\"no\",\"prophylaxis\":\"no\"}', 'ijKMibXjiapa2sLNfNhiWFlq+siCazXEiv02H63yfItjmEhnPfI0f9F/OriEfYJBlcIStA6zqcGWRZdi6r8e48/GFFoAR7rkdK1SBJuK0uFAAZ52981Ze0LfY1wyxTfPl6p9U35feRxIVuRy0+g1XYZ08bPu/OSCcFPV/IfTSwgj4+5T9h/iAeRLJ/6tQ15knYGA3ySDi/p1IXPQ6k9SPWsrtuq1CewyjSMJWDug6kKrMRzi5HIr0F5DU21m/MB9Lm0vJW5BJxSAaR2ZUdXieLD2OBrM7OKL', '2025-03-29 11:07:49', '2025-03-29 11:07:49');

-- --------------------------------------------------------

--
-- Table structure for table `medications`
--

CREATE TABLE `medications` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `generic_name` varchar(255) DEFAULT NULL,
  `form` varchar(50) NOT NULL COMMENT 'tablet, capsule, liquid, etc.',
  `strength` varchar(50) NOT NULL,
  `instructions` text DEFAULT NULL,
  `side_effects` text DEFAULT NULL,
  `contraindications` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medications`
--

INSERT INTO `medications` (`id`, `name`, `generic_name`, `form`, `strength`, `instructions`, `side_effects`, `contraindications`, `created_at`, `updated_at`) VALUES
(1, 'Amoxicillin', 'Amoxicillin', 'capsule', '500mg', 'Take with or without food.', 'Diarrhea, nausea, vomiting, allergic reactions.', 'Hypersensitivity to penicillins.', '2025-03-24 15:14:55', '2025-03-24 15:14:55'),
(2, 'Ibuprofen', 'Ibuprofen', 'tablet', '400mg', 'Take with food.', 'Stomach upset, heartburn, dizziness.', 'Renal impairment, history of GI bleeding.', '2025-03-24 15:14:55', '2025-03-24 15:14:55'),
(3, 'Tylenol', 'Acetaminophen', 'tablet', '500mg', 'Do not exceed recommended dose.', 'Rare: liver damage with overdose.', 'Liver disease, alcoholism.', '2025-03-24 15:14:55', '2025-03-24 15:14:55'),
(4, 'Peridex', 'Chlorhexidine Gluconate', 'solution', '0.12%', 'Rinse twice daily for 30 seconds.', 'Tooth staining, taste alteration.', 'Known sensitivity to chlorhexidine.', '2025-03-24 15:14:55', '2025-03-24 15:14:55'),
(5, 'Lidocaine', 'Lidocaine Hydrochloride', 'gel', '2%', 'Apply to affected area as needed.', 'Numbness, tingling.', 'Allergy to amide anesthetics.', '2025-03-24 15:14:55', '2025-03-24 15:14:55');

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
(103, 98, 1, 'doctor', 'admin', 'hi', 1, '2025-03-25 23:09:14'),
(104, 1, 98, 'admin', 'doctor', 'Hello', 1, '2025-03-25 23:09:20'),
(105, 1, 98, 'admin', 'doctor', 'Fast', 1, '2025-03-25 23:09:43'),
(106, 1, 98, 'admin', 'doctor', 'Hi', 1, '2025-03-25 23:15:21'),
(107, 98, 1, 'doctor', 'admin', 'hello', 1, '2025-03-25 23:16:44'),
(108, 1, 98, 'admin', 'doctor', 'g', 1, '2025-03-25 23:17:25'),
(109, 1, 98, 'admin', 'doctor', 'Hi', 1, '2025-03-25 23:19:52'),
(110, 1, 98, 'admin', 'doctor', 'Hi', 1, '2025-03-25 23:20:08'),
(111, 1, 98, 'admin', 'doctor', 'Hi', 1, '2025-03-25 23:22:20'),
(112, 159, 1, 'patient', 'admin', 'dsdsdsd', 1, '2025-03-27 15:09:38'),
(113, 159, 1, 'patient', 'admin', 'sdsadsad', 1, '2025-03-28 00:42:31');

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
(61, 137, '2003-06-24', 21, 'male', 'La Libertad, Titay, Zamboanga Sibugay, Zamboanga Peninsula, 7003', '090000000', '098315000', '098315012', '7003', 'EA3A1B', 'Parent', 0, '', '', '', '', 0, '2025-03-25 22:55:33', '2025-03-26 13:56:53'),
(117, 461, '2005-06-29', 19, 'male', 'Purok2, Brgy. Simbol, Kabasalan (Zamboanga Sibugay), Zamboanga Peninsula 7005', 'REGION-IX', 'Kabasalan (Zamboanga Sibugay)', 'Simbol', '7005', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, '2025-03-29 10:20:04', '2025-03-29 10:20:04'),
(119, 463, '2004-06-09', 20, 'male', 'Brgy. Taway, Ipil (Zamboanga Sibugay), Zamboanga Peninsula 7001', 'REGION-IX', 'Ipil (Zamboanga Sibugay)', 'Taway', '7001', '5BD751', 'Parent', 0, NULL, NULL, NULL, NULL, 0, '2025-03-29 11:02:54', '2025-03-29 11:47:00');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method` enum('cash','gcash') NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `invoice_id`, `amount`, `payment_date`, `payment_method`, `transaction_id`, `notes`, `created_at`) VALUES
(21, 110, 900.00, '2025-03-29 05:53:53', 'cash', '', '', '2025-03-29 05:53:53');

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL,
  `prescription_number` varchar(20) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `diagnosis` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('draft','active','completed','cancelled') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescription_items`
--

CREATE TABLE `prescription_items` (
  `id` int(11) NOT NULL,
  `prescription_id` int(11) NOT NULL,
  `medication_id` int(11) NOT NULL,
  `dosage` varchar(100) NOT NULL,
  `frequency` varchar(100) NOT NULL,
  `duration` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
(3, 'Dental Filling (Pasta)', 'Dental filling to restore damaged or decayed teeth', '45', '20-45 minutes', 1000.00, 'active', '2025-03-08 09:18:40', '2025-03-26 12:44:40'),
(4, 'Denture', 'Removable replacement for missing teeth', '120', '2 hours', 5000.00, 'active', '2025-03-08 09:18:40', '2025-03-17 23:47:05'),
(5, 'Braces', 'Orthodontic treatment to align teeth', '120', '1-2 hours', 15000.00, 'active', '2025-03-08 09:18:40', '2025-03-23 02:49:22'),
(6, 'Retainer', 'Custom-made device to maintain teeth alignment', '30', '30 minutes', 3000.00, 'active', '2025-03-08 09:18:40', '2025-03-17 23:47:05'),
(7, 'Fixed Bridge', 'Non-removable prosthetic to replace missing teeth', '60', '1 hours', 10000.00, 'active', '2025-03-08 09:18:40', '2025-03-19 10:38:09'),
(8, 'Whitening', 'Professional teeth whitening treatment', '60', '1 hour', 1500.00, 'active', '2025-03-08 09:18:40', '2025-03-17 23:47:05'),
(9, 'Root Canal', 'Treatment for infected tooth pulp', '300', '1-5 hours', 3000.00, 'active', '2025-03-08 09:18:40', '2025-03-26 12:54:56'),
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
-- Table structure for table `staff_transactions`
--

CREATE TABLE `staff_transactions` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `transaction_type` varchar(50) NOT NULL,
  `details` text NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_transactions`
--

INSERT INTO `staff_transactions` (`id`, `staff_id`, `transaction_type`, `details`, `patient_id`, `appointment_id`, `amount`, `transaction_date`) VALUES
(1, 98, 'appointment', 'Scheduled an appointment for Amer Sangkalia on Apr 20, 2025 at 11:39 AM', 61, NULL, NULL, '2025-03-07 13:39:12'),
(2, 98, 'invoice', 'Created invoice #INV-20250326-4F35 for Amer Sangkalia - Total: ₱2,432.00', 61, NULL, 2432.00, '2025-03-06 13:39:12'),
(3, 98, 'appointment', 'Scheduled an appointment for Amer Sangkalia on Apr 13, 2025 at 01:39 PM', 61, NULL, NULL, '2025-03-20 13:39:12'),
(5, 98, 'invoice', 'Created invoice #INV-20250326-0C0C for Amer Sangkalia - Total: ₱4,471.00', 61, NULL, 4471.00, '2025-02-25 13:39:12'),
(6, 1, 'invoice', 'Created new invoice #INV-20250326-3B6D for patient: Amer Sangkalia - Total Amount: ₱900.00', 61, 130, 900.00, '2025-03-26 13:48:57'),
(7, 1, 'invoice', 'Created new invoice #INV-20250326-47F0 for patient: Amer Sangkalia - Total Amount: ₱900.00', 61, 130, 900.00, '2025-03-26 13:48:57'),
(8, 1, 'invoice', 'Created new invoice #INV-20250326-FDFA for patient: Amer Sangkalia - Total Amount: ₱900.00', 61, 130, 900.00, '2025-03-26 13:49:34'),
(9, 1, 'invoice', 'Created new invoice #INV-20250326-48CF for patient: Amer Sangkalia - Total Amount: ₱900.00', 61, 130, 900.00, '2025-03-26 13:49:34'),
(10, 1, 'appointment', 'Updated appointment status to \'completed\' for appointment #131 - Patient: Rodne Meja, Service: Tooth Extraction, Date: Mar 28, 2025 at 02:00 PM', 72, 131, NULL, '2025-03-27 09:31:45'),
(11, 1, 'appointment', 'Updated appointment status to \'approved\' for appointment #132 - Patient: Amer Sangkalia, Service: Tooth Extraction, Date: Mar 28, 2025 at 01:00 PM', 61, 132, NULL, '2025-03-27 12:36:11'),
(12, 1, 'appointment', 'Updated appointment status to \'cancelled\' for appointment #132 - Patient: Amer Sangkalia, Service: Tooth Extraction, Date: Mar 28, 2025 at 01:00 PM', 61, 132, NULL, '2025-03-27 13:07:41'),
(13, 1, 'appointment', 'Updated appointment status to \'completed\' for appointment #133 - Patient: Amer Sangkalia, Service: Tooth Extraction, Date: Mar 28, 2025 at 11:30 AM', 61, 133, NULL, '2025-03-27 13:07:47'),
(14, 1, 'appointment', 'Updated appointment status to \'completed\' for appointment #135 - Patient: Amer Sangkalia, Service: Tooth Extraction, Date: Mar 30, 2025 at 03:00 PM', 61, 135, NULL, '2025-03-29 05:51:58');

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
  `email_verified` tinyint(1) DEFAULT 0,
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `email`, `first_name`, `middle_name`, `last_name`, `phone`, `created_at`, `updated_at`, `active`, `email_verified`, `profile_picture`) VALUES
(1, 'Staff', '$2y$10$SDbEOGbwdlfmRLCBUff/dubDKBY51Rc/uQOFzBdW6K5Ik3nwcOXKm', 'admin', 'admin@gmail.com', 'Clinic', '', 'Staff', '097523423573', '2025-03-08 17:18:40', '2025-03-29 10:38:51', 1, 0, 'uploads/profile_pictures/67e7cdbaf35e9.jpg'),
(98, 'jc2', '$2y$10$EAwhCrJN1DLkpr7IDqsZ1eoAq92YMngBtI7zadFKQ3h/rdOLu1ru.', 'doctor', 'jcfebasoy@gmail.com', 'Jc Feb', '', 'Asoy', '09133334343', '2025-03-17 11:09:11', '2025-03-22 01:54:27', 1, 0, NULL),
(137, 'walkin_amesan4bdb', '$2y$10$lZF5L6Td/B0tGyoSYR4sAOuz2SAr9XeAtXdQlW9QLNPLINZDu4YES', 'patient', 'wisekiller034@gmail.com', 'Amer', 'Gwapo', 'Sangkalia', '09133334346', '2025-03-25 22:55:33', '2025-03-25 22:55:33', 1, 0, NULL),
(156, 'patient1', '$2y$10$jc4NrxQZJr15xkSMldxLdeyhbs9ReIpQ4cnW0K7JO9gqsg5MVITua', 'patient', 'staff2@gmail.com', 'Staff2', 'Daw', 'Ka', '09322222222', '2025-03-27 13:59:33', '2025-03-27 14:59:16', 1, 0, NULL),
(157, 'Staff3', '$2y$10$TM7Gd8Dkf8zSFUeifI7VoOQ9lFMD4uYKZu1IHwz72PDTzeGPDQiIq', 'admin', 'staff3@gmail.com', 'Staff3`', 'Daw', 'Ko', '0923353878343', '2025-03-27 14:03:41', '2025-03-27 14:03:41', 1, 0, NULL),
(158, 'Staff4', '$2y$10$Vb/cmIE08vDaOQ252KyY7OFgajurH/LG1IXy2vwDsYgINtJKLsrPW', 'admin', 'staff4@gmail.com', 'Staff4', 'Santos', 'Ki', '0932332323', '2025-03-27 14:07:09', '2025-03-27 14:20:42', 1, 0, 'uploads/profile_pictures/67e55ebaef3d3.jpg'),
(461, 'rod2', '$2y$10$njSmQFz2YbUsFwHRirWZCupXHuEbfY74SY22zjaBx7iqR7GID4lvi', 'patient', 'rodnemeja@gmail.com', 'Rodne', 'Orapa', 'Meja', '09434354464', '2025-03-29 10:20:04', '2025-03-29 10:21:06', 1, 1, NULL),
(463, 'Rike12', '$2y$10$PzfIH0gA3tAo1hycP0way.6lbRysELWKPUz14QFtja4m/WXLlzpPC', 'patient', 'rikemarwim@gmail.com', 'Siju', 'Manda', 'Dog', '09876543212', '2025-03-29 11:02:54', '2025-03-29 11:06:54', 1, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_logs`
--

CREATE TABLE `user_activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Indexes for table `medications`
--
ALTER TABLE `medications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `medication_unique` (`name`,`strength`,`form`);

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
  ADD KEY `idx_family_role` (`family_role`),
  ADD KEY `idx_is_minor` (`is_minor`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `prescription_number` (`prescription_number`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `prescription_items`
--
ALTER TABLE `prescription_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prescription_id` (`prescription_id`),
  ADD KEY `medication_id` (`medication_id`);

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
-- Indexes for table `staff_transactions`
--
ALTER TABLE `staff_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `family_codes`
--
ALTER TABLE `family_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `family_relationships`
--
ALTER TABLE `family_relationships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=164;

--
-- AUTO_INCREMENT for table `medical_history`
--
ALTER TABLE `medical_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `medications`
--
ALTER TABLE `medications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescription_items`
--
ALTER TABLE `prescription_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reschedule_suggestions`
--
ALTER TABLE `reschedule_suggestions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

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
-- AUTO_INCREMENT for table `staff_transactions`
--
ALTER TABLE `staff_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=464;

--
-- AUTO_INCREMENT for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_doctor_id_fk` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescriptions_patient_id_fk` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prescription_items`
--
ALTER TABLE `prescription_items`
  ADD CONSTRAINT `prescription_items_medication_id_fk` FOREIGN KEY (`medication_id`) REFERENCES `medications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescription_items_prescription_id_fk` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE;

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

--
-- Constraints for table `staff_transactions`
--
ALTER TABLE `staff_transactions`
  ADD CONSTRAINT `staff_transactions_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  ADD CONSTRAINT `user_activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 18, 2024 at 03:34 PM
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
-- Database: `tcaps_g8_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `content_sections`
--

CREATE TABLE `content_sections` (
  `id` int(11) NOT NULL,
  `section_name` varchar(100) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `content_sections`
--

INSERT INTO `content_sections` (`id`, `section_name`, `content`, `image_path`, `updated_at`) VALUES
(1, 'brand', 'BRAND\", \"The School That Cares For Your Future.\"', NULL, '2024-08-01 16:09:26'),
(2, 'vision', 'A leading educational Institution that advocate holistic transformational development for global competitiveness.', NULL, '2024-08-01 16:09:26'),
(3, 'mission', 'To provide responsive, relevant and innovative education and training that equip students with the knowledge, attributes, values and skills to become successful in their chosen career and meet the demands of the national and global industry.', NULL, '2024-08-01 16:09:26'),
(4, 'core_values', '<span class=\"core-values-acro\">S</span> <span class=\"core-values-content\">- Servant Leadership</span>...etc', NULL, '2024-08-01 16:09:26'),
(5, 'about_system', 'Archiving platform for research ( Faculty Research’, Capstone, Thesis, ) of faculty and students at Sibugay Technical Institute Incorporated...', '', '2024-08-01 16:10:21');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `course_department` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `course_name`, `course_department`) VALUES
(13, 'BSIT', '83'),
(14, 'BSCS ', '83');

-- --------------------------------------------------------

--
-- Table structure for table `dean`
--

CREATE TABLE `dean` (
  `dean_id` int(11) NOT NULL,
  `dean_name` varchar(255) NOT NULL,
  `dean_username` varchar(255) NOT NULL,
  `dean_email` varchar(255) NOT NULL,
  `dean_password` varchar(255) NOT NULL,
  `dean_department` varchar(255) NOT NULL,
  `access` varchar(99) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dean`
--

INSERT INTO `dean` (`dean_id`, `dean_name`, `dean_username`, `dean_email`, `dean_password`, `dean_department`, `access`) VALUES
(53, 'Rodz Meja', 'rod1', 'rodnemeja12@gmail.com', '12', '83', '');

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(255) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `status` varchar(50) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`department_id`, `department_name`, `course_name`, `description`, `status`) VALUES
(83, 'College of Computer Studies', '', '', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` int(11) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `student_middlename` varchar(255) NOT NULL,
  `student_lastname` varchar(255) NOT NULL,
  `student_suffix` varchar(255) NOT NULL,
  `student_section` varchar(255) NOT NULL,
  `student_department` varchar(255) NOT NULL,
  `student_username` varchar(255) NOT NULL,
  `student_password` varchar(255) NOT NULL,
  `student_status` varchar(255) NOT NULL,
  `access` enum('Student','Teacher','Coordinator') NOT NULL,
  `profile_image` varchar(255) NOT NULL,
  `id_image` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `student_name`, `student_middlename`, `student_lastname`, `student_suffix`, `student_section`, `student_department`, `student_username`, `student_password`, `student_status`, `access`, `profile_image`, `id_image`) VALUES
(112, 'Rodne ', 'O.', 'Meja', '2nd', 'BSIT', '83', 'rodnemeja@gmail.com', '12', 'Approved', 'Student', '../Upload/h.jfif', '../Upload/hshas.png');

-- --------------------------------------------------------

--
-- Table structure for table `upload`
--

CREATE TABLE `upload` (
  `upload_id` int(11) NOT NULL,
  `upload_name` varchar(255) NOT NULL,
  `upload_abstract` varchar(1000) NOT NULL,
  `upload_file` varchar(255) NOT NULL,
  `upload_image` varchar(255) NOT NULL,
  `upload_author` varchar(255) NOT NULL,
  `upload_student_id` int(11) NOT NULL,
  `upload_department` varchar(255) NOT NULL,
  `status` enum('Pending','Approved','Disapproved') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `upload`
--

INSERT INTO `upload` (`upload_id`, `upload_name`, `upload_abstract`, `upload_file`, `upload_image`, `upload_author`, `upload_student_id`, `upload_department`, `status`) VALUES
(71, 'TOURISM', 'TESTTTTT', 'TOURISM EXAMPLE CHAPTER1.pdf', '', 'Meja,Joshua,Daynata', 112, '83', 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(99) NOT NULL,
  `name` varchar(99) NOT NULL,
  `username` varchar(99) NOT NULL,
  `password` varchar(99) NOT NULL,
  `access` varchar(99) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `name`, `username`, `password`, `access`) VALUES
(1, 'Admin', 'admin', 'admin', 'Administrator'),
(4, 'student ', 'stud1', '1', 'Student');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `content_sections`
--
ALTER TABLE `content_sections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`);

--
-- Indexes for table `dean`
--
ALTER TABLE `dean`
  ADD PRIMARY KEY (`dean_id`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`student_id`);

--
-- Indexes for table `upload`
--
ALTER TABLE `upload`
  ADD PRIMARY KEY (`upload_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `content_sections`
--
ALTER TABLE `content_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `dean`
--
ALTER TABLE `dean`
  MODIFY `dean_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `upload`
--
ALTER TABLE `upload`
  MODIFY `upload_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(99) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

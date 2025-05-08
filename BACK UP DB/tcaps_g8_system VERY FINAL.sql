-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 28, 2024 at 08:11 PM
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
(26, 'Wincy Heart Magbanua   ', 'wincy', '', '1236', '68', 'Coordinator'),
(30, 'Lea', 'lea@gmail.com', '', '123', '69', '');

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(255) NOT NULL,
  `status` varchar(50) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`department_id`, `department_name`, `status`) VALUES
(68, 'College of Computer Studies', 'active'),
(69, 'Social Work ', 'active'),
(70, 'Midwifery', 'active'),
(71, 'Criminology', 'active'),
(72, 'Education', 'active'),
(73, 'Agriculture', 'active'),
(74, 'HRM', 'active');

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
  `access` enum('Student') NOT NULL,
  `profile_image` varchar(255) NOT NULL,
  `id_image` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `student_name`, `student_middlename`, `student_lastname`, `student_suffix`, `student_section`, `student_department`, `student_username`, `student_password`, `student_status`, `access`, `profile_image`, `id_image`) VALUES
(64, 'Joshua', '', 'Brionesd', 'Jr.', 'BSIT -4A', '69', 'joshua@gmail.com', '123', 'Approved', 'Student', 'download (1).jfif', '../Upload/ID SAMPLE.jpg'),
(71, 'Rodne', '', 'Meja', '2nd', 'BSIT-3B', '68', 'rodnemeja@gmail.com', '123', 'Approved', 'Student', '../Upload/RODNEM.png', '../Upload/RODNE ID.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `upload`
--

CREATE TABLE `upload` (
  `upload_id` int(11) NOT NULL,
  `upload_name` varchar(255) NOT NULL,
  `upload_abstract` varchar(1000) NOT NULL,
  `upload_file` varchar(255) NOT NULL,
  `upload_author` varchar(255) NOT NULL,
  `upload_student_id` int(11) NOT NULL,
  `upload_department` varchar(255) NOT NULL,
  `status` enum('Pending','Approved','Disapproved') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `upload`
--

INSERT INTO `upload` (`upload_id`, `upload_name`, `upload_abstract`, `upload_file`, `upload_author`, `upload_student_id`, `upload_department`, `status`) VALUES
(65, 'TCAPS', 'SAMPLE', 'Introduction-to-Basic-Manufacturing-Processes-and-Workshop-Technology-PDFDrive-.pdf', 'Meja', 64, '68', 'Approved'),
(66, 'THESIS AND CAPSTONE ARCHIVING SYSTEM', 'sample', 'CHAPTER 1- FINAL G8.pdf', 'ako', 71, '68', 'Pending');

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
-- AUTO_INCREMENT for table `dean`
--
ALTER TABLE `dean`
  MODIFY `dean_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `upload`
--
ALTER TABLE `upload`
  MODIFY `upload_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(99) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

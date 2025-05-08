-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 22, 2024 at 10:30 AM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 8.1.6

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
  `dean_password` varchar(255) NOT NULL,
  `dean_department` varchar(255) NOT NULL,
  `access` varchar(99) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `dean`
--

INSERT INTO `dean` (`dean_id`, `dean_name`, `dean_username`, `dean_password`, `dean_department`, `access`) VALUES
(26, 'Wincy Heart Magbanua   ', 'wincy', '1236', '68', 'Coordinator'),
(30, 'Lea', 'lea@gmail.com', '123', '69', '');

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`department_id`, `department_name`) VALUES
(68, 'College of Computer Studies'),
(69, 'Social Work '),
(70, 'Midwifery'),
(71, 'Criminology'),
(72, 'Education'),
(73, 'Agriculture');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` int(11) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `student_middlename` varchar(255) NOT NULL,
  `student_lastname` varchar(255) NOT NULL,
  `student_section` varchar(255) NOT NULL,
  `student_department` varchar(255) NOT NULL,
  `student_username` varchar(255) NOT NULL,
  `student_password` varchar(255) NOT NULL,
  `student_status` varchar(255) NOT NULL,
  `access` varchar(99) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `student_name`, `student_middlename`, `student_lastname`, `student_section`, `student_department`, `student_username`, `student_password`, `student_status`, `access`) VALUES
(32, 'Goju Saturo', '', '', 'BSIT -2A', '68', 'killuazoldyk933@gmail.com', '123', 'Confirmed', ''),
(39, 'Joshua', '', 'Briones', 'BSIT-2A', '68', 'briones@gmail.com', '123', 'Confirmed', ''),
(40, 'Jaypee', '', 'Celio', 'BSIT-2A', '68', 'jaypee@gmail.com', '1', 'Confirmed', 'Student'),
(42, 'Joshua', '', 'Sweety', 'BSIT-2A', '68', 'brionesjoshua03@gmail.com', '12', 'Confirmed', ''),
(43, 'Nhadzmar', '', 'Hajana', 'BSIT-2A', '68', 'nhadzmar@gmail.com', '123', 'Confirmed', ''),
(44, 'jagdish', 'ampol', 'berondo', 'BSIT-2A', '68', 'berondo@gmail.com', '123', 'Confirmed', ''),
(45, 'Rodne', '', 'Meja', 'BSIT-2A', '68', 'rodnemeja@gmail.com', '1234', 'Confirmed', ''),
(46, 'Jessabel', '', 'Ortega', 'BSIT-2A', '68', 'jessable@gmail.com', '123', 'Pending', '');

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
  `upload_department` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `upload`
--

INSERT INTO `upload` (`upload_id`, `upload_name`, `upload_abstract`, `upload_file`, `upload_author`, `upload_student_id`, `upload_department`) VALUES
(35, 'Calculator System', 'The Cartculator system aims to revolutionize the traditional shopping experience by incorporating smart technology into shopping carts. The rise of e-commerce and online shopping, retailers are seeking innovative ways to enhance the in-store shopping experience and provide customers with personalized, convenient, and efficient services. It involves equipping traditional shopping carts with advanced sensors, digital displays, and connectivity features to create a smart and interactive shopping companion for customers. Cartculator ', 'docu-cartculator.pdf', 'GOJU', 32, '68'),
(48, 'Artificial Intelligence and Robotics', 'Artificial Intelligence (AI) is a commonly employed appellation\r\nto refer to the field of science aimed at providing machines\r\nwith the capacity of performing functions such as logic,\r\nreasoning, planning, learning, and perception. Despite the\r\nreference to “machines” in this definition, the latter could\r\nbe applied to “any type of living intelligence”. Likewise, the\r\nmeaning of intelligence, as it is found in primates and other\r\nexceptional animals for example, it can be extended to\r\ninclude an interleaved set of capacities, including creativity,\r\nemotional knowledge, and self-awareness.', '1. Artificial Intelligence and Robotics Author Javier Andreu Perez,Fani Deligianni,Daniele Ravi.pdf', 'Javier Andreu Perez, Fani Deligianni, Daniele Ravi and Guang-Zhong Yang', 42, '68'),
(49, 'THESIS AND CAPSTONE ARCHIVING SYSTEM', 'This system typically involved digital repositories where students can submit their complete thesis or capstones projects, making them accessible to future researcher.\r\nOne encountered situation the realm of system could be the frustration experienced by students and faculty when attempting to access previous works for reference or inspiration without a centralized and well-organized repository, individuals may spend considerable time searching for relevant materials, only find incomplete or outdated records. This can hinder the research process and impact the development of new ideas. A robust archiving system would alleviate this challenge by providing easy access to comprehensive collection of past theses and  capstone projects, facilitating knowledge sharing and academic advancement.\r\n', 'CHAPTER 1- FINAL G8.pdf', 'Rodne Meja,Joshua Briones, Nhadzmar Hajana,Sweety Ferraren,Jessabel Ortega', 45, '68'),
(50, 'AI ', 'This system typically involved digital repositories where students can submit their complete thesis or capstones projects, making them accessible to future researcher.\r\nOne encountered situation the realm of system could be the frustration experienced by students and faculty when attempting to access previous works for reference or inspiration without a centralized and well-organized repository, individuals may spend considerable time searching for relevant materials, only find incomplete or outdated records. This can hinder the research process and impact the development of new ideas. A robust archiving system would alleviate this challenge by providing easy access to comprehensive collection of past theses and  capstone projects, facilitating knowledge sharing and academic advancement.\r\n', 'CHAPTER 1- FINAL G8.pdf', 'Rodne Meja,Joshua Briones, Nhadzmar Hajana,Sweety Ferraren,Jessabel Ortega', 45, '68');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `name`, `username`, `password`, `access`) VALUES
(1, 'Admin', 'admin', 'admin', 'Administrator');

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
  MODIFY `dean_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `upload`
--
ALTER TABLE `upload`
  MODIFY `upload_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(99) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

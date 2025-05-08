-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 25, 2025 at 12:01 PM
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
-- Database: `vibrant_dental`
--

-- --------------------------------------------------------

--
-- Table structure for table `medical_history`
--

CREATE TABLE `medical_history` (
  `med_id` int(20) NOT NULL,
  `med_date` varchar(200) NOT NULL,
  `med_prev_dent` varchar(200) NOT NULL,
  `med_last_vi` varchar(200) NOT NULL,
  `one` varchar(200) NOT NULL,
  `two` varchar(200) NOT NULL,
  `two_answer` varchar(200) NOT NULL,
  `three` varchar(200) NOT NULL,
  `three_answer` varchar(200) NOT NULL,
  `four` varchar(200) NOT NULL,
  `four_answer` varchar(200) NOT NULL,
  `five` varchar(200) NOT NULL,
  `five_answer` varchar(200) NOT NULL,
  `six` varchar(200) NOT NULL,
  `seven` varchar(200) NOT NULL,
  `allergies_str` longtext NOT NULL,
  `pregnant` varchar(200) NOT NULL,
  `nursing` varchar(200) NOT NULL,
  `pills` varchar(200) NOT NULL,
  `blood_type` varchar(200) NOT NULL,
  `conditions_str` longtext NOT NULL,
  `u_id` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `medical_history`
--

INSERT INTO `medical_history` (`med_id`, `med_date`, `med_prev_dent`, `med_last_vi`, `one`, `two`, `two_answer`, `three`, `three_answer`, `four`, `four_answer`, `five`, `five_answer`, `six`, `seven`, `allergies_str`, `pregnant`, `nursing`, `pills`, `blood_type`, `conditions_str`, `u_id`) VALUES
(37, 'March 01,2025 00:18: AM', '', '', 'No', 'No', '', 'No', '', 'No', '', 'No', '', 'No', 'No', '', 'No', 'No', 'No', 'B-', 'Blood Diseases, Head Injuries', '101'),
(39, 'March 01,2025 11:46: AM', '', '', 'No', 'No', '', 'No', '', 'No', '', 'No', '', 'No', 'No', '', 'No', 'No', 'No', 'B+', '', '1'),
(40, 'March 01,2025 15:11: PM', '', '', 'No', 'No', '', 'No', '', 'No', '', 'No', '', 'No', 'No', '', 'No', 'No', 'No', 'B+', '', '104');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `medical_history`
--
ALTER TABLE `medical_history`
  ADD PRIMARY KEY (`med_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `medical_history`
--
ALTER TABLE `medical_history`
  MODIFY `med_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

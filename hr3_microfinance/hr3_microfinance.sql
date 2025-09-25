-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 25, 2025 at 05:04 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hr3_microfinance`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED DEFAULT NULL,
  `shift` varchar(255) NOT NULL,
  `rfid` varchar(191) DEFAULT NULL,
  `time_in` varchar(50) DEFAULT NULL,
  `Clock_In_Status` varchar(50) NOT NULL,
  `time_out` varchar(50) DEFAULT NULL,
  `status_clock_out` varchar(50) DEFAULT 'Present',
  `date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `employee_id`, `shift`, `rfid`, `time_in`, `Clock_In_Status`, `time_out`, `status_clock_out`, `date`) VALUES
(9, 5, 'Afternoon', '111', '15:17:15', 'On Time', '15:17:40', 'Present', '2025-09-25'),
(10, 6, 'Afternoon', '222', '15:17:26', 'On Time', '2025-09-25 18:40:22', 'Present', '2025-09-25'),
(12, 7, 'Afternoon', '333', '15:19:48', 'On Time', '2025-09-25 17:22:12', 'Present', '2025-09-25'),
(53, 9, 'Afternoon', '3032159310', '2025-09-25 17:04:47', 'On Time', '2025-09-25 17:21:49', 'Present', '2025-09-25'),
(54, 11, 'Afternoon', '3035121566', '2025-09-25 17:05:11', 'On Time', '2025-09-25 17:30:08', 'Present', '2025-09-25'),
(55, 10, 'Afternoon', '2869943342', '2025-09-25 17:05:19', 'Late', '2025-09-25 17:21:36', 'Present', '2025-09-25'),
(56, 12, 'Afternoon', '3031964270', '2025-09-25 17:05:23', 'On Time', '2025-09-25 17:30:05', 'Present', '2025-09-25'),
(57, 8, 'Afternoon', '3035128254', '2025-09-25 17:21:26', 'On Time', '2025-09-25 17:52:25', 'Present', '2025-09-25'),
(58, 13, 'Afternoon', '3035541086', '2025-09-25 18:46:40', 'On Time', '2025-09-25 19:39:27', 'Present', '2025-09-25'),
(59, 14, 'Morning', NULL, NULL, 'Absent', NULL, 'Absent', '2025-09-25'),
(68, 15, 'Afternoon', NULL, NULL, 'Absent', NULL, 'Absent', '2025-09-25'),
(69, 16, 'Night', NULL, NULL, 'Absent', NULL, 'Absent', '2025-09-25'),
(71, 17, 'Night', NULL, NULL, 'Absent', NULL, 'Absent', '2025-09-25'),
(72, 16, 'Afternoon', '2920516186', '2025-09-25 22:40:22', 'On Time', '2025-09-25 23:02:25', 'Present', '2025-09-25');

-- --------------------------------------------------------

--
-- Table structure for table `claims`
--

CREATE TABLE `claims` (
  `id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `claims`
--

INSERT INTO `claims` (`id`, `employee_id`, `amount`, `description`, `status`, `created_at`) VALUES
(1, 12, 5000.00, 'Travel', 'Approved', '2025-09-24 16:00:00'),
(2, 9, 200.00, 'wadwadwa', 'Pending', '2025-09-24 16:00:00'),
(3, 10, 5000.00, 'Accommodation', 'Approved', '2025-09-24 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `rfid` varchar(191) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `name`, `position`, `email`, `rfid`, `department`, `created_at`) VALUES
(5, 'jhon Doe', 'janitor', 'john@example.com', '3031822926', NULL, '2025-09-25 07:15:10'),
(6, 'jane doe', 'hr', 'none@gmail.com', '222', NULL, '2025-09-25 07:15:29'),
(7, 'maria', 'absolute', 'mar@gmail.com', '333', NULL, '2025-09-25 07:15:56'),
(8, 'Reed Richard', 'Manager', 'reed@gmail.com', '3035128254', NULL, '2025-09-25 07:23:31'),
(9, 'Damian Wayne', 'janitor', 'dam@gmail.com', '3032159310', NULL, '2025-09-25 07:24:00'),
(10, 'david taylor', 'hr', 'da@gmail.com', '2869943342', NULL, '2025-09-25 07:24:20'),
(11, 'victor zsasz', 'cleaning', 'vic@gmail.com', '3035121566', NULL, '2025-09-25 07:24:54'),
(12, 'clark kent', 'journalist', 'kent@gmail.com', '3031964270', NULL, '2025-09-25 07:25:16'),
(13, 'Tony', 'lead eng', 'ton2@gmail.com', '3035541086', NULL, '2025-09-25 10:45:48'),
(14, 'Peter parker', 'dadwadwa', 'per@gmail.com', '3031817438', NULL, '2025-09-25 13:24:31'),
(15, 'Barry Allen', 'absolute', 'bar@gmail.com', '3031810206', NULL, '2025-09-25 13:35:51'),
(16, 'juliana awdawd', 'Manager', 'ju@gmail.com', '2920516186', NULL, '2025-09-25 14:01:06'),
(17, 'dam way', 'janitor', 'da@gmail.com', '3033278222', NULL, '2025-09-25 14:08:10');

-- --------------------------------------------------------

--
-- Table structure for table `leaves`
--

CREATE TABLE `leaves` (
  `id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `leave_letter` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leaves`
--

INSERT INTO `leaves` (`id`, `employee_id`, `start_date`, `end_date`, `reason`, `status`, `created_at`, `leave_letter`) VALUES
(2, 12, '2025-09-25', '2025-10-28', 'Emergency Leave', 'Approved', '2025-09-25 12:23:07', '1758803216_Leave-Letter-Templates-3r570j5-12-22-03.pdf'),
(3, 15, '2025-09-25', '2025-09-27', 'Sick Leave', 'Pending', '2025-09-25 14:53:13', '1758811993_Leave-Letter-Templates-3r570j5-12-22-03.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

CREATE TABLE `shifts` (
  `id` int(11) NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `shift_name` varchar(50) NOT NULL,
  `start_time` varchar(50) NOT NULL,
  `end_time` varchar(50) NOT NULL,
  `date` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shifts`
--

INSERT INTO `shifts` (`id`, `employee_id`, `shift_name`, `start_time`, `end_time`, `date`, `department`) VALUES
(4, 6, 'Afternoon', '15:16:00', '16:00:00', '2025-09-25', 'cleaning'),
(5, 5, 'Afternoon', '15:17:00', '16:00:00', '2025-09-25', 'cleaning'),
(6, 7, 'Night', '17:01', '15:25:00', '2025-09-25', 'HR'),
(7, 12, 'Afternoon', '17:00', '17:30', '2025-09-25', 'awdwadwa'),
(8, 11, 'Afternoon', '17:02', '17:30', '2025-09-25', 'cleaning'),
(9, 10, 'Night', '16:30', '17:06', '2025-09-25', 'cleaning'),
(10, 9, 'Night', '16:50', '17:15', '2025-09-25', 'cleaning'),
(11, 8, 'Night', '17:20', '17:40', '2025-09-25', 'awdwadwa'),
(13, 6, 'Morning', '07:30', '12:00', '2025-09-26', 'cleaning'),
(14, 13, 'Night', '18:56', '19:20', '2025-09-25', 'eng'),
(15, 14, 'Morning', '07:30', '11:30', '2025-09-25', 'cleaning'),
(16, 15, 'Afternoon', '13:00', '16:30', '2025-09-25', 'HR'),
(17, 16, 'Night', '22:40', '22:50', '2025-09-25', 'cleaning'),
(18, 17, 'Night', '19:00', '21:00', '2025-09-25', 'cleaning');

-- --------------------------------------------------------

--
-- Table structure for table `timesheets`
--

CREATE TABLE `timesheets` (
  `id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED DEFAULT NULL,
  `shift` varchar(120) DEFAULT NULL,
  `ts_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attendance_employee_idx` (`employee_id`);

--
-- Indexes for table `claims`
--
ALTER TABLE `claims`
  ADD PRIMARY KEY (`id`),
  ADD KEY `claims_employee_idx` (`employee_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rfid_unique` (`rfid`);

--
-- Indexes for table `leaves`
--
ALTER TABLE `leaves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leaves_employee_idx` (`employee_id`);

--
-- Indexes for table `shifts`
--
ALTER TABLE `shifts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `timesheets`
--
ALTER TABLE `timesheets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_idx` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `claims`
--
ALTER TABLE `claims`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `leaves`
--
ALTER TABLE `leaves`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `shifts`
--
ALTER TABLE `shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `timesheets`
--
ALTER TABLE `timesheets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_employee_fk` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `claims`
--
ALTER TABLE `claims`
  ADD CONSTRAINT `claims_employee_fk` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `leaves`
--
ALTER TABLE `leaves`
  ADD CONSTRAINT `leaves_employee_fk` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `shifts`
--
ALTER TABLE `shifts`
  ADD CONSTRAINT `shifts_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timesheets`
--
ALTER TABLE `timesheets`
  ADD CONSTRAINT `timesheets_employee_fk` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

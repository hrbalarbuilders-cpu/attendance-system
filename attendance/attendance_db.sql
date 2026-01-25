-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Jan 11, 2026 at 03:46 PM
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
-- Database: `attendance_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

CREATE TABLE `attendance_logs` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `type` varchar(10) NOT NULL,
  `working_from` varchar(50) DEFAULT NULL,
  `reason` enum('lunch','tea','shift_start','shift_end') NOT NULL DEFAULT 'shift_start',
  `time` datetime NOT NULL,
  `device_id` varchar(100) NOT NULL,
  `latitude` decimal(10,6) DEFAULT NULL,
  `longitude` decimal(10,6) DEFAULT NULL,
  `synced` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_logs`
--

INSERT INTO `attendance_logs` (`id`, `user_id`, `type`, `working_from`, `reason`, `time`, `device_id`, `latitude`, `longitude`, `synced`) VALUES
(261, '3', 'in', 'Ville Flora', 'shift_start', '2026-01-11 20:13:58', 'WEB_DASHBOARD', 25.594095, 85.137565, 1),
(262, '3', 'out', 'Ville Flora', 'shift_end', '2026-01-11 20:14:32', 'WEB_DASHBOARD', 25.594095, 85.137565, 1),
(263, '3', 'in', 'Ville Flora', 'shift_start', '2026-01-11 20:14:47', 'WEB_DASHBOARD', 25.594095, 85.137565, 1),
(264, '3', 'out', 'Ville Flora', 'shift_end', '2026-01-11 20:14:51', 'WEB_DASHBOARD', 25.594095, 85.137565, 1),
(265, '3', 'in', 'Ville Flora', 'shift_start', '2026-01-11 20:14:55', 'WEB_DASHBOARD', 25.594095, 85.137565, 1),
(266, '3', 'out', 'Ville Flora', 'shift_end', '2026-01-11 20:14:58', 'WEB_DASHBOARD', 25.594095, 85.137565, 1);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `department_name`, `created_at`, `updated_at`) VALUES
(1, 'HR', '2025-12-09 11:27:17', NULL),
(7, '1', '2026-01-10 10:20:11', NULL),
(8, '1', '2026-01-10 10:20:11', NULL),
(9, '2', '2026-01-10 10:20:14', NULL),
(10, '2', '2026-01-10 10:20:14', NULL),
(11, '3', '2026-01-10 10:20:17', NULL),
(12, '3', '2026-01-10 10:20:17', NULL),
(13, '4', '2026-01-10 10:20:21', NULL),
(14, '4', '2026-01-10 10:20:21', NULL),
(15, '5', '2026-01-10 10:20:24', NULL),
(16, '5', '2026-01-10 10:20:24', NULL),
(17, '6', '2026-01-10 10:20:26', NULL),
(18, '6', '2026-01-10 10:20:26', NULL),
(19, '7', '2026-01-10 10:20:40', NULL),
(20, '7', '2026-01-10 10:20:40', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `designations`
--

CREATE TABLE `designations` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `designation_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `designations`
--

INSERT INTO `designations` (`id`, `department_id`, `designation_name`, `created_at`, `updated_at`) VALUES
(2, 1, 'HR Executive', '2025-12-09 11:28:17', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `user_id` int(11) NOT NULL,
  `emp_code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `designation_id` int(11) DEFAULT NULL,
  `shift_id` int(11) DEFAULT NULL,
  `default_working_from` varchar(50) DEFAULT '',
  `weekoff_days` varchar(100) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `device_id` varchar(100) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`user_id`, `emp_code`, `name`, `mobile`, `email`, `dob`, `department_id`, `designation_id`, `shift_id`, `default_working_from`, `weekoff_days`, `joining_date`, `updated_at`, `device_id`, `status`, `created_at`) VALUES
(3, 'EMP001', 'Sachin Mandal', '6352816306', 'sachin.balarbuilders@gmail.com', '2001-12-28', 1, 2, 2, 'Ville Flora', 'Wednesday', '2025-12-28', '2025-12-28 11:21:23', 'flutter_device', 1, '2025-12-09 20:17:04'),
(4, 'EMP002', 'Harish Thapa', '6352816306', 'harish.balarbuilders@gmail.com', '2025-12-29', 1, 2, 4, 'Ville Flora', 'Thursday', '2025-12-31', '2025-12-28 12:33:13', NULL, 1, '2025-12-10 09:10:56'),
(5, 'EMP005', 'Ganesh Rohit', '', 'ganesh.balarbuilders@gmail.com', '0000-00-00', 1, 2, 2, 'Ville Flora', NULL, '2025-12-22', '2026-01-10 11:19:53', NULL, 1, '2025-12-22 11:18:40');

-- --------------------------------------------------------

--
-- Table structure for table `geo_settings`
--

CREATE TABLE `geo_settings` (
  `id` int(11) NOT NULL,
  `location_name` varchar(100) NOT NULL DEFAULT 'Office',
  `latitude` decimal(10,6) NOT NULL,
  `longitude` decimal(10,6) NOT NULL,
  `radius_meters` int(11) NOT NULL DEFAULT 150,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `location_group` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `geo_settings`
--

INSERT INTO `geo_settings` (`id`, `location_name`, `latitude`, `longitude`, `radius_meters`, `created_at`, `updated_at`, `is_active`, `location_group`) VALUES
(1, 'Chauraman', 25.043023, 86.192341, 2147483647, '2025-12-10 19:47:04', '2026-01-11 14:43:49', 1, 'Ville flora');

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `id` int(11) NOT NULL,
  `holiday_name` varchar(100) NOT NULL,
  `holiday_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`id`, `holiday_name`, `holiday_date`, `created_at`, `updated_at`) VALUES
(1, 'Makar Sankranti', '2026-01-14', '2025-12-10 18:38:35', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `looking_for_id` int(11) DEFAULT NULL,
  `lead_source_id` int(11) DEFAULT NULL,
  `sales_person` varchar(100) DEFAULT NULL,
  `profile` varchar(50) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `purpose` varchar(100) DEFAULT NULL,
  `lead_status` enum('Hot','Warm','Cold') DEFAULT 'Warm',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `looking_for_type_id` int(11) DEFAULT NULL,
  `looking_for_subtypes` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leads`
--

INSERT INTO `leads` (`id`, `name`, `contact_number`, `email`, `looking_for_id`, `lead_source_id`, `sales_person`, `profile`, `pincode`, `city`, `state`, `country`, `reference`, `purpose`, `lead_status`, `notes`, `created_at`, `updated_at`, `looking_for_type_id`, `looking_for_subtypes`) VALUES
(1, 'Sachin Mandal', '6352816306', 'sachin.balarbuilders@gmail.com', 1, 3, 'Ganesh Rohit', 'business', '396105', 'Valsad', 'Gujarat', 'India', '', 'Stay', 'Warm', 'okay', '2025-12-29 18:28:08', '2026-01-09 19:49:56', 7, '17,18'),
(2, 'mnm', '', '', 1, 17, 'Ganesh Rohit', '', '', '', '', '', '', '', 'Hot', '', '2026-01-09 19:57:02', '2026-01-09 20:22:40', 7, '18');

-- --------------------------------------------------------

--
-- Table structure for table `lead_looking_for`
--

CREATE TABLE `lead_looking_for` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `status` varchar(32) DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lead_looking_for`
--

INSERT INTO `lead_looking_for` (`id`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Residential', 'active', '2026-01-05 10:01:49', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lead_looking_for_types`
--

CREATE TABLE `lead_looking_for_types` (
  `id` int(11) NOT NULL,
  `looking_for_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lead_looking_for_types`
--

INSERT INTO `lead_looking_for_types` (`id`, `looking_for_id`, `name`) VALUES
(7, 1, 'Villa');

-- --------------------------------------------------------

--
-- Table structure for table `lead_looking_for_type_subtypes`
--

CREATE TABLE `lead_looking_for_type_subtypes` (
  `id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lead_looking_for_type_subtypes`
--

INSERT INTO `lead_looking_for_type_subtypes` (`id`, `type_id`, `name`) VALUES
(19, 7, '6 BHK'),
(18, 7, '4 BHK'),
(17, 7, '3 BHK');

-- --------------------------------------------------------

--
-- Table structure for table `lead_sources`
--

CREATE TABLE `lead_sources` (
  `id` int(11) NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lead_sources`
--

INSERT INTO `lead_sources` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(2, 'D', 'ok', 1, '2025-12-30 16:37:09', '2026-01-05 12:56:13'),
(3, 'hh', '', 1, '2025-12-30 16:56:11', '2025-12-30 16:56:11'),
(4, 'a', '', 1, '2026-01-05 13:24:36', '2026-01-05 13:24:36'),
(5, 'b', '', 1, '2026-01-05 13:24:40', '2026-01-05 13:24:40'),
(6, 'c', '', 1, '2026-01-05 13:24:44', '2026-01-05 13:24:44'),
(7, 'd', '', 1, '2026-01-05 13:24:49', '2026-01-05 13:24:49'),
(8, 'eeeeeeeeeee', '', 1, '2026-01-05 13:24:53', '2026-01-05 14:56:54'),
(9, 'f', '', 1, '2026-01-05 13:24:59', '2026-01-05 13:24:59'),
(10, 'g', '', 1, '2026-01-05 13:25:05', '2026-01-05 13:25:05'),
(11, 'h', '', 1, '2026-01-05 13:25:10', '2026-01-05 13:25:10'),
(12, 'j', '', 1, '2026-01-05 13:25:14', '2026-01-05 13:25:14'),
(13, 'k', '', 1, '2026-01-05 13:25:17', '2026-01-05 13:25:17'),
(14, 'l', '', 1, '2026-01-05 13:25:21', '2026-01-05 13:25:21'),
(15, 'm', '', 1, '2026-01-05 13:25:26', '2026-01-05 13:25:26'),
(16, 'vv', '', 1, '2026-01-05 13:34:43', '2026-01-05 13:34:43'),
(17, 'vv', '', 1, '2026-01-05 13:34:46', '2026-01-05 13:34:46'),
(18, 'd', '', 1, '2026-01-05 13:39:32', '2026-01-05 13:39:32');

-- --------------------------------------------------------

--
-- Table structure for table `leave_applications`
--

CREATE TABLE `leave_applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `reason` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `yearly_quota` int(11) NOT NULL DEFAULT 0,
  `monthly_limit` int(11) DEFAULT NULL,
  `color_hex` varchar(20) DEFAULT '#111827',
  `unused_action` varchar(20) NOT NULL DEFAULT 'lapse',
  `applicability` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_types`
--

INSERT INTO `leave_types` (`id`, `code`, `name`, `yearly_quota`, `monthly_limit`, `color_hex`, `unused_action`, `applicability`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'SL', 'Sick Leave', 10, NULL, '#17d99f', 'lapse', '', 1, '2025-12-21 09:27:01', '2025-12-21 09:34:54');

-- --------------------------------------------------------

--
-- Table structure for table `leave_type_employees`
--

CREATE TABLE `leave_type_employees` (
  `id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_type_employees`
--

INSERT INTO `leave_type_employees` (`id`, `leave_type_id`, `user_id`, `created_at`) VALUES
(14, 1, 5, '2025-12-22 11:18:52'),
(15, 1, 4, '2025-12-22 11:18:52'),
(16, 1, 3, '2025-12-22 11:18:52');

-- --------------------------------------------------------

--
-- Table structure for table `sales_persons`
--

CREATE TABLE `sales_persons` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` tinyint(4) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales_persons`
--

INSERT INTO `sales_persons` (`id`, `user_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2026-01-05 13:04:59', NULL),
(12, 5, 1, '2026-01-09 16:17:09', NULL),
(15, 3, 1, '2026-01-09 20:22:16', NULL),
(18, 4, 1, '2026-01-10 14:08:23', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

CREATE TABLE `shifts` (
  `id` int(11) NOT NULL,
  `shift_name` varchar(50) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `lunch_start` time DEFAULT NULL,
  `lunch_end` time DEFAULT NULL,
  `early_clock_in_before` int(11) NOT NULL DEFAULT 0,
  `late_mark_after` int(11) NOT NULL,
  `half_day_after` int(11) NOT NULL,
  `total_punches` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `shift_color` varchar(20) NOT NULL DEFAULT '#0d6efd'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shifts`
--

INSERT INTO `shifts` (`id`, `shift_name`, `start_time`, `end_time`, `lunch_start`, `lunch_end`, `early_clock_in_before`, `late_mark_after`, `half_day_after`, `total_punches`, `created_at`, `updated_at`, `shift_color`) VALUES
(2, 'Office', '10:00:00', '19:30:00', '13:00:00', '14:00:00', 60, 30, 270, 4, '2025-12-09 12:06:33', '2025-12-28 08:53:11', '#0d6efd'),
(4, 'General', '09:00:00', '21:00:00', '13:00:00', '14:00:00', 60, 10, 360, 4, '2025-12-28 09:51:54', '2026-01-11 10:50:51', '#fd6d0d');

-- --------------------------------------------------------

--
-- Table structure for table `working_from_master`
--

CREATE TABLE `working_from_master` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `working_from_master`
--

INSERT INTO `working_from_master` (`id`, `code`, `label`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Ville Flora', 'Ville Flora', 1, '2025-12-21 07:21:56', '2026-01-11 12:05:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `designations`
--
ALTER TABLE `designations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `designation_id` (`designation_id`);

--
-- Indexes for table `geo_settings`
--
ALTER TABLE `geo_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `holiday_date` (`holiday_date`);

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `looking_for_id` (`looking_for_id`),
  ADD KEY `lead_source_id` (`lead_source_id`);

--
-- Indexes for table `lead_looking_for`
--
ALTER TABLE `lead_looking_for`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lead_looking_for_types`
--
ALTER TABLE `lead_looking_for_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `looking_for_id` (`looking_for_id`);

--
-- Indexes for table `lead_looking_for_type_subtypes`
--
ALTER TABLE `lead_looking_for_type_subtypes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `lead_sources`
--
ALTER TABLE `lead_sources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lead_sources_name` (`name`);

--
-- Indexes for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `leave_type_employees`
--
ALTER TABLE `leave_type_employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_leave_emp` (`leave_type_id`,`user_id`);

--
-- Indexes for table `sales_persons`
--
ALTER TABLE `sales_persons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `shifts`
--
ALTER TABLE `shifts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `working_from_master`
--
ALTER TABLE `working_from_master`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=267;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `designations`
--
ALTER TABLE `designations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `lead_looking_for`
--
ALTER TABLE `lead_looking_for`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `lead_looking_for_types`
--
ALTER TABLE `lead_looking_for_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `lead_looking_for_type_subtypes`
--
ALTER TABLE `lead_looking_for_type_subtypes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `lead_sources`
--
ALTER TABLE `lead_sources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `leave_applications`
--
ALTER TABLE `leave_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leave_type_employees`
--
ALTER TABLE `leave_type_employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `sales_persons`
--
ALTER TABLE `sales_persons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `shifts`
--
ALTER TABLE `shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `working_from_master`
--
ALTER TABLE `working_from_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `designations`
--
ALTER TABLE `designations`
  ADD CONSTRAINT `designations_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `employees_ibfk_2` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

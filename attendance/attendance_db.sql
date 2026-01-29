-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 29, 2026 at 09:28 AM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;


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

DROP TABLE IF EXISTS `attendance_logs`;
CREATE TABLE IF NOT EXISTS `attendance_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `type` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `working_from` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reason` enum('lunch','tea','shift_start','shift_end') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'shift_start',
  `time` datetime NOT NULL,
  `device_id` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `latitude` decimal(10,6) DEFAULT NULL,
  `longitude` decimal(10,6) DEFAULT NULL,
  `synced` tinyint(1) DEFAULT '1',
  `is_auto` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_logs_user_date` (`user_id`,`time`)
) ENGINE=InnoDB AUTO_INCREMENT=344 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_logs`
--

INSERT INTO `attendance_logs` (`id`, `user_id`, `type`, `working_from`, `reason`, `time`, `device_id`, `latitude`, `longitude`, `synced`, `is_auto`) VALUES
(342, '3', 'in', 'Ville Flora', 'shift_start', '2026-01-29 14:42:01', 'AP3A.240905.015.A2', 20.420290, 72.870793, 1, 1),
(343, '3', 'out', 'Ville Flora', 'shift_end', '2026-01-29 14:49:19', 'AP3A.240905.015.A2', 20.420325, 72.870740, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `attendance_settings`
--

DROP TABLE IF EXISTS `attendance_settings`;
CREATE TABLE IF NOT EXISTS `attendance_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_general_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=578 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_settings`
--

INSERT INTO `attendance_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'global_auto_attendance', '1', '2026-01-29 09:05:46'),
(125, 'device_limit', '2', '2026-01-25 09:20:57'),
(140, 'ip_restriction_enabled', '0', '2026-01-25 09:18:22'),
(141, 'allowed_ips', '', '2026-01-25 09:18:22');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
CREATE TABLE IF NOT EXISTS `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `department_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

DROP TABLE IF EXISTS `designations`;
CREATE TABLE IF NOT EXISTS `designations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `department_id` int NOT NULL,
  `designation_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `designations`
--

INSERT INTO `designations` (`id`, `department_id`, `designation_name`, `created_at`, `updated_at`) VALUES
(2, 1, 'HR Executive', '2025-12-09 11:28:17', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
CREATE TABLE IF NOT EXISTS `employees` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `emp_code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `mobile` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `designation_id` int DEFAULT NULL,
  `shift_id` int DEFAULT NULL,
  `default_working_from` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `weekoff_days` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `device_id` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  KEY `department_id` (`department_id`),
  KEY `designation_id` (`designation_id`),
  KEY `idx_emp_dept` (`department_id`),
  KEY `idx_emp_shift` (`shift_id`),
  KEY `idx_emp_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`user_id`, `emp_code`, `name`, `mobile`, `email`, `dob`, `department_id`, `designation_id`, `shift_id`, `default_working_from`, `weekoff_days`, `joining_date`, `updated_at`, `device_id`, `status`, `created_at`) VALUES
(3, 'EMP001', 'Sachin Mandal', '6352816306', 'sachin.balarbuilders@gmail.com', '2001-12-28', 1, 2, 2, 'Ville Flora', 'Wednesday', '2025-12-28', '2026-01-25 10:12:14', NULL, 1, '2025-12-09 20:17:04'),
(4, 'EMP002', 'Harish Thapa', '6352816306', 'harish.balarbuilders@gmail.com', '2025-12-29', 1, 2, 4, 'Ville Flora', 'Thursday', '2025-12-31', '2025-12-28 12:33:13', NULL, 1, '2025-12-10 09:10:56'),
(5, 'EMP005', 'Ganesh Rohit', '', 'ganesh.balarbuilders@gmail.com', '0000-00-00', 1, 2, 2, 'Ville Flora', NULL, '2025-12-22', '2026-01-10 11:19:53', NULL, 1, '2025-12-22 11:18:40');

-- --------------------------------------------------------

--
-- Table structure for table `employee_devices`
--

DROP TABLE IF EXISTS `employee_devices`;
CREATE TABLE IF NOT EXISTS `employee_devices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `device_id` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`device_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_devices`
--

INSERT INTO `employee_devices` (`id`, `user_id`, `device_id`, `created_at`) VALUES
(4, 3, 'AP3A.240905.015.A2', '2026-01-25 10:12:31');

-- --------------------------------------------------------

--
-- Table structure for table `geo_settings`
--

DROP TABLE IF EXISTS `geo_settings`;
CREATE TABLE IF NOT EXISTS `geo_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `location_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Office',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `location_group` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `geofence_polygon` longtext COLLATE utf8mb4_general_ci,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `radius_meters` int DEFAULT '150',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `geo_settings`
--

INSERT INTO `geo_settings` (`id`, `location_name`, `created_at`, `updated_at`, `is_active`, `location_group`, `geofence_polygon`, `latitude`, `longitude`, `radius_meters`) VALUES
(2, 'Ville Flora', '2026-01-29 09:05:29', NULL, 1, 'Kunta', '[[72.87066018168956,20.42009206795784],[72.87096867803032,20.421615646186414],[72.87041866164246,20.42190358587029],[72.87083591543859,20.423211749540524],[72.8718297381318,20.42369519851215],[72.87671919408285,20.423940477180764],[72.87676091946521,20.422461689681935],[72.8756077816633,20.42210620979175],[72.87242527307937,20.42205288773622],[72.8724101002408,20.422056442541198],[72.87233423591144,20.422451025285422],[72.87179939239485,20.422419032127785],[72.87116213204163,20.420001752046037]]', 20.42197110, 72.87358980, 590);

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

DROP TABLE IF EXISTS `holidays`;
CREATE TABLE IF NOT EXISTS `holidays` (
  `id` int NOT NULL AUTO_INCREMENT,
  `holiday_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `holiday_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `holiday_date` (`holiday_date`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`id`, `holiday_name`, `holiday_date`, `created_at`, `updated_at`) VALUES
(1, 'Makar Sankranti', '2026-01-14', '2025-12-10 18:38:35', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

DROP TABLE IF EXISTS `leads`;
CREATE TABLE IF NOT EXISTS `leads` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `looking_for_id` int DEFAULT NULL,
  `lead_source_id` int DEFAULT NULL,
  `sales_person` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pincode` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purpose` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lead_status` enum('Hot','Warm','Cold') COLLATE utf8mb4_unicode_ci DEFAULT 'Warm',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `looking_for_type_id` int DEFAULT NULL,
  `looking_for_subtypes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `looking_for_id` (`looking_for_id`),
  KEY `lead_source_id` (`lead_source_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

DROP TABLE IF EXISTS `lead_looking_for`;
CREATE TABLE IF NOT EXISTS `lead_looking_for` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lead_looking_for`
--

INSERT INTO `lead_looking_for` (`id`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Residential', 'active', '2026-01-05 10:01:49', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lead_looking_for_types`
--

DROP TABLE IF EXISTS `lead_looking_for_types`;
CREATE TABLE IF NOT EXISTS `lead_looking_for_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `looking_for_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `looking_for_id` (`looking_for_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lead_looking_for_types`
--

INSERT INTO `lead_looking_for_types` (`id`, `looking_for_id`, `name`) VALUES
(7, 1, 'Villa');

-- --------------------------------------------------------

--
-- Table structure for table `lead_looking_for_type_subtypes`
--

DROP TABLE IF EXISTS `lead_looking_for_type_subtypes`;
CREATE TABLE IF NOT EXISTS `lead_looking_for_type_subtypes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `type_id` (`type_id`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

DROP TABLE IF EXISTS `lead_sources`;
CREATE TABLE IF NOT EXISTS `lead_sources` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lead_sources_name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(8, 'ee', '', 1, '2026-01-05 13:24:53', '2026-01-25 14:02:27'),
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

DROP TABLE IF EXISTS `leave_applications`;
CREATE TABLE IF NOT EXISTS `leave_applications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `leave_type_id` int NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_leaves_user` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

DROP TABLE IF EXISTS `leave_types`;
CREATE TABLE IF NOT EXISTS `leave_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `yearly_quota` int NOT NULL DEFAULT '0',
  `monthly_limit` int DEFAULT NULL,
  `color_hex` varchar(20) COLLATE utf8mb4_general_ci DEFAULT '#111827',
  `unused_action` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'lapse',
  `applicability` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_types`
--

INSERT INTO `leave_types` (`id`, `code`, `name`, `yearly_quota`, `monthly_limit`, `color_hex`, `unused_action`, `applicability`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'SL', 'Sick Leave', 10, NULL, '#17d99f', 'lapse', '', 1, '2025-12-21 09:27:01', '2025-12-21 09:34:54');

-- --------------------------------------------------------

--
-- Table structure for table `leave_type_employees`
--

DROP TABLE IF EXISTS `leave_type_employees`;
CREATE TABLE IF NOT EXISTS `leave_type_employees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `leave_type_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_leave_emp` (`leave_type_id`,`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

DROP TABLE IF EXISTS `sales_persons`;
CREATE TABLE IF NOT EXISTS `sales_persons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `status` tinyint DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales_persons`
--

INSERT INTO `sales_persons` (`id`, `user_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2026-01-05 13:04:59', NULL),
(12, 5, 1, '2026-01-09 16:17:09', NULL),
(15, 3, 1, '2026-01-09 20:22:16', NULL),
(22, 4, 1, '2026-01-25 16:09:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

DROP TABLE IF EXISTS `shifts`;
CREATE TABLE IF NOT EXISTS `shifts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `shift_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `lunch_start` time DEFAULT NULL,
  `lunch_end` time DEFAULT NULL,
  `early_clock_in_before` int NOT NULL DEFAULT '0',
  `late_mark_after` int NOT NULL,
  `half_day_after` int NOT NULL,
  `total_punches` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `shift_color` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '#0d6efd',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

DROP TABLE IF EXISTS `working_from_master`;
CREATE TABLE IF NOT EXISTS `working_from_master` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `label` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `working_from_master`
--

INSERT INTO `working_from_master` (`id`, `code`, `label`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Ville Flora', 'Ville Flora', 1, '2025-12-21 07:21:56', '2026-01-11 12:05:02');

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
SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

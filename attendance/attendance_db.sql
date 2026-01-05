-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 05, 2026 at 11:20 AM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=237 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_logs`
--

INSERT INTO `attendance_logs` (`id`, `user_id`, `type`, `working_from`, `reason`, `time`, `device_id`, `latitude`, `longitude`, `synced`) VALUES
(144, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 11:54:18', 'flutter_device', NULL, NULL, 1),
(145, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 12:27:01', 'flutter_device', NULL, NULL, 1),
(146, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 12:39:21', 'flutter_device', NULL, NULL, 1),
(147, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 12:46:59', 'flutter_device', NULL, NULL, 1),
(148, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 12:49:02', 'flutter_device', NULL, NULL, 1),
(149, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 12:49:10', 'flutter_device', NULL, NULL, 1),
(150, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 12:49:43', 'flutter_device', NULL, NULL, 1),
(151, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 12:49:44', 'flutter_device', NULL, NULL, 1),
(152, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 12:49:45', 'flutter_device', NULL, NULL, 1),
(153, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 12:49:45', 'flutter_device', NULL, NULL, 1),
(154, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 12:49:47', 'flutter_device', NULL, NULL, 1),
(155, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 12:49:48', 'flutter_device', NULL, NULL, 1),
(156, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 12:49:49', 'flutter_device', NULL, NULL, 1),
(157, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 12:50:08', 'flutter_device', NULL, NULL, 1),
(158, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 12:50:12', 'flutter_device', NULL, NULL, 1),
(159, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 12:51:31', 'flutter_device', NULL, NULL, 1),
(160, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 12:51:47', 'flutter_device', NULL, NULL, 1),
(161, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 12:51:47', 'flutter_device', NULL, NULL, 1),
(162, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 12:51:59', 'flutter_device', NULL, NULL, 1),
(163, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 12:53:08', 'flutter_device', NULL, NULL, 1),
(164, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 12:55:30', 'flutter_device', NULL, NULL, 1),
(165, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 12:55:34', 'flutter_device', NULL, NULL, 1),
(166, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 12:55:37', 'flutter_device', NULL, NULL, 1),
(167, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 12:55:54', 'flutter_device', NULL, NULL, 1),
(168, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 12:55:57', 'flutter_device', NULL, NULL, 1),
(169, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 12:57:00', 'flutter_device', NULL, NULL, 1),
(170, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 12:57:04', 'flutter_device', NULL, NULL, 1),
(171, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 12:57:10', 'flutter_device', NULL, NULL, 1),
(172, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 12:59:43', 'flutter_device', NULL, NULL, 1),
(173, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 13:01:01', 'flutter_device', NULL, NULL, 1),
(174, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 13:02:50', 'flutter_device', NULL, NULL, 1),
(175, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 13:02:50', 'flutter_device', NULL, NULL, 1),
(176, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 13:02:56', 'flutter_device', NULL, NULL, 1),
(177, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 13:05:20', 'flutter_device', NULL, NULL, 1),
(178, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 13:05:24', 'flutter_device', NULL, NULL, 1),
(179, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 13:06:08', 'flutter_device', NULL, NULL, 1),
(180, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 13:06:12', 'flutter_device', NULL, NULL, 1),
(181, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 13:06:16', 'flutter_device', NULL, NULL, 1),
(182, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 13:06:19', 'flutter_device', NULL, NULL, 1),
(183, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 13:08:42', 'flutter_device', NULL, NULL, 1),
(184, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 13:08:46', 'flutter_device', NULL, NULL, 1),
(185, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 13:08:48', 'flutter_device', NULL, NULL, 1),
(186, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 13:11:05', 'flutter_device', NULL, NULL, 1),
(187, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 13:11:09', 'flutter_device', NULL, NULL, 1),
(188, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 13:11:52', 'flutter_device', NULL, NULL, 1),
(189, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 13:12:21', 'flutter_device', NULL, NULL, 1),
(190, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 13:12:26', 'flutter_device', NULL, NULL, 1),
(191, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 13:12:36', 'flutter_device', NULL, NULL, 1),
(192, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 13:12:40', 'flutter_device', NULL, NULL, 1),
(193, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 13:12:45', 'flutter_device', NULL, NULL, 1),
(194, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 13:14:37', 'flutter_device', NULL, NULL, 1),
(195, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 13:14:43', 'flutter_device', NULL, NULL, 1),
(196, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 13:19:39', 'flutter_device', NULL, NULL, 1),
(197, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 14:13:11', 'flutter_device', NULL, NULL, 1),
(198, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 14:39:58', 'flutter_device', NULL, NULL, 1),
(199, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 14:40:01', 'flutter_device', NULL, NULL, 1),
(200, '1', 'out', 'Ville Flora', 'shift_end', '2025-12-28 14:40:03', 'flutter_device', NULL, NULL, 1),
(201, '1', 'in', 'Ville Flora', 'shift_start', '2025-12-28 14:40:04', 'flutter_device', NULL, NULL, 1),
(202, '3', 'in', 'Ville Flora', 'shift_start', '2025-12-28 14:55:19', 'flutter_device', NULL, NULL, 1),
(203, '3', 'out', 'Ville Flora', 'shift_end', '2025-12-28 14:55:23', 'flutter_device', NULL, NULL, 1),
(204, '3', 'in', 'Ville Flora', 'shift_start', '2025-12-28 14:55:33', 'flutter_device', NULL, NULL, 1),
(205, '3', 'out', 'Ville Flora', 'shift_end', '2025-12-28 15:10:26', 'flutter_device', NULL, NULL, 1),
(206, '3', 'in', 'Ville Flora', 'shift_start', '2025-12-28 15:12:12', 'flutter_device', NULL, NULL, 1),
(207, '3', 'in', 'Ville Flora', 'shift_start', '2025-12-28 15:50:49', 'flutter_device', NULL, NULL, 1),
(208, '3', 'out', 'Ville Flora', 'shift_end', '2025-12-28 15:52:36', 'flutter_device', NULL, NULL, 1),
(209, '3', 'in', 'Ville Flora', 'shift_start', '2025-12-28 15:52:38', 'flutter_device', NULL, NULL, 1),
(210, '3', 'out', 'Ville Flora', 'shift_end', '2025-12-28 15:52:40', 'flutter_device', NULL, NULL, 1),
(211, '3', 'in', 'Ville Flora', 'shift_start', '2025-12-28 15:52:42', 'flutter_device', NULL, NULL, 1),
(212, '3', 'out', 'Ville Flora', 'shift_end', '2025-12-28 15:52:44', 'flutter_device', NULL, NULL, 1),
(213, '3', 'in', 'Ville Flora', 'shift_start', '2025-12-28 15:57:08', 'flutter_device', NULL, NULL, 1),
(214, '3', 'out', 'Ville Flora', 'shift_end', '2025-12-28 15:57:12', 'flutter_device', NULL, NULL, 1),
(215, '3', 'in', 'Ville Flora', 'shift_start', '2025-12-28 16:00:22', 'flutter_device', NULL, NULL, 1),
(216, '3', 'out', 'Ville Flora', 'shift_end', '2025-12-28 16:00:24', 'flutter_device', NULL, NULL, 1),
(217, '3', 'in', 'Ville Flora', 'shift_start', '2025-12-28 16:02:41', 'flutter_device', NULL, NULL, 1),
(218, '3', 'in', 'Ville Flora', 'shift_start', '2025-12-28 16:13:50', 'flutter_device', NULL, NULL, 1),
(219, '3', 'out', 'Ville Flora', 'shift_end', '2025-12-28 16:14:01', 'flutter_device', NULL, NULL, 1),
(220, '3', 'in', 'Ville Flora', 'shift_start', '2025-12-28 16:24:02', 'flutter_device', NULL, NULL, 1),
(221, '3', 'out', 'Ville Flora', 'shift_end', '2025-12-28 16:24:05', 'flutter_device', NULL, NULL, 1),
(222, '3', 'in', 'Ville Flora', 'shift_start', '2025-12-28 16:34:28', 'flutter_device', NULL, NULL, 1),
(223, '3', 'in', 'Ville Flora', 'shift_start', '2025-12-28 17:02:53', 'flutter_device', NULL, NULL, 1),
(224, '3', 'out', 'Ville Flora', 'shift_end', '2025-12-28 17:02:58', 'flutter_device', NULL, NULL, 1),
(225, '3', 'in', 'Ville Flora', 'shift_start', '2025-12-28 17:12:20', 'flutter_device', NULL, NULL, 1),
(226, '3', 'in', 'Ville Flora', 'shift_start', '2025-12-28 17:37:13', 'flutter_device', NULL, NULL, 1),
(227, '3', 'leave', NULL, '', '2025-12-29 00:00:00', '', NULL, NULL, 1),
(228, '3', 'leave', NULL, '', '2025-12-31 00:00:00', '', NULL, NULL, 1),
(229, '3', 'leave', NULL, '', '2025-12-30 00:00:00', '', NULL, NULL, 1),
(230, '3', 'in', 'Ville Flora', 'shift_start', '2025-12-28 18:19:20', 'flutter_device', NULL, NULL, 1),
(231, '3', 'out', 'Ville Flora', 'shift_end', '2025-12-28 18:19:23', 'flutter_device', NULL, NULL, 1),
(232, '3', 'in', 'Ville Flora', 'shift_start', '2025-12-28 18:25:24', 'flutter_device', NULL, NULL, 1),
(233, '3', 'in', 'Ville Flora', 'shift_start', '2025-12-28 18:57:42', 'flutter_device', NULL, NULL, 1),
(234, '3', 'leave', NULL, '', '2026-01-03 00:00:00', '', NULL, NULL, 1),
(235, '3', 'in', 'Ville Flora', 'shift_start', '2025-12-28 19:06:15', 'flutter_device', NULL, NULL, 1),
(236, '3', 'in', 'Ville Flora', 'shift_start', '2025-12-28 19:08:14', 'flutter_device', NULL, NULL, 1);

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `department_name`, `created_at`, `updated_at`) VALUES
(1, 'HR', '2025-12-09 11:27:17', NULL);

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
  `id` int NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`),
  KEY `designation_id` (`designation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `emp_code`, `name`, `mobile`, `email`, `dob`, `department_id`, `designation_id`, `shift_id`, `default_working_from`, `weekoff_days`, `joining_date`, `updated_at`, `device_id`, `status`, `created_at`) VALUES
(3, 'EMP001', 'Sachin Mandal', '6352816306', 'sachin.balarbuilders@gmail.com', '2001-12-28', 1, 2, 2, 'Ville Flora', 'Wednesday', '2025-12-28', '2025-12-28 11:21:23', 'flutter_device', 1, '2025-12-09 20:17:04'),
(4, 'EMP002', 'Harish Thapa', '6352816306', 'harish.balarbuilders@gmail.com', '2025-12-29', 1, 2, 4, 'Ville Flora', 'Thursday', '2025-12-31', '2025-12-28 12:33:13', NULL, 1, '2025-12-10 09:10:56'),
(5, 'EMP005', 'Ganesh Rohit', '', 'ganesh.balarbuilders@gmail.com', '0000-00-00', 1, 2, 2, 'Ville Flora', NULL, '2025-12-22', NULL, NULL, 1, '2025-12-22 11:18:40');

-- --------------------------------------------------------

--
-- Table structure for table `geo_settings`
--

DROP TABLE IF EXISTS `geo_settings`;
CREATE TABLE IF NOT EXISTS `geo_settings` (
  `id` int NOT NULL,
  `location_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Office',
  `latitude` decimal(10,6) NOT NULL,
  `longitude` decimal(10,6) NOT NULL,
  `radius_meters` int NOT NULL DEFAULT '150',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `geo_settings`
--

INSERT INTO `geo_settings` (`id`, `location_name`, `latitude`, `longitude`, `radius_meters`, `created_at`, `updated_at`) VALUES
(1, 'Ville Flora', 20.420399, 72.870863, 150, '2025-12-10 19:47:04', '2025-12-21 07:23:20');

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
  PRIMARY KEY (`id`),
  KEY `looking_for_id` (`looking_for_id`),
  KEY `lead_source_id` (`lead_source_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leads`
--

INSERT INTO `leads` (`id`, `name`, `contact_number`, `email`, `looking_for_id`, `lead_source_id`, `sales_person`, `profile`, `pincode`, `city`, `state`, `country`, `reference`, `purpose`, `lead_status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Sachin Mandal', '6352816306', 'sachin.balarbuilders@gmail.com', 1, 2, 'Sachin Mandal', 'business', '396105', 'Valsad', 'Gujarat', 'India', '', 'Stay', 'Hot', 'asa', '2025-12-29 18:28:08', '2026-01-05 16:36:00');

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
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lead_looking_for_types`
--

INSERT INTO `lead_looking_for_types` (`id`, `looking_for_id`, `name`) VALUES
(4, 1, 'Villa');

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
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lead_looking_for_type_subtypes`
--

INSERT INTO `lead_looking_for_type_subtypes` (`id`, `type_id`, `name`) VALUES
(10, 4, '6 BHK'),
(9, 4, '4 BHK'),
(8, 4, '3 BHK');

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

DROP TABLE IF EXISTS `leave_applications`;
CREATE TABLE IF NOT EXISTS `leave_applications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `leave_type_id` int NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_applications`
--

INSERT INTO `leave_applications` (`id`, `employee_id`, `leave_type_id`, `from_date`, `to_date`, `reason`, `status`, `created_at`) VALUES
(26, 3, 1, '2025-12-28', '2025-12-28', 'sick', 'approved', '2025-12-28 12:37:35'),
(25, 3, 1, '2025-12-30', '2025-12-30', 'sick', 'approved', '2025-12-28 12:37:27'),
(24, 3, 1, '2025-12-29', '2025-12-29', 'sick', 'approved', '2025-12-28 12:36:42'),
(23, 5, 1, '2025-12-23', '2025-12-23', 'jk', 'approved', '2025-12-22 13:17:39'),
(27, 3, 1, '2025-12-31', '2025-12-31', 'sick', 'approved', '2025-12-28 12:38:31'),
(28, 3, 1, '2026-01-01', '2026-01-03', 'ok', 'cancelled', '2025-12-28 12:46:51'),
(29, 3, 1, '2026-01-03', '2026-01-03', 'ok', 'approved', '2025-12-28 13:21:04');

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
  `employee_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_leave_emp` (`leave_type_id`,`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_type_employees`
--

INSERT INTO `leave_type_employees` (`id`, `leave_type_id`, `employee_id`, `created_at`) VALUES
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
  `employee_id` int NOT NULL,
  `status` tinyint DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sales_persons`
--

INSERT INTO `sales_persons` (`id`, `employee_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2026-01-05 13:04:59', NULL),
(3, 4, 1, '2026-01-05 13:13:20', NULL),
(4, 5, 1, '2026-01-05 13:20:04', NULL),
(6, 3, 1, '2026-01-05 13:23:45', NULL);

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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shifts`
--

INSERT INTO `shifts` (`id`, `shift_name`, `start_time`, `end_time`, `lunch_start`, `lunch_end`, `early_clock_in_before`, `late_mark_after`, `half_day_after`, `total_punches`, `created_at`, `updated_at`) VALUES
(2, 'Office', '10:00:00', '19:30:00', '13:00:00', '14:00:00', 60, 30, 270, 4, '2025-12-09 12:06:33', '2025-12-28 08:53:11'),
(4, 'General', '09:00:00', '21:00:00', NULL, NULL, 0, 10, 360, 4, '2025-12-28 09:51:54', NULL);

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
(1, 'Ville Flora', 'Ville Flora', 1, '2025-12-21 07:21:56', '2025-12-21 07:22:30');

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

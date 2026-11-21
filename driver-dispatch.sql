-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 12, 2026 at 10:10 AM
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
-- Database: `driver-dispatch`
--

-- --------------------------------------------------------

--
-- Table structure for table `attachments`
--

CREATE TABLE `attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `attachable_type` varchar(255) NOT NULL,
  `attachable_id` bigint(20) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `mime_type` varchar(255) NOT NULL,
  `file_size` int(10) UNSIGNED NOT NULL,
  `uploaded_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `module` varchar(255) NOT NULL,
  `record_type` varchar(255) DEFAULT NULL,
  `record_id` bigint(20) UNSIGNED DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `module`, `record_type`, `record_id`, `old_value`, `new_value`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(52, 57, 'UPDATE_VEHICLE_DRIVERS', 'Vehicle', 'Vehicle', 16, '[]', '[22,23,24,20]', '118.99.74.131', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 20:15:50', '2026-11-11 20:15:50'),
(53, 57, 'UPDATE_VEHICLE_DRIVERS', 'Vehicle', 'Vehicle', 17, '[]', '[21,23]', '118.99.74.131', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 20:16:54', '2026-11-11 20:16:54'),
(54, 57, 'UPDATE_VEHICLE_DRIVERS', 'Vehicle', 'Vehicle', 18, '[]', '[23,21]', '118.99.74.131', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 20:17:51', '2026-11-11 20:17:51'),
(55, 57, 'UPDATE_VEHICLE_DRIVERS', 'Vehicle', 'Vehicle', 19, '[]', '[23,20]', '118.99.74.131', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 20:18:26', '2026-11-11 20:18:26'),
(56, 57, 'UPDATE_VEHICLE_DRIVERS', 'Vehicle', 'Vehicle', 20, '[]', '[24,21]', '118.99.74.131', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 20:18:42', '2026-11-11 20:18:42'),
(57, 57, 'UPDATE_VEHICLE_DRIVERS', 'Vehicle', 'Vehicle', 21, '[]', '[20,22,23,24]', '118.99.74.131', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 20:19:03', '2026-11-11 20:19:03'),
(58, 57, 'UPDATE_VEHICLE_DRIVERS', 'Vehicle', 'Vehicle', 22, '[]', '[20,23,19]', '118.99.74.131', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 20:19:44', '2026-11-11 20:19:44'),
(59, 57, 'UPDATE_VEHICLE_DRIVERS', 'Vehicle', 'Vehicle', 23, '[]', '[19]', '118.99.74.131', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 20:20:06', '2026-11-11 20:20:06'),
(60, 57, 'UPDATE_VEHICLE_DRIVERS', 'Vehicle', 'Vehicle', 24, '[]', '[19,20,23]', '118.99.74.131', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 20:20:36', '2026-11-11 20:20:36'),
(61, 57, 'UPDATE_VEHICLE_DRIVERS', 'Vehicle', 'Vehicle', 25, '[]', '[19,20,21,22,23,24]', '118.99.74.131', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 20:20:51', '2026-11-11 20:20:51'),
(62, 57, 'UPDATE_VEHICLE_DRIVERS', 'Vehicle', 'Vehicle', 26, '[]', '[22,24,20]', '118.99.74.131', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', '2026-11-11 20:21:29', '2026-11-11 20:21:29');

-- --------------------------------------------------------

--
-- Table structure for table `berita_acaras`
--

CREATE TABLE `berita_acaras` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ba_number` varchar(255) NOT NULL,
  `trip_request_id` bigint(20) UNSIGNED NOT NULL,
  `driver_id` bigint(20) UNSIGNED DEFAULT NULL,
  `vehicle_id` bigint(20) UNSIGNED DEFAULT NULL,
  `requestor_name` varchar(255) NOT NULL,
  `department_name` varchar(255) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `purpose` text NOT NULL,
  `departure_time` datetime NOT NULL,
  `return_time` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `signed_by` varchar(255) DEFAULT NULL,
  `signed_position` varchar(255) DEFAULT NULL,
  `signed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-superadkun@example.com|118.99.74.131', 'i:1;', 1794448223),
('laravel-cache-superadkun@example.com|118.99.74.131:timer', 'i:1794448223;', 1794448223);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `head_name` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `code`, `name`, `head_name`, `notes`, `created_at`, `updated_at`) VALUES
(22, 'HRD', 'Human Resource Development', 'Ade Budi', NULL, '2026-11-11 18:51:06', '2026-11-11 18:51:06'),
(23, 'IT', 'INFORMATIC TECHNOLOGY', 'Ade Budi', NULL, '2026-11-11 18:51:48', '2026-11-11 18:51:48'),
(24, 'FNC', 'FINANCE', 'Ade Budi', NULL, '2026-11-11 18:52:24', '2026-11-11 18:52:24'),
(25, 'PRCS', 'PURCHASING', 'INTAN', NULL, '2026-11-11 18:53:48', '2026-11-11 18:53:48'),
(26, 'UM', 'UMUM', 'TANDI', NULL, '2026-11-11 18:54:09', '2026-11-11 18:54:09'),
(27, 'ACTG', 'ACCOUNTING', 'ACTG', NULL, '2026-11-11 18:55:04', '2026-11-11 18:55:26'),
(28, 'SLS', 'SALES', 'JENNY', NULL, '2026-11-11 18:56:03', '2026-11-11 18:56:03'),
(29, 'RJS', 'RUPAT JAYA SEJAHTERA', 'WIDIANTI', 'WIDIANTI', '2026-11-11 18:56:23', '2026-11-11 18:56:48'),
(30, 'DV', 'DRIVER', NULL, NULL, '2026-11-11 18:57:38', '2026-11-11 18:57:38'),
(31, 'BOD', 'Board Of Directors', 'Mariya', NULL, '2026-11-11 20:22:24', '2026-11-11 20:22:24');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `license_number` varchar(255) NOT NULL,
  `vehicle_plate` varchar(255) DEFAULT NULL,
  `vehicle_type` varchar(255) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `status` enum('STANDBY','ASSIGNED','ACCEPTED','ON_TRIP','RETURNING','COMPLETED','OFF_DUTY','LEAVE','SICK','UNAVAILABLE') NOT NULL DEFAULT 'STANDBY',
  `willing_to_load` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `last_status_change_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `user_id`, `license_number`, `vehicle_plate`, `vehicle_type`, `phone`, `status`, `willing_to_load`, `notes`, `last_status_change_at`, `created_at`, `updated_at`) VALUES
(19, 80, 'Sim A 00001', NULL, 'Truk', '085274681911', 'STANDBY', 1, NULL, NULL, '2026-11-11 18:59:52', '2026-11-11 18:59:52'),
(20, 81, 'Sim A 00002', NULL, 'Pribadi', '085355538899', 'STANDBY', 1, NULL, NULL, '2026-11-11 19:02:19', '2026-11-11 19:02:19'),
(21, 82, 'Sim A 00003', NULL, 'Pribadi', '082312703411', 'STANDBY', 1, NULL, NULL, '2026-11-11 19:04:37', '2026-11-11 19:04:37'),
(22, 83, 'Sim A 00004', NULL, 'Pribadi', '081275266047', 'STANDBY', 1, NULL, NULL, '2026-11-11 19:06:34', '2026-11-11 19:06:34'),
(23, 84, 'Sim A 00005', NULL, 'Pribadi', '0895367202171', 'STANDBY', 1, NULL, NULL, '2026-11-11 19:08:41', '2026-11-11 19:08:41'),
(24, 85, 'Sim A 00006', NULL, 'Pribadi', '082164473015', 'STANDBY', 1, NULL, NULL, '2026-11-11 19:22:33', '2026-11-11 19:22:33');

-- --------------------------------------------------------

--
-- Table structure for table `driver_status_logs`
--

CREATE TABLE `driver_status_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `driver_id` bigint(20) UNSIGNED NOT NULL,
  `from_status` enum('STANDBY','ASSIGNED','ACCEPTED','ON_TRIP','RETURNING','COMPLETED','OFF_DUTY','LEAVE','SICK','UNAVAILABLE') NOT NULL,
  `to_status` enum('STANDBY','ASSIGNED','ACCEPTED','ON_TRIP','RETURNING','COMPLETED','OFF_DUTY','LEAVE','SICK','UNAVAILABLE') NOT NULL,
  `reason` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `driver_vehicle`
--

CREATE TABLE `driver_vehicle` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `driver_id` bigint(20) UNSIGNED NOT NULL,
  `vehicle_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `driver_vehicle`
--

INSERT INTO `driver_vehicle` (`id`, `driver_id`, `vehicle_id`, `created_at`, `updated_at`) VALUES
(45, 22, 16, NULL, NULL),
(46, 23, 16, NULL, NULL),
(47, 24, 16, NULL, NULL),
(48, 20, 16, NULL, NULL),
(49, 21, 17, NULL, NULL),
(50, 23, 17, NULL, NULL),
(51, 23, 18, NULL, NULL),
(52, 21, 18, NULL, NULL),
(53, 23, 19, NULL, NULL),
(54, 20, 19, NULL, NULL),
(55, 24, 20, NULL, NULL),
(56, 21, 20, NULL, NULL),
(57, 20, 21, NULL, NULL),
(58, 22, 21, NULL, NULL),
(59, 23, 21, NULL, NULL),
(60, 24, 21, NULL, NULL),
(61, 20, 22, NULL, NULL),
(62, 23, 22, NULL, NULL),
(63, 19, 22, NULL, NULL),
(64, 19, 23, NULL, NULL),
(65, 19, 24, NULL, NULL),
(66, 20, 24, NULL, NULL),
(67, 23, 24, NULL, NULL),
(68, 19, 25, NULL, NULL),
(69, 20, 25, NULL, NULL),
(70, 21, 25, NULL, NULL),
(71, 22, 25, NULL, NULL),
(72, 23, 25, NULL, NULL),
(73, 24, 25, NULL, NULL),
(74, 22, 26, NULL, NULL),
(75, 24, 26, NULL, NULL),
(76, 20, 26, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loading_requests`
--

CREATE TABLE `loading_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `trip_request_id` bigint(20) UNSIGNED NOT NULL,
  `driver_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('PENDING','ACCEPTED','COMPLETED','CANCELLED') NOT NULL DEFAULT 'PENDING',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_10_02_010000_create_departments_table', 1),
(5, '2026_10_02_011000_create_roles_table', 1),
(6, '2026_10_02_012000_create_permissions_table', 1),
(7, '2026_10_02_013000_create_role_has_permissions_table', 1),
(8, '2026_10_02_014000_add_fields_to_users_table', 1),
(9, '2026_10_02_015000_create_drivers_table', 1),
(10, '2026_10_02_016000_create_driver_status_logs_table', 1),
(11, '2026_10_02_017000_create_vehicles_table', 1),
(12, '2026_10_02_018000_create_vehicle_status_logs_table', 1),
(13, '2026_10_02_019000_create_trip_requests_table', 1),
(14, '2026_10_02_020000_create_trip_related_tables', 1),
(15, '2026_10_02_021000_add_trip_execution_fields', 1),
(16, '2026_10_02_022000_create_loading_requests_table', 1),
(17, '2026_10_02_023000_create_berita_acaras_table', 1),
(18, '2026_10_02_024000_create_attachments_and_audit_logs_table', 1),
(19, '2026_10_09_000000_create_driver_vehicle_table', 1),
(20, '2026_10_09_035330_create_notifications_table', 1),
(21, '2026_10_13_000000_add_request_type_to_trip_requests_table', 1),
(22, '2026_10_14_000000_make_estimated_return_time_nullable_on_trip_requests_table', 1),
(23, '2026_10_14_010000_add_priority_request_to_trip_requests_table', 1),
(24, '2026_10_15_000000_create_settings_table', 1),
(25, '2026_10_16_042459_create_personal_access_tokens_table', 1),
(27, '2026_11_09_040138_convert_existing_trip_numbers_to_year_format', 2),
(28, '2026_11_09_042936_make_vehicle_id_nullable_on_trip_assignments', 3);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) UNSIGNED NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `group` varchar(255) NOT NULL DEFAULT 'general',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `display_name`, `description`, `group`, `created_at`, `updated_at`) VALUES
(37, 'manage_users', 'Manage Users', NULL, 'users', '2026-10-18 18:17:47', '2026-10-18 18:17:47'),
(38, 'manage_roles', 'Manage Roles', NULL, 'roles', '2026-10-18 18:17:47', '2026-10-18 18:17:47'),
(39, 'manage_departments', 'Manage Departments', NULL, 'departments', '2026-10-18 18:17:47', '2026-10-18 18:17:47'),
(40, 'manage_drivers', 'Manage Drivers', NULL, 'drivers', '2026-10-18 18:17:47', '2026-10-18 18:17:47'),
(41, 'manage_vehicles', 'Manage Vehicles', NULL, 'vehicles', '2026-10-18 18:17:47', '2026-10-18 18:17:47'),
(42, 'manage_trips', 'Manage Trips', NULL, 'trips', '2026-10-18 18:17:47', '2026-10-18 18:17:47'),
(43, 'dispatch_trips', 'Dispatch Trips', NULL, 'dispatch', '2026-10-18 18:17:47', '2026-10-18 18:17:47'),
(44, 'approve_trips', 'Approve Trips', NULL, 'approval', '2026-10-18 18:17:47', '2026-10-18 18:17:47'),
(45, 'create_trips', 'Create Trips', NULL, 'trips', '2026-10-18 18:17:47', '2026-10-18 18:17:47'),
(46, 'view_reports', 'View Reports', NULL, 'reports', '2026-10-18 18:17:47', '2026-10-18 18:17:47'),
(47, 'view_audit_log', 'View Audit Log', NULL, 'audit', '2026-10-18 18:17:47', '2026-10-18 18:17:47'),
(48, 'generate_ba', 'Generate Berita Acara', NULL, 'ba', '2026-10-18 18:17:47', '2026-10-18 18:17:47');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `created_at`, `updated_at`) VALUES
(19, 'SUPERADMIN', 'Super Admin', 'Full system access', '2026-10-18 18:17:47', '2026-10-18 18:17:47'),
(20, 'ADMIN', 'Admin', 'Manage master data, drivers, vehicles, departments, trips', '2026-10-18 18:17:47', '2026-10-18 18:17:47'),
(21, 'DISPATCHER', 'Dispatcher', 'Dispatch drivers and vehicles', '2026-10-18 18:17:47', '2026-10-18 18:17:47'),
(22, 'APPROVER', 'Approver', 'Approve or reject trip requests', '2026-10-18 18:17:47', '2026-10-18 18:17:47'),
(23, 'EMPLOYEE', 'Employee', 'Create and track trip requests', '2026-10-18 18:17:47', '2026-10-18 18:17:47'),
(24, 'DRIVER', 'MMJ Driver', 'Receive, complete, and track trips', '2026-10-18 18:17:47', '2026-10-18 18:17:47');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `permission_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`role_id`, `permission_id`) VALUES
(19, 37),
(19, 38),
(19, 39),
(19, 40),
(19, 41),
(19, 42),
(19, 43),
(19, 44),
(19, 45),
(19, 46),
(19, 47),
(19, 48),
(20, 39),
(20, 40),
(20, 41),
(20, 42),
(20, 43),
(20, 44),
(20, 45),
(20, 46),
(20, 47),
(20, 48),
(21, 40),
(21, 43),
(21, 46),
(22, 44),
(22, 46),
(23, 45),
(24, 45);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('0YRKi89S0K74VlKbm2HEV3X6wWBR3Mss5U8Eh5aE', 91, '2a0d:5600:8:337:40c1:81a7:c75f:cc3a', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Mobile Safari/537.36', 'eyJfdG9rZW4iOiJrWm9GTzJCS2EzYWJOcmJRTU5QN0Q3OUxaZ2xyU1lURmZZSVdnSnZwIiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9wZW5kaW5nLWxpcXVlZnktZWFzdGVyLm5ncm9rLWZyZWUuZGV2XC9sb2dpbiIsInJvdXRlIjoibG9naW4ifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6OTEsInBhc3N3b3JkX2hhc2hfd2ViIjoiZWIwYTRhYzY5MmM1ODdmZWRjM2RkODNjZDQ2NWM3OTJiNTU0N2JmYmNiOTU1MzNhZDZjY2E1ODg0ZTE4MzhhZCJ9', 1794458249),
('8MBdq893dtQRq4AZvIS2Bq2BpHDPbUGhGVe7ZNK8', 57, '118.99.74.131', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Mobile Safari/537.36', 'eyJfdG9rZW4iOiJFbG5QbVNrSzlqVHBib21vcExzeWoxRnVsSURsdTlZU3B1eFlTbTVsIiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9wZW5kaW5nLWxpcXVlZnktZWFzdGVyLm5ncm9rLWZyZWUuZGV2XC9yZXBvcnRzIiwicm91dGUiOiJyZXBvcnRzLmluZGV4In0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjU3LCJwYXNzd29yZF9oYXNoX3dlYiI6ImE0OTcyYzg0ZGRjNjQ4YmYwZTM1OGRlZDQ3M2YzNTZmNDYyZWRmNTYxYjlhNDg2MTRjMmQ4MjhhNTNmYzQ2MWQifQ==', 1794453707),
('AEdoCrbOH8hQBxvESSha5XezyM5QMOJMm6xfT4yK', NULL, '118.99.74.131', 'WhatsApp/2.2642.101 W', 'eyJfdG9rZW4iOiJxajB5SVhSa2ZUNklDcElkM25LQ25RQ2FJOTJIVVJpSTY2WFEyYUhpIiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL3BlbmRpbmctbGlxdWVmeS1lYXN0ZXIubmdyb2stZnJlZS5kZXZcL2FkbWluXC92ZWhpY2xlcyJ9LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cHM6XC9cL3BlbmRpbmctbGlxdWVmeS1lYXN0ZXIubmdyb2stZnJlZS5kZXZcL2xvZ2luIiwicm91dGUiOiJsb2dpbiJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1794458216),
('Ouknpa0EVXnmIld9StJePsrSjgeJKVMzctKI68Y2', NULL, '118.99.74.131', 'WhatsApp/2.2642.101 W', 'eyJfdG9rZW4iOiJEM2pZRkxJdW54M1NCNWJwb21vVkRaQ2FST1N4MWR1UkZ3OXl4VXg1IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cHM6XC9cL3BlbmRpbmctbGlxdWVmeS1lYXN0ZXIubmdyb2stZnJlZS5kZXZcL3JlcG9ydHMifSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9wZW5kaW5nLWxpcXVlZnktZWFzdGVyLm5ncm9rLWZyZWUuZGV2XC9sb2dpbiIsInJvdXRlIjoibG9naW4ifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==', 1794448105),
('sXIMBp0eAsgqPGkKQ973lVfz13gq3osb1RXtW3zW', 90, '118.99.74.131', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJUZklrYTBVNUE0eVZOSXhwQmZtOEhOS2cxMGU0c2hTVzdybnhEWGk4IiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6OTAsInBhc3N3b3JkX2hhc2hfd2ViIjoiOTA1Yjc0OWYyNTJhNmVkZDFmODQ1MGE2MzIxMzVmZjRiMjJjYjQ3NTlkOWIxYWNlYzA5YTU0OWRkMWU1YzgxZCJ9', 1794457918);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `group` varchar(50) NOT NULL DEFAULT 'general',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trip_approvals`
--

CREATE TABLE `trip_approvals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `trip_request_id` bigint(20) UNSIGNED NOT NULL,
  `approver_id` bigint(20) UNSIGNED NOT NULL,
  `decision` enum('APPROVE','REJECT') NOT NULL,
  `notes` text DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trip_assignments`
--

CREATE TABLE `trip_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `trip_request_id` bigint(20) UNSIGNED NOT NULL,
  `driver_id` bigint(20) UNSIGNED NOT NULL,
  `vehicle_id` bigint(20) UNSIGNED DEFAULT NULL,
  `assigned_by` bigint(20) UNSIGNED NOT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `driver_accepted_at` datetime DEFAULT NULL,
  `driver_rejection_reason` text DEFAULT NULL,
  `driver_response` enum('PENDING','ACCEPTED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trip_passengers`
--

CREATE TABLE `trip_passengers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `trip_request_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `nik` varchar(16) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trip_requests`
--

CREATE TABLE `trip_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `request_type` varchar(30) NOT NULL DEFAULT 'BUSINESS_TRIP',
  `trip_number` varchar(255) NOT NULL,
  `requestor_id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `trip_date` date DEFAULT NULL,
  `departure_time` datetime NOT NULL,
  `estimated_return_time` datetime DEFAULT NULL,
  `destination` varchar(255) NOT NULL,
  `destination_address` text DEFAULT NULL,
  `guest_pickup_location` varchar(255) DEFAULT NULL,
  `guest_institution` varchar(255) DEFAULT NULL,
  `purpose` text NOT NULL,
  `passenger_count` int(11) NOT NULL DEFAULT 1,
  `priority` enum('NORMAL','URGENT','VERY_URGENT') NOT NULL DEFAULT 'NORMAL',
  `requested_priority` varchar(20) DEFAULT NULL,
  `urgency_notes` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `requires_loading_assistance` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('DRAFT','SUBMITTED','WAITING_APPROVAL','APPROVED','REJECTED','WAITING_DRIVER','DRIVER_ASSIGNED','DRIVER_ACCEPTED','DRIVER_REJECTED','READY_TO_DEPART','ON_TRIP','COMPLETED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `priority_set_by` varchar(20) DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `assigned_driver_id` bigint(20) UNSIGNED DEFAULT NULL,
  `assigned_vehicle_id` bigint(20) UNSIGNED DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `started_by` bigint(20) UNSIGNED DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `completed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `completion_notes` text DEFAULT NULL,
  `actual_return_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trip_status_logs`
--

CREATE TABLE `trip_status_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `trip_request_id` bigint(20) UNSIGNED NOT NULL,
  `from_status` varchar(50) DEFAULT NULL,
  `to_status` varchar(50) NOT NULL,
  `changed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `role_id` bigint(20) UNSIGNED DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `gender` enum('L','P') DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `nik` varchar(16) DEFAULT NULL,
  `npwp` varchar(15) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `role_id`, `department_id`, `phone`, `avatar`, `gender`, `birth_date`, `nik`, `npwp`, `is_active`, `last_login_at`) VALUES
(57, 'Super Admin', 'superadmin@example.com', '2026-10-18 18:17:47', '$2y$12$EBUmLdociwZIRAgHJM0LuOikJQTCn7D4HfMz6cQ2oWf06S2lgchza', 'PI1HsX8Sg4kHLngtvqoEO9h3RDhOZdTDJ3qZCjIgcP7zpZCZ1qsUlzqG2vnt', '2026-10-18 18:17:48', '2026-11-11 18:55:40', 19, 22, '081100000001', NULL, NULL, NULL, NULL, NULL, 1, NULL),
(80, 'Yusmanto Silaban', 'yusmanto@example.com', NULL, '$2y$12$N3WqnWBAb4DCMztd8aFVNusFr7bpEEK0A6hXoBgIKkT64VC.Q8ZrG', NULL, '2026-11-11 18:59:14', '2026-11-11 18:59:14', 24, 30, '085274681911', NULL, NULL, NULL, NULL, NULL, 1, NULL),
(81, 'Roni Candra', 'roni@example.com', NULL, '$2y$12$YjfCNeheAKjSHxL3yCUR4.ryijMBophTSnEUA2piZvwHw0PeZD4Qi', NULL, '2026-11-11 19:01:31', '2026-11-11 19:07:20', 24, 30, '085355538899', NULL, NULL, NULL, NULL, NULL, 1, NULL),
(82, 'Mauludil Al Aziz', 'aziz@example.com', NULL, '$2y$12$VSo97I3Hlo8TB2sXECFelu.HaT9M7LnvJevxb5hyr.hrYWKCxY6Fi', NULL, '2026-11-11 19:04:11', '2026-11-11 19:04:11', 24, 30, '082312703411', NULL, NULL, NULL, NULL, NULL, 1, NULL),
(83, 'Priagung Budi Santoso', 'agung@example.com', NULL, '$2y$12$QUB94hk0lSzEPLUAqwnh6OwmsVMqPAN.arc06ilqIjRAEKUJ/IPMW', NULL, '2026-11-11 19:06:02', '2026-11-11 19:06:02', 24, 30, '081275266047', NULL, NULL, NULL, NULL, NULL, 1, NULL),
(84, 'Ridho', 'ridho@example.com', NULL, '$2y$12$e9AN8thiNOVi2yFFpcChGuKShVzNzYAn8Ok2m11NGf5I89oFRlJUq', NULL, '2026-11-11 19:08:14', '2026-11-11 19:08:14', 24, 30, '0895367202171', NULL, NULL, NULL, NULL, NULL, 1, NULL),
(85, 'Joko Wiyono', 'joko@example.com', NULL, '$2y$12$6J4eOHfqouZOuTDPdULHUerJFlYYGsFyA7e1qjpOhum1ZnMN.VsR2', NULL, '2026-11-11 19:20:35', '2026-11-11 19:20:35', 24, 30, '082164473015', NULL, NULL, NULL, NULL, NULL, 1, NULL),
(86, 'Welson', 'wellson.zheng@maritagroups.com', NULL, '$2y$12$P9AVcElgftHMV7V1ESPj/.95fn/RTK5/2UMu9.h8mKLJLGSAjD2Gq', NULL, '2026-11-11 20:25:04', '2026-11-11 20:25:04', 23, 23, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL),
(87, 'Hendry', 'hendry.chan@maritagroups.com', NULL, '$2y$12$22HKbk.VbQJLuwBgmqZHS.eqy0NsTbiSFVPwkG1nYlOHR2pvpRd42', NULL, '2026-11-11 20:25:53', '2026-11-11 20:25:53', 23, 27, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL),
(88, 'Ipeh', 'lipah.ipeh@maritagroups.com', NULL, '$2y$12$jtMEC0p/bnv4AADBH.gQSeqO/uZaq.N4mJNIOGiQaMHSIrw/RE7ha', NULL, '2026-11-11 20:26:19', '2026-11-11 20:26:19', 23, 24, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL),
(89, 'Tandi', 'tandi.siregar@maritagroups.com', NULL, '$2y$12$3/opKimROxWr8Upb/XI4jO.KoQXPKO8DqJoGDuhXrSC96bPhPLmxe', NULL, '2026-11-11 20:26:41', '2026-11-11 20:26:41', 23, 26, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL),
(90, 'ade.budy@maritagroups.com', 'ade.budy@maritagroups.com', NULL, '$2y$12$0Gaf.EYGyAotw9GRt5WBcuIaezBC7khyYOm5.qzDMVYK3rKtPwymO', NULL, '2026-11-11 20:27:10', '2026-11-11 20:27:10', 19, 22, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL),
(91, 'Fiqhi Ibrahim', 'fiqhi.ibrahim11@gmail.com', NULL, '$2y$12$s6m5zD1OhRtA.HfdRROVk.5ymlv0/h7g87v86BPPag0fJTokwHVqa', NULL, '2026-11-11 20:27:55', '2026-11-11 20:27:55', 19, 23, '085275426955', NULL, NULL, NULL, NULL, NULL, 1, NULL),
(92, 'Widianti', 'widianti@example.com', NULL, '$2y$12$kKwkdK0g0TTTJD5XmqxyM.8cfU3w50TYac0aUBitYsVrTDU9YzV1i', NULL, '2026-11-11 20:29:03', '2026-11-11 20:29:03', 23, 29, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `plate_number` varchar(255) NOT NULL,
  `brand` varchar(255) NOT NULL,
  `model` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `year` int(11) NOT NULL,
  `passenger_capacity` int(11) NOT NULL,
  `status` enum('AVAILABLE','ASSIGNED','ON_TRIP','MAINTENANCE','OUT_OF_SERVICE') NOT NULL DEFAULT 'AVAILABLE',
  `primary_driver_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `last_service_date` date DEFAULT NULL,
  `next_service_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `plate_number`, `brand`, `model`, `type`, `year`, `passenger_capacity`, `status`, `primary_driver_id`, `notes`, `last_service_date`, `next_service_date`, `created_at`, `updated_at`) VALUES
(16, 'BM 1538 TE', 'TOYOTA/RIZE', 'MINI BUS', 'A250RA-GBVJ 1.0T S CVT', 2023, 5, 'AVAILABLE', NULL, NULL, NULL, NULL, '2026-11-11 19:31:08', '2026-11-11 19:31:08'),
(17, 'BM 8373 QH', 'Isuzu', 'Pick Up', 'PHR54U-CAAIN1 4X2 MT', 2023, 2, 'AVAILABLE', NULL, NULL, NULL, NULL, '2026-11-11 19:34:48', '2026-11-11 19:34:48'),
(18, 'BM 1199 SD', 'Mitsubishi', 'Mini Bus', 'XPANDER 1.5L SPORT -  L 4X2 M/T', 2023, 5, 'AVAILABLE', NULL, NULL, NULL, NULL, '2026-11-11 19:36:15', '2026-11-11 19:36:15'),
(19, 'BM 999 SR', 'Toyota', 'Mini Bus', 'INNOVA VENTURER 2.0 A/T (TGN140R-MDTHK)', 2023, 6, 'AVAILABLE', NULL, NULL, NULL, NULL, '2026-11-11 19:37:17', '2026-11-11 19:37:17'),
(20, 'BM 999 MRY', 'Lexus', 'Lexus', 'LEXUS LX570 SPORT 4X4 A/T', 2023, 2, 'AVAILABLE', NULL, NULL, NULL, NULL, '2026-11-11 19:38:40', '2026-11-11 19:38:40'),
(21, 'BM 9 MRY', 'Toyota/ALPARD', 'Mini Bus', 'ALPARD 2.5 HYBRID CVT (AAHH40R-PFXLB)', 2026, 3, 'AVAILABLE', NULL, NULL, NULL, NULL, '2026-11-11 20:04:07', '2026-11-11 20:04:07'),
(22, 'BM 8851 QJ', 'Mitsubishi', 'Pick Up', 'L300 PU FB-R (4X2) M/T', 2026, 3, 'AVAILABLE', NULL, NULL, NULL, NULL, '2026-11-11 20:06:50', '2026-11-11 20:06:50'),
(23, 'BM 8063 JO', 'Mitsubishi', 'Colt Diesel', 'CANTER FE 74L N (4X2) M/T', 2026, 3, 'AVAILABLE', NULL, NULL, NULL, NULL, '2026-11-11 20:09:17', '2026-11-11 20:09:17'),
(24, 'BM 7726 AZ', 'Toyota Hiace', 'Mini Bus', 'HIACE COMMUTER M/T (KDH223R-LEMDY)', 2026, 16, 'AVAILABLE', NULL, NULL, NULL, NULL, '2026-11-11 20:11:51', '2026-11-11 20:11:51'),
(25, 'BM 1078 NG', 'Toyota Yaris', 'Mini Bus', 'YARIS 1.5 MT', 2013, 4, 'AVAILABLE', NULL, NULL, NULL, NULL, '2026-11-11 20:13:50', '2026-11-11 20:13:50'),
(26, 'BM 1550 ANN', 'Toyota Fortuner', 'Fortuner', 'FORTUNER 2.8 4X4 GR-S A/T (GUN156R-SDTHX)', 2025, 4, 'AVAILABLE', NULL, NULL, NULL, NULL, '2026-11-11 20:15:07', '2026-11-11 20:15:07');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_status_logs`
--

CREATE TABLE `vehicle_status_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `vehicle_id` bigint(20) UNSIGNED NOT NULL,
  `from_status` enum('AVAILABLE','ASSIGNED','ON_TRIP','MAINTENANCE','OUT_OF_SERVICE') NOT NULL,
  `to_status` enum('AVAILABLE','ASSIGNED','ON_TRIP','MAINTENANCE','OUT_OF_SERVICE') NOT NULL,
  `reason` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attachments_attachable_type_attachable_id_index` (`attachable_type`,`attachable_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_logs_user_id_created_at_index` (`user_id`,`created_at`),
  ADD KEY `audit_logs_module_created_at_index` (`module`,`created_at`);

--
-- Indexes for table `berita_acaras`
--
ALTER TABLE `berita_acaras`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `berita_acaras_ba_number_unique` (`ba_number`),
  ADD KEY `berita_acaras_trip_request_id_foreign` (`trip_request_id`),
  ADD KEY `berita_acaras_driver_id_foreign` (`driver_id`),
  ADD KEY `berita_acaras_vehicle_id_foreign` (`vehicle_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `departments_code_unique` (`code`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `drivers_user_id_unique` (`user_id`),
  ADD UNIQUE KEY `drivers_license_number_unique` (`license_number`);

--
-- Indexes for table `driver_status_logs`
--
ALTER TABLE `driver_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `driver_status_logs_driver_id_foreign` (`driver_id`);

--
-- Indexes for table `driver_vehicle`
--
ALTER TABLE `driver_vehicle`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `driver_vehicle_driver_id_vehicle_id_unique` (`driver_id`,`vehicle_id`),
  ADD KEY `driver_vehicle_vehicle_id_foreign` (`vehicle_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  ADD KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `loading_requests`
--
ALTER TABLE `loading_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loading_requests_trip_request_id_foreign` (`trip_request_id`),
  ADD KEY `loading_requests_driver_id_foreign` (`driver_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_unique` (`name`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_unique` (`name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `role_has_permissions_permission_id_foreign` (`permission_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `settings_key_unique` (`key`);

--
-- Indexes for table `trip_approvals`
--
ALTER TABLE `trip_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trip_approvals_trip_request_id_foreign` (`trip_request_id`),
  ADD KEY `trip_approvals_approver_id_foreign` (`approver_id`);

--
-- Indexes for table `trip_assignments`
--
ALTER TABLE `trip_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trip_assignments_trip_request_id_foreign` (`trip_request_id`),
  ADD KEY `trip_assignments_driver_id_foreign` (`driver_id`),
  ADD KEY `trip_assignments_vehicle_id_foreign` (`vehicle_id`),
  ADD KEY `trip_assignments_assigned_by_foreign` (`assigned_by`);

--
-- Indexes for table `trip_passengers`
--
ALTER TABLE `trip_passengers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trip_passengers_trip_request_id_foreign` (`trip_request_id`);

--
-- Indexes for table `trip_requests`
--
ALTER TABLE `trip_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `trip_requests_trip_number_unique` (`trip_number`),
  ADD KEY `trip_requests_requestor_id_foreign` (`requestor_id`),
  ADD KEY `trip_requests_department_id_foreign` (`department_id`),
  ADD KEY `trip_requests_approved_by_foreign` (`approved_by`),
  ADD KEY `trip_requests_created_by_foreign` (`created_by`),
  ADD KEY `trip_requests_updated_by_foreign` (`updated_by`),
  ADD KEY `trip_requests_assigned_driver_id_foreign` (`assigned_driver_id`),
  ADD KEY `trip_requests_assigned_vehicle_id_foreign` (`assigned_vehicle_id`),
  ADD KEY `trip_requests_started_by_foreign` (`started_by`),
  ADD KEY `trip_requests_completed_by_foreign` (`completed_by`),
  ADD KEY `trip_requests_request_type_index` (`request_type`);

--
-- Indexes for table `trip_status_logs`
--
ALTER TABLE `trip_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trip_status_logs_trip_request_id_foreign` (`trip_request_id`),
  ADD KEY `trip_status_logs_changed_by_foreign` (`changed_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_nik_unique` (`nik`),
  ADD UNIQUE KEY `users_npwp_unique` (`npwp`),
  ADD KEY `users_role_id_foreign` (`role_id`),
  ADD KEY `users_department_id_foreign` (`department_id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vehicles_plate_number_unique` (`plate_number`),
  ADD KEY `vehicles_primary_driver_id_foreign` (`primary_driver_id`);

--
-- Indexes for table `vehicle_status_logs`
--
ALTER TABLE `vehicle_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_status_logs_vehicle_id_foreign` (`vehicle_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attachments`
--
ALTER TABLE `attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `berita_acaras`
--
ALTER TABLE `berita_acaras`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `driver_status_logs`
--
ALTER TABLE `driver_status_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `driver_vehicle`
--
ALTER TABLE `driver_vehicle`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loading_requests`
--
ALTER TABLE `loading_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trip_approvals`
--
ALTER TABLE `trip_approvals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `trip_assignments`
--
ALTER TABLE `trip_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `trip_passengers`
--
ALTER TABLE `trip_passengers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `trip_requests`
--
ALTER TABLE `trip_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `trip_status_logs`
--
ALTER TABLE `trip_status_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `vehicle_status_logs`
--
ALTER TABLE `vehicle_status_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `berita_acaras`
--
ALTER TABLE `berita_acaras`
  ADD CONSTRAINT `berita_acaras_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `berita_acaras_trip_request_id_foreign` FOREIGN KEY (`trip_request_id`) REFERENCES `trip_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `berita_acaras_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `drivers`
--
ALTER TABLE `drivers`
  ADD CONSTRAINT `drivers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `driver_status_logs`
--
ALTER TABLE `driver_status_logs`
  ADD CONSTRAINT `driver_status_logs_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `driver_vehicle`
--
ALTER TABLE `driver_vehicle`
  ADD CONSTRAINT `driver_vehicle_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `driver_vehicle_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loading_requests`
--
ALTER TABLE `loading_requests`
  ADD CONSTRAINT `loading_requests_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `loading_requests_trip_request_id_foreign` FOREIGN KEY (`trip_request_id`) REFERENCES `trip_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trip_approvals`
--
ALTER TABLE `trip_approvals`
  ADD CONSTRAINT `trip_approvals_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `trip_approvals_trip_request_id_foreign` FOREIGN KEY (`trip_request_id`) REFERENCES `trip_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trip_assignments`
--
ALTER TABLE `trip_assignments`
  ADD CONSTRAINT `trip_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `trip_assignments_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`),
  ADD CONSTRAINT `trip_assignments_trip_request_id_foreign` FOREIGN KEY (`trip_request_id`) REFERENCES `trip_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trip_assignments_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`);

--
-- Constraints for table `trip_passengers`
--
ALTER TABLE `trip_passengers`
  ADD CONSTRAINT `trip_passengers_trip_request_id_foreign` FOREIGN KEY (`trip_request_id`) REFERENCES `trip_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trip_requests`
--
ALTER TABLE `trip_requests`
  ADD CONSTRAINT `trip_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `trip_requests_assigned_driver_id_foreign` FOREIGN KEY (`assigned_driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `trip_requests_assigned_vehicle_id_foreign` FOREIGN KEY (`assigned_vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `trip_requests_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `trip_requests_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `trip_requests_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `trip_requests_requestor_id_foreign` FOREIGN KEY (`requestor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `trip_requests_started_by_foreign` FOREIGN KEY (`started_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `trip_requests_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `trip_status_logs`
--
ALTER TABLE `trip_status_logs`
  ADD CONSTRAINT `trip_status_logs_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `trip_status_logs_trip_request_id_foreign` FOREIGN KEY (`trip_request_id`) REFERENCES `trip_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_primary_driver_id_foreign` FOREIGN KEY (`primary_driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `vehicle_status_logs`
--
ALTER TABLE `vehicle_status_logs`
  ADD CONSTRAINT `vehicle_status_logs_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 27, 2026 at 08:34 AM
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
-- Database: `maseno_graduate_tracer`
--

-- --------------------------------------------------------

--
-- Table structure for table `account_statuses`
--

CREATE TABLE `account_statuses` (
  `account_status_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(60) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `account_statuses`
--

INSERT INTO `account_statuses` (`account_status_id`, `code`, `name`, `is_active`, `created_at`) VALUES
(1, 'active', 'Active', 1, '2026-07-23 11:46:55.340389'),
(2, 'inactive', 'Inactive', 1, '2026-07-23 11:46:55.340389'),
(3, 'pending_verification', 'Pending Verification', 1, '2026-07-23 11:46:55.340389'),
(4, 'suspended', 'Suspended', 1, '2026-07-23 11:46:55.340389');

-- --------------------------------------------------------

--
-- Table structure for table `administrator_profiles`
--

CREATE TABLE `administrator_profiles` (
  `administrator_profile_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `employee_number` varchar(100) DEFAULT NULL,
  `department` varchar(150) DEFAULT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(250) NOT NULL,
  `message` text NOT NULL,
  `announcement_type` varchar(50) DEFAULT NULL,
  `published_at` datetime(6) DEFAULT NULL,
  `expires_at` datetime(6) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcement_recipients`
--

CREATE TABLE `announcement_recipients` (
  `announcement_recipient_id` bigint(20) UNSIGNED NOT NULL,
  `announcement_id` bigint(20) UNSIGNED NOT NULL,
  `graduate_profile_id` bigint(20) UNSIGNED DEFAULT NULL,
  `faculty_id` bigint(20) UNSIGNED DEFAULT NULL,
  `programme_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime(6) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `businesses`
--

CREATE TABLE `businesses` (
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `graduate_profile_id` bigint(20) UNSIGNED NOT NULL,
  `business_name` varchar(200) NOT NULL,
  `business_type` varchar(150) DEFAULT NULL,
  `registration_number` varchar(100) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `employees_count` int(10) UNSIGNED DEFAULT 0,
  `established_year` year(4) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `degree_classifications`
--

CREATE TABLE `degree_classifications` (
  `degree_classification_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(100) NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `faculty_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `faculty_id`, `name`, `code`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Applied Plant Sciences and Soil Sciences', 'APSS', 1, '2026-07-23 15:12:39.794663', '2026-07-23 15:12:39.794663'),
(3, 7, 'Medical Physiology', 'MPHY', 1, '2026-07-23 15:12:39.794663', '2026-07-23 15:12:39.794663'),
(4, 7, 'Psychiatry', 'PSY', 1, '2026-07-23 15:12:39.794663', '2026-07-23 15:12:39.794663'),
(5, 7, 'Health and Nutrition', 'HN', 1, '2026-07-23 15:12:39.794663', '2026-07-23 15:12:39.794663'),
(6, 7, 'Human Anatomy', 'HANA', 1, '2026-07-23 15:12:39.794663', '2026-07-23 15:12:39.794663'),
(141, 5, 'Educational Psychology', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(142, 2, 'Linguistics and Literary Studies', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(143, 2, 'Art and Design', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(144, 8, 'Environmental Sciences', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(145, 1, 'Agricultural Economics and Rural Development', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(146, 7, 'Internal Medicine', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(147, 7, 'Obstetrics and Gynecology', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(148, 8, 'Physics and Material Science', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(149, 11, 'Pure and Applied Mathematics', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(150, 6, 'Public Health', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(151, 7, 'Biomedical Science and Technology', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(152, 5, 'Education Communication Technology and Curriculum Studies', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(153, 2, 'Music and Theatre Studies', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(154, 3, 'Business Administration', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(155, 2, 'Psychology', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(156, 2, 'Communication and Media Technology', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(157, 5, 'Educational Management and Foundations', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(158, 8, 'Chemistry', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(159, 6, 'Community Health and Family Medicine', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(160, 4, 'Information Technology', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(161, 7, 'Medical Microbiology', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(162, 7, 'Surgery and Anesthesiology', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(163, 11, 'Statistics and Actuarial Science', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(164, 5, 'Special Needs Education', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(165, 7, 'Medical Biochemistry', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(166, 2, 'Sociology and Anthropology', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(167, 2, 'French and Other Foreign Languages', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(168, 2, 'Religion, Theology and Philosophy', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(169, 7, 'Radiology and Imaging', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(170, 13, 'Pharmacology', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(171, 4, 'Computer Science', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(172, 8, 'Botany', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(173, 8, 'Zoology', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(174, 1, 'Animal and Fisheries Sciences', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(175, 12, 'Ecotourism, Hotel and Institutional Management', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(176, 3, 'Finance and Accounting', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(177, 7, 'Human Pathology', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(178, 13, 'Pharmacy and Pharmaceutics Practice', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(179, 12, 'Geography and Natural Resources', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(180, 2, 'History and Archaeology', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(181, 2, 'Kiswahili and Other African Languages', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(182, 5, 'Early Childhood Development and Education', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(183, 3, 'Economics', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(184, 10, 'Midwifery', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(185, 10, 'Nursing Education Leadership and Research', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(186, 10, 'Community Health Nursing', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(187, 10, 'Medical Surgical Nursing', NULL, 1, '2026-06-24 11:40:42.000000', '2026-07-23 15:41:24.481479'),
(188, 3, 'Management Science', NULL, 1, '2026-06-29 06:13:52.000000', '2026-07-23 15:41:24.481479');

-- --------------------------------------------------------

--
-- Table structure for table `education_levels`
--

CREATE TABLE `education_levels` (
  `education_level_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(100) NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `education_levels`
--

INSERT INTO `education_levels` (`education_level_id`, `code`, `name`, `sort_order`, `is_active`) VALUES
(1, 'CERT', 'Certificate', 1, 1),
(2, 'DIP', 'Diploma', 2, 1),
(3, 'BACH', 'Bachelor\'s Degree', 3, 1),
(4, 'PGD', 'Postgraduate Diploma', 4, 1),
(5, 'MAST', 'Master\'s Degree', 5, 1),
(6, 'PHD', 'Doctor of Philosophy', 6, 1);

-- --------------------------------------------------------

--
-- Table structure for table `employment_records`
--

CREATE TABLE `employment_records` (
  `employment_record_id` bigint(20) UNSIGNED NOT NULL,
  `graduate_profile_id` bigint(20) UNSIGNED NOT NULL,
  `employment_status_id` bigint(20) UNSIGNED NOT NULL,
  `company_name` varchar(200) DEFAULT NULL,
  `job_title` varchar(150) DEFAULT NULL,
  `industry` varchar(150) DEFAULT NULL,
  `employment_type` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `current_job` tinyint(1) DEFAULT 0,
  `monthly_income` decimal(12,2) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employment_statuses`
--

CREATE TABLE `employment_statuses` (
  `employment_status_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(250) NOT NULL,
  `description` text DEFAULT NULL,
  `event_type` varchar(50) DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `start_datetime` datetime(6) NOT NULL,
  `end_datetime` datetime(6) DEFAULT NULL,
  `registration_deadline` datetime(6) DEFAULT NULL,
  `max_attendees` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_registrations`
--

CREATE TABLE `event_registrations` (
  `event_registration_id` bigint(20) UNSIGNED NOT NULL,
  `event_id` bigint(20) UNSIGNED NOT NULL,
  `graduate_profile_id` bigint(20) UNSIGNED NOT NULL,
  `registration_status` varchar(30) NOT NULL DEFAULT 'Registered',
  `registered_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `attended` tinyint(1) NOT NULL DEFAULT 0,
  `attended_at` datetime(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculties`
--

CREATE TABLE `faculties` (
  `faculty_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(200) NOT NULL,
  `dean_email` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `faculties`
--

INSERT INTO `faculties` (`faculty_id`, `code`, `name`, `dean_email`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'AG', 'School of Agriculture and Food Security', NULL, 1, '2026-07-23 15:04:01.807759', '2026-07-23 15:04:01.807759'),
(2, 'AR', 'School of Arts and Social Sciences', 'deanfas@maseno.ac.ke', 1, '2026-07-23 15:04:01.807759', '2026-07-23 15:04:01.807759'),
(3, 'BE', 'School of Business and Economics', 'deanbusiness@maseno.ac.ke', 1, '2026-07-23 15:04:01.807759', '2026-07-23 15:04:01.807759'),
(4, 'CO', 'School of Computing and Informatics', NULL, 1, '2026-07-23 15:04:01.807759', '2026-07-23 15:04:01.807759'),
(5, 'ED', 'School of Education', 'deanedu@maseno.ac.ke', 1, '2026-07-23 15:04:01.807759', '2026-07-23 15:04:01.807759'),
(6, 'ES', 'School of Public Health and Community Development', 'espudec@maseno.ac.ke', 1, '2026-07-23 15:04:01.807759', '2026-07-23 15:04:01.807759'),
(7, 'ME', 'School of Medicine', NULL, 1, '2026-07-23 15:04:01.807759', '2026-07-23 15:04:01.807759'),
(8, 'SC', 'School of Biological and Physical Sciences', NULL, 1, '2026-07-23 15:04:01.807759', '2026-07-23 15:04:01.807759'),
(9, 'SD', 'School of Development and Strategic Studies', NULL, 1, '2026-07-23 15:04:01.807759', '2026-07-23 15:04:01.807759'),
(10, 'SE', 'School of Environment and Earth Sciences', NULL, 1, '2026-07-23 15:04:01.807759', '2026-07-23 15:04:01.807759'),
(11, 'SM', 'School of Mathematics, Applied Statistics and Actuarial Science', 'deanmaths@maseno.ac.ke', 1, '2026-07-23 15:04:01.807759', '2026-07-23 15:04:01.807759'),
(12, 'SP', 'School of Planning and Architecture', 'deanarchitecture@maseno.ac.ke', 1, '2026-07-23 15:04:01.807759', '2026-07-23 15:04:01.807759'),
(13, 'PH', 'School of Pharmacy', NULL, 1, '2026-07-23 15:41:02.653228', '2026-07-23 15:41:02.653228');

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `file_id` bigint(20) UNSIGNED NOT NULL,
  `uploaded_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `storage_key` varchar(500) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `byte_size` bigint(20) UNSIGNED NOT NULL,
  `checksum_sha256` char(64) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `graduate_profiles`
--

CREATE TABLE `graduate_profiles` (
  `graduate_profile_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `county` varchar(100) DEFAULT NULL,
  `sub_county` varchar(100) DEFAULT NULL,
  `postal_address` varchar(255) DEFAULT NULL,
  `physical_address` varchar(255) DEFAULT NULL,
  `cv_file_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `graduate_qualifications`
--

CREATE TABLE `graduate_qualifications` (
  `graduate_qualification_id` bigint(20) UNSIGNED NOT NULL,
  `graduate_profile_id` bigint(20) UNSIGNED NOT NULL,
  `programme_id` bigint(20) UNSIGNED NOT NULL,
  `graduation_status_id` bigint(20) UNSIGNED DEFAULT NULL,
  `degree_classification_id` bigint(20) UNSIGNED DEFAULT NULL,
  `admission_number` varchar(100) DEFAULT NULL,
  `registration_number` varchar(100) DEFAULT NULL,
  `year_of_admission` smallint(5) UNSIGNED DEFAULT NULL,
  `year_of_graduation` smallint(5) UNSIGNED DEFAULT NULL,
  `degree_awarded` varchar(250) DEFAULT NULL,
  `campus` varchar(150) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `graduation_statuses`
--

CREATE TABLE `graduation_statuses` (
  `graduation_status_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `management_profiles`
--

CREATE TABLE `management_profiles` (
  `management_profile_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `faculty_id` bigint(20) UNSIGNED DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `designation` varchar(150) NOT NULL,
  `employee_number` varchar(100) DEFAULT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_deliveries`
--

CREATE TABLE `notification_deliveries` (
  `delivery_id` bigint(20) UNSIGNED NOT NULL,
  `notification_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `channel` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL,
  `sent_at` datetime(6) DEFAULT NULL,
  `error_message` varchar(1000) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(250) NOT NULL,
  `message` text NOT NULL,
  `notification_type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime(6) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_history`
--

CREATE TABLE `password_history` (
  `password_history_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `changed_at` datetime(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `person_profiles`
--

CREATE TABLE `person_profiles` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `national_id` char(8) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `programmes`
--

CREATE TABLE `programmes` (
  `programme_id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `education_level_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(250) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `programmes`
--

INSERT INTO `programmes` (`programme_id`, `department_id`, `education_level_id`, `code`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
(20, 163, 5, 'eRM', 'Master of Science in Quantitative Research Methods', 1, '2026-07-23 15:45:36.992808', '2026-07-23 15:45:36.992808'),
(21, 157, 4, 'PGDE', 'Post Graduate Diploma in Education(PGDE)', 1, '2026-07-23 15:45:36.992808', '2026-07-23 15:45:36.992808'),
(22, 154, 3, NULL, 'Bachelor of Business Administration (BBA, With IT)', 1, '2026-07-23 15:45:36.992808', '2026-07-23 15:45:36.992808'),
(23, 163, 3, NULL, 'Bachelor of Science (Mathematics and Business Studies, With IT)', 1, '2026-07-23 15:45:36.992808', '2026-07-23 15:45:36.992808'),
(24, 163, 3, NULL, 'Bachelor of Science (Mathematical Sciences, With IT)', 1, '2026-07-23 15:45:36.992808', '2026-07-23 15:45:36.992808'),
(25, 163, 3, NULL, 'Bachelor of Science (Actuarial Science, With IT)', 1, '2026-07-23 15:45:36.992808', '2026-07-23 15:45:36.992808'),
(26, 163, 3, NULL, 'Bachelor of Science (Applied Statistics, With IT)', 1, '2026-07-23 15:45:36.992808', '2026-07-23 15:45:36.992808'),
(27, 150, 5, 'MPH', 'Master of Public Health(MPH)', 1, '2026-07-23 15:45:36.992808', '2026-07-23 15:45:36.992808'),
(28, 179, 5, NULL, 'Master of Arts in Project Planning and Management', 1, '2026-07-23 15:45:36.992808', '2026-07-23 15:45:36.992808'),
(29, 179, 5, NULL, 'Master of Arts in Monitoring and Evaluation', 1, '2026-07-23 15:45:36.992808', '2026-07-23 15:45:36.992808'),
(30, 166, 5, NULL, 'Master of Arts in Social Development and Management', 1, '2026-07-23 15:45:36.992808', '2026-07-23 15:45:36.992808'),
(31, 157, 5, NULL, 'Master of Education in Educational Administration', 1, '2026-07-23 15:45:36.992808', '2026-07-23 15:45:36.992808'),
(32, 141, 5, NULL, 'Master of Education in Guidance and Counselling', 1, '2026-07-23 15:45:36.992808', '2026-07-23 15:45:36.992808'),
(33, 141, 5, NULL, 'Master of Education in Educational Psychology', 1, '2026-07-23 15:45:36.992808', '2026-07-23 15:45:36.992808'),
(34, 163, 1, 'eStats', 'Certificate in Basic Statistics', 1, '2026-07-23 15:45:36.992808', '2026-07-23 15:45:36.992808'),
(35, 163, 1, NULL, 'Certificate in Bridging Mathematics', 1, '2026-07-23 15:45:36.992808', '2026-07-23 15:45:36.992808'),
(36, 166, 5, NULL, 'Master of Arts in Social Policy', 1, '2026-07-23 15:45:36.992808', '2026-07-23 15:45:36.992808'),
(37, 160, 1, NULL, 'Common IT Courses', 1, '2026-07-23 15:45:36.992808', '2026-07-23 15:45:36.992808'),
(38, 152, 5, NULL, 'Master of Education in Educational Technology', 1, '2026-07-23 15:45:36.992808', '2026-07-23 15:45:36.992808');

-- --------------------------------------------------------

--
-- Table structure for table `question_types`
--

CREATE TABLE `question_types` (
  `question_type_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recommendation_letters`
--

CREATE TABLE `recommendation_letters` (
  `recommendation_letter_id` bigint(20) UNSIGNED NOT NULL,
  `recommendation_request_id` bigint(20) UNSIGNED NOT NULL,
  `issued_by` bigint(20) UNSIGNED NOT NULL,
  `file_id` bigint(20) UNSIGNED DEFAULT NULL,
  `letter_title` varchar(250) DEFAULT NULL,
  `issued_date` date NOT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recommendation_requests`
--

CREATE TABLE `recommendation_requests` (
  `recommendation_request_id` bigint(20) UNSIGNED NOT NULL,
  `graduate_profile_id` bigint(20) UNSIGNED NOT NULL,
  `purpose` text NOT NULL,
  `request_status` varchar(30) NOT NULL DEFAULT 'Pending',
  `requested_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `processed_at` datetime(6) DEFAULT NULL,
  `processed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `report_name` varchar(250) NOT NULL,
  `report_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `file_id` bigint(20) UNSIGNED DEFAULT NULL,
  `generated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(60) NOT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `code`, `name`, `created_at`) VALUES
(1, 'graduate', 'Graduate', '2026-07-23 11:44:33.120405'),
(2, 'management', 'Management', '2026-07-23 11:44:33.120405'),
(3, 'administrator', 'Administrator', '2026-07-23 11:44:33.120405');

-- --------------------------------------------------------

--
-- Table structure for table `surveys`
--

CREATE TABLE `surveys` (
  `survey_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(250) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `available_from` datetime(6) DEFAULT NULL,
  `available_until` datetime(6) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `survey_answers`
--

CREATE TABLE `survey_answers` (
  `survey_answer_id` bigint(20) UNSIGNED NOT NULL,
  `survey_response_id` bigint(20) UNSIGNED NOT NULL,
  `survey_question_id` bigint(20) UNSIGNED NOT NULL,
  `survey_question_option_id` bigint(20) UNSIGNED DEFAULT NULL,
  `answer_text` text DEFAULT NULL,
  `answer_number` decimal(12,2) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `survey_invitations`
--

CREATE TABLE `survey_invitations` (
  `survey_invitation_id` bigint(20) UNSIGNED NOT NULL,
  `survey_id` bigint(20) UNSIGNED NOT NULL,
  `graduate_profile_id` bigint(20) UNSIGNED NOT NULL,
  `survey_response_status_id` bigint(20) UNSIGNED NOT NULL,
  `sent_at` datetime(6) DEFAULT NULL,
  `completed_at` datetime(6) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `survey_questions`
--

CREATE TABLE `survey_questions` (
  `survey_question_id` bigint(20) UNSIGNED NOT NULL,
  `survey_section_id` bigint(20) UNSIGNED NOT NULL,
  `prompt` text NOT NULL,
  `response_type` varchar(30) NOT NULL DEFAULT 'long_text',
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `survey_question_options`
--

CREATE TABLE `survey_question_options` (
  `survey_question_option_id` bigint(20) UNSIGNED NOT NULL,
  `survey_question_id` bigint(20) UNSIGNED NOT NULL,
  `label` varchar(250) NOT NULL,
  `value` varchar(250) NOT NULL,
  `display_order` smallint(5) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `survey_responses`
--

CREATE TABLE `survey_responses` (
  `survey_response_id` bigint(20) UNSIGNED NOT NULL,
  `survey_invitation_id` bigint(20) UNSIGNED NOT NULL,
  `submitted_at` datetime(6) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `survey_response_statuses`
--

CREATE TABLE `survey_response_statuses` (
  `survey_response_status_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_final` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `survey_sections`
--

CREATE TABLE `survey_sections` (
  `survey_section_id` bigint(20) UNSIGNED NOT NULL,
  `survey_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(250) NOT NULL,
  `display_order` smallint(5) UNSIGNED NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `account_status_id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(254) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `avatar_file_id` bigint(20) UNSIGNED DEFAULT NULL,
  `last_login_at` datetime(6) DEFAULT NULL,
  `email_verified_at` datetime(6) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `preference_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `preference_key` varchar(100) NOT NULL,
  `preference_value` varchar(255) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `remember_me` tinyint(1) NOT NULL DEFAULT 0,
  `issued_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `expires_at` datetime(6) NOT NULL,
  `last_used_at` datetime(6) DEFAULT NULL,
  `revoked_at` datetime(6) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_statuses`
--
ALTER TABLE `account_statuses`
  ADD PRIMARY KEY (`account_status_id`),
  ADD UNIQUE KEY `uq_account_statuses_code` (`code`),
  ADD UNIQUE KEY `uq_account_statuses_name` (`name`);

--
-- Indexes for table `administrator_profiles`
--
ALTER TABLE `administrator_profiles`
  ADD PRIMARY KEY (`administrator_profile_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `employee_number` (`employee_number`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `fk_announcement_creator` (`created_by`);

--
-- Indexes for table `announcement_recipients`
--
ALTER TABLE `announcement_recipients`
  ADD PRIMARY KEY (`announcement_recipient_id`),
  ADD UNIQUE KEY `uq_announcement_recipient_graduate` (`announcement_id`,`graduate_profile_id`),
  ADD KEY `fk_recipient_announcement` (`announcement_id`),
  ADD KEY `fk_recipient_graduate` (`graduate_profile_id`),
  ADD KEY `fk_recipient_faculty` (`faculty_id`),
  ADD KEY `fk_recipient_programme` (`programme_id`);

--
-- Indexes for table `businesses`
--
ALTER TABLE `businesses`
  ADD PRIMARY KEY (`business_id`),
  ADD KEY `fk_business_graduate` (`graduate_profile_id`);

--
-- Indexes for table `degree_classifications`
--
ALTER TABLE `degree_classifications`
  ADD PRIMARY KEY (`degree_classification_id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `faculty_id` (`faculty_id`,`name`);

--
-- Indexes for table `education_levels`
--
ALTER TABLE `education_levels`
  ADD PRIMARY KEY (`education_level_id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `employment_records`
--
ALTER TABLE `employment_records`
  ADD PRIMARY KEY (`employment_record_id`),
  ADD KEY `fk_employment_graduate` (`graduate_profile_id`),
  ADD KEY `fk_employment_status` (`employment_status_id`);

--
-- Indexes for table `employment_statuses`
--
ALTER TABLE `employment_statuses`
  ADD PRIMARY KEY (`employment_status_id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `fk_event_creator` (`created_by`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`event_registration_id`),
  ADD UNIQUE KEY `event_id` (`event_id`,`graduate_profile_id`),
  ADD KEY `fk_event_registration_graduate` (`graduate_profile_id`);

--
-- Indexes for table `faculties`
--
ALTER TABLE `faculties`
  ADD PRIMARY KEY (`faculty_id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`file_id`),
  ADD UNIQUE KEY `uq_files_storage_key` (`storage_key`),
  ADD KEY `idx_files_uploaded_by_user` (`uploaded_by_user_id`),
  ADD KEY `idx_files_mime_type` (`mime_type`),
  ADD KEY `idx_files_checksum` (`checksum_sha256`);

--
-- Indexes for table `graduate_profiles`
--
ALTER TABLE `graduate_profiles`
  ADD PRIMARY KEY (`graduate_profile_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `cv_file_id` (`cv_file_id`);

--
-- Indexes for table `graduate_qualifications`
--
ALTER TABLE `graduate_qualifications`
  ADD PRIMARY KEY (`graduate_qualification_id`),
  ADD UNIQUE KEY `admission_number` (`admission_number`),
  ADD UNIQUE KEY `registration_number` (`registration_number`),
  ADD KEY `fk_qualification_graduate` (`graduate_profile_id`),
  ADD KEY `fk_qualification_programme` (`programme_id`),
  ADD KEY `fk_qualification_status` (`graduation_status_id`),
  ADD KEY `fk_qualification_classification` (`degree_classification_id`);

--
-- Indexes for table `graduation_statuses`
--
ALTER TABLE `graduation_statuses`
  ADD PRIMARY KEY (`graduation_status_id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `management_profiles`
--
ALTER TABLE `management_profiles`
  ADD PRIMARY KEY (`management_profile_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `employee_number` (`employee_number`),
  ADD KEY `fk_management_faculty` (`faculty_id`),
  ADD KEY `fk_management_department` (`department_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `fk_notification_user` (`user_id`);

--
-- Indexes for table `notification_deliveries`
--
ALTER TABLE `notification_deliveries`
  ADD PRIMARY KEY (`delivery_id`),
  ADD UNIQUE KEY `uq_notification_delivery_channel` (`notification_id`,`channel`),
  ADD KEY `idx_notification_deliveries_user_status` (`user_id`,`status`);

--
-- Indexes for table `password_history`
--
ALTER TABLE `password_history`
  ADD PRIMARY KEY (`password_history_id`),
  ADD KEY `idx_password_history_user` (`user_id`),
  ADD KEY `idx_password_history_changed` (`changed_at`),
  ADD KEY `idx_password_history_user_changed` (`user_id`,`changed_at`);

--
-- Indexes for table `person_profiles`
--
ALTER TABLE `person_profiles`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_person_profiles_national_id` (`national_id`),
  ADD KEY `idx_person_profiles_phone` (`phone_number`);

--
-- Indexes for table `programmes`
--
ALTER TABLE `programmes`
  ADD PRIMARY KEY (`programme_id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD UNIQUE KEY `department_id` (`department_id`,`name`),
  ADD KEY `fk_programme_level` (`education_level_id`);

--
-- Indexes for table `question_types`
--
ALTER TABLE `question_types`
  ADD PRIMARY KEY (`question_type_id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `recommendation_letters`
--
ALTER TABLE `recommendation_letters`
  ADD PRIMARY KEY (`recommendation_letter_id`),
  ADD KEY `fk_recommendation_letter_request` (`recommendation_request_id`),
  ADD KEY `fk_recommendation_letter_staff` (`issued_by`),
  ADD KEY `fk_recommendation_letter_file` (`file_id`);

--
-- Indexes for table `recommendation_requests`
--
ALTER TABLE `recommendation_requests`
  ADD PRIMARY KEY (`recommendation_request_id`),
  ADD KEY `fk_recommendation_request_graduate` (`graduate_profile_id`),
  ADD KEY `fk_recommendation_request_staff` (`processed_by`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `fk_report_creator` (`created_by`),
  ADD KEY `fk_report_file` (`file_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `uq_roles_code` (`code`),
  ADD UNIQUE KEY `uq_roles_name` (`name`);

--
-- Indexes for table `surveys`
--
ALTER TABLE `surveys`
  ADD PRIMARY KEY (`survey_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `survey_answers`
--
ALTER TABLE `survey_answers`
  ADD PRIMARY KEY (`survey_answer_id`),
  ADD KEY `fk_answer_response` (`survey_response_id`),
  ADD KEY `fk_answer_question` (`survey_question_id`),
  ADD KEY `fk_answer_option` (`survey_question_option_id`);

--
-- Indexes for table `survey_invitations`
--
ALTER TABLE `survey_invitations`
  ADD PRIMARY KEY (`survey_invitation_id`),
  ADD UNIQUE KEY `survey_id` (`survey_id`,`graduate_profile_id`),
  ADD KEY `fk_invitation_graduate` (`graduate_profile_id`),
  ADD KEY `fk_invitation_status` (`survey_response_status_id`);

--
-- Indexes for table `survey_questions`
--
ALTER TABLE `survey_questions`
  ADD PRIMARY KEY (`survey_question_id`),
  ADD UNIQUE KEY `uq_question_order` (`survey_section_id`,`display_order`);

--
-- Indexes for table `survey_question_options`
--
ALTER TABLE `survey_question_options`
  ADD PRIMARY KEY (`survey_question_option_id`),
  ADD UNIQUE KEY `uq_question_option` (`survey_question_id`,`value`);

--
-- Indexes for table `survey_responses`
--
ALTER TABLE `survey_responses`
  ADD PRIMARY KEY (`survey_response_id`),
  ADD KEY `fk_response_invitation` (`survey_invitation_id`);

--
-- Indexes for table `survey_response_statuses`
--
ALTER TABLE `survey_response_statuses`
  ADD PRIMARY KEY (`survey_response_status_id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `survey_sections`
--
ALTER TABLE `survey_sections`
  ADD PRIMARY KEY (`survey_section_id`),
  ADD UNIQUE KEY `uq_survey_section_order` (`survey_id`,`display_order`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_role` (`role_id`),
  ADD KEY `idx_users_account_status` (`account_status_id`),
  ADD KEY `idx_users_last_name` (`last_name`),
  ADD KEY `idx_users_first_name` (`first_name`),
  ADD KEY `idx_users_last_login` (`last_login_at`),
  ADD KEY `idx_users_created_at` (`created_at`),
  ADD KEY `idx_users_role_status` (`role_id`,`account_status_id`),
  ADD KEY `fk_users_avatar` (`avatar_file_id`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`preference_id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`preference_key`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD UNIQUE KEY `uq_user_sessions_token_hash` (`token_hash`),
  ADD KEY `idx_user_sessions_user` (`user_id`),
  ADD KEY `idx_user_sessions_expires` (`expires_at`),
  ADD KEY `idx_user_sessions_revoked` (`revoked_at`),
  ADD KEY `idx_user_sessions_issued` (`issued_at`),
  ADD KEY `idx_user_sessions_user_revoked` (`user_id`,`revoked_at`),
  ADD KEY `idx_user_sessions_user_expires` (`user_id`,`expires_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account_statuses`
--
ALTER TABLE `account_statuses`
  MODIFY `account_status_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `administrator_profiles`
--
ALTER TABLE `administrator_profiles`
  MODIFY `administrator_profile_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcement_recipients`
--
ALTER TABLE `announcement_recipients`
  MODIFY `announcement_recipient_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `businesses`
--
ALTER TABLE `businesses`
  MODIFY `business_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `degree_classifications`
--
ALTER TABLE `degree_classifications`
  MODIFY `degree_classification_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=189;

--
-- AUTO_INCREMENT for table `education_levels`
--
ALTER TABLE `education_levels`
  MODIFY `education_level_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `employment_records`
--
ALTER TABLE `employment_records`
  MODIFY `employment_record_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employment_statuses`
--
ALTER TABLE `employment_statuses`
  MODIFY `employment_status_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `event_registration_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faculties`
--
ALTER TABLE `faculties`
  MODIFY `faculty_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `file_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `graduate_profiles`
--
ALTER TABLE `graduate_profiles`
  MODIFY `graduate_profile_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `graduate_qualifications`
--
ALTER TABLE `graduate_qualifications`
  MODIFY `graduate_qualification_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `graduation_statuses`
--
ALTER TABLE `graduation_statuses`
  MODIFY `graduation_status_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `management_profiles`
--
ALTER TABLE `management_profiles`
  MODIFY `management_profile_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_deliveries`
--
ALTER TABLE `notification_deliveries`
  MODIFY `delivery_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_history`
--
ALTER TABLE `password_history`
  MODIFY `password_history_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `programmes`
--
ALTER TABLE `programmes`
  MODIFY `programme_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `question_types`
--
ALTER TABLE `question_types`
  MODIFY `question_type_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recommendation_letters`
--
ALTER TABLE `recommendation_letters`
  MODIFY `recommendation_letter_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recommendation_requests`
--
ALTER TABLE `recommendation_requests`
  MODIFY `recommendation_request_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `surveys`
--
ALTER TABLE `surveys`
  MODIFY `survey_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `survey_answers`
--
ALTER TABLE `survey_answers`
  MODIFY `survey_answer_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `survey_invitations`
--
ALTER TABLE `survey_invitations`
  MODIFY `survey_invitation_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `survey_questions`
--
ALTER TABLE `survey_questions`
  MODIFY `survey_question_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `survey_question_options`
--
ALTER TABLE `survey_question_options`
  MODIFY `survey_question_option_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `survey_responses`
--
ALTER TABLE `survey_responses`
  MODIFY `survey_response_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `survey_response_statuses`
--
ALTER TABLE `survey_response_statuses`
  MODIFY `survey_response_status_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `survey_sections`
--
ALTER TABLE `survey_sections`
  MODIFY `survey_section_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `preference_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `session_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `administrator_profiles`
--
ALTER TABLE `administrator_profiles`
  ADD CONSTRAINT `fk_admin_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_announcement_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `announcement_recipients`
--
ALTER TABLE `announcement_recipients`
  ADD CONSTRAINT `fk_recipient_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`announcement_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_recipient_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`faculty_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_recipient_graduate` FOREIGN KEY (`graduate_profile_id`) REFERENCES `graduate_profiles` (`graduate_profile_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_recipient_programme` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`programme_id`) ON DELETE CASCADE;

--
-- Constraints for table `businesses`
--
ALTER TABLE `businesses`
  ADD CONSTRAINT `fk_business_graduate` FOREIGN KEY (`graduate_profile_id`) REFERENCES `graduate_profiles` (`graduate_profile_id`) ON DELETE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `fk_department_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`faculty_id`);

--
-- Constraints for table `employment_records`
--
ALTER TABLE `employment_records`
  ADD CONSTRAINT `fk_employment_graduate` FOREIGN KEY (`graduate_profile_id`) REFERENCES `graduate_profiles` (`graduate_profile_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_employment_status` FOREIGN KEY (`employment_status_id`) REFERENCES `employment_statuses` (`employment_status_id`);

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `fk_event_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `fk_event_registration_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_event_registration_graduate` FOREIGN KEY (`graduate_profile_id`) REFERENCES `graduate_profiles` (`graduate_profile_id`) ON DELETE CASCADE;

--
-- Constraints for table `files`
--
ALTER TABLE `files`
  ADD CONSTRAINT `fk_files_uploaded_by_user` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `graduate_profiles`
--
ALTER TABLE `graduate_profiles`
  ADD CONSTRAINT `fk_graduate_cv` FOREIGN KEY (`cv_file_id`) REFERENCES `files` (`file_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_graduate_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `graduate_qualifications`
--
ALTER TABLE `graduate_qualifications`
  ADD CONSTRAINT `fk_qualification_classification` FOREIGN KEY (`degree_classification_id`) REFERENCES `degree_classifications` (`degree_classification_id`),
  ADD CONSTRAINT `fk_qualification_graduate` FOREIGN KEY (`graduate_profile_id`) REFERENCES `graduate_profiles` (`graduate_profile_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_qualification_programme` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`programme_id`),
  ADD CONSTRAINT `fk_qualification_status` FOREIGN KEY (`graduation_status_id`) REFERENCES `graduation_statuses` (`graduation_status_id`);

--
-- Constraints for table `management_profiles`
--
ALTER TABLE `management_profiles`
  ADD CONSTRAINT `fk_management_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_management_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`faculty_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_management_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_deliveries`
--
ALTER TABLE `notification_deliveries`
  ADD CONSTRAINT `fk_notification_delivery_notification` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`notification_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_notification_delivery_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `password_history`
--
ALTER TABLE `password_history`
  ADD CONSTRAINT `fk_password_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `person_profiles`
--
ALTER TABLE `person_profiles`
  ADD CONSTRAINT `fk_person_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `programmes`
--
ALTER TABLE `programmes`
  ADD CONSTRAINT `fk_programme_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`),
  ADD CONSTRAINT `fk_programme_level` FOREIGN KEY (`education_level_id`) REFERENCES `education_levels` (`education_level_id`);

--
-- Constraints for table `recommendation_letters`
--
ALTER TABLE `recommendation_letters`
  ADD CONSTRAINT `fk_recommendation_letter_file` FOREIGN KEY (`file_id`) REFERENCES `files` (`file_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_recommendation_letter_request` FOREIGN KEY (`recommendation_request_id`) REFERENCES `recommendation_requests` (`recommendation_request_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_recommendation_letter_staff` FOREIGN KEY (`issued_by`) REFERENCES `management_profiles` (`management_profile_id`);

--
-- Constraints for table `recommendation_requests`
--
ALTER TABLE `recommendation_requests`
  ADD CONSTRAINT `fk_recommendation_request_graduate` FOREIGN KEY (`graduate_profile_id`) REFERENCES `graduate_profiles` (`graduate_profile_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_recommendation_request_staff` FOREIGN KEY (`processed_by`) REFERENCES `management_profiles` (`management_profile_id`) ON DELETE SET NULL;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_report_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_report_file` FOREIGN KEY (`file_id`) REFERENCES `files` (`file_id`) ON DELETE SET NULL;

--
-- Constraints for table `survey_answers`
--
ALTER TABLE `survey_answers`
  ADD CONSTRAINT `fk_answer_option` FOREIGN KEY (`survey_question_option_id`) REFERENCES `survey_question_options` (`survey_question_option_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_answer_question` FOREIGN KEY (`survey_question_id`) REFERENCES `survey_questions` (`survey_question_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_answer_response` FOREIGN KEY (`survey_response_id`) REFERENCES `survey_responses` (`survey_response_id`) ON DELETE CASCADE;

--
-- Constraints for table `survey_invitations`
--
ALTER TABLE `survey_invitations`
  ADD CONSTRAINT `fk_invitation_graduate` FOREIGN KEY (`graduate_profile_id`) REFERENCES `graduate_profiles` (`graduate_profile_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_invitation_status` FOREIGN KEY (`survey_response_status_id`) REFERENCES `survey_response_statuses` (`survey_response_status_id`),
  ADD CONSTRAINT `fk_invitation_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`survey_id`) ON DELETE CASCADE;

--
-- Constraints for table `survey_questions`
--
ALTER TABLE `survey_questions`
  ADD CONSTRAINT `fk_questions_section` FOREIGN KEY (`survey_section_id`) REFERENCES `survey_sections` (`survey_section_id`);

--
-- Constraints for table `survey_question_options`
--
ALTER TABLE `survey_question_options`
  ADD CONSTRAINT `fk_option_question` FOREIGN KEY (`survey_question_id`) REFERENCES `survey_questions` (`survey_question_id`);

--
-- Constraints for table `survey_responses`
--
ALTER TABLE `survey_responses`
  ADD CONSTRAINT `fk_response_invitation` FOREIGN KEY (`survey_invitation_id`) REFERENCES `survey_invitations` (`survey_invitation_id`) ON DELETE CASCADE;

--
-- Constraints for table `survey_sections`
--
ALTER TABLE `survey_sections`
  ADD CONSTRAINT `fk_survey_sections_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`survey_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_account_status` FOREIGN KEY (`account_status_id`) REFERENCES `account_statuses` (`account_status_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_users_avatar` FOREIGN KEY (`avatar_file_id`) REFERENCES `files` (`file_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON UPDATE CASCADE;

--
-- Constraints for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `fk_preference_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `fk_user_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

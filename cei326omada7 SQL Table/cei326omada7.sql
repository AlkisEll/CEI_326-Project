-- phpMyAdmin SQL Dump
-- version 5.2.2deb1+jammy3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 26, 2025 at 05:55 PM
-- Server version: 8.0.42-0ubuntu0.22.04.1
-- PHP Version: 8.1.2-1ubuntu2.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cei326omada7`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `period_id` int NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `rejection_reason` text COLLATE utf8mb4_general_ci,
  `reviewed_by` int DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `id_card` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gender` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nationality` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `current_position` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `current_employer` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `professional_experience` text COLLATE utf8mb4_general_ci,
  `expertise_area` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `project_highlights` text COLLATE utf8mb4_general_ci,
  `degree_level` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `degree_title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `institution` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `education_start_date` date DEFAULT NULL,
  `education_end_date` date DEFAULT NULL,
  `institution_country` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `degree_grade` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `thesis_title` text COLLATE utf8mb4_general_ci,
  `additional_qualifications` text COLLATE utf8mb4_general_ci,
  `expected_graduation_date` date DEFAULT NULL,
  `job_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `experience_start_date` date DEFAULT NULL,
  `experience_end_date` date DEFAULT NULL,
  `part_or_full_time` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `submitted_full_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `submitted_email` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `submitted_phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `submitted_address` text COLLATE utf8mb4_general_ci,
  `submitted_country` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `submitted_postcode` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `submitted_dob` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `user_id`, `period_id`, `status`, `created_at`, `rejection_reason`, `reviewed_by`, `reviewed_at`, `id_card`, `gender`, `nationality`, `current_position`, `current_employer`, `professional_experience`, `expertise_area`, `project_highlights`, `degree_level`, `degree_title`, `institution`, `education_start_date`, `education_end_date`, `institution_country`, `degree_grade`, `thesis_title`, `additional_qualifications`, `expected_graduation_date`, `job_type`, `experience_start_date`, `experience_end_date`, `part_or_full_time`, `submitted_full_name`, `submitted_email`, `submitted_phone`, `submitted_address`, `submitted_country`, `submitted_postcode`, `submitted_dob`) VALUES
(49, 24, 4, 'pending', '2025-05-22 11:51:23', NULL, NULL, NULL, '1127041', 'Male', 'Cypriot', 'none', '-', '-', '', '', 'none', 'No Degree Yet', NULL, NULL, NULL, NULL, NULL, '', '', '2025-05-24', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(50, 25, 4, 'rejected', '2025-05-22 13:30:33', 'couldn\'t pass', 2, '2025-05-22 13:46:50', '1127041', 'Male', 'Cypriot', 'δοδηφδ', 'δφσησδοη', 'δφφξδφηξοδφξηξοδφηξοηφγιξφηγδιξβγηξιβγξδι', 'Industry/Field Work', '', 'Bachelor (BSc, BA)', 'dfssfd', 'sdffds', '2025-05-22', '2025-05-30', 'Cyprus', '', '', '', NULL, NULL, '2025-05-22', '2025-06-20', 'Full Time', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(51, 25, 4, 'accepted', '2025-05-22 13:37:53', NULL, 2, '2025-05-22 13:45:26', '1127041', 'Male', 'Cypriot', 'δοδηφδ', 'δφσησδοη', 'δφφξδφηξοδφξηξοδφηξοηφγιξφηγδιξβγηξιβγξδι', 'Industry/Field Work', '', 'PhD / Doctorate', 'thriskeftika', 'theologiki sxoli', '2025-05-15', '2025-05-31', 'France', '', '', '', NULL, NULL, '2025-05-22', '2025-06-20', 'Full Time', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(54, 30, 5, 'pending', '2025-05-26 08:56:30', NULL, NULL, NULL, '1127041', 'Male', 'Cypriot', 'fsdfsdfsd', 'fdghgfh', 'ghfgfhghf', 'Research', '', 'Master (MSc, MA)', 'dgfhdfgsdfg', 'ghfghfgfh', '2025-05-13', '2025-05-29', 'Cyprus', '', '', '', NULL, NULL, '2025-05-15', '2025-06-06', 'Full Time', 'marios petrides', 'dfsdsfjhkl@gmail.com', '+35799478828', 'fgfgd 45', 'Cyprus (Κύπρος)', '3234', '2003-11-26');

-- --------------------------------------------------------

--
-- Table structure for table `application_courses`
--

CREATE TABLE `application_courses` (
  `id` int NOT NULL,
  `application_id` int NOT NULL,
  `course_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `application_courses`
--

INSERT INTO `application_courses` (`id`, `application_id`, `course_id`) VALUES
(1, 13, 31),
(2, 13, 30),
(3, 14, 31),
(4, 14, 30),
(5, 15, 31),
(6, 15, 30),
(7, 16, 31),
(8, 16, 30),
(9, 17, 31),
(10, 17, 30),
(11, 18, 31),
(12, 18, 30),
(13, 19, 31),
(14, 19, 30),
(15, 20, 31),
(16, 20, 30),
(17, 21, 31),
(18, 21, 30),
(19, 22, 31),
(20, 22, 30),
(21, 23, 31),
(22, 23, 30),
(23, 24, 31),
(24, 24, 30),
(25, 25, 31),
(26, 25, 30),
(27, 26, 31),
(28, 26, 30),
(29, 27, 31),
(30, 27, 30),
(31, 28, 31),
(32, 28, 30),
(33, 29, 31),
(34, 29, 30),
(35, 30, 31),
(36, 30, 30),
(37, 31, 31),
(38, 31, 30),
(39, 32, 31),
(40, 32, 30),
(41, 33, 31),
(42, 33, 30),
(43, 34, 31),
(44, 34, 30),
(45, 35, 31),
(46, 35, 30),
(47, 36, 8),
(48, 36, 44),
(49, 37, 31),
(50, 37, 30),
(51, 38, 31),
(52, 38, 30),
(53, 39, 37),
(54, 39, 39),
(55, 40, 15),
(56, 40, 13),
(57, 41, 31),
(58, 41, 30),
(59, 42, 31),
(60, 42, 30),
(61, 46, 31),
(62, 46, 30),
(63, 47, 31),
(64, 47, 30),
(65, 48, 27),
(66, 49, 31),
(67, 49, 30),
(68, 50, 44),
(69, 51, 15),
(70, 51, 13),
(71, 52, 31),
(72, 52, 30),
(73, 53, 31),
(74, 53, 30),
(75, 54, 31),
(76, 54, 30);

-- --------------------------------------------------------

--
-- Table structure for table `application_files`
--

CREATE TABLE `application_files` (
  `id` int NOT NULL,
  `application_id` int NOT NULL,
  `file_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `application_periods`
--

CREATE TABLE `application_periods` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `application_periods`
--

INSERT INTO `application_periods` (`id`, `name`, `start_date`, `end_date`) VALUES
(5, 'Summer 2025', '2025-06-01', '2025-08-31'),
(6, 'September 2024', '2024-09-03', '2024-09-22'),
(7, 'Spring 2025', '2025-03-01', '2025-05-31');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int NOT NULL,
  `department_id` int DEFAULT NULL,
  `course_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `course_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `department_id`, `course_code`, `course_name`, `category`) VALUES
(5, 5, 'CS101', 'Computer Science', 'Science & Engineering'),
(6, 5, 'AI301', 'Artificial Intelligence & Machine Learning', 'Science & Engineering'),
(7, 5, 'SE201', 'Software Engineering', 'Science & Engineering'),
(8, 5, 'WD202', 'Web Development / Mobile Applications', 'Science & Engineering'),
(9, 5, 'CSY303', 'Cybersecurity & Privacy', 'Science & Engineering'),
(10, 5, 'DS304', 'Data Science / Big Data', 'Science & Engineering'),
(11, 8, 'MATH101', 'Mathematics / Applied Mathematics', 'Science & Engineering'),
(12, 9, 'PHYS101', 'Physics', 'Science & Engineering'),
(13, 7, 'ENV201', 'Environmental Science / Climate Change', 'Science & Engineering'),
(14, 6, 'EE101', 'Electrical Engineering', 'Science & Engineering'),
(15, 7, 'CE101', 'Civil Engineering', 'Science & Engineering'),
(16, 6, 'ME101', 'Mechanical Engineering', 'Science & Engineering'),
(17, 6, 'BME201', 'Biomedical Engineering', 'Science & Engineering'),
(18, 12, 'MI301', 'Medical Informatics', 'Health Sciences'),
(19, 10, 'NS101', 'Nursing Science', 'Health Sciences'),
(20, 12, 'CRM401', 'Clinical Research Methods', 'Health Sciences'),
(21, 10, 'PH201', 'Public Health', 'Health Sciences'),
(22, 10, 'BST301', 'Biostatistics', 'Health Sciences'),
(23, 11, 'PHAR101', 'Pharmacy or Pharmacology', 'Health Sciences'),
(24, 13, 'COM101', 'Communication Studies', 'Humanities & Social Sciences'),
(25, 13, 'JDM201', 'Journalism / Digital Media', 'Humanities & Social Sciences'),
(26, 14, 'PSY101', 'Psychology', 'Humanities & Social Sciences'),
(27, 14, 'SOC101', 'Sociology', 'Humanities & Social Sciences'),
(28, 15, 'HIS101', 'History or Philosophy', 'Humanities & Social Sciences'),
(29, 15, 'LANG101', 'Modern Languages (Greek, English, etc.)', 'Humanities & Social Sciences'),
(30, 16, 'BA101', 'Business Administration', 'Business & Economics'),
(31, 16, 'AF201', 'Accounting & Finance', 'Business & Economics'),
(32, 19, 'ENT301', 'Entrepreneurship & Innovation', 'Business & Economics'),
(33, 19, 'MKT101', 'Marketing', 'Business & Economics'),
(34, 20, 'LSC401', 'Logistics & Supply Chain', 'Business & Economics'),
(35, 20, 'OM301', 'Organizational Management', 'Business & Economics'),
(36, 20, 'WT101', 'Web Technologies', 'ICT & Digital Skills'),
(37, 21, 'CC301', 'Cloud Computing', 'ICT & Digital Skills'),
(38, 21, 'DBS201', 'Database Systems', 'ICT & Digital Skills'),
(39, 21, 'NET401', 'Networking & Internet of Things (IoT)', 'ICT & Digital Skills'),
(40, 21, 'ID101', 'Instructional Design', 'Teaching Methodology & Educational Innovation'),
(41, 21, 'DE201', 'Distance Education / Moodle', 'Teaching Methodology & Educational Innovation'),
(42, 21, 'STEM301', 'STEM Education', 'Teaching Methodology & Educational Innovation'),
(43, 21, 'DP401', 'Digital Pedagogy', 'Teaching Methodology & Educational Innovation'),
(44, 5, 'CEI326', 'Web Engineering', 'Science & Engineering');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int NOT NULL,
  `school_id` int DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `school_id`, `name`) VALUES
(5, 5, 'Department of Computer Science & Engineering'),
(6, 5, 'Department of Electrical & Mechanical Engineering'),
(7, 5, 'Department of Civil & Environmental Engineering'),
(8, 5, 'Department of Mathematics & Statistics'),
(9, 5, 'Department of Physics'),
(10, 5, 'Department of Nursing & Public Health'),
(11, 5, 'Department of Pharmacy & Clinical Research'),
(12, 5, 'Department of Medical Informatics & Biostatistics'),
(13, 5, 'Department of Communication & Digital Media'),
(14, 5, 'Department of Psychology & Sociology'),
(15, 5, 'Department of History, Philosophy & Languages'),
(16, 5, 'Department of Business Administration'),
(17, 5, 'Department of Accounting & Finance'),
(18, 5, 'Department of Marketing & Entrepreneurship'),
(19, 5, 'Department of Logistics & Management'),
(20, 5, 'Department of Information Technology & Cybersecurity'),
(21, 5, 'Department of Educational Sciences & Digital Pedagogy');

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `code` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verifications`
--

INSERT INTO `email_verifications` (`id`, `user_id`, `email`, `code`, `created_at`) VALUES
(6, 32, 'em.solomonides@gmail.com', '381630', '2025-05-26 15:58:56');

-- --------------------------------------------------------

--
-- Table structure for table `evaluators`
--

CREATE TABLE `evaluators` (
  `id` int NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `submitted_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `user_id`, `message`, `submitted_at`) VALUES
(1, NULL, 'Hello', '2025-05-22 10:58:21'),
(2, 25, 'hello', '2025-05-22 13:26:06');

-- --------------------------------------------------------

--
-- Table structure for table `moodle_sync_logs`
--

CREATE TABLE `moodle_sync_logs` (
  `id` int NOT NULL,
  `type` enum('user','course') COLLATE utf8mb4_general_ci NOT NULL,
  `reference_id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('success','failure') COLLATE utf8mb4_general_ci NOT NULL,
  `message` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `moodle_sync_logs`
--

INSERT INTO `moodle_sync_logs` (`id`, `type`, `reference_id`, `status`, `message`, `created_at`) VALUES
(1, 'user', '0', 'success', 'User created successfully.', '2025-05-07 08:01:59'),
(2, 'course', '0', 'success', 'Course created successfully.', '2025-05-07 08:01:59'),
(3, 'user', '0', 'success', 'User created successfully.', '2025-05-07 08:01:59'),
(4, 'course', '0', 'success', 'Course created successfully.', '2025-05-07 08:01:59'),
(5, 'user', '0', 'failure', 'Short name is already used for another course (BST301)', '2025-05-07 08:02:00'),
(6, 'course', '0', 'failure', 'Short name is already used for another course (BST301)', '2025-05-07 08:02:00'),
(7, 'user', '0', 'failure', 'Short name is already used for another course (CC301)', '2025-05-07 08:02:00'),
(8, 'course', '0', 'failure', 'Short name is already used for another course (CC301)', '2025-05-07 08:02:00'),
(9, 'user', '0', 'success', 'User created successfully.', '2025-05-07 08:05:26'),
(10, 'course', '0', 'success', 'Course created successfully.', '2025-05-07 08:05:26'),
(11, 'user', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 08:05:48'),
(12, 'course', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 08:05:48'),
(13, 'user', '0', 'failure', 'Short name is already used for another course (BST301)', '2025-05-07 08:05:48'),
(14, 'course', '0', 'failure', 'Short name is already used for another course (BST301)', '2025-05-07 08:05:48'),
(15, 'user', '0', 'failure', 'Short name is already used for another course (CC301)', '2025-05-07 08:05:48'),
(16, 'course', '0', 'failure', 'Short name is already used for another course (CC301)', '2025-05-07 08:05:48'),
(17, 'user', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 11:09:25'),
(18, 'course', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 11:09:25'),
(19, 'user', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 11:30:37'),
(20, 'course', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 11:30:37'),
(21, 'user', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 11:31:43'),
(22, 'course', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 11:31:43'),
(23, 'user', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 11:38:28'),
(24, 'course', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 11:38:28'),
(25, 'user', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 11:43:29'),
(26, 'course', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 11:43:29'),
(27, 'user', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 11:43:51'),
(28, 'course', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 11:43:51'),
(29, 'user', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 11:45:23'),
(30, 'course', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 11:45:23'),
(31, 'user', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 11:49:30'),
(32, 'course', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 11:49:30'),
(33, 'user', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 11:49:36'),
(34, 'course', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 11:49:36'),
(35, 'user', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 11:49:49'),
(36, 'course', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 11:49:49'),
(37, 'user', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 11:50:59'),
(38, 'course', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 11:50:59'),
(39, 'user', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 11:57:16'),
(40, 'course', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 11:57:16'),
(41, 'user', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 12:03:34'),
(42, 'course', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 12:03:34'),
(43, 'user', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 12:09:01'),
(44, 'course', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 12:09:01'),
(45, 'user', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 12:11:13'),
(46, 'course', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 12:11:13'),
(47, 'user', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 12:14:15'),
(48, 'course', '0', 'failure', 'Short name is already used for another course (AF201)', '2025-05-07 12:14:15'),
(49, 'user', '0', 'success', 'User created successfully.', '2025-05-07 17:08:05'),
(50, 'course', '0', 'success', 'Course created successfully.', '2025-05-07 17:08:05'),
(51, 'user', 'nikoscy100', 'failure', 'User creation failed.', '2025-05-21 17:17:33'),
(52, 'course', 'AF201', 'failure', 'Could not create or retrieve course.', '2025-05-21 17:17:33'),
(53, 'course', 'BA101', 'failure', 'Could not create or retrieve course.', '2025-05-21 17:17:33'),
(54, 'user', 'nikoscy100', 'failure', 'User creation failed.', '2025-05-21 17:55:24'),
(55, 'course', 'AF201', 'failure', 'Could not create or retrieve course.', '2025-05-21 17:55:24'),
(56, 'course', 'BA101', 'failure', 'Could not create or retrieve course.', '2025-05-21 17:55:24'),
(57, 'user', '', 'success', 'User created successfully.', '2025-05-21 18:25:22'),
(58, 'course', 'AF201', 'success', 'Course created successfully.', '2025-05-21 18:25:22'),
(59, 'user', '', 'success', 'User created successfully.', '2025-05-21 18:25:23'),
(60, 'course', 'BA101', 'success', 'Course created successfully.', '2025-05-21 18:25:23'),
(61, 'user', '', 'success', 'User created successfully.', '2025-05-21 18:38:11'),
(62, 'course', 'CEI326', 'success', 'Course created successfully.', '2025-05-21 18:38:12'),
(63, 'user', '', 'success', 'User created successfully.', '2025-05-22 11:18:11'),
(64, 'course', 'AF201', 'success', 'Course created successfully.', '2025-05-22 11:18:11'),
(65, 'user', '', 'success', 'User created successfully.', '2025-05-22 11:18:11'),
(66, 'course', 'BA101', 'success', 'Course created successfully.', '2025-05-22 11:18:11'),
(67, 'user', '', 'success', 'User created successfully.', '2025-05-22 13:45:27'),
(68, 'course', 'CE101', 'success', 'Course created successfully.', '2025-05-22 13:45:27'),
(69, 'user', '', 'success', 'User created successfully.', '2025-05-22 13:45:27'),
(70, 'course', 'ENV201', 'success', 'Course created successfully.', '2025-05-22 13:45:27'),
(71, 'user', '', 'success', 'User created successfully.', '2025-05-22 13:50:56'),
(72, 'course', 'BME201', 'success', 'Course created successfully.', '2025-05-22 13:50:56'),
(73, 'user', '', 'success', 'User created successfully.', '2025-05-22 13:51:10'),
(74, 'course', 'BME201', 'success', 'Course created successfully.', '2025-05-22 13:51:10'),
(75, 'user', '', 'success', 'User created successfully.', '2025-05-22 13:52:01'),
(76, 'course', 'BST301', 'success', 'Course created successfully.', '2025-05-22 13:52:01'),
(77, 'user', '', 'success', 'User created successfully.', '2025-05-22 13:52:50'),
(78, 'course', 'CEI326', 'success', 'Course created successfully.', '2025-05-22 13:52:50'),
(79, 'user', '', 'success', 'User created successfully.', '2025-05-25 16:36:28'),
(80, 'course', 'BST301', 'success', 'Course created successfully.', '2025-05-25 16:36:28'),
(81, 'user', '', 'success', 'User created successfully.', '2025-05-25 16:36:31'),
(82, 'course', 'AI301', 'success', 'Course created successfully.', '2025-05-25 16:36:31'),
(83, 'user', '', 'success', 'User created successfully.', '2025-05-26 07:29:13'),
(84, 'course', 'AF201', 'success', 'Course created successfully.', '2025-05-26 07:29:13'),
(85, 'user', '', 'success', 'User created successfully.', '2025-05-26 07:29:13'),
(86, 'course', 'BA101', 'success', 'Course created successfully.', '2025-05-26 07:29:13');

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`id`, `name`) VALUES
(5, 'Cyprus University of Technology');

-- --------------------------------------------------------

--
-- Table structure for table `system_config`
--

CREATE TABLE `system_config` (
  `id` int NOT NULL,
  `config_key` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `config_value` text COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_config`
--

INSERT INTO `system_config` (`id`, `config_key`, `config_value`) VALUES
(1, 'site_title', 'Special Scientists'),
(2, 'moodle_url', 'https://cut.ac.cy'),
(5, 'logo_path', '');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int NOT NULL,
  `auto_sync_enabled` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `auto_sync_enabled`) VALUES
(1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `full_name` varchar(128) COLLATE utf8mb4_general_ci NOT NULL,
  `id_card` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `verification_token` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_verified` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reset_token` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `verification_code` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `postcode` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `twofa_code` varchar(6) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `twofa_expires` datetime DEFAULT NULL,
  `role` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'user',
  `profile_complete` tinyint(1) DEFAULT '0',
  `lms_access` tinyint(1) DEFAULT '0',
  `first_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `middle_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `id_card`, `email`, `username`, `password`, `verification_token`, `is_verified`, `reset_token`, `reset_token_expiry`, `verification_code`, `phone`, `country`, `city`, `address`, `postcode`, `dob`, `twofa_code`, `twofa_expires`, `role`, `profile_complete`, `lms_access`, `first_name`, `middle_name`, `last_name`, `last_login`, `updated_at`) VALUES
(1, 'Elias Solomonides', NULL, 'eliassolomonides0@gmail.com', 'elias1', '$2y$10$b3218/vXplCK6xJaJtxbkuvK4MLJ99iP1FVjcdeYgkhdjeNXvUt3u', NULL, '1', NULL, NULL, NULL, '+35799221775', 'Cyprus (Κύπρος)', 'Limassol', 'Darvinou 7', '3041', '2003-11-26', '855152', '2025-05-27 09:19:17', 'owner', 1, 0, NULL, NULL, NULL, '2025-05-26 12:56:47', '2025-05-26 12:56:47'),
(2, 'test test', NULL, 'solomonideselias@gmail.com', 'test2', '$2y$10$0sC6hyZW1qoTAo7tiDvxxO5dfLvxfc/Krc5e6Q3stes4PVwfb.kRm', NULL, '1', NULL, NULL, '691549', '+35799221775', 'Cyprus (Κύπρος)', 'Limassol', 'Darvinou 7', '3041', '2003-11-26', '347288', '2025-05-28 07:28:20', 'hr', 0, 0, NULL, NULL, NULL, '2025-05-22 13:43:33', '2025-05-26 07:28:20'),
(23, 'Alkis Ell', NULL, 'alkisellinides25@gmail.com', 'Alkis', '$2y$10$nrWKZ5a8jK3hFjlO4aUiQecaHuRObbXiMAkFOQJ5OEInhYpupK5zi', NULL, '1', NULL, NULL, '330270', '+35796610532', 'Cyprus (Κύπρος)', 'Nicosia', 'Loka 123', '1212', '2003-12-25', NULL, NULL, 'admin', 0, 0, 'Alkis', NULL, 'Ell', '2025-05-22 11:41:25', '2025-05-22 11:41:25'),
(24, 'sdfsfdgsdf sdfsfdgsdfg', NULL, 'eliassolomonides300@gmail.com', 'djsdok3', '$2y$10$xagibnYAi3WV4b8472xMz.bLaNhiRVqVmZNBe6FAAUAml/C6Sz8Y6', NULL, '1', NULL, NULL, '351499', '+35799221775', 'Cyprus (Κύπρος)', 'sddsffds', 'dsfdsf 4', '3041', '2003-02-11', '965338', '2025-05-24 14:08:50', 'user', 0, 0, 'sdfsfdgsdf', NULL, 'sdfsfdgsdfg', '2025-05-22 14:09:42', '2025-05-22 14:09:42'),
(25, 'kostas georgiou', NULL, 'eliassolomonides200@gmail.com', 'fddfgfgd5', '$2y$10$juF2qkZiJepUD35XdHczyund/y92GGz4FKZ1rUniE1PPePEq8AfxO', NULL, '1', NULL, NULL, '874822', '+35799221775', 'Cyprus (Κύπρος)', 'sddsffds ff', 'dsfdsf 4', '3041', '2003-11-26', '838444', '2025-05-28 06:45:04', 'scientist', 0, 1, 'dfgdfg', NULL, 'gfdfgd', '2025-05-26 15:25:39', '2025-05-26 15:25:39'),
(27, 'John 74t32u', NULL, 'aru@gmail.com', 'Me', '$2y$10$/j2bqsfB5lQVSJpsR/Zyeedr/oQQNgdH.0jk9LnyqhDglZJMytnZy', NULL, '0', NULL, NULL, NULL, '+35799558844', 'Cyprus (Κύπρος)', 'Limassol', 'G VHg', '698986', '2025-05-31', NULL, NULL, 'user', 0, 0, 'John', NULL, '74t32u', NULL, '2025-05-22 19:06:13'),
(28, 'Μικρός Αετός', NULL, 'chrodos@gmail.com', 'mikrosaetos', '$2y$10$1453wyww6Hqz3.qVE0ynyOi0DgxCB5C55nNNxI904wmnhNmzdu0o2', NULL, '1', NULL, NULL, NULL, '+35796123456', 'Cyprus (Κύπρος)', 'dfhhdfhg', 'hfghh', 'fghhdfghfg', '1900-06-06', NULL, NULL, 'user', 0, 0, 'Μικρός', NULL, 'Αετός', '2025-05-23 05:38:02', '2025-05-23 05:54:16'),
(30, 'marios petrides', NULL, 'dfsdsfjhkl@gmail.com', 'fdgfdgfd', '$2y$10$/uLpj4pa8cTcxdb9m5rxOu0p4T8SS6HklqFmpaeCmkV1Cde87QSye', NULL, '1', NULL, NULL, '320767', '+35799478828', 'Cyprus (Κύπρος)', 'fgdfdgfgd', 'fgfgd 45', '3234', '2003-11-26', '128167', '2025-05-28 07:05:13', 'scientist', 0, 1, 'fdsfg', NULL, 'fggd', '2025-05-26 07:05:32', '2025-05-26 08:39:22'),
(31, 'like a boss', NULL, 'icetigerboss@gmail.com', NULL, '$2y$10$3apKz424ST2tr2t4ac613.d9cF4XFvTl5fORRWpVpOX7U72sYvNvm', NULL, '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'user', 0, 0, NULL, NULL, NULL, '2025-05-26 13:42:36', '2025-05-26 13:42:36'),
(32, 'sdfsd dfsfgddfg', NULL, 'em.solomonides@gmail.com', 'fgdfgdfgd44', '$2y$10$EtVJGAGYoMAVAhl5wv3EEeZOKjWp8s5NjqLHfJpoV8bPIZiSTAmRO', NULL, '1', NULL, NULL, '159551', '+35799221775', 'Cyprus (Κύπρος)', 'fgchgvch', 'fgdfdg 5234', '2234', '2003-11-26', NULL, NULL, 'user', 0, 0, 'sdfsd', NULL, 'dfsfgddfg', NULL, '2025-05-26 15:58:07');

-- --------------------------------------------------------

--
-- Table structure for table `user_course_assignments`
--

CREATE TABLE `user_course_assignments` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `course_id` int NOT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `application_courses`
--
ALTER TABLE `application_courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `application_files`
--
ALTER TABLE `application_files`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `application_periods`
--
ALTER TABLE `application_periods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `evaluators`
--
ALTER TABLE `evaluators`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `moodle_sync_logs`
--
ALTER TABLE `moodle_sync_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_config`
--
ALTER TABLE `system_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `config_key` (`config_key`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_course_assignments`
--
ALTER TABLE `user_course_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`course_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `application_courses`
--
ALTER TABLE `application_courses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `application_files`
--
ALTER TABLE `application_files`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `application_periods`
--
ALTER TABLE `application_periods`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `evaluators`
--
ALTER TABLE `evaluators`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `moodle_sync_logs`
--
ALTER TABLE `moodle_sync_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `system_config`
--
ALTER TABLE `system_config`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `user_course_assignments`
--
ALTER TABLE `user_course_assignments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `evaluators`
--
ALTER TABLE `evaluators`
  ADD CONSTRAINT `evaluators_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

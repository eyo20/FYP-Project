-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2025-06-30 17:53:07
-- 服务器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `peer_tutoring_platform`
--

-- --------------------------------------------------------

--
-- 表的结构 `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `password`, `email`, `first_name`, `last_name`, `created_at`) VALUES
(1, 'admin', 'admin123', 'admin@example.com', 'System', 'Admin', '2025-04-22 16:30:11');

-- --------------------------------------------------------

--
-- 表的结构 `course`
--

CREATE TABLE `course` (
  `id` int(11) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `course`
--

INSERT INTO `course` (`id`, `course_name`, `details`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Program Design', NULL, 'active', '2025-06-02 15:51:07', '2025-06-02 15:51:07'),
(2, 'Calculus & Algebra', NULL, 'active', '2025-06-02 15:51:07', '2025-06-02 15:51:07'),
(3, 'Data Communications & Networking', NULL, 'active', '2025-06-02 15:51:07', '2025-06-02 15:51:07'),
(4, 'Operating Systems', NULL, 'active', '2025-06-02 15:51:07', '2025-06-02 15:51:07'),
(5, 'Systems Analysis & Design', NULL, 'active', '2025-06-02 15:51:07', '2025-06-02 15:51:07'),
(6, 'Computer Architecture', NULL, 'active', '2025-06-02 15:51:07', '2025-06-02 15:51:07'),
(7, 'Mathematical & Statistical Techniques', NULL, 'active', '2025-06-02 15:51:07', '2025-06-02 15:51:07'),
(8, 'Discrete Structures & Probability', NULL, 'active', '2025-06-02 15:51:07', '2025-06-02 15:51:07'),
(9, 'Database Systems', NULL, 'active', '2025-06-02 15:51:07', '2025-06-02 15:51:07'),
(16, 'Final Year Project', NULL, 'active', '2025-06-18 22:51:05', '2025-06-18 22:51:05'),
(17, 'Human Machine Interaction', NULL, 'active', '2025-06-18 22:51:33', '2025-06-18 22:51:33');

-- --------------------------------------------------------

--
-- 表的结构 `credential_file`
--

CREATE TABLE `credential_file` (
  `file_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_verified` tinyint(1) DEFAULT 0,
  `verified_by` int(11) DEFAULT NULL,
  `verification_date` datetime DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `credential_file`
--

INSERT INTO `credential_file` (`file_id`, `file_name`, `file_path`, `file_type`, `upload_date`, `is_verified`, `verified_by`, `verification_date`, `status`, `rejection_reason`, `user_id`) VALUES
(5, 'DAVID CHONG YUN HIN_1231201533_ACADEMIC TRANSCIPT (1).pdf', 'Uploads/credentials/6862b24f91538_DAVID_CHONG_YUN_HIN_1231201533_ACADEMIC_TRANSCIPT__1_.pdf', 'cgpa', '2025-06-30 15:50:39', 0, NULL, NULL, 'pending', NULL, 16);

-- --------------------------------------------------------

--
-- 表的结构 `location`
--

CREATE TABLE `location` (
  `location_id` int(11) NOT NULL,
  `location_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `location`
--

INSERT INTO `location` (`location_id`, `location_name`) VALUES
(1, 'Library'),
(2, 'Learning Point Study Room'),
(3, 'Campus Cafe');

-- --------------------------------------------------------

--
-- 表的结构 `message`
--

CREATE TABLE `message` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `sent_datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `is_community` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `message`
--

INSERT INTO `message` (`message_id`, `sender_id`, `receiver_id`, `content`, `sent_datetime`, `is_read`, `is_community`) VALUES
(2, 28, 16, 'Hi', '2025-06-12 03:21:44', 1, 0),
(9, 17, 25, 'Hi', '2025-06-16 02:47:05', 0, 0),
(10, 17, 25, 'Good Morning', '2025-06-16 03:12:09', 0, 0),
(91, 32, NULL, 'hi', '2025-06-19 10:37:50', 0, 1),
(93, 32, 21, 'Hi', '2025-06-19 10:50:14', 0, 0),
(94, 32, NULL, 'hi', '2025-06-19 10:55:27', 0, 1),
(95, 32, 17, 'hi', '2025-06-19 10:55:33', 1, 0),
(97, 32, 17, 'Good morning', '2025-06-19 12:01:12', 1, 0),
(101, 32, NULL, 'Community function test 19/06/2025', '2025-06-19 14:25:30', 0, 1),
(103, 17, 21, 'hhhh', '2025-06-19 23:02:52', 0, 0),
(105, 32, 17, 'Hi', '2025-06-19 23:04:06', 1, 0),
(106, 32, NULL, 'JHHHH', '2025-06-19 23:04:40', 0, 1),
(107, 28, 26, 'good morning', '2025-06-19 23:21:58', 1, 0),
(109, 17, 26, 'Your Operating Systems course in the 10:00-12:00 slot on Sep 22, 2025 has been cancelled. You can rebook another session or discuss alternative timings with your tutor.', '2025-06-30 20:20:42', 1, 0),
(110, 23, 28, 'hi admin,a tutor named \" mingwen \" not pepared enough for the session.', '2025-06-30 20:35:59', 1, 0),
(111, 23, NULL, 'hi everyone ,nice to meet yall .I\'m freshie in FIST Diploma', '2025-06-30 20:39:15', 0, 1),
(112, 23, NULL, 'What course do yall thing i shall take session as a freshie?', '2025-06-30 20:40:46', 0, 1),
(113, 26, NULL, 'Welcome to MMU,freshie. I am ur senior in Diploma Year 2.If you have a challenging course like Programme Design or Database with assignments or exams soon, prioritize those, as they form the core of IT skills and often require consistent practice.', '2025-06-30 20:45:31', 0, 1);

-- --------------------------------------------------------

--
-- 表的结构 `notification`
--

CREATE TABLE `notification` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `type` enum('session','payment','message','review','system') NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `notification`
--

INSERT INTO `notification` (`notification_id`, `user_id`, `title`, `message`, `type`, `related_id`, `is_read`, `created_at`) VALUES
(1, 23, 'Request Accepted', 'Your tutoring request has been accepted. Please discuss timing with your tutor.', 'session', 9, 0, '2025-06-03 19:08:31'),
(2, 23, 'Request Accepted', 'Your tutoring request has been accepted. Please discuss timing with your tutor.', 'session', 10, 0, '2025-06-03 19:25:39'),
(3, 23, 'Request Accepted', 'Your tutoring request has been accepted. Please discuss timing with your tutor.', 'session', 11, 0, '2025-06-03 20:20:42'),
(4, 23, 'Request Rejected', 'Your tutoring request has been rejected.', 'session', 12, 0, '2025-06-03 22:40:01'),
(5, 23, 'Request Accepted', 'Your tutoring request has been accepted. Please discuss timing with your tutor.', 'session', 13, 0, '2025-06-03 22:56:28'),
(6, 23, 'Request Accepted', 'Your tutoring request has been accepted. Please discuss timing with your tutor.', 'session', 14, 0, '2025-06-03 23:11:32'),
(7, 23, 'Request Accepted', 'Your tutoring request has been accepted. Please discuss timing with your tutor.', 'session', 16, 0, '2025-06-15 20:40:45'),
(8, 23, 'Request Accepted', 'Your tutoring request has been accepted. Please discuss timing with your tutor.', 'session', 19, 0, '2025-06-15 21:12:49'),
(9, 23, 'Request Rejected', 'Your tutoring request was rejected due to a conflicting accepted request.', 'session', 21, 0, '2025-06-15 23:14:03'),
(10, 23, 'Request Accepted', 'Your tutoring request has been accepted. Please discuss timing with your tutor.', 'session', 20, 0, '2025-06-15 23:14:03'),
(11, 23, 'Request Rejected', 'Your tutoring request was rejected due to a conflicting accepted request.', 'session', 23, 0, '2025-06-15 23:20:25'),
(12, 23, 'Request Accepted', 'Your tutoring request has been accepted. Please discuss timing with your tutor.', 'session', 22, 0, '2025-06-15 23:20:25'),
(15, 23, 'Request Rejected', 'Your Calculus & Algebra course in the 10:00-12:00 slot on Jun 16, 2025 has been cancelled. You can rebook another session or discuss alternative timings with your tutor.', 'session', 25, 0, '2025-06-15 23:45:25'),
(16, 23, 'Request Accepted', 'Your tutoring request has been accepted. Please discuss timing with your tutor.', 'session', 24, 0, '2025-06-15 23:45:25'),
(17, 23, 'Request Rejected', 'Your Calculus & Algebra course in the 08:00-10:00 slot on Jun 17, 2025 has been cancelled. You can rebook another session or discuss alternative timings with your tutor.', 'session', 26, 0, '2025-06-17 14:17:49'),
(18, 23, 'Request Accepted', 'Your tutoring request has been accepted. Please discuss timing with your tutor.', 'session', 27, 0, '2025-06-17 14:17:49'),
(19, 23, 'Request Accepted', 'Your tutoring request has been accepted. Please discuss timing with your tutor.', 'session', 28, 0, '2025-06-17 16:09:20'),
(20, 23, 'Request Rejected', 'Your Program Design course in the 10:00-12:00 slot on Jun 18, 2025 has been cancelled. You can rebook another session or discuss alternative timings with your tutor.', 'session', 29, 0, '2025-06-18 20:57:20'),
(21, 26, 'Request Rejected', 'Your Computer Architecture course in the 08:00-10:00 slot on Jun 19, 2025 has been cancelled. You can rebook another session or discuss alternative timings with your tutor.', 'session', 30, 0, '2025-06-18 21:59:05'),
(22, 26, 'Request Accepted', 'Your tutoring request has been accepted. Please discuss timing with your tutor.', 'session', 31, 0, '2025-06-18 21:59:05'),
(23, 26, 'Request Accepted', 'Your tutoring request has been accepted. Please discuss timing with your tutor.', 'session', 33, 0, '2025-06-18 22:30:10'),
(24, 26, 'Request Accepted', 'Your tutoring request has been accepted. Please discuss timing with your tutor.', 'session', 34, 0, '2025-06-18 22:55:16'),
(25, 26, 'Request Rejected', 'Your Operating Systems course in the 10:00-12:00 slot on Sep 22, 2025 has been cancelled. You can rebook another session or discuss alternative timings with your tutor.', 'session', 36, 0, '2025-06-30 12:20:42'),
(26, 23, 'Request Accepted', 'Your tutoring request has been accepted. Please discuss timing with your tutor.', 'session', 35, 0, '2025-06-30 12:20:42');

-- --------------------------------------------------------

--
-- 表的结构 `password_reset`
--

CREATE TABLE `password_reset` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `password_reset`
--

INSERT INTO `password_reset` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(22, '1231201533@student.mmu.edu.my', '890113', '2025-06-19 23:02:12', '2025-06-19 14:47:12');

-- --------------------------------------------------------

--
-- 表的结构 `review`
--

CREATE TABLE `review` (
  `review_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `tutor_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `review`
--

INSERT INTO `review` (`review_id`, `session_id`, `student_id`, `tutor_id`, `rating`, `comment`, `is_approved`, `approved_by`, `created_at`) VALUES
(4, 9, 23, 16, 5, 'overall ok', 1, NULL, '2025-06-18 23:34:14'),
(5, 13, 23, 16, 3, 'normal exp', 1, NULL, '2025-06-19 15:41:34'),
(6, 17, 26, 27, 5, 'best exp i ever had.', 1, NULL, '2025-06-30 12:10:14'),
(7, 11, 23, 16, 2, 'bad exp\r\n', 1, NULL, '2025-06-30 12:28:31');

-- --------------------------------------------------------

--
-- 表的结构 `session`
--

CREATE TABLE `session` (
  `session_id` int(11) NOT NULL,
  `tutor_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `status` enum('pending','confirmed','rejected','completed','cancelled') NOT NULL DEFAULT 'pending',
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `cancellation_deadline` datetime GENERATED ALWAYS AS (`start_datetime` - interval 24 hour) STORED,
  `cancellation_reason` text DEFAULT NULL,
  `cancelled_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email_sent` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `session`
--

INSERT INTO `session` (`session_id`, `tutor_id`, `student_id`, `course_id`, `location_id`, `status`, `start_datetime`, `end_datetime`, `cancellation_reason`, `cancelled_by`, `created_at`, `email_sent`) VALUES
(8, 16, 23, 3, 3, 'completed', '2025-06-30 08:00:00', '2025-06-30 10:00:00', NULL, NULL, '2025-06-15 21:12:49', 0),
(9, 16, 23, 2, 1, 'completed', '2025-06-16 08:00:00', '2025-06-16 10:00:00', NULL, NULL, '2025-06-15 23:14:03', 0),
(11, 16, 23, 1, 1, 'completed', '2025-06-30 10:00:00', '2025-06-30 12:00:00', NULL, NULL, '2025-06-15 23:20:25', 0),
(12, 16, 23, 2, 1, 'completed', '2025-06-16 10:00:00', '2025-06-16 12:00:00', NULL, NULL, '2025-06-15 23:45:25', 0),
(13, 16, 23, 2, 1, 'completed', '2025-06-17 08:00:00', '2025-06-17 10:00:00', NULL, NULL, '2025-06-17 14:17:49', 0),
(14, 16, 23, 1, 2, 'completed', '2025-06-25 08:00:00', '2025-06-25 10:00:00', NULL, NULL, '2025-06-17 16:09:20', 0),
(15, 16, 26, 1, 2, 'completed', '2025-06-19 08:00:00', '2025-06-19 10:00:00', NULL, NULL, '2025-06-18 21:59:05', 0),
(16, 27, 26, 4, 2, 'completed', '2025-06-18 10:00:00', '2025-06-18 12:00:00', NULL, NULL, '2025-06-16 22:30:10', 0),
(17, 27, 26, 2, 3, 'completed', '2025-06-20 08:00:00', '2025-06-20 10:00:00', NULL, NULL, '2025-06-18 22:55:16', 0),
(18, 27, 23, 2, 2, 'confirmed', '2025-09-22 10:00:00', '2025-09-22 12:00:00', NULL, NULL, '2025-06-30 12:20:42', 0);

-- --------------------------------------------------------

--
-- 表的结构 `session_requests`
--

CREATE TABLE `session_requests` (
  `request_id` int(11) NOT NULL,
  `tutor_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `time_slot` varchar(20) NOT NULL,
  `selected_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','confirmed','rejected','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `session_requests`
--

INSERT INTO `session_requests` (`request_id`, `tutor_id`, `student_id`, `course_id`, `location_id`, `time_slot`, `selected_date`, `notes`, `status`, `created_at`) VALUES
(19, 16, 23, 3, 3, '08:00-10:00', '2025-06-30', '', 'confirmed', '2025-06-16 05:12:32'),
(20, 16, 23, 2, 1, '08:00-10:00', '2025-06-16', '', 'confirmed', '2025-06-16 07:12:05'),
(21, 16, 23, 2, 1, '08:00-10:00', '2025-06-16', '', 'rejected', '2025-06-16 07:13:49'),
(22, 16, 23, 1, 1, '10:00-12:00', '2025-06-30', '', 'confirmed', '2025-06-16 07:19:57'),
(23, 16, 23, 1, 1, '10:00-12:00', '2025-06-30', '', 'rejected', '2025-06-16 07:20:09'),
(24, 16, 23, 2, 1, '10:00-12:00', '2025-06-16', '', 'confirmed', '2025-06-16 07:37:18'),
(25, 16, 23, 2, 1, '10:00-12:00', '2025-06-16', '', 'rejected', '2025-06-16 07:37:30'),
(26, 16, 23, 2, 2, '08:00-10:00', '2025-06-17', '', 'rejected', '2025-06-17 22:13:47'),
(27, 16, 23, 2, 1, '08:00-10:00', '2025-06-17', '', 'confirmed', '2025-06-17 22:14:27'),
(28, 16, 23, 1, 2, '08:00-10:00', '2025-06-25', '', 'confirmed', '2025-06-17 23:37:09'),
(29, 16, 23, 1, 2, '10:00-12:00', '2025-06-18', '', 'rejected', '2025-06-17 23:37:21'),
(30, 16, 26, 6, 1, '08:00-10:00', '2025-06-19', 'i would like to have a revision for Midterm Test.', 'rejected', '2025-06-19 05:47:39'),
(31, 16, 26, 1, 2, '08:00-10:00', '2025-06-19', 'i would like to have a revision for Final Test.', 'confirmed', '2025-06-19 05:49:38'),
(32, 16, 26, 3, 3, '12:00-14:00', '2025-06-30', 'please give me some advise towards the project.', 'pending', '2025-06-19 05:53:59'),
(33, 27, 26, 4, 2, '10:00-12:00', '2025-06-20', 'i would like to have a revision for Midterm Test.', 'confirmed', '2025-06-19 06:29:14'),
(34, 27, 26, 2, 3, '08:00-10:00', '2025-06-20', '', 'confirmed', '2025-06-19 06:55:06'),
(35, 27, 23, 2, 2, '10:00-12:00', '2025-09-22', 'I want to have a full preparation for the incoming semester\'s course,please provide structure advice and learning material towards the test.', 'confirmed', '2025-06-30 20:17:58'),
(36, 27, 26, 4, 2, '10:00-12:00', '2025-09-22', '', 'rejected', '2025-06-30 20:20:02');

-- --------------------------------------------------------

--
-- 表的结构 `studentprofile`
--

CREATE TABLE `studentprofile` (
  `user_id` int(11) NOT NULL,
  `major` varchar(100) DEFAULT NULL,
  `year` varchar(50) DEFAULT NULL,
  `school` varchar(100) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `actions` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `student_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `studentprofile`
--

INSERT INTO `studentprofile` (`user_id`, `major`, `year`, `school`, `program`, `status`, `actions`, `created_at`, `updated_at`, `student_name`) VALUES
(23, '', '', '', 'IT', 'approved', NULL, NULL, NULL, NULL),
(26, '', '', '', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- 表的结构 `tutorprofile`
--

CREATE TABLE `tutorprofile` (
  `user_id` int(11) NOT NULL,
  `major` varchar(100) DEFAULT NULL,
  `year` varchar(50) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `qualifications` text DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_sessions` int(11) DEFAULT 0,
  `program` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `tutorprofile`
--

INSERT INTO `tutorprofile` (`user_id`, `major`, `year`, `bio`, `qualifications`, `is_verified`, `rating`, `total_sessions`, `program`, `status`) VALUES
(16, 'Software Engineering', 'Degree second year', 'I’m a committed MMU IT graduate with over 4 years of experience in web development, cybersecurity, and system administration. Having completed my Diploma in Information Technology and Bachelor’s in Software Engineering, I provide tailored support to Diploma in IT students, specializing in HTML/CSS, ethical hacking, and Linux administration. My professional stints at CyberSec Innovations and WebTech Solutions, along with mentoring 15+ students with a 25% average grade boost, help me connect academic concepts to real-world applications, setting you up for industry readiness!', 'Diploma in Information Technology - Multimedia University (MMU), 2019: Focused on web technologies, security basics, and system operations, building a robust IT skill set.\r\n\r\n\r\n\r\nBachelor’s Degree in Software Engineering - Multimedia University (MMU), 2022: Deepened expertise in software architecture, security protocols, and DevOps practices.\r\nCertified Ethical Hacker (CEH) - EC-Council, 2020: Certifies skills in penetration testing and network security, ideal for teaching cybersecurity.\r\nLinux+ Certification - CompTIA, 2021: Validates proficiency in Linux system management, enhancing practical IT instruction.\r\nGoogle Cloud Digital Leader - Google Cloud, 2023: Confirms understanding of cloud solutions, supporting modern IT education.', 0, 3.33, 7, 'IT', 'approved'),
(27, 'Computer Science', 'Degree second year', 'I’m a dedicated MMU IT student with over 5 years of experience in software development, network administration, and database management. Having earned my Diploma in Information Technology and pursued a Bachelor’s in Computer Science, I offer personalized guidance to fellow Diploma in IT students, focusing on programming, troubleshooting, and tools like Git and SQL. My industry roles at TechSolutions and NetCore, plus mentoring 20+ students with 20% grade improvements, enable me to bridge theory and practice, preparing you for internships and career success!', 'Diploma in Information Technology - Multimedia University (MMU), 2020\r\nCompleted with a focus on software development, networking, and database management, laying a strong foundation for IT expertise.\r\n\r\n\r\n\r\nBachelor’s Degree in Computer Science - Multimedia University (MMU), 2023\r\nAdvanced studies in algorithms, system design, and cloud computing, enhancing practical and theoretical IT skills.\r\n\r\n\r\n\r\nCertified Associate in Python Programming (PCAP) - Python Institute, 2021\r\nDemonstrates proficiency in Python for coding and scripting, beneficial for teaching programming fundamentals.\r\n\r\n\r\n\r\nCompTIA Network+ Certification - CompTIA, 2022\r\nValidates expertise in network configuration and troubleshooting, supporting hands-on IT training.\r\n\r\n\r\n\r\nAWS Certified Cloud Practitioner - Amazon Web Services, 2023\r\nCertifies knowledge of cloud concepts and AWS services, enabling guidance on modern IT infrastructure.', 0, 5.00, 2, 'IT', 'pending'),
(29, 'Artificial Intelligence', 'Degree second year', 'I’m a dedicated MMU IT professional with 5 years of experience in AI development, database design, and network security. Holding a Diploma in Information Technology and a Bachelor’s in Artificial Intelligence, I provide personalized tutoring for Diploma in IT students, specializing in machine learning, SQL optimization, and firewall configuration. My work at AI Innovations and SecureNet Systems, along with mentoring 12+ students to a 30% grade improvement, equips me to link theoretical knowledge with industry practices, paving the way for your tech success!', 'Diploma in Information Technology - Multimedia University (MMU), 2019: Covered AI basics, database systems, and network security essentials.\r\n\r\n\r\n\r\nBachelor’s Degree in Artificial Intelligence - Multimedia University (MMU), 2022: Advanced skills in neural networks, natural language processing, and AI deployment.\r\n\r\n\r\n\r\nOracle Certified Professional: Java SE Programmer - Oracle, 2020: Demonstrates Java proficiency, key for teaching programming concepts.\r\n\r\n\r\n\r\nCisco Certified Network Associate (CCNA) - Cisco, 2021: Certifies networking skills, ideal for hands-on security training.\r\n\r\n\r\n\r\nMicrosoft Azure AI Fundamentals - Microsoft, 2023: Confirms expertise in AI on Azure, supporting cutting-edge IT education.', 0, 0.00, 0, 'IT', 'pending');

-- --------------------------------------------------------

--
-- 表的结构 `tutorsubject`
--

CREATE TABLE `tutorsubject` (
  `id` int(11) NOT NULL,
  `tutor_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `tutorsubject`
--

INSERT INTO `tutorsubject` (`id`, `tutor_id`, `course_id`, `hourly_rate`) VALUES
(4, 16, 2, 30.00),
(5, 16, 1, 20.00),
(6, 16, 6, 30.00),
(7, 16, 3, 50.00),
(8, 27, 4, 40.00),
(9, 27, 7, 30.00),
(10, 27, 2, 35.00),
(11, 29, 16, 50.00),
(12, 29, 1, 40.00);

-- --------------------------------------------------------

--
-- 表的结构 `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('student','tutor','admin') NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `reset_token_hash` varchar(64) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `user`
--

INSERT INTO `user` (`user_id`, `username`, `password`, `email`, `role`, `first_name`, `last_name`, `phone`, `profile_image`, `is_active`, `created_at`, `last_login`, `reset_token_hash`, `reset_token_expires_at`) VALUES
(16, 'mingwen', '$2y$10$9BtLg9AS1waXzlUC7YArjeiRhxLax9jZFSa5pLbcv5oKgLAzlXb/i', 'mingwen123@student.mmu.edu.my', 'tutor', 'mingwengg', 'koh', '0123456789', 'Uploads/profile_images/16_1750019694_Multimedia-University-MMU-Cyberjaya-Malaysia.jpg', 1, '2025-04-23 17:15:45', NULL, NULL, NULL),
(17, 'admin2', '$2y$10$L5c70bKowCIl98ItcrvCmOPLYYVFnz66EEdv/vhNf98A8elwthKKu', '1231203077@student.mmu.edu.my', 'admin', '', '', NULL, 'uploads\\profile_images\\68093d8631aa8_imgstudent.png', 1, '2025-06-30 12:23:33', NULL, NULL, NULL),
(23, 'ongenyong1', '$2y$10$yLVbXt7Vmv90ol5HI7REWu1vwbH28IJE.Dk9sTgCXvdFH8HiKYiZ2', 'fufufefe123@student.mmu.edu.my', 'student', 'dovid', 'ong', '01110377800', 'Uploads/profile_images/684f32e29ca95_Zombatar_1.jpg', 1, '2025-05-01 10:30:35', NULL, NULL, NULL),
(26, 'ey', '$2y$10$nE4obq6O1iacKRgVbrut0e22WkI/cSq8LIoio6Ei0CAB6JnJ/.c0y', '1231203070@student.mmu.edu.my', 'student', 'Lebron', 'James', '01110377800', 'Uploads/profile_images/685337bc0566f_lebron_prof.jpg', 1, '2025-06-18 21:44:41', NULL, NULL, NULL),
(27, 'chong hin', '$2y$10$sw7L5u9bKO6J5/rxOgQpq.f74keAnplpCTe7vy4dWaXQUFC2/6q36', '1231201533@student.mmu.edu.my', 'tutor', 'chong', 'hin', '0127770233', 'Uploads/profile_images/27_1750289265_Chihuahua.jpg', 1, '2025-06-18 22:14:37', NULL, NULL, NULL),
(28, 'admin', '$2y$10$PEHBJW7zsq3VhOfiTtnbL.FPcIY.cddmxYWgHwPUUcOESDJoaU3W.', 'ongenyong22@gmail.com', 'admin', '', '', NULL, 'uploads\\profile_images\\admin_icon.png', 1, '2025-06-18 22:37:24', NULL, NULL, NULL),
(29, 'Darren', '$2y$10$RBTiVDj6TAYc6/qsDBMbXe1/bipxiJAhvWzEIwwXzwbtV6j2tFTHC', '1231203078@student.mmu.edu.my', 'tutor', 'Darren', 'New', '0123456782', 'Uploads/profile_images/29_1751291562_javascript.png', 1, '2025-06-30 13:52:23', NULL, NULL, NULL);

--
-- 转储表的索引
--

--
-- 表的索引 `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- 表的索引 `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `credential_file`
--
ALTER TABLE `credential_file`
  ADD PRIMARY KEY (`file_id`);

--
-- 表的索引 `location`
--
ALTER TABLE `location`
  ADD PRIMARY KEY (`location_id`);

--
-- 表的索引 `message`
--
ALTER TABLE `message`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `is_read` (`is_read`);

--
-- 表的索引 `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_notification_user_read` (`user_id`,`is_read`);

--
-- 表的索引 `password_reset`
--
ALTER TABLE `password_reset`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `uq_review_session_student` (`session_id`,`student_id`),
  ADD KEY `fk_review_student` (`student_id`),
  ADD KEY `fk_review_tutor` (`tutor_id`),
  ADD KEY `fk_review_approved_by` (`approved_by`);

--
-- 表的索引 `session`
--
ALTER TABLE `session`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `fk_session_course` (`course_id`),
  ADD KEY `fk_session_location` (`location_id`),
  ADD KEY `fk_session_cancelled_by` (`cancelled_by`),
  ADD KEY `idx_session_status` (`status`),
  ADD KEY `idx_session_datetime` (`start_datetime`,`end_datetime`),
  ADD KEY `idx_session_student` (`student_id`),
  ADD KEY `idx_session_tutor` (`tutor_id`);

--
-- 表的索引 `session_requests`
--
ALTER TABLE `session_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `tutor_id` (`tutor_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `location_id` (`location_id`);

--
-- 表的索引 `studentprofile`
--
ALTER TABLE `studentprofile`
  ADD PRIMARY KEY (`user_id`);

--
-- 表的索引 `tutorprofile`
--
ALTER TABLE `tutorprofile`
  ADD PRIMARY KEY (`user_id`);

--
-- 表的索引 `tutorsubject`
--
ALTER TABLE `tutorsubject`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tutor_subject_tutor` (`tutor_id`),
  ADD KEY `fk_tutor_subject_course` (`course_id`);

--
-- 表的索引 `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `reset_token_hash` (`reset_token_hash`),
  ADD KEY `idx_user_role` (`role`),
  ADD KEY `idx_user_email` (`email`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `course`
--
ALTER TABLE `course`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- 使用表AUTO_INCREMENT `credential_file`
--
ALTER TABLE `credential_file`
  MODIFY `file_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `location`
--
ALTER TABLE `location`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `message`
--
ALTER TABLE `message`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- 使用表AUTO_INCREMENT `notification`
--
ALTER TABLE `notification`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- 使用表AUTO_INCREMENT `password_reset`
--
ALTER TABLE `password_reset`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- 使用表AUTO_INCREMENT `review`
--
ALTER TABLE `review`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- 使用表AUTO_INCREMENT `session`
--
ALTER TABLE `session`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- 使用表AUTO_INCREMENT `session_requests`
--
ALTER TABLE `session_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- 使用表AUTO_INCREMENT `tutorsubject`
--
ALTER TABLE `tutorsubject`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- 使用表AUTO_INCREMENT `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- 限制导出的表
--

--
-- 限制表 `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- 限制表 `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `fk_review_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_review_session` FOREIGN KEY (`session_id`) REFERENCES `session` (`session_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_student` FOREIGN KEY (`student_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_tutor` FOREIGN KEY (`tutor_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- 限制表 `session`
--
ALTER TABLE `session`
  ADD CONSTRAINT `fk_session_cancelled_by` FOREIGN KEY (`cancelled_by`) REFERENCES `user` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_session_course` FOREIGN KEY (`course_id`) REFERENCES `course` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_session_location` FOREIGN KEY (`location_id`) REFERENCES `location` (`location_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_session_student` FOREIGN KEY (`student_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_session_tutor` FOREIGN KEY (`tutor_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- 限制表 `session_requests`
--
ALTER TABLE `session_requests`
  ADD CONSTRAINT `session_requests_ibfk_1` FOREIGN KEY (`tutor_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `session_requests_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `session_requests_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `course` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `session_requests_ibfk_4` FOREIGN KEY (`location_id`) REFERENCES `location` (`location_id`) ON DELETE CASCADE;

--
-- 限制表 `studentprofile`
--
ALTER TABLE `studentprofile`
  ADD CONSTRAINT `fk_student_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- 限制表 `tutorprofile`
--
ALTER TABLE `tutorprofile`
  ADD CONSTRAINT `fk_tutor_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- 限制表 `tutorsubject`
--
ALTER TABLE `tutorsubject`
  ADD CONSTRAINT `fk_tutor_subject_course` FOREIGN KEY (`course_id`) REFERENCES `course` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tutor_subject_tutor` FOREIGN KEY (`tutor_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

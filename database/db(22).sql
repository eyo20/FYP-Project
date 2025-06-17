-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 17, 2025 at 07:24 PM
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
-- Database: `peer_tutoring_platform`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
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
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `password`, `email`, `first_name`, `last_name`, `created_at`) VALUES
(1, 'admin', 'admin123', 'admin@example.com', 'System', 'Admin', '2025-04-22 16:30:11');

-- --------------------------------------------------------

--
-- Table structure for table `course`
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
-- Dumping data for table `course`
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
(9, 'Database Systems', NULL, 'active', '2025-06-02 15:51:07', '2025-06-02 15:51:07');

-- --------------------------------------------------------

--
-- Table structure for table `credential_file`
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
-- Dumping data for table `credential_file`
--

INSERT INTO `credential_file` (`file_id`, `file_name`, `file_path`, `file_type`, `upload_date`, `is_verified`, `verified_by`, `verification_date`, `status`, `rejection_reason`, `user_id`) VALUES
(3, 'OIP.jpeg', 'Uploads/credentials/684d6998a9b8d_OIP.jpeg', 'cgpa', '2025-06-14 12:22:48', 0, NULL, NULL, 'pending', NULL, 16);

-- --------------------------------------------------------

--
-- Table structure for table `location`
--

CREATE TABLE `location` (
  `location_id` int(11) NOT NULL,
  `location_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `location`
--

INSERT INTO `location` (`location_id`, `location_name`) VALUES
(1, 'Library'),
(2, 'Learning Point Study Room'),
(3, 'Campus Cafe');

-- --------------------------------------------------------

--
-- Table structure for table `message`
--

CREATE TABLE `message` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `sent_datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `message`
--

INSERT INTO `message` (`message_id`, `sender_id`, `receiver_id`, `content`, `sent_datetime`, `is_read`) VALUES
(1, 16, 25, 'Hi', '2025-06-12 03:21:17', 1),
(2, 25, 16, 'Hi', '2025-06-12 03:21:44', 1),
(6, 17, 16, 'Hi', '2025-06-15 22:25:13', 1),
(7, 23, 17, 'Hi', '2025-06-15 23:18:49', 1),
(8, 16, 17, 'Hi', '2025-06-15 23:19:48', 1),
(9, 17, 25, 'Hi', '2025-06-16 02:47:05', 1),
(10, 17, 25, 'Good Morning', '2025-06-16 03:12:09', 0),
(11, 17, 23, 'Your Calculus & Algebra course in the 10:00-12:00 slot on Jun 16, 2025 has been cancelled. You can rebook another session or discuss alternative timings with your tutor.', '2025-06-16 07:45:25', 1),
(12, 17, 23, 'Your Calculus & Algebra course in the 08:00-10:00 slot on Jun 17, 2025 has been cancelled. You can rebook another session or discuss alternative timings with your tutor.', '2025-06-17 22:17:49', 1);

-- --------------------------------------------------------

--
-- Table structure for table `notification`
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
-- Dumping data for table `notification`
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
(19, 23, 'Request Accepted', 'Your tutoring request has been accepted. Please discuss timing with your tutor.', 'session', 28, 0, '2025-06-17 16:09:20');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset`
--

CREATE TABLE `password_reset` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset`
--

INSERT INTO `password_reset` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(2, '1231201533@student.mmu.edu.my', '591b5f4130b18364571057be6fe82e501e50dae67213ba2638db52b587855d39', '2025-05-01 12:22:01', '2025-05-01 10:07:01');

-- --------------------------------------------------------

--
-- Table structure for table `review`
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
-- Dumping data for table `review`
--

INSERT INTO `review` (`review_id`, `session_id`, `student_id`, `tutor_id`, `rating`, `comment`, `is_approved`, `approved_by`, `created_at`) VALUES
(1, 10, 23, 16, 5, 'good', 1, NULL, '2025-06-15 23:52:52');

-- --------------------------------------------------------

--
-- Table structure for table `session`
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
-- Dumping data for table `session`
--

INSERT INTO `session` (`session_id`, `tutor_id`, `student_id`, `course_id`, `location_id`, `status`, `start_datetime`, `end_datetime`, `cancellation_reason`, `cancelled_by`, `created_at`, `email_sent`) VALUES
(8, 16, 23, 3, 3, 'confirmed', '2025-06-30 08:00:00', '2025-06-30 10:00:00', NULL, NULL, '2025-06-15 21:12:49', 0),
(9, 16, 23, 2, 1, 'confirmed', '2025-06-16 08:00:00', '2025-06-16 10:00:00', NULL, NULL, '2025-06-15 23:14:03', 0),
(10, 16, 23, 2, 2, 'completed', '2025-06-16 08:00:00', '2025-06-16 10:00:00', NULL, NULL, '2025-06-15 23:18:59', 0),
(11, 16, 23, 1, 1, 'confirmed', '2025-06-30 10:00:00', '2025-06-30 12:00:00', NULL, NULL, '2025-06-15 23:20:25', 0),
(12, 16, 23, 2, 1, 'confirmed', '2025-06-16 10:00:00', '2025-06-16 12:00:00', NULL, NULL, '2025-06-15 23:45:25', 0),
(13, 16, 23, 2, 1, 'confirmed', '2025-06-17 08:00:00', '2025-06-17 10:00:00', NULL, NULL, '2025-06-17 14:17:49', 0),
(14, 16, 23, 1, 2, 'confirmed', '2025-06-25 08:00:00', '2025-06-25 10:00:00', NULL, NULL, '2025-06-17 16:09:20', 0);

-- --------------------------------------------------------

--
-- Table structure for table `session_requests`
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
-- Dumping data for table `session_requests`
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
(29, 16, 23, 1, 2, '10:00-12:00', '2025-06-18', '', 'pending', '2025-06-17 23:37:21');

-- --------------------------------------------------------

--
-- Table structure for table `studentprofile`
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
-- Dumping data for table `studentprofile`
--

INSERT INTO `studentprofile` (`user_id`, `major`, `year`, `school`, `program`, `status`, `actions`, `created_at`, `updated_at`, `student_name`) VALUES
(23, '', '', '', 'IT', 'approved', NULL, NULL, NULL, NULL),
(25, 'Computer Science', 'Master', '', 'IT', 'approved', NULL, '2025-05-25 16:26:59', '2025-05-25 16:27:41', 'Mengwen\r\n');

-- --------------------------------------------------------

--
-- Table structure for table `tutorprofile`
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
-- Dumping data for table `tutorprofile`
--

INSERT INTO `tutorprofile` (`user_id`, `major`, `year`, `bio`, `qualifications`, `is_verified`, `rating`, `total_sessions`, `program`, `status`) VALUES
(16, 'Computer Science', 'Diploma', 'hello', '1', 0, 5.00, 1, 'IT', 'approved'),
(19, '1', 'Master', '1', '1', 0, 0.00, 0, 'IT', 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `tutorsubject`
--

CREATE TABLE `tutorsubject` (
  `id` int(11) NOT NULL,
  `tutor_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tutorsubject`
--

INSERT INTO `tutorsubject` (`id`, `tutor_id`, `course_id`, `hourly_rate`) VALUES
(4, 16, 2, 30.00),
(5, 16, 1, 20.00),
(6, 16, 6, 30.00),
(7, 16, 3, 50.00);

-- --------------------------------------------------------

--
-- Table structure for table `user`
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
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `username`, `password`, `email`, `role`, `first_name`, `last_name`, `phone`, `profile_image`, `is_active`, `created_at`, `last_login`, `reset_token_hash`, `reset_token_expires_at`) VALUES
(12, 'david', '$2y$10$UOLIV9n8GVuRyn4fShLouu76Tg5Tb0dHnYwxJwuHZCoU3Y189TBgu', 'davidchong1121@gmail.com', 'student', 'davidd', 'chongg', '0127770231', 'uploads/profile_images/6814809581a48_xukun.png', 1, '2025-04-22 22:11:05', NULL, NULL, NULL),
(15, 'enyong', '$2y$10$5rNBEN5/5eDAzLx4IoExNOfk365fgb.TTiBhChzjA/hDZm.4zBLG6', 'enyong123@gmail.com', 'student', 'enyong', 'ong', NULL, NULL, 1, '2025-04-22 22:21:31', NULL, NULL, NULL),
(16, 'mingwen', '$2y$10$9BtLg9AS1waXzlUC7YArjeiRhxLax9jZFSa5pLbcv5oKgLAzlXb/i', 'mingwen123@student.mmu.edu.my', 'tutor', 'mingwengg', 'koh', '0123456789', 'Uploads/profile_images/16_1750019694_Multimedia-University-MMU-Cyberjaya-Malaysia.jpg', 1, '2025-04-23 17:15:45', NULL, NULL, NULL),
(17, 'admin', '$2y$10$ZD1r0AWEtPwMnxPDwN2kvOLrSMtdxtVf3wqOPBuY6UIqY1Toj57OW', 'admin123@gmail.com', 'admin', '', '', NULL, NULL, 1, '2025-04-24 06:46:09', NULL, NULL, NULL),
(18, 'new', '$2y$10$GHqTNyYHQk5mZ.010TO6QO9QTcxSoW8XIYoB8Ayq2urX1OExGzUC.', 'davidchong11@gmail.com', 'student', 'david', 'chong', 'abcd', NULL, 1, '2025-04-24 08:35:12', NULL, NULL, NULL),
(19, 'jieixnbeauty', '$2y$10$r7iJmcKR/MKf7Uq8eZIVtumFi0VnpTfnpxIX/krA22686RQuBpoYC', 'jiexin123@gmail.com', 'tutor', 'jiexin', 'chong', '1', 'uploads/profile_images/680bc925a94d7_screenshot-1717507504216.png', 1, '2025-04-25 17:32:35', NULL, NULL, NULL),
(20, 'dchong', '$2y$10$h22VvIUY5xnhHVyWDb.dTuUZ5Eaqp2U0horWVgxwzQv5MW8n26Vfy', '1231201533@student.mmu.edu.my', 'student', '1', '1', NULL, NULL, 1, '2025-04-29 15:11:19', NULL, NULL, NULL),
(21, 'ongenyong', '$2y$10$2Dw4zGT.H4YjaUxr.mTV3uJGhpfNYUDTwxUSPFIanESJZgXR0dn2O', '1231203070@student.mmu.edu.my', 'tutor', 'en', 'yong', NULL, NULL, 1, '2025-04-29 16:18:42', NULL, 'b499ca1f127b0afddeed380f011fe2477c1825aa64dd2bf7507c50f88e138ff4', '2025-06-16 11:16:29'),
(23, 'ongenyong1', '$2y$10$Zeb9SrE7iE2ctUC5ezjJsOb7hfzoxatE47JpC3SHZ21wxkoBV2l/C', 'jiunendarren@gmail.com', 'student', 'dovid', 'dog', '01110377800', 'Uploads/profile_images/684f32e29ca95_Zombatar_1.jpg', 1, '2025-05-01 10:30:35', NULL, NULL, NULL),
(25, 'Mengwen', '$2y$10$TnSrDEQEOoBrRer9Zb.aneR0pV8Dm9kgenCXLcRgIFkqfhwaorqHK', '1231200968@student.mmu.edu.my', 'student', 'Koh', 'Meng Wen', NULL, NULL, 1, '2025-06-09 21:42:26', NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `credential_file`
--
ALTER TABLE `credential_file`
  ADD PRIMARY KEY (`file_id`);

--
-- Indexes for table `location`
--
ALTER TABLE `location`
  ADD PRIMARY KEY (`location_id`);

--
-- Indexes for table `message`
--
ALTER TABLE `message`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `is_read` (`is_read`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_notification_user_read` (`user_id`,`is_read`);

--
-- Indexes for table `password_reset`
--
ALTER TABLE `password_reset`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `uq_review_session_student` (`session_id`,`student_id`),
  ADD KEY `fk_review_student` (`student_id`),
  ADD KEY `fk_review_tutor` (`tutor_id`),
  ADD KEY `fk_review_approved_by` (`approved_by`);

--
-- Indexes for table `session`
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
-- Indexes for table `session_requests`
--
ALTER TABLE `session_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `tutor_id` (`tutor_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `studentprofile`
--
ALTER TABLE `studentprofile`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `tutorprofile`
--
ALTER TABLE `tutorprofile`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `tutorsubject`
--
ALTER TABLE `tutorsubject`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tutor_subject_tutor` (`tutor_id`),
  ADD KEY `fk_tutor_subject_course` (`course_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `reset_token_hash` (`reset_token_hash`),
  ADD KEY `idx_user_role` (`role`),
  ADD KEY `idx_user_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `course`
--
ALTER TABLE `course`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `credential_file`
--
ALTER TABLE `credential_file`
  MODIFY `file_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `location`
--
ALTER TABLE `location`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `message`
--
ALTER TABLE `message`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `password_reset`
--
ALTER TABLE `password_reset`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `review`
--
ALTER TABLE `review`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `session`
--
ALTER TABLE `session`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `session_requests`
--
ALTER TABLE `session_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `tutorsubject`
--
ALTER TABLE `tutorsubject`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `message`
--
ALTER TABLE `message`
  ADD CONSTRAINT `message_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `fk_review_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_review_session` FOREIGN KEY (`session_id`) REFERENCES `session` (`session_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_student` FOREIGN KEY (`student_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_tutor` FOREIGN KEY (`tutor_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `session`
--
ALTER TABLE `session`
  ADD CONSTRAINT `fk_session_cancelled_by` FOREIGN KEY (`cancelled_by`) REFERENCES `user` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_session_course` FOREIGN KEY (`course_id`) REFERENCES `course` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_session_location` FOREIGN KEY (`location_id`) REFERENCES `location` (`location_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_session_student` FOREIGN KEY (`student_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_session_tutor` FOREIGN KEY (`tutor_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `session_requests`
--
ALTER TABLE `session_requests`
  ADD CONSTRAINT `session_requests_ibfk_1` FOREIGN KEY (`tutor_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `session_requests_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `session_requests_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `course` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `session_requests_ibfk_4` FOREIGN KEY (`location_id`) REFERENCES `location` (`location_id`) ON DELETE CASCADE;

--
-- Constraints for table `studentprofile`
--
ALTER TABLE `studentprofile`
  ADD CONSTRAINT `fk_student_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `tutorprofile`
--
ALTER TABLE `tutorprofile`
  ADD CONSTRAINT `fk_tutor_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `tutorsubject`
--
ALTER TABLE `tutorsubject`
  ADD CONSTRAINT `fk_tutor_subject_course` FOREIGN KEY (`course_id`) REFERENCES `course` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tutor_subject_tutor` FOREIGN KEY (`tutor_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 14, 2025 at 08:18 PM
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
-- Table structure for table `availability`
--

CREATE TABLE `availability` (
  `availability_id` int(11) NOT NULL,
  `tutor_id` int(11) NOT NULL,
  `day_of_week` tinyint(4) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `status` enum('open','booked','cancelled') NOT NULL DEFAULT 'open',
  `is_booked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `course_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`course_id`, `subject_id`, `course_code`, `course_name`, `description`) VALUES
(1, 1, 'BMM2010', 'Marketing Management ', 'Focus on product promotion, market analysis, consumer behavior and brand strategy'),
(2, 1, 'BBA3859', 'Business Administration', 'Learn all aspects of business operations, including management, marketing, finance, human resources, and more'),
(3, 2, 'LCL5978', 'Constitutional Law', 'Study the country\'s basic legal system and the protection of citizens\' rights'),
(4, 2, 'LCL4869', 'Corporate Law', 'Study the legal framework for corporate organization, operations and governance'),
(5, 3, 'ECE3957', 'Civil Engineering', 'Study the design and construction of infrastructure such as buildings, bridges, roads, etc.'),
(6, 3, 'TME348', 'Mechanical Engineering', 'Study the design, manufacture and maintenance of mechanical systems'),
(7, 4, 'TSE', 'Software Engineering', 'Focus on the design, development and maintenance of software systems'),
(8, 4, 'TCS3457', 'Computer Science', 'Study the theoretical foundations and practical applications of computer systems');

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
  `sent_datetime` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(2, '1231201533@student.mmu.edu.my', '591b5f4130b18364571057be6fe82e501e50dae67213ba2638db52b587855d39', '2025-05-01 12:22:01', '2025-05-01 10:07:01'),
(3, '1231203070@student.mmu.edu.my', '408f031b0964fb579eedba64d444d5be2ed8d4d5bd63c6f7a419847ac816db8b', '2025-05-14 19:02:23', '2025-05-14 16:47:23');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `payment_method` varchar(50) NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_datetime` datetime DEFAULT NULL,
  `refund_amount` decimal(10,2) DEFAULT 0.00,
  `refund_reason` text DEFAULT NULL,
  `refund_datetime` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `session`
--

CREATE TABLE `session` (
  `session_id` int(11) NOT NULL,
  `tutor_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `availability_id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `cancellation_deadline` datetime GENERATED ALWAYS AS (`start_datetime` - interval 24 hour) STORED,
  `cancellation_reason` text DEFAULT NULL,
  `cancelled_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `studentprofile`
--

CREATE TABLE `studentprofile` (
  `user_id` int(11) NOT NULL,
  `major` varchar(100) DEFAULT NULL,
  `year` varchar(50) DEFAULT NULL,
  `school` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `studentprofile`
--

INSERT INTO `studentprofile` (`user_id`, `major`, `year`, `school`) VALUES
(23, 'Computer Science', 'Foundation', ''),
(24, 'CS', 'Degree', '');

-- --------------------------------------------------------

--
-- Table structure for table `student_cart`
--

CREATE TABLE `student_cart` (
  `cartID` int(11) NOT NULL,
  `studentID` int(11) NOT NULL,
  `tutorID` int(11) NOT NULL,
  `hours` int(11) DEFAULT 1,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subject`
--

CREATE TABLE `subject` (
  `subject_id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject`
--

INSERT INTO `subject` (`subject_id`, `subject_name`) VALUES
(1, 'Business'),
(3, 'Engineering'),
(4, 'Information Technology'),
(2, 'Law');

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
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 50.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tutorprofile`
--

INSERT INTO `tutorprofile` (`user_id`, `major`, `year`, `bio`, `qualifications`, `is_verified`, `rating`, `total_sessions`, `hourly_rate`) VALUES
(16, 'Computer Science', 'Master', '1', '1', 0, 0.00, 0, 50.00),
(19, '1', 'Master', '1', '1', 0, 0.00, 0, 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `tutorsubject`
--

CREATE TABLE `tutorsubject` (
  `tutor_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tutorsubject`
--

INSERT INTO `tutorsubject` (`tutor_id`, `subject_id`, `hourly_rate`) VALUES
(16, 3, 20.00);

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
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `username`, `password`, `email`, `role`, `first_name`, `last_name`, `phone`, `profile_image`, `is_active`, `created_at`, `last_login`) VALUES
(12, 'david', '$2y$10$UOLIV9n8GVuRyn4fShLouu76Tg5Tb0dHnYwxJwuHZCoU3Y189TBgu', 'davidchong1121@gmail.com', 'student', 'david', 'chong', '0127770231', 'uploads/profile_images/681216950ba2a_OIP.jpg', 1, '2025-04-22 22:11:05', NULL),
(15, 'enyong', '$2y$10$5rNBEN5/5eDAzLx4IoExNOfk365fgb.TTiBhChzjA/hDZm.4zBLG6', 'enyong123@gmail.com', 'student', 'enyong', 'ong', NULL, NULL, 1, '2025-04-22 22:21:31', NULL),
(16, 'mingwen', '$2y$10$TaBn1BcCd0pi0gqAus1BKeyLQeBTWzrbb2BVrUMV0p3WmD/3FuRFq', 'mingwen123@gmail.com', 'tutor', 'mingweng', 'koh', 'abcd', 'uploads/profile_images/6809e5c4739bb_OIP.jpg 1.jpg', 1, '2025-04-23 17:15:45', NULL),
(17, 'admin', '$2y$10$ZD1r0AWEtPwMnxPDwN2kvOLrSMtdxtVf3wqOPBuY6UIqY1Toj57OW', 'admin123@gmail.com', 'admin', '', '', NULL, NULL, 1, '2025-04-24 06:46:09', NULL),
(18, 'new', '$2y$10$GHqTNyYHQk5mZ.010TO6QO9QTcxSoW8XIYoB8Ayq2urX1OExGzUC.', 'davidchong11@gmail.com', 'student', 'david', 'chong', 'abcd', NULL, 1, '2025-04-24 08:35:12', NULL),
(19, 'jieixnbeauty', '$2y$10$r7iJmcKR/MKf7Uq8eZIVtumFi0VnpTfnpxIX/krA22686RQuBpoYC', 'jiexin123@gmail.com', 'tutor', 'jiexin', 'chong', '1', 'uploads/profile_images/680bc925a94d7_screenshot-1717507504216.png', 1, '2025-04-25 17:32:35', NULL),
(20, 'dchong', '$2y$10$h22VvIUY5xnhHVyWDb.dTuUZ5Eaqp2U0horWVgxwzQv5MW8n26Vfy', '1231201533@student.mmu.edu.my', 'student', '1', '1', NULL, NULL, 1, '2025-04-29 15:11:19', NULL),
(23, 'ongenyong1', '$2y$10$vFxjsI6GsmhU2F5DwQ4mJuSsAmeTfmwtD3v1k3Ast7OjTq5y2OrY6', 'fufufefe123@student.mmu.edu.my', 'student', 'en', 'yong', '03253426456', NULL, 1, '2025-05-01 10:30:35', NULL),
(24, 'dogvid', '$2y$10$BB6sQ8GJ933HqCROYS47Hu2eiR40YaroX6r7L3Xxm9yok8o/cgQ9S', '1231203070@student.mmu.edu.my', 'student', 'u', 'm', '01110978191', NULL, 1, '2025-05-01 12:50:52', NULL);

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
-- Indexes for table `availability`
--
ALTER TABLE `availability`
  ADD PRIMARY KEY (`availability_id`),
  ADD KEY `idx_availability_tutor_status` (`tutor_id`,`status`),
  ADD KEY `idx_availability_datetime` (`start_datetime`,`end_datetime`),
  ADD KEY `idx_availability_day_time` (`day_of_week`,`start_time`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `uq_course_code` (`course_code`),
  ADD KEY `fk_course_subject` (`subject_id`),
  ADD KEY `idx_course_code` (`course_code`),
  ADD KEY `idx_course_name` (`course_name`);

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
  ADD KEY `idx_message_sender_receiver` (`sender_id`,`receiver_id`),
  ADD KEY `idx_message_read_status` (`receiver_id`,`is_read`);

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
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `fk_payment_session` (`session_id`),
  ADD KEY `idx_payment_status` (`status`);

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
  ADD KEY `fk_session_availability` (`availability_id`),
  ADD KEY `fk_session_location` (`location_id`),
  ADD KEY `fk_session_cancelled_by` (`cancelled_by`),
  ADD KEY `idx_session_status` (`status`),
  ADD KEY `idx_session_datetime` (`start_datetime`,`end_datetime`),
  ADD KEY `idx_session_student` (`student_id`),
  ADD KEY `idx_session_tutor` (`tutor_id`);

--
-- Indexes for table `studentprofile`
--
ALTER TABLE `studentprofile`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `student_cart`
--
ALTER TABLE `student_cart`
  ADD PRIMARY KEY (`cartID`);

--
-- Indexes for table `subject`
--
ALTER TABLE `subject`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `uq_subject_name` (`subject_name`),
  ADD KEY `idx_subject_name` (`subject_name`);

--
-- Indexes for table `tutorprofile`
--
ALTER TABLE `tutorprofile`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `tutorsubject`
--
ALTER TABLE `tutorsubject`
  ADD PRIMARY KEY (`tutor_id`,`subject_id`),
  ADD KEY `fk_tutor_subject_subject` (`subject_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
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
-- AUTO_INCREMENT for table `availability`
--
ALTER TABLE `availability`
  MODIFY `availability_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course`
--
ALTER TABLE `course`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `location`
--
ALTER TABLE `location`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `message`
--
ALTER TABLE `message`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset`
--
ALTER TABLE `password_reset`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `review`
--
ALTER TABLE `review`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `session`
--
ALTER TABLE `session`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_cart`
--
ALTER TABLE `student_cart`
  MODIFY `cartID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subject`
--
ALTER TABLE `subject`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `availability`
--
ALTER TABLE `availability`
  ADD CONSTRAINT `fk_availability_tutor` FOREIGN KEY (`tutor_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `course`
--
ALTER TABLE `course`
  ADD CONSTRAINT `fk_course_subject` FOREIGN KEY (`subject_id`) REFERENCES `subject` (`subject_id`) ON DELETE CASCADE;

--
-- Constraints for table `message`
--
ALTER TABLE `message`
  ADD CONSTRAINT `fk_message_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_message_sender` FOREIGN KEY (`sender_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `fk_payment_session` FOREIGN KEY (`session_id`) REFERENCES `session` (`session_id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_session_availability` FOREIGN KEY (`availability_id`) REFERENCES `availability` (`availability_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_session_cancelled_by` FOREIGN KEY (`cancelled_by`) REFERENCES `user` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_session_course` FOREIGN KEY (`course_id`) REFERENCES `course` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_session_location` FOREIGN KEY (`location_id`) REFERENCES `location` (`location_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_session_student` FOREIGN KEY (`student_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_session_tutor` FOREIGN KEY (`tutor_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2025-06-03 13:36:54
-- 服务器版本： 10.4.32-MariaDB
-- PHP 版本： 8.0.30

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
(9, 'Database Systems', NULL, 'active', '2025-06-02 15:51:07', '2025-06-02 15:51:07');

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
(0, '1231203070_TER_LAB5.pdf', 'Uploads/credentials/683ecb2abab79_1231203070_TER_LAB5.pdf', 'application/pdf', '2025-06-03 10:15:06', 0, NULL, NULL, 'pending', NULL, 16),
(1, '', 'uploads/credentials/682dac69a78cd_OIP.jpg', '', '2025-05-20 18:40:42', 0, NULL, NULL, 'pending', NULL, 16);

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
  `receiver_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `sent_datetime` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(2, '1231201533@student.mmu.edu.my', '591b5f4130b18364571057be6fe82e501e50dae67213ba2638db52b587855d39', '2025-05-01 12:22:01', '2025-05-01 10:07:01');

-- --------------------------------------------------------

--
-- 表的结构 `payment`
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

-- --------------------------------------------------------

--
-- 表的结构 `review_table`
--

CREATE TABLE `review_table` (
  `review_id` int(11) NOT NULL,
  `user_name` varchar(200) NOT NULL,
  `user_rating` int(1) NOT NULL,
  `user_review` text NOT NULL,
  `datetime` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- 转存表中的数据 `review_table`
--

INSERT INTO `review_table` (`review_id`, `user_name`, `user_rating`, `user_review`, `datetime`) VALUES
(6, 'John Smith', 4, 'Nice Product, Value for money', 1621935691),
(7, 'Peter Parker', 5, 'Nice Product with Good Feature.', 1621939888),
(8, 'Donna Hubber', 1, 'Worst Product, lost my money.', 1621940010);

-- --------------------------------------------------------

--
-- 表的结构 `session`
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
-- 表的结构 `session_requests`
--

CREATE TABLE `session_requests` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `tutor_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `preferred_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','accepted','rejected','time_suggested') NOT NULL DEFAULT 'pending',
  `suggested_time` datetime DEFAULT NULL,
  `suggested_end_time` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `studentprofile`
--

CREATE TABLE `studentprofile` (
  `user_id` int(11) NOT NULL,
  `major` varchar(100) DEFAULT NULL,
  `year` varchar(50) DEFAULT NULL,
  `school` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `studentprofile`
--

INSERT INTO `studentprofile` (`user_id`, `major`, `year`, `school`) VALUES
(12, 'Computer Science', 'Master', ''),
(23, 'Computer Science', 'Foundation', '');

-- --------------------------------------------------------

--
-- 表的结构 `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `level` enum('Foundation','Diploma','Degree','Master') NOT NULL,
  `program` varchar(50) DEFAULT 'IT',
  `course` enum('Program Design','Calculus & Algebra','Operating Systems','Computer Architecture','Database Systems','Mathematical & Statistical Techniques') NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `actions` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `students`
--

INSERT INTO `students` (`id`, `student_name`, `level`, `program`, `course`, `status`, `actions`, `created_at`, `updated_at`) VALUES
(1, 'Koh Meng Wen', 'Degree', 'IT', 'Calculus & Algebra', 'approved', NULL, '2025-06-02 14:07:51', '2025-06-02 14:09:04'),
(2, 'Ton Xin Yi', 'Diploma', 'IT', 'Program Design', 'pending', NULL, '2025-06-02 14:07:51', '2025-06-02 14:09:04'),
(3, 'Jake Cornell', 'Master', 'IT', 'Computer Architecture', 'approved', NULL, '2025-06-02 14:07:51', '2025-06-02 14:09:04'),
(4, 'Alan Eass', 'Degree', 'IT', 'Program Design', 'approved', NULL, '2025-06-02 14:07:51', '2025-06-02 14:09:04'),
(5, 'Carson Massy', 'Foundation', 'IT', 'Mathematical & Statistical Techniques', 'pending', NULL, '2025-06-02 14:07:51', '2025-06-02 14:09:04'),
(6, 'David Chong', 'Degree', 'IT', 'Database Systems', 'rejected', NULL, '2025-06-02 14:07:51', '2025-06-02 14:09:04'),
(7, 'Venus Thash', 'Diploma', 'IT', 'Calculus & Algebra', 'approved', NULL, '2025-06-02 14:07:51', '2025-06-02 14:09:04'),
(9, 'John Lee', 'Degree', 'IT', 'Program Design', 'approved', NULL, '2025-06-02 14:07:51', '2025-06-02 14:09:04'),
(10, 'Kim Sae Jung', 'Diploma', 'IT', 'Computer Architecture', 'approved', NULL, '2025-06-02 14:07:51', '2025-06-02 14:09:04');

-- --------------------------------------------------------

--
-- 表的结构 `student_cart`
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
  `total_sessions` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `tutorprofile`
--

INSERT INTO `tutorprofile` (`user_id`, `major`, `year`, `bio`, `qualifications`, `is_verified`, `rating`, `total_sessions`) VALUES
(16, 'Computer Science', 'Diploma first year', 'hello im dog', '1', 0, 0.00, 0),
(19, '1', 'Master', '1', '1', 0, 0.00, 0);

-- --------------------------------------------------------

--
-- 表的结构 `tutors`
--

CREATE TABLE `tutors` (
  `id` int(11) NOT NULL,
  `tutor_name` varchar(100) NOT NULL,
  `level` enum('Foundation','Diploma','Degree') NOT NULL,
  `program` enum('IT','Business','Law') NOT NULL,
  `course_year` enum('Year 1','Year 2','Year 3') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `details` text DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `cgpa` decimal(3,2) DEFAULT NULL,
  `transcript` blob DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `tutors`
--

INSERT INTO `tutors` (`id`, `tutor_name`, `level`, `program`, `course_year`, `created_at`, `updated_at`, `details`, `email`, `status`, `cgpa`, `transcript`, `phone`) VALUES
(1, 'Koh Meng Wen', 'Diploma', 'IT', 'Year 2', '2025-05-25 08:26:59', '2025-06-02 15:25:08', '', 'mingwen123@gmail.com\r\n', 'approved', 3.42, 0x255044462d312e360d0a31302030206f626a0d0a3c3c2f547970652f584f626a6563742f537562747970652f466f726d2f466f726d5479706520312f42426f785b302030203539352e33203834312e395d202f4c656e67746820313238202f46696c746572202f466c6174654465636f6465203e3e0d0a73747265616d0d0a0a195842cb64feac62c97f632c954911ee671108437ace27feb6d6e83282874d69c0f67d6698c8dd18405e9e8c373cc3bac8ae149d52de0cbdfa4766a6f30390727c86989c54471d4f969bc8689419e5bb1c983c82cf6bbe75b3a1d2cef67f2ec922d70414a8cde39b0ba88281e3cc368136f72c4b5217787c534bfcd757f3a20d0a656e6473747265616d0d0a656e646f626a0d0a372030206f626a0d0a3c3c202f4c656e6774682032373034202f46696c746572202f466c6174654465636f6465203e3e0d0a73747265616d0d0a5b88e0a8408127d42a8d1e463e9099b76475899c2018dcdbe753111fe1462897ab3e4a1fc66050553a3a8b674d7931dc77963b76a2a65f8bc3d07d36f6cc1f6bf7a9aa8129cc9f0271220e68192162639fe61561db44fad73f7c8c32abca8a2710c5b161f118c0ce7739ff441293396169415bb526dc231f68f9ed0b4892de92b4a6cdfc9f3fa183f277ff63c3309df816abdc6b22e216b3e28a682717653306134277979701393e9fc0faab8eabd206458ffdbbeee16306907ee6dd3ebdfb734ac47dad8b7dec2f784bb17faee7b869fe6dfe21c79ca6ebd175d7c4edc0f74e8f5e99feea950f569a526e6bf65673010ad9e21ce9173b319e35d8a90bfafb80c6af91f761de623f83e01aefcb4db971c75390c38d0fdcfa3fae6ea8191096b8088e838db5ebf99ec9b3e34a1c6b6c7bbd037e625f47799d09dd6c2ae0a0202011358d807dfc74c285dd5f612740b7025b4e0a5fd09dcb42c4da6c1b08ffee6083ded0e9cd80db0dc901c57077cf7f077ea912ec168445a6ad4e50d2a9bd958803a4e67db0ebe6f67cfcb4bb13b10d8ecb9177f0edad8dd134d643df945f431dc23f6ed9080cc8b96ce95ed812a0cb3904b115bf260b94f2f117f733f050de31042fb6d3cb029183475eb81dd26702c3d56f777fccdd3f2158a517f24c56048e2be99c95a135656cb3741e3420edf307c8bb954fd093ecc0bce4216357d7a37f712a1a2f730c2edbbf94c9908f5395bcafecf3ab1c941e56627c01352414910addf4f86bd7cd51eecd9a6ff46869a0e97dd47cabc630aaed064592480597833bd93a889f5128780d745829165a6b2a9f94e14d5573bb97dcd6812bcc222132447bae1f34a3fd21854ddeffb103f1653268105f81ed5e4b6dd600518da37b5616e8557e5b3dcd3ce92f31a1f1648cc7db8eee1cce1479239e44e956a517527777b345850464ec9cb6d5d1c2ddea33b71537c111d22e28b20ff2d688b80eeb73ee422ad6bcef98a68cee1f0d296571335195bb5e21110fb1f9b6e771e8e55f6501d8b784233516ef549f758754649b1d794fd05ec552656df5a09b9a9e31c6f934f29bb5748f16f90b676146041b7bfd2ac85ac3bd20346b0ba67b2a542a1ae6e9994d5d8840101304c2f3466d3829649e78db97e1beae8f6b181276fa080df18f286737baf42d49cd35b35f7a8889e0237b41c81ff3d979c4e831faa91c693162ba7f23dbbba61d273830016e90e174cbf87d9dfe69721f181345598cccc8765e06b7d036074ef96bb9cf9159cba7cc3b04f0a2d1706ce172445e17392d41e849aee909bf47891d5c66a9474e9cc39c498cd8874bfecb5c977e1c07fa9b5c173434e12565fa1e61de11aa9d99b0e9c3e782b72879a396f0cd30d0e585476be816dd952cc1b2769aa0ede323752db5d9f5bed441d94d27a6adce53bc86ad7029206a10f2a505ed7fa6fa19614adf0a7c6afa247f6f7260cb93eb637a053907f40298d7d2255a7383e0b5822047741571f2d93732ea9bf024ad756be31678c862a0aef7cb2e7598387f45a1f28ff93e06aa87faca66d67e8492368339b2f37643c9c4a8b6e8f97014a74fbbf86ead6f0b27d65ac5c57d00e4d81b01bc2c0a96208762a94830c16e1ca5df490265f837c357e5401067a04f7300fe8f7904f2729da56f1946973c70fb93f81d3f82dad559925f42edd0c20982112fc6aa78c810f1a0a18e30d71911bbf59f134e2492045da86b9bd35790a4acce3b28aedb28b536c15c30206ba82aac065f742ae559cff9bbac8462854847c243e8fa08996b094336c9b6b58412d9126069e3822b6d722ae1fc1359f58c9c8b9277d2ca995e568d86a306114f1cb5146085cc9cd40c5eb6d247ce505a9ad41b9fde529f8011aa9408d1de0cd2f9d9a8c98552b4304f2f5948ed9c57f9e3d3940787e49f276821e57cda0bed4de2cac7b4f4a5757b0e579b153dc06e3d923c78dda44434eb754087fa3b01e0d654be88120cc2504f16693bb93bf9bda7ec5ca8a4cf6f6555d6250a7d4f15a03e73b1d3d091d8d6d8504f11a1e5ffe0d171fe602682824088d4bf7064c89f4eee1e6dbed7fc9e5b6901c91ea18bc1b0d4c86d1116f7e1f5fe92bf7c90892fe65dc6ed23625be73d06529b3c51d5abbed0e14b7ac5e3fc5a7886c94b0d013e85ad7a4db0bab1d2316fcf0d6b984c51020ff0d2b8b69a1b778f8fde9f182724017d4ebc17342d03e2731a8b1a36c2b79f61216d8fcee43e8a1648338023c2b287314cdb1846bf13fc46ab1514923bef70e0913fbae0aa92f7447d560a2e83b6b9c45fdf7bda3839f0ac8021504ec6da89bbaab7205271dadc9753542f2c4f154a2380d55811c45bd93828a820c4336f77825f09bbd94ef29ede29e7d313368323004e7b0eb68aa587cbec256cfe8d303ddc09ff21abe21049a2f513f9e543c4e879265853e2c7f7e4cca15d19555978a22c6f0798a818dbef8f5638e2df5f23b26104a3180748f43750d9ff1bf12ce8fa41dbf9362f69db366e4436679dca312906d28b29b2f1d16cfaef7a1c10a166cfae185baf8a068ac14bfece18c5d3f2b9ef38d17cd8cf19728245b479578b44dfd516ee83702ba295160cb9e9864442968930d030ae17e2369b6650cf9d01796f7741e5d64e60e0ea30509da492d0af06387ce7648d4f1ecc52172e96f456d9eca605ad3cc2af89e15fe3a4b478598f320869b93541f4eb147de965c9002f9467bf942133ec89de7898efd4a175cef2ecb89bac7d5fb86d62740ee8d3311e57de25e4fb5c04e9b121bcf9f4629dde0677685e6bee519d55a59fb07864e35fabdbcdcd9ae666205db9da682fb43406acc14dab80f20bd0af6ed8ad3c0840dddd7ccba6a78c49726e04012ab97ebafcdc3ab8f292bd22d3448614a342049cf162d3c4695a64bb86d4c82233b08e63b9f49a2f4ba06e81ad183be8e608506d018564669eb8919d3160ab0b87ec4ee1483649a429cbd8fe9ade763fdcbcf31e7c41df72995c16df1864948b2055c2bd97700bf23f0e530eaf8f17e0000be80afd24fc97cbccc66f9140b5b8231e6d806da5b202fbb8b342f4ee757a79995891511b8b221830741bded5152ea7e1b07a4e37d5fa60296b67016823a287ed3692286cde7200cbf26c90ad515ee87901f6994dc5bef6ba3ad8aa02e347998f3e28c285758e9cc05660d884618ccc8b71572eeabba1a53b947b33cac1ff0cea6d72fcdee04db605f22f34838a9b6e9dfafddd6afa5b5acfda5e83eaac8f0cc62679c666635c1a8b5e8306b021e09bf6dc2326d321fd93474a63b8b0794017ef6c7d9bdcdfd69f09aa3082dc4cdca68b24c49fa0f4600eb24761dcde2e66811d927a50c97c142802cb63ed9f7f8c30ce11e4987faee513c74950cd8e27e74606963266e87666c81f744e214a08986747e8dd0f843ea4127f952ba966964231e333e523dbb4a47b75194042611a90c8f8010b219725be355ccfd316da0c56f61b542332a569c16c74185e40f5d59168d0d65eaff136fe40d2dd10f2a058b532843e9accbb3cdd4ebcf4ff9ba4fc95b09ec30b3356ff3045edab66313f552238c29c72656833c4da059f57f1d10e021d6f323655e3ba988c6d6a20ef5ff86f568605e56e7baaa81ee1a66a29735c83b4ffc0a1e00a0755521bd2b42673b5133a596a4cb10ac2a90c2aa2b00ae2d22bff46db71e0f1b7be0c2d0011709186897e1800c8c36af6df6b890b2622915f7c85d8e87985ea2a2b9e8ec9275cbea3a8aa9404d5b7c51ca8af5468868922885eba4833bd72b4286a5c7651d4ce1564bffae2c0a00d0a656e6473747265616d0d0a656e646f626a0d0a312030206f626a0d0a3c3c0d0a2f46696c746572202f5374616e646172640d0a2f4f202836451bd39d753b7c1d10922c5c28e6665aa4f3353fb0348b536893e3b1db5c5c579b290d0a2f55202876322b75493658ff57ea77005192850000000000000000000000000000000000290d0a2f50202d313334300d0a2f5220340d0a2f5620340d0a2f4c656e677468203132380d0a2f43463c3c2f53746443463c3c2f4c656e6774682031362f417574684576656e742f446f634f70656e2f43464d2f41455356323e3e3e3e0d0a2f537472462f53746443460d0a2f53746d462f53746443460d0a3e3e0d0a656e646f626a0d0a322030206f626a0d0a3c3c0d0a2f54797065202f436174616c6f670d0a2f50616765732034203020520d0a3e3e0d0a656e646f626a0d0a332030206f626a0d0a3c3c0d0a2f54797065202f496e666f0d0a2f50726f64756365722028f1387e043064dffe95e9ae18279baac371dec196ed30b61aa2723be3924c8c330702aedb8503a13ecce3ccb65c7441ec68290d0a3e3e0d0a656e646f626a0d0a342030206f626a0d0a3c3c0d0a2f54797065202f50616765730d0a2f4b696473205b0d0a36203020520d0a5d0d0a2f436f756e7420310d0a3e3e0d0a656e646f626a0d0a352030206f626a0d0a3c3c0d0a2f50726f63536574205b202f504446202f54657874205d0d0a2f466f6e74203c3c200d0a2f46312038203020520d0a2f46322039203020520d0a2f4633203133203020520d0a3e3e0d0a2f584f626a656374203c3c200d0a2f667830203130203020520d0a3e3e0d0a3e3e0d0a656e646f626a0d0a362030206f626a0d0a3c3c0d0a2f54797065202f506167650d0a2f506172656e742034203020520d0a2f5265736f75726365732035203020520d0a2f436f6e74656e74732037203020520d0a2f4d65646961426f785b20302030203539352e33203834312e39205d0d0a2f43726f70426f785b20302030203539352e33203834312e39205d0d0a2f526f7461746520300d0a3e3e0d0a656e646f626a0d0a382030206f626a0d0a3c3c0d0a2f54797065202f466f6e740d0a2f53756274797065202f54797065310d0a2f42617365466f6e74202f48656c7665746963612d426f6c640d0a2f456e636f64696e67202f57696e416e7369456e636f64696e670d0a3e3e0d0a656e646f626a0d0a392030206f626a0d0a3c3c0d0a2f54797065202f466f6e740d0a2f53756274797065202f54797065310d0a2f42617365466f6e74202f48656c7665746963610d0a2f456e636f64696e67202f57696e416e7369456e636f64696e670d0a3e3e0d0a656e646f626a0d0a31312030206f626a0d0a5b203620302052202f58595a2033302e362034312e333433206e756c6c205d0d0a656e646f626a0d0a31322030206f626a0d0a5b203620302052202f58595a2033302e362034312e333433206e756c6c205d0d0a656e646f626a0d0a31332030206f626a0d0a3c3c0d0a2f54797065202f466f6e740d0a2f53756274797065202f54797065310d0a2f42617365466f6e74202f436f75726965720d0a2f456e636f64696e67202f57696e416e7369456e636f64696e670d0a3e3e0d0a656e646f626a0d0a787265660d0a302031340d0a3030303030303030303020363535333520660d0a30303030303033303539203030303030206e0d0a30303030303033333031203030303030206e0d0a30303030303033333536203030303030206e0d0a30303030303033343537203030303030206e0d0a30303030303033353235203030303030206e0d0a30303030303033363535203030303030206e0d0a30303030303030323736203030303030206e0d0a30303030303033383133203030303030206e0d0a30303030303033393233203030303030206e0d0a30303030303030303130203030303030206e0d0a30303030303034303238203030303030206e0d0a30303030303034303739203030303030206e0d0a30303030303034313330203030303030206e0d0a747261696c65720d0a3c3c0d0a2f53697a652031340d0a2f526f6f742032203020520d0a2f496e666f2033203020520d0a2f456e63727970742031203020520d0a2f4944205b3c613961383861393133333435306230313339306664343664353835346633343536386664376534306563623138626266333362323866336664303766653138383e3c613961383861393133333435306230313339306664343664353835346633343536386664376534306563623138626266333362323866336664303766653138383e5d0d0a3e3e0d0a7374617274787265660d0a343233340d0a2525454f460d0a, '+601126677887'),
(2, 'Ali Ahmad', 'Foundation', 'IT', 'Year 1', '2025-05-25 08:26:59', '2025-06-02 14:51:13', '', NULL, 'approved', NULL, NULL, NULL),
(6, 'Rock Lee', 'Degree', 'IT', 'Year 3', '2025-06-02 14:59:08', '2025-06-02 17:20:41', NULL, NULL, 'approved', NULL, NULL, NULL);

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
(7, 16, 3, 50.00);

-- --------------------------------------------------------

--
-- 表的结构 `tutorsubject_backup`
--

CREATE TABLE `tutorsubject_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `tutor_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `programme_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `user`
--

INSERT INTO `user` (`user_id`, `username`, `password`, `email`, `role`, `first_name`, `last_name`, `phone`, `profile_image`, `is_active`, `created_at`, `last_login`) VALUES
(12, 'david', '$2y$10$UOLIV9n8GVuRyn4fShLouu76Tg5Tb0dHnYwxJwuHZCoU3Y189TBgu', 'davidchong1121@gmail.com', 'student', 'davidd', 'chongg', '0127770231', 'uploads/profile_images/6814809581a48_xukun.png', 1, '2025-04-22 22:11:05', NULL),
(15, 'enyong', '$2y$10$5rNBEN5/5eDAzLx4IoExNOfk365fgb.TTiBhChzjA/hDZm.4zBLG6', 'enyong123@gmail.com', 'student', 'enyong', 'ong', NULL, NULL, 1, '2025-04-22 22:21:31', NULL),
(16, 'mingwen', '$2y$10$9BtLg9AS1waXzlUC7YArjeiRhxLax9jZFSa5pLbcv5oKgLAzlXb/i', 'mingwen123@gmail.com', 'tutor', 'mingweng', 'gan', '0123456789', 'uploads/profile_images/6809e5c4739bb_OIP.jpg 1.jpg', 1, '2025-04-23 17:15:45', NULL),
(17, 'admin', '$2y$10$ZD1r0AWEtPwMnxPDwN2kvOLrSMtdxtVf3wqOPBuY6UIqY1Toj57OW', 'admin123@gmail.com', 'admin', '', '', NULL, NULL, 1, '2025-04-24 06:46:09', NULL),
(18, 'new', '$2y$10$GHqTNyYHQk5mZ.010TO6QO9QTcxSoW8XIYoB8Ayq2urX1OExGzUC.', 'davidchong11@gmail.com', 'student', 'david', 'chong', 'abcd', NULL, 1, '2025-04-24 08:35:12', NULL),
(19, 'jieixnbeauty', '$2y$10$r7iJmcKR/MKf7Uq8eZIVtumFi0VnpTfnpxIX/krA22686RQuBpoYC', 'jiexin123@gmail.com', 'tutor', 'jiexin', 'chong', '1', 'uploads/profile_images/680bc925a94d7_screenshot-1717507504216.png', 1, '2025-04-25 17:32:35', NULL),
(20, 'dchong', '$2y$10$h22VvIUY5xnhHVyWDb.dTuUZ5Eaqp2U0horWVgxwzQv5MW8n26Vfy', '1231201533@student.mmu.edu.my', 'student', '1', '1', NULL, NULL, 1, '2025-04-29 15:11:19', NULL),
(21, 'ongenyong', '$2y$10$2Dw4zGT.H4YjaUxr.mTV3uJGhpfNYUDTwxUSPFIanESJZgXR0dn2O', '1231203070@student.mmu.edu.my', 'tutor', 'en', 'yong', NULL, NULL, 1, '2025-04-29 16:18:42', NULL),
(23, 'ongenyong1', '$2y$10$Zeb9SrE7iE2ctUC5ezjJsOb7hfzoxatE47JpC3SHZ21wxkoBV2l/C', 'fufufefe123@student.mmu.edu.my', 'student', 'dogvid', 'c', '0172823489', NULL, 1, '2025-05-01 10:30:35', NULL),
(24, 'Mengwen', '$2y$10$ornms94.VuO9CEpg60qj4.8NlHytcnYOugHn3irkLmJM4ImgvVxIy', '1231200968@student.mmu.edu.my', 'student', 'Meng Wen', 'Koh', NULL, NULL, 1, '2025-05-25 09:00:45', NULL);

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
  ADD KEY `idx_message_sender_receiver` (`sender_id`,`receiver_id`),
  ADD KEY `idx_message_read_status` (`receiver_id`,`is_read`);

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
-- 表的索引 `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `fk_payment_session` (`session_id`),
  ADD KEY `idx_payment_status` (`status`);

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
-- 表的索引 `studentprofile`
--
ALTER TABLE `studentprofile`
  ADD PRIMARY KEY (`user_id`);

--
-- 表的索引 `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `student_cart`
--
ALTER TABLE `student_cart`
  ADD PRIMARY KEY (`cartID`);

--
-- 表的索引 `tutorprofile`
--
ALTER TABLE `tutorprofile`
  ADD PRIMARY KEY (`user_id`);

--
-- 表的索引 `tutors`
--
ALTER TABLE `tutors`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- 使用表AUTO_INCREMENT `location`
--
ALTER TABLE `location`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `message`
--
ALTER TABLE `message`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `notification`
--
ALTER TABLE `notification`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `password_reset`
--
ALTER TABLE `password_reset`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `review`
--
ALTER TABLE `review`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `session`
--
ALTER TABLE `session`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- 使用表AUTO_INCREMENT `student_cart`
--
ALTER TABLE `student_cart`
  MODIFY `cartID` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `tutors`
--
ALTER TABLE `tutors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- 使用表AUTO_INCREMENT `tutorsubject`
--
ALTER TABLE `tutorsubject`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- 使用表AUTO_INCREMENT `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- 限制导出的表
--

--
-- 限制表 `message`
--
ALTER TABLE `message`
  ADD CONSTRAINT `fk_message_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_message_sender` FOREIGN KEY (`sender_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- 限制表 `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- 限制表 `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `fk_payment_session` FOREIGN KEY (`session_id`) REFERENCES `session` (`session_id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_session_availability` FOREIGN KEY (`availability_id`) REFERENCES `availability` (`availability_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_session_cancelled_by` FOREIGN KEY (`cancelled_by`) REFERENCES `user` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_session_course` FOREIGN KEY (`course_id`) REFERENCES `course2` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_session_location` FOREIGN KEY (`location_id`) REFERENCES `location` (`location_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_session_student` FOREIGN KEY (`student_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_session_tutor` FOREIGN KEY (`tutor_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

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

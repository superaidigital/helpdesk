-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 14, 2025 at 05:28 PM
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
-- Database: `helpdesk_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `icon`, `description`, `is_active`, `created_at`) VALUES
(1, 'ฮาร์ดแวร์', 'fa-desktop', 'คอมพิวเตอร์, ปริ้นเตอร์', 1, '2025-09-12 18:00:00'),
(2, 'ซอฟต์แวร์', 'fa-window-maximize', 'Windows, Office', 1, '2025-09-12 18:00:00'),
(3, 'ระบบเครือข่าย', 'fa-wifi', 'อินเทอร์เน็ต, Wi-Fi, LAN', 1, '2025-09-12 18:00:00'),
(4, 'ออกแบบและพัฒนาระบบ', 'fa-file-invoice', 'ออกแบบและพัฒนาระบบ DEV', 1, '2025-09-12 18:00:00'),
(5, 'อีเมล', 'fa-envelope-open-text', 'การรับ-ส่งอีเมล', 1, '2025-09-12 18:00:00'),
(6, 'อื่นๆ', 'fa-question-circle', 'ปัญหาไม่เข้าหมวดหมู่อื่น', 1, '2025-09-12 18:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `issue_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment_text` text NOT NULL,
  `attachment_link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `issue_id`, `user_id`, `comment_text`, `attachment_link`, `created_at`) VALUES
(1, 1, 100, 'รับเรื่องแล้ว กำลังดำเนินการตรวจสอบ', NULL, '2025-09-14 08:48:38'),
(2, 1, 100, 'กก', '', '2025-09-14 09:34:08'),
(3, 1, 100, 'กก', '', '2025-09-14 09:34:20'),
(4, 2, 100, 'รับเรื่องแล้ว กำลังดำเนินการตรวจสอบ', NULL, '2025-09-14 10:18:55'),
(5, 2, 100, '555', '', '2025-09-14 10:19:05'),
(6, 3, 100, 'รับเรื่องแล้ว กำลังดำเนินการตรวจสอบ', NULL, '2025-09-14 12:46:54'),
(7, 4, 100, 'รับเรื่องแล้ว กำลังดำเนินการตรวจสอบ', NULL, '2025-09-14 15:13:25');

-- --------------------------------------------------------

--
-- Table structure for table `comment_files`
--

CREATE TABLE `comment_files` (
  `id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `issues`
--

CREATE TABLE `issues` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reporter_name` varchar(255) NOT NULL,
  `reporter_contact` varchar(255) NOT NULL,
  `reporter_position` varchar(255) DEFAULT NULL,
  `reporter_department` varchar(255) DEFAULT NULL,
  `division` varchar(255) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `urgency` enum('ด่วนมาก','ปกติ','สามารถรอได้') NOT NULL,
  `status` enum('pending','in_progress','done','cannot_resolve','awaiting_parts') NOT NULL DEFAULT 'pending',
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `satisfaction_rating` int(1) DEFAULT NULL COMMENT 'คะแนนความพึงพอใจ 1-5',
  `signature_image` varchar(255) DEFAULT NULL COMMENT 'เส้นทางไฟล์รูปภาพลายเซ็น'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `issues`
--

INSERT INTO `issues` (`id`, `user_id`, `reporter_name`, `reporter_contact`, `reporter_position`, `reporter_department`, `division`, `category`, `title`, `description`, `urgency`, `status`, `assigned_to`, `created_at`, `updated_at`, `completed_at`, `satisfaction_rating`, `signature_image`) VALUES
(1, NULL, 'แม็ก', '0981051534', '', '', NULL, 'ฮาร์ดแวร์', 'เครืองปริ้น', 'พัง', 'สามารถรอได้', 'done', 100, '2025-09-14 08:48:21', '2025-09-14 15:26:55', '2025-09-14 09:58:13', 5, 'uploads/signatures/sig_1_68c6debfba712.png'),
(2, NULL, 'แม็ก', '0981051534', '', '', NULL, 'ซอฟต์แวร์', 'เครืองปริ้น', 'ววว', 'สามารถรอได้', 'done', 100, '2025-09-14 10:18:13', '2025-09-14 15:27:06', '2025-09-14 12:34:54', 5, 'uploads/signatures/sig_2_68c6decaac4c6.png'),
(3, NULL, 'แม็ก', '0981051534', 'ผปแแปผ', 'แปผแผ', 'กหกฟฟห', 'ระบบเครือข่าย', 'เน็ต', 'เน็ต', 'ด่วนมาก', 'awaiting_parts', 100, '2025-09-14 12:44:31', '2025-09-14 15:03:59', NULL, NULL, NULL),
(4, NULL, 'แม็ก', '0981051534', '', '', NULL, 'ระบบสารบรรณ/ERP', 'ปป', 'ปป', 'ปกติ', 'cannot_resolve', 100, '2025-09-14 15:13:02', '2025-09-14 15:27:41', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `issue_checklist`
--

CREATE TABLE `issue_checklist` (
  `id` int(11) NOT NULL,
  `issue_id` int(11) NOT NULL,
  `item_description` varchar(255) NOT NULL,
  `is_checked` tinyint(1) NOT NULL DEFAULT 0,
  `item_value` text DEFAULT NULL,
  `checked_by` int(11) DEFAULT NULL,
  `checked_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `issue_files`
--

CREATE TABLE `issue_files` (
  `id` int(11) NOT NULL,
  `issue_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `issue_files`
--

INSERT INTO `issue_files` (`id`, `issue_id`, `file_name`, `file_path`, `uploaded_at`) VALUES
(1, 4, 'Helpdesk_Report_ปฐวีกานต์_ศรีคราม_from_2025-09-01_to_2025-09-30 (1).csv', 'uploads/issue_4_68c6db7e72623.csv', '2025-09-14 15:13:02'),
(2, 4, 'Helpdesk_Report_ปฐวีกานต์_ศรีคราม_from_2025-09-01_to_2025-09-30.csv', 'uploads/issue_4_68c6db7e7346a.csv', '2025-09-14 15:13:02');

-- --------------------------------------------------------

--
-- Table structure for table `knowledge_base`
--

CREATE TABLE `knowledge_base` (
  `id` int(11) NOT NULL,
  `issue_id_source` int(11) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `position` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `division` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `line_id` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `image_url` varchar(255) DEFAULT 'assets/images/user.png',
  `role` enum('user','it','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `position`, `department`, `division`, `phone`, `line_id`, `email`, `password`, `image_url`, `role`, `created_at`) VALUES
(100, 'ปฐวีกานต์ ศรีคราม', 'นักวิชาการคอมพิวเตอร์ปฏิบัติงาน', 'กองยุทธศาสตร์และงบประมาณ', 'ประชาสัมพันธ์', '0981051534', 'maxmumi37', 'itmax@sisaket.go.th', '$2y$10$DnoSEFkiEApZXAl0LcbQTOedEusKePVykzaQbm.Wg0.YddkHmSNYK', 'uploads/avatars/avatar_68c6de9a46f5c.png', 'admin', '2025-09-14 08:43:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `issue_id` (`issue_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `comment_files`
--
ALTER TABLE `comment_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `comment_id` (`comment_id`);

--
-- Indexes for table `issues`
--
ALTER TABLE `issues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `issue_checklist`
--
ALTER TABLE `issue_checklist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `issue_id` (`issue_id`);

--
-- Indexes for table `issue_files`
--
ALTER TABLE `issue_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `issue_id` (`issue_id`);

--
-- Indexes for table `knowledge_base`
--
ALTER TABLE `knowledge_base`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `comment_files`
--
ALTER TABLE `comment_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `issues`
--
ALTER TABLE `issues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `issue_checklist`
--
ALTER TABLE `issue_checklist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `issue_files`
--
ALTER TABLE `issue_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `knowledge_base`
--
ALTER TABLE `knowledge_base`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

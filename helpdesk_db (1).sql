-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 22, 2025 at 03:46 AM
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
-- Table structure for table `articles`
--

CREATE TABLE `articles` (
  `id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `excerpt` varchar(500) DEFAULT NULL,
  `featured_image_url` varchar(255) DEFAULT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `tags` varchar(255) DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(9, 5, 100, 'รับเรื่องแล้ว กำลังดำเนินการตรวจสอบ', NULL, '2025-09-15 02:24:08'),
(10, 5, 100, 'ดำเนินการเสร็จสิ้น', '', '2025-09-15 02:26:03'),
(11, 6, 100, 'รับเรื่องแล้ว กำลังดำเนินการตรวจสอบ', NULL, '2025-09-15 03:09:49'),
(12, 6, 100, 'รีสตาร์ทคอมพิวเตอร์และเราเตอร์ เพื่อรีเซ็ตการตั้งค่าเครือข่าย', '', '2025-09-15 03:10:47'),
(13, 7, 100, 'รับเรื่องแล้ว กำลังดำเนินการตรวจสอบ', NULL, '2025-09-15 16:46:11'),
(14, 7, 100, 'ดำเนิดการตั้งค่า ip เชื่อมต่อเครื่องพิมพ์ เรียบร้อย', '', '2025-09-15 16:47:26'),
(15, 8, 100, 'รับเรื่องแล้ว กำลังดำเนินการตรวจสอบ', NULL, '2025-09-16 02:24:01'),
(16, 8, 100, 'ให้คำปรึกษาและสอนวิธีการใช้งานเร็ยบร้อย', '', '2025-09-16 02:25:05'),
(17, 9, 100, 'รับเรื่องแล้ว กำลังดำเนินการตรวจสอบ', NULL, '2025-09-16 02:55:56'),
(18, 9, 100, 'ออกแบบแผ่นพับเสร็จแล้วเรียบร้อย', 'https://www.canva.com/design/DAGw58Qkjy0/QLkAnEMOK60_CSh6I5JoAg/view?utm_content=DAGw58Qkjy0&utm_campaign=designshare&utm_medium=link2&utm_source=uniquelinks&utlId=h7e87e4fb9f', '2025-09-16 03:00:10'),
(19, 10, 102, 'รับเรื่องแล้ว กำลังดำเนินการตรวจสอบ', NULL, '2025-09-17 08:58:24'),
(20, 10, 102, 'ดาวน์โหลดฟอนต์ Th Sarabunit9 และทำการติดตั้ง', '', '2025-09-17 09:00:30'),
(21, 11, 102, 'รับเรื่องแล้ว กำลังดำเนินการตรวจสอบ', NULL, '2025-09-18 06:51:27'),
(22, 11, 102, 'ดำเนินการติดตั้งเครื่องสแกนเนอร์พร้อมไดร์เวอร์สำหรับการใช้งาน', '', '2025-09-18 06:52:20'),
(23, 12, 102, 'รับเรื่องแล้ว กำลังดำเนินการตรวจสอบ', NULL, '2025-09-18 06:59:11'),
(24, 12, 102, 'ดาวโหลดและติดตั้งไดรเวอร์เครื่องสแกนเนอร์ พร้อมติดตั้งเครื่องสแกนเนอร์', '', '2025-09-18 07:01:02'),
(25, 13, 102, 'รับเรื่องแล้ว กำลังดำเนินการตรวจสอบ', NULL, '2025-09-18 07:05:46'),
(26, 13, 102, 'ดาวน์โหลดและติดตั้งไดรเวอร์สำหรับเครื่องสแกนเนอร์', '', '2025-09-18 07:06:59');

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

--
-- Dumping data for table `comment_files`
--

INSERT INTO `comment_files` (`id`, `comment_id`, `file_name`, `file_path`) VALUES
(2, 18, 'หลวงพ่อโต วัดมหาพุทธาราม (วัดพระโต.zip - 1.png', 'uploads/comments/comment_18_68c8d2baa486d.png'),
(3, 18, 'หลวงพ่อโต วัดมหาพุทธาราม (วัดพระโต.zip - 2.png', 'uploads/comments/comment_18_68c8d2baaea2d.png');

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
(5, NULL, 'น้องตั้งโอ๊', '0981051534', 'ผู้ช่วยธุรการ', 'กองยุทธศาสตร์และงบประมาณ', NULL, 'ซอฟต์แวร์', 'Windows 11 ไม่เปิดใช้งาน', 'ได้รับรหัสข้อผิดพลาดที่ไม่ได้แสดงอยู่ในหน้ารายการข้อผิดพลาด ควรติดต่อฝ่ายสนับสนุนลูกค้าของ Microsoft.', 'สามารถรอได้', 'done', 100, '2025-09-15 02:20:55', '2025-09-15 02:35:29', '2025-09-15 02:26:03', 5, 'uploads/signatures/sig_5_68c77b71aede6.png'),
(6, NULL, 'นางธัญชนก ธนปุญญ', '0653241939', 'ผู้อำนวยกองยุทธศาสตร์และงบประมาณ', 'กองยุทธศาสตร์และงบประมาณ', NULL, 'ระบบเครือข่าย', 'เชื่อมต่อ Wi-Fi ไม่ได้', 'ชื่อมต่อ Wi-Fi บน Windows 11 ไม่ได้', 'ด่วนมาก', 'done', 100, '2025-09-15 02:55:17', '2025-09-15 03:11:12', '2025-09-15 03:10:47', 5, 'uploads/signatures/sig_6_68c783d0178e0.png'),
(7, NULL, 'ตาร์', '0458146676', '', '', NULL, 'ฮาร์ดแวร์', 'แชร์ปรินเตอร์', 'แชร์ปรินเตอร์จาก ให้ปริ้นจากเครื่องน้องแป้ง ไปเครื่องพี่หนุ่ย', 'ปกติ', 'done', 100, '2025-09-15 08:53:04', '2025-09-16 02:34:27', '2025-09-15 16:47:26', 5, 'uploads/signatures/sig_7_68c8ccb3d9b8b.png'),
(8, NULL, 'พี่หมู', '0652192565', '', '', NULL, 'ซอฟต์แวร์', 'ให้คำปรึกษาด้านการแทรกเลขหน้า Microsoft Word', 'แทรกเลขหน้า', 'สามารถรอได้', 'done', 100, '2025-09-16 02:23:41', '2025-09-16 02:25:55', '2025-09-16 02:25:05', 5, 'uploads/signatures/sig_8_68c8cab30c915.png'),
(9, NULL, 'นางอารยา สุบินดี', '0981051534', 'เจ้าพนักงานส่งเสริมการท่องเที่ยวชำนาญการ', 'สำนักปลัดฯ', NULL, 'อื่นๆ', 'ออกแบบแผ่นพับประชาสัมพันธ์ด้านการท่องเที่ยวจังหวัดศรีสะเกษ', 'สถานที่ท่องเที่ยวจังหวัดศรีสะเกษ และข้อมูลที่พัก ร้านอาหาร', 'สามารถรอได้', 'done', 100, '2025-09-16 02:54:11', '2025-09-16 03:00:10', '2025-09-16 03:00:10', NULL, NULL),
(10, NULL, 'พี่โตโต้', '0991755809', '', '', NULL, 'ซอฟต์แวร์', 'ไม่มีฟอนต์ Th sarabun it9', 'ไม่สามารถใช้งานฟอนต์ดังกล่าวได้', 'ปกติ', 'done', 102, '2025-09-17 08:57:35', '2025-09-17 09:03:15', '2025-09-17 09:03:15', NULL, NULL),
(11, NULL, 'นางสาวจิตรราภา  พิญญาณ', '0644496952', '', '', NULL, 'ฮาร์ดแวร์', 'ติดตั้งเครื่องสแกนเนอร์ พร้อมติดตั้งไดร์ฟเวอร์', 'ติดตั้งเครื่องสแกนเนอร์ เพื่อสแกนเอกสารราคากลาง', 'ด่วนมาก', 'done', 102, '2025-09-18 06:49:25', '2025-09-18 06:53:34', '2025-09-18 06:53:34', NULL, NULL),
(12, NULL, 'นางสาวจิตราภา  พิณญาณ', '0644496952', 'เจ้าพนักงานธุรการปฎิบัติงาน', 'สำนักช่าง', 'ออกแบบ', 'ฮาร์ดแวร์', 'ติดตั้งเครื่องสแกนเนอร์', 'ช่วยติดตั้งเครื่องสแกนเนอร์ เพื่อสแกนเอกสาร', 'ด่วนมาก', 'done', 102, '2025-09-18 06:58:41', '2025-09-18 07:01:02', '2025-09-18 07:01:02', NULL, NULL),
(13, NULL, 'นางสาวจิตราภา  พิญญาณ', '0644496952', '', '', NULL, 'ฮาร์ดแวร์', 'ติดตั้งเครื่องสแกนเนอร์', 'ช่วยติดตั้งเครื่องสแกนเนอร์ เพื่อสแกนเอกสาร', 'ด่วนมาก', 'done', 102, '2025-09-18 07:05:25', '2025-09-18 07:07:33', '2025-09-18 07:07:33', NULL, NULL);

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

--
-- Dumping data for table `issue_checklist`
--

INSERT INTO `issue_checklist` (`id`, `issue_id`, `item_description`, `is_checked`, `item_value`, `checked_by`, `checked_at`) VALUES
(1, 7, 'ตรวจสอบการเชื่อมต่อสายไฟ/สายสัญญาณ', 1, '', 100, '2025-09-15 16:47:22'),
(2, 7, 'ตรวจสอบไดรเวอร์อุปกรณ์', 1, '', 100, '2025-09-15 16:47:22'),
(3, 7, 'ทดสอบการทำงานของพอร์ตเชื่อมต่อ', 1, '', 100, '2025-09-15 16:47:22'),
(4, 7, 'ทำความสะอาดอุปกรณ์เบื้องต้น', 0, '', 100, '2025-09-15 16:47:22'),
(5, 7, 'ทดสอบกับอุปกรณ์อื่น', 1, '', 100, '2025-09-15 16:47:22'),
(6, 7, 'อื่นๆ', 0, '', 100, '2025-09-15 16:47:22'),
(7, 10, 'ตรวจสอบ Log File ของโปรแกรม', 0, '', 102, '2025-09-17 08:59:19'),
(8, 10, 'ลอง Re-install โปรแกรม', 0, '', 102, '2025-09-17 08:59:19'),
(9, 10, 'อัปเดตโปรแกรมเป็นเวอร์ชันล่าสุด', 0, '', 102, '2025-09-17 08:59:19'),
(10, 10, 'สแกนไวรัส/มัลแวร์', 0, '', 102, '2025-09-17 08:59:19'),
(11, 10, 'ตรวจสอบความเข้ากันได้ของระบบ', 0, '', 102, '2025-09-17 08:59:19'),
(12, 10, 'อื่นๆ', 1, 'ดาวน์โหลดและติดตั้งฟอนต์ Th sarabunit9', 102, '2025-09-17 08:59:19'),
(13, 11, 'ตรวจสอบการเชื่อมต่อสายไฟ/สายสัญญาณ', 0, '', 102, '2025-09-18 06:53:33'),
(14, 11, 'ตรวจสอบไดรเวอร์อุปกรณ์', 0, '', 102, '2025-09-18 06:53:33'),
(15, 11, 'ทดสอบการทำงานของพอร์ตเชื่อมต่อ', 0, '', 102, '2025-09-18 06:53:33'),
(16, 11, 'ทำความสะอาดอุปกรณ์เบื้องต้น', 0, '', 102, '2025-09-18 06:53:33'),
(17, 11, 'ทดสอบกับอุปกรณ์อื่น', 0, '', 102, '2025-09-18 06:53:33'),
(18, 11, 'อื่นๆ', 1, 'ดาวน์โหลดและติดตั้งไดรเวอร์ พร้อมเครื่องสแกนเนอร์', 102, '2025-09-18 06:53:33'),
(19, 12, 'ตรวจสอบการเชื่อมต่อสายไฟ/สายสัญญาณ', 0, '', 102, '2025-09-18 07:00:59'),
(20, 12, 'ตรวจสอบไดรเวอร์อุปกรณ์', 0, '', 102, '2025-09-18 07:00:59'),
(21, 12, 'ทดสอบการทำงานของพอร์ตเชื่อมต่อ', 0, '', 102, '2025-09-18 07:00:59'),
(22, 12, 'ทำความสะอาดอุปกรณ์เบื้องต้น', 0, '', 102, '2025-09-18 07:00:59'),
(23, 12, 'ทดสอบกับอุปกรณ์อื่น', 0, '', 102, '2025-09-18 07:00:59'),
(24, 12, 'อื่นๆ', 1, 'ดาวน์โหลดและติดตั้งไดรเวอร์ และติดตั้งเครื่องสแกนเนอร์', 102, '2025-09-18 07:00:59'),
(25, 13, 'ตรวจสอบการเชื่อมต่อสายไฟ/สายสัญญาณ', 0, '', 102, '2025-09-18 07:07:31'),
(26, 13, 'ตรวจสอบไดรเวอร์อุปกรณ์', 0, '', 102, '2025-09-18 07:07:31'),
(27, 13, 'ทดสอบการทำงานของพอร์ตเชื่อมต่อ', 0, '', 102, '2025-09-18 07:07:31'),
(28, 13, 'ทำความสะอาดอุปกรณ์เบื้องต้น', 0, '', 102, '2025-09-18 07:07:31'),
(29, 13, 'ทดสอบกับอุปกรณ์อื่น', 0, '', 102, '2025-09-18 07:07:31'),
(30, 13, 'อื่นๆ', 1, 'ดาวน์โหลดและติดตั้งไดรเวอร์ พร้อมติดตั้งเครื่องสแกนเนอร์', 102, '2025-09-18 07:07:31');

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

-- --------------------------------------------------------

--
-- Table structure for table `it_portfolio`
--

CREATE TABLE `it_portfolio` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `project_title` varchar(255) NOT NULL,
  `project_description` text NOT NULL,
  `project_category` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `technologies_used` varchar(255) DEFAULT NULL COMMENT 'เก็บเป็น comma-separated string เช่น PHP, CSS, Photoshop',
  `project_url` varchar(255) DEFAULT NULL,
  `main_image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

--
-- Dumping data for table `knowledge_base`
--

INSERT INTO `knowledge_base` (`id`, `issue_id_source`, `category`, `question`, `answer`, `created_by`, `created_at`) VALUES
(2, 5, 'ซอฟต์แวร์', 'Windows 11 ไม่เปิดใช้งาน', 'ไปที่ การตั้งค่า > ระบบ > การเปิดใช้งาน เพื่อตรวจสอบสถานะ และลอง ใช้ตัวแก้ไขปัญหาการเปิดใช้งาน หรือ ป้อนรหัสผลิตภัณฑ์\r\nหากปัญหายังคงอยู่ ให้ตรวจสอบว่ารุ่นของ Windows 11 ตรงกับใบอนุญาตหรือไม่ หรือหากมีการเปลี่ยนฮาร์ดแวร์สำคัญอาจต้อง เชื่อมโยงบัญชี Microsoft กับใบอนุญาตดิจิทัล แล้วใช้ตัวแก้ไขปัญหาอีกครั้ง.', 100, '2025-09-15 02:25:18'),
(3, 6, 'ระบบเครือข่าย', 'เชื่อมต่อ Wi-Fi ไม่ได้', 'ลองทำตามขั้นตอนเหล่านี้: ตรวจสอบว่า Wi-Fi เปิดอยู่และอยู่ใกล้เราเตอร์หรือไม่, รีสตาร์ทคอมพิวเตอร์และเราเตอร์, ใช้ตัวแก้ไขปัญหาเครือข่ายอัตโนมัติในแอปการตั้งค่า, ลืมเครือข่าย Wi-Fi เดิมแล้วเชื่อมต่อใหม่, อัปเดตไดรเวอร์การ์ด Wi-Fi, หรือใช้คำสั่งใน Command Prompt เพื่อรีเซ็ตการตั้งค่าเครือข่าย', 100, '2025-09-15 03:10:23'),
(4, 10, 'ซอฟต์แวร์', 'ไม่มีฟอนต์ Th sarabun it9', 'ดาวน์โหลดและติดตั้งไฟล์ Th Sarabunit9/Th SarabunPsk เวอร์ชั่น 1.0 เพื่อป้องกันปัญหาตัวอักษรเพี้ยนในโปรแกรม Microsoft Excel', 102, '2025-09-17 09:03:06');

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
(100, 'ปฐวีกานต์ ศรีคราม', 'นักวิชาการคอมพิวเตอร์ปฏิบัติงาน', 'กองยุทธศาสตร์และงบประมาณ', 'ประชาสัมพันธ์', '0981051534', 'maxmumi37', 'itmax@sisaket.go.th', '$2y$10$DnoSEFkiEApZXAl0LcbQTOedEusKePVykzaQbm.Wg0.YddkHmSNYK', 'uploads/avatars/avatar_68c6de9a46f5c.png', 'it', '2025-09-14 08:43:56'),
(102, 'นายสิโรดม  พรมชา', 'นักวิชาการคอมพิวเตอร์ปฏิบัติการ', 'สำนักช่าง', 'บริหารงานทั่วไป', '0991755809', 'me.rachar', 'itmen@sisaket.go.th', '$2y$10$./Pcs9SR4BT294b1bA6Vx.aPwg7WngSH0lT.pZ/33lPXzzXuAa.IW', 'assets/images/user.png', 'it', '2025-09-17 04:49:23'),
(103, 'ชิราวุธ ศรีคราม', 'นักวิชาการคอมพิวแตด', '', 'บริหารทั่วทีป', '045814683', 'pkkit', 'klunksskpao@gmail.com', '$2y$10$/L1gqo0OmQ52JPSpgsA3V.AytaA3fgZ.pN4veEdh1Fm5.hNrBoldi', 'uploads/avatars/avatar_68cb64e50d1f0.png', 'it', '2025-09-17 06:37:21');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `author_id` (`author_id`);

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
-- Indexes for table `it_portfolio`
--
ALTER TABLE `it_portfolio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

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
-- AUTO_INCREMENT for table `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `comment_files`
--
ALTER TABLE `comment_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `issues`
--
ALTER TABLE `issues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `issue_checklist`
--
ALTER TABLE `issue_checklist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `issue_files`
--
ALTER TABLE `issue_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `it_portfolio`
--
ALTER TABLE `it_portfolio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `knowledge_base`
--
ALTER TABLE `knowledge_base`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `it_portfolio`
--
ALTER TABLE `it_portfolio`
  ADD CONSTRAINT `it_portfolio_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

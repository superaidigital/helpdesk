<?php
// includes/functions.php

// --- Use PHPMailer for Email Notifications ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Include config for database and other settings
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}
require_once 'db.php';


//======================================================================
// NEW PORTFOLIO FUNCTIONS
//======================================================================

/**
 * ดึงข้อมูลผลงานทั้งหมดของเจ้าหน้าที่จาก user_id
 * @param int $user_id
 * @param mysqli $conn
 * @return array
 */
function getPortfolioByUserId($user_id, $conn) {
    $portfolio = [];
    $stmt = $conn->prepare("SELECT * FROM it_portfolio WHERE user_id = ? ORDER BY end_date DESC, start_date DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $portfolio[] = $row;
    }
    $stmt->close();
    return $portfolio;
}

/**
 * [NEW] ดึงข้อมูลผลงานทั้งหมดของเจ้าหน้าที่ทุกคน (สำหรับ Admin)
 * @param mysqli $conn
 * @return array
 */
function getAllPortfolios($conn) {
    $portfolios = [];
    $sql = "SELECT p.*, u.fullname as author_name, u.image_url as author_avatar 
            FROM it_portfolio p
            JOIN users u ON p.user_id = u.id 
            ORDER BY p.end_date DESC, p.start_date DESC";
    $result = $conn->query($sql);
    while($row = $result->fetch_assoc()) {
        $portfolios[] = $row;
    }
    return $portfolios;
}


//======================================================================
// ARTICLE & NEWS FUNCTIONS
//======================================================================

/**
 * Creates a URL-friendly slug from a string.
 * @param string $text
 * @return string
 */
function createSlug($text) {
    // Replace non-letter or digits with -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    // Remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Trim
    $text = trim($text, '-');
    // Remove duplicate -
    $text = preg_replace('~-+~', '-', $text);
    // Lowercase
    $text = strtolower($text);
    if (empty($text)) {
        return 'n-a-' . uniqid();
    }
    return $text;
}

/**
 * ดึงข้อมูลบทความที่เผยแพร่แล้วสำหรับหน้าสาธารณะ (มี Pagination และ Search)
 * @param mysqli $conn
 * @param int $page
 * @param int $perPage
 * @param string $searchTerm
 * @return array
 */
function getPublishedArticles($conn, $page = 1, $perPage = 6, $searchTerm = '') {
    $offset = ($page - 1) * $perPage;
    $articles = [];
    
    $count_sql = "SELECT COUNT(a.id) as total FROM articles a WHERE a.status = 'published'";
    $data_sql = "SELECT a.*, u.fullname as author_name, u.image_url as author_avatar 
                 FROM articles a 
                 JOIN users u ON a.author_id = u.id 
                 WHERE a.status = 'published'";
    
    $params = [];
    $types = "";

    if (!empty($searchTerm)) {
        $search_query = "%" . $searchTerm . "%";
        $condition = " AND (a.title LIKE ? OR a.tags LIKE ?)";
        $count_sql .= $condition;
        $data_sql .= $condition;
        $params = [$search_query, $search_query];
        $types = "ss";
    }

    // Get total count
    $stmt_count = $conn->prepare($count_sql);
    if (!empty($params)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $total_items = $stmt_count->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_items / $perPage);
    $stmt_count->close();

    // Get paginated data
    $data_sql .= " ORDER BY a.published_at DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    $types .= "ii";

    $stmt_data = $conn->prepare($data_sql);
    $stmt_data->bind_param($types, ...$params);
    $stmt_data->execute();
    $result = $stmt_data->get_result();
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
    $stmt_data->close();
    
    return ['articles' => $articles, 'total_pages' => $total_pages];
}


/**
 * ดึงข้อมูลบทความเดียวจาก slug
 * @param mysqli $conn
 * @param string $slug
 * @return array|null
 */
function getArticleBySlug($conn, $slug) {
    $sql = "SELECT a.*, u.fullname as author_name, u.image_url as author_avatar 
            FROM articles a 
            JOIN users u ON a.author_id = u.id 
            WHERE a.slug = ? AND a.status = 'published'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    $article = $result->fetch_assoc();
    $stmt->close();
    return $article;
}

/**
 * ดึงข้อมูลบทความทั้งหมดสำหรับหน้าจัดการ (Admin)
 * @param mysqli $conn
 * @return array
 */
function getAllArticles($conn) {
    $articles = [];
    $sql = "SELECT a.*, u.fullname as author_name 
            FROM articles a 
            JOIN users u ON a.author_id = u.id 
            ORDER BY a.created_at DESC";
    $result = $conn->query($sql);
    while($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
    return $articles;
}


//======================================================================
// UTILITY & FORMATTING FUNCTIONS
//======================================================================

/**
 * จัดรูปแบบวันที่และเวลาเป็นภาษาไทยเต็มรูปแบบ (วัน เดือน พ.ศ., เวลา)
 * @param string|null $dateString The date string from the database (e.g., 'YYYY-MM-DD HH:MM:SS')
 * @return string Formatted Thai date string (e.g., '13 กันยายน 2568, 15:03 น.') or '-' if invalid.
 */
function formatDate($dateString) {
    if (is_null($dateString) || $dateString === '') return '-';
    
    $thai_months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม',
        4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
        7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน',
        10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    
    try {
        $date = new DateTime($dateString);
        $day = $date->format('j');
        $month = $thai_months[(int)$date->format('n')];
        $year = (int)$date->format('Y') + 543;
        $time = $date->format('H:i');
        
        return "$day $month $year, $time น.";
    } catch (Exception $e) {
        return '-';
    }
}

/**
 * จัดรูปแบบระยะเวลาจากนาทีเป็นสตริงที่อ่านง่าย (วัน ชั่วโมง นาที)
 * @param int|null $total_minutes จำนวนนาทีทั้งหมด
 * @return string
 */
function formatDuration($total_minutes) {
    if (is_null($total_minutes) || $total_minutes <= 0) {
        return 'N/A';
    }
    $days = floor($total_minutes / 1440);
    $rem_minutes = $total_minutes % 1440;
    $hours = floor($rem_minutes / 60);
    $minutes = floor($rem_minutes % 60);
    
    $str = '';
    if ($days > 0) $str .= "{$days} วัน ";
    if ($hours > 0) $str .= "{$hours} ชม. ";
    if ($minutes > 0) $str .= "{$minutes} นาที";

    return trim($str) ?: '0 นาที';
}


/**
 * สร้างลิงก์สำหรับระบบแบ่งหน้า (Pagination)
 * @param int $total_pages จำนวนหน้าทั้งหมด
 * @param int $current_page หน้าปัจจุบัน
 * @param string $base_url URL หลักของหน้า
 * @param array $params พารามิเตอร์อื่นๆ ที่ต้องคงไว้ใน URL
 * @return string HTML code for pagination links
 */
function generate_pagination_links($total_pages, $current_page, $base_url, $params = []) {
    $total_pages = (int)$total_pages;
    $current_page = (int)$current_page;

    if ($total_pages <= 1) {
        return '';
    }

    $query_string = http_build_query($params);
    $base_url = $base_url . '?' . $query_string;

    $html = '<nav class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6" aria-label="Pagination">';
    $html .= '<div class="hidden sm:block"><p class="text-sm text-gray-700">หน้า <span class="font-medium">' . $current_page . '</span> จาก <span class="font-medium">' . $total_pages . '</span></p></div>';
    $html .= '<div class="flex-1 flex justify-between sm:justify-end">';
    
    if ($current_page > 1) {
        $html .= '<a href="' . $base_url . '&page=' . ($current_page - 1) . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">ก่อนหน้า</a>';
    } else {
        $html .= '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-50 cursor-not-allowed">ก่อนหน้า</span>';
    }
    
    if ($current_page < $total_pages) {
        $html .= '<a href="' . $base_url . '&page=' . ($current_page + 1) . '" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">ถัดไป</a>';
    } else {
        $html .= '<span class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-50 cursor-not-allowed">ถัดไป</span>';
    }
    
    $html .= '</div></nav>';
    return $html;
}

/**
 * ดึงเส้นทางรูปภาพโปรไฟล์ที่พร้อมใช้งาน
 * @param string|null $image_url เส้นทางรูปภาพจากฐานข้อมูล
 * @return string เส้นทางรูปภาพที่ถูกต้องสำหรับแสดงผล
 */
function get_user_avatar($image_url) {
    $default_avatar = 'assets/images/user.png';
    if (!empty($image_url) && file_exists($image_url)) {
        return $image_url;
    }
    return $default_avatar;
}

//======================================================================
// SECURITY FUNCTIONS
//======================================================================

/**
 * สร้างและแสดง hidden input สำหรับ CSRF token
 * @return string HTML input tag
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

/**
 * ตรวจสอบ CSRF token
 */
function validate_csrf_token() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        unset($_SESSION['csrf_token']);
        redirect_with_message('index.php', 'error', 'Session หมดอายุหรือไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง');
    }
    unset($_SESSION['csrf_token']);
}

//======================================================================
// FLASH MESSAGE & REDIRECT FUNCTIONS
//======================================================================

/**
 * ตั้งค่าข้อความแจ้งเตือน (Flash Message)
 * @param string $type ('success' or 'error')
 * @param string $message
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

/**
 * แสดงผล Flash Message (ถ้ามี)
 */
function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_message']['type'] === 'success' ? 'green' : 'red';
        $title = $_SESSION['flash_message']['type'] === 'success' ? 'สำเร็จ!' : 'เกิดข้อผิดพลาด!';
        echo '<div class="bg-'.$type.'-100 border-l-4 border-'.$type.'-500 text-'.$type.'-700 p-4 mb-4" role="alert">';
        echo '<p class="font-bold">' . $title . '</p>';
        echo '<p>' . htmlspecialchars($_SESSION['flash_message']['message']) . '</p>';
        echo '</div>';
        unset($_SESSION['flash_message']);
    }
}

/**
 * Redirect ไปยังหน้าที่ต้องการ พร้อมกับตั้งค่า Flash Message
 * @param string $location
 * @param string $type
 * @param string $message
 */
function redirect_with_message($location, $type, $message) {
    set_flash_message($type, $message);
    header("Location: " . $location);
    exit();
}

//======================================================================
// AUTHENTICATION & AUTHORIZATION
//======================================================================

/**
 * ตรวจสอบสิทธิ์การเข้าถึงหน้า
 * @param array $required_roles e.g., ['it', 'admin', 'user']
 */
function check_auth($required_roles) {
    if (!is_array($required_roles)) {
        $required_roles = [$required_roles];
    }
    if (!isset($_SESSION['user_id'])) {
        redirect_with_message('index.php', 'error', 'กรุณาเข้าสู่ระบบก่อน');
    }
    if (!in_array($_SESSION['role'], $required_roles)) {
        session_unset();
        session_destroy();
        redirect_with_message('index.php', 'error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
    }
}

//======================================================================
// DATA FETCHING FUNCTIONS
//======================================================================

/**
 * ดึงข้อมูลผู้ใช้ทั้งหมดจาก ID
 * @param int $id
 * @param mysqli $conn
 * @return array|null
 */
function getUserById($id, $conn) {
    if ($id === null) return null;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

/**
 * ดึง "ชื่อเต็ม" ของผู้ใช้จาก ID
 * @param int|null $id
 * @param mysqli $conn
 * @return string
 */
function getUserNameById($id, $conn) {
    $user = getUserById($id, $conn);
    return $user ? htmlspecialchars($user['fullname']) : '-';
}

/**
 * ดึงไฟล์แนบทั้งหมดของเรื่อง
 * @param int $issue_id
 * @param mysqli $conn
 * @return array
 */
function getIssueFiles($issue_id, $conn) {
    $files = [];
    $stmt = $conn->prepare("SELECT file_name, file_path FROM issue_files WHERE issue_id = ?");
    $stmt->bind_param("i", $issue_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $files[] = $row;
    }
    $stmt->close();
    return $files;
}

/**
 * ดึงความคิดเห็นทั้งหมดของเรื่อง
 * @param int $issue_id
 * @param mysqli $conn
 * @return array
 */
function getIssueComments($issue_id, $conn) {
    $comments = [];
    $sql = "SELECT c.id, c.comment_text, c.attachment_link, c.created_at, u.id as user_id, u.fullname, u.image_url FROM comments c JOIN users u ON c.user_id = u.id WHERE c.issue_id = ? ORDER BY c.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $issue_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['files'] = getCommentFiles($row['id'], $conn);
        $comments[] = $row;
    }
    $stmt->close();
    return $comments;
}

/**
 * ดึงไฟล์แนบของความคิดเห็น
 * @param int $comment_id
 * @param mysqli $conn
 * @return array
 */
function getCommentFiles($comment_id, $conn) {
    $files = [];
    $stmt = $conn->prepare("SELECT file_name, file_path FROM comment_files WHERE comment_id = ?");
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $files[] = $row;
    }
    $stmt->close();
    return $files;
}

/**
 * ค้นหา ID ของเจ้าหน้าที่ IT ที่มีงาน 'in_progress' มากที่สุด
 * @param mysqli $conn
 * @return array
 */
function getBusiestITStaffIds($conn) {
    $max_count_query = "SELECT COUNT(id) as max_total FROM issues WHERE status = 'in_progress' AND assigned_to IS NOT NULL GROUP BY assigned_to ORDER BY max_total DESC LIMIT 1";
    $max_result = $conn->query($max_count_query);
    if (!$max_result || $max_result->num_rows === 0) {
        return [];
    }
    $max_count = $max_result->fetch_assoc()['max_total'];
    $busiest_staff_query = "SELECT assigned_to FROM issues WHERE status = 'in_progress' AND assigned_to IS NOT NULL GROUP BY assigned_to HAVING COUNT(id) = ?";
    $stmt = $conn->prepare($busiest_staff_query);
    $stmt->bind_param("i", $max_count);
    $stmt->execute();
    $result = $stmt->get_result();
    $busiest_ids = [];
    while ($row = $result->fetch_assoc()) {
        $busiest_ids[] = (int)$row['assigned_to'];
    }
    $stmt->close();
    return $busiest_ids;
}

/**
 * นับจำนวนผู้ใช้งานใหม่ที่สมัครเข้ามาภายใน 24 ชั่วโมง
 * @param mysqli $conn
 * @return int
 */
function get_new_user_count($conn) {
    $sql = "SELECT COUNT(id) as new_users FROM users WHERE role = 'user' AND created_at >= NOW() - INTERVAL 1 DAY";
    $result = $conn->query($sql);
    return $result ? (int)$result->fetch_assoc()['new_users'] : 0;
}

/**
 * ดึงข้อความ Comment ล่าสุดของเจ้าหน้าที่ที่รับผิดชอบงาน
 * @param int $issue_id
 * @param int $assigned_to_id
 * @param mysqli $conn
 * @return string|null
 */
function getLatestITComment($issue_id, $assigned_to_id, $conn) {
    if (is_null($issue_id) || is_null($assigned_to_id)) {
        return null;
    }
    $stmt = $conn->prepare("SELECT comment_text FROM comments WHERE issue_id = ? AND user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("ii", $issue_id, $assigned_to_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['comment_text'];
    }
    $stmt->close();
    return null;
}

/**
 * ดึงข้อมูล Checklist ทั้งหมดของเรื่อง
 * @param int $issue_id ID ของเรื่อง
 * @param mysqli $conn Connection object
 * @return array ข้อมูล Checklist
 */
function getIssueChecklistItems($issue_id, $conn) {
    $checklist = [];
    $stmt = $conn->prepare("SELECT item_description, is_checked, item_value FROM issue_checklist WHERE issue_id = ?");
    $stmt->bind_param("i", $issue_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $checklist[$row['item_description']] = [
            'checked' => (bool)$row['is_checked'],
            'value' => $row['item_value']
        ];
    }
    $stmt->close();
    return $checklist;
}

/**
 * [NEW] ดึงชุดรายการตรวจสอบ (Checklist) ตามหมวดหมู่ของปัญหา
 * @param string $category ชื่อหมวดหมู่
 * @return array รายการ Checklist
 */
function get_checklist_by_category($category) {
    $checklists = [
        'ฮาร์ดแวร์' => [
            'ตรวจสอบการเชื่อมต่อสายไฟ/สายสัญญาณ', 'ตรวจสอบไดรเวอร์อุปกรณ์', 'ทดสอบการทำงานของพอร์ตเชื่อมต่อ',
            'ทำความสะอาดอุปกรณ์เบื้องต้น', 'ทดสอบกับอุปกรณ์อื่น', 'อื่นๆ'
        ],
        'ซอฟต์แวร์' => [
            'ตรวจสอบ Log File ของโปรแกรม', 'ลอง Re-install โปรแกรม', 'อัปเดตโปรแกรมเป็นเวอร์ชันล่าสุด',
            'สแกนไวรัส/มัลแวร์', 'ตรวจสอบความเข้ากันได้ของระบบ', 'อื่นๆ'
        ],
        'ระบบเครือข่าย' => [
            'ตรวจสอบสถานะไฟบน Router/Switch', 'ทดสอบคำสั่ง Ping ไปยัง Gateway', 'ตรวจสอบการตั้งค่า IP Address',
            'ลองเชื่อมต่อด้วยสาย LAN', 'ล้างค่า DNS Cache', 'อื่นๆ'
        ],
        // หมวดหมู่อื่นๆ ใช้ชุดรายการตรวจสอบเริ่มต้น
        'default' => [
            'ตรวจสอบสายไฟ/สายสัญญาณ', 'ทดสอบการพิมพ์/การสแกน', 'ตรวจสอบการเชื่อมต่อเครือข่าย', 
            'อัปเดตไดรเวอร์/ซอฟต์แวร์', 'สแกนไวรัส/มัลแวร์', 'ทำความสะอาดอุปกรณ์เบื้องต้น', 'อื่นๆ'
        ]
    ];

    return $checklists[$category] ?? $checklists['default'];
}


//======================================================================
// EMAIL FUNCTIONS
//======================================================================

/**
 * ฟังก์ชันสำหรับส่งอีเมลผ่าน PHPMailer
 * @param string $to
 * @param string $subject
 * @param string $body
 * @return bool
 */
function send_email($to, $subject, $body) {
    // Check if SMTP settings are defined in config.php
    if (!defined('SMTP_HOST') || !defined('SMTP_USER') || !defined('SMTP_PASS')) {
        error_log("SMTP settings are not configured in config.php. Email not sent.");
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>


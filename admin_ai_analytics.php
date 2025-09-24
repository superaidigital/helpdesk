<?php
$page_title = "รายงานวิเคราะห์ภาพรวมโดย AI";
require_once 'includes/functions.php';
check_auth(['admin']);

// =================================================================
// ส่วนของการเชื่อมต่อ Gemini API (นำมาจาก ai_issue_helper.php)
// =================================================================
define('GEMINI_API_KEY', 'AIzaSyCEHI88GtEHBHEE2C1vjrOyKKVv-1kl5W4'); 
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . GEMINI_API_KEY);

function callGeminiAPI($prompt) {
    if (GEMINI_API_KEY === '' || strpos(GEMINI_API_KEY, 'YOUR_GEMINI_API_KEY') !== false) {
        return ['success' => false, 'data' => "ข้อผิดพลาด: กรุณาตั้งค่า GEMINI_API_KEY ในไฟล์ " . basename(__FILE__) . " ก่อนใช้งาน"];
    }
    $data = ['contents' => [['parts' => [['text' => $prompt]]]]];
    $ch = curl_init(GEMINI_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response_json = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) return ['success' => false, 'data' => 'เกิดข้อผิดพลาดในการเชื่อมต่อ (cURL): ' . $curl_error];
    $response_data = json_decode($response_json, true);
    if ($http_code !== 200 || isset($response_data['error'])) {
        $error_message = $response_data['error']['message'] ?? 'ไม่สามารถสื่อสารกับ AI ได้';
        return ['success' => false, 'data' => 'API Error: ' . $error_message];
    }
    $text = $response_data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if (is_null($text)) return ['success' => false, 'data' => 'ไม่ได้รับการตอบกลับที่ถูกต้องจาก AI'];
    return ['success' => true, 'data' => $text];
}

require_once 'includes/header.php';

// --- Date Filtering Logic ---
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$base_condition = "DATE(i.created_at) BETWEEN ? AND ?";

// --- Data Fetching for AI Analysis ---
$data_for_prompt = "ข้อมูลสรุปจากระบบ IT Helpdesk ระหว่างวันที่ $start_date ถึง $end_date:\n\n";

// 1. Overall Stats
$stmt = $conn->prepare("SELECT COUNT(id) as total_issues, COUNT(CASE WHEN status = 'done' THEN 1 END) as total_completed, AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_hours, AVG(satisfaction_rating) as avg_rating FROM issues i WHERE $base_condition");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$overall_stats = $stmt->get_result()->fetch_assoc();
$data_for_prompt .= "## ภาพรวม\n";
$data_for_prompt .= "- จำนวนเรื่องทั้งหมด: " . ($overall_stats['total_issues'] ?? 0) . " เรื่อง\n";
$data_for_prompt .= "- แก้ไขเสร็จสิ้น: " . ($overall_stats['total_completed'] ?? 0) . " เรื่อง\n";
$data_for_prompt .= "- เวลาแก้ไขเฉลี่ย: " . round($overall_stats['avg_hours'] ?? 0, 1) . " ชั่วโมง\n";
$data_for_prompt .= "- คะแนนความพึงพอใจเฉลี่ย: " . round($overall_stats['avg_rating'] ?? 0, 2) . "/5\n\n";
$stmt->close();

// 2. Top 5 Problems
$stmt = $conn->prepare("SELECT title, COUNT(id) as total FROM issues i WHERE $base_condition GROUP BY title ORDER BY total DESC, title ASC LIMIT 5");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$top_problems_q = $stmt->get_result();
$data_for_prompt .= "## 5 อันดับปัญหาที่พบบ่อยที่สุด\n";
while($row = $top_problems_q->fetch_assoc()) { $data_for_prompt .= "- " . $row['title'] . ": " . $row['total'] . " ครั้ง\n"; }
$data_for_prompt .= "\n";
$stmt->close();

// 3. Performance by Staff
$stmt = $conn->prepare("SELECT u.fullname, COUNT(i.id) as total, AVG(TIMESTAMPDIFF(MINUTE, i.created_at, i.completed_at)) as avg_min, AVG(i.satisfaction_rating) as avg_rating FROM issues i JOIN users u ON i.assigned_to = u.id WHERE u.role IN ('it', 'admin') AND DATE(i.created_at) BETWEEN ? AND ? GROUP BY u.id ORDER BY total DESC");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$staff_perf_q = $stmt->get_result();
$data_for_prompt .= "## ประสิทธิภาพของเจ้าหน้าที่\n";
while($row = $staff_perf_q->fetch_assoc()) {
    $data_for_prompt .= "- " . $row['fullname'] . ": " . $row['total'] . " เรื่อง, เวลาเฉลี่ย " . formatDuration($row['avg_min']) . ", คะแนน " . round($row['avg_rating'], 2) . "/5\n";
}
$data_for_prompt .= "\n";
$stmt->close();

// --- Call Gemini API ---
$ai_summary = "กำลังรอการวิเคราะห์จาก AI...";
if (isset($_GET['start_date'])) { // Only run AI if form is submitted
    $prompt = "ในฐานะนักวิเคราะห์ข้อมูล (Data Analyst) ขององค์กร โปรดวิเคราะห์ข้อมูลสรุประบบ IT Helpdesk ต่อไปนี้ พร้อมทั้งให้ข้อมูลเชิงลึก (Insight) และข้อเสนอแนะที่สามารถนำไปปฏิบัติได้จริง (Actionable Recommendations) สำหรับผู้ดูแลระบบ IT โดยเน้นที่การปรับปรุงประสิทธิภาพ ลดปัญหาซ้ำซ้อน และเพิ่มความพึงพอใจของผู้ใช้ ตอบเป็นภาษาไทยในรูปแบบ Markdown ที่ชัดเจน\n\n" . $data_for_prompt;
    $ai_result = callGeminiAPI($prompt);
    $ai_summary = $ai_result['success'] ? $ai_result['data'] : '<p class="text-red-500">' . htmlspecialchars($ai_result['data']) . '</p>';
}
?>

<div class="space-y-6">
    <div class="bg-white p-4 rounded-lg shadow-md">
        <form method="GET" action="admin_ai_analytics.php" class="flex flex-col sm:flex-row items-center gap-4">
            <div>
                <label for="start_date" class="text-sm font-medium text-gray-700">วันที่เริ่มต้น:</label>
                <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($start_date); ?>" class="mt-1 block border border-gray-300 rounded-md shadow-sm py-2 px-3">
            </div>
            <div>
                <label for="end_date" class="text-sm font-medium text-gray-700">วันที่สิ้นสุด:</label>
                <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($end_date); ?>" class="mt-1 block border border-gray-300 rounded-md shadow-sm py-2 px-3">
            </div>
            <div>
                <button type="submit" class="w-full sm:w-auto mt-6 px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700">
                    <i class="fa-solid fa-brain mr-2"></i>วิเคราะห์ข้อมูล
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex items-center gap-4 border-b pb-4 mb-4">
            <i class="fa-solid fa-robot text-3xl text-indigo-500"></i>
            <div>
                <h3 class="text-xl font-bold text-gray-800">บทสรุปและข้อเสนอแนะโดย AI</h3>
                <p class="text-sm text-gray-500">สรุปจากข้อมูลระหว่างวันที่ <?php echo htmlspecialchars($start_date); ?> ถึง <?php echo htmlspecialchars($end_date); ?></p>
            </div>
        </div>
        <div class="prose max-w-none text-gray-700">
            <?php echo nl2br(htmlspecialchars($ai_summary)); // Use nl2br for simple line breaks, or a markdown parser for full markdown ?>
        </div>
    </div>
</div>

<?php 
$conn->close();
require_once 'includes/footer.php'; 
?>
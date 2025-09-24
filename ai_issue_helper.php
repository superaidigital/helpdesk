<?php
header('Content-Type: application/json');
require_once 'includes/functions.php'; // This already includes db.php
check_auth(['it', 'admin']);

// =================================================================
// ส่วนของการตั้งค่า Gemini API
// =================================================================
// !!! กรุณากรอก API Key ของคุณที่นี่ !!!
define('GEMINI_API_KEY', 'AIzaSyCEHI88GtEHBHEE2C1vjrOyKKVv-1kl5W4'); 
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . GEMINI_API_KEY);

/**
 * ฟังก์ชันสำหรับเรียกใช้ Gemini API พร้อมการจัดการข้อผิดพลาดที่ละเอียดขึ้น
 * @param string $prompt ข้อความที่ต้องการส่งให้ AI วิเคราะห์
 * @return array [success: bool, data: string]
 */
function callGeminiAPI($prompt) {
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

    if ($curl_error) {
        error_log("cURL Error: " . $curl_error);
        return ['success' => false, 'data' => 'เกิดข้อผิดพลาดในการเชื่อมต่อ (cURL): ' . $curl_error];
    }

    $response_data = json_decode($response_json, true);

    if ($http_code !== 200 || isset($response_data['error'])) {
        error_log("Gemini API Error: " . $response_json);
        $error_message = $response_data['error']['message'] ?? 'ไม่สามารถสื่อสารกับ AI ได้';
        return ['success' => false, 'data' => 'API Error: ' . $error_message];
    }
    
    $text = $response_data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    
    if (is_null($text)) {
        return ['success' => false, 'data' => 'ไม่ได้รับการตอบกลับที่ถูกต้องจาก AI'];
    }

    return ['success' => true, 'data' => $text];
}

// =================================================================
// ส่วนหลักของการทำงาน
// =================================================================

$data = json_decode(file_get_contents('php://input'), true);
$title = $data['title'] ?? '';

if (empty($title)) {
    echo json_encode(['success' => false, 'suggestion' => 'ข้อมูลไม่เพียงพอสำหรับวิเคราะห์']);
    exit();
}

// 1. ค้นหาใน Knowledge Base ก่อน
$search_query = "%" . $title . "%";
$stmt = $conn->prepare("SELECT answer FROM knowledge_base WHERE question LIKE ? LIMIT 1");
$stmt->bind_param("s", $search_query);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $kb_item = $result->fetch_assoc();
    $suggestion = "💡 **พบข้อมูลที่ใกล้เคียงในฐานความรู้ (Knowledge Base):**\n\n";
    $suggestion .= "--------------------------------------------------\n\n";
    $suggestion .= htmlspecialchars($kb_item['answer']);
    $response = ['success' => true, 'suggestion' => $suggestion];
} else {
    // 2. ถ้าไม่พบ ให้เรียก Gemini API
    if (GEMINI_API_KEY === 'YOUR_GEMINI_API_KEY' || GEMINI_API_KEY === '') {
        $response = ['success' => false, 'suggestion' => "ข้อผิดพลาด: กรุณาตั้งค่า GEMINI_API_KEY ในไฟล์ ai_issue_helper.php ก่อนใช้งาน"];
    } else {
        $prompt = "ในฐานะผู้เชี่ยวชาญ IT Support ขององค์กร, โปรดวิเคราะห์ปัญหาจากข้อมูลต่อไปนี้ และให้คำแนะนำแนวทางการแก้ไขเบื้องต้นเป็นขั้นตอนที่ชัดเจนสำหรับเจ้าหน้าที่ IT:\n\n";
        $prompt .= "--- ข้อมูลปัญหา ---\n";
        $prompt .= "หมวดหมู่: " . htmlspecialchars($data['category'] ?? '') . "\n";
        $prompt .= "หัวข้อ: " . htmlspecialchars($title) . "\n";
        $prompt .= "รายละเอียด: " . htmlspecialchars($data['description'] ?? '') . "\n";
        $prompt .= "------------------\n\n";
        $prompt .= "คำแนะนำ (ตอบเป็นภาษาไทย):";

        $ai_result = callGeminiAPI($prompt);

        if ($ai_result['success']) {
            $response = [
                'success' => true,
                'suggestion' => "🤖 **บทวิเคราะห์และคำแนะนำจาก Gemini AI:**\n\n" . $ai_result['data']
            ];
        } else {
            $response = [
                'success' => false,
                'suggestion' => $ai_result['data'] // ส่งต่อข้อความ error ที่ละเอียดขึ้น
            ];
        }
    }
}

$stmt->close();
$conn->close();

echo json_encode($response);
exit();
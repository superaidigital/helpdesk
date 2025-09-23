<?php
// $page_title = "รายงานวิเคราะห์ภาพรวมโดย AI";
require_once 'includes/functions.php';
check_auth(['admin']);
require_once 'includes/header.php';

// --- Date Filtering Logic ---
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

$start_date = date('Y-m-d', strtotime("$year-$month-01"));
$end_date = date('Y-m-t', strtotime($start_date));

$thai_months = [
    '01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน',
    '05' => 'พฤษภาคม', '06' => 'มิถุนายน', '07' => 'กรกฎาคม', '08' => 'สิงหาคม',
    '09' => 'กันยายน', '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'
];
$report_title_month_year = $thai_months[$month] . " " . ($year + 543);

// --- Fetch and Summarize Data for AI ---
$data_summary = [];

// Overall stats
$stmt = $conn->prepare("SELECT COUNT(id) as total_issues, AVG(satisfaction_rating) as avg_rating FROM issues WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$overall_stats = $stmt->get_result()->fetch_assoc();
$data_summary['total_issues'] = $overall_stats['total_issues'] ?? 0;
$data_summary['avg_satisfaction'] = number_format($overall_stats['avg_rating'] ?? 0, 2);
$stmt->close();

// Issues by Category
$stmt = $conn->prepare("SELECT category, COUNT(id) as total FROM issues WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY category ORDER BY total DESC");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$data_summary['by_category'] = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Issues by Department
$stmt = $conn->prepare("SELECT reporter_department, COUNT(id) as total FROM issues WHERE DATE(created_at) BETWEEN ? AND ? AND reporter_department IS NOT NULL AND reporter_department != '' GROUP BY reporter_department ORDER BY total DESC");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$data_summary['by_department'] = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Top 5 issues
$stmt = $conn->prepare("SELECT title, COUNT(id) as total FROM issues WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY title ORDER BY total DESC LIMIT 5");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$data_summary['top_issues'] = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Convert the summary to a JSON string to be used in JavaScript
$data_summary_json = json_encode($data_summary, JSON_UNESCAPED_UNICODE);

?>
<div x-data="aiReportGenerator()">
    <!-- Header and Filters -->
    <div class="flex flex-col sm:flex-row justify-between items-start mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">รายงานวิเคราะห์ภาพรวมโดย AI</h2>
            <p class="text-gray-500">สำหรับเดือน <?php echo $report_title_month_year; ?></p>
        </div>
        <div class="flex items-center gap-4 bg-white p-2 rounded-lg shadow-sm">
            <form method="GET" action="admin_ai_report.php" class="flex items-center gap-2">
                <select name="month" class="border-gray-300 rounded-md shadow-sm text-sm">
                    <?php foreach ($thai_months as $m_num => $m_name): ?>
                        <option value="<?php echo $m_num; ?>" <?php echo ($m_num == $month) ? 'selected' : ''; ?>><?php echo $m_name; ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="year" class="border-gray-300 rounded-md shadow-sm text-sm">
                     <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($y == $year) ? 'selected' : ''; ?>><?php echo $y + 543; ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 text-sm">แสดงผล</button>
            </form>
        </div>
    </div>
    
    <!-- AI Analysis Section -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex flex-col sm:flex-row justify-between items-center pb-4 border-b">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fa-solid fa-wand-magic-sparkles text-indigo-500 mr-2"></i>
                บทวิเคราะห์และข้อเสนอแนะจาก AI
            </h3>
            <button @click="generateAnalysis(<?php echo htmlspecialchars($data_summary_json, ENT_QUOTES, 'UTF-8'); ?>)" :disabled="isLoading" class="mt-4 sm:mt-0 px-6 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700 disabled:bg-gray-400 transition-all transform hover:scale-105">
                <span x-show="!isLoading"><i class="fa-solid fa-brain mr-2"></i>วิเคราะห์ข้อมูลด้วย AI</span>
                <span x-show="isLoading" style="display: none;"><i class="fa-solid fa-spinner fa-spin mr-2"></i>กำลังประมวลผล...</span>
            </button>
        </div>

        <div x-show="showAnalysis" x-transition class="mt-4">
            <div x-html="aiAnalysisResult" class="prose prose-lg max-w-none"></div>
        </div>
        <div x-show="!showAnalysis && !isLoading" class="text-center py-12 text-gray-500">
             <i class="fa-solid fa-chart-line text-4xl mb-4"></i>
             <p>คลิกปุ่ม "วิเคราะห์ข้อมูลด้วย AI" เพื่อเริ่มการวิเคราะห์</p>
        </div>
    </div>
</div>

<script>
    function aiReportGenerator() {
        return {
            isLoading: false,
            showAnalysis: false,
            aiAnalysisResult: '',
            generateAnalysis(summaryData) {
                this.isLoading = true;
                this.showAnalysis = false;
                this.aiAnalysisResult = '<h4>กำลังสร้างบทวิเคราะห์...</h4>';

                const systemPrompt = `คุณคือผู้เชี่ยวชาญด้านการวิเคราะห์ข้อมูลและวางกลยุทธ์ IT Helpdesk สำหรับองค์กรภาครัฐ
                หน้าที่ของคุณคือการวิเคราะห์ข้อมูลสรุปรายเดือนที่ได้รับ และเขียนรายงานสรุปสำหรับผู้บริหาร
                โดยต้องวิเคราะห์หาแนวโน้ม, ปัญหาที่สำคัญ, และเสนอแนะแนวทางแก้ไขที่นำไปปฏิบัติได้จริง
                ใช้ภาษาไทยที่เป็นทางการแต่เข้าใจง่าย จัดรูปแบบด้วย Markdown`;

                const userQuery = `
                    นี่คือข้อมูลสรุปของ Helpdesk ประจำเดือน ${'<?php echo $report_title_month_year; ?>'}:
                    - จำนวนเรื่องทั้งหมด: ${summaryData.total_issues} เรื่อง
                    - คะแนนความพึงพอใจเฉลี่ย: ${summaryData.avg_satisfaction} / 5
                    - ปัญหา 5 อันดับแรก: ${JSON.stringify(summaryData.top_issues)}
                    - จำนวนเรื่องตามหมวดหมู่: ${JSON.stringify(summaryData.by_category)}
                    - จำนวนเรื่องตามหน่วยงาน: ${JSON.stringify(summaryData.by_department)}

                    จากข้อมูลทั้งหมดนี้ โปรดสร้างรายงานวิเคราะห์ตามโครงสร้างต่อไปนี้:
                    ### 1. สรุปภาพรวม
                    (สรุปสั้นๆ เกี่ยวกับปริมาณงานและความพึงพอใจในเดือนนี้)

                    ### 2. ปัญหาที่ต้องให้ความสำคัญ
                    (วิเคราะห์จาก 'ปัญหา 5 อันดับแรก' และ 'จำนวนเรื่องตามหมวดหมู่' เพื่อระบุว่าปัญหาประเภทไหนเกิดขึ้นบ่อยที่สุด)

                    ### 3. หน่วยงานที่ต้องการการสนับสนุนเป็นพิเศษ
                    (ระบุหน่วยงานที่มีการแจ้งปัญหาสูงสุด และวิเคราะห์ว่าอาจเกิดจากสาเหตุใด)

                    ### 4. ข้อเสนอแนะเชิงกลยุทธ์
                    (จากข้อมูลทั้งหมด ให้เสนอแนวทางแก้ไขหรือป้องกันปัญหาที่นำไปปฏิบัติได้จริง เช่น 'ควรจัดอบรมการใช้งานโปรแกรม X ให้กับหน่วยงาน Y' หรือ 'ควรพิจารณาเปลี่ยนอุปกรณ์ Z เนื่องจากมีการแจ้งปัญหาฮาร์ดแวร์บ่อยครั้ง')
                `;

                const apiKey = "AIzaSyB9mrBQ1AY4lg6UbgmeUczuYrCnY7FBI80"; // Your Gemini API Key
                const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-05-20:generateContent?key=${apiKey}`;
                
                const payload = {
                    contents: [{ parts: [{ text: userQuery }] }],
                    systemInstruction: { parts: [{ text: systemPrompt }] },
                };

                fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                .then(response => response.json())
                .then(result => {
                    const candidate = result.candidates?.[0];
                    if (candidate && candidate.content?.parts?.[0]?.text) {
                        let htmlResult = candidate.content.parts[0].text;
                        // Basic Markdown to HTML conversion
                        htmlResult = htmlResult.replace(/### (.*)/g, '<h3>$1</h3>');
                        htmlResult = htmlResult.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                        htmlResult = htmlResult.replace(/\* (.*)/g, '<li>$1</li>');
                        htmlResult = htmlResult.replace(/\n/g, '<br>');
                        this.aiAnalysisResult = htmlResult;
                    } else {
                        this.aiAnalysisResult = '<p class="text-red-500">ขออภัย, ไม่สามารถสร้างบทวิเคราะห์ได้ในขณะนี้</p>';
                    }
                    this.showAnalysis = true;
                })
                .catch(error => {
                    console.error("Error calling Gemini API:", error);
                    this.aiAnalysisResult = `<p class="text-red-500">เกิดข้อผิดพลาดในการเชื่อมต่อกับ AI: ${error.message}</p>`;
                    this.showAnalysis = true;
                })
                .finally(() => {
                    this.isLoading = false;
                });
            }
        }
    }
</script>

<?php 
$conn->close();
require_once 'includes/footer.php'; 
?>

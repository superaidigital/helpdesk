<?php
require_once 'includes/functions.php';
check_auth(['it', 'admin']);

$issue_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($issue_id === 0) {
    die("Invalid Issue ID.");
}

// Fetch issue details along with assigned user's info (including department and division)
$stmt = $conn->prepare("
    SELECT 
        i.*, 
        assignee.fullname as assigned_to_name, 
        assignee.position as assigned_to_position, 
        assignee.department as assigned_to_department,
        assignee.division as assigned_to_division,
        reporter_user.division as reporter_division
    FROM issues i
    LEFT JOIN users assignee ON i.assigned_to = assignee.id
    LEFT JOIN users reporter_user ON i.user_id = reporter_user.id
    WHERE i.id = ?
");
$stmt->bind_param("i", $issue_id);
$stmt->execute();
$issue = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$issue) {
    die("Issue not found.");
}

// Fetch latest comment and checklist data
$latest_comment = getLatestITComment($issue['id'], $issue['assigned_to'], $conn);
$checklist_data = getIssueChecklistItems($issue_id, $conn);
// [IMPROVEMENT] Get the correct checklist based on the issue's category
$checklist_items_for_display = get_checklist_by_category($issue['category']);


// Fetch all active categories from the database for the category selection section
$all_categories_result = $conn->query("SELECT name FROM categories WHERE is_active = 1 ORDER BY id ASC");
$all_categories = [];
if ($all_categories_result) {
    while($row = $all_categories_result->fetch_assoc()) {
        $all_categories[] = $row['name'];
    }
}


$thai_date = formatDate($issue['created_at']);
list($date_part, $time_part) = explode(',', $thai_date);
$time_part_cleaned = str_replace('น.', '', $time_part);

// Get completed time, if available
$completed_time_str = '-';
if ($issue['completed_at']) {
    $completed_thai_date = formatDate($issue['completed_at']);
    list(, $completed_time_part) = explode(',', $completed_thai_date);
    $completed_time_str = trim(str_replace('น.', '', $completed_time_part));
}


// Determine reporter's division
$reporter_division = $issue['user_id'] ? ($issue['reporter_division'] ?? '') : ($issue['division'] ?? '');

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ใบงานแจ้งปัญหา #<?php echo $issue['id']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Sarabun', sans-serif; font-size: 14px; }
        .a4-page { width: 210mm; min-height: 297mm; padding: 1.5cm; margin: 1cm auto; border: 1px #D1D5DB solid; background: white; box-shadow: 0 0 5px rgba(0, 0, 0, 0.1); }
        .form-section { border: 1px solid #333; padding: 0.75rem; }
        .form-title { font-weight: bold; font-size: 1rem; }
        .form-subtitle { font-size: 0.9rem; margin-top: -5px; }
        .field { display: flex; align-items: flex-end; border-bottom: 1px dotted #999; margin-top: 0.5rem; padding-bottom: 2px; }
        .field-label { font-weight: bold; flex-shrink: 0; }
        .field-value { width: 100%; padding-left: 0.5rem; }
        .checkbox { font-family: sans-serif; font-size: 1.2rem; margin-right: 0.5rem; }

        @media print {
            body { margin: 0; background: white; -webkit-print-color-adjust: exact; }
            .a4-page { border: initial; margin: 0; box-shadow: initial; width: initial; min-height: initial; }
            .no-print { display: none; }
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="no-print text-center my-4 space-x-2">
        <a href="issue_view.php?id=<?php echo $issue['id']; ?>" class="px-6 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">
            <i class="fa-solid fa-arrow-left mr-2"></i>กลับหน้ารายละเอียด
        </a>
        <button onclick="window.print()" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700">
            <i class="fa-solid fa-print mr-2"></i>พิมพ์ใบงาน
        </button>
    </div>

    <div class="a4-page">
        <header class="text-center relative mb-4">
            <div class="absolute left-0 top-0">
                 <img src="assets/images/LogoSSKPao.png" alt="Logo" class="h-16 w-16">
            </div>
            <h1 class="text-lg font-bold pt-2">แบบฟอร์มให้บริการ</h1>
            <p class="text-base">
                <?php 
                    $department_text = htmlspecialchars($issue['assigned_to_department'] ?? '');
                    $division_text = htmlspecialchars($issue['assigned_to_division'] ?? '');
                    echo 'ฝ่าย ' . ($division_text ?: '......................') . ' สังกัด ' . ($department_text ?: '......................');
                ?>
            </p>
        </header>

        <div class="form-section">
            <div class="flex justify-between items-center">
                <p class="form-title">ส่วนที่ 1 สำหรับผู้แจ้ง</p>
                <div class="flex space-x-4">
                    <div class="field"><span class="field-label">เลขที่:</span> <span class="field-value"><?php echo $issue['id']; ?></span></div>
                    <div class="field"><span class="field-label">วันที่:</span> <span class="field-value"><?php echo trim($date_part); ?></span></div>
                    <div class="field"><span class="field-label">เวลา:</span> <span class="field-value"><?php echo trim($time_part_cleaned); ?></span></div>
                </div>
            </div>
            <p class="form-subtitle">บันทึกการแจ้งปัญหา/ขอคำปรึกษา ด้าน IT</p>
             <div class="field mt-4"><span class="field-label">ชื่อ-นามสกุล:</span> <span class="field-value"><?php echo htmlspecialchars($issue['reporter_name']); ?></span></div>
             <div class="grid grid-cols-2 gap-x-6">
                <div class="field"><span class="field-label">สำนัก/กอง:</span> <span class="field-value"><?php echo htmlspecialchars($issue['reporter_department']); ?></span></div>
                <div class="field"><span class="field-label">ฝ่าย/งาน:</span> <span class="field-value"><?php echo htmlspecialchars($reporter_division); ?></span></div>
             </div>
             <div class="grid grid-cols-2 gap-x-6">
                <div class="field"><span class="field-label">โทร:</span> <span class="field-value"><?php echo htmlspecialchars($issue['reporter_contact']); ?></span></div>
                <div class="field"><span class="field-label">ประเภทเครื่อง:</span> <span class="field-value"></span></div>
             </div>
             <div class="field items-start mt-2">
                <span class="field-label">สาเหตุ/ปัญหา:</span>
                <div class="field-value min-h-[3rem]"><?php echo nl2br(htmlspecialchars($issue['description'])); ?></div>
             </div>

             <div class="mt-4">
                <p class="font-bold">หมวดหมู่รายการแจ้งปัญหา/ขอคำปรึกษา ด้าน IT </p>
                <div class="grid grid-cols-2 gap-x-4 text-sm mt-1">
                    <?php foreach ($all_categories as $cat_name): ?>
                        <div>
                            <span class="checkbox">
                                <?php echo ($issue['category'] === $cat_name) ? '☑' : '☐'; ?>
                            </span> 
                            <?php echo htmlspecialchars($cat_name); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
             </div>
        </div>

        <div class="form-section mt-4">
            <p class="form-title">ส่วนที่ ๒ สำหรับเจ้าหน้าที่</p>
            <p class="form-subtitle">บันทึกการตรวจสอบและแก้ไข</p>
            <div class="grid grid-cols-2 gap-x-6 mt-2">
                <div class="field"><span class="field-label">เวลาเริ่มดำเนินการ:</span> <span class="field-value"><?php echo trim($time_part_cleaned); ?></span></div>
                <div class="field"><span class="field-label">เวลาแล้วเสร็จ:</span> <span class="field-value"><?php echo $completed_time_str; ?></span></div>
            </div>
            
            <!-- Checklist Section -->
            <?php if ($issue['category'] !== 'อื่นๆ'): ?>
            <div class="mt-4">
                <p class="font-bold">รายการตรวจสอบและแก้ไข:</p>
                <div class="grid grid-cols-2 gap-x-4 text-sm mt-1">
                    <?php foreach($checklist_items_for_display as $item): 
                        $is_checked = isset($checklist_data[$item]) && $checklist_data[$item]['checked'];
                        $item_value = isset($checklist_data[$item]) ? $checklist_data[$item]['value'] : '';
                    ?>
                    <div>
                        <span class="checkbox"><?php echo $is_checked ? '☑' : '☐'; ?></span>
                        <span><?php echo htmlspecialchars($item); ?></span>
                        <?php if ($item === 'อื่นๆ' && $is_checked && !empty($item_value)): ?>
                            <span class="ml-2 text-indigo-600">(<?php echo htmlspecialchars($item_value); ?>)</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="mt-4 space-y-2">
                <p><span class="checkbox"><?php echo $issue['status'] === 'done' ? '☑' : '☐'; ?></span> ดำเนินการแล้วเสร็จ สามารถใช้งานได้ปกติ</p>
                 <div class="field items-start ml-8">
                    <span class="field-label">รายละเอียดการแก้ไขเพิ่มเติม:</span> 
                    <div class="field-value min-h-[4rem]"><?php echo nl2br(htmlspecialchars($latest_comment ?? '')); ?></div>
                </div>
                <p><span class="checkbox"><?php echo $issue['status'] === 'awaiting_parts' ? '☑' : '☐'; ?></span> ขอให้หน่วยงาน สั่งซื้ออุปกรณ์เพื่อใช้ในการซ่อม</p>
                <p><span class="checkbox"><?php echo $issue['status'] === 'cannot_resolve' ? '☑' : '☐'; ?></span> ไม่สามารถดำเนินการเองได้ / ให้ดำเนินการส่งซ่อม</p>
            </div>
            <div class="mt-6 flex justify-end">
                <div class="w-2/5 text-center">
                    <p>ลงชื่อ............................................</p>
                    <p class="mt-1">(<?php echo htmlspecialchars($issue['assigned_to_name'] ?? '............................................'); ?>)</p>
                    <p class="text-sm">เจ้าหน้าที่ผู้ดำเนินการ</p>
                </div>
            </div>
        </div>

        <div class="form-section mt-4">
            <p class="form-title">ส่วนที่ ๓ สำหรับผู้รับบริการ</p>
            <p class="form-subtitle">แบบสำรวจความพึงพอใจ</p>
            <div class="mt-2">
                <p class="font-bold">ความพึงพอใจในการรับบริการจากเจ้าหน้าที่</p>
                <div class="flex space-x-6 mt-1 text-sm">
                    <span><span class="checkbox"><?php echo ($issue['satisfaction_rating'] == 5) ? '☑' : '☐'; ?></span> ดีมาก</span>
                    <span><span class="checkbox"><?php echo ($issue['satisfaction_rating'] == 4) ? '☑' : '☐'; ?></span> ดี</span>
                    <span><span class="checkbox"><?php echo ($issue['satisfaction_rating'] == 3) ? '☑' : '☐'; ?></span> พอใช้</span>
                    <span><span class="checkbox"><?php echo ($issue['satisfaction_rating'] <= 2 && !is_null($issue['satisfaction_rating'])) ? '☑' : '☐'; ?></span> ควรปรับปรุง</span>
                </div>
            </div>
             <div class="field items-start mt-2"><span class="field-label">ข้อเสนอแนะ:</span><div class="field-value min-h-[2rem]"></div></div>
            <div class="grid grid-cols-2 gap-x-8 mt-10">
                 <div class="text-center">
                    <?php if (!empty($issue['signature_image'])): ?>
                        <img src="<?php echo htmlspecialchars($issue['signature_image']); ?>" alt="Signature" class="h-16 mx-auto">
                    <?php else: ?>
                        <p>ลงชื่อ............................................</p>
                    <?php endif; ?>
                    <p class="mt-1">(<?php echo htmlspecialchars($issue['reporter_name']); ?>)</p>
                    <p class="text-sm">ผู้รับบริการ</p>
                </div>
                <div class="text-center">
                     <p>ลงชื่อ............................................</p>
                    <p class="mt-1">(............................................)</p>
                    <p class="text-sm">หัวหน้าฝ่ายสถิติข้อมูลและสารสนเทศ</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php
$page_title = "รายละเอียดปัญหา";
require_once 'includes/functions.php';
// Allow any logged-in user to view, but actions will be restricted inside the page
check_auth(['user', 'it', 'admin']);
require_once 'includes/header.php';

// ADD SCRIPT FOR SIGNATURE PAD
echo '<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>';

$issue_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($issue_id === 0) {
    redirect_with_message('it_dashboard.php', 'error', 'ไม่ได้ระบุ ID ของปัญหา');
}

// Fetch main issue data, also join to get user's division if they are a registered user
$stmt = $conn->prepare("
    SELECT i.*, u.division as reporter_user_division 
    FROM issues i 
    LEFT JOIN users u ON i.user_id = u.id 
    WHERE i.id = ?
");
$stmt->bind_param("i", $issue_id);
$stmt->execute();
$result = $stmt->get_result();
$issue = $result->fetch_assoc();
$stmt->close();

if (!$issue) {
    redirect_with_message('it_dashboard.php', 'error', 'ไม่พบข้อมูลปัญหา ID: ' . $issue_id);
}

// --- Permission Check: Regular users can only see their own tickets ---
$current_user_data = getUserById($_SESSION['user_id'], $conn);
$is_reporter = false;
if (isset($issue['user_id']) && $issue['user_id'] == $_SESSION['user_id']) {
    $is_reporter = true;
} elseif (is_null($issue['user_id']) && !empty($current_user_data['email']) && $issue['reporter_contact'] === $current_user_data['email']) {
    $is_reporter = true;
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'user' && !$is_reporter) {
     redirect_with_message('user_dashboard.php', 'error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
}

// --- Define Editing & Evaluation Permissions ---
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_assigned_it = isset($issue['assigned_to']) && isset($_SESSION['user_id']) && $issue['assigned_to'] == $_SESSION['user_id'];
$is_job_open = $issue['status'] !== 'done';

// Admin can do anything. Assigned IT can work on open jobs.
$can_perform_actions = ($is_assigned_it && $is_job_open) || $is_admin;
$can_edit_reporter = ($is_assigned_it && $is_job_open) || $is_admin;


$can_evaluate = false;
if ($issue['status'] === 'done' && is_null($issue['signature_image'])) {
    // Evaluation button should show for the reporter OR any IT/Admin staff
    if ($is_reporter || $is_admin || $is_assigned_it) {
        $can_evaluate = true;
    }
}


// Fetch related data
$issue_files = getIssueFiles($issue_id, $conn);
$comments = getIssueComments($issue_id, $conn);
$other_it_staff_result = $conn->query("SELECT id, fullname FROM users WHERE role = 'it' AND id != " . (int)($issue['assigned_to'] ?? 0));

// Fetch checklist data
$checklist_items_db = getIssueChecklistItems($issue_id, $conn);

// Get the correct checklist based on the issue's category
$default_checklist = get_checklist_by_category($issue['category']);
$json_checklist_items_db = json_encode($checklist_items_db);


// --- Display Maps ---
$status_text_map = [ 
    'pending' => 'รอตรวจสอบ', 
    'in_progress' => 'กำลังดำเนินการ', 
    'done' => 'เสร็จสิ้น', 
    'cannot_resolve' => 'ไม่สามารถดำเนินการเองได้',
    'awaiting_parts' => 'รอสั่งซื้ออุปกรณ์'
];
$status_color_map = [ 
    'pending' => 'bg-yellow-100 text-yellow-800', 
    'in_progress' => 'bg-blue-100 text-blue-800', 
    'done' => 'bg-green-100 text-green-800', 
    'cannot_resolve' => 'bg-red-100 text-red-800',
    'awaiting_parts' => 'bg-purple-100 text-purple-800'
];
$category_icon_map = [ 'ฮาร์ดแวร์' => 'fa-desktop', 'ซอฟต์แวร์' => 'fa-window-maximize', 'ระบบเครือข่าย' => 'fa-wifi', 'ระบบสารบรรณ/ERP' => 'fa-file-invoice', 'อีเมล' => 'fa-envelope-open-text', 'อื่นๆ' => 'fa-question-circle' ];
$current_category_icon = $category_icon_map[$issue['category']] ?? 'fa-question-circle';

// Determine reporter's division (if available)
$reporter_division = $issue['user_id'] ? ($issue['reporter_user_division'] ?? '') : ($issue['division'] ?? '');
?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="{ selectedStatus: '<?php echo $issue['status']; ?>', isEditingReporter: false, editingCommentId: null, deleteCommentId: null, deleteCommentText: '', isEvaluationModalOpen: false, ai: { loading: false, suggestion: '', error: '' } }">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-lg shadow-md p-6">
             <div class="flex justify-between items-start">
                <div class="flex items-center gap-4">
                    <i class="fa-solid <?php echo $current_category_icon; ?> text-3xl text-indigo-500"></i>
                    <div>
                        <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($issue['title']); ?></h2>
                        <p class="text-sm text-gray-500">แจ้งเมื่อ: <?php echo formatDate($issue['created_at']); ?></p>
                    </div>
                </div>
                <?php if (in_array($_SESSION['role'], ['it', 'admin'])): ?>
                <a href="work_order.php?id=<?php echo $issue['id']; ?>" target="_blank" class="flex-shrink-0 ml-4 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                    <i class="fa-solid fa-print mr-2"></i>พิมพ์ใบงาน
                </a>
                <?php endif; ?>
            </div>
            <p class="mt-4 text-gray-600 border-t pt-4"><?php echo nl2br(htmlspecialchars($issue['description'])); ?></p>
            
            <?php if (!empty($issue_files)): ?>
            <div class="mt-4">
                <p class="font-semibold mb-2">ไฟล์แนบจากผู้แจ้ง:</p>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <?php foreach ($issue_files as $file): ?>
                    <a href="<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="block bg-gray-50 p-3 rounded-lg hover:bg-gray-100 transition">
                        <div class="flex items-center">
                            <i class="fa-solid fa-file text-3xl mr-3 w-8 text-center text-gray-400"></i>
                            <span class="font-medium text-gray-800 text-sm truncate"><?php echo htmlspecialchars($file['file_name']); ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($can_perform_actions): ?>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="font-semibold text-gray-800 text-lg border-b pb-3 mb-4">
                <i class="fa-solid fa-robot text-indigo-500 mr-2"></i>ผู้ช่วย AI
            </h3>
            <div x-show="!ai.suggestion && !ai.loading">
                <p class="text-sm text-gray-600 mb-4">ให้ AI ช่วยวิเคราะห์ปัญหาและแนะนำแนวทางการแก้ไขเบื้องต้น</p>
                <button @click="
                    ai.loading = true;
                    ai.error = '';
                    fetch('ai_issue_helper.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            title: '<?php echo addslashes(htmlspecialchars($issue['title'])); ?>',
                            description: '<?php echo addslashes(htmlspecialchars($issue['description'])); ?>',
                            category: '<?php echo addslashes(htmlspecialchars($issue['category'])); ?>'
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(data.success) {
                            ai.suggestion = data.suggestion;
                        } else {
                            ai.error = 'ไม่สามารถเรียกข้อมูลได้';
                        }
                    })
                    .catch(() => ai.error = 'เกิดข้อผิดพลาดในการเชื่อมต่อ')
                    .finally(() => ai.loading = false)
                " class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold hover:bg-indigo-700">
                    <i class="fa-solid fa-brain mr-2"></i>เริ่มการวิเคราะห์
                </button>
            </div>
            <div x-show="ai.loading" class="text-center text-gray-500">
                <i class="fa-solid fa-spinner fa-spin text-2xl"></i>
                <p class="mt-2">AI กำลังวิเคราะห์ข้อมูล...</p>
            </div>
            <div x-show="ai.suggestion" x-transition class="prose max-w-none text-gray-700 bg-indigo-50 p-4 rounded-lg">
                <pre class="bg-transparent p-0 whitespace-pre-wrap font-sans" x-text="ai.suggestion"></pre>
            </div>
            <div x-show="ai.error" x-text="ai.error" class="text-red-500 mt-2"></div>
        </div>
        <?php endif; ?>

        <?php if ($can_perform_actions): ?>
        <div class="bg-white p-6 rounded-lg shadow-md">
           <h3 class="font-semibold mb-4 text-gray-800 text-lg border-b pb-3">ดำเนินการ</h3>
           
           <?php display_flash_message(); ?>

            <form action="issue_action.php" method="POST" enctype="multipart/form-data">
                <?php echo generate_csrf_token(); ?>
                <input type="hidden" name="issue_id" value="<?php echo $issue['id']; ?>">
                <div class="space-y-4">
                    <div>
                        <label for="status" class="text-sm font-medium">เปลี่ยนสถานะ</label>
                        <select id="status" name="status" x-model="selectedStatus" class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="in_progress">กำลังดำเนินการ</option>
                            <option value="done">เสร็จสิ้น</option>
                            <option value="awaiting_parts">รอสั่งซื้ออุปกรณ์</option>
                            <option value="cannot_resolve">ไม่สามารถดำเนินการเองได้</option>
                            <option value="forward">ส่งงานต่อ</option>
                        </select>
                    </div>

                    <div x-show="selectedStatus === 'forward'" x-transition class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                        <label class="text-sm font-medium">ส่งต่องานให้</label>
                        <div class="flex items-center space-x-2 mt-1">
                            <select name="forward_to_user_id" class="flex-grow rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <?php while($it_user = $other_it_staff_result->fetch_assoc()): ?>
                                <option value="<?php echo $it_user['id']; ?>"><?php echo htmlspecialchars($it_user['fullname']); ?></option>
                                <?php endwhile; ?>
                            </select>
                            <button type="submit" name="submit_forward" class="px-3 py-2 bg-yellow-500 text-white rounded-md text-sm font-semibold">ส่งงานต่อ</button>
                        </div>
                    </div>
                    
                     <div x-show="selectedStatus !== 'forward'">
                        <label for="comment_text" class="text-sm font-medium">เพิ่มความคิดเห็น / บันทึกการแก้ไข</label>
                        <textarea id="comment_text" name="comment_text" rows="3" class="w-full mt-1 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm" placeholder="..."></textarea>
                    </div>
                     <div x-show="selectedStatus !== 'forward'">
                        <label class="text-sm font-medium">แนบไฟล์ประกอบ</label>
                        <input type="file" name="comment_files[]" multiple class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 mt-1"/>
                    </div>
                    <div x-show="selectedStatus !== 'forward'">
                        <label class="text-sm font-medium">แนบลิงก์</label>
                        <input type="url" name="attachment_link" placeholder="https://example.com" class="w-full mt-1 border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm">
                    </div>
                    <div x-show="selectedStatus !== 'forward'" class="flex justify-end space-x-2">
                        <button type="submit" name="submit_kb" class="px-4 py-2 bg-amber-500 text-white rounded-md text-sm font-semibold hover:bg-amber-600">
                            <i class="fa-solid fa-lightbulb mr-2"></i>เก็บเป็น KB
                        </button>
                        <button type="submit" name="submit_update" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold hover:bg-indigo-700">
                            <i class="fa-solid fa-save mr-2"></i>บันทึกการเปลี่ยนแปลง
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($can_perform_actions): ?>
        <div class="bg-white rounded-lg shadow-md p-6" x-data="checklistHandler(<?php echo $issue['id']; ?>, <?php echo htmlspecialchars($json_checklist_items_db, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401); ?>)">
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                 <h3 class="font-semibold text-lg text-gray-800">รายการตรวจสอบ (Checklist)</h3>
                 <span x-show="saveStatus.message" :class="{ 'text-green-600': saveStatus.success, 'text-red-600': !saveStatus.success }" class="text-sm mr-4" x-text="saveStatus.message" x-transition></span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3">
                <?php foreach($default_checklist as $item): ?>
                <div class="flex items-start space-x-3">
                    <input type="checkbox" 
                           x-model="items['<?php echo $item; ?>'].checked"
                           class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-1 shrink-0">
                    <div class="flex-grow">
                        <label class="text-gray-700 cursor-pointer" :class="{'line-through text-gray-400': items['<?php echo $item; ?>'] && items['<?php echo $item; ?>'].checked}"><?php echo $item; ?></label>
                        <?php if ($item === 'อื่นๆ'): ?>
                            <div x-show="items['<?php echo $item; ?>'] && items['<?php echo $item; ?>'].checked" x-transition>
                                <input type="text" 
                                       x-model="items['<?php echo $item; ?>'].value"
                                       placeholder="ระบุรายละเอียด..."
                                       class="text-sm w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
             <div class="flex justify-end items-center mt-6 pt-4 border-t">
                 <button @click="saveAllChanges" class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700 disabled:bg-gray-400" :disabled="isSaving">
                    <span x-show="!isSaving"><i class="fa-solid fa-save mr-2"></i>บันทึก Checklist</span>
                    <span x-show="isSaving" style="display: none;"><i class="fa-solid fa-spinner fa-spin mr-2"></i>กำลังบันทึก...</span>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="font-semibold text-lg mb-4 text-gray-800">ประวัติการดำเนินการ</h3>
            <div class="space-y-4">
                <?php if (!empty($comments)): ?>
                    <?php foreach ($comments as $comment): ?>
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                             <img class="h-10 w-10 rounded-full object-cover" src="<?php echo htmlspecialchars(get_user_avatar($comment['image_url'])); ?>" alt="Profile image of <?php echo htmlspecialchars($comment['fullname']); ?>">
                        </div>
                        <div class="w-full">
                            <div class="flex justify-between items-center">
                                <p><strong><?php echo htmlspecialchars($comment['fullname']); ?></strong> 
                                   <span class="text-xs text-gray-500"><?php echo formatDate($comment['created_at']); ?></span>
                                </p>
                                <?php if ($is_admin || (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $comment['user_id'])): ?>
                                <div class="flex items-center space-x-2 text-xs">
                                    <button @click="editingCommentId = <?php echo $comment['id']; ?>" class="text-gray-400 hover:text-indigo-600"><i class="fa-solid fa-pencil"></i></button>
                                    <button @click="deleteCommentId = <?php echo $comment['id']; ?>; deleteCommentText = '<?php echo htmlspecialchars(addslashes(substr($comment['comment_text'], 0, 50) . '...')); ?>';" class="text-gray-400 hover:text-red-600"><i class="fa-solid fa-trash"></i></button>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div x-show="editingCommentId !== <?php echo $comment['id']; ?>" class="text-gray-700 bg-gray-100 p-3 rounded-lg mt-1">
                                <p><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                                <?php if (!empty($comment['files']) || !empty($comment['attachment_link'])): ?>
                                <div class="mt-2 pt-2 border-t border-gray-200 space-y-2">
                                    <?php if (!empty($comment['files'])): ?>
                                    <div>
                                        <p class="text-xs font-semibold mb-1">ไฟล์แนบ:</p>
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach($comment['files'] as $file): ?>
                                            <a href="<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="text-xs flex items-center bg-white p-1 rounded border hover:bg-gray-50">
                                                <i class="fa-solid fa-paperclip mr-1"></i> <?php echo htmlspecialchars($file['file_name']); ?>
                                            </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($comment['attachment_link'])): ?>
                                    <div>
                                        <p class="text-xs font-semibold mb-1">ลิงก์:</p>
                                        <a href="<?php echo htmlspecialchars($comment['attachment_link']); ?>" target="_blank" class="text-xs text-indigo-600 hover:underline flex items-center">
                                            <i class="fa-solid fa-link mr-1"></i><?php echo htmlspecialchars($comment['attachment_link']); ?>
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <form x-show="editingCommentId === <?php echo $comment['id']; ?>" action="comment_action.php" method="POST" class="mt-2">
                                <?php echo generate_csrf_token(); ?>
                                <input type="hidden" name="action" value="edit_comment">
                                <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                <input type="hidden" name="issue_id" value="<?php echo $issue_id; ?>">
                                <textarea name="comment_text" class="w-full border-gray-300 rounded-md text-sm" rows="3"><?php echo htmlspecialchars($comment['comment_text']); ?></textarea>
                                <div class="flex justify-end space-x-2 mt-2">
                                    <button type="button" @click="editingCommentId = null" class="px-3 py-1 bg-gray-200 text-gray-800 text-xs font-semibold rounded-md">ยกเลิก</button>
                                    <button type="submit" class="px-3 py-1 bg-indigo-600 text-white text-xs font-semibold rounded-md">บันทึก</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500">ยังไม่มีการดำเนินการ</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="lg:sticky top-24 space-y-6 self-start">
        <div class="bg-white rounded-lg shadow-md p-6">
             <div x-show="!isEditingReporter" class="space-y-4">
                <div>
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold text-gray-800">ผู้แจ้ง</h3>
                        <?php if ($can_edit_reporter): ?>
                        <button @click="isEditingReporter = true" class="text-sm text-indigo-600 hover:text-indigo-800" title="แก้ไขข้อมูลผู้แจ้ง">
                            <i class="fa-solid fa-pencil"></i> แก้ไข
                        </button>
                        <?php endif; ?>
                    </div>
                    <p class="text-gray-600"><?php echo htmlspecialchars($issue['reporter_name']); ?></p>
                </div>
                 <div><h3 class="font-semibold text-gray-800">สถานะปัจจุบัน</h3><span class="mt-1 inline-block px-2 text-xs leading-5 font-semibold rounded-full <?php echo $status_color_map[$issue['status']] ?? ''; ?>"><?php echo $status_text_map[$issue['status']] ?? htmlspecialchars($issue['status']); ?></span></div>
                <?php if ($issue['reporter_position']): ?><div><h3 class="font-semibold text-gray-800">ตำแหน่ง</h3><p class="text-gray-600"><?php echo htmlspecialchars($issue['reporter_position']); ?></p></div><?php endif; ?>
                <?php if ($issue['reporter_department']): ?><div><h3 class="font-semibold text-gray-800">สังกัด</h3><p class="text-gray-600"><?php echo htmlspecialchars($issue['reporter_department']); ?></p></div><?php endif; ?>
                <?php if (isset($reporter_division) && $reporter_division): ?><div><h3 class="font-semibold text-gray-800">ฝ่าย</h3><p class="text-gray-600"><?php echo htmlspecialchars($reporter_division); ?></p></div><?php endif; ?>
                <?php if ($issue['reporter_contact']): ?><div><h3 class="font-semibold text-gray-800">ข้อมูลติดต่อ</h3><p class="text-gray-600"><?php echo htmlspecialchars($issue['reporter_contact']); ?></p></div><?php endif; ?>
                <div><h3 class="font-semibold text-gray-800">หมวดหมู่</h3><p class="text-gray-600"><?php echo htmlspecialchars($issue['category']); ?></p></div>
                <div><h3 class="font-semibold text-gray-800">ความเร่งด่วน</h3><p class="text-gray-600"><?php echo htmlspecialchars($issue['urgency']); ?></p></div>
                <div><h3 class="font-semibold text-gray-800">ผู้รับผิดชอบ</h3><p class="text-gray-600"><?php echo getUserNameById($issue['assigned_to'], $conn); ?></p></div>
            </div>
            
            <?php if ($can_edit_reporter): ?>
            <form x-show="isEditingReporter" x-transition action="issue_action.php" method="POST" class="space-y-4">
                <?php echo generate_csrf_token(); ?>
                <input type="hidden" name="issue_id" value="<?php echo $issue['id']; ?>">
                <input type="hidden" name="action" value="edit_reporter">

                <h3 class="font-semibold text-gray-800 border-b pb-2">แก้ไขข้อมูลผู้แจ้ง</h3>
                 <div>
                    <label for="form_reporter_name" class="block text-sm font-medium text-gray-700">ชื่อผู้แจ้ง</label>
                    <input type="text" name="reporter_name" id="form_reporter_name" value="<?php echo htmlspecialchars($issue['reporter_name']); ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm py-2 px-3 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="form_reporter_contact" class="block text-sm font-medium text-gray-700">ข้อมูลติดต่อ</label>
                    <input type="text" name="reporter_contact" id="form_reporter_contact" value="<?php echo htmlspecialchars($issue['reporter_contact']); ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm py-2 px-3 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="form_reporter_position" class="block text-sm font-medium text-gray-700">ตำแหน่ง</label>
                    <input type="text" name="reporter_position" id="form_reporter_position" value="<?php echo htmlspecialchars($issue['reporter_position']); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm py-2 px-3 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="form_reporter_department" class="block text-sm font-medium text-gray-700">สังกัด</label>
                    <input type="text" name="reporter_department" id="form_reporter_department" value="<?php echo htmlspecialchars($issue['reporter_department']); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm py-2 px-3 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="form_reporter_division" class="block text-sm font-medium text-gray-700">ฝ่าย</label>
                    <input type="text" name="division" id="form_reporter_division" value="<?php echo htmlspecialchars($reporter_division ?? ''); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm py-2 px-3 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="isEditingReporter = false" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">ยกเลิก</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">บันทึก</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="font-semibold text-lg text-gray-800 mb-4">การยืนยันและประเมินผล</h3>
            <?php if ($can_evaluate): ?>
                 <div class="text-center">
                    <button @click="isEvaluationModalOpen = true" class="w-full px-6 py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 text-lg transition-transform transform hover:scale-105">
                        <i class="fa-solid fa-clipboard-check mr-2"></i>ประเมินผล
                    </button>
                </div>
             <?php elseif (!is_null($issue['signature_image'])): ?>
                <div class="space-y-4">
                     <div>
                         <h4 class="font-semibold text-gray-800">ลายมือชื่อผู้รับบริการ</h4>
                         <div class="mt-2 p-2 border rounded-md bg-gray-50 flex justify-center">
                             <img src="<?php echo htmlspecialchars($issue['signature_image']); ?>" alt="ลายมือชื่อ" class="max-h-20">
                         </div>
                     </div>
                     <?php if (!is_null($issue['satisfaction_rating'])): ?>
                     <div>
                         <h4 class="font-semibold text-gray-800">คะแนนความพึงพอใจ</h4>
                         <p class="text-lg font-bold text-amber-500"><?php echo str_repeat('★', $issue['satisfaction_rating']) . str_repeat('☆', 5 - $issue['satisfaction_rating']); ?> <span class="text-sm text-gray-600">(<?php echo $issue['satisfaction_rating']; ?>/5)</span></p>
                     </div>
                     <?php endif; ?>
                 </div>
            <?php else: ?>
                 <p class="text-sm text-gray-500 text-center">
                    <?php if ($issue['status'] === 'done'): ?>
                        <i class="fa-solid fa-hourglass-half mr-2"></i> รอผู้แจ้ง (<?php echo htmlspecialchars($issue['reporter_name']); ?>) ปิดงานและประเมินผล
                    <?php else: ?>
                        <i class="fa-solid fa-info-circle mr-2"></i> จะสามารถปิดงานและประเมินผลได้เมื่อสถานะเป็น "เสร็จสิ้น"
                    <?php endif; ?>
                 </p>
            <?php endif; ?>
        </div>
    </div>

    <div x-show="deleteCommentId !== null" @keydown.escape.window="deleteCommentId = null" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50" x-cloak>
        <div class="bg-white rounded-lg shadow-2xl max-w-sm w-full" @click.away="deleteCommentId = null">
            <div class="p-6 text-center">
                <i class="fa-solid fa-triangle-exclamation text-4xl text-red-500 mx-auto mb-4"></i>
                <h3 class="font-semibold text-lg text-gray-800">ยืนยันการลบความคิดเห็น</h3>
                <p class="mt-2 text-gray-600">คุณแน่ใจหรือไม่ว่าต้องการลบความคิดเห็นนี้: "<strong x-text="deleteCommentText"></strong>"?</p>
            </div>
            <form action="comment_action.php" method="POST" class="bg-gray-50 px-6 py-3 flex justify-end space-x-3 rounded-b-lg">
                <?php echo generate_csrf_token(); ?>
                <input type="hidden" name="action" value="delete_comment">
                <input type="hidden" name="comment_id" :value="deleteCommentId">
                <input type="hidden" name="issue_id" value="<?php echo $issue_id; ?>">
                <button type="button" @click="deleteCommentId = null" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">ยืนยันการลบ</button>
            </form>
        </div>
    </div>

    <div x-show="isEvaluationModalOpen" @keydown.escape.window="isEvaluationModalOpen = false" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60" x-cloak>
        <div class="bg-white rounded-lg shadow-2xl max-w-lg w-full" @click.away="isEvaluationModalOpen = false">
            <div class="p-4 border-b flex justify-between items-center">
                 <h3 class="font-semibold text-lg text-gray-800">ประเมินผลและปิดงาน</h3>
                 <button @click="isEvaluationModalOpen = false" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <div class="p-6">
                <form action="track_issue_action.php" method="POST" class="space-y-6" x-data="{ satisfaction: null }">
                    <?php echo generate_csrf_token(); ?>
                    <input type="hidden" name="issue_id" value="<?php echo $issue_id; ?>">
                    <input type="hidden" name="signature_data" id="signature_data">
                    <input type="hidden" name="source" value="issue_view">

                    <div>
                        <label class="font-semibold text-gray-800 mb-2 block">1. ลงลายมือชื่อเพื่อปิดงาน</label>
                        <div class="border-2 border-gray-300 rounded-md bg-white">
                            <canvas id="signature-pad" class="w-full h-40"></canvas>
                        </div>
                        <div class="text-center mt-2">
                            <button type="button" id="clear-signature" class="text-sm text-gray-500 hover:text-red-600">ล้างลายเซ็น</button>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <label class="font-semibold text-gray-800 block">2. ประเมินความพึงพอใจ</label>
                        <div class="flex space-x-4 mt-2 text-4xl justify-center">
                            <label class="cursor-pointer"><input type="radio" name="satisfaction_rating" value="5" class="sr-only" @change="satisfaction = 5" required><i class="fa-solid fa-face-laugh-beam transition-transform" :class="{'text-green-500 scale-125': satisfaction === 5, 'text-gray-300 hover:text-green-400': satisfaction !== 5}"></i></label>
                            <label class="cursor-pointer"><input type="radio" name="satisfaction_rating" value="4" class="sr-only" @change="satisfaction = 4"><i class="fa-solid fa-face-smile transition-transform" :class="{'text-lime-500 scale-125': satisfaction === 4, 'text-gray-300 hover:text-lime-400': satisfaction !== 4}"></i></label>
                            <label class="cursor-pointer"><input type="radio" name="satisfaction_rating" value="3" class="sr-only" @change="satisfaction = 3"><i class="fa-solid fa-face-meh transition-transform" :class="{'text-yellow-500 scale-125': satisfaction === 3, 'text-gray-300 hover:text-yellow-400': satisfaction !== 3}"></i></label>
                            <label class="cursor-pointer"><input type="radio" name="satisfaction_rating" value="2" class="sr-only" @change="satisfaction = 2"><i class="fa-solid fa-face-frown transition-transform" :class="{'text-orange-500 scale-125': satisfaction === 2, 'text-gray-300 hover:text-orange-400': satisfaction !== 2}"></i></label>
                            <label class="cursor-pointer"><input type="radio" name="satisfaction_rating" value="1" class="sr-only" @change="satisfaction = 1"><i class="fa-solid fa-face-sad-tear transition-transform" :class="{'text-red-500 scale-125': satisfaction === 1, 'text-gray-300 hover:text-red-400': satisfaction !== 1}"></i></label>
                        </div>
                    </div>
                    
                    <button type="submit" name="submit_close_job" class="w-full px-6 py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 text-lg">
                        <i class="fa-solid fa-check-double mr-2"></i>ยืนยันการปิดงาน
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function checklistHandler(issueId, initialItems) {
        return {
            issueId: issueId,
            items: {},
            isSaving: false,
            saveStatus: { success: false, message: '' },
            
            init() {
                const defaultKeys = <?php echo json_encode($default_checklist); ?>;
                defaultKeys.forEach(key => {
                    this.items[key] = {
                        checked: (initialItems[key] && initialItems[key].checked) ? true : false,
                        value: (initialItems[key] && initialItems[key].value) ? initialItems[key].value : ''
                    };
                });
            },
            
            saveAllChanges() {
                this.isSaving = true;
                this.saveStatus.message = '';
                fetch('issue_checklist_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ issue_id: this.issueId, checklist_data: this.items })
                })
                .then(response => response.ok ? response.json() : Promise.reject(response))
                .then(data => {
                    this.saveStatus = data.success ? { success: true, message: 'บันทึกเรียบร้อย!' } : { success: false, message: 'เกิดข้อผิดพลาด: ' + (data.message || 'ไม่ทราบสาเหตุ') };
                })
                .catch(error => {
                    this.saveStatus = { success: false, message: 'เกิดข้อผิดพลาดในการเชื่อมต่อ' };
                    console.error('Error:', error);
                })
                .finally(() => {
                    this.isSaving = false;
                    setTimeout(() => { this.saveStatus.message = '' }, 5000);
                });
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        let signaturePad = null;
        const canvas = document.getElementById('signature-pad');

        // Function to initialize SignaturePad
        function initializeSignaturePad() {
            if (canvas && !signaturePad) {
                signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgb(255, 255, 255)' });
                
                // Adjust canvas size on window resize
                function resizeCanvas() {
                    const ratio = Math.max(window.devicePixelRatio || 1, 1);
                    canvas.width = canvas.offsetWidth * ratio;
                    canvas.height = canvas.offsetHeight * ratio;
                    canvas.getContext("2d").scale(ratio, ratio);
                    signaturePad.clear(); // Clear signature after resize
                }
                window.addEventListener("resize", resizeCanvas);
                resizeCanvas();

                document.getElementById('clear-signature').addEventListener('click', () => signaturePad.clear());

                const form = canvas.closest('form');
                form.addEventListener('submit', function (event) {
                    if (signaturePad.isEmpty()) {
                        alert("กรุณาลงลายมือชื่อเพื่อยืนยันการปิดงาน");
                        event.preventDefault();
                        return;
                    }
                    document.getElementById('signature_data').value = signaturePad.toDataURL('image/png');
                });
            }
        }

        // Use MutationObserver to initialize the signature pad only when the modal becomes visible
        const modal = document.querySelector('[x-show="isEvaluationModalOpen"]');
        if(modal) {
             const observer = new MutationObserver((mutationsList) => {
                for(const mutation of mutationsList) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                        // Check if modal is being displayed
                        if (modal.style.display !== 'none' && !signaturePad) {
                             initializeSignaturePad();
                        } else if (modal.style.display === 'none' && signaturePad) {
                            // Optional: destroy signature pad instance when modal is hidden to free resources
                            // signaturePad.off();
                            // signaturePad = null;
                        }
                    }
                }
            });
            observer.observe(modal, { attributes: true });
        }
    });
</script>
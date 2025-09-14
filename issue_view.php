<?php
$page_title = "รายละเอียดปัญหา";
require_once 'includes/functions.php';
// Allow any logged-in user to view, but actions will be restricted inside the page
check_auth(['user', 'it', 'admin']);
require_once 'includes/header.php';

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
if (isset($_SESSION['role']) && $_SESSION['role'] === 'user' && isset($issue['user_id']) && $issue['user_id'] != $_SESSION['user_id']) {
     redirect_with_message('user_dashboard.php', 'error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
}


// Fetch related data
$issue_files = getIssueFiles($issue_id, $conn);
$comments = getIssueComments($issue_id, $conn);
$other_it_staff_result = $conn->query("SELECT id, fullname FROM users WHERE role = 'it' AND id != " . (int)($issue['assigned_to'] ?? 0));

// Fetch checklist data
$checklist_items_db = getIssueChecklistItems($issue_id, $conn);

// Get the correct checklist based on the issue's category
$default_checklist = get_checklist_by_category($issue['category']);

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
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="{ selectedStatus: '<?php echo $issue['status']; ?>', isEditingReporter: false }">
    <!-- Left Column -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Issue Details Card -->
        <div class="bg-white rounded-lg shadow-md p-6">
             <div class="flex items-center gap-4">
                <i class="fa-solid <?php echo $current_category_icon; ?> text-3xl text-indigo-500"></i>
                <div>
                    <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($issue['title']); ?></h2>
                    <p class="text-sm text-gray-500">แจ้งเมื่อ: <?php echo formatDate($issue['created_at']); ?></p>
                </div>
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

        <!-- IT Action Card: Show ONLY to the assigned IT staff -->
        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === (int)$issue['assigned_to']): ?>
        <div class="bg-white p-6 rounded-lg shadow-md">
           <h3 class="font-semibold mb-4 text-gray-800 text-lg border-b pb-3">ดำเนินการ</h3>
           
           <?php display_flash_message(); ?>

            <form action="issue_action.php" method="POST" enctype="multipart/form-data">
                <?php echo generate_csrf_token(); ?>
                <input type="hidden" name="issue_id" value="<?php echo $issue['id']; ?>">
                <div class="space-y-4">
                    <div>
                        <label for="status" class="text-sm font-medium">เปลี่ยนสถานะ</label>
                        <select id="status" name="status" x-model="selectedStatus" class="w-full mt-1 rounded-md border-gray-300 text-sm">
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
                            <select name="forward_to_user_id" class="flex-grow rounded-md border-gray-300 text-sm">
                                <?php while($it_user = $other_it_staff_result->fetch_assoc()): ?>
                                <option value="<?php echo $it_user['id']; ?>"><?php echo htmlspecialchars($it_user['fullname']); ?></option>
                                <?php endwhile; ?>
                            </select>
                            <button type="submit" name="submit_forward" class="px-3 py-2 bg-yellow-500 text-white rounded-md text-sm font-semibold">ส่งงานต่อ</button>
                        </div>
                    </div>
                    
                     <div x-show="selectedStatus !== 'forward'">
                        <label for="comment_text" class="text-sm font-medium">เพิ่มความคิดเห็น / บันทึกการแก้ไข</label>
                        <textarea id="comment_text" name="comment_text" rows="3" class="w-full mt-1 border-gray-300 rounded-md text-sm" placeholder="..."></textarea>
                    </div>
                     <div x-show="selectedStatus !== 'forward'">
                        <label class="text-sm font-medium">แนบไฟล์ประกอบ</label>
                        <input type="file" name="comment_files[]" multiple class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 mt-1"/>
                    </div>
                    <div x-show="selectedStatus !== 'forward'">
                        <label class="text-sm font-medium">แนบลิงก์</label>
                        <input type="url" name="attachment_link" placeholder="https://example.com" class="w-full mt-1 border-gray-300 rounded-md text-sm">
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

        <!-- Checklist Card (Show ONLY to assigned IT) -->
        <div class="bg-white rounded-lg shadow-md p-6" x-data='checklistHandler(<?php echo $issue_id; ?>, <?php echo htmlspecialchars(json_encode($checklist_items_db), ENT_QUOTES, 'UTF-8'); ?>)'>
            <h3 class="font-semibold text-lg mb-4 text-gray-800 border-b pb-3">รายการตรวจสอบ (Checklist)</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3">
                <?php foreach($default_checklist as $item): ?>
                <div class="flex items-start space-x-3">
                    <input type="checkbox" 
                           :checked="items['<?php echo $item; ?>'] && items['<?php echo $item; ?>'].checked"
                           @change="toggleCheck('<?php echo $item; ?>')"
                           class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-1 shrink-0">
                    <div class="flex-grow">
                        <label class="text-gray-700 cursor-pointer" :class="{'line-through text-gray-400': items['<?php echo $item; ?>'] && items['<?php echo $item; ?>'].checked}"><?php echo $item; ?></label>
                        <?php if ($item === 'อื่นๆ'): ?>
                            <div x-show="items['<?php echo $item; ?>'] && items['<?php echo $item; ?>'].checked" x-transition @click.stop>
                                <input type="text" 
                                       @input.debounce.500ms="updateValue('อื่นๆ', $event.target.value)"
                                       :value="items['<?php echo $item; ?>'] ? items['<?php echo $item; ?>'].value : ''"
                                       placeholder="ระบุรายละเอียด..."
                                       class="text-sm w-full mt-1 border-gray-200 rounded-md shadow-sm">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <p x-show="errorMessage" x-text="errorMessage" class="text-xs text-red-500 mt-2"></p>
        </div>
        <?php endif; ?>

        <!-- Comments History Card -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="font-semibold text-lg mb-4 text-gray-800">ประวัติการดำเนินการ</h3>
            <div class="space-y-4">
                <?php if (!empty($comments)): ?>
                    <?php foreach ($comments as $comment): ?>
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                             <img class="h-10 w-10 rounded-full object-cover" src="<?php echo htmlspecialchars(get_user_avatar($comment['image_url'])); ?>" alt="Profile image of <?php echo htmlspecialchars($comment['fullname']); ?>">
                        </div>
                        <div>
                            <p><strong><?php echo htmlspecialchars($comment['fullname']); ?></strong> 
                               <span class="text-xs text-gray-500"><?php echo formatDate($comment['created_at']); ?></span>
                            </p>
                            <div class="text-gray-700 bg-gray-100 p-3 rounded-lg mt-1">
                                <p><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                                
                                <?php if (!empty($comment['files'])): ?>
                                <div class="mt-2 pt-2 border-t border-gray-200">
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
                                <div class="mt-2 pt-2 border-t border-gray-200">
                                    <p class="text-xs font-semibold mb-1">ลิงก์:</p>
                                    <a href="<?php echo htmlspecialchars($comment['attachment_link']); ?>" target="_blank" class="text-xs text-indigo-600 hover:underline flex items-center">
                                        <i class="fa-solid fa-link mr-1"></i><?php echo htmlspecialchars($comment['attachment_link']); ?>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500">ยังไม่มีการดำเนินการ</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Right Column -->
    <div class="lg:sticky top-24 space-y-6 self-start">
        <!-- Status Card -->
        <div class="bg-white rounded-lg shadow-md p-6">
             <!-- Display Mode -->
            <div x-show="!isEditingReporter" class="space-y-4">
                <div>
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold text-gray-800">ผู้แจ้ง</h3>
                        <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'it' || $_SESSION['role'] === 'admin') && $issue['status'] !== 'done'): ?>
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
            
            <!-- Edit Mode -->
            <form x-show="isEditingReporter" x-transition action="issue_action.php" method="POST" class="space-y-4 bg-white rounded-lg shadow-md p-6">
                <?php echo generate_csrf_token(); ?>
                <input type="hidden" name="issue_id" value="<?php echo $issue_id; ?>">
                <input type="hidden" name="action" value="edit_reporter">

                <h3 class="font-semibold text-gray-800 border-b pb-2">แก้ไขข้อมูลผู้แจ้ง</h3>
                 <div>
                    <label for="form_reporter_name" class="block text-sm font-medium text-gray-700">ชื่อผู้แจ้ง</label>
                    <input type="text" name="reporter_name" id="form_reporter_name" value="<?php echo htmlspecialchars($issue['reporter_name']); ?>" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                </div>
                <div>
                    <label for="form_reporter_contact" class="block text-sm font-medium text-gray-700">ข้อมูลติดต่อ</label>
                    <input type="text" name="reporter_contact" id="form_reporter_contact" value="<?php echo htmlspecialchars($issue['reporter_contact']); ?>" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                </div>
                <div>
                    <label for="form_reporter_position" class="block text-sm font-medium text-gray-700">ตำแหน่ง</label>
                    <input type="text" name="reporter_position" id="form_reporter_position" value="<?php echo htmlspecialchars($issue['reporter_position']); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                </div>
                <div>
                    <label for="form_reporter_department" class="block text-sm font-medium text-gray-700">สังกัด</label>
                    <input type="text" name="reporter_department" id="form_reporter_department" value="<?php echo htmlspecialchars($issue['reporter_department']); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                </div>
                <div>
                    <label for="form_reporter_division" class="block text-sm font-medium text-gray-700">ฝ่าย</label>
                    <input type="text" name="division" id="form_reporter_division" value="<?php echo htmlspecialchars($reporter_division ?? ''); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="isEditingReporter = false" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">ยกเลิก</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">บันทึก</button>
                </div>
            </form>
        </div>
        
        <!-- ... (Work Order, Signature, and Satisfaction Cards remain the same) ... -->
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('checklistHandler', (issueId, initialItems) => ({
            issueId: issueId,
            items: initialItems,
            errorMessage: '',
            
            init() {
                const defaultKeys = <?php echo json_encode($default_checklist); ?>;
                defaultKeys.forEach(key => {
                    if (!this.items[key]) {
                        this.items[key] = { checked: false, value: '' };
                    }
                });
            },
            toggleCheck(itemDescription) {
                this.items[itemDescription].checked = !this.items[itemDescription].checked;
                this.updateChecklist(itemDescription, this.items[itemDescription].checked, null);
            },
            updateValue(itemDescription, value) {
                this.items[itemDescription].value = value;
                this.updateChecklist(itemDescription, null, value);
            },
            updateChecklist(itemDescription, isChecked, itemValue) {
                fetch('issue_checklist_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        issue_id: this.issueId,
                        item_description: itemDescription,
                        is_checked: isChecked,
                        item_value: itemValue
                    })
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (!data.success) {
                        this.errorMessage = 'เกิดข้อผิดพลาดในการบันทึก: ' + (data.message || 'ไม่ทราบสาเหตุ');
                        if (isChecked !== null) this.items[itemDescription].checked = !isChecked;
                    } else {
                        this.errorMessage = '';
                    }
                })
                .catch(error => {
                    this.errorMessage = 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้';
                    if (isChecked !== null) this.items[itemDescription].checked = !isChecked;
                });
            }
        }));
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<?php 
$conn->close();
require_once 'includes/footer.php'; 
?>


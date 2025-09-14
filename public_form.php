<?php
$page_title = "แจ้งปัญหา / ขอคำปรึกษา";
require_once 'includes/db.php'; // เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล

// ดึงข้อมูลหมวดหมู่จากฐานข้อมูล
$categories_result = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY id ASC");
$categories = [];
if ($categories_result) {
    while($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - อบจ.ศรีสะเกษ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 font-sans">
    <main class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <!-- Category Selection Page -->
            <div id="category-selection-page">
                <div class="text-center">
                    <h1 class="text-3xl font-bold text-gray-900">แจ้งปัญหา / ขอคำปรึกษา</h1>
                    <p class="mt-2 text-gray-600">กรุณาเลือกหมวดหมู่ของปัญหาที่ต้องการแจ้ง</p>
                </div>
                <div id="category-cards" class="grid grid-cols-2 md:grid-cols-3 gap-6 mt-8">
                    <?php foreach ($categories as $cat): ?>
                    <button data-category="<?php echo htmlspecialchars($cat['name']); ?>" data-icon="<?php echo htmlspecialchars($cat['icon']); ?>" class="category-card text-center p-6 bg-white rounded-xl shadow-md hover:shadow-lg hover:bg-indigo-50 transition-all transform hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fa-solid <?php echo $cat['icon']; ?> text-4xl text-indigo-500"></i>
                        <h3 class="mt-4 font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($cat['name']); ?></h3>
                        <p class="mt-1 text-sm text-gray-500"><?php echo htmlspecialchars($cat['description']); ?></p>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Issue Form Page (Initially hidden) -->
            <div id="issue-form-page" style="display: none;">
                 <div class="text-center">
                    <h1 class="text-3xl font-bold text-gray-900">กรอกรายละเอียดปัญหา</h1>
                </div>
                <div class="max-w-2xl mx-auto mt-8">
                    <form id="new-issue-form" action="submit_issue.php" method="POST" enctype="multipart/form-data" class="bg-white p-8 rounded-xl shadow-md space-y-6" x-data="{ showExtraInfo: false }">
                        <div id="selected-category-display" class="border-b pb-4"></div>
                        <input type="hidden" id="issue-category-hidden" name="category" value="">
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label for="reporter-name" class="block text-sm font-medium text-gray-700">ชื่อผู้แจ้ง</label>
                                <input type="text" id="reporter-name" name="reporter_name" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label for="reporter-contact" class="block text-sm font-medium text-gray-700">ติดต่อ (เบอร์โทร/ไลน์/อีเมล)</label>
                                <input type="text" id="reporter-contact" name="reporter_contact" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="button" @click="showExtraInfo = !showExtraInfo" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                <span x-show="!showExtraInfo"><i class="fa-solid fa-plus-circle mr-1"></i> เพิ่มข้อมูลพื้นฐาน (ตำแหน่ง/สังกัด)</span>
                                <span x-show="showExtraInfo"><i class="fa-solid fa-minus-circle mr-1"></i> ซ่อนข้อมูลพื้นฐาน</span>
                            </button>
                        </div>

                        <div x-show="showExtraInfo" x-transition class="grid grid-cols-1 sm:grid-cols-2 gap-6 border-t pt-6">
                            <div>
                                <label for="reporter-position" class="block text-sm font-medium text-gray-700">ตำแหน่ง</label>
                                <input type="text" id="reporter-position" name="reporter_position" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label for="reporter-department" class="block text-sm font-medium text-gray-700">สังกัด (กอง/สำนัก)</label>
                                <input type="text" id="reporter-department" name="reporter_department" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>

                        <div>
                            <label for="issue-title" class="block text-sm font-medium text-gray-700">หัวข้อปัญหา</label>
                            <input type="text" id="issue-title" name="title" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label for="issue-description" class="block text-sm font-medium text-gray-700">รายละเอียด</label>
                            <textarea id="issue-description" name="description" rows="4" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        </div>
                        <div>
                            <label for="issue-urgency" class="block text-sm font-medium text-gray-700">ความเร่งด่วน</label>
                            <select id="issue-urgency" name="urgency" class="mt-1 block w-full pl-3 pr-10 py-2.5 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option>สามารถรอได้</option><option>ปกติ</option><option>ด่วนมาก</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">แนบไฟล์ (ถ้ามี)</label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                <div class="space-y-1 text-center">
                                    <i class="fa-solid fa-cloud-arrow-up text-4xl text-gray-400"></i>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="issue-files" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none">
                                            <span>อัปโหลดไฟล์ (เลือกได้หลายไฟล์)</span>
                                            <input id="issue-files" name="issue_files[]" type="file" class="sr-only" multiple>
                                        </label>
                                        <p class="pl-1">หรือลากมาวาง</p>
                                    </div>
                                    <p class="text-xs text-gray-500">รองรับไฟล์รูปภาพ, เอกสาร, PDF, ZIP</p>
                                </div>
                            </div>
                            <div id="file-list-display" class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3"></div>
                        </div>
                        <div class="flex justify-end space-x-3 pt-4">
                            <button type="button" id="back-to-category-btn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">ย้อนกลับ</button>
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700">ส่งเรื่อง</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="text-center mt-8">
            <a href="index.php" class="text-sm text-gray-600 hover:text-indigo-600">กลับหน้าแรก</a>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const categorySelectionPage = document.getElementById('category-selection-page');
            const issueFormPage = document.getElementById('issue-form-page');
            const categoryCards = document.querySelectorAll('.category-card');
            const backBtn = document.getElementById('back-to-category-btn');

            categoryCards.forEach(card => {
                card.addEventListener('click', function() {
                    const category = this.dataset.category;
                    const icon = this.dataset.icon;
                    
                    document.getElementById('issue-category-hidden').value = category;
                    document.getElementById('selected-category-display').innerHTML = `
                        <div class="flex items-center">
                            <i class="fa-solid ${icon} text-2xl text-indigo-600 mr-4"></i>
                            <div>
                                <label class="text-sm font-medium text-gray-500">หมวดหมู่ที่เลือก</label>
                                <p class="font-bold text-xl text-gray-800">${category}</p>
                            </div>
                        </div>`;
                    
                    categorySelectionPage.style.display = 'none';
                    issueFormPage.style.display = 'block';
                });
            });

            backBtn.addEventListener('click', function() {
                issueFormPage.style.display = 'none';
                categorySelectionPage.style.display = 'block';
            });
            
            // File upload preview
            const fileInput = document.getElementById('issue-files');
            const fileListDisplay = document.getElementById('file-list-display');
            let selectedFiles = [];

            fileInput.addEventListener('change', (e) => {
                for (const file of e.target.files) {
                    if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                        selectedFiles.push(file);
                    }
                }
                renderFileList();
            });

            function renderFileList() {
                fileListDisplay.innerHTML = selectedFiles.map((file, index) => `
                    <div class="flex items-center justify-between bg-gray-50 border p-2 rounded-lg text-sm">
                        <div class="flex items-center truncate">
                            <i class="fa-solid fa-file text-2xl mr-3 w-6 text-center text-gray-400"></i>
                            <div class="truncate">
                                <span class="font-medium text-gray-800 truncate block">${file.name}</span>
                                <span class="text-gray-500 text-xs">${(file.size / 1024).toFixed(1)} KB</span>
                            </div>
                        </div>
                        <button type="button" data-index="${index}" class="remove-file-btn text-red-500 hover:text-red-700 ml-2 flex-shrink-0">
                            <i class="fa-solid fa-times-circle"></i>
                        </button>
                    </div>`).join('');
            }
            
            fileListDisplay.addEventListener('click', function(e) {
                if(e.target.closest('.remove-file-btn')) {
                    const indexToRemove = parseInt(e.target.closest('.remove-file-btn').dataset.index);
                    selectedFiles.splice(indexToRemove, 1);
                    
                    // A bit of a hack to update the file input's internal list
                    const dt = new DataTransfer();
                    selectedFiles.forEach(file => dt.items.add(file));
                    fileInput.files = dt.files;
                    
                    renderFileList();
                }
            });
        });
    </script>
</body>
</html>


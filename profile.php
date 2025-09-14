<?php
$page_title = "โปรไฟล์ของฉัน";
require_once 'includes/functions.php';
check_auth(['user', 'it', 'admin']); // ผู้ใช้ทุกประเภทที่ล็อกอินสามารถเข้าถึงได้
require_once 'includes/header.php';

// ดึงข้อมูลผู้ใช้ปัจจุบันจาก Session ID
$current_user_id = $_SESSION['user_id'];
$user_data = getUserById($current_user_id, $conn);

// --- Fetch Statistics for IT/Admin roles ---
$stats = null;
if ($user_data['role'] === 'it' || $user_data['role'] === 'admin') {
    // Total completed issues
    $stmt_total = $conn->prepare("SELECT COUNT(id) as total FROM issues WHERE assigned_to = ? AND status = 'done'");
    $stmt_total->bind_param("i", $current_user_id);
    $stmt_total->execute();
    $total_completed = $stmt_total->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt_total->close();

    // Average satisfaction rating
    $stmt_avg = $conn->prepare("SELECT AVG(satisfaction_rating) as avg_rating FROM issues WHERE assigned_to = ? AND satisfaction_rating IS NOT NULL");
    $stmt_avg->bind_param("i", $current_user_id);
    $stmt_avg->execute();
    $avg_rating = $stmt_avg->get_result()->fetch_assoc()['avg_rating'] ?? 0;
    $stmt_avg->close();
    
    // Issues by Category (for Doughnut Chart)
    $stmt_cat = $conn->prepare("SELECT category, COUNT(id) as total FROM issues WHERE assigned_to = ? AND status = 'done' GROUP BY category ORDER BY total DESC");
    $stmt_cat->bind_param("i", $current_user_id);
    $stmt_cat->execute();
    $category_result = $stmt_cat->get_result();
    $category_data = [];
    while ($row = $category_result->fetch_assoc()) {
        $category_data[] = $row;
    }
    $stmt_cat->close();
    
    $stats = [
        'total_completed' => $total_completed,
        'avg_rating' => $avg_rating,
        'category_labels_json' => json_encode(array_column($category_data, 'category')),
        'category_values_json' => json_encode(array_column($category_data, 'total')),
    ];
}
?>
<!-- Cropper.js CSS & Chart.js Script -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .img-container { max-height: 50vh; }
    #image-to-crop { max-width: 100%; }
</style>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
    <!-- Left Column: Profile Edit Form -->
    <div class="lg:col-span-3">
        <form action="profile_action.php" method="POST" class="bg-white p-8 rounded-xl shadow-md space-y-6">
            <?php echo generate_csrf_token(); ?>
            <input type="hidden" name="cropped_image_data" id="cropped_image_data">
            
            <div class="text-center">
                <div class="relative w-24 h-24 mx-auto">
                    <img id="image_preview" src="<?php echo htmlspecialchars(get_user_avatar($user_data['image_url'])); ?>" class="w-24 h-24 rounded-full object-cover border-2 border-gray-200">
                    <label for="profile_image_input" class="absolute -bottom-2 -right-2 bg-indigo-600 text-white rounded-full p-2 cursor-pointer hover:bg-indigo-700 transition" title="เปลี่ยนรูปโปรไฟล์">
                        <i class="fa-solid fa-camera"></i>
                        <input type="file" id="profile_image_input" class="hidden" accept="image/png, image/jpeg, image/gif">
                    </label>
                </div>
                <h2 class="mt-4 text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($user_data['fullname']); ?></h2>
                <p class="text-gray-500"><?php echo htmlspecialchars($user_data['email']); ?></p>
            </div>

            <div class="border-t pt-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="fullname" class="block text-sm font-medium text-gray-700">ชื่อ-สกุล</label>
                    <input type="text" name="fullname" id="fullname" value="<?php echo htmlspecialchars($user_data['fullname']); ?>" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                </div>
                 <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">เบอร์โทรศัพท์</label>
                    <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($user_data['phone']); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                </div>
                 <div>
                    <label for="position" class="block text-sm font-medium text-gray-700">ตำแหน่ง</label>
                    <input type="text" name="position" id="position" value="<?php echo htmlspecialchars($user_data['position']); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                </div>
                <div>
                    <label for="department" class="block text-sm font-medium text-gray-700">สังกัด (กอง/สำนัก)</label>
                    <input type="text" name="department" id="department" value="<?php echo htmlspecialchars($user_data['department']); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                </div>
                <div>
                    <label for="division" class="block text-sm font-medium text-gray-700">ฝ่าย</label>
                    <input type="text" name="division" id="division" value="<?php echo htmlspecialchars($user_data['division'] ?? ''); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                </div>
                 <div>
                    <label for="line_id" class="block text-sm font-medium text-gray-700">Line ID</label>
                    <input type="text" name="line_id" id="line_id" value="<?php echo htmlspecialchars($user_data['line_id']); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                </div>
            </div>

            <div class="border-t pt-6">
                <h3 class="text-lg font-semibold text-gray-700">เปลี่ยนรหัสผ่าน</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-4">
                     <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700">รหัสผ่านใหม่</label>
                        <input type="password" name="new_password" id="new_password" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                    </div>
                     <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">ยืนยันรหัสผ่านใหม่</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                    </div>
                </div>
                 <p class="mt-2 text-xs text-gray-500">เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน</p>
            </div>


            <div class="flex justify-end pt-4 border-t">
                <button type="submit" name="update_profile" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700">
                    บันทึกการเปลี่ยนแปลง
                </button>
            </div>
        </form>
    </div>
    
    <!-- Right Column: Statistics Card (for IT/Admin only) -->
    <div class="lg:col-span-2">
        <?php if ($stats): ?>
        <div class="bg-white p-8 rounded-xl shadow-md sticky top-24">
            <h3 class="text-xl font-bold text-gray-800 border-b pb-4">สถิติการให้บริการของคุณ</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-6">
                <div class="text-center p-4 border rounded-lg">
                    <p class="text-sm font-medium text-gray-500">แก้ไขเสร็จสิ้นทั้งหมด</p>
                    <p class="text-3xl font-bold text-green-600 mt-1"><?php echo $stats['total_completed']; ?></p>
                    <p class="text-xs text-gray-500">เรื่อง</p>
                </div>
                 <div class="text-center p-4 border rounded-lg">
                    <p class="text-sm font-medium text-gray-500">คะแนนความพึงพอใจเฉลี่ย</p>
                    <p class="text-3xl font-bold text-amber-500 mt-1"><?php echo number_format((float)$stats['avg_rating'], 2); ?></p>
                     <p class="text-xs text-gray-500">จาก 5 คะแนน</p>
                </div>
                <div class="sm:col-span-2 p-4 border rounded-lg">
                     <h4 class="text-sm font-medium text-gray-500 text-center mb-2">สัดส่วนงานตามหมวดหมู่</h4>
                     <div class="h-48">
                        <canvas id="serviceChart"></canvas>
                     </div>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (document.getElementById('serviceChart')) {
                    const serviceCtx = document.getElementById('serviceChart').getContext('2d');
                    new Chart(serviceCtx, {
                        type: 'doughnut',
                        data: {
                            labels: <?php echo $stats['category_labels_json']; ?>,
                            datasets: [{
                                data: <?php echo $stats['category_values_json']; ?>,
                                backgroundColor: ['#4F46E5', '#10B981', '#F59E0B', '#3B82F6', '#EF4444', '#6B7280'],
                                hoverOffset: 4
                            }]
                        },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: {font: { family: 'Sarabun' } } }}}
                    });
                }
            });
        </script>
        <?php endif; ?>
    </div>
</div>

<!-- Cropper Modal -->
<div id="cropper-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-75" style="display: none;">
    <div class="bg-white rounded-lg shadow-2xl max-w-lg w-full">
        <div class="p-4 border-b"><h3 class="font-semibold text-lg">ปรับขนาดและตัดรูปภาพ</h3></div>
        <div class="p-4"><div class="img-container"><img id="image-to-crop" src=""></div></div>
        <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3 rounded-b-lg">
            <button type="button" id="cancel-crop-btn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">ยกเลิก</button>
            <button type="button" id="crop-btn" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">ตัดและบันทึก</button>
        </div>
    </div>
</div>

<!-- Cropper.js Script -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('cropper-modal');
        const imageToCrop = document.getElementById('image-to-crop');
        const fileInput = document.getElementById('profile_image_input');
        const cropBtn = document.getElementById('crop-btn');
        const cancelBtn = document.getElementById('cancel-crop-btn');
        const imagePreview = document.getElementById('image_preview');
        const hiddenInput = document.getElementById('cropped_image_data');
        let cropper;

        fileInput.addEventListener('change', (e) => {
            const files = e.target.files;
            if (files && files.length > 0) {
                const reader = new FileReader();
                reader.onload = () => {
                    imageToCrop.src = reader.result;
                    modal.style.display = 'flex';
                    cropper = new Cropper(imageToCrop, {
                        aspectRatio: 1,
                        viewMode: 1,
                        background: false,
                    });
                };
                reader.readAsDataURL(files[0]);
            }
        });

        cropBtn.addEventListener('click', () => {
            if (cropper) {
                const canvas = cropper.getCroppedCanvas({
                    width: 200,
                    height: 200,
                });
                const croppedDataUrl = canvas.toDataURL('image/png');
                imagePreview.src = croppedDataUrl;
                hiddenInput.value = croppedDataUrl;
                closeModal();
            }
        });

        cancelBtn.addEventListener('click', closeModal);

        function closeModal() {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            modal.style.display = 'none';
            fileInput.value = ''; // Reset file input to allow re-selection of the same file
        }
    });
</script>

<?php 
$conn->close();
require_once 'includes/footer.php'; 
?>
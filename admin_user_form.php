<?php
require_once 'includes/functions.php';
check_auth(['admin']);

$user_id_to_edit = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_editing = $user_id_to_edit > 0;
$form_user_data = [
    'fullname' => '',
    'position' => '',
    'department' => '',
    'division' => '',
    'phone' => '',
    'line_id' => '',
    'email' => '',
    'role' => 'user',
    'image_url' => ''
];

if ($is_editing) {
    $form_user_data = getUserById($user_id_to_edit, $conn);
    if (!$form_user_data) {
        redirect_with_message('admin_users.php', 'error', 'ไม่พบผู้ใช้งาน');
    }
}

$page_title = $is_editing ? "แก้ไขข้อมูลผู้ใช้: " . htmlspecialchars($form_user_data['fullname']) : "เพิ่มผู้ใช้งานใหม่";
require_once 'includes/header.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
<style>
    .img-container { max-height: 50vh; }
    #image-to-crop { max-width: 100%; }
</style>

<div class="max-w-2xl mx-auto">
    <form action="admin_user_action.php" method="POST" enctype="multipart/form-data" class="bg-white p-8 rounded-xl shadow-md space-y-6" x-data="passwordConfirmation()">
        <?php echo generate_csrf_token(); ?>
        <input type="hidden" name="user_id" value="<?php echo $user_id_to_edit; ?>">
        <input type="hidden" name="action" value="<?php echo $is_editing ? 'edit_user' : 'add_user'; ?>">
        <input type="hidden" name="cropped_image_data" id="cropped_image_data">

        <div>
            <label class="block text-sm font-medium text-gray-700">รูปภาพโปรไฟล์</label>
            <div class="mt-1 flex items-center space-x-4">
                <img id="image_preview" src="<?php echo htmlspecialchars(get_user_avatar($form_user_data['image_url'])); ?>" class="h-16 w-16 rounded-full object-cover border">
                <input type="file" id="profile_image_input" class="hidden" accept="image/png, image/jpeg, image/gif">
                <button type="button" onclick="document.getElementById('profile_image_input').click()" class="px-3 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    เลือกรูปภาพ
                </button>
            </div>
        </div>

        <div class="border-t pt-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label for="fullname" class="block text-sm font-medium text-gray-700">ชื่อ-สกุล</label>
                <input type="text" name="fullname" id="fullname" value="<?php echo htmlspecialchars($form_user_data['fullname']); ?>" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">อีเมล</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($form_user_data['email']); ?>" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
            </div>
             <div>
                <label for="position" class="block text-sm font-medium text-gray-700">ตำแหน่ง</label>
                <input type="text" name="position" id="position" value="<?php echo htmlspecialchars($form_user_data['position']); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
            </div>
            <div>
                <label for="department" class="block text-sm font-medium text-gray-700">สังกัด (กอง/สำนัก)</label>
                <input type="text" name="department" id="department" value="<?php echo htmlspecialchars($form_user_data['department']); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
            </div>
             <div>
                <label for="division" class="block text-sm font-medium text-gray-700">ฝ่าย</label>
                <input type="text" name="division" id="division" value="<?php echo htmlspecialchars($form_user_data['division'] ?? ''); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
            </div>
             <div>
                <label for="line_id" class="block text-sm font-medium text-gray-700">Line ID</label>
                <input type="text" name="line_id" id="line_id" value="<?php echo htmlspecialchars($form_user_data['line_id']); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
            </div>
            <div class="sm:col-span-2">
                <label for="phone" class="block text-sm font-medium text-gray-700">เบอร์โทรศัพท์</label>
                <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($form_user_data['phone']); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
            </div>
        </div>

        <div>
            <label for="role" class="block text-sm font-medium text-gray-700">สิทธิ์การใช้งาน</label>
            <select name="role" id="role" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                <option value="user" <?php echo ($form_user_data['role'] === 'user') ? 'selected' : ''; ?>>ผู้ใช้งานทั่วไป</option>
                <option value="it" <?php echo ($form_user_data['role'] === 'it') ? 'selected' : ''; ?>>เจ้าหน้าที่ IT</option>
                <option value="admin" <?php echo ($form_user_data['role'] === 'admin') ? 'selected' : ''; ?>>ผู้ดูแลระบบ</option>
            </select>
        </div>

        <div class="border-t pt-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">รหัสผ่าน</label>
                <input type="password" name="password" id="password" x-model="password" @input="validatePassword" <?php echo !$is_editing ? 'required' : ''; ?> class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                <?php if ($is_editing): ?>
                <p class="mt-1 text-xs text-gray-500">เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน</p>
                <?php endif; ?>
            </div>
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700">ยืนยันรหัสผ่าน</label>
                <input type="password" name="confirm_password" id="confirm_password" x-model="confirmPassword" @input="validatePassword" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                <p x-show="!passwordsMatch && confirmPassword" class="mt-1 text-xs text-red-500">รหัสผ่านไม่ตรงกัน</p>
            </div>
        </div>


        <div class="flex justify-end space-x-3 pt-4 border-t">
            <a href="admin_users.php" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">ยกเลิก</a>
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 disabled:bg-gray-400" :disabled="!passwordsMatch">
                <?php echo $is_editing ? 'บันทึกการเปลี่ยนแปลง' : 'เพิ่มผู้ใช้งาน'; ?>
            </button>
        </div>
    </form>
</div>

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

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // AlpineJS component for password confirmation
    window.passwordConfirmation = function() {
        return {
            password: '',
            confirmPassword: '',
            passwordsMatch: true,
            validatePassword() {
                if (this.password || this.confirmPassword) {
                    this.passwordsMatch = this.password === this.confirmPassword;
                } else {
                    this.passwordsMatch = true;
                }
            }
        }
    }

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
        fileInput.value = ''; // Reset file input
    }
});
</script>

<?php 
$conn->close();
require_once 'includes/footer.php'; 
?>
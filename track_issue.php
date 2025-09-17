<?php
$page_title = "ติดตามสถานะเรื่อง";
require_once 'includes/functions.php'; // ใช้ functions.php เพื่อดึงข้อมูลต่างๆ

$issue_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$issue = null;
$issue_files = [];
$comments = [];

if ($issue_id > 0) {
    // ดึงข้อมูลปัญหาหลัก
    $stmt = $conn->prepare("SELECT * FROM issues WHERE id = ?");
    $stmt->bind_param("i", $issue_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $issue = $result->fetch_assoc();
    $stmt->close();

    if ($issue) {
        $issue_files = getIssueFiles($issue_id, $conn);
        $comments = getIssueComments($issue_id, $conn);
    }
}
$status_text_map = [
    'pending' => 'รอตรวจสอบ',
    'in_progress' => 'กำลังดำเนินการ',
    'done' => 'เสร็จสิ้น',
    'cannot_resolve' => 'แก้ไขไม่ได้'
];
$status_color_map = [
    'pending' => 'bg-yellow-100 text-yellow-800',
    'in_progress' => 'bg-blue-100 text-blue-800',
    'done' => 'bg-green-100 text-green-800',
    'cannot_resolve' => 'bg-red-100 text-red-800'
];
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
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
</head>
<body class="bg-gray-100 font-sans">
    <main class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h1 class="text-2xl font-bold text-center text-gray-800">ติดตามสถานะเรื่อง</h1>
                <form action="track_issue.php" method="GET" class="mt-4 flex">
                    <input type="number" name="id" placeholder="กรอกหมายเลขเรื่อง..." value="<?php echo $issue_id > 0 ? $issue_id : ''; ?>" class="flex-grow border border-gray-300 rounded-l-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" required>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-r-md hover:bg-indigo-700">
                        <i class="fa-solid fa-search"></i>
                    </button>
                </form>
                <a href="index.php" class="text-sm text-center block mt-4 text-gray-600 hover:text-indigo-600">กลับหน้าแรก</a>
            </div>

            <?php if ($issue_id > 0): ?>
                <?php if ($issue): ?>
                    <!-- แสดงผลข้อมูล -->
                    <div class="mt-6 space-y-6">
                        <!-- Issue Details -->
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h2 class="text-xl font-bold text-gray-800">เรื่อง: <?php echo htmlspecialchars($issue['title']); ?></h2>
                                    <p class="text-sm text-gray-500">แจ้งเมื่อ: <?php echo formatDate($issue['created_at']); ?></p>
                                </div>
                                <div class="text-right">
                                     <span class="mt-1 inline-block px-2 py-1 text-sm leading-5 font-semibold rounded-full <?php echo $status_color_map[$issue['status']] ?? ''; ?>">
                                        <?php echo $status_text_map[$issue['status']] ?? htmlspecialchars($issue['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <p class="mt-4 text-gray-600 border-t pt-4"><?php echo nl2br(htmlspecialchars($issue['description'])); ?></p>
                        </div>
                        
                        <!-- History -->
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
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-gray-500">ยังไม่มีการดำเนินการ</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Close Job Section: Show if job is 'done' and not yet signed -->
                        <?php if ($issue['status'] === 'done' && is_null($issue['signature_image'])): ?>
                        <form action="track_issue_action.php" method="POST" class="space-y-6" x-data="{ satisfaction: null }">
                            <?php echo generate_csrf_token(); ?>
                            <input type="hidden" name="issue_id" value="<?php echo $issue_id; ?>">
                            <input type="hidden" name="signature_data" id="signature_data">

                            <!-- Signature Card -->
                            <div class="bg-white rounded-lg shadow-md p-6">
                                <h3 class="font-semibold text-gray-800 mb-2">ลงลายมือชื่อเพื่อปิดงาน</h3>
                                <div class="border rounded-md bg-white">
                                    <canvas id="signature-pad" class="w-full h-40"></canvas>
                                </div>
                                <div class="text-center mt-2">
                                    <button type="button" id="clear-signature" class="text-sm text-gray-500 hover:text-red-600">ล้างลายเซ็น</button>
                                </div>
                            </div>
                            
                            <!-- Satisfaction Card -->
                            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                                <h3 class="font-semibold text-gray-800">ประเมินความพึงพอใจ</h3>
                                <div class="flex space-x-4 mt-2 text-3xl justify-center">
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
                        <?php elseif ($issue['status'] === 'done' && !is_null($issue['signature_image'])): ?>
                            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                                <i class="fa-solid fa-circle-check text-green-500 text-3xl"></i>
                                <p class="mt-2 font-semibold">ท่านได้ยืนยันการปิดงานและประเมินความพึงพอใจเรียบร้อยแล้ว</p>
                                <p class="text-sm text-gray-500">ขอขอบคุณที่ใช้บริการ</p>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php else: ?>
                    <!-- ไม่พบข้อมูล -->
                     <div class="mt-6 bg-white rounded-lg shadow-md p-8 text-center">
                        <i class="fa-solid fa-file-circle-question text-4xl text-red-400"></i>
                        <h2 class="mt-4 font-semibold text-red-600">ไม่พบข้อมูล</h2>
                        <p class="text-gray-500">ไม่พบข้อมูลสำหรับหมายเลขเรื่อง #<?php echo $issue_id; ?></p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const canvas = document.getElementById('signature-pad');
            if (canvas) {
                const signaturePad = new SignaturePad(canvas, {
                    backgroundColor: 'rgb(255, 255, 255)'
                });

                document.getElementById('clear-signature').addEventListener('click', function () {
                    signaturePad.clear();
                });

                const form = canvas.closest('form');
                form.addEventListener('submit', function (event) {
                    if (signaturePad.isEmpty()) {
                        alert("กรุณาลงลายมือชื่อเพื่อยืนยันการปิดงาน");
                        event.preventDefault();
                        return;
                    }
                    const signatureDataInput = document.getElementById('signature_data');
                    signatureDataInput.value = signaturePad.toDataURL('image/png');
                });
            }
        });
    </script>
</body>
</html>


<?php
// ==============================
// بخش امنیتی و اتصال به دیتابیس
// ==============================
session_start();
// بررسی ورود مدیر - در واقعیت باید مجوزها چک شوند
if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_username'])) {
    header('Location: loginAdmin.php');
    exit;
}

// اتصال به دیتابیس
$host = 'localhost';
$user = 'maskanhb_maskansazan';
$pass = 'NJ)B*$@y3R9P0Oyg';
$dbname = 'maskanhb_maskansazan';
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// دریافت شناسه تیکت از آدرس
$ticket_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ==============================
// دریافت اطلاعات تیکت از دیتابیس
// ==============================
$ticket = null;
$messages = [];

if ($ticket_id > 0) {
    // دریافت اطلاعات اصلی تیکت - این بخش اصلاح شد
    $sql = "SELECT t.*, CONCAT(u.first_name, ' ', u.last_name) as fullname 
            FROM tickets t
            LEFT JOIN project_members u ON t.user_id = u.id  -- فرض: user_id در tickets
            WHERE t.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ticket = $result->fetch_assoc();
    $stmt->close();

    // دریافت مکالمات تیکت
    if ($ticket) {
        $sql = "SELECT tm.*, 
                       CASE 
                           WHEN tm.sender_type = 'admin' THEN CONCAT('مدیر #', tm.sender_id)
                           ELSE 'کاربر'
                       END as sender_name
                FROM ticket_messages tm
                WHERE tm.ticket_id = ? 
                ORDER BY tm.created_at ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $ticket_id);
        $stmt->execute();
        $messages_result = $stmt->get_result();
        while ($row = $messages_result->fetch_assoc()) {
            $messages[] = $row;
        }
        $stmt->close();
    }
}

// اگر تیکت یافت نشد
if (!$ticket) {
    echo "<div class='alert alert-danger m-4'>تیکت مورد نظر یافت نشد یا حذف شده است.</div>";
    echo "<a href='tickets.php' class='btn btn-primary m-4'>بازگشت به لیست تیکت‌ها</a>";
    exit();
}

// ==============================
// پردازش ارسال پاسخ جدید
// ==============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reply_message'])) {
        $admin_id = $_SESSION['admin_id'];
        $message = trim($_POST['reply_message']);
        
        if (!empty($message)) {
            // ذخیره پاسخ در دیتابیس
            $sql = "INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message, created_at) 
                    VALUES (?, 'admin', ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $ticket_id, $admin_id, $message);
            $stmt->execute();
            $stmt->close();
            
            // بروزرسانی وضعیت تیکت به "پاسخ داده شده"
            $sql = "UPDATE tickets SET status = 'answered', updated_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $ticket_id);
            $stmt->execute();
            $stmt->close();
            
            // ریدایرکت برای جلوگیری از ارسال مجدد فرم
            header("Location: ticketDetails.php?id=" . $ticket_id);
            exit();
        }
    }
    
    // پردازش تغییر وضعیت تیکت
    if (isset($_POST['change_status'])) {
        $new_status = $_POST['status'];
        $sql = "UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_status, $ticket_id);
        $stmt->execute();
        $stmt->close();
        
        header("Location: ticketDetails.php?id=" . $ticket_id);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جزئیات تیکت #<?php echo $ticket_id; ?> - سیستم مدیریت</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f5f5f5; font-family: 'Vazir', Tahoma, sans-serif; }
        .header-info { background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%); }
        .user-message { background-color: #f0f8ff; border-right: 4px solid #0d6efd; margin-left: 20%; }
        .admin-message { background-color: #f0fff0; border-right: 4px solid #198754; margin-right: 20%; }
        .message-time { font-size: 0.85rem; color: #6c757d; }
        .badge-priority { font-size: 0.8rem; }
        .chat-container { max-height: 500px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- هدر و اطلاعات تیکت -->
        <div class="card shadow mb-4">
            <div class="card-header header-info text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="bi bi-ticket-detailed me-2"></i>
                    جزئیات تیکت #<?php echo $ticket['id']; ?>
                </h4>
                <div>
                    <a href="tickets.php" class="btn btn-light btn-sm me-2">
                        <i class="bi bi-arrow-right me-1"></i> بازگشت به لیست
                    </a>
                    <!-- فرم تغییر وضعیت -->
                    <form method="POST" action="" class="d-inline">
                        <div class="input-group input-group-sm" style="width: 200px;">
                            <select name="status" class="form-select form-select-sm">
                                <option value="open" <?php echo ($ticket['status'] == 'open') ? 'selected' : ''; ?>>باز</option>
                                <option value="answered" <?php echo ($ticket['status'] == 'answered') ? 'selected' : ''; ?>>پاسخ داده شده</option>
                                <option value="closed" <?php echo ($ticket['status'] == 'closed') ? 'selected' : ''; ?>>بسته</option>
                            </select>
                            <button type="submit" name="change_status" class="btn btn-warning btn-sm">
                                <i class="bi bi-check-circle"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong><i class="bi bi-person me-2"></i> کاربر:</strong> 
                            <?php echo htmlspecialchars($ticket['fullname'] ?? 'نامشخص'); ?>
                        </p>
                        <p><strong><i class="bi bi-envelope me-2"></i> موضوع:</strong> 
                            <?php echo htmlspecialchars($ticket['subject'] ?? 'بدون موضوع'); ?>
                        </p>
                        <p><strong><i class="bi bi-calendar me-2"></i> تاریخ ایجاد:</strong> 
                            <?php echo date('Y/m/d H:i', strtotime($ticket['created_at'])); ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong><i class="bi bi-flag me-2"></i> اولویت:</strong> 
                            <?php
                            $priority_class = [
                                'low' => 'success',
                                'medium' => 'warning',
                                'high' => 'danger',
                                'کم' => 'success',
                                'متوسط' => 'warning',
                                'بالا' => 'danger'
                            ];
                            $priority_text = [
                                'low' => 'کم',
                                'medium' => 'متوسط',
                                'high' => 'بالا'
                            ];
                            $p = $ticket['priority'] ?? 'medium';
                            ?>
                            <span class="badge bg-<?php echo $priority_class[$p] ?? 'warning'; ?> badge-priority">
                                <?php echo $priority_text[$p] ?? $p; ?>
                            </span>
                        </p>
                        <p><strong><i class="bi bi-circle-fill me-2"></i> وضعیت:</strong> 
                            <?php
                            $status_class = [
                                'open' => 'success',
                                'answered' => 'info',
                                'closed' => 'secondary',
                                'باز' => 'success',
                                'پاسخ داده شده' => 'info',
                                'بسته' => 'secondary'
                            ];
                            $status_text = [
                                'open' => 'باز',
                                'answered' => 'پاسخ داده شده',
                                'closed' => 'بسته'
                            ];
                            $s = $ticket['status'] ?? 'open';
                            ?>
                            <span class="badge bg-<?php echo $status_class[$s] ?? 'success'; ?>">
                                <?php echo $status_text[$s] ?? $s; ?>
                            </span>
                        </p>
                        <p><strong><i class="bi bi-tags me-2"></i> دسته‌بندی:</strong> 
                            <?php echo htmlspecialchars($ticket['category'] ?? 'عمومی'); ?>
                        </p>
                    </div>
                </div>
                <?php if (!empty($ticket['description'])): ?>
                <div class="mt-3 p-3 bg-light rounded">
                    <strong><i class="bi bi-chat-text me-2"></i>شرح تیکت:</strong>
                    <p class="mb-0 mt-2"><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- بخش مکالمات -->
        <div class="card shadow mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>مکالمات</h5>
                <span class="badge bg-primary"><?php echo count($messages); ?> پیام</span>
            </div>
            <div class="card-body chat-container">
                <?php if (empty($messages)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-chat-dots display-4 text-muted"></i>
                        <p class="mt-3">هنوز هیچ مکالمه‌ای ثبت نشده است.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="mb-3 p-3 rounded <?php echo $msg['sender_type'] === 'user' ? 'user-message' : 'admin-message'; ?>">
                            <div class="d-flex justify-content-between mb-2">
                                <strong>
                                    <?php if ($msg['sender_type'] === 'user'): ?>
                                        <i class="bi bi-person-circle text-primary me-1"></i> 
                                        <?php echo htmlspecialchars($msg['sender_name'] ?? 'کاربر'); ?>
                                    <?php else: ?>
                                        <i class="bi bi-shield-check text-success me-1"></i> 
                                        <?php echo htmlspecialchars($msg['sender_name'] ?? 'مدیر'); ?>
                                    <?php endif; ?>
                                </strong>
                                <span class="message-time">
                                    <i class="bi bi-clock me-1"></i>
                                    <?php echo date('H:i Y/m/d', strtotime($msg['created_at'])); ?>
                                </span>
                            </div>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- فرم پاسخ‌گویی -->
        <div class="card shadow">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-reply me-2"></i>ارسال پاسخ
                    <?php if ($ticket['status'] == 'closed'): ?>
                        <span class="badge bg-secondary ms-2">تیکت بسته است</span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if ($ticket['status'] != 'closed'): ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="reply_message" class="form-label">متن پاسخ:</label>
                        <textarea class="form-control" id="reply_message" name="reply_message" 
                                  rows="5" placeholder="متن پاسخ خود را وارد کنید..." required></textarea>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            پاسخ شما به نام <strong><?php echo $_SESSION['admin_username']; ?></strong> ثبت خواهد شد.
                        </small>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-2"></i> ارسال پاسخ
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    این تیکت بسته شده است. برای پاسخ‌گویی ابتدا وضعیت آن را به "باز" یا "پاسخ داده شده" تغییر دهید.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- اسکریپت‌ها -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // اسکرول به پایین چت
        document.addEventListener('DOMContentLoaded', function() {
            const chatContainer = document.querySelector('.chat-container');
            if (chatContainer) {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
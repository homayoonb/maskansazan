<?php
// ==============================
// بخش امنیتی و اتصال به دیتابیس
// ==============================
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// بررسی ورود مدیر
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
    die("اتصال به دیتابیس ناموفق: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// دریافت شناسه تیکت
$ticket_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ==============================
// دریافت اطلاعات تیکت از دیتابیس
// ==============================
$ticket = null;
$messages = [];

if ($ticket_id > 0) {
    // دریافت اطلاعات تیکت با توجه به ساختار واقعی
    $sql = "SELECT t.*, 
                   p.name as project_name,
                   t.user_name  -- از فیلد user_name خود جدول tickets استفاده می‌کنیم
            FROM tickets t 
            LEFT JOIN projects p ON t.project_id = p.id 
            WHERE t.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ticket_id);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $ticket = $result->fetch_assoc();
            
            // اگر project_name خالی است
            if (empty($ticket['project_name'])) {
                $ticket['project_name'] = 'بدون پروژه';
            }
        } else {
            $error = "تیکت #{$ticket_id} یافت نشد.";
        }
    } else {
        $error = "خطا در اجرای کوئری: " . $stmt->error;
    }
    
    $stmt->close();
    
    // دریافت مکالمات تیکت
    if ($ticket) {
        // ابتدا ساختار ticket_messages را بررسی می‌کنیم
        $check_sql = "SHOW COLUMNS FROM ticket_messages LIKE 'sender_type'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            // اگر sender_type وجود دارد
            $sql = "SELECT tm.* 
                    FROM ticket_messages tm
                    WHERE tm.ticket_id = ? 
                    ORDER BY tm.created_at ASC";
        } else {
            // اگر sender_type وجود ندارد، همه پیام‌ها را بگیر
            $sql = "SELECT * FROM ticket_messages 
                    WHERE ticket_id = ? 
                    ORDER BY created_at ASC";
        }
        
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
    echo "<div class='alert alert-danger m-4'>" . ($error ?? "تیکت مورد نظر یافت نشد.") . "</div>";
    echo "<a href='dashboardAdmin.php' class='btn btn-primary m-4'>بازگشت به داشبورد</a>";
    exit();
}

// ==============================
// پردازش ارسال پاسخ جدید
// ==============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reply_message'])) {
        $admin_id = $_SESSION['admin_id'];
        $admin_name = $_SESSION['admin_username'];
        $message = trim($_POST['reply_message']);
        
        if (!empty($message)) {
            // بررسی ساختار ticket_messages برای INSERT صحیح
            $columns_sql = "SHOW COLUMNS FROM ticket_messages";
            $cols_result = $conn->query($columns_sql);
            $columns = [];
            while ($col = $cols_result->fetch_assoc()) {
                $columns[] = $col['Field'];
            }
            
            // INSERT بر اساس ساختار واقعی
            if (in_array('sender_type', $columns)) {
                // اگر sender_type وجود دارد
                $sql = "INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message, created_at) 
                        VALUES (?, 'admin', ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iis", $ticket_id, $admin_id, $message);
            } 
            elseif (in_array('sender', $columns)) {
                // اگر sender وجود دارد
                $sql = "INSERT INTO ticket_messages (ticket_id, sender, message, created_at) 
                        VALUES (?, 'admin', ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("is", $ticket_id, $message);
            }
            else {
                // ساختار ساده
                $sql = "INSERT INTO ticket_messages (ticket_id, message, created_at) 
                        VALUES (?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("is", $ticket_id, $message);
            }
            
            if ($stmt->execute()) {
                // بروزرسانی وضعیت تیکت و admin_reply
                $sql = "UPDATE tickets 
                        SET status = 'answered', 
                            admin_reply = ?, 
                            updated_at = NOW() 
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $message, $ticket_id);
                $stmt->execute();
                $stmt->close();
                
                // ریدایرکت
                header("Location: ticketDetails.php?id=" . $ticket_id . "&success=1");
                exit();
            } else {
                $error_message = "خطا در ذخیره پاسخ: " . $conn->error;
            }
        } else {
            $error_message = "لطفا متن پاسخ را وارد کنید.";
        }
    }
    
    // پردازش تغییر وضعیت
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

// نمایش پیام موفقیت
$success = isset($_GET['success']) ? true : false;
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
        .user-message { 
            background-color: #e8f4fd; 
            border-right: 4px solid #0d6efd; 
            margin-right: 15%;
            margin-left: 5%;
        }
        .admin-message { 
            background-color: #f0f7f0; 
            border-right: 4px solid #198754; 
            margin-left: 15%;
            margin-right: 5%;
        }
        .message-time { font-size: 0.85rem; color: #6c757d; }
        .badge-priority { font-size: 0.8rem; }
        .chat-container { max-height: 500px; overflow-y: auto; }
        .ticket-desc { 
            background-color: #f8f9fa; 
            border-right: 4px solid #6c757d;
            padding: 15px;
            border-radius: 5px;
            white-space: pre-wrap;
        }
        .status-open { background-color: #d1e7dd !important; }
        .status-pending { background-color: #fff3cd !important; }
        .status-answered { background-color: #cff4fc !important; }
        .status-closed { background-color: #f8d7da !important; }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- نمایش پیام موفقیت -->
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            پاسخ شما با موفقیت ثبت شد.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- نمایش خطا -->
        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- هدر و اطلاعات تیکت -->
        <div class="card shadow mb-4">
            <div class="card-header header-info text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="bi bi-ticket-detailed me-2"></i>
                    تیکت #<?php echo $ticket['id']; ?>
                    <small class="fs-6 opacity-75">(<?php echo $ticket['project_name']; ?>)</small>
                </h4>
                <div>
                    <!-- فرم تغییر وضعیت -->
                    <form method="POST" action="" class="d-inline me-3">
                        <div class="input-group input-group-sm">
                            <select name="status" class="form-select form-select-sm" style="width: 150px;">
                                <option value="open" <?php echo ($ticket['status'] == 'open') ? 'selected' : ''; ?>>باز</option>
                                <option value="pending" <?php echo ($ticket['status'] == 'pending') ? 'selected' : ''; ?>>در انتظار</option>
                                <option value="answered" <?php echo ($ticket['status'] == 'answered') ? 'selected' : ''; ?>>پاسخ داده شده</option>
                                <option value="closed" <?php echo ($ticket['status'] == 'closed') ? 'selected' : ''; ?>>بسته</option>
                            </select>
                            <button type="submit" name="change_status" class="btn btn-warning btn-sm">
                                <i class="bi bi-check-circle"></i>
                            </button>
                        </div>
                    </form>
                    <a href="dashboardAdmin.php" class="btn btn-light btn-sm">
                        <i class="bi bi-house-door me-1"></i> داشبورد
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong><i class="bi bi-person me-2"></i> کاربر:</strong> 
                            <?php echo htmlspecialchars($ticket['user_name']); ?>
                        </p>
                        <p><strong><i class="bi bi-briefcase me-2"></i> پروژه:</strong> 
                            <?php echo htmlspecialchars($ticket['project_name']); ?>
                        </p>
                        <p><strong><i class="bi bi-envelope me-2"></i> موضوع:</strong> 
                            <?php echo htmlspecialchars($ticket['title']); ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong><i class="bi bi-circle-fill me-2"></i> وضعیت:</strong> 
                            <?php
                            $status_class = [
                                'open' => 'status-open',
                                'pending' => 'status-pending', 
                                'answered' => 'status-answered',
                                'closed' => 'status-closed'
                            ];
                            $status_text = [
                                'open' => 'باز',
                                'pending' => 'در انتظار',
                                'answered' => 'پاسخ داده شده',
                                'closed' => 'بسته'
                            ];
                            $s = $ticket['status'] ?? 'open';
                            ?>
                            <span class="badge rounded-pill px-3 py-2 <?php echo $status_class[$s]; ?>">
                                <?php echo $status_text[$s]; ?>
                            </span>
                        </p>
                        <p><strong><i class="bi bi-calendar me-2"></i> تاریخ ایجاد:</strong> 
                            <?php echo date('Y/m/d H:i', strtotime($ticket['created_at'])); ?>
                        </p>
                        <p><strong><i class="bi bi-arrow-clockwise me-2"></i> آخرین بروزرسانی:</strong> 
                            <?php 
                            if (!empty($ticket['updated_at']) && $ticket['updated_at'] != $ticket['created_at']) {
                                echo date('Y/m/d H:i', strtotime($ticket['updated_at']));
                            } else {
                                echo '---';
                            }
                            ?>
                        </p>
                    </div>
                </div>
                
                <!-- نمایش admin_reply اگر وجود دارد -->
                <?php if (!empty($ticket['admin_reply'])): ?>
                <div class="mt-4 p-3 bg-info bg-opacity-10 rounded border border-info">
                    <h6><i class="bi bi-chat-dots me-2"></i>پاسخ ادمین:</h6>
                    <p class="mb-0 mt-2"><?php echo nl2br(htmlspecialchars($ticket['admin_reply'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- بخش مکالمات -->
        <div class="card shadow mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>تاریخچه مکاتبات</h5>
                <span class="badge bg-primary"><?php echo count($messages); ?> پیام</span>
            </div>
            <div class="card-body chat-container">
                <?php if (empty($messages)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-chat-dots display-4 text-muted"></i>
                        <p class="mt-3">هنوز هیچ پیامی ثبت نشده است.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php
                        // تشخیص نوع فرستنده
                        $is_admin = false;
                        if (isset($msg['sender_type']) && $msg['sender_type'] == 'admin') {
                            $is_admin = true;
                        } elseif (isset($msg['sender']) && $msg['sender'] == 'admin') {
                            $is_admin = true;
                        } elseif (isset($msg['admin_id']) && !empty($msg['admin_id'])) {
                            $is_admin = true;
                        }
                        ?>
                        <div class="mb-3 p-3 rounded <?php echo $is_admin ? 'admin-message' : 'user-message'; ?>">
                            <div class="d-flex justify-content-between mb-2">
                                <strong>
                                    <?php if ($is_admin): ?>
                                        <i class="bi bi-shield-check text-success me-1"></i>
                                        مدیر سیستم
                                    <?php else: ?>
                                        <i class="bi bi-person-circle text-primary me-1"></i>
                                        <?php echo htmlspecialchars($ticket['user_name']); ?>
                                    <?php endif; ?>
                                </strong>
                                <span class="message-time">
                                    <i class="bi bi-clock me-1"></i>
                                    <?php echo date('H:i Y/m/d', strtotime($msg['created_at'] ?? $msg['timestamp'] ?? 'now')); ?>
                                </span>
                            </div>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($msg['message'] ?? $msg['content'] ?? '')); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- فرم پاسخ‌گویی -->
        <div class="card shadow">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-reply me-2"></i>ارسال پاسخ جدید
                    <?php if ($ticket['status'] == 'closed'): ?>
                        <span class="badge bg-secondary ms-2">تیکت بسته شده است</span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if ($ticket['status'] != 'closed'): ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="reply_message" class="form-label">متن پاسخ:</label>
                        <textarea class="form-control" id="reply_message" name="reply_message" 
                                  rows="5" placeholder="پاسخ خود را اینجا وارد کنید..." 
                                  required><?php echo isset($_POST['reply_message']) ? htmlspecialchars($_POST['reply_message']) : ''; ?></textarea>
                        <div class="form-text">
                            در حال پاسخ به تیکت <strong>#<?php echo $ticket_id; ?></strong> از 
                            <strong><?php echo htmlspecialchars($ticket['user_name']); ?></strong>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send me-2"></i> ارسال پاسخ
                            </button>
                            <a href="dashboardAdmin.php" class="btn btn-outline-secondary ms-2">
                                انصراف
                            </a>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            پاسخ شما به عنوان <strong><?php echo $_SESSION['admin_username']; ?></strong> ثبت می‌شود.
                        </small>
                    </div>
                </form>
                <?php else: ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    این تیکت بسته شده است. برای پاسخ‌گویی ابتدا وضعیت آن را به "باز" تغییر دهید.
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
            
            // Auto-focus روی textarea
            const textarea = document.getElementById('reply_message');
            if (textarea) {
                textarea.focus();
            }
        });
        
        // پیشنهاد پاسخ استاندارد
        function insertTemplate(template) {
            const textarea = document.getElementById('reply_message');
            if (textarea) {
                textarea.value = template;
                textarea.focus();
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
<?php
// admin/verify_document.php
session_start();

// تنظیم هدر JSON
header('Content-Type: application/json; charset=utf-8');

// بررسی دسترسی ادمین
if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_username'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'دسترسی غیرمجاز. لطفاً دوباره وارد شوید.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// بررسی CSRF token
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'توکن امنیتی نامعتبر یا منقضی شده است.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// بررسی پارامترهای ورودی
if (empty($_POST['document_id']) || empty($_POST['action'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'پارامترهای ورودی نامعتبر. document_id و action ضروری هستند.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$documentId = (int)$_POST['document_id'];
$action = $_POST['action'];
$reason = $_POST['reason'] ?? '';

// بررسی action مجاز
if (!in_array($action, ['verify', 'reject'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'عملیات نامعتبر. فقط verify و reject مجاز هستند.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// اتصال به دیتابیس
try {
    $DB_HOST = 'localhost';
    $DB_NAME = 'maskanhb_maskansazan';
    $DB_USER = 'maskanhb_maskansazan';
    $DB_PASS = 'NJ)B*$@y3R9P0Oyg';

    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    
    if ($mysqli->connect_error) {
        throw new Exception('خطا در اتصال به پایگاه داده: ' . $mysqli->connect_error);
    }
    
    $mysqli->set_charset("utf8mb4");
    
    // تعیین وضعیت جدید
    $newStatus = ($action === 'verify') ? 'verified' : 'rejected';
    
    // ابتدا وضعیت فعلی را بگیریم
    $checkStmt = $mysqli->prepare("SELECT verification_status FROM user_documents WHERE id = ?");
    if (!$checkStmt) {
        throw new Exception('خطا در آماده‌سازی کوئری بررسی');
    }
    
    $checkStmt->bind_param("i", $documentId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        $checkStmt->close();
        $mysqli->close();
        
        http_response_code(404);
        echo json_encode([
            'success' => false, 
            'message' => 'مدرک مورد نظر یافت نشد.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $checkRow = $checkResult->fetch_assoc();
    $oldStatus = $checkRow['verification_status'] ?? 'pending';
    $checkStmt->close();
    
    // به‌روزرسانی وضعیت مدرک
    $stmt = $mysqli->prepare("UPDATE user_documents SET verification_status = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception('خطا در آماده‌سازی کوئری به‌روزرسانی: ' . $mysqli->error);
    }
    
    $stmt->bind_param("si", $newStatus, $documentId);
    
    if (!$stmt->execute()) {
        throw new Exception('خطا در اجرای کوئری به‌روزرسانی: ' . $stmt->error);
    }
    
    $affectedRows = $stmt->affected_rows;
    $stmt->close();
    
    // درج لاگ (اگر جدول وجود دارد)
    try {
        $logQuery = "CREATE TABLE IF NOT EXISTS document_status_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            document_id INT NOT NULL,
            old_status VARCHAR(50),
            new_status VARCHAR(50),
            action VARCHAR(50),
            reason TEXT,
            admin_id INT NOT NULL,
            change_date DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        $mysqli->query($logQuery);
        
        $logStmt = $mysqli->prepare("
            INSERT INTO document_status_logs 
            (document_id, old_status, new_status, action, reason, admin_id) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        if ($logStmt) {
            $logStmt->bind_param("issssi", $documentId, $oldStatus, $newStatus, $action, $reason, $_SESSION['admin_id']);
            $logStmt->execute();
            $logStmt->close();
        }
    } catch (Exception $logError) {
        // فقط خطای لاگ را ثبت می‌کنیم اما عملیات اصلی را ادامه می‌دهیم
        error_log("خطا در ثبت لاگ: " . $logError->getMessage());
    }
    
    $mysqli->close();
    
    // پاسخ موفقیت‌آمیز
    $message = ($action === 'verify') 
        ? 'مدرک با موفقیت تأیید شد.' 
        : 'مدرک با موفقیت رد شد.';
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'data' => [
            'document_id' => $documentId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'affected_rows' => $affectedRows
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // خطا در پردازش
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'خطای سرور: ' . $e->getMessage(),
        'debug' => [
            'document_id' => $documentId ?? null,
            'action' => $action ?? null,
            'error' => $e->getMessage()
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>
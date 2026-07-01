<?php
// submit_ticket_reply.php
session_start();
require_once 'config/database.php';

// بررسی ادمین
if (empty($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز']);
    exit;
}

// بررسی CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'توکن CSRF نامعتبر']);
    exit;
}

// دریافت داده‌ها
$ticketId = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$status = isset($_POST['status']) ? $_POST['status'] : 'answered';

if ($ticketId <= 0 || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'داده‌های ورودی ناقص']);
    exit;
}

try {
    $mysqli->begin_transaction();
    
    // ۱. افزودن پیام به ticket_messages
    $insertStmt = $mysqli->prepare("
        INSERT INTO ticket_messages 
        (ticket_id, sender, message, created_at) 
        VALUES (?, 'admin', ?, NOW())
    ");
    
    $insertStmt->bind_param("is", $ticketId, $message);
    $insertStmt->execute();
    
    // ۲. به‌روزرسانی وضعیت تیکت
    $updateStmt = $mysqli->prepare("
        UPDATE tickets 
        SET status = ?, 
            updated_at = NOW(),
            admin_id = ?
        WHERE id = ?
    ");
    
    $adminId = $_SESSION['admin_id'];
    $updateStmt->bind_param("sii", $status, $adminId, $ticketId);
    $updateStmt->execute();
    
    // ۳. ارسال نوتیفیکیشن (اختیاری)
    // در اینجا می‌توانید کد ارسال ایمیل یا پیامک را اضافه کنید
    
    $mysqli->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'پاسخ با موفقیت ثبت شد',
        'ticket_id' => $ticketId
    ]);
    
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'خطا در ثبت پاسخ: ' . $e->getMessage()
    ]);
}
?>
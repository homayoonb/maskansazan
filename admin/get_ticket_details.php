<?php
// get_ticket_details.php
session_start();
require_once 'config/database.php'; // فایل کانفیگ دیتابیس شما

// بررسی ادمین
if (empty($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز']);
    exit;
}

if (!isset($_GET['ticket_id']) || !is_numeric($_GET['ticket_id'])) {
    echo json_encode(['success' => false, 'message' => 'شناسه تیکت معتبر نیست']);
    exit;
}

$ticketId = (int)$_GET['ticket_id'];

try {
    // ۱. اطلاعات تیکت
    $stmt = $mysqli->prepare("
        SELECT 
            t.id,
            t.title,
            t.description,
            t.status,
            t.user_name,
            t.user_id,
            t.project_id,
            t.created_at,
            p.name as project_name
        FROM tickets t
        LEFT JOIN projects p ON t.project_id = p.id
        WHERE t.id = ?
    ");
    
    $stmt->bind_param("i", $ticketId);
    $stmt->execute();
    $ticketResult = $stmt->get_result();
    $ticket = $ticketResult->fetch_assoc();
    
    if (!$ticket) {
        echo json_encode(['success' => false, 'message' => 'تیکت یافت نشد']);
        exit;
    }
    
    // ۲. دریافت پیام‌های قبلی تیکت
    $messagesStmt = $mysqli->prepare("
        SELECT 
            tm.id,
            tm.message,
            tm.sender,
            tm.created_at,
            CASE 
                WHEN tm.sender = 'admin' THEN 'مدیریت'
                ELSE tm.sender_name
            END as sender_name
        FROM ticket_messages tm
        WHERE tm.ticket_id = ?
        ORDER BY tm.created_at ASC
    ");
    
    $messagesStmt->bind_param("i", $ticketId);
    $messagesStmt->execute();
    $messagesResult = $messagesStmt->get_result();
    $previousReplies = [];
    
    while ($row = $messagesResult->fetch_assoc()) {
        $previousReplies[] = $row;
    }
    
    // ۳. بازگرداندن داده‌ها
    echo json_encode([
        'success' => true,
        'ticket' => $ticket,
        'previous_replies' => $previousReplies
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطا در دریافت اطلاعات: ' . $e->getMessage()
    ]);
}
?>
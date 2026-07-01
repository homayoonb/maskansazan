<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/config/Database.php';

// اتصال به دیتابیس
$db = new Database();
$mysqli = $db->connect();
if (!$mysqli) {
    echo json_encode(['success' => false, 'message' => 'خطا در اتصال به دیتابیس']);
    exit;
}

// گرفتن داده‌ها از فرم
$ticketId = intval($_POST['ticket_id'] ?? 0);
$reply = trim($_POST['reply'] ?? '');

if ($ticketId <= 0 || $reply === '') {
    echo json_encode(['success' => false, 'message' => 'اطلاعات ارسال شده نامعتبر است']);
    exit;
}

// آماده‌سازی کوئری درج پیام در ticket_messages
$stmt = $mysqli->prepare("
    INSERT INTO ticket_messages (ticket_id, sender, message, created_at) 
    VALUES (?, 'admin', ?, NOW())
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'خطا در آماده‌سازی کوئری: ' . $mysqli->error]);
    exit;
}

$stmt->bind_param("is", $ticketId, $reply);

if ($stmt->execute()) {
    // بروزرسانی وضعیت تیکت به answered
    $mysqli->query("UPDATE tickets SET status='answered', updated_at=NOW() WHERE id=$ticketId");

    echo json_encode(['success' => true, 'message' => 'پاسخ شما با موفقیت ثبت شد']);
} else {
    echo json_encode(['success' => false, 'message' => 'خطای دیتابیس: ' . $stmt->error]);
}

$stmt->close();
$mysqli->close();

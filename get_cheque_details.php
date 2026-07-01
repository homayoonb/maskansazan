<?php
// ajax/get_cheque_details.php
// دریافت جزئیات چک با استفاده از AJAX

// ==================== تنظیمات اولیه ====================
header('Content-Type: application/json; charset=utf-8');

// فعال‌سازی نمایش خطا برای دیباگ
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// ==================== بررسی سشن (موقتاً غیرفعال) ====================
// برای تست اولیه، بخش بررسی سشن را غیرفعال می‌کنیم

session_start();


// ==================== بررسی پارامترهای ورودی ====================
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'شناسه چک ارسال نشده است']);
    exit;
}

$chequeId = intval($_GET['id']);

if ($chequeId <= 0) {
    echo json_encode(['success' => false, 'message' => 'شناسه چک نامعتبر است']);
    exit;
}

// ==================== اتصال به دیتابیس ====================
$db = new mysqli("localhost", "maskanhb_maskansazan", "NJ)B*$@y3R9P0Oyg", "maskanhb_maskansazan");

if ($db->connect_error) {
    echo json_encode([
        'success' => false, 
        'message' => 'خطا در اتصال به دیتابیس',
        'error' => $db->connect_error
    ]);
    exit;
}

$db->set_charset("utf8mb4");

// ==================== دریافت اطلاعات چک ====================
try {
    $query = "SELECT *
              FROM cheque_transactions
              WHERE id = ?";

    if (!$stmt = $db->prepare($query)) {
        throw new Exception('خطا در آماده‌سازی کوئری: ' . $db->error);
    }

    $stmt->bind_param("i", $chequeId);
    
    if (!$stmt->execute()) {
        throw new Exception('خطا در اجرای کوئری: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'چک مورد نظر یافت نشد (ID: ' . $chequeId . ')'
        ]);
        exit;
    }

    // دریافت اطلاعات چک
    $cheque = $result->fetch_assoc();

    // تبدیل وضعیت به فارسی
    $statusLabels = [
        'pending' => 'در انتظار',
        'cleared' => 'وصول شده',
        'returned' => 'برگشتی',
        'cancelled' => 'ابطال شده'
    ];

    $cheque['status_label'] = $statusLabels[$cheque['status']] ?? $cheque['status'];

    // فرمت کردن مبلغ
    $cheque['amount_formatted'] = number_format(floatval($cheque['amount'])) . ' ریال';

    // بستن اتصالات
    $stmt->close();
    $db->close();

    // ==================== ارسال پاسخ ====================
    echo json_encode([
        'success' => true,
        'cheque' => $cheque,
        'debug' => [
            'id_received' => $chequeId,
            'query_executed' => true
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // در صورت خطا
    if (isset($stmt)) $stmt->close();
    if (isset($db)) $db->close();
    
    echo json_encode([
        'success' => false,
        'message' => 'خطا در پردازش درخواست',
        'error' => $e->getMessage(),
        'debug' => [
            'id_received' => $chequeId
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>
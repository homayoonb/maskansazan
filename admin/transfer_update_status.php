<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

/* فقط POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => false,
        'message' => 'Method Not Allowed'
    ]);
    exit;
}

/* ===============================
   اتصال دیتابیس (مستقیم)
================================ */
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4",
        "maskanhb_maskansazan",
        "NJ)B*$@y3R9P0Oyg",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'خطا در اتصال به دیتابیس'
    ]);
    exit;
}

/* ===============================
   دریافت داده JSON
================================ */
$input = json_decode(file_get_contents('php://input'), true);

if (
    !$input ||
    empty($input['id']) ||
    empty($input['status'])
) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'message' => 'داده‌های ارسالی نامعتبر است'
    ]);
    exit;
}

$id     = (int)$input['id'];
$status = trim($input['status']);

/* ===============================
   اعتبارسنجی وضعیت
================================ */
$allowedStatus = ['pending', 'verified', 'rejected'];

if (!in_array($status, $allowedStatus, true)) {
    echo json_encode([
        'status' => false,
        'message' => 'وضعیت انتخاب‌شده معتبر نیست'
    ]);
    exit;
}

/* ===============================
   بروزرسانی وضعیت
================================ */
$stmt = $pdo->prepare(
    "UPDATE transfers 
     SET status = :status, updated_at = NOW() 
     WHERE id = :id"
);

$stmt->execute([
    ':status' => $status,
    ':id'     => $id
]);

if ($stmt->rowCount() === 0) {
    echo json_encode([
        'status' => false,
        'message' => 'حواله‌ای با این شناسه پیدا نشد'
    ]);
    exit;
}

/* ===============================
   پاسخ موفق
================================ */
echo json_encode([
    'status' => true,
    'message' => 'وضعیت حواله با موفقیت بروزرسانی شد'
]);
exit;

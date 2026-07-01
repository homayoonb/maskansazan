<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* فقط POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status'=>false,'message'=>'درخواست نامعتبر']);
    exit;
}

/* دریافت JSON */
$data = json_decode(file_get_contents('php://input'), true);
$id = (int)($data['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['status'=>false,'message'=>'شناسه نامعتبر']);
    exit;
}

/* اتصال دیتابیس */
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4",
        "maskanhb_maskansazan",
        "NJ)B*$@y3R9P0Oyg",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo json_encode(['status'=>false,'message'=>'خطا در اتصال دیتابیس']);
    exit;
}

/* دریافت تصویر */
$stmt = $pdo->prepare("SELECT receipt_image FROM transfers WHERE id=?");
$stmt->execute([$id]);
$transfer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transfer) {
    echo json_encode(['status'=>false,'message'=>'حواله یافت نشد']);
    exit;
}

/* حذف تصویر */
if (!empty($transfer['receipt_image'])) {
    $path = $_SERVER['DOCUMENT_ROOT'].'/'.$transfer['receipt_image'];
    if (file_exists($path)) unlink($path);
}

/* حذف رکورد */
$stmt = $pdo->prepare("DELETE FROM transfers WHERE id=?");
$stmt->execute([$id]);

echo json_encode([
    'status'=>true,
    'message'=>'🗑 حواله با موفقیت حذف شد'
]);
exit;

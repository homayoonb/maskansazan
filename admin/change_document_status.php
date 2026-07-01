<?php
session_start();
require_once __DIR__ . '/SafeDatabase.php';

$document_id = (int)($_GET['id'] ?? 0);
$user_id     = (int)($_GET['user_id'] ?? 0);
$status      = $_GET['status'] ?? '';

$allowedStatuses = ['verified', 'rejected', 'pending'];

if ($document_id <= 0 || $user_id <= 0 || !in_array($status, $allowedStatuses, true)) {
    die('درخواست نامعتبر است.');
}

try {

    $pdo = new PDO(
        "mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $stmt = $pdo->prepare("
        UPDATE user_documents
        SET verification_status = :status
        WHERE id = :id
    ");

    $stmt->execute([
        ':status' => $status,
        ':id'     => $document_id
    ]);

    header("Location: showDocuments.php?id=" . $user_id . "&status=updated");
    exit;

} catch (PDOException $e) {
    die('خطا در بروزرسانی وضعیت مدرک: ' . $e->getMessage());
}

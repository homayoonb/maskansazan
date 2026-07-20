<?php
session_start();
$activeTab = $_GET['tab'] ?? 'suppliers';
$contractorId = (int)$_GET['id'];
$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboardAdmin.php?activeTab={$activeTab}&project_id={$projectId}");
    exit;
}


$pdo = new PDO(
    "mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4",
    "root",
    "",
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);;
try {

    $pdo->beginTransaction();

    // بررسی وجود پیمانکار
    $checkStmt = $pdo->prepare("SELECT id FROM contractors WHERE id = :id LIMIT 1");
    $checkStmt->bindValue(':id', $contractorId, PDO::PARAM_INT);
    $checkStmt->execute();

    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        $pdo->rollBack();
        header("Location: dashboardAdmin.php?activeTab={$activeTab}&project_id={$projectId}&msg=not_found");
        exit;
    }

    // گرفتن فایل‌های مرتبط برای حذف از سرور
    $docStmt = $pdo->prepare("SELECT id, profile_image FROM file_documents WHERE contractor_id = :contractor_id");
    $docStmt->bindValue(':contractor_id', $contractorId, PDO::PARAM_INT);
    $docStmt->execute();
    $documents = $docStmt->fetchAll(PDO::FETCH_ASSOC);

    // حذف فایل‌های فیزیکی
    foreach ($documents as $document) {
        if (!empty($document['profile_image']) && file_exists($document['profile_image'])) {
            unlink($document['profile_image']);
        }
    }

    // حذف رکوردهای مدارک
    $deleteDocsStmt = $pdo->prepare("DELETE FROM file_documents WHERE contractor_id = :contractor_id");
    $deleteDocsStmt->bindValue(':contractor_id', $contractorId, PDO::PARAM_INT);
    $deleteDocsStmt->execute();

    // حذف پیمانکار
    $deleteContractorStmt = $pdo->prepare("DELETE FROM contractors WHERE id = :id");
    $deleteContractorStmt->bindValue(':id', $contractorId, PDO::PARAM_INT);
    $deleteContractorStmt->execute();

    $pdo->commit();

    header("Location: dashboardAdmin.php?activeTab={$activeTab}&project_id={$projectId}&msg=deleted");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header("Location: dashboardAdmin.php?activeTab={$activeTab}&project_id={$projectId}&msg=error");
    exit;
}

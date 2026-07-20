<?php
session_start();
$pdo = new PDO(
        "mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4",
        "root",
        "",
        [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
);
$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['id'] ?? 0);
$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : (int)($_POST['project_id'] ?? 0);
$activeTab = $_GET['activeTab'] ?? ($_POST['activeTab'] ?? 'suppliers');

if ($id <= 0) {
    header("Location: dashboardAdmin.php?activeTab={$activeTab}&project_id={$projectId}");
    exit;
}
$allowedDocumentTypes = ['شناسنامه', 'کارت ملی', 'قرارداد', 'سند', 'فیش بانکی', 'سایر'];
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $nationalCode = trim($_POST['national_code'] ?? '');
    $jobTitle = trim($_POST['job_title'] ?? '');
    $documentType = trim($_POST['document_type'] ?? '');

    try {
        $pdo = new PDO(
                "mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4",
                "root",
                "",
                [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
        );;
        $pdo->beginTransaction();

        $updateContractor = $pdo->prepare("
            UPDATE contractors
            SET first_name = :first_name,
                last_name = :last_name,
                phone = :phone,
                national_code = :national_code,
                job_title = :job_title
            WHERE id = :id
        ");

        $updateContractor->execute([
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':phone' => $phone,
            ':national_code' => $nationalCode,
            ':job_title' => $jobTitle,
            ':id' => $id
        ]);

        if (!empty($_FILES['document_image']['name'])) {
            $uploadDir = 'uploads/contractors/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileTmp = $_FILES['document_image']['tmp_name'];
            $fileName = time() . '_' . basename($_FILES['document_image']['name']);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($fileTmp, $targetPath)) {
                $oldDocStmt = $pdo->prepare("
                    SELECT id, profile_image
                    FROM file_documents
                    WHERE contractor_id = :contractor_id
                    ORDER BY id DESC
                    LIMIT 1
                ");
                $oldDocStmt->execute([':contractor_id' => $id]);
                $oldDocument = $oldDocStmt->fetch(PDO::FETCH_ASSOC);

                if ($oldDocument) {
                    if (!empty($oldDocument['profile_image']) && file_exists($oldDocument['profile_image'])) {
                        unlink($oldDocument['profile_image']);
                    }

                    $updateDocument = $pdo->prepare("
                        UPDATE file_documents
                        SET document_type = :document_type,
                            profile_image = :profile_image
                        WHERE id = :id
                    ");
                    $updateDocument->execute([
                        ':document_type' => $documentType,
                        ':profile_image' => $targetPath,
                        ':id' => $oldDocument['id']
                    ]);
                } else {
                    $insertDocument = $pdo->prepare("
                        INSERT INTO file_documents (contractor_id, document_type, profile_image)
                        VALUES (:contractor_id, :document_type, :profile_image)
                    ");
                    $insertDocument->execute([
                        ':contractor_id' => $id,
                        ':document_type' => $documentType,
                        ':profile_image' => $targetPath
                    ]);
                }
            }
        } else {
            $checkDocStmt = $pdo->prepare("
                SELECT id
                FROM file_documents
                WHERE contractor_id = :contractor_id
                ORDER BY id DESC
                LIMIT 1
            ");
            $checkDocStmt->execute([':contractor_id' => $id]);
            $existingDoc = $checkDocStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingDoc) {
                $updateDocType = $pdo->prepare("
                    UPDATE file_documents
                    SET document_type = :document_type
                    WHERE id = :id
                ");
                $updateDocType->execute([
                    ':document_type' => $documentType,
                    ':id' => $existingDoc['id']
                ]);
            }
        }

        $pdo->commit();

        header("Location: dashboardAdmin.php?activeTab={$activeTab}&project_id={$projectId}&msg=updated");
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "خطا در ویرایش اطلاعات: " . $e->getMessage();
        exit;
    }
}

$stmt = $pdo->prepare("
    SELECT contractors.*, fd.profile_image AS document_image, fd.document_type
    FROM contractors
    LEFT JOIN file_documents fd
        ON fd.id = (
            SELECT fd2.id
            FROM file_documents fd2
            WHERE fd2.contractor_id = contractors.id
            ORDER BY fd2.id DESC
            LIMIT 1
        )
    WHERE contractors.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $id]);
$contractor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contractor) {
    header("Location: dashboardAdmin.php?activeTab={$activeTab}&project_id={$projectId}");
    exit;
}
ob_start()
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ویرایش پیمانکار</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body>
<div class="d-flex gap-2">

    <a href="dashboardAdmin.php?activeTab=<?= urlencode($activeTab) ?>&project_id=<?= (int)$projectId ?>"
       class="btn btn-secondary">بازگشت</a>
</div>

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-warning text-dark">
            <h4 class="mb-0">ویرایش پیمانکار</h4>
        </div>

        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= (int)$contractor['id'] ?>">
                <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">
                <input type="hidden" name="activeTab" value="<?= htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8') ?>">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">نام</label>
                        <input type="text" name="first_name" class="form-control"
                               value="<?= htmlspecialchars($contractor['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">نام خانوادگی</label>
                        <input type="text" name="last_name" class="form-control"
                               value="<?= htmlspecialchars($contractor['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">شماره تماس</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= htmlspecialchars($contractor['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">کد ملی</label>
                        <input type="text" name="national_code" class="form-control"
                               value="<?= htmlspecialchars($contractor['national_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">عنوان شغلی</label>
                        <input type="text" name="job_title" class="form-control"
                               value="<?= htmlspecialchars($contractor['job_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">نوع مدرک</label>
                        <select name="document_type" class="form-select">
                            <option value="">انتخاب مدرک</option>
                            <?php foreach ($allowedDocumentTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>" <?= (($_POST['document_type'] ?? '') === $type) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">تصویر فعلی</label><br>
                        <?php if (!empty($contractor['document_image'])): ?>
                            <a href="<?= htmlspecialchars($contractor['document_image'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">
                                <img src="<?= htmlspecialchars($contractor['document_image'], ENT_QUOTES, 'UTF-8') ?>"
                                     alt="document"
                                     style="width:150px;height:150px;object-fit:cover;border:1px solid #ddd;border-radius:8px;">
                            </a>
                        <?php else: ?>
                            <span class="text-muted">تصویری ثبت نشده است</span>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">آپلود تصویر جدید</label>
                        <input type="file" name="document_image" class="form-control" accept="image/*">
                    </div>
                    <div class="d-flex gap-2">
                        <button  type="submit" class="btn btn-success">ذخیره تغییرات</button>
                    </div>

            </form>
        </div>
    </div>
</div>

</body>
</html>
<?php
$content = ob_get_clean();
$title = "پیمانکاران/تامین کنندگان";
include '../partials/layout.php';
include '../partials/slider.php';
?>
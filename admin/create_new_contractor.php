<?php

$errors = [];
$pdo = new PDO(
        "mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4",
        "root",
        "",
        [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
);
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$activeTab = $_GET['tab'] ?? 'suppliers';
if ($project_id <= 0) {
    var_dump($_GET);
    die("شناسه پروژه معتبر نیست.");
}
$allowedDocumentTypes = ['فاکتور','شناسنامه', 'کارت ملی', 'قرارداد', 'سند', 'فیش بانکی', 'سایر'];
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];

function generateUploadName($prefix, $extension)
{
    return $prefix . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $extension;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $nationalCode = trim($_POST['national_code'] ?? '');
    $jobTitle = trim($_POST['job_title'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $documentType = trim($_POST['document_type'] ?? '');

    $documentPath = null;


    if ($firstName === '') {
        $errors[] = 'نام الزامی است.';
    }

    if ($lastName === '') {
        $errors[] = 'نام خانوادگی الزامی است.';
    }

    if ($nationalCode === '') {
        $errors[] = 'کد ملی الزامی است.';
    }

    if ($documentType !== '' && !in_array($documentType, $allowedDocumentTypes, true)) {
        $errors[] = 'نوع مدرک معتبر نیست.';
    }

    $hasDocumentFile = isset($_FILES['document_image']) &&
            (($_FILES['document_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);

    if ($documentType !== '' && !$hasDocumentFile) {
        $errors[] = 'فایل مدرک را انتخاب کنید.';
    }

    if ($hasDocumentFile && $documentType === '') {
        $errors[] = 'نوع مدرک را انتخاب کنید.';
    }

    $uploadDir = 'uploads/contractors/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if ($hasDocumentFile) {
        if (($_FILES['document_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errors[] = 'آپلود فایل مدرک با خطا مواجه شد.';
        } else {
            $originalName = basename($_FILES['document_image']['name']);
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (!in_array($extension, $allowedExtensions, true)) {
                $errors[] = 'فرمت فایل مدرک مجاز نیست.';
            }
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO contractors
                (project_id, first_name, last_name, national_code, job_title, phone, address, document_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, NULL)
            ");

            $stmt->execute([
                    $project_id,
                    $firstName,
                    $lastName,
                    $nationalCode,
                    $jobTitle,
                    $phone,
                    $address
            ]);

            $contractorId = (int)$pdo->lastInsertId();

            if ($hasDocumentFile && $documentType !== '') {
                $extension = strtolower(pathinfo(basename($_FILES['document_image']['name']), PATHINFO_EXTENSION));
                $fileName = generateUploadName('contractor_doc', $extension);
                $targetPath = $uploadDir . $fileName;

                if (!move_uploaded_file($_FILES['document_image']['tmp_name'], $targetPath)) {
                    throw new Exception('ذخیره فایل مدرک انجام نشد.');
                }

                $documentPath = $targetPath;

                $docStmt = $pdo->prepare("
                    INSERT INTO file_documents
                    (contractor_id, document_type, profile_image)
                    VALUES (?, ?, ?)
                ");

                $docStmt->execute([
                        $contractorId,
                        $documentType,
                        $documentPath
                ]);

                $documentId = (int)$pdo->lastInsertId();

                $updateStmt = $pdo->prepare("
                    UPDATE contractors
                    SET document_id = ?
                    WHERE id = ?
                ");

                $updateStmt->execute([
                        $documentId,
                        $contractorId
                ]);
            }

            $pdo->commit();

            header("Location: dashboardAdmin.php?project_id=" . $project_id . "&success=created");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();

            if ($documentPath !== null && is_file($documentPath)) {
                unlink($documentPath);
            }

            $errors[] = 'خطا در ثبت اطلاعات: ' . $e->getMessage();
        }
    }
}
ob_start()
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ثبت پیمانکار جدید</title>
</head>
<body>
<a href="dashboardAdmin.php?project_id=<?php echo $project_id?>" class="btn btn-secondary">بازگشت</a>
<div class="container mb-5">
    <h3 class="mb-4">ثبت پیمانکار جدید</h3>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
<div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">نام</label>
            <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">نام خانوادگی</label>
            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">کد ملی</label>
            <input type="text" name="national_code" class="form-control" value="<?= htmlspecialchars($_POST['national_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">عنوان شغلی</label>
            <input type="text" name="job_title" class="form-control" value="<?= htmlspecialchars($_POST['job_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">تلفن</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">آدرس</label>
            <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($_POST['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <hr>

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

        <div class="col-md-6 mb-3">
            <label class="form-label">فایل مدرک</label>
            <input type="file" name="document_image" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
        </div>
</div>
        <button type="submit" class="btn btn-success">ثبت پیمانکار</button>
    </form>
</div>
</body>
</html>
<?php
$content = ob_get_clean();
$title = "پیمانکاران/تامین کنندگان";
include '../partials/layout.php';
include '../partials/slider.php';
?>

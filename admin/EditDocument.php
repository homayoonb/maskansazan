<?php
session_start();
require_once __DIR__ . '/SafeDatabase.php';

/*
|--------------------------------------------------------------------------
| اتصال PDO
|--------------------------------------------------------------------------
| اگر SafeDatabase.php خودش $pdo را می‌سازد، این بخش را حذف کن.
*/
if (!isset($pdo)) {
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
    } catch (PDOException $e) {
        die('خطا در اتصال به دیتابیس: ' . $e->getMessage());
    }
}

/*
|--------------------------------------------------------------------------
| دریافت شناسه مدرک
|--------------------------------------------------------------------------
*/
$document_id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($document_id <= 0) {
    die('شناسه مدرک معتبر نیست.');
}

/*
|--------------------------------------------------------------------------
| واکشی اطلاعات مدرک
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT *
    FROM user_documents
    WHERE id = :id
    LIMIT 1
");
$stmt->execute([':id' => $document_id]);

$document = $stmt->fetch();

if (!$document) {
    die('مدرک مورد نظر یافت نشد.');
}

/*
|--------------------------------------------------------------------------
| متغیرهای پیام
|--------------------------------------------------------------------------
*/
$errorMessage = '';
$successMessage = '';

/*
|--------------------------------------------------------------------------
| اگر فرم ارسال شده باشد
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_id       = (int)($_POST['user_id'] ?? $document['user_id'] ?? 0);
    $document_name = trim($_POST['document_name'] ?? '');
    $document_type = trim($_POST['document_type'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $current_path  = $document['file_path'] ?? '';
    $current_name  = $document['file_name'] ?? '';
    $verification_status = trim($_POST['verification_status'] ?? 'pending');
    if ($user_id <= 0 || $document_name === '' || $document_type === '') {
        $errorMessage = 'لطفاً تمام فیلدهای ضروری را تکمیل کنید.';
    } else {

        $new_file_path = $current_path;
        $new_file_name = $current_name;
        $new_file_size = $document['file_size'] ?? 0;
        $new_file_type = $document['file_type'] ?? '';

        /*
        |--------------------------------------------------------------------------
        | اگر فایل جدید انتخاب شده باشد
        |--------------------------------------------------------------------------
        */
        if (isset($_FILES['document_file']) && !empty($_FILES['document_file']['name'])) {

            if ($_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
                $errorMessage = 'خطا در آپلود فایل.';
            } else {

                $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
                $originalFileName = $_FILES['document_file']['name'];
                $tmpFilePath = $_FILES['document_file']['tmp_name'];
                $fileSize = (int)$_FILES['document_file']['size'];
                $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

                if (!in_array($fileExtension, $allowedExtensions, true)) {
                    $errorMessage = 'فرمت فایل مجاز نیست.';
                } else {

                    $uploadDir = __DIR__ . '/uploads/documents/';

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $newGeneratedName = time() . '_' . mt_rand(1000, 9999) . '.' . $fileExtension;
                    $destination = $uploadDir . $newGeneratedName;

                    if (move_uploaded_file($tmpFilePath, $destination)) {

                        // حذف فایل قبلی در صورت وجود
                        if (!empty($current_path)) {
                            $oldFullPath = __DIR__ . '/' . ltrim($current_path, '/\\');
                            if (file_exists($oldFullPath) && is_file($oldFullPath)) {
                                @unlink($oldFullPath);
                            }
                        }

                        $new_file_path = 'uploads/documents/' . $newGeneratedName;
                        $new_file_name = $originalFileName;
                        $new_file_size = $fileSize;
                        $new_file_type = $fileExtension;
                    } else {
                        $errorMessage = 'ذخیره فایل جدید با خطا مواجه شد.';
                    }
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | بروزرسانی دیتابیس
        |--------------------------------------------------------------------------
        */
        if ($errorMessage === '') {
            $updateStmt = $pdo->prepare("
                UPDATE user_documents
                SET user_id = :user_id,
                    document_name = :document_name,
                    document_type = :document_type,
                    description = :description,
                    file_path = :file_path,
                    file_name = :file_name,
                    file_size = :file_size,
                    file_type = :file_type,
                     verification_status = :verification_status
                WHERE id = :id
            ");

            $updated = $updateStmt->execute([
                ':user_id'       => $user_id,
                ':document_name' => $document_name,
                ':document_type' => $document_type,
                ':description'   => $description,
                ':file_path'     => $new_file_path,
                ':file_name'     => $new_file_name,
                ':file_size'     => $new_file_size,
                ':file_type'     => $new_file_type,
                ':id'            => $document_id,
                    ':verification_status'=>$verification_status
            ]);

            if ($updated) {
                header("Location: showDocuments.php?id=" . $user_id . "&status=edited");
                exit;
            } else {
                $errorMessage = 'ویرایش مدرک انجام نشد.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ویرایش مدرک</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            ویرایش مدرک
        </div>

        <div class="card-body">

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($successMessage) ?>
                </div>
            <?php endif; ?>

            <form action="EditDocument.php?id=<?= (int)$document_id ?>" method="POST" enctype="multipart/form-data">

                <input type="hidden" name="id" value="<?= (int)$document_id ?>">
                <input type="hidden" name="user_id" value="<?= (int)$document['user_id'] ?>">

                <div class="mb-3">
                    <label class="form-label">نام مدرک</label>
                    <input
                        type="text"
                        name="document_name"
                        class="form-control"
                        value="<?= htmlspecialchars($document['document_name'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">نوع مدرک</label>
                    <select name="document_type" class="form-select" required>
                        <option value="">انتخاب کنید</option>
                        <option value="national_card">کارت ملی</option>
                        <option value="birth_certificate">شناسنامه</option>
                        <option value="ownership_document">سند مالکیت</option>
                        <option value="bank_document">مدرک بانکی</option>
                        <option value="other">سایر</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">توضیحات</label>
                    <textarea
                        name="description"
                        class="form-control"
                        rows="4"
                    ><?= htmlspecialchars($document['description'] ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">فایل فعلی</label><br>

                    <?php if (!empty($document['file_path'])): ?>
                        <a href="/maskansazan/<?= htmlspecialchars(ltrim($document['file_path'], '/')) ?>" target="_blank">
                            مشاهده فایل فعلی
                        </a>
                    <?php else: ?>
                        <span class="text-muted">فایلی ثبت نشده است.</span>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label">جایگزینی فایل جدید</label>
                    <input type="file" name="document_file" class="form-control">

                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        ذخیره تغییرات
                    </button>

                    <a href="showDocuments.php?id=<?= (int)$document['user_id'] ?>" class="btn btn-secondary">
                        بازگشت
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

</body>
</html>

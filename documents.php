<?php
include 'config/Database.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: groupmembers.php");
    exit;
}

$fullName = $_SESSION['full_name'] ?? 'کاربر';
$memberType = $_SESSION['main_member'] ?? 'عضو';
$nationalCode = $_SESSION['NationalCode'] ?? '----';
$phone = $_SESSION['phone'] ?? '09123456789';
$projectShare = $_SESSION['share'] ?? '----';

$message = '';
$messageType = '';

$uploadDir = "uploads/" . $fullName . "/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$userDocs = [
        "BirthCertificate" => $uploadDir . "BirthCertificate.jpg",
        "NationalCard" => $uploadDir . "NationalCard.jpg",
        "PaymentReceipt" => $uploadDir . "PaymentReceipt.jpg"
];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_GET["action"]) && $_GET["action"] === "upload") {
    $docType = $_GET["docType"];
    $file = $_FILES["fileToUpload"] ?? null;

    if ($file && $file["error"] === 0) {
        $allowedTypes = ['application/jpg'];
//        $maxSize = 5 * 1024 * 1024;

            $target = $uploadDir . basename($docType . ".jpg");
            if (move_uploaded_file($file["tmp_name"], $target)) {
                $message = "فایل با موفقیت آپلود شد.";
                $messageType = "success";
            } else {
                $message = "خطا در آپلود فایل!";
                $messageType = "danger";
            }
        }
}

if (isset($_GET["action"]) && $_GET["action"] === "delete" && isset($_GET["file"])) {
    $filePath = $uploadDir . basename($_GET["file"]);
    if (file_exists($filePath)) {
        unlink($filePath);
        $message = "فایل حذف شد.";
        $messageType = "warning";
    }
}

if (isset($_GET["action"]) && $_GET["action"] === "download" && isset($_GET["file"])) {
    $filePath = $uploadDir . basename($_GET["file"]);
    if (file_exists($filePath)) {
        header("Content-Type: application/pdf");
        header("Content-Disposition: attachment; filename=" . basename($filePath));
        readfile($filePath);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>مدارک و مستندات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { font-family:'Vazir', sans-serif; background:#f8f9fc; }
        .sidebar { position:fixed; top:0; right:0; width:250px; height:100%; background:#4e73df; color:white; padding-top:20px; }
        .sidebar a { color:white; display:block; padding:12px 20px; text-decoration:none; }
        .sidebar a:hover { background:rgba(255,255,255,0.1); }
        .main-content { margin-right:250px; padding:20px; }

        .card { border:none; border-radius:10px; box-shadow:0 3px 10px rgba(0,0,0,0.08); margin-bottom:20px; }
        .stat-card { border-left:4px solid; padding:20px; }
        .stat-card.primary { border-left-color:#4e73df; }
        .stat-card.success { border-left-color:#1cc88a; }
        .stat-card.info { border-left-color:#36b9cc; }
        .stat-card.warning { border-left-color:#f6c23e; }
        .icon-circle { width:40px; height:40px; border-radius:50%; display:flex; justify-content:center; align-items:center; color:white; font-size:18px; }

        .doc-card { display:flex; align-items:center; justify-content:space-between; padding:15px; border-radius:10px; margin-bottom:15px; background:white; box-shadow:0 3px 10px rgba(0,0,0,0.05); }
        .doc-card i { font-size:24px; margin-left:15px; }
        .doc-actions a { margin-left:5px; }
        .doc-primary { border-left:4px solid #4e73df; }
        .doc-success { border-left:4px solid #1cc88a; }
        .doc-info { border-left:4px solid #36b9cc; }
        .doc-warning { border-left:4px solid #f6c23e; }

        input[type=file] { display:inline-block; max-width:200px; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="text-center mb-4">
        <h4><?= htmlspecialchars($fullName) ?></h4>
        <div class="profile-badge">نوع عضویت: <?= htmlspecialchars($memberType) ?></div>
    </div>
    <a href="dashboard.php"><i class="bi bi-speedometer2"></i> داشبورد</a>
    <a href="#"><i class="bi bi-person"></i> پروفایل</a>
    <a href="#"><i class="bi bi-building"></i> پروژه‌ها</a>
    <a href="documents.php"><i class="bi bi-file-earmark-text"></i> مدارک</a>
    <a href="#"><i class="bi bi-credit-card"></i> پرداخت‌ها</a>
    <a href="logout.php"><i class="bi bi-box-arrow-left"></i> خروج</a>
</div>

<div class="main-content">
    <h2 class="mb-4">مدارک و مستندات</h2>
    <?php if(!empty($message)): ?>
        <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php
    function renderDocCard($label, $icon, $docKey, $path, $colorClass) {
        $fileName = basename($path);
        echo '<div class="doc-card '.$colorClass.'">';
        echo '<div><i class="bi '.$icon.'"></i> '.$label.'</div>';
        echo '<div class="doc-actions">';
        if(!file_exists($path)) {
            echo '<form method="post" enctype="multipart/form-data" style="display:inline-block" action="?action=upload&docType='.$docKey.'">
                    <input type="file" name="fileToUpload" required>
                    <button type="submit" class="btn btn-success btn-sm">آپلود</button>
                  </form>';
        } else {
            echo '<a href="?action=download&file='.$fileName.'" class="btn btn-primary btn-sm"><i class="bi bi-download"></i> دانلود</a>';
            echo '<a href="?action=delete&file='.$fileName.'" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i> حذف</a>';
        }
        echo '</div></div>';
    }

    renderDocCard("شناسنامه", "bi-file-earmark-text", "BirthCertificate", $userDocs["BirthCertificate"], "doc-primary");
    renderDocCard("کارت ملی", "bi-credit-card", "NationalCard", $userDocs["NationalCard"], "doc-success");
    renderDocCard("فیش واریزی", "bi-cash-stack", "PaymentReceipt", $userDocs["PaymentReceipt"], "doc-warning");
    ?>
</div>
</body>
</html>

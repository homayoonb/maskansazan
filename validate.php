<?php
session_start();

// تنظیمات خطا (در حالت تست فعال است؛ در هاست واقعی می‌توانید display_errors را 0 کنید)
error_reporting(E_ALL);
ini_set('display_errors', 1);

function redirect_with_error($msg, $tab = 'login') {
    $_SESSION['login_error'] = $msg;
    header("Location: groupmembers.php?tab=" . urlencode((string)$tab));
    exit();
}

// بررسی متد درخواست
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_error("درخواست نامعتبر.");
}

// دریافت اطلاعات ارسالی
$national_code = trim($_POST['national_code'] ?? '');
$password      = trim($_POST['password'] ?? '');
$project_id    = trim($_POST['project_id'] ?? '');
$not_robot     = isset($_POST['not_robot']);

// اعتبارسنجی اولیه
if (empty($national_code) || empty($password) || empty($project_id)) {
    redirect_with_error("لطفاً همه فیلدها را پر کنید.");
}

if (!preg_match('/^[0-9]{10}$/', $national_code)) {
    redirect_with_error("کد ملی باید 10 رقم باشد.");
}

if (!is_numeric($project_id)) {
    redirect_with_error("پروژه انتخاب شده معتبر نیست.");
}

if (!$not_robot) {
    redirect_with_error("لطفاً تأیید کنید که ربات نیستید.");
}

// اتصال به دیتابیس
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // واکشی اطلاعات کاربر و پروژه
    $stmt = $pdo->prepare("
        SELECT pm.*, p.name AS project_name
        FROM users_project pm
        LEFT JOIN projects p ON pm.project_id = p.id
        WHERE pm.national_code = :national_code
          AND pm.project_id = :project_id
        LIMIT 1
    ");

    $stmt->execute([
        ':national_code' => $national_code,
        ':project_id' => (int)$project_id
    ]);

    $user = $stmt->fetch();

    if ($user) {
        // بررسی پسورد (در این سیستم کد ملی همزمان نقش رمز عبور را دارد)
        if ($user['national_code'] === $password) {

            $_SESSION['loggedin'] = true;

            // شناسه‌ها
            $_SESSION['id'] = $user['id'] ?? '';
            $_SESSION['user_id'] = $user['id'] ?? '';
            $_SESSION['users_project_id'] = $user['id'] ?? '';

            // اطلاعات اصلی کاربر
            $_SESSION['full_name'] = $user['full_name'] ?? '';
            $_SESSION['has_partner'] = $user['has_partner'] ?? '';
            $_SESSION['father_name'] = $user['father_name'] ?? '';
            $_SESSION['national_code'] = $user['national_code'] ?? '';
            $_SESSION['NationalCode'] = $user['national_code'] ?? '';
            $_SESSION['phone'] = $user['phone'] ?? '';

            // اطلاعات شناسنامه و تکمیلی
            $_SESSION['birth_certificate_no'] = $user['birth_certificate_no'] ?? '';
            $_SESSION['birth_date'] = $user['birth_date'] ?? '';
            $_SESSION['sana_code'] = $user['sana_code'] ?? '';
            $_SESSION['postal_code'] = $user['postal_code'] ?? '';

            // آدرس
            $_SESSION['address'] = !empty($user['address']) ? $user['address'] : 'ثبت نشده';
            $_SESSION['Address'] = !empty($user['address']) ? $user['address'] : 'ثبت نشده';

            // شرکا
            $_SESSION['partner_1'] = $user['partner_1'] ?? '';
            $_SESSION['partner_2'] = $user['partner_2'] ?? '';
            $_SESSION['partner_3'] = $user['partner_3'] ?? '';
            $_SESSION['partner_4'] = $user['partner_4'] ?? '';

            // پروژه
            $_SESSION['project_id'] = $user['project_id'] ?? '';
            $_SESSION['project_name'] = $user['project_name'] ?? 'پروژه';

            // آرایه کامل اطلاعات کاربر برای صفحات پروفایل و اعضا
            $_SESSION['users_project'] = [
                'id' => $user['id'] ?? '',
                'full_name' => $user['full_name'] ?? '',
                'has_partner' => $user['has_partner'] ?? '',
                'father_name' => $user['father_name'] ?? '',
                'national_code' => $user['national_code'] ?? '',
                'birth_certificate_no' => $user['birth_certificate_no'] ?? '',
                'birth_date' => $user['birth_date'] ?? '',
                'phone' => $user['phone'] ?? '',
                'sana_code' => $user['sana_code'] ?? '',
                'address' => !empty($user['address']) ? $user['address'] : 'ثبت نشده',
                'postal_code' => $user['postal_code'] ?? '',
                'partner_1' => $user['partner_1'] ?? '',
                'partner_2' => $user['partner_2'] ?? '',
                'partner_3' => $user['partner_3'] ?? '',
                'partner_4' => $user['partner_4'] ?? '',
                'project_id' => $user['project_id'] ?? '',
                'project_name' => $user['project_name'] ?? 'پروژه',
            ];

            unset($_SESSION['login_error']);

            header('Location: dashboard.php');
            exit();
        }

        redirect_with_error("رمز عبور اشتباه است.");
    } else {
        // بررسی اینکه آیا کاربر با این کد ملی در پروژه دیگری عضو است
        $stmt2 = $pdo->prepare("
            SELECT p.name
            FROM users_project pm
            JOIN projects p ON pm.project_id = p.id
            WHERE pm.national_code = :national_code
        ");

        $stmt2->execute([':national_code' => $national_code]);
        $projects = $stmt2->fetchAll(PDO::FETCH_COLUMN);

        if (count($projects) > 0) {
            $projects_list = implode('، ', $projects);
            redirect_with_error("به این پروژه دسترسی ندارید. شما در پروژه‌های زیر عضو هستید: " . $projects_list);
        } else {
            var_dump($national_code);
            redirect_with_error("کاربری با این کد ملی یافت نشد.");
        }
    }

} catch (PDOException $e) {
    error_log("Login system error: " . $e->getMessage());
    redirect_with_error("خطا در برقراری ارتباط با پایگاه داده. لطفاً مجدداً تلاش کنید.");
}
?>

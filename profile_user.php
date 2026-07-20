<?php
// شروع ایمن سشن در صورتی که قبلاً فعال نشده باشد
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ۱. اتصال به دیتابیس
require_once 'config/Database.php';

// بررسی لاگین بودن کاربر
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: groupmembers.php');
    exit;
}

// ۲. دریافت ID کاربر از URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$memberName = isset($_GET['name']) ? trim($_GET['name']) : '';

if ($id <= 0) {
    // در صورت مشخص نبودن ID، به عنوان پیش‌فرض مشخصات خود کاربر لاگین شده نمایش داده می‌شود
    $id = (int)($_SESSION['users_project_id'] ?? $_SESSION['id'] ?? 0);
}

if ($id <= 0) {
    echo "<pre>پارامترهای دریافتی در URL: ";
    print_r($_GET);
    echo "</pre>";
    die("شناسه کاربر معتبر نیست.");
}

// ۳. کوئری برای گرفتن اطلاعات کاربر
try {
    if (!isset($pdo)) {
        $pdo = new PDO("mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4", "root", "", [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }

    $stmt = $pdo->prepare("
                            SELECT up.*, p.name 
                            FROM users_project AS up
                            INNER JOIN projects AS p ON p.id = up.project_id
                            WHERE up.id = ? 
                            LIMIT 1
                        ");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        die("کاربر مورد نظر یافت نشد.");
    }

    /*
     * بررسی امنیت دسترسی:
     * کاربر لاگین شده فقط مجاز به دیدن اطلاعات خود یا هم‌گروهی‌های خود در همان پروژه است.
     * (مگر اینکه نقش کاربر جاری مدیریت/admin باشد)
     */
    $logged_in_project_id = $_SESSION['project_id'] ?? null;
    $user_role = $_SESSION['role'] ?? 'member'; // فرض بر این است که نقش پیش‌فرض عضو است

    if ($user_role !== 'admin' && $logged_in_project_id !== null) {
        if ((int)$user['project_id'] !== (int)$logged_in_project_id) {
            die("خطای امنیتی: شما مجاز به مشاهده مشخصات اعضای پروژه‌های دیگر نیستید.");
        }
    }

} catch (PDOException $e) {
    die("خطا در پایگاه داده: " . $e->getMessage());
}

// بررسی نام ستون آدرس در دیتابیس (پشتیبانی از هر دو حالت حروف کوچک و بزرگ)
$userAddress = $user['address'] ?? $user['Address'] ?? '';
?>
<div class="profile-badge table-responsive">
    <div class="profile-table-container">
        <div class="table-responsive">
            <table class="table table-hover align-middle custom-profile-table">
                <thead>
                <tr>
                    <th>نام و نام خانوادگی</th>
                    <th>شریک</th>
                    <th>نام پدر</th>
                    <th>کد ملی</th>
                    <th>تاریخ تولد</th>
                    <th>آدرس</th>
                    <th>کد پستی</th>
                    <th>شرکا</th>
                    <th>نام پروژه</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><?= htmlspecialchars($user['full_name'] ?? '-') ?></td>
                    <td>
                        <span class="badge <?= ($user['has_partner'] ?? '') === 'دارد' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?> px-3 py-2 rounded-pill">
                            <?= htmlspecialchars($user['has_partner'] ?? 'ندارد') ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($user['father_name'] ?? '-') ?></td>
                    <td class="font-digit"><?= htmlspecialchars($user['national_code'] ?? '-') ?></td>
                    <td class="font-digit"><?= htmlspecialchars($user['birth_date'] ?? '-') ?></td>
                    <td class="text-wrap" style="max-width: 250px;"><?= htmlspecialchars($user['address'] ?? '-') ?></td>
                    <td class="font-digit"><?= htmlspecialchars($user['postal_code'] ?? '-') ?></td>
                    <td>
                        <?php if (($user['has_partner'] ?? '') === 'دارد'): ?>
                            <?php
                            $partners = [
                                    $user['partner_1'] ?? '',
                                    $user['partner_2'] ?? '',
                                    $user['partner_3'] ?? '',
                                    $user['partner_4'] ?? ''
                            ];
                            $partners = array_filter($partners, 'trim');
                            ?>
                            <?php if (!empty($partners)): ?>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($partners as $partner): ?>
                                        <span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($partner) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted small">ثبت نشده</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted small">ندارد</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded">
                            <?= htmlspecialchars($user['name'] ?? 'تعیین نشده') ?>
                        </span>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>


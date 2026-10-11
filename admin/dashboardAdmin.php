<?php
// admin/dashboardAdmin.php - نسخه نهایی اصلاح شده بدون خطا
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// تنظیمات سشن
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}
require_once 'helpers.php'; // اضافه کردن فایل توابع
// تنظیم هدرهای HTTP برای کدگذاری UTF-8
header('Content-Type: text/html; charset=utf-8');

/* اتصال دیتابیس */
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
    die("خطا در اتصال دیتابیس");
}
ob_start('ob_gzhandler');

// ========== توابع کمکی ==========
if (!function_exists('generatePagination')) {
    function generatePagination($totalItems, $itemsPerPage, $currentPage, $url) {
        $totalPages = ceil($totalItems / $itemsPerPage);
        if ($totalPages <= 1) return '';

        $pagination = '<nav><ul class="pagination justify-content-center">';

        // دکمه قبلی
        if ($currentPage > 1) {
            $prevUrl = $url . (strpos($url, '?') !== false ? '&' : '?') . 'page=' . ($currentPage - 1);
            $pagination .= '<li class="page-item"><a class="page-link" href="' . $prevUrl . '">&laquo; قبلی</a></li>';
        }

        // صفحات
        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);

        for ($i = $startPage; $i <= $endPage; $i++) {
            $active = ($i == $currentPage) ? ' active' : '';
            $pageUrl = $url . (strpos($url, '?') !== false ? '&' : '?') . 'page=' . $i;

            $pagination .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $pageUrl . '">' . $i . '</a></li>';
        }

        // دکمه بعدی
        if ($currentPage < $totalPages) {
            $nextUrl = $url . (strpos($url, '?') !== false ? '&' : '?') . 'page=' . ($currentPage + 1);
            $pagination .= '<li class="page-item"><a class="page-link" href="' . $nextUrl . '">بعدی &raquo;</a></li>';
        }

        $pagination .= '</ul></nav>';
        return $pagination;
    }
}
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time']) || time() - $_SESSION['csrf_token_time'] > 3600) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('safeEcho')) {
    function safeEcho($value) {
        if ($value === null || $value === '') {
            return '';
        }
        
        // لیست encoding‌های ایمن
        $encodings = ['UTF-8', 'ASCII', 'ISO-8859-1'];
        
        // اضافه کردن encoding‌های اضافی اگر پشتیبانی می‌شوند
        if (function_exists('mb_list_encodings')) {
            $supportedEncodings = mb_list_encodings();
            if (in_array('Windows-1256', $supportedEncodings)) {
                $encodings[] = 'Windows-1256';
            }
            if (in_array('CP1256', $supportedEncodings)) {
                $encodings[] = 'CP1256';
            }
        }
        
        // استفاده از @ برای جلوگیری از خطای mb_detect_encoding
        $encoding = @mb_detect_encoding($value, $encodings, true);
        
        if ($encoding === false || $encoding === 'UTF-8') {
            $cleanValue = $value;
        } else {
            $cleanValue = @mb_convert_encoding($value, 'UTF-8', $encoding);
        }
        
        $cleanValue = preg_replace('/^\xEF\xBB\xBF/', '', $cleanValue);
        return htmlspecialchars($cleanValue, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('getUserStatusBadge')) {
    function getUserStatusBadge($main_member) {
        if ($main_member === 'اصلی' || $main_member === 'main') {
            return '<span class="badge bg-success">اصلی</span>';
        } else {
            return '<span class="badge bg-secondary">عضو</span>';
        }
    }
}

if (!function_exists('getStatusBadge')) {
    function getStatusBadge($status) {
        if (empty($status)) {
            return '<span class="badge bg-secondary">نامشخص</span>';
        }
        
        $statusLower = strtolower(trim($status));
        switch($statusLower) {
            case 'active':
            case 'approved':
            case 'verified':
                return '<span class="badge bg-success">تأیید شده</span>';
            case 'pending':
            case 'open':
                return '<span class="badge bg-warning">در انتظار</span>';
            case 'rejected':
            case 'closed':
                return '<span class="badge bg-danger">رد شده</span>';
            case 'inactive':
                return '<span class="badge bg-secondary">غیرفعال</span>';
            default:
                return '<span class="badge bg-info">' . safeEcho($status) . '</span>';
        }
    }
}

// تابع اصلاح مسیر تصاویر (نسخه ساده‌تر و ایمن)
if (!function_exists('fixImagePath')) {
    function fixImagePath($imagePath) {
        if (empty($imagePath) || trim($imagePath) === '') {
            return '';
        }
        
        $imagePath = trim($imagePath);
        
        // اگر آدرس کامل است، برگردان
        if (strpos($imagePath, 'http://') === 0 || strpos($imagePath, 'https://') === 0) {
            return $imagePath;
        }
        
        // حذف BOM اگر وجود دارد
        $imagePath = preg_replace('/^\xEF\xBB\xBF/', '', $imagePath);
        
        // اطمینان از شروع با /
        if (strpos($imagePath, '/') !== 0) {
            $imagePath = '/' . $imagePath;
        }
        
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
        
        // لیست مسیرهای ممکن برای بررسی
        $possiblePaths = [
            $imagePath,
            '/uploads' . $imagePath,
            '/public/uploads' . $imagePath,
            '/home/maskanhb/public_html' . $imagePath,
            str_replace('//', '/', '/home/maskanhb/public_html' . $imagePath)
        ];
        
        foreach ($possiblePaths as $path) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . $path;
            if (file_exists($fullPath)) {
                return $baseUrl . $path;
            }
        }
        
        // اگر فایل پیدا نشد، آدرس نسبی برگردانده شود
        return $baseUrl . $imagePath;
    }
}

// توابع جدید برای صفحه‌بندی و مرتب‌سازی
if (!function_exists('generatePagination')) {
    function generatePagination($totalItems, $itemsPerPage, $currentPage, $url) {
        $totalPages = ceil($totalItems / $itemsPerPage);
        if ($totalPages <= 1) return '';

        $pagination = '<nav><ul class="pagination justify-content-center">';

        // دکمه قبلی
        if ($currentPage > 1) {
            $prevUrl = $url . (strpos($url, '?') !== false ? '&' : '?') . 'page=' . ($currentPage - 1);
            $pagination .= '<li class="page-item"><a class="page-link" href="' . $prevUrl . '">&laquo; قبلی</a></li>';
        }

        // صفحات
        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);

        for ($i = $startPage; $i <= $endPage; $i++) {
            $active = ($i == $currentPage) ? ' active' : '';
            $pageUrl = $url . (strpos($url, '?') !== false ? '&' : '?') . 'page=' . $i;

            $pagination .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $pageUrl . '">' . $i . '</a></li>';
        }

        // دکمه بعدی
        if ($currentPage < $totalPages) {
            $nextUrl = $url . (strpos($url, '?') !== false ? '&' : '?') . 'page=' . ($currentPage + 1);
            $pagination .= '<li class="page-item"><a class="page-link" href="' . $nextUrl . '">بعدی &raquo;</a></li>';
        }

        $pagination .= '</ul></nav>';
        return $pagination;
    }
}

// تابع فرمت حجم فایل
if (!function_exists('formatFileSize')) {
    function formatFileSize($bytes) {
        if ($bytes == 0) return '0 بایت';
        $k = 1024;
        $sizes = ['بایت', 'کیلوبایت', 'مگابایت', 'گیگابایت'];
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
}

// تابع اصلاح متن فارسی (نسخه ایمن)
if (!function_exists('fixPersianText')) {
    function fixPersianText($text) {
        if (empty($text)) return '';
        
        // اگر متن UTF-8 است، مستقیم برگردان
        if (mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }
        
        // لیست encoding‌های ایمن
        $encodings = ['UTF-8', 'ASCII', 'ISO-8859-1'];
        
        // اضافه کردن encoding‌های اضافی اگر پشتیبانی می‌شوند
        if (function_exists('mb_list_encodings')) {
            $supportedEncodings = mb_list_encodings();
            if (in_array('Windows-1256', $supportedEncodings)) {
                $encodings[] = 'Windows-1256';
            }
            if (in_array('CP1256', $supportedEncodings)) {
                $encodings[] = 'CP1256';
            }
        }
        
        // تشخیص کدگذاری
        $encoding = @mb_detect_encoding($text, $encodings, true);
        
        if ($encoding === false || $encoding === 'UTF-8') {
            return $text;
        }
        
        try {
            $converted = @mb_convert_encoding($text, 'UTF-8', $encoding);
            return $converted ?: $text;
        } catch (Exception $e) {
            return $text;
        }
    }
}

// بررسی ورود کاربر
if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_username'])) {
    header('Location: loginAdmin.php');
    exit;
}

// ========== اتصال امن به دیتابیس ==========
class SafeDatabase {
    private static $connection = null;
    
    public static function connect() {
        if (self::$connection === null) {
            $DB_HOST = 'localhost';
            $DB_NAME = 'maskanhb_maskansazan';
            $DB_USER = 'root';
            $DB_PASS = '';
            
            self::$connection = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
            
            if (self::$connection->connect_error) {
                error_log("خطا در اتصال به دیتابیس: " . self::$connection->connect_error);
                return false;
            }
            
            // تنظیم کدگذاری UTF-8
            self::$connection->set_charset("utf8mb4");
            self::$connection->query("SET NAMES 'utf8mb4'");
            self::$connection->query("SET CHARACTER SET 'utf8mb4'");
            self::$connection->query("SET character_set_connection = 'utf8mb4'");
            self::$connection->query("SET character_set_client = 'utf8mb4'");
            self::$connection->query("SET character_set_results = 'utf8mb4'");
            self::$connection->query("SET COLLATION_CONNECTION = 'utf8mb4_persian_ci'");
        }
        
        return self::$connection;
    }
    
    public static function close() {
        if (self::$connection !== null) {
            self::$connection->close();
            self::$connection = null;
        }
    }
}

// اتصال به دیتابیس
$mysqli = SafeDatabase::connect();
if (!$mysqli) {
    die("خطا در اتصال به پایگاه داده. لطفاً با مدیر سیستم تماس بگیرید.");
}

// CSRF token
$csrfToken = generate_csrf_token();

// بررسی وجود کاربر در دیتابیس
$stmt = $mysqli->prepare("SELECT id, username, status FROM admins WHERE id = ? LIMIT 1");
if (!$stmt) {
    die("خطا در آماده‌سازی کوئری");
}

$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$admin) {
    session_destroy();
    header('Location: loginAdmin.php');
    exit;
}

// بررسی وضعیت کاربر
$status = strtolower($admin['status'] ?? '');
if ($status !== 'active') {
    session_destroy();
    header('Location: loginAdmin.php');
    exit;
}

// ========== پارامترهای جستجو و فیلتر ==========
// تعیین تب فعال
$activeTab = isset($_GET['tab']) ? htmlspecialchars($_GET['tab']) : 'tickets';

// پارامترهای فیلتر
$selectedProjectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$userStatusFilter = isset($_GET['user_status']) ? htmlspecialchars($_GET['user_status']) : '';
$ticketStatusFilter = isset($_GET['ticket_status']) ? htmlspecialchars($_GET['ticket_status']) : '';
$memberStatusFilter = isset($_GET['member_status']) ? htmlspecialchars($_GET['member_status']) : '';
$searchQuery = isset($_GET['search']) ? trim(htmlspecialchars($_GET['search'])) : '';

// پارامترهای مرتب‌سازی و صفحه‌بندی
$itemsPerPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 15;
if (!in_array($itemsPerPage, [10, 15, 20, 50, 100])) {
    $itemsPerPage = 15;
}

$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// مرتب‌سازی
$sortField = isset($_GET['sort']) ? htmlspecialchars($_GET['sort']) : 'id';
$sortOrder = isset($_GET['order']) ? strtoupper(htmlspecialchars($_GET['order'])) : 'DESC';

// لیست فیلدهای مجاز برای مرتب‌سازی
$allowedSortFields = ['id', 'created_at', 'updated_at', 'first_name', 'last_name', 'national_id', 'mobile', 'title', 'status'];
if (!in_array($sortField, $allowedSortFields)) {
    $sortField = 'id';
}

if (!in_array($sortOrder, ['ASC', 'DESC'])) {
    $sortOrder = 'DESC';
}
// ========== گرفتن لیست پروژه‌ها ==========
$projects = [];
$projectsRes = $mysqli->query("SELECT id, name FROM projects ORDER BY id DESC");
if ($projectsRes) {
    while ($row = $projectsRes->fetch_assoc()) {
        $row['name'] = fixPersianText($row['name']);
        $projects[] = $row;
    }
}

// ========== کوئری اعضای سایت ==========
$membersArray = [];
$totalMembers = 0;
$totalMemberPages = 1;

if ($activeTab === 'members') {
    // دریافت پارامترها
    $currentPage = $_GET['page'] ?? 1;
    $currentPage = max(1, (int)$currentPage);
    
    $itemsPerPage = $_GET['per_page'] ?? 20;
    $itemsPerPage = max(1, min(100, (int)$itemsPerPage));
    
    $sortField = $_GET['sort'] ?? 'created_at';
    $sortOrder = $_GET['order'] ?? 'DESC';
    
    $memberStatusFilter = $_GET['member_status'] ?? '';
    $searchQuery = $_GET['search'] ?? '';
    
    // اعتبارسنجی مرتب‌سازی
    $allowedSortFields = ['id', 'mobile', 'status', 'created_at', 'first_name', 'last_name'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'created_at';
    }
    
    $sortOrder = strtoupper($sortOrder);
    if (!in_array($sortOrder, ['ASC', 'DESC'])) {
        $sortOrder = 'DESC';
    }
    
    // ساخت کوئری پایه
    $membersQueryBase = "SELECT 
        id,
        first_name,
        last_name,
        national_id,
        birth_certificate_number,
        father_name,
        birth_date,
        birth_date_fa,
        mobile,
        phone,
        address,
        national_card_image,
        birth_certificate_image,
        check1_image,
        check2_image,
        check3_image,
        check1_number,
        check2_number,
        check3_number,
        check1_amount,
        check2_amount,
        check3_amount,
        check1_bank,
        check2_bank,
        check3_bank,
        check1_date,
        check2_date,
        check3_date,
        registration_date,
        ip_address,
        status,
        created_at,
        updated_at
    FROM members WHERE 1=1";

    $membersQueryCount = "SELECT COUNT(*) FROM members WHERE 1=1";

    $memberParams = [];
    $memberTypes = '';
    $memberWhereConditions = [];

    // فیلتر وضعیت
    if ($memberStatusFilter && in_array($memberStatusFilter, ['pending', 'approved', 'rejected'])) {
        $memberWhereConditions[] = "status = ?";
        $memberParams[] = $memberStatusFilter;
        $memberTypes .= 's';
    }

    // فیلتر جستجو
    if (!empty($searchQuery)) {
        $searchParam = "%$searchQuery%";
        $memberWhereConditions[] = "(
            first_name LIKE ? OR 
            last_name LIKE ? OR 
            national_id LIKE ? OR 
            mobile LIKE ? OR 
            father_name LIKE ?
        )";
        
        array_push($memberParams, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
        $memberTypes .= 'sssss';
    }

    // اضافه کردن شرایط
    if (!empty($memberWhereConditions)) {
        $whereSQL = " AND " . implode(' AND ', $memberWhereConditions);
        $membersQueryBase .= $whereSQL;
        $membersQueryCount .= $whereSQL;
    }

    // گرفتن تعداد کل
    $totalMembers = getTotalCount($mysqli, $membersQueryCount, $memberParams, $memberTypes);
    
    // محاسبه صفحات
    $totalMemberPages = ceil($totalMembers / $itemsPerPage);
    
    // تنظیم صفحه جاری
    if ($currentPage > $totalMemberPages && $totalMemberPages > 0) {
        $currentPage = $totalMemberPages;
    }
    
    // محاسبه offset
    $offset = ($currentPage - 1) * $itemsPerPage;
    $offset = max(0, $offset);
    
    // مرتب‌سازی
    $sortSQL = " ORDER BY " . $sortField . " " . $sortOrder;
    
    // صفحه‌بندی
    $paginationSQL = " LIMIT ? OFFSET ?";
    
    // کوئری نهایی
    $membersQuery = $membersQueryBase . $sortSQL . $paginationSQL;
    
    // اضافه کردن پارامترهای صفحه‌بندی
    $memberParams[] = $itemsPerPage;
    $memberParams[] = $offset;
    $memberTypes .= 'ii';
    
    // گرفتن داده‌ها
    $membersArray = fetchMembers($mysqli, $membersQuery, $memberParams, $memberTypes);
    
    // اگر خطا رخ داد
    if ($membersArray === false) {
        $membersArray = [];
        error_log("خطا در دریافت اعضا");
    }
}

// ========== کوئری تیکت‌ها ==========
$ticketsArray = [];
$totalTickets = 0;
$totalTicketPages = 1;

if ($activeTab === 'tickets') {
    $ticketsQueryBase = "
        SELECT 
            t.id,
            t.user_name AS display_name,
            t.title,
            t.status,
            t.created_at,
            p.name AS project_name
        FROM tickets t 
        LEFT JOIN projects p ON t.project_id = p.id 
        WHERE 1=1
    ";

    $ticketsQueryCount = "
        SELECT COUNT(*) as total
        FROM tickets t 
        LEFT JOIN projects p ON t.project_id = p.id 
        WHERE 1=1
    ";
// پارامترهای فیلتر عمومی
$selectedProjectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$userStatusFilter = isset($_GET['user_status']) ? htmlspecialchars($_GET['user_status']) : '';
$ticketStatusFilter = isset($_GET['ticket_status']) ? htmlspecialchars($_GET['ticket_status']) : '';
$memberStatusFilter = isset($_GET['member_status']) ? htmlspecialchars($_GET['member_status']) : '';
$searchQuery = isset($_GET['search']) ? trim(htmlspecialchars($_GET['search'])) : '';

// پارامترهای مرتب‌سازی و صفحه‌بندی عمومی
$itemsPerPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 15;
if (!in_array($itemsPerPage, [10, 15, 20, 50, 100])) {
    $itemsPerPage = 15;
}

$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// مرتب‌سازی عمومی
$sortField = isset($_GET['sort']) ? htmlspecialchars($_GET['sort']) : 'id';
$sortOrder = isset($_GET['order']) ? strtoupper(htmlspecialchars($_GET['order'])) : 'DESC';

// لیست فیلدهای مجاز برای مرتب‌سازی
$allowedSortFields = ['id', 'created_at', 'updated_at', 'first_name', 'last_name', 'national_id', 'mobile', 'title', 'status'];
if (!in_array($sortField, $allowedSortFields)) {
    $sortField = 'id';
}

if (!in_array($sortOrder, ['ASC', 'DESC'])) {
    $sortOrder = 'DESC';
}

// ========== پارامترهای تیکت‌ها (اضافه شده) ==========
$ticketSearchQuery = isset($_GET['ticket_search']) ? trim(htmlspecialchars($_GET['ticket_search'])) : '';
$ticketSortField = isset($_GET['ticket_sort']) ? htmlspecialchars($_GET['ticket_sort']) : 'created_at';
$ticketSortOrder = isset($_GET['ticket_order']) ? htmlspecialchars($_GET['ticket_order']) : 'DESC';
$ticketPage = isset($_GET['ticket_page']) ? max(1, intval($_GET['ticket_page'])) : 1;
$ticketsPerPage = isset($_GET['ticket_per_page']) ? intval($_GET['ticket_per_page']) : 10;
    $ticketParams = [];
    $ticketTypes = '';
    $ticketConditions = [];

    if ($selectedProjectId > 0) {
        $ticketConditions[] = "t.project_id = ?";
        $ticketParams[] = $selectedProjectId;
        $ticketTypes .= 'i';
    }

    if ($ticketStatusFilter && in_array($ticketStatusFilter, ['open', 'answered', 'closed'])) {
        $ticketConditions[] = "t.status = ?";
        $ticketParams[] = $ticketStatusFilter;
        $ticketTypes .= 's';
    }

    if (!empty($searchQuery)) {
        $searchParam = "%$searchQuery%";
        $ticketConditions[] = "(
            t.title LIKE ? OR 
            t.description LIKE ? OR 
            t.user_name LIKE ?
        )";
        $ticketParams[] = $searchParam;
        $ticketParams[] = $searchParam;
        $ticketParams[] = $searchParam;
        $ticketTypes .= 'sss';
    }

    // اضافه کردن شرایط
    if (!empty($ticketConditions)) {
        $whereSQL = " AND " . implode(' AND ', $ticketConditions);
        $ticketsQueryBase .= $whereSQL;
        $ticketsQueryCount .= $whereSQL;
    }

    // گرفتن تعداد کل تیکت‌ها
    $stmt = $mysqli->prepare($ticketsQueryCount);
    if ($stmt) {
        if (!empty($ticketParams)) {
            $stmt->bind_param($ticketTypes, ...$ticketParams);
        }
        $stmt->execute();
        $countResult = $stmt->get_result();
        if ($countResult) {
            $totalTickets = $countResult->fetch_assoc()['total'] ?? 0;
        }
        $stmt->close();
    }

    // محاسبه تعداد صفحات
    $totalTicketPages = ceil($totalTickets / $itemsPerPage);

    // مرتب‌سازی و صفحه‌بندی برای تیکت‌ها
    $ticketsQuery = $ticketsQueryBase . " ORDER BY " . $sortField . " " . $sortOrder . " LIMIT ? OFFSET ?";

    $ticketPdoParams = array_merge($ticketParams, [$itemsPerPage, $offset]);
    $ticketPdoTypes = $ticketTypes . "ii";

    $stmt = $mysqli->prepare($ticketsQuery);
    if ($stmt) {
        if (!empty($ticketPdoTypes)) {
            $stmt->bind_param($ticketPdoTypes, ...$ticketPdoParams);
        }
        $stmt->execute();
        $ticketsResult = $stmt->get_result();
        if ($ticketsResult) {
            while ($row = $ticketsResult->fetch_assoc()) {
                $row['display_name'] = fixPersianText($row['display_name']);
                $row['title'] = fixPersianText($row['title']);
                $row['project_name'] = fixPersianText($row['project_name']);
                $ticketsArray[] = $row;
            }
        }
        $stmt->close();
    }
}


// ========== کوئری کاربران پروژه‌ها ==========
$usersArray = [];
$usersQuery = "SELECT 
    id, 
    CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as display_name,
    COALESCE(national_code, '') as national_code, 
    COALESCE(phone, '') as phone, 
    COALESCE(project_id, 0) as project_id,
    COALESCE(main_member, '') as main_member
FROM project_members 
WHERE 1=1";

$userParams = [];
$userTypes = '';

if ($selectedProjectId > 0) {
    $usersQuery .= " AND project_id = ?";
    $userParams[] = $selectedProjectId;
    $userTypes .= 'i';
}

// فیلتر وضعیت کاربر
if ($userStatusFilter && in_array($userStatusFilter, ['active', 'inactive'])) {
    if ($userStatusFilter === 'active') {
        $usersQuery .= " AND (main_member = 'اصلی' OR main_member = 'main')";
    } else {
        $usersQuery .= " AND (main_member != 'اصلی' AND main_member != 'main')";
    }
}

$usersQuery .= " ORDER BY id DESC LIMIT 100";

// اجرای کوئری کاربران
if (!empty($userParams)) {
    $stmt = $mysqli->prepare($usersQuery);
    if ($stmt) {
        $stmt->bind_param($userTypes, ...$userParams);
        $stmt->execute();
        $usersResult = $stmt->get_result();
        if ($usersResult) {
            while ($row = $usersResult->fetch_assoc()) {
                $row['display_name'] = fixPersianText($row['display_name']);
                $usersArray[] = $row;
            }
        }
        $stmt->close();
    }
} else {
    $usersResult = $mysqli->query($usersQuery);
    if ($usersResult) {
        while ($row = $usersResult->fetch_assoc()) {
            $row['display_name'] = fixPersianText($row['display_name']);
            $usersArray[] = $row;
        }
    }
}

// ========== کوئری چک‌ها ==========
$chequesArray = [];
$chequesQuery = "
SELECT *
FROM cheques_transactions
ORDER BY cheque_date DESC, id DESC
LIMIT 100;
";

$chequesResult = $mysqli->query($chequesQuery);
if ($chequesResult) {
    while ($row = $chequesResult->fetch_assoc()) {
        $row['member_name'] = fixPersianText($row['member_name'] ?? '');
        $row['account_holder_name'] = fixPersianText($row['account_holder_name'] ?? '');
        $row['receiver_name'] = fixPersianText($row['receiver_name'] ?? '');
        $row['description'] = fixPersianText($row['description'] ?? '');
        $chequesArray[] = $row;
    }
}

// ===== محاسبه آمار =====
$stats = [
    'total_cheques' => count($chequesArray),
    'total_amount' => array_sum(array_column($chequesArray, 'amount')),
    'overdue_count' => 0,
    'upcoming_count' => 0,
    'avg_amount' => 0
];

$today = date('Y-m-d'); // فرمت YYYY-MM-DD فرض شده
foreach ($chequesArray as $cheque) {
    $chequeDate = $cheque['cheque_date'];
    if ($chequeDate < $today) {
        $stats['overdue_count']++;
    } else {
        $stats['upcoming_count']++;
    }
}

$stats['avg_amount'] = $stats['total_cheques'] > 0 ? $stats['total_amount'] / $stats['total_cheques'] : 0;


$statsRes = $mysqli->query("
    SELECT 
        COUNT(*) as total_cheques,
        SUM(COALESCE(amount, 0)) as total_amount,
        SUM(CASE WHEN cheque_date < CURDATE() THEN 1 ELSE 0 END) as overdue_count,
        SUM(CASE WHEN cheque_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_count,
        AVG(COALESCE(amount, 0)) as avg_amount
    FROM cheque_transactions
");

if ($statsRes) {
    $row = $statsRes->fetch_assoc();
    if ($row) {
        $stats = $row;
    }
}

// ========== مدارک کاربران ==========
$documentsArray = [];
$user_info = [];
$totalDocuments = 0;
$totalDocPages = 1;
$usersList = [];
$totalUsers = 0;
$totalUserPages = 1;

$documentsArray = [];
$user_info = [];
$totalDocuments = 0;
$totalDocPages = 1;
$usersList = [];
$totalUsers = 0;
$totalUserPages = 1;

// تعریف متغیر $docStatusFilter با مقدار پیش‌فرض
$docStatusFilter = isset($_GET['doc_status']) ? htmlspecialchars($_GET['doc_status']) : '';

if ($activeTab === 'documents' && isset($_GET['user_id']) && ctype_digit($_GET['user_id'])) {

    $user_id = (int)$_GET['user_id'];

    /* ---------- صفحه‌بندی ---------- */
    $docItemsPerPage = isset($_GET['doc_per_page']) ? (int)$_GET['doc_per_page'] : 10;
    if (!in_array($docItemsPerPage, [10, 20, 50], true)) {
        $docItemsPerPage = 10;
    }

    $docCurrentPage = isset($_GET['doc_page']) ? max(1, (int)$_GET['doc_page']) : 1;
    $docOffset = ($docCurrentPage - 1) * $docItemsPerPage;

    /* ---------- مرتب‌سازی ---------- */
    $allowedDocSortFields = ['id', 'document_type', 'upload_date', 'verification_status'];
    $docSortField = $_GET['doc_sort'] ?? 'upload_date';
    if (!in_array($docSortField, $allowedDocSortFields, true)) {
        $docSortField = 'upload_date';
    }

    $docSortOrder = strtoupper($_GET['doc_order'] ?? 'DESC');
    if (!in_array($docSortOrder, ['ASC', 'DESC'], true)) {
        $docSortOrder = 'DESC';
    }

    /* ---------- فیلتر وضعیت ---------- */
    $docStatusFilter = $_GET['doc_status'] ?? '';
    $allowedStatuses = ['pending', 'verified', 'rejected'];

    /* ---------- کوئری پایه ---------- */
    $docQueryBase = "
        SELECT 
            d.id,
            d.document_type,
            d.file_path AS document_path,
            COALESCE(d.verification_status, 'pending') AS verification_status,
            d.upload_date,
            d.description,
            d.file_size,
            pm.first_name,
            pm.last_name,
            pm.national_code,
            pm.phone
        FROM user_documents d
        LEFT JOIN project_members pm ON d.user_id = pm.id
        WHERE d.user_id = ?
    ";

    $docQueryCount = "
        SELECT COUNT(*) AS total
        FROM user_documents
        WHERE user_id = ?
    ";

    $docParams = [$user_id];
    $docTypes  = 'i';

    /* ---------- اعمال فیلتر وضعیت ---------- */
    if (in_array($docStatusFilter, $allowedStatuses, true)) {
        $docQueryBase  .= " AND d.verification_status = ?";
        $docQueryCount .= " AND verification_status = ?";
        $docParams[] = $docStatusFilter;
        $docTypes   .= 's';
    }

    /* ---------- تعداد کل ---------- */
    $stmt = $mysqli->prepare($docQueryCount);
    $stmt->bind_param($docTypes, ...$docParams);
    $stmt->execute();
    $totalDocuments = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    $totalDocPages = (int)ceil($totalDocuments / $docItemsPerPage);

    /* ---------- مرتب‌سازی + صفحه‌بندی ---------- */
    $docQueryBase .= " ORDER BY d.$docSortField $docSortOrder LIMIT ? OFFSET ?";
    $docParams[] = $docItemsPerPage;
    $docParams[] = $docOffset;
    $docTypes   .= 'ii';

    /* ---------- اجرای نهایی ---------- */
    $stmt = $mysqli->prepare($docQueryBase);
    $stmt->bind_param($docTypes, ...$docParams);
    $stmt->execute();

    $documentsArray = [];
    $docResult = $stmt->get_result();
    while ($doc = $docResult->fetch_assoc()) {
        $doc['document_path'] = fixImagePath2($doc['document_path']);
        $doc['document_type'] = fixPersianText($doc['document_type']);
        $doc['description']   = fixPersianText($doc['description']);
        $doc['first_name']    = fixPersianText($doc['first_name']);
        $doc['last_name']     = fixPersianText($doc['last_name']);
        $documentsArray[] = $doc;
    }
    $stmt->close();
}  elseif ($activeTab === 'documents' && !isset($_GET['user_id'])) {
    // پارامترهای جستجو، صفحه‌بندی و مرتب‌سازی برای لیست کاربران
    $userSearchQuery = isset($_GET['search']) ? trim(htmlspecialchars($_GET['search'])) : '';
    $userCurrentPage = isset($_GET['user_page']) ? max(1, (int)$_GET['user_page']) : 1;
    $userItemsPerPage = isset($_GET['user_per_page']) ? (int)$_GET['user_per_page'] : 20;
    if (!in_array($userItemsPerPage, [10, 20, 50, 100])) {
        $userItemsPerPage = 20;
    }
    $userOffset = ($userCurrentPage - 1) * $userItemsPerPage;
    
    // پارامترهای مرتب‌سازی لیست کاربران
    $userSortField = isset($_GET['user_sort']) ? htmlspecialchars($_GET['user_sort']) : 'id';
    $userSortOrder = isset($_GET['user_order']) ? strtoupper(htmlspecialchars($_GET['user_order'])) : 'DESC';
    
    $allowedUserSortFields = ['id', 'first_name', 'last_name', 'national_code', 'phone', 'document_count'];
    if (!in_array($userSortField, $allowedUserSortFields)) {
        $userSortField = 'id';
    }
    
    if (!in_array($userSortOrder, ['ASC', 'DESC'])) {
        $userSortOrder = 'DESC';
    }
    
    // فیلتر وضعیت کاربر (اصلی/عضو)
    $userMainFilter = isset($_GET['user_main']) ? htmlspecialchars($_GET['user_main']) : '';
    
    // کوئری پایه برای گرفتن کاربران با تعداد مدارک
    $userQueryBase = "
        SELECT 
            pm.id,
            pm.first_name,
            pm.last_name,
            CONCAT(COALESCE(pm.first_name, ''), ' ', COALESCE(pm.last_name, '')) as display_name,
            COALESCE(pm.national_code, '') as national_code,
            COALESCE(pm.phone, '') as phone,
            COALESCE(pm.main_member, '') as main_member,
            COALESCE((SELECT COUNT(*) FROM user_documents WHERE user_id = pm.id), 0) as document_count,
            COALESCE((SELECT MAX(upload_date) FROM user_documents WHERE user_id = pm.id), NULL) as last_document_date
        FROM project_members pm 
        WHERE 1=1
    ";
    
    $userQueryCount = "SELECT COUNT(*) as total FROM project_members pm WHERE 1=1";
    
    $userParams = [];
    $userTypes = '';
    $userConditions = [];
    
    // فیلتر جستجو
    if (!empty($userSearchQuery)) {
        $searchParam = "%$userSearchQuery%";
        $userConditions[] = "(
            pm.first_name LIKE ? OR 
            pm.last_name LIKE ? OR 
            pm.national_code LIKE ? OR 
            pm.phone LIKE ? OR
            CONCAT(COALESCE(pm.first_name, ''), ' ', COALESCE(pm.last_name, '')) LIKE ?
        )";
        array_push($userParams, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
        $userTypes .= 'sssss';
    }
    
    // فیلتر وضعیت (اصلی/عضو)
    if ($userMainFilter === 'main') {
        $userConditions[] = "(pm.main_member = 'اصلی' OR pm.main_member = 'main')";
    } elseif ($userMainFilter === 'member') {
        $userConditions[] = "(pm.main_member != 'اصلی' AND pm.main_member != 'main')";
    }
    
    // اضافه کردن شرایط به کوئری
    if (!empty($userConditions)) {
        $whereSQL = " AND " . implode(' AND ', $userConditions);
        $userQueryBase .= $whereSQL;
        $userQueryCount .= $whereSQL;
    }
    
    // گرفتن تعداد کل کاربران
    $stmt = $mysqli->prepare($userQueryCount);
    if ($stmt) {
        if (!empty($userParams)) {
            $stmt->bind_param($userTypes, ...$userParams);
        }
        $stmt->execute();
        $countResult = $stmt->get_result();
        if ($countResult) {
            $totalUsers = $countResult->fetch_assoc()['total'] ?? 0;
        }
        $stmt->close();
    }
    
    // مرتب‌سازی
    $sortSQL = " ORDER BY " . $userSortField . " " . $userSortOrder;
    
    // اگر مرتب‌سازی بر اساس تعداد مدارک باشد
    if ($userSortField === 'document_count') {
        $sortSQL = " ORDER BY document_count " . $userSortOrder;
    }
    
    // اگر مرتب‌سازی بر اساس آخرین مدرک باشد
    if ($userSortField === 'last_document_date') {
        $sortSQL = " ORDER BY last_document_date " . $userSortOrder . " IS NULL, last_document_date " . $userSortOrder;
    }
    
    // صفحه‌بندی
    $paginationSQL = " LIMIT ? OFFSET ?";
    
    // کوئری نهایی
    $userQuery = $userQueryBase . $sortSQL . $paginationSQL;
    
    // گرفتن لیست کاربران
    $pdoParams = array_merge($userParams, [$userItemsPerPage, $userOffset]);
    $pdoTypes = $userTypes . "ii";
    
    $stmt = $mysqli->prepare($userQuery);
    if ($stmt) {
        if (!empty($pdoTypes)) {
            $stmt->bind_param($pdoTypes, ...$pdoParams);
        } else {
            $stmt->bind_param('ii', $userItemsPerPage, $userOffset);
        }
        $stmt->execute();
        $userResult = $stmt->get_result();
        while ($row = $userResult->fetch_assoc()) {
            $row['display_name'] = fixPersianText($row['display_name']);
            $row['first_name'] = fixPersianText($row['first_name']);
            $row['last_name'] = fixPersianText($row['last_name']);
            $usersList[] = $row;
        }
        $stmt->close();
    }
    
    $totalUserPages = ceil($totalUsers / $userItemsPerPage);
}


// اختصاص داده‌ها به متغیرهای قابل استفاده در HTML
$users = $usersArray ?? [];
$tickets = $ticketsArray ?? [];
$cheques = $chequesArray ?? [];
$documents = $documentsArray ?? [];
$members = $membersArray ?? [];



// ===== حواله‌ها =====
$transfers = [];

$sql = "
SELECT 
    id,
    transfer_date,
    amount,
    bank_name,
    destination_account,
    receipt_image,
    description,
    status,
    created_at
FROM transfers
ORDER BY id DESC
LIMIT 100
";

$res = $mysqli->query($sql);
if($res){
    while($row = $res->fetch_assoc()){
        $transfers[] = $row;
    }
}
function transferStatusBadge($status){
    return match($status){
        'verified' => '<span class="badge bg-success">تأیید شده</span>',
        'rejected' => '<span class="badge bg-danger">رد شده</span>',
        default    => '<span class="badge bg-warning text-dark">در انتظار</span>',
    };
}
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// ===== سرچ =====
$search = trim($_GET['search'] ?? '');

// ===== سورت =====
$allowedSort = ['transfer_date','amount','bank_name','status','created_at'];
$sort  = in_array($_GET['sort'] ?? '', $allowedSort) ? $_GET['sort'] : 'created_at';
$order = ($_GET['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

$where = '';
$params = [];

if ($search !== '') {
    $where = "WHERE bank_name LIKE :s OR destination_account LIKE :s";
    $params[':s'] = "%$search%";
}

// ===== تعداد کل =====
$stmt = $pdo->prepare("SELECT COUNT(*) FROM transfers $where");
$stmt->execute($params);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// ===== گرفتن حواله‌ها =====
$sql = "
SELECT * FROM transfers
$where
ORDER BY $sort $order
LIMIT $perPage OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$activeTab = $_GET['tab'] ?? 'members';
$selectedProjectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

$projectMembers = [];
if ($activeTab === 'members' && $selectedProjectId > 0) {
// ۱. دریافت مقدار جستجو از URL (اگر نبود، خالی در نظر بگیر)
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

// ۲. پایه کوئری اصلی شما همراه با JOIN
    $sql = "SELECT 
    users_project.id AS user_project_id,
    users_project.full_name,
    users_project.has_partner,
    users_project.father_name,
    users_project.national_code,
    users_project.father_name,
    users_project.birth_certificate_no,
    users_project.birth_date,
    users_project.phone,
    users_project.sana_code,
    users_project.address,
    users_project.postal_code,
    users_project.partner_1,
    users_project.partner_2,
    users_project.partner_3,
    users_project.partner_4,
    users_project.project_id,
    projects.name,
    projects.description,
    projects.created_at
FROM users_project
JOIN projects ON users_project.project_id = projects.id
WHERE users_project.project_id = :project_id";

// آرایه پارامترها برای Bind کردن مقادیر دیتابیس
    $params = [':project_id' => $selectedProjectId];
// ۳. اگر مقدار جستجو خالی نبود، فیلتر نام و نام خانوادگی را اضافه کن
    if ($search !== '') {
        $sql .= " AND (project_members.first_name LIKE :search 
                OR project_members.last_name LIKE :search 
                OR CONCAT(project_members.first_name, ' ', project_members.last_name) LIKE :search)";
        $params[':search'] = "%{$search}%";
    }

// اجرای کوئری
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $projectMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);


}
foreach ($projectMembers as $member) {
    $user_project_id = (int)($member['user_project_id'] ?? 0);


}
$rows = [];
if ($activeTab === 'expenses' && $selectedProjectId > 0) {

    $contractors = [];
    $contractorSearch = trim($_GET['contractor_search'] ?? '');
    $contractorPage = max(1, (int)($_GET['contractor_page'] ?? 1));
    $contractorPerPage = 10;
    $totalContractors = 0;
    $totalContractorPages = 1;

    if ($activeTab === 'suppliers' && $selectedProjectId > 0) {
        $whereParts = ['contractors.project_id = :project_id'];
        $params = [
                ':project_id' => (int)$selectedProjectId
        ];

        if ($contractorSearch !== '') {
            $whereParts[] = "(
            contractors.first_name LIKE :search OR
            contractors.last_name LIKE :search OR
            contractors.national_code LIKE :search OR
            contractors.phone LIKE :search OR
            contractors.job_title LIKE :search
        )";
            $params[':search'] = '%' . $contractorSearch . '%';
        }

        $whereSql = implode(' AND ', $whereParts);

        $countSql = "SELECT COUNT(*) 
                 FROM contractors
                 WHERE $whereSql";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->bindValue(':project_id', $params[':project_id'], PDO::PARAM_INT);

        if (isset($params[':search'])) {
            $countStmt->bindValue(':search', $params[':search'], PDO::PARAM_STR);
        }

        $countStmt->execute();
        $totalContractors = (int)$countStmt->fetchColumn();

        $totalContractorPages = max(1, (int)ceil($totalContractors / $contractorPerPage));
        if ($contractorPage > $totalContractorPages) {
            $contractorPage = $totalContractorPages;
        }

        $offset = ($contractorPage - 1) * $contractorPerPage;

        $listSql = "SELECT 
                    contractors.*,
                    fd.profile_image AS document_image,
                    fd.document_type
                FROM contractors
                LEFT JOIN file_documents fd 
                    ON fd.id = (
                        SELECT fd2.id
                        FROM file_documents fd2
                        WHERE fd2.contractor_id = contractors.id
                        ORDER BY fd2.id DESC
                        LIMIT 1
                    )
                WHERE $whereSql
                ORDER BY contractors.id DESC
                LIMIT :limit OFFSET :offset";

        $listStmt = $pdo->prepare($listSql);
        $listStmt->bindValue(':project_id', $params[':project_id'], PDO::PARAM_INT);

        if (isset($params[':search'])) {
            $listStmt->bindValue(':search', $params[':search'], PDO::PARAM_STR);
        }

        $listStmt->bindValue(':limit', $contractorPerPage, PDO::PARAM_INT);
        $listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $listStmt->execute();

        $contractors = $listStmt->fetchAll(PDO::FETCH_ASSOC);
    }


}

if ($activeTab === 'remitance_out' && $selectedProjectId > 0) {
    // ۱. تابع نرمال‌سازی ارقام فارسی/عربی به انگلیسی
    if (!function_exists('toEnglishDigits')) {
        function toEnglishDigits($str)
        {
            $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $arabic  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
            $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $str = str_replace($persian, $english, (string)$str);
            return str_replace($arabic, $english, $str);
        }
    }

    // ۲. تابع کمکی استخراج لیست‌های متمایز (جلوگیری از تکرار کد و امنیت Prepared Statement)
    if (!function_exists('fetchDistinctColumn')) {
        function fetchDistinctColumn(PDO $pdo, string $column, int $projectId): array
        {
            $allowedColumns = ['bank/hesab', 'subchapter', 'transaction', 'beneficiary', 'document', 'voroodi/khoroujy'];
            if (!in_array($column, $allowedColumns, true)) {
                return [];
            }
            $stmt = $pdo->prepare("SELECT DISTINCT `{$column}` FROM `remittance_output` 
                                  WHERE `{$column}` IS NOT NULL AND `{$column}` != '' 
                                    AND `project_id` = :pid 
                                  ORDER BY `{$column}` ASC");
            $stmt->execute([':pid' => $projectId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        }
    }

    // ۳. دریافت و اعتبارسنجی ورودی‌های فیلتر
    $bank_hesab       = trim((string)($_GET['bank/hesab'] ?? ''));
    $subchapter       = trim((string)($_GET['subchapter'] ?? ''));
    $transaction      = trim((string)($_GET['transaction'] ?? ''));
    $beneficiary      = trim((string)($_GET['beneficiary'] ?? ''));
    $document         = trim((string)($_GET['document'] ?? ''));
    $voroodi_khoroujy = trim((string)($_GET['voroodi/khoroujy'] ?? ''));
    $dateFrom         = toEnglishDigits(trim((string)($_GET['date_from'] ?? '')));
    $dateTo           = toEnglishDigits(trim((string)($_GET['date_to'] ?? '')));
    $minAmount        = toEnglishDigits(trim((string)($_GET['min_amount'] ?? '')));
    $maxAmount        = toEnglishDigits(trim((string)($_GET['max_amount'] ?? '')));

    // تنظیمات صفحه‌بندی
    $allowedLimits = [10, 25, 50, 100];
    $perPage = (int)($_GET['per_page'] ?? 25);
    if (!in_array($perPage, $allowedLimits, true)) {
        $perPage = 25;
    }
    $page = max(1, (int)($_GET['page'] ?? 1));

    // تنظیمات مرتب‌سازی (Sort Whitelist)
    $allowedSort = ['id', 'history', 'harvest', 'invoice_number', 'document'];
    $orderBy = in_array($_GET['order_by'] ?? '', $allowedSort, true) ? $_GET['order_by'] : 'id';
    $orderDir = strtolower($_GET['order_dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
    $toggleDir = ($orderDir === 'ASC') ? 'desc' : 'asc';

    // ۴. دریافت لیست‌های دراپ‌داون
    $bankHesabList   = fetchDistinctColumn($pdo, 'bank/hesab', (int)$selectedProjectId);
    $beneficiaryList = fetchDistinctColumn($pdo, 'beneficiary', (int)$selectedProjectId);
    $transactionList = fetchDistinctColumn($pdo, 'transaction', (int)$selectedProjectId);
    $subchapterList  = fetchDistinctColumn($pdo, 'subchapter', (int)$selectedProjectId);
    $documentList    = fetchDistinctColumn($pdo, 'document', (int)$selectedProjectId);

    // ۵. ساخت شروط WHERE پویا
    $whereConditions = ["`project_id` = :project_id"];
    $queryParams = [':project_id' => (int)$selectedProjectId];

    if ($bank_hesab !== '') {
        $whereConditions[] = "`bank/hesab` LIKE :bank_hesab";
        $queryParams[':bank_hesab'] = "%{$bank_hesab}%";
    }
    if ($subchapter !== '') {
        $whereConditions[] = "`subchapter` LIKE :subchapter";
        $queryParams[':subchapter'] = "%{$subchapter}%";
    }
    if ($transaction !== '') {
        $whereConditions[] = "`transaction` LIKE :transaction";
        $queryParams[':transaction'] = "%{$transaction}%";
    }
    if ($beneficiary !== '') {
        $whereConditions[] = "`beneficiary` LIKE :beneficiary";
        $queryParams[':beneficiary'] = "%{$beneficiary}%";
    }
    if ($document !== '') {
        $whereConditions[] = "`document` LIKE :document";
        $queryParams[':document'] = "%{$document}%";
    }
    if ($voroodi_khoroujy !== '') {
        $whereConditions[] = "`voroodi/khoroujy` LIKE :voroodi_khoroujy";
        $queryParams[':voroodi_khoroujy'] = "%{$voroodi_khoroujy}%";
    }

    if ($dateFrom !== '') {
        $whereConditions[] = "`history` >= :date_from";
        $queryParams[':date_from'] = $dateFrom;
    }
    if ($dateTo !== '') {
        $whereConditions[] = "`history` <= :date_to";
        $queryParams[':date_to'] = $dateTo;
    }

    if ($minAmount !== '' && is_numeric($minAmount)) {
        $whereConditions[] = "`harvest` >= :min_amount";
        $queryParams[':min_amount'] = (float)$minAmount;
    }
    if ($maxAmount !== '' && is_numeric($maxAmount)) {
        $whereConditions[] = "`harvest` <= :max_amount";
        $queryParams[':max_amount'] = (float)$maxAmount;
    }

    $whereSql = 'WHERE ' . implode(' AND ', $whereConditions);

    // ۶. استخراج نقشه‌برداری روابط (Mapping) سرفصل به زیرفصل
    $transactionSubMap = [];
    try {
        $mapStmt = $pdo->prepare("
            SELECT DISTINCT `transaction`, `subchapter` 
            FROM `remittance_output` 
            WHERE `transaction` IS NOT NULL AND `transaction` != '' 
              AND `project_id` = :pid
            ORDER BY `transaction` ASC, `subchapter` ASC
        ");
        $mapStmt->execute([':pid' => (int)$selectedProjectId]);
        $mapRows = $mapStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($mapRows as $r) {
            $t = trim($r['transaction'] ?? '');
            $s = trim($r['subchapter'] ?? '');
            if ($t === '') continue;
            if (!isset($transactionSubMap[$t])) {
                $transactionSubMap[$t] = [];
            }
            if ($s !== '' && !in_array($s, $transactionSubMap[$t], true)) {
                $transactionSubMap[$t][] = $s;
            }
        }
    } catch (Exception $e) {
        error_log("Transaction Map Error: " . $e->getMessage());
        $transactionSubMap = [];
    }

    // ساخت نقشه بانک به ذی‌نفع‌ها (Prepared Statement ایمن بدون var_dump)
    $bank_hesabMap = [];
    try {
        $stmtMap = $pdo->prepare("
            SELECT DISTINCT `bank/hesab`, `beneficiary` 
            FROM `remittance_output` 
            WHERE `bank/hesab` IS NOT NULL AND `bank/hesab` != ''
              AND `project_id` = :pid
        ");
        $stmtMap->execute([':pid' => (int)$selectedProjectId]);
        while ($row = $stmtMap->fetch(PDO::FETCH_ASSOC)) {
            $bank = trim($row['bank/hesab']);
            $beneficiary = trim($row['beneficiary'] ?? '');
            if ($bank !== '') {
                if (!isset($bank_hesabMap[$bank])) {
                    $bank_hesabMap[$bank] = [];
                }
                if ($beneficiary !== '' && !in_array($beneficiary, $bank_hesabMap[$bank], true)) {
                    $bank_hesabMap[$bank][] = $beneficiary;
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Bank Map Error: " . $e->getMessage());
        $bank_hesabMap = [];
    }

    // ۷. اجرای کوئری تجمیع داده‌ها و صفحه‌بندی
    try {
        // کوئری شمارش کل و جمع مبالغ
        $summarySql = "SELECT COUNT(*) AS total_count, COALESCE(SUM(`harvest`), 0) AS total_amount 
                       FROM `remittance_output` {$whereSql}";
        $summaryStmt = $pdo->prepare($summarySql);
        $summaryStmt->execute($queryParams);
        $summaryData = $summaryStmt->fetch(PDO::FETCH_ASSOC);

        $totalRecords = (int)($summaryData['total_count'] ?? 0);
        $totalWithdrawalAmount = (float)($summaryData['total_amount'] ?? 0);
        $totalPages = max(1, (int)ceil($totalRecords / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        // کوئری دریافت داده‌های صفحه جاری
        $sql = "SELECT * FROM `remittance_output` 
                {$whereSql} 
                ORDER BY `{$orderBy}` {$orderDir}, `id` DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);

        foreach ($queryParams as $param => $val) {
            $stmt->bindValue($param, $val);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        // ذخیره همزمان در هر دو متغیر برای هماهنگی با هر کجای جدول
        $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $remittance_output_rows = $expenses;

    } catch (PDOException $e) {
        error_log("Remittance Output Error: " . $e->getMessage());
        $expenses = [];
        $remittance_output_rows = [];
        $totalRecords = 0;
        $totalWithdrawalAmount = 0;
    }
}

// مقداردهی اولیه در بالای فایل یا قبل از شرط (برای جلوگیری از Undefined در تب‌های دیگر)

if ($activeTab === 'check_out' && $selectedProjectId > 0) {

    /*
    |--------------------------------------------------------------------------
    | ۱. مقداردهی اولیه
    |--------------------------------------------------------------------------
    */

    $checks = [];
    $check_output_rows = [];
    $totalRecords = 0;
    $totalPages = 1;
    $offset = 0;

    /*
    |--------------------------------------------------------------------------
    | ۲. نرمال‌سازی ارقام فارسی و عربی
    |--------------------------------------------------------------------------
    */

    if (!function_exists('toEnglishDigits')) {
        function toEnglishDigits($value): string
        {
            $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $arabic  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
            $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

            $value = str_replace($persian, $english, (string)$value);
            return str_replace($arabic, $english, $value);
        }
    }
    /**
     * تبدیل اعداد با فرمت پول (دارای کاما یا ارقام فارسی/عربی) به عدد صحیح قابل درج در دیتابیس
     *
     * @param mixed $val
     * @return int
     */
    if (!function_exists('cleanMoneyToInt')) {
        function cleanMoneyToInt($val): int {
            if ($val === null || $val === '') {
                return 0;
            }

            $val = (string)$val;

            // تبدیل ارقام فارسی به انگلیسی
            $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $arabicDigits  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
            $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

            $val = str_replace($persianDigits, $englishDigits, $val);
            $val = str_replace($arabicDigits, $englishDigits, $val);

            // حذف کاما، فاصله و هر کاراکتر غیر عددی به جز علامت منفی
            $val = preg_replace('/[^\d-]/', '', $val);

            return (int)$val;
        }
    }

    /**
     * پاک‌سازی ورودی‌های رشته‌ای
     */
    if (!function_exists('cleanInput')) {
        function cleanInput($data) {
            if (is_array($data)) {
                return array_map('cleanInput', $data);
            }
            return ($data !== null) ? trim((string)$data) : '';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ۳. تابع دریافت مقادیر متمایز ستون‌ها
    |--------------------------------------------------------------------------
    */

    if (!function_exists('fetchCheckDistinctColumn')) {
        function fetchCheckDistinctColumn(PDO $pdo, string $column, int $projectId): array
        {
            $allowedColumns = [
                    'check_amount',
                    'check_due_date',
                    'passed/failed',
                    'bank',
                    'check_number',
                    'transaction',
                    'subchapter',
                    'recipient'
            ];

            if (!in_array($column, $allowedColumns, true)) {
                return [];
            }

            try {
                $sql = "
                    SELECT DISTINCT `{$column}`
                    FROM `check_output`
                    WHERE `project_id` = :project_id
                      AND `{$column}` IS NOT NULL
                      AND TRIM(`{$column}`) <> ''
                    ORDER BY `{$column}` ASC
                ";

                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':project_id', $projectId, PDO::PARAM_INT);
                $stmt->execute();

                return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            } catch (PDOException $e) {
                error_log("Check distinct column error [{$column}]: " . $e->getMessage());
                return [];
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ۴. دریافت مقادیر ورودی فیلترها و نرمال‌سازی
    |--------------------------------------------------------------------------
    */

    // فیلتر وضعیت چک با نام متغیر مجزا (جلوگیری از بازنویسی با لیست دیتابیس)
    $filterPassedFailed = trim((string)($_GET['passed/failed'] ?? $_GET['status'] ?? ''));
    $filterCheckNumber  = trim((string)($_GET['check_number'] ?? ''));
    $filterRecipient    = trim((string)($_GET['recipient'] ?? ''));
    $filterTransaction  = trim((string)($_GET['transaction'] ?? ''));
    $filterSubchapter   = trim((string)($_GET['subchapter'] ?? ''));
    $filterBank         = trim((string)($_GET['bank'] ?? ''));

    $dateFrom  = toEnglishDigits(trim((string)($_GET['date_from'] ?? '')));
    $dateTo    = toEnglishDigits(trim((string)($_GET['date_to'] ?? '')));
    $minAmount = toEnglishDigits(str_replace([',', ' '], '', (string)($_GET['min_amount'] ?? '')));
    $maxAmount = toEnglishDigits(str_replace([',', ' '], '', (string)($_GET['max_amount'] ?? '')));

    /*
    |--------------------------------------------------------------------------
    | ۵. دریافت گزینه‌های دراپ‌داون‌ها
    |--------------------------------------------------------------------------
    */

    $transactionList   = fetchCheckDistinctColumn($pdo, 'transaction', (int)$selectedProjectId);
    $subchapterList    = fetchCheckDistinctColumn($pdo, 'subchapter', (int)$selectedProjectId);
    $check_numberList  = fetchCheckDistinctColumn($pdo, 'check_number', (int)$selectedProjectId);
    $recipientList     = fetchCheckDistinctColumn($pdo, 'recipient', (int)$selectedProjectId);
    $passedFailedList  = fetchCheckDistinctColumn($pdo, 'passed/failed', (int)$selectedProjectId);

    /*
    |--------------------------------------------------------------------------
    | ۶. تنظیم صفحه‌بندی و مرتب‌سازی امن
    |--------------------------------------------------------------------------
    */

    $allowedLimits = [10, 25, 50, 100];
    $perPage = (int)($_GET['per_page'] ?? 25);
    if (!in_array($perPage, $allowedLimits, true)) {
        $perPage = 25;
    }

    $page = max(1, (int)($_GET['page'] ?? 1));

    $allowedSortColumns = [
            'id',
            'check_amount',
            'check_due_date',
            'passed/failed',
            'bank',
            'check_number',
    ];

    $requestedOrderBy = (string)($_GET['order_by'] ?? 'id');
    $orderBy = in_array($requestedOrderBy, $allowedSortColumns, true) ? $requestedOrderBy : 'id';

    $requestedOrderDir = strtolower((string)($_GET['order_dir'] ?? 'desc'));
    $orderDir = $requestedOrderDir === 'asc' ? 'ASC' : 'DESC';
    $toggleDir = $orderDir === 'ASC' ? 'desc' : 'asc';

    /*
    |--------------------------------------------------------------------------
    | ۷. ساخت شرط‌های WHERE به صورت یکپارچه
    |--------------------------------------------------------------------------
    */

    $whereConditions = [
            'c.`project_id` = :project_id'
    ];

    $queryParams = [
            ':project_id' => (int)$selectedProjectId
    ];

    // فیلتر وضعیت چک (اصلاح شده بدون کاراکتر اسلش در نام پارامتر PDO)
    if ($filterPassedFailed !== '') {
        $whereConditions[] = "c.`passed/failed` = :passed_failed";
        $queryParams[':passed_failed'] = $filterPassedFailed;
    }

    // شماره چک
    if ($filterCheckNumber !== '') {
        $whereConditions[] = "c.`check_number` = :check_number";
        $queryParams[':check_number'] = $filterCheckNumber;
    }

    // دریافت کننده
    if ($filterRecipient !== '') {
        $whereConditions[] = "c.`recipient` LIKE :recipient";
        $queryParams[':recipient'] = "%{$filterRecipient}%";
    }

    // سرفصل
    if ($filterTransaction !== '') {
        $whereConditions[] = "c.`transaction` = :transaction";
        $queryParams[':transaction'] = $filterTransaction;
    }

    // زیرفصل
    if ($filterSubchapter !== '') {
        $whereConditions[] = "c.`subchapter` = :subchapter";
        $queryParams[':subchapter'] = $filterSubchapter;
    }

    // بانک
    if ($filterBank !== '') {
        $whereConditions[] = "c.`bank` LIKE :bank";
        $queryParams[':bank'] = "%{$filterBank}%";
    }

    // تاریخ سررسید
    if ($dateFrom !== '') {
        $whereConditions[] = "c.`check_due_date` >= :date_from";
        $queryParams[':date_from'] = $dateFrom;
    }
    if ($dateTo !== '') {
        $whereConditions[] = "c.`check_due_date` <= :date_to";
        $queryParams[':date_to'] = $dateTo;
    }

    // مبلغ
    if ($minAmount !== '' && is_numeric($minAmount)) {
        $whereConditions[] = "c.`check_amount` >= :min_amount";
        $queryParams[':min_amount'] = (float)$minAmount;
    }
    if ($maxAmount !== '' && is_numeric($maxAmount)) {
        $whereConditions[] = "c.`check_amount` <= :max_amount";
        $queryParams[':max_amount'] = (float)$maxAmount;
    }

    $whereSql = $whereConditions ? ('WHERE ' . implode(' AND ', $whereConditions)) : '';

    /*
    |--------------------------------------------------------------------------
    | ۸. تابع امن اتصال پارامترها
    |--------------------------------------------------------------------------
    */

    $bindCheckParams = static function (PDOStatement $stmt, array $params): void {
        foreach ($params as $name => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($name, $value, $type);
        }
    };

// دریافت نقشه سرفصل‌ها به زیرفصل‌های مرتبط در همین پروژه
    $transactionSubMap = [];
    try {
        $subStmt = $pdo->prepare("
        SELECT DISTINCT `transaction`, `subchapter`
        FROM `check_output`
        WHERE `project_id` = :project_id
          AND `transaction` IS NOT NULL AND TRIM(`transaction`) <> ''
          AND `subchapter` IS NOT NULL AND TRIM(`subchapter`) <> ''
        ORDER BY `transaction` ASC, `subchapter` ASC
    ");
        $subStmt->bindValue(':project_id', (int)$selectedProjectId, PDO::PARAM_INT);
        $subStmt->execute();
        while ($row = $subStmt->fetch(PDO::FETCH_ASSOC)) {
            $t = trim($row['transaction']);
            $s = trim($row['subchapter']);
            if (!isset($transactionSubMap[$t])) {
                $transactionSubMap[$t] = [];
            }
            if (!in_array($s, $transactionSubMap[$t], true)) {
                $transactionSubMap[$t][] = $s;
            }
        }
    } catch (PDOException $e) {
        error_log("SubMap Error: " . $e->getMessage());
    }

// مقادیر فیلتر فعلی از GET
    $currentTransaction = trim((string)($_GET['transaction'] ?? ''));
    $currentSubchapter  = trim((string)($_GET['subchapter'] ?? ''));



    /*
    |--------------------------------------------------------------------------
    | ۹. شمارش و دریافت رکوردهای چک
    |--------------------------------------------------------------------------
    */

    try {
        // شمارش کل رکوردهای منطبق
        $summarySql = "
            SELECT COUNT(*) AS total_count, COALESCE(SUM(`check_amount`), 0) AS total_amount
            FROM `check_output` AS c
            {$whereSql}
        ";

        $summaryStmt = $pdo->prepare($summarySql);
        $bindCheckParams($summaryStmt, $queryParams);
        $summaryStmt->execute();

        $summaryData = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $totalWithdrawalAmount = (float)($summaryData['total_amount'] ?? 0);
        $totalRecords = (int)($summaryData['total_count'] ?? 0);

        // محاسبه صفحات
        $totalPages = max(1, (int)ceil($totalRecords / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $perPage;

        // دریافت رکوردهای صفحه جاری
        $sql = "
            SELECT
                c.*,
                p.`name` AS `project_name`
            FROM `check_output` AS c
            LEFT JOIN `projects` AS p
                ON p.`id` = c.`project_id`
            {$whereSql}
            ORDER BY
                c.`{$orderBy}` {$orderDir},
                c.`id` DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);
        $bindCheckParams($stmt, $queryParams);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $checks = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $check_output_rows = $checks;

    } catch (PDOException $e) {
        error_log('Check Output Error: ' . $e->getMessage());
        $checks = [];
        $check_output_rows = [];
        $totalRecords = 0;
        $totalPages = 1;
        $page = 1;
        $offset = 0;
    }

// پردازش فرم ثبت چک



}




if ($activeTab === 'remittance_input' && $selectedProjectId > 0)
{
    // ۱. تابع نرمال‌سازی ارقام فارسی/عربی به انگلیسی
    if (!function_exists('toEnglishDigits')) {
        function toEnglishDigits($str)
        {
            $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $arabic  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
            $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $str = str_replace($persian, $english, (string)$str);
            return str_replace($arabic, $english, $str);
        }
    }

    // ۲. تابع کمکی استخراج لیست‌های متمایز (جلوگیری از تکرار کد و امنیت Prepared Statement)
    if (!function_exists('fetchDistinctColumn')) {
        function fetchDistinctColumn(PDO $pdo, string $column, int $projectId): array
        {
            $allowedColumns = ['bank_account', 'sub_account', 'transaction_type', 'beneficiary', 'document_number', 'direction'];
            if (!in_array($column, $allowedColumns, true)) {
                return [];
            }
            $stmt = $pdo->prepare("SELECT DISTINCT `{$column}` FROM `remittance_output` 
                                  WHERE `{$column}` IS NOT NULL AND `{$column}` != '' 
                                    AND `project_id` = :pid 
                                  ORDER BY `{$column}` ASC");
            $stmt->execute([':pid' => $projectId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        }
    }

    // تنظیمات صفحه‌بندی
    $allowedLimits = [10, 25, 50, 100];
    $perPage = (int)($_GET['per_page'] ?? 25);
    if (!in_array($perPage, $allowedLimits, true)) {
        $perPage = 25;
    }
    $page = max(1, (int)($_GET['page'] ?? 1));

    // تنظیمات مرتب‌سازی (Sort Whitelist)
    $allowedSort = ['id', 'transaction_date', 'deposit', 'receipt_number', 'document_number'];
    $orderBy = in_array($_GET['order_by'] ?? '', $allowedSort, true) ? $_GET['order_by'] : 'id';
    $orderDir = strtolower($_GET['order_dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
    $toggleDir = ($orderDir === 'ASC') ? 'desc' : 'asc';

    // ۴. دریافت لیست‌های دراپ‌داون
    $bank_accountList   = fetchDistinctColumn($pdo, 'bank_account', (int)$selectedProjectId);
    $beneficiaryList = fetchDistinctColumn($pdo, 'beneficiary', (int)$selectedProjectId);
    $transaction_typeList = fetchDistinctColumn($pdo, 'transaction_type', (int)$selectedProjectId);
    $sub_accountList  = fetchDistinctColumn($pdo, 'sub_account', (int)$selectedProjectId);
    $document_numberList    = fetchDistinctColumn($pdo, 'document_number', (int)$selectedProjectId);

    // ۵. ساخت شروط WHERE پویا
    $whereConditions = ["`project_id` = :project_id"];
    $queryParams = [':project_id' => (int)$selectedProjectId];

    if ($bank_hesab !== '') {
        $whereConditions[] = "`bank/hesab` LIKE :bank_hesab";
        $queryParams[':bank_hesab'] = "%{$bank_hesab}%";
    }
    if ($subchapter !== '') {
        $whereConditions[] = "`subchapter` LIKE :subchapter";
        $queryParams[':subchapter'] = "%{$subchapter}%";
    }
    if ($transaction !== '') {
        $whereConditions[] = "`transaction` LIKE :transaction";
        $queryParams[':transaction'] = "%{$transaction}%";
    }
    if ($beneficiary !== '') {
        $whereConditions[] = "`beneficiary` LIKE :beneficiary";
        $queryParams[':beneficiary'] = "%{$beneficiary}%";
    }
    if ($document !== '') {
        $whereConditions[] = "`document` LIKE :document";
        $queryParams[':document'] = "%{$document}%";
    }
    if ($voroodi_khoroujy !== '') {
        $whereConditions[] = "`voroodi/khoroujy` LIKE :voroodi_khoroujy";
        $queryParams[':voroodi_khoroujy'] = "%{$voroodi_khoroujy}%";
    }

    if ($dateFrom !== '') {
        $whereConditions[] = "`history` >= :date_from";
        $queryParams[':date_from'] = $dateFrom;
    }
    if ($dateTo !== '') {
        $whereConditions[] = "`history` <= :date_to";
        $queryParams[':date_to'] = $dateTo;
    }

    if ($minAmount !== '' && is_numeric($minAmount)) {
        $whereConditions[] = "`harvest` >= :min_amount";
        $queryParams[':min_amount'] = (float)$minAmount;
    }
    if ($maxAmount !== '' && is_numeric($maxAmount)) {
        $whereConditions[] = "`harvest` <= :max_amount";
        $queryParams[':max_amount'] = (float)$maxAmount;
    }

    $whereSql = 'WHERE ' . implode(' AND ', $whereConditions);

    // ۶. استخراج نقشه‌برداری روابط (Mapping) سرفصل به زیرفصل
    $transactionSubMap = [];
    try {
        $mapStmt = $pdo->prepare("
            SELECT DISTINCT `transaction`, `subchapter` 
            FROM `remittance_output` 
            WHERE `transaction` IS NOT NULL AND `transaction` != '' 
              AND `project_id` = :pid
            ORDER BY `transaction` ASC, `subchapter` ASC
        ");
        $mapStmt->execute([':pid' => (int)$selectedProjectId]);
        $mapRows = $mapStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($mapRows as $r) {
            $t = trim($r['transaction'] ?? '');
            $s = trim($r['subchapter'] ?? '');
            if ($t === '') continue;
            if (!isset($transactionSubMap[$t])) {
                $transactionSubMap[$t] = [];
            }
            if ($s !== '' && !in_array($s, $transactionSubMap[$t], true)) {
                $transactionSubMap[$t][] = $s;
            }
        }
    } catch (Exception $e) {
        error_log("Transaction Map Error: " . $e->getMessage());
        $transactionSubMap = [];
    }

    // ساخت نقشه بانک به ذی‌نفع‌ها (Prepared Statement ایمن بدون var_dump)
    $bank_hesabMap = [];
    try {
        $stmtMap = $pdo->prepare("
            SELECT DISTINCT `bank/hesab`, `beneficiary` 
            FROM `remittance_output` 
            WHERE `bank/hesab` IS NOT NULL AND `bank/hesab` != ''
              AND `project_id` = :pid
        ");
        $stmtMap->execute([':pid' => (int)$selectedProjectId]);
        while ($row = $stmtMap->fetch(PDO::FETCH_ASSOC)) {
            $bank = trim($row['bank/hesab']);
            $beneficiary = trim($row['beneficiary'] ?? '');
            if ($bank !== '') {
                if (!isset($bank_hesabMap[$bank])) {
                    $bank_hesabMap[$bank] = [];
                }
                if ($beneficiary !== '' && !in_array($beneficiary, $bank_hesabMap[$bank], true)) {
                    $bank_hesabMap[$bank][] = $beneficiary;
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Bank Map Error: " . $e->getMessage());
        $bank_hesabMap = [];
    }

    // ۷. اجرای کوئری تجمیع داده‌ها و صفحه‌بندی
    try {
        // کوئری شمارش کل و جمع مبالغ
        $summarySql = "SELECT COUNT(*) AS total_count, COALESCE(SUM(`harvest`), 0) AS total_amount 
                       FROM `remittance_output` {$whereSql}";
        $summaryStmt = $pdo->prepare($summarySql);
        $summaryStmt->execute($queryParams);
        $summaryData = $summaryStmt->fetch(PDO::FETCH_ASSOC);

        $totalRecords = (int)($summaryData['total_count'] ?? 0);
        $totalWithdrawalAmount = (float)($summaryData['total_amount'] ?? 0);
        $totalPages = max(1, (int)ceil($totalRecords / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        // کوئری دریافت داده‌های صفحه جاری
        $sql = "SELECT * FROM `remittance_output` 
                {$whereSql} 
                ORDER BY `{$orderBy}` {$orderDir}, `id` DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);

        foreach ($queryParams as $param => $val) {
            $stmt->bindValue($param, $val);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        // ذخیره همزمان در هر دو متغیر برای هماهنگی با هر کجای جدول
        $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $remittance_output_rows = $expenses;

    } catch (PDOException $e) {
        error_log("Remittance Output Error: " . $e->getMessage());
        $expenses = [];
        $remittance_output_rows = [];
        $totalRecords = 0;
        $totalWithdrawalAmount = 0;
    }
}

// ========== اتمام کد PHP ==========
ob_start();
?>
<!-- ۱. فونت‌ها و آیکون‌ها -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">


<!-- ۲. استایل اصلی قالب (Bootstrap 5 RTL) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">

<!-- ۳. استایل دیتاتیبل مخصوص بوت‌استرپ ۵ -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />

    <link rel="stylesheet" href="https://unpkg.com/@majidh1/jalalidatepicker/dist/jalalidatepicker.min.css">
    <!-- Persian Datepicker JS -->
    <script type="text/javascript" src="https://unpkg.com/@majidh1/jalalidatepicker/dist/jalalidatepicker.min.js"></script>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>




<!-- ۶. جاوااسکریپت اصلی دیتاتیبل و افزونه بوت‌استرپ ۵ آن -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<style>

    .sidebar-menu-half {
        width: 25%;
    }

    @media (max-width: 768px) {
        .sidebar-menu-half {
            width: 100%;
        }
    }
    /* انیمیشن برای چرخش آیکون پلاس */
    .transition-icon {
        transition: transform 0.3s ease;
    }
    .rotate-45 {
        transform: rotate(45deg); /* تبدیل پلاس به ضربدر یا نشان دادن باز شدن */
        color: #0d6efd;
    }
    .list-group-item.active {
        background-color: #0d6efd !important;
        border-color: #0d6efd !important;
    }
    .expense-submenu {
        display: none;
        transition: all 0.3s ease;
    }

    .expense-submenu.open {
        display: block;
    }

    .toggle-icon {
        transition: transform 0.25s ease-in-out;
    }

    .toggle-icon.rotated {
        transform: rotate(45deg);
    }
    /* اصلاح ترتیب نمایش مودال روی لایه تاریک */
    .modal-backdrop {
        z-index: 1050 !important;
    }

    .modal {
        z-index: 1055 !important;
    }

    .modal.show {
        display: block !important;
    }

    body > .modal {
        z-index: 1060;
    }
    .transition-icon {
        transition: transform 0.3s ease-in-out;
    }
    [aria-expanded="true"] .transition-icon {
        transform: rotate(45deg); /* تبدیل + به × */
    }
    /* چرخش نرم آیکون پلاس هنگام باز و بسته شدن ساب‌منو */
    .list-group-item[data-bs-toggle="collapse"] .transition-icon {
        transition: transform 0.25s ease-in-out;
    }

    .list-group-item[data-bs-toggle="collapse"][aria-expanded="true"] .transition-icon {
        transform: rotate(45deg);
    }
    /* اصلاح قرارگیری لایه پس‌زمینه مودال */
    .modal-backdrop {
        display: none !important;
    }


    /* تنظیم تراز وسط و فاصله مناسب از هدر */
    #addCheckModal .modal-dialog {
        margin: 3.5rem auto !important; /* فاصله مناسب از هدر بالایی */
        max-width: 800px; /* یا عرض دلخواه */
    }

    /* در صورت لزوم اطمینان از قرارگیری روی هدر چسبان */
    #addCheckModal {
        z-index: 9999 !important;
    }
    /* برای کتابخانه jalaliDatepicker (data-jdp) */
    jdp-container {
        z-index: 100050 !important;
    }

    /* برای کتابخانه persianDatepicker (کلاس pwt-datepicker) */
    .pwt-datepicker-input-view,
    .datepicker-container,
    .datepicker-plot-area {
        z-index: 100050 !important;
    }
    /* ✅ اطمینان از اسکرول داخل مودال */
    .modal-dialog-scrollable .modal-body {
        overflow-y: auto;
        max-height: calc(100vh - 200px); /* محدودیت ارتفاع نسبت به صفحه */
    }
    .modal-dialog-scrollable .modal-content {
        max-height: 100%;
    }



</style>

<a href="logoutAdmin.php" class="list-group-item list-group-item-action text-danger">
    <i class="bi bi-box-arrow-right me-2"></i>خروج
</a>
<br>
<div class="text-bg-info-50 small">خوش آمدید، <?php echo safeEcho($_SESSION['admin_username'] ?? 'ادمین') ?>
</div>
<br>
<form method="GET" action="dashboardAdmin.php">
    <!-- نگه داشتن تب فعلی در فرم -->
    <input type="hidden" name="tab" value="<?= htmlspecialchars($activeTab) ?>">

    <label for="project_id" class="form-label">🏢 نام پروژه:</label>
    <select name="project_id" class="form-select" onchange="this.form.submit()">
        <option value="">-- انتخاب پروژه --</option>
        <?php foreach ($projects as $project): ?>
            <option value="<?= (int)$project['id'] ?>" <?= ($selectedProjectId == $project['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($project['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>


<div class="mb-3 sidebar-menu-half">
    <div class="list-group">


        <a href="dashboardAdmin.php?tab=members&project_id=<?= $selectedProjectId ?>"
           class="list-group-item list-group-item-action <?= $activeTab === 'members' ? 'active' : '' ?>"
        style="font-weight: bold"

        >
            <i class="bi bi-people me-2"></i>مدیریت اعضای

        </a>
        <div class="list-group">
            <div class="list-group-item p-2">
                <!-- بخش هزینه با زیرمنو -->
                <div>
                    <div class="d-flex justify-content-between align-items-center p-2 cursor-pointer"
                         style="cursor: pointer;"
                         onclick="toggleCollapse('collapseExpenses', this)">

                        <div class="d-flex align-items-center">
                            <i class="bi bi-receipt me-2 <?= $activeTab === 'expenses' ? 'text-primary' : '' ?>"></i>
                            <a href="dashboardAdmin.php?tab=expenses&project_id=<?= (int)$selectedProjectId ?>"
                               class="text-decoration-none <?= $activeTab === 'expenses' ? 'fw-bold text-primary' : 'text-dark' ?>"
                               style="font-weight: bold"
                               onclick="event.stopPropagation();"> <!-- جلوگیری از بسته شدن منو هنگام کلیک روی متن -->
                                 <b>

                                مدیریت هزینه ها

                                 </b>
                            </a>
                        </div>

                        <i class="bi bi-plus-lg transition-icon"></i>
                    </div>

                    <!-- زیرمنو -->
                    <div id="collapseExpenses"  class="collapse <?= ($activeTab === 'expenses') ? 'show' : '' ?>">
                        <div class="mx-3 mb-3 ps-3  bg-light-subtle rounded">
                            <?php
                            $isExpenseOpen = in_array($activeTab, ['check', 'havaleh'], true);
                            ?>
                            <a href="dashboardAdmin.php?tab=check_out&project_id=<?= (int)$selectedProjectId ?>"
                               class="text-decoration-none d-flex align-items-center gap-2 <?= $activeTab === 'check' ? 'fw-bold text-primary' : 'text-dark' ?> border-bottom">
                                <i class="bi bi-check2-square fs-5"></i>
                                <span>چک</span>
                            </a>
                            <a href="dashboardAdmin.php?tab=remitance_out&project_id=<?= $selectedProjectId ?>"
                               class="text-decoration-none d-flex align-items-center gap-2 <?= $activeTab === 'havaleh' ? 'fw-bold text-primary' : 'text-dark' ?> border-bottom">
                                <i class="bi bi-receipt-cutoff fs-5"></i>
                                <span>حواله</span>
                            </a>
                            <a href="dashboardAdmin.php?tab=expenses&project_id=<?= $selectedProjectId ?>" class="d-block py-2 text-decoration-none text-dark small border-bottom">مدیریت فاکتور</a>
                            <a href="#" class="d-block py-2 text-decoration-none text-dark border-bottom small">مدیریت پیمانکاران</a>
                            <a href="#" class="d-block py-2 text-decoration-none text-dark border-bottom small">قراردادها</a>






                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="list-group">
            <!-- بخش درآمد -->
            <div class="list-group-item p-0 border rounded mb-2 bg-white">
                <!-- هدر منو -->
                <div class="d-flex justify-content-between align-items-center p-3 cursor-pointer"
                     style="cursor: pointer;"
                     onclick="toggleCollapse('collapseincome', this)">

                    <!-- ۱. آیکون + (سمت چپ) -->


                    <!-- ۲. ترکیب متن و آیکون اصلی (سمت راست) -->
                    <div class="d-flex align-items-end" >
                        <a href="dashboardAdmin.php?tab=income&project_id=<?= (int)$selectedProjectId ?>"
                           class="text-decoration-none me-2 <?= $activeTab === 'income' ? 'fw-bold text-primary' : 'text-dark' ?>"
                           onclick="event.stopPropagation();">
                            <i class="bi bi-receipt-cutoff fs-5 text-primary"></i>
                           <b> درآمد</b>
                        </a>
                        <i class="bi fs-5 <?= $activeTab === 'income' ? 'text-primary' : 'text-secondary' ?>"></i>
                    </div>
                    <i class="bi bi-plus-lg  transition-icon text-secondary"></i>
                </div>

                <!-- زیرمنو -->
                <?php
                // آرایه‌ای از تب‌های مرتبط با زیرمنوی درآمد
                $incomeTabs = ['check_input', 'remittance_input', 'presale_input', 'refund_input'];
                $isIncomeOpen = in_array($activeTab, $incomeTabs, true);
                ?>

                <div id="collapseincome" class="collapse <?= $isIncomeOpen ? 'show' : '' ?>">
                    <div class="mx-3 mb-3 ps-3 bg-light-subtle rounded">
                        <!-- چک دریافتی / ورودی -->
                        <a href="dashboardAdmin.php?tab=check_input&project_id=<?= (int)$selectedProjectId ?>"
                           class="d-block py-2 text-decoration-none small border-bottom text-start <?= $activeTab === 'check_input' ? 'fw-bold text-primary' : 'text-secondary' ?>">
                            چک
                        </a>

                        <!-- حواله دریافتی (remittance_input) -->
                        <a href="dashboardAdmin.php?tab=remittance_input&project_id=<?= (int)$selectedProjectId ?>"
                           class="d-block py-2 text-decoration-none small border-bottom text-start <?= $activeTab === 'remittance_input' ? 'fw-bold text-primary' : 'text-secondary' ?>">
                            حواله
                        </a>

                        <!-- پیش‌فروش -->
                        <a href="dashboardAdmin.php?tab=presale_input&project_id=<?= (int)$selectedProjectId ?>"
                           class="d-block py-2 text-decoration-none small border-bottom text-start <?= $activeTab === 'presale_input' ? 'fw-bold text-primary' : 'text-secondary' ?>">
                            پیشفروش
                        </a>

                        <!-- درآمد حاصل از مرجوعی -->
                        <a href="dashboardAdmin.php?tab=refund_input&project_id=<?= (int)$selectedProjectId ?>"
                           class="d-block py-2 text-decoration-none small text-start <?= $activeTab === 'refund_input' ? 'fw-bold text-primary' : 'text-secondary' ?>">
                            درآمد حاصل از مرجوعی و برگشت
                        </a>
                    </div>
                </div>

            </div>
        </div>


        <a href="dashboardAdmin.php?tab=finance&project_id=<?= $selectedProjectId ?>"
           class="list-group-item list-group-item-action <?= $activeTab === 'finance' ? 'active' : '' ?>">
            <i class="bi bi-cash-coin me-2"></i>بیلان مالی
        </a>
        <a href="javascript:void(0)"
           onclick="toggleSubmenu('suppliersSubmenu', this, event)"
           aria-expanded="<?= $activeTab === 'suppliers' ? 'true' : 'false' ?>"
           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $activeTab === 'suppliers' ? 'active' : '' ?>"
           style="font-weight: bold; cursor: pointer;">
    <span>
        <i class="bi bi-truck me-2"></i>تامین‌کنندگان/پیمانکاران
    </span>
            <i class="bi bi-plus-lg transition-icon <?= $activeTab === 'suppliers' ? 'rotate-45 text-white' : 'text-secondary' ?>"></i>
        </a>

        <!-- زیرمنو -->
        <div class="collapse <?= $activeTab === 'suppliers' ? 'show' : '' ?>" id="suppliersSubmenu">
            <div class="list-group list-group-flush ps-3">
                <a href="dashboardAdmin.php?tab=suppliers&project_id=<?= $selectedProjectId ?>" class="list-group-item list-group-item-action d-block py-2 text-decoration-none text-dark small border-bottom text-start">
                    قراردادها
                </a>
            </div>
        </div>


        <a href="dashboardAdmin.php?tab=tickets&project_id=<?= $selectedProjectId ?>"
           class="list-group-item list-group-item-action <?= $activeTab === 'tickets' ? 'active' : '' ?>">
            <i class="bi bi-chat-dots me-2"></i>تیکت
        </a>
    </div>
</div>
<br>
<br>
<br>
<?php if ($activeTab === 'members'): ?>

    <div class="card-header" >
        <div class="card-body">


            <h5 class="mb-3">اعضای پروژه</h5>
            <div class="row">
                <div class="mb-3 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="memberSearchInput" class="form-control"  placeholder="جستجوی آنی نام یا نام خانوادگی...">
                    </div>
                </div>
            </div>
            <?php if ($selectedProjectId <= 0): ?>
                <div class="alert alert-warning">لطفاً ابتدا یک پروژه انتخاب کنید.</div>

            <?php elseif (empty($projectMembers)): ?>
                <div class="alert alert-info">برای این پروژه عضوی ثبت نشده است.</div>

            <?php else: ?>
                <div class="table-responsive" >
                    <table id="membersTable" class="table table-bordered table-hover text-center align-middle">
                        <thead>
                        <tr>
                            <th>ردیف</th>
                            <th>نام و نام خانوادگی</th>
                            <th>مدارک</th>
                            <th>مالی</th>
                            <th>جمع واریزی</th>
                            <th>میزان بدهی</th>
                        </tr>
                        </thead>
                        <tbody id="projectMembersList">
                        <?php foreach ($projectMembers as $i => $member): ?>
                            <?php
                            $_SESSION['fullName']=$member['full_name'];
                            echo "<!-- User ID: " . ($member['user_project_id'] ?? 'Not Found') . " -->";
                            ?>
                            <tr class="member-row">
                                <td><?= (int)$member['user_project_id'] ?></td>
                                <td  class="member-name">
                                    <a href="profileuser.php?user_project_id=<?= (int)$member['user_project_id'] ?>">
                                        <?= htmlspecialchars($_SESSION['fullName']) ?>
                                    </a>
                                </td>
                                <td>
                                    <?php
                                    $_SESSION['full_name']=trim(($member['full_name'] ?? ''));

                                    echo "<!-- User ID: " . ($member['member_record_id'] ?? 'Not Found') . " -->";
                                    ?>
                                    <a href="showDocuments.php?id=<?= (int)$member['user_project_id'] ?>&full_name=<?= trim(($member['full_name'] ?? '')) ?>&project_id=<?= (int)$selectedProjectId ?>"
                                       class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm"
                                       style="transition: all 0.3s ease;">
                                        <i class="bi bi-file-earmark-text me-1"></i> مشاهده
                                    </a>
                                </td>
                                <td>
                                    <a href="member_statements.php?member_name=<?= urlencode($_SESSION['fullName'])  ?>&user_project_id=<?= (int)$member['user_project_id'] ?>&project_id=<?= $selectedProjectId ?>"
                                       class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm"
                                       style="transition: all 0.3s ease;">
                                        <i class="bi bi-cash-coin me-1"></i> مشاهده
                                    </a>
                                </td>
                                <td class="text-center fw-bold">
                                    <?php
                                    // ۱. استخراج کد ملی از آرایه عضو
                                    $n_code = $member['national_code'] ?? '';

                                    // ۲. استخراج آیدی پروژه و آیدی عضو مربوط به سطر جاری
                                    $p_id = (int)($member['project_id'] ?? 0);
                                    $u_p_id = (int)($member['user_project_id'] ?? 0);

                                    $total_paid = 0;

                                    if (!empty($n_code) && $u_p_id > 0) {
                                        // ۳. کوئری دقیق با اضافه شدن کلمه کلیدی FROM
                                        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(ms.paid_amount), 0)
            FROM member_statements AS ms
            INNER JOIN projects AS p ON ms.project_id = p.id
            INNER JOIN users_project AS up ON ms.users_project_id = up.id
            WHERE ms.project_id = :project_id
            AND ms.users_project_id = :user_project_id
        ");

                                        // مقادیر دقیقاً بر اساس اطلاعات عضوِ این ردیف فرستاده می‌شوند
                                        $stmt->execute([
                                                ':project_id'      => $p_id,
                                                ':user_project_id' => $u_p_id
                                        ]);

                                        $total_paid = (float)$stmt->fetchColumn();
                                    }

                                    // نمایش مبلغ (مثلاً با فرمت سه‌رقم سه‌رقم)

                                    ?>

                                    <span class="text-primary">
                                        <?php echo number_format($total_paid);?>
                                    </span>
                                    <small class="text-muted" style="font-size: 0.7rem;">ریال</small>
                                    <i class="bi bi-cash-stack text-success ms-1"></i>
                                </td>


                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($activeTab === 'suppliers'): ?>

    <div class="card mt-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">لیست پیمانکاران</h5>

                <?php if ($selectedProjectId > 0): ?>
                    <a href="create_new_contractor.php?project_id=<?= (int)$selectedProjectId ?>"
                       class="btn btn-primary btn-sm">
                        + ثبت پیمانکار جدید
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($selectedProjectId <= 0): ?>

                <div class="alert alert-warning">لطفاً ابتدا یک پروژه انتخاب کنید.</div>

            <?php elseif (empty($contractors)): ?>

                <div class="alert alert-info">برای این پروژه پیمانکاری ثبت نشده است.</div>

            <?php else:
                ?>


                <div class="table-responsive">
                    <table id="contractorsTable" class="table table-bordered table-hover text-center align-middle">
                        <thead>
                        <tr>
                            <th>ردیف</th>
                            <th>نام تصویر</th>
                            <th>تصویر</th>
                            <th>نام پیمانکار</th>
                            <th>شماره تماس</th>
                            <th>کد ملی</th>
                            <th>عنوان شغلی</th>
                            <th>عملیات</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php if (!empty($contractors)): ?>
                            <?php $i = (($contractorPage - 1) * $contractorPerPage) + 1; ?>
                            <?php foreach ($contractors as $contractor): ?>
                                <tr>
                                    <td><?= $i++ ?></td>

                                    <td>
                                        <?= htmlspecialchars(basename($contractor['document_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?: 'ندارد' ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($contractor['document_image'])): ?>
                                            <img
                                                    src="<?= htmlspecialchars($contractor['document_image'], ENT_QUOTES, 'UTF-8') ?>"
                                                    alt="profile"
                                                    style="width:50px;height:50px;object-fit:cover;border-radius:6px;"
                                            >
                                        <?php else: ?>
                                            <span class="text-muted">ندارد</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(trim(($contractor['first_name'] ?? '') . ' ' . ($contractor['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                    </td>

                                    <td><?= htmlspecialchars($contractor['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($contractor['national_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($contractor['job_title'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>

                                    <td>
                                        <a href="edit_contractor.php?id=<?= (int)$contractor['id'] ?>&project_id=<?= (int)$selectedProjectId ?>"
                                           class="btn btn-sm btn-outline-warning">
                                            ویرایش
                                        </a>

                                        <a href="delete_contractor.php?id=<?= (int)$contractor['id'] ?>&project_id=<?= (int)$selectedProjectId ?>&tab=suppliers"
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('آیا از حذف این پیمانکار مطمئن هستید؟')">
                                            حذف
                                        </a>
                                        <?php if (!empty($contractor['document_image'])): ?>
                                            <a href="<?= htmlspecialchars($contractor['document_image'], ENT_QUOTES, 'UTF-8') ?>"
                                               target="_blank"
                                               class="btn btn-sm btn-outline-info">
                                                مشاهده تصویر
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-muted">موردی یافت نشد</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>



            <?php endif; ?>
        </div>
    </div>

<?php elseif ($activeTab === 'expenses'): ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <?php
        $tab = $_GET['tab'] ?? 'dashboard'; // تب پیش‌فرض

        switch ($tab) {
            case 'expenses':
                include __DIR__ . '/factor_list.php';
                break;


            // سایر تب‌ها...
            default:
                break;
        }
        ?>
    </main>

<?php elseif ($activeTab==='tickets') : ?>
    <div class="card mt-4">
        <div class="card-body">
            <h5 class="mb-3">لیست تیکت ها</h5>

            <?php if ($selectedProjectId <= 0): ?>
                <div class="alert alert-warning">لطفاً ابتدا یک پروژه انتخاب کنید.</div>
            <?php elseif (empty($tickets)): ?>
                <div class="alert alert-info">برای این پروژه عضوی ثبت نشده است.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="membersTable" class="table table-bordered table-hover text-center align-middle">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>عنوان</th>
                            <th>کاربر</th>
                            <th>پروژه</th>
                            <th>وضعیت</th>
                            <th>تاریخ</th>
                            <th>عملیات</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($tickets as $i => $ticket): ?>
                            <tr>
                                <td><?= $i+1 ?></td>
                                <td><?= safeEcho($ticket['title']) ?></td>
                                <td><?= safeEcho($ticket['display_name']) ?></td>
                                <td><?= safeEcho($ticket['project_name'] ?? 'بدون پروژه') ?></td>
                                <td>
                                    <?php if($ticket['status'] === 'open'): ?>
                                        <span class="badge bg-warning">باز</span>
                                    <?php elseif($ticket['status'] === 'pending'): ?>
                                        <span class="badge bg-info">در انتظار پاسخ</span>
                                    <?php elseif($ticket['status'] === 'answered'): ?>
                                        <span class="badge bg-success">پاسخ داده شد</span>
                                    <?php elseif($ticket['status'] === 'closed'): ?>
                                        <span class="badge bg-danger">بسته</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= safeEcho($ticket['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= safeEcho($ticket['created_at']) ?></td>
                                <td>
                                    <a href="ticketDetails.php?id=<?= $ticket['id'] ?>"
                                       class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-eye"></i> مشاهده
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>
        </div>
    </div>
<?php elseif ($activeTab === 'remitance_out'): ?>
    <h1 class="h1 mb-3">
        لیست حواله ها
    </h1>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 my-3">
            <!-- بخش دکمه‌های عملیاتی -->
            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- دکمه جست‌وجوی پیشرفته -->
                <button type="button"
                        class="btn btn-primary d-inline-flex align-items-center gap-1 shadow-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#advancedSearchModal">
                    <i class="bi bi-funnel"></i>
                    <span>جست‌وجوی پیشرفته</span>
                </button>

                <!-- دکمه درج حواله جدید -->
                <button type="button"
                        class="btn btn-outline-primary d-inline-flex align-items-center gap-1 shadow-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#addRemitanceOutModal">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span>درج حواله جدید</span>
                </button>
            </div>

            <!-- مجموع برداشت بر اساس فیلترهای فعال -->
            <div class="d-flex align-items-center">
                <div class="badge bg-danger-subtle text-danger border border-danger-subtle fs-6 py-2 px-3 fw-medium d-inline-flex align-items-center gap-1">
                    <i class="bi bi-wallet2"></i>
                    <span>مجموع برداشت:</span>
                    <strong class="mx-1 fs-6 font-monospace"><?= number_format((float)($totalWithdrawalAmount ?? 0)) ?></strong>
                    <small class="fw-normal">ریال</small>
                </div>
            </div>
        </div>



        <!-- فرم کوچک برای تغییر آنی تعداد ردیف در بالای جدول -->
        <form method="GET" action="" class="d-flex align-items-center gap-2 m-0">
            <!-- حفظ تمام فیلترهای فعال فعلی هنگام تغییر تعداد ردیف -->
            <?php foreach ($_GET as $key => $val): ?>
                <?php if (!in_array($key, ['per_page', 'page'], true) && is_string($val)): ?>
                    <input type="hidden" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <input type="hidden" name="page" value="1">

            <label for="tablePerPage" class="form-label mb-0 text-nowrap small text-muted">
                <i class="bi bi-list-ol me-1"></i>تعداد نمایش:
            </label>
            <select name="per_page" id="tablePerPage" class="form-select form-select-sm" style="width: 120px;" onchange="this.form.submit()">
                <option value="10" <?= ($perPage ?? 25) == 10 ? 'selected' : '' ?>>۱۰ ردیف</option>
                <option value="25" <?= ($perPage ?? 25) == 25 ? 'selected' : '' ?>>۲۵ ردیف</option>
                <option value="50" <?= ($perPage ?? 25) == 50 ? 'selected' : '' ?>>۵۰ ردیف</option>
                <option value="100" <?= ($perPage ?? 25) == 100 ? 'selected' : '' ?>>۱۰۰ ردیف</option>
            </select>
            <label class="text-muted small mb-0">
                از
                <strong><?= number_format($totalRecords ?? 0) ?></strong>
            </label>
        </form>
    </div>

    <div class="table-responsive shadow-sm rounded border">
        <table class="table table-bordered table-hover text-center align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>ردیف</th>

                <!-- ستون سورت‌شونده تاریخ -->
                <?php
                $orderDirCurrent = strtolower($orderDir ?? ($_GET['order_dir'] ?? 'desc'));
                $toggleDir = ($orderDirCurrent === 'asc') ? 'desc' : 'asc';

                $queryParams = $_GET ?? [];
                $queryParams['order_by']  = 'history';
                $queryParams['order_dir'] = $toggleDir;
                $sortDateUrl = '?' . http_build_query($queryParams);
                ?>
                <th class="text-center align-middle">
                    <a href="<?= htmlspecialchars($sortDateUrl, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none text-dark d-inline-flex align-items-center justify-content-center gap-1 w-100">
                        <span>تاریخ</span>
                        <?php if (($orderBy ?? '') === 'history'): ?>
                            <i class="bi <?= strtolower($orderDir ?? 'desc') === 'asc' ? 'bi-sort-numeric-down text-primary' : 'bi-sort-numeric-up-alt text-primary' ?> fs-6"></i>
                        <?php else: ?>
                            <i class="bi bi-arrow-down-up text-muted opacity-50 fs-6"></i>
                        <?php endif; ?>
                    </a>
                </th>

                <th>سند</th>

                <!-- ستون سورت‌شونده مبلغ برداشت -->
                <?php
                $paramsHarvest = $_GET;
                $paramsHarvest['order_by'] = 'harvest';

                $currentOrderBy  = $_GET['order_by'] ?? 'history';
                $currentOrderDir = strtolower($_GET['order_dir'] ?? 'desc');

                $paramsHarvest['order_dir'] =
                        ($currentOrderBy === 'harvest' && $currentOrderDir === 'asc')
                                ? 'desc'
                                : 'asc';

                $sortHarvestUrl = '?' . http_build_query($paramsHarvest);
                ?>

                <th class="text-center align-middle">
                    <a href="<?= htmlspecialchars($sortHarvestUrl, ENT_QUOTES, 'UTF-8') ?>"
                       class="text-decoration-none text-dark d-inline-flex align-items-center justify-content-center gap-1 w-100">
                        <span>برداشت مبلغ (ریال)</span>
                        <?php if ($currentOrderBy === 'harvest'): ?>
                            <i class="bi <?= $currentOrderDir === 'asc'
                                    ? 'bi-sort-numeric-down text-primary'
                                    : 'bi-sort-numeric-up-alt text-primary' ?> fs-6"></i>
                        <?php else: ?>
                            <i class="bi bi-arrow-down-up text-muted opacity-50 fs-6"></i>
                        <?php endif; ?>
                    </a>
                </th>

                <th>ذی‌نفع</th>
                <th>سرفصل</th>
                <th>زیرفصل</th>
                <th>بانک/حساب</th>
                <th>ورودی/خروجی</th>
            </tr>
            </thead>

            <tbody>
            <?php
            $tableRows = $expenses ?? $remittance_output_rows ?? [];
            ?>
            <?php if (!empty($tableRows)): ?>
                <?php foreach ($tableRows as $i => $row): ?>
                    <tr>
                        <td><?= ($offset ?? 0) + $i + 1 ?></td>
                        <td><?= htmlspecialchars($row['history'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <!-- ستون سند -->
                        <td>
                            <?php if (!empty($row['document'])): ?>
                                <button type="button"
                                        class="btn btn-link text-decoration-none fw-semibold text-primary p-0 m-0 border-0"
                                        onclick="showDocumentDetails(this)"
                                        data-doc="<?= htmlspecialchars($row['document'] ?? '-', ENT_QUOTES, 'UTF-8') ?>"
                                        data-history="<?= htmlspecialchars($row['history'] ?? '-', ENT_QUOTES, 'UTF-8') ?>"
                                        data-amount="<?= !empty($row['harvest']) ? number_format((float) $row['harvest']) . ' ریال' : '-' ?>"
                                        data-beneficiary="<?= htmlspecialchars($row['beneficiary'] ?? '-', ENT_QUOTES, 'UTF-8') ?>"
                                        data-transaction="<?= htmlspecialchars($row['transaction'] ?? '-', ENT_QUOTES, 'UTF-8') ?>"
                                        data-subchapter="<?= htmlspecialchars($row['subchapter'] ?? '-', ENT_QUOTES, 'UTF-8') ?>"
                                        data-description="<?= htmlspecialchars($row['description'] ?? $row['tozih'] ?? $row['comments'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="bi bi-file-earmark-text me-1"></i>
                                    <?= htmlspecialchars($row['document'], ENT_QUOTES, 'UTF-8') ?>
                                </button>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap fw-bold text-danger">
                            <?= !empty($row['harvest']) ? number_format((float) $row['harvest']) : '-' ?>
                        </td>
                        <td><?= htmlspecialchars($row['beneficiary'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['transaction'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['subchapter'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['bank/hesab'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['voroodi/khoroujy'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border-0 rounded-circle p-1"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        data-bs-boundary="window"
                                        aria-expanded="false"
                                        style="width: 32px; height: 32px;">
                                    <i class="bi bi-three-dots-vertical text-secondary"></i>
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 small">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-info btn-open-remittance"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewRemitanceOutModal"
                                            data-remitance-out-id="<?= (int)$row['id'] ?>"
                                            data-project-id="<?= (int)($selectedProjectId ?? 0) ?>"
                                            title="مشاهده جزئیات">
                                        <i class="bi bi-eye"></i>
                                    </button>


                                    <li><hr class="dropdown-divider my-1"></li>

                                    <!-- ویرایش -->
                                    <li>
                                        <a class="dropdown-item py-2 btn-edit-remitance-out"
                                           href="javascript:void(0)"
                                           data-remitance-out-id="<?= (int)$row['id'] ?>"
                                           data-project-id="<?= (int)($row['project_id'] ?? ($project_id ?? 0)) ?>">
                                            <i class="bi bi-pencil-square text-warning me-2"></i> ویرایش
                                        </a>

                                    </li>

                                    <li><hr class="dropdown-divider my-1"></li>

                                    <!-- باز کردن مودال حذف -->
                                    <li>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteRemitanceOutModal"
                                                data-remitance-out-id="<?= (int)$row['id'] ?>"
                                                data-project-id="<?= (int)($selectedProjectId ?? $row['project_id'] ?? 0) ?>"
                                                data-remitance-out-info="شماره سند <?= htmlspecialchars((string)($row['document'] ?: 'ثبت‌نشده')) ?> (در وجه <?= htmlspecialchars((string)(!empty($row['beneficiary']) ? $row['beneficiary'] : 'نامشخص')) ?>)">
                                            <i class="bi bi-trash"></i>
                                            حذف حواله
                                        </button>


                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php elseif (in_array($activeTab, ['remitance_out', 'remittance_out'],true)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                        موردی مطابق با فیلترهای انتخابی یافت نشد.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- نوار صفحه‌بندی -->
        <?php if (!empty($totalPages) && $totalPages > 1): ?>
            <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-2 border-top bg-light">
                <div class="text-muted small">
                    نمایش <?= count($expenses) ?> از <?= number_format($totalRecords ?? 0) ?> ردیف (صفحه <?= $page ?? 1 ?> از <?= $totalPages ?>)
                </div>

                <nav aria-label="صفحه‌بندی">
                    <ul class="pagination pagination-sm mb-0">
                        <?php $pageParams = $_GET; ?>

                        <!-- دکمه قبلی -->
                        <li class="page-item <?= (($page ?? 1) <= 1) ? 'disabled' : '' ?>">
                            <?php $pageParams['page'] = max(1, ($page ?? 1) - 1); ?>
                            <a class="page-link" href="?<?= htmlspecialchars(http_build_query($pageParams), ENT_QUOTES, 'UTF-8') ?>">قبلی</a>
                        </li>

                        <!-- شماره صفحات -->
                        <?php for ($p = max(1, ($page ?? 1) - 2); $p <= min($totalPages, ($page ?? 1) + 2); $p++): ?>
                            <?php $pageParams['page'] = $p; ?>
                            <li class="page-item <?= ($p === ($page ?? 1)) ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= htmlspecialchars(http_build_query($pageParams), ENT_QUOTES, 'UTF-8') ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>

                        <!-- دکمه بعدی -->
                        <li class="page-item <?= (($page ?? 1) >= $totalPages) ? 'disabled' : '' ?>">
                            <?php $pageParams['page'] = min($totalPages, ($page ?? 1) + 1); ?>
                            <a class="page-link" href="?<?= htmlspecialchars(http_build_query($pageParams), ENT_QUOTES, 'UTF-8') ?>">بعدی</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>

    <!-- مودال جست‌وجوی پیشرفته -->
    <div class="modal fade"
         id="advancedSearchModal"
         tabindex="-1"
         aria-labelledby="advancedSearchModalLabel"
         aria-hidden="true"
         dir="rtl">

        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header d-flex justify-content-between align-items-center">
                    <h5 class="modal-title m-0" id="advancedSearchModalLabel">
                        <i class="bi bi-funnel-fill text-primary ms-2"></i>
                        جست‌وجوی پیشرفته حواله‌ها
                    </h5>
                    <button type="button"
                            class="btn-close m-0"
                            data-bs-dismiss="modal"
                            aria-label="بستن"></button>
                </div>

                <form method="GET" action="">
                    <div class="modal-body">
                        <!-- تب فعال -->
                        <input type="hidden" name="tab" value="remitance_out">

                        <!-- بازگشت به صفحه اول در زمان فیلتر جدید -->
                        <input type="hidden" name="page" value="1">

                        <!-- پروژه انتخاب‌شده -->
                        <?php if (!empty($selectedProjectId)): ?>
                            <input type="hidden" name="project_id" value="<?= htmlspecialchars($selectedProjectId, ENT_QUOTES, 'UTF-8') ?>">
                        <?php else : ?>
                            <div class="alert-danger">
                                <?php echo "پروژه مورد نظر را انتخاب کنید"?>
                            </div>
                        <?php endif; ?>

                        <!-- مرتب‌سازی -->
                        <input type="hidden" name="order_by" value="<?= htmlspecialchars($orderBy ?? 'history', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="order_dir" value="<?= htmlspecialchars(strtolower($orderDir ?? 'DESC'), ENT_QUOTES, 'UTF-8') ?>">

                        <div class="row g-3">
                            <?php
                            $current_bank_hesab = trim($_GET['bank/hesab'] ?? '');
                            $current_beneficiary = trim($_GET['beneficiary'] ?? '');
                            ?>
                            <div class="row g-3">

                                <!-- بانک/حساب -->
                                <div class="col-md-6 position-relative">
                                    <label class="form-label">بانک/حساب</label>
                                    <input type="hidden" name="bank/hesab" id="selectedBankInput"
                                           value="<?= htmlspecialchars($current_bank_hesab, ENT_QUOTES, 'UTF-8') ?>">

                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary dropdown-toggle w-100 text-end d-flex justify-content-between align-items-center bg-white"
                                                type="button" id="bankDropdownBtn"
                                                data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                <span id="bankBtnText" class="text-truncate">
                    <?= $current_bank_hesab !== '' ? htmlspecialchars($current_bank_hesab, ENT_QUOTES, 'UTF-8') : 'انتخاب یا جست‌وجوی حساب...' ?>
                </span>
                                        </button>

                                        <div class="dropdown-menu w-100 p-2 shadow" aria-labelledby="bankDropdownBtn" style="max-height: 280px;">
                                            <input type="text" class="form-control form-control-sm mb-2" id="bankSearchInput"
                                                   placeholder="تایپ کنید تا فیلتر شود..." autocomplete="off">

                                            <div class="overflow-auto" style="max-height: 200px;" id="bankItemsList">
                                                <a class="dropdown-item py-2 rounded <?= $current_bank_hesab === '' ? 'active' : '' ?>"
                                                   href="#" data-val="">-- همه حساب‌ها --</a>
                                                <?php
                                                $bank_hesabMap = $bank_hesabMap ?? [];
                                                if (!is_array($bank_hesabMap))
                                                    $bank_hesabMap = [];
                                                ?>
                                                <?php foreach (array_keys($bank_hesabMap) as $bankName): ?>
                                                    <a class="dropdown-item py-2 rounded <?= ($current_bank_hesab === $bankName) ? 'active' : '' ?>"
                                                       href="#"
                                                       data-val="<?= htmlspecialchars($bankName, ENT_QUOTES, 'UTF-8') ?>">
                                                        <?= htmlspecialchars($bankName, ENT_QUOTES, 'UTF-8') ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ذی‌نفع (وابسته به حساب) -->
                                <div class="col-md-6 position-relative">
                                    <label class="form-label">ذی‌نفع</label>
                                    <input type="hidden" name="beneficiary" id="selectedbeneficiaryInput"
                                           value="<?= htmlspecialchars($current_beneficiary, ENT_QUOTES, 'UTF-8') ?>">

                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary dropdown-toggle w-100 text-end d-flex justify-content-between align-items-center bg-white"
                                                type="button" id="beneficiaryDropdownBtn"
                                                data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                <span id="beneficiaryBtnText" class="text-truncate">
                    <?= $current_beneficiary !== '' ? htmlspecialchars($current_beneficiary, ENT_QUOTES, 'UTF-8') : 'انتخاب یا جست‌وجوی ذینفع...' ?>
                </span>
                                        </button>

                                        <div class="dropdown-menu w-100 p-2 shadow" aria-labelledby="beneficiaryDropdownBtn" style="max-height: 280px;">
                                            <input type="text" class="form-control form-control-sm mb-2" id="beneficiarySearchInput"
                                                   placeholder="تایپ کنید تا فیلتر شود..." autocomplete="off">

                                            <div class="overflow-auto" style="max-height: 200px;" id="beneficiaryItemsList">
                                                <a class="dropdown-item py-2 rounded <?= $current_beneficiary === '' ? 'active' : '' ?>"
                                                   href="#" data-val="">-- همه ذی‌نفع‌ها --</a>
                                                <!-- با JS پر می‌شود -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <!-- انتخاب سرفصل -->
                                <div class="col-md-6 position-relative">
                                    <label class="form-label">سرفصل</label>

                                    <!-- اصلاح مقدار value با استفاده از ?? '' برای جلوگیری از خطا -->
                                    <input type="hidden" name="transaction" id="selectedtransactionInput"
                                           value="<?= htmlspecialchars($transaction ?? '', ENT_QUOTES, 'UTF-8') ?>">

                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary dropdown-toggle w-100 text-end d-flex justify-content-between align-items-center bg-white"
                                                type="button" id="transactionDropdownBtn"
                                                data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">

                                            <!-- اصلاح متن نمایش داده شده در دکمه -->
                                            <span id="transactionBtnText" class="text-truncate">
                <?= htmlspecialchars($current_transaction ?? 'همه سرفصل‌ها', ENT_QUOTES, 'UTF-8') ?>
            </span>
                                        </button>

                                        <div class="dropdown-menu w-100 p-2 shadow" aria-labelledby="transactionDropdownBtn" style="max-height: 280px;">
                                            <input type="text" class="form-control form-control-sm mb-2" id="transactionSearchInput"
                                                   placeholder="تایپ کنید تا فیلتر شود..." autocomplete="off">

                                            <div class="overflow-auto" style="max-height: 200px;" id="transactionItemsList">
                                                <a class="dropdown-item py-2 rounded" href="#" data-val="">-- همه سرفصل‌ها --</a>
                                                <!-- با JS پر می‌شود -->
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <!-- انتخاب زیرفصل -->
                                <div class="col-md-6">
                                    <label class="form-label">زیرفصل</label>
                                    <select name="subchapter" id="filterSubchapter" class="form-select select2-searchable" <?= empty($currentTransaction) ? 'disabled' : '' ?>>
                                        <option value="">-- همه زیرفصل‌ها --</option>
                                    </select>
                                </div>
                            </div>



                            <div class="col-md-6">
                                <label for="document" class="form-label">سند</label>
                                <select id="modaldocument" name="document" class="form-select">
                                    <option value="">-- همه سندها --</option>
                                    <?php if (!empty($documentList)): ?>
                                        <?php foreach ($documentList as $account): ?>
                                            <option value="<?= htmlspecialchars($account, ENT_QUOTES, 'UTF-8') ?>"
                                                    <?= (isset($_GET['document']) && $_GET['document'] === $account) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($account, ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="modalDateFrom" class="form-label">از تاریخ</label>
                                <input type="text"
                                       id="modalDateFrom"
                                       name="date_from"
                                       class="form-control persian-date"
                                       autocomplete="off"
                                       value="<?= htmlspecialchars($_GET['date_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="مثلاً ۱۴۰۳/۰۱/۰۱">
                            </div>

                            <div class="col-md-6">
                                <label for="modalDateTo" class="form-label">تا تاریخ</label>
                                <input type="text"
                                       id="modalDateTo"
                                       name="date_to"
                                       class="form-control persian-date"
                                       autocomplete="off"
                                       value="<?= htmlspecialchars($_GET['date_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="مثلاً ۱۴۰۳/۱۲/۲۹">
                            </div>

                            <div class="col-md-6">
                                <label for="modalMinAmount" class="form-label">حداقل مبلغ</label>
                                <input type="text"
                                       id="modalMinAmount"
                                       name="min_amount"
                                       class="form-control"
                                       inputmode="decimal"
                                       value="<?= htmlspecialchars($_GET['min_amount'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="حداقل مبلغ">
                            </div>

                            <div class="col-md-6">
                                <label for="modalMaxAmount" class="form-label">حداکثر مبلغ</label>
                                <input type="text"
                                       id="modalMaxAmount"
                                       name="max_amount"
                                       class="form-control"
                                       inputmode="decimal"
                                       value="<?= htmlspecialchars($_GET['max_amount'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="حداکثر مبلغ">
                            </div>

                        </div>

                    </div>

                    <div class="modal-footer">
                        <a href="?<?= htmlspecialchars(http_build_query([
                                'tab' => 'remitance_out',
                                'order_by' => $orderBy ?? 'history',
                                'order_dir' => strtolower($orderDir ?? 'DESC'),
                                'project_id'=>$selectedProjectId
                        ]), ENT_QUOTES, 'UTF-8') ?>"
                           class="btn btn-outline-secondary">
                            پاک‌کردن فیلترها
                        </a>

                        <button type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                            انصراف
                        </button>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i>
                            اعمال فیلتر
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- مودال نمایش توضیحات سند -->
    <div class="modal fade" id="documentDetailModal" tabindex="-1" aria-hidden="true" dir="rtl" style="z-index: 99999 !important;">
        <div class="modal-dialog modal-dialog-centered" style="z-index: 100000 !important;">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="modal-title m-0 text-primary fs-6">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        مشخصات سند: <span id="modalDocTitle" class="fw-bold text-dark"></span>
                    </h5>
                    <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>

                <div class="modal-body p-4">
                    <table class="table table-sm table-bordered text-start align-middle mb-3">
                        <tbody>
                        <tr>
                            <th class="table-light w-25">تاریخ:</th>
                            <td id="modalDocDate">-</td>
                        </tr>
                        <tr>
                            <th class="table-light">مبلغ:</th>
                            <td id="modalDocAmount" class="fw-bold text-danger">-</td>
                        </tr>
                        <tr>
                            <th class="table-light">ذی‌نفع:</th>
                            <td id="modalDocBeneficiary">-</td>
                        </tr>
                        <tr>
                            <th class="table-light">سرفصل / زیرفصل:</th>
                            <td><span id="modalDocChapter">-</span> / <span id="modalDocSubchapter">-</span></td>
                        </tr>
                        </tbody>
                    </table>

                    <div class="mb-0">
                        <label class="form-label fw-bold text-secondary small mb-1">
                            <i class="bi bi-card-text me-1"></i> شرح / توضیحات سند:
                        </label>
                        <div id="modalDocDescription" class="p-3 bg-light border rounded" style="min-height: 70px; white-space: pre-wrap; line-height: 1.7;">
                            توضیحاتی برای این سند ثبت نشده است.
                        </div>
                    </div>
                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                        بستن
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="addRemitanceOutModal" tabindex="-1" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-0 rounded-3">

                <!-- هدر مودال -->
                <div class="modal-header bg-light border-bottom py-3">
                    <h5 class="modal-title fs-5 fw-bold text-dark d-flex align-items-center gap-2" id="addRemitanceOutModalLabel">
                        <i class="bi bi-card-checklist text-danger fs-4"></i>
                        <span>ثبت و درج حواله جدید</span>
                    </h5>
                    <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>

                <form action="add_remitance_output.php" method="POST" id="addRemitanceOutForm">
                    <!-- فیلدهای سیستمی ضروری -->
                    <input type="hidden" name="action" value="add_remitance_output">
                    <input type="hidden" name="project_id" value="<?= htmlspecialchars((string)($selectedProjectId ?? $_GET['project_id'] ?? '')) ?>">

                    <div class="modal-body p-4" id="addRemitanceModalBody">
                        <div class="row g-3">

                            <!-- کد شعبه -->
                            <div class="col-md-6">
                                <label for="remit_branch_code" class="form-label fw-medium text-secondary">
                                    کد شعبه <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-folder2-open text-muted"></i></span>
                                    <input type="text"
                                           id="remit_branch_code"
                                           name="branch_code"
                                           class="form-control"
                                           placeholder="مثال: ۱۰۲۵"
                                           dir="ltr"
                                           inputmode="numeric"
                                           required>
                                </div>
                            </div>

                            <!-- شعبه -->
                            <div class="col-md-6">
                                <label for="remit_branch" class="form-label fw-medium text-secondary">
                                    شعبه <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-tag text-muted"></i></span>
                                    <input type="text"
                                           name="branch"
                                           id="remit_branch"
                                           class="form-control"
                                           placeholder="مثال: مرکزی، آزادی"
                                           required>
                                </div>
                            </div>

                            <!-- تاریخ -->
                            <div class="col-md-6">
                                <label for="remit_history" class="form-label fw-medium text-secondary">تاریخ</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-calendar3 text-muted"></i></span>
                                    <input type="text"
                                           id="remit_history"
                                           name="history"
                                           class="form-control text-start pwt-datepicker font-monospace"
                                           data-jdp
                                           dir="ltr"
                                           placeholder="۱۴۰۳/۰۱/۰۱">
                                </div>
                            </div>

                            <!-- ساعت -->
                            <div class="col-md-6">
                                <label for="remit_clock" class="form-label fw-medium text-secondary">ساعت</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-clock text-muted"></i></span>
                                    <input type="text"
                                           id="remit_clock"
                                           name="clock"
                                           class="form-control text-start font-monospace"
                                           dir="ltr"
                                           placeholder="مثال: 14:30">
                                </div>
                            </div>

                            <!-- سند -->
                            <div class="col-md-6">
                                <label for="remit_document" class="form-label fw-medium text-secondary">شماره سند</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-file-earmark-text text-muted"></i></span>
                                    <input type="text"
                                           id="remit_document"
                                           name="document"
                                           class="form-control text-start font-monospace"
                                           dir="ltr"
                                           placeholder="مثال: ۱۲۳۴۵">
                                </div>
                            </div>

                            <!-- برداشت (ریال) -->
                            <div class="col-md-6">
                                <label for="remit_harvest" class="form-label fw-medium text-secondary">
                                    مبلغ برداشت (ریال) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">ریال</span>
                                    <input type="text"
                                           name="harvest"
                                           id="remit_harvest"
                                           class="form-control text-start fw-bold font-monospace"
                                           dir="ltr"
                                           inputmode="numeric"
                                           placeholder="۰"
                                           required>
                                </div>
                            </div>

                            <!-- شماره قبض -->
                            <div class="col-md-6">
                                <label for="remit_invoice_number" class="form-label fw-medium text-secondary">
                                    شماره قبض <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-receipt text-muted"></i></span>
                                    <input type="text"
                                           id="remit_invoice_number"
                                           name="invoice_number"
                                           class="form-control text-start font-monospace"
                                           dir="ltr"
                                           inputmode="numeric"
                                           placeholder="مثال: ۹۸۷۶۵۴۳۲۱"
                                           required>
                                </div>
                            </div>

                            <!-- واریز -->
                            <div class="col-md-3">
                                <label for="remit_diposit" class="form-label fw-medium text-secondary">واریز</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-arrow-down-left-circle text-muted"></i></span>
                                    <input type="text"
                                           id="remit_diposit"
                                           name="diposit"
                                           class="form-control"
                                           dir="ltr"
                                           inputmode="numeric"
                                           placeholder="۰">
                                </div>
                            </div>

                            <!-- مانده -->
                            <div class="col-md-3">
                                <label for="remit_remainder" class="form-label fw-medium text-secondary">مانده</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-wallet2 text-muted"></i></span>
                                    <input type="text"
                                           id="remit_remainder"
                                           name="remainder"
                                           class="form-control"
                                           dir="ltr"
                                           inputmode="numeric"
                                           placeholder="۰">
                                </div>
                            </div>

                            <!-- ذینفع -->
                            <div class="col-md-6">
                                <label for="remit_beneficiary" class="form-label fw-medium text-secondary">ذینفع</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-person text-muted"></i></span>
                                    <input type="text"
                                           id="remit_beneficiary"
                                           name="beneficiary"
                                           class="form-control"
                                           placeholder="نام شخص یا شرکت دریافت‌کننده">
                                </div>
                            </div>

                            <!-- شبا / کارت -->
                            <div class="col-md-6">
                                <label for="remit_sheba_cart" class="form-label fw-medium text-secondary">شبا / کارت</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-credit-card-2-front text-muted"></i></span>
                                    <input type="text"
                                           id="remit_sheba_cart"
                                           name="sheba/cart"
                                           class="form-control text-start font-monospace"
                                           dir="ltr"
                                           placeholder="IR... یا ۱۶ رقمی">
                                </div>
                            </div>

                            <!-- بابت -->
                            <div class="col-md-6">
                                <label for="remit_behalf" class="form-label fw-medium text-secondary">بابت</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-chat-left-dots text-muted"></i></span>
                                    <input type="text"
                                           id="remit_behalf"
                                           name="behalf"
                                           class="form-control"
                                           placeholder="مثال: پیش‌پرداخت، تسویه فاکتور">
                                </div>
                            </div>

                            <!-- سرفصل -->
                            <div class="col-md-6">
                                <label for="remit_transaction" class="form-label fw-medium text-secondary">سرفصل</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-bookmark text-muted"></i></span>
                                    <input type="text"
                                           id="remit_transaction"
                                           name="transaction"
                                           class="form-control"
                                           placeholder="مثال: خرید مصالح، تأسیسات">
                                </div>
                            </div>

                            <!-- زیرفصل -->
                            <div class="col-md-6">
                                <label for="remit_subchapter" class="form-label fw-medium text-secondary">زیرفصل</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-diagram-3 text-muted"></i></span>
                                    <input type="text"
                                           id="remit_subchapter"
                                           name="subchapter"
                                           class="form-control"
                                           placeholder="مثال: میلگرد، بتن">
                                </div>
                            </div>

                            <!-- بانک/حساب -->
                            <div class="col-md-6">
                                <label for="remit_bank_hesab" class="form-label fw-medium text-secondary">بانک / حساب</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-bank text-muted"></i></span>
                                    <input type="text"
                                           id="remit_bank_hesab"
                                           name="bank/hesab"
                                           class="form-control"
                                           placeholder="مثال: حساب جاری بانک ملت">
                                </div>
                            </div>

                            <!-- ورودی/خروجی -->
                            <div class="col-md-6">
                                <label for="remit_voroodi_khoroujy" class="form-label fw-medium text-secondary">ورودی / خروجی</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-arrow-left-right text-muted"></i></span>
                                    <input type="text"
                                           id="remit_voroodi_khoroujy"
                                           name="voroodi/khoroujy"
                                           class="form-control"
                                           placeholder="مثال: خروجی">
                                </div>
                            </div>

                            <!-- توضیحات -->
                            <div class="col-12">
                                <label for="remit_description" class="form-label fw-medium text-secondary">توضیحات</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-card-text text-muted"></i></span>
                                    <textarea id="remit_description"
                                              name="description"
                                              class="form-control"
                                              rows="2"
                                              placeholder="توضیحات تکمیلی حواله..."></textarea>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- پاورقی و دکمه‌ها -->
                    <div class="modal-footer bg-light border-top py-2 px-3 d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-danger px-4 d-flex align-items-center gap-2">
                            <i class="bi bi-check2-circle"></i>
                            <span>ثبت و ذخیره حواله</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function() {
            // تابع نرمال‌سازی ارقام فارسی و عربی به انگلیسی
            function normalizeDigits(str) {
                return (str || '').toString()
                    .replace(/[۰-۹]/g, function(d) { return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d); })
                    .replace(/[٠-٩]/g, function(d) { return '٠١٢٣٤٥٦٧٨٩'.indexOf(d); });
            }

            // فرمت‌بندی ۳ رقمی
            function formatNumber(val) {
                var clean = normalizeDigits(val).replace(/\D/g, '');
                return clean ? clean.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '';
            }

            document.addEventListener('DOMContentLoaded', function() {
                var modalEl = document.getElementById('addRemitanceOutModal');
                var form = document.getElementById('addRemitanceOutForm');
                var harvestInput = document.getElementById('remit_harvest');

                // ماسک‌گذاری هم‌زمان روی مبالغ (برداشت، واریز، مانده)
                var currencyFields = ['remit_harvest', 'remit_diposit', 'remit_remainder'];
                currencyFields.forEach(function(fieldId) {
                    var el = document.getElementById(fieldId);
                    if (!el) return;
                    el.addEventListener('input', function() {
                        var cursor = this.selectionStart;
                        this.value = formatNumber(this.value);
                    });
                });

                // تمیزکردن کاماها و نرمال‌سازی ارقام قبل از ارسال فرم
                if (form) {
                    form.addEventListener('submit', function() {
                        currencyFields.forEach(function(fieldId) {
                            var el = document.getElementById(fieldId);
                            if (el) {
                                el.value = normalizeDigits(el.value).replace(/,/g, '');
                            }
                        });
                    });
                }

                // رفع هشدار aria-hidden و ریست فرم پس از بسته شدن مودال
                if (modalEl) {
                    modalEl.addEventListener('hide.bs.modal', function() {
                        if (document.activeElement && modalEl.contains(document.activeElement)) {
                            document.activeElement.blur();
                        }
                    });

                    modalEl.addEventListener('hidden.bs.modal', function() {
                        if (form) form.reset();
                    });
                }
            });
        })();
    </script>
    <!-- مودال ویرایش حواله خروجی -->
    <div class="modal fade" id="editRemitanceOutModal" tabindex="-1" aria-labelledby="editRemitanceOutModalLabel" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-0 rounded-3">

                <!-- هدر مودال -->
                <div class="modal-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2 mb-0" id="editRemitanceOutModalLabel">
                        <i class="bi bi-pencil-square text-primary fs-5"></i>
                        <span>ویرایش اطلاعات حواله</span>
                    </h6>
                    <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>

                <!-- بدنه مودال (محتوا از طریق AJAX لود می‌شود) -->
                <div class="modal-body p-4" id="editRemitanceOutModalBody">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">در حال دریافت...</span>
                        </div>
                        <div class="mt-2 text-muted small">در حال بارگذاری اطلاعات حواله...</div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <div class="modal fade" id="deleteRemitanceOutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <!-- توجه: اکشن به delete_remitance_out.php تغییر یافت -->
                <form id="deleteRemitanceOutForm" action="delete_remitance_out.php" method="POST">
                    <input type="hidden" name="remitance_out_id" id="delete_remitance_out_id" value="">
                    <input type="hidden" name="project_id" id="delete_project_id" value="<?= htmlspecialchars((string)($selectedProjectId ?? $_GET['project_id'] ?? '')) ?>">
                    <input type="hidden" name="tab" value="remitance_out">

                    <div class="modal-header">
                        <h5 class="modal-title text-danger">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> تأیید حذف حواله
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center py-4">
                        <div class="text-danger mb-3">
                            <i class="bi bi-trash3-fill" style="font-size: 3rem;"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-2">آیا از حذف این رکورد حواله اطمینان دارید؟</h6>
                        <div class="alert alert-light border py-2 px-3 my-3 text-secondary d-inline-block">
                            <i class="bi bi-receipt me-1 text-primary"></i>
                            مشخصات: <strong id="delete_remitance_out_info_text" class="text-dark">---</strong>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" id="btnConfirmDelete" class="btn btn-danger">
                            <i class="bi bi-trash3 me-1"></i> حذف نهایی
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="viewRemitanceOutModal" tabindex="-1" aria-labelledby="viewRemitanceOutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fs-6 fw-bold" id="viewRemitanceOutModalLabel">
                        <i class="bi bi-receipt me-1 text-primary"></i> جزئیات حواله
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>

                <div class="modal-body p-4" id="viewRemitanceOutModalBody">
                    <!-- اسپینر پیش‌فرض در زمان لودینگ -->
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">در حال بارگذاری...</span>
                        </div>
                        <p class="text-muted mt-2 small">در حال دریافت اطلاعات حواله...</p>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">بستن</button>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($activeTab === 'check_out'): ?>
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= htmlspecialchars($_SESSION['flash_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <h1 class="h1 mb-3">
        لیست چک ها
    </h1>
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex gap-2">
            <button type="button"
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#advancedSearchModal">
                <i class="bi bi-funnel me-1"></i>
                جست‌وجوی پیشرفته
            </button>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="badge bg-danger-subtle text-danger border border-danger-subtle fs-6 py-2 px-3 fw-medium">
                <i class="bi bi-wallet2 me-1"></i>
                مجموع مبلغ چک ها:
                <strong><?= number_format((float)($totalWithdrawalAmount ?? 0)) ?></strong>
                <small class="fw-normal">ریال</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button"
                    class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 py-2 px-3 fw-medium text-decoration-none border-0 d-inline-flex align-items-center gap-2"
                    style="cursor: pointer; transition: all 0.2s ease-in-out;"
                    onmouseover="this.classList.add('shadow-sm', 'opacity-75')"
                    onmouseout="this.classList.remove('shadow-sm', 'opacity-75')"
                    data-bs-toggle="modal"
                    data-bs-target="#addCheckModal">
                <i class="bi bi-plus-circle-fill"></i>
                <span>درج چک جدید</span>
            </button>
        </div>

        <!-- فرم کوچک برای تغییر آنی تعداد ردیف در بالای جدول -->
        <form method="GET" action="" class="d-flex align-items-center gap-2 m-0">
            <?php foreach ($_GET as $key => $val): ?>
                <?php if (!in_array($key, ['per_page', 'page'], true) && is_string($val)): ?>
                    <input type="hidden" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <input type="hidden" name="page" value="1">

            <label for="tablePerPage" class="form-label mb-0 text-nowrap small text-muted">
                <i class="bi bi-list-ol me-1"></i>تعداد نمایش:
            </label>
            <select name="per_page" id="tablePerPage" class="form-select form-select-sm" style="width: 120px;" onchange="this.form.submit()">
                <option value="10" <?= ($perPage ?? 25) == 10 ? 'selected' : '' ?>>۱۰ ردیف</option>
                <option value="25" <?= ($perPage ?? 25) == 25 ? 'selected' : '' ?>>۲۵ ردیف</option>
                <option value="50" <?= ($perPage ?? 25) == 50 ? 'selected' : '' ?>>۵۰ ردیف</option>
                <option value="100" <?= ($perPage ?? 25) == 100 ? 'selected' : '' ?>>۱۰۰ ردیف</option>
            </select>
            <label class="text-muted small mb-0">
                از
                <strong><?= number_format((float)($totalRecords ?? 0)) ?></strong>
            </label>
        </form>
    </div>

    <!-- جدول نمایش چک‌ها -->
    <div class="table-responsive shadow-sm rounded border">
        <table class="table table-bordered table-hover text-center align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>ردیف</th>
                <th class="text-center align-middle">سرفصل</th>
                <th>زیرفصل</th>
                <th class="text-center align-middle">شماره چک</th>
                <?php
                $queryParams['order_by']  = 'check_due_date';
                $queryParams['order_dir'] = $toggleDir;
                $sortDueDateUrl = '?' . http_build_query($queryParams);
                ?>
                <th>
                    <a href="<?= htmlspecialchars($sortDueDateUrl, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none text-dark d-inline-flex align-items-center justify-content-center gap-1 w-100">
                        <span>موعد چک</span>
                        <?php if (($orderBy ?? '') === 'check_due_date'): ?>
                            <i class="bi <?= strtolower($orderDir ?? 'desc') === 'asc' ? 'bi-sort-numeric-down text-primary' : 'bi-sort-numeric-up-alt text-primary' ?> fs-6"></i>
                        <?php else: ?>
                            <i class="bi bi-arrow-down-up text-muted opacity-50 fs-6"></i>
                        <?php endif; ?>
                    </a>
                </th>
                <?php
                $orderDirCurrent = strtolower($orderDir ?? ($_GET['order_dir'] ?? 'desc'));
                $toggleDir = ($orderDirCurrent === 'asc') ? 'desc' : 'asc';

                $queryParams = $_GET ?? [];
                $queryParams['order_by']  = 'check_amount';
                $queryParams['order_dir'] = $toggleDir;
                $sortAmountUrl = '?' . http_build_query($queryParams);
                ?>
                <th>
                    <a href="<?= htmlspecialchars($sortAmountUrl, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none text-dark d-inline-flex align-items-center justify-content-center gap-1 w-100">
                        <span>مبلغ چک</span>
                        <?php if (($orderBy ?? '') === 'check_amount'): ?>
                            <i class="bi <?= strtolower($orderDir ?? 'desc') === 'asc' ? 'bi-sort-numeric-down text-primary' : 'bi-sort-numeric-up-alt text-primary' ?> fs-6"></i>
                        <?php else: ?>
                            <i class="bi bi-arrow-down-up text-muted opacity-50 fs-6"></i>
                        <?php endif; ?>
                    </a>
                </th>
                <th>بابت</th>


                <th>دریافت کننده</th>
                <th>پاس شده/نشده</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!empty($checks)): ?>
                <?php foreach ($checks as $index => $row): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($row['transaction'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['subchapter'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row["check_number"] ?? '') ?></td>
                        <td><?= htmlspecialchars($row["check_due_date"] ?? '') ?></td>
                        <td><?= htmlspecialchars(number_format((float)($row["check_amount"] ?? 0))) ?></td>
                        <td><?= htmlspecialchars($row['behalf'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['recipient'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row["passed/failed"] ?? '') ?></td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border-0 rounded-circle p-1"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        data-bs-boundary="window"
                                        aria-expanded="false"
                                        style="width: 32px; height: 32px;">
                                    <i class="bi bi-three-dots-vertical text-secondary"></i>
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 small">
                                    <button type="button"
                                            class="dropdown-item py-2 btn-open-check"
                                            data-check-id="<?= (int)($row['id'] ?? 0) ?>"
                                            data-project-id="<?= (int)($row['project_id'] ?? $project_id ?? 0) ?>">
                                        <i class="bi bi-receipt text-primary me-2"></i> مشاهده چک (کد: <?= $row['id'] ?? 'خالی' ?>)
                                    </button>

                                    <li><hr class="dropdown-divider my-1"></li>

                                    <!-- ویرایش -->
                                    <li>
                                        <a class="dropdown-item py-2 btn-edit-check"
                                           href="javascript:void(0)"
                                           data-check-id="<?= (int)$row['id'] ?>"
                                           data-project-id="<?= (int)($row['project_id'] ?? ($project_id ?? 0)) ?>">
                                            <i class="bi bi-pencil-square text-warning me-2"></i> ویرایش
                                        </a>

                                    </li>

                                    <li><hr class="dropdown-divider my-1"></li>

                                    <!-- باز کردن مودال حذف -->
                                    <li>
                                        <button type="button"
                                                class="dropdown-item py-2 text-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteCheckModal"
                                                data-check-id="<?= (int)($row['id'] ?? 0) ?>"
                                                data-project-id="<?= (int)($row['project_id'] ?? 0) ?>"
                                                data-check-info="شماره <?= htmlspecialchars((string)($row['check_number'] ?? '')) ?> (<?= htmlspecialchars((string)(!empty($row['recipient']) ? $row['recipient'] : '---')) ?>)">
                                            <i class="bi bi-trash3 text-danger me-2"></i> حذف چک
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="14" class="text-center text-muted py-3">داده‌ای برای این پروژه یافت نشد.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- نوار صفحه‌بندی -->
        <?php if (!empty($totalPages) && $totalPages > 1): ?>
            <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-2 border-top bg-light">
                <div class="text-muted small">
                    نمایش <?= count($checks ?? []) ?> از <?= number_format((float)($totalRecords ?? 0)) ?> ردیف (صفحه <?= $page ?? 1 ?> از <?= $totalPages ?>)
                </div>

                <nav aria-label="صفحه‌بندی">
                    <ul class="pagination pagination-sm mb-0">
                        <?php $pageParams = $_GET; ?>
                        <li class="page-item <?= (($page ?? 1) <= 1) ? 'disabled' : '' ?>">
                            <?php $pageParams['page'] = max(1, ($page ?? 1) - 1); ?>
                            <a class="page-link" href="?<?= htmlspecialchars(http_build_query($pageParams), ENT_QUOTES, 'UTF-8') ?>">قبلی</a>
                        </li>
                        <?php for ($p = max(1, ($page ?? 1) - 2); $p <= min($totalPages, ($page ?? 1) + 2); $p++): ?>
                            <?php $pageParams['page'] = $p; ?>
                            <li class="page-item <?= ($p === ($page ?? 1)) ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= htmlspecialchars(http_build_query($pageParams), ENT_QUOTES, 'UTF-8') ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= (($page ?? 1) >= $totalPages) ? 'disabled' : '' ?>">
                            <?php $pageParams['page'] = min($totalPages, ($page ?? 1) + 1); ?>
                            <a class="page-link" href="?<?= htmlspecialchars(http_build_query($pageParams), ENT_QUOTES, 'UTF-8') ?>">بعدی</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>

    <!-- ========================================== -->
    <!-- مودال ۱: جست‌وجوی پیشرفته (فرم مستقل GET) -->
    <!-- ========================================== -->
    <div class="modal fade"
         id="advancedSearchModal"
         tabindex="-1"
         aria-labelledby="advancedSearchModalLabel"
         aria-hidden="true"
         dir="rtl">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header d-flex justify-content-between align-items-center">
                    <h5 class="modal-title m-0" id="advancedSearchModalLabel">
                        <i class="bi bi-funnel-fill text-primary ms-2"></i>
                        جست‌وجوی پیشرفته چک‌ها
                    </h5>
                    <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>

                <form method="GET" action="">
                    <div class="modal-body">
                        <input type="hidden" name="tab" value="check_out">
                        <input type="hidden" name="page" value="1">
                        <input type="hidden" name="order_by" value="<?= htmlspecialchars($orderBy ?? 'history', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="order_dir" value="<?= htmlspecialchars(strtolower($orderDir ?? 'DESC'), ENT_QUOTES, 'UTF-8') ?>">

                        <?php if (!empty($selectedProjectId)): ?>
                            <input type="hidden" name="project_id" value="<?= htmlspecialchars((string)$selectedProjectId, ENT_QUOTES, 'UTF-8') ?>">
                        <?php else : ?>
                            <div class="alert alert-danger p-2 mb-3">پروژه مورد نظر را انتخاب کنید</div>
                        <?php endif; ?>

                        <div class="row g-3">
                            <!-- دریافت کننده -->
                            <div class="col-md-6">
                                <label class="form-label">دریافت کننده</label>
                                <select name="recipient" id="filterrecipient" class="form-select select2-searchable">
                                    <option value="">-- همه دریافت کننده‌ها --</option>
                                    <?php if (!empty($recipientList)): ?>
                                        <?php foreach ($recipientList as $account): ?>
                                            <option value="<?= htmlspecialchars((string)$account, ENT_QUOTES, 'UTF-8') ?>"
                                                    <?= (isset($_GET['recipient']) && $_GET['recipient'] === (string)$account) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars((string)$account, ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- شماره چک -->
                            <div class="col-md-6">
                                <label for="modalcheck_number" class="form-label">شماره چک</label>
                                <select id="modalcheck_number" name="check_number" class="form-select">
                                    <option value="">-- همه شماره چک‌ها --</option>
                                    <?php if (!empty($check_numberList)): ?>
                                        <?php foreach ($check_numberList as $account): ?>
                                            <option value="<?= htmlspecialchars((string)$account, ENT_QUOTES, 'UTF-8') ?>"
                                                    <?= (isset($_GET['check_number']) && $_GET['check_number'] === (string)$account) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars((string)$account, ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- مبالغ -->
                            <div class="col-md-6">
                                <label for="modalMinAmount" class="form-label">حداقل مبلغ</label>
                                <input type="text" id="modalMinAmount" name="min_amount" class="form-control" inputmode="decimal"
                                       value="<?= htmlspecialchars($_GET['min_amount'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="حداقل مبلغ">
                            </div>
                            <div class="col-md-6">
                                <label for="modalMaxAmount" class="form-label">حداکثر مبلغ</label>
                                <input type="text" id="modalMaxAmount" name="max_amount" class="form-control" inputmode="decimal"
                                       value="<?= htmlspecialchars($_GET['max_amount'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="حداکثر مبلغ">
                            </div>

                            <!-- سرفصل -->
                            <div class="col-md-6 position-relative">
                                <label class="form-label">سرفصل</label>
                                <input type="hidden" name="transaction" id="selectedTransactionInput"
                                       value="<?= htmlspecialchars($currentTransaction ?? '', ENT_QUOTES, 'UTF-8') ?>">

                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary dropdown-toggle w-100 text-end d-flex justify-content-between align-items-center bg-white"
                                            type="button" id="transactionDropdownBtn"
                                            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                        <span id="transactionBtnText" class="text-truncate">
                                            <?= !empty($currentTransaction) ? htmlspecialchars($currentTransaction, ENT_QUOTES, 'UTF-8') : '-- همه سرفصل‌ها --' ?>
                                        </span>
                                    </button>

                                    <div class="dropdown-menu w-100 p-2 shadow" aria-labelledby="transactionDropdownBtn" style="max-height: 280px;">
                                        <input type="text" class="form-control form-control-sm mb-2" id="transactionSearchInput"
                                               placeholder="تایپ کنید تا فیلتر شود..." autocomplete="off">

                                        <div class="overflow-auto" style="max-height: 200px;" id="transactionItemsList">
                                            <a class="dropdown-item py-2 rounded text-muted" href="javascript:void(0)" data-val="">-- همه سرفصل‌ها --</a>
                                            <?php if (!empty($transactionSubMap)): ?>
                                                <?php foreach (array_keys($transactionSubMap) as $tr): ?>
                                                    <a class="dropdown-item py-2 rounded <?= (($currentTransaction ?? '') === $tr) ? 'active bg-primary text-white' : '' ?>"
                                                       href="javascript:void(0)"
                                                       data-val="<?= htmlspecialchars($tr, ENT_QUOTES, 'UTF-8') ?>">
                                                        <?= htmlspecialchars($tr, ENT_QUOTES, 'UTF-8') ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- زیرفصل -->
                            <div class="col-md-6">
                                <label class="form-label" for="filterSubchapter">زیرفصل</label>
                                <select name="subchapter" id="filterSubchapter" class="form-select" <?= empty($currentTransaction) ? 'disabled' : '' ?>>
                                    <option value="">-- همه زیرفصل‌ها --</option>
                                    <?php if (!empty($currentTransaction) && !empty($transactionSubMap[$currentTransaction])): ?>
                                        <?php foreach ($transactionSubMap[$currentTransaction] as $sub): ?>
                                            <option value="<?= htmlspecialchars($sub, ENT_QUOTES, 'UTF-8') ?>"
                                                    <?= (($currentSubchapter ?? '') === $sub) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($sub, ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- تاریخ‌ها -->
                            <div class="col-md-6">
                                <label for="modalDateFrom" class="form-label">از تاریخ</label>
                                <input type="text" id="modalDateFrom" name="date_from" class="form-control persian-date"
                                       autocomplete="off" value="<?= htmlspecialchars($_GET['date_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="مثال: ۱۴۰۳/۰۱/۰۱">
                            </div>
                            <div class="col-md-6">
                                <label for="modalDateTo" class="form-label">تا تاریخ</label>
                                <input type="text" id="modalDateTo" name="date_to" class="form-control persian-date"
                                       autocomplete="off" value="<?= htmlspecialchars($_GET['date_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="مثال: ۱۴۰۳/۱۲/۲۹">
                            </div>

                            <!-- وضعیت -->
                            <div class="col-md-6">
                                <label class="form-label" for="modalPassedFailed">وضعیت</label>
                                <select id="modalPassedFailed" name="passed/failed" class="form-select">
                                    <option value="">-- همه وضعیت --</option>
                                    <?php if (!empty($passedFailedList)): ?>
                                        <?php foreach ($passedFailedList as $st): ?>
                                            <?php $st = trim((string)$st); if ($st === '') continue; ?>
                                            <option value="<?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?>"
                                                    <?= (isset($_GET['passed/failed']) && (string)$_GET['passed/failed'] === $st) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <a href="?<?= htmlspecialchars(http_build_query([
                                'tab' => 'check_out',
                                'order_by' => $orderBy ?? 'history',
                                'order_dir' => strtolower($orderDir ?? 'DESC'),
                                'project_id' => $selectedProjectId ?? ''
                        ]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
                            پاک‌کردن فیلترها
                        </a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> اعمال فیلتر</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- مودال ۲: درج چک جدید (فرم مستقل POST)      -->
    <!-- ========================================== -->
    <div class="modal fade" id="addCheckModal" tabindex="-1" aria-labelledby="addCheckModalLabel" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg border-0 rounded-3">

                <!-- هدر مودال -->
                <div class="modal-header bg-light border-bottom py-3">
                    <h5 class="modal-title fs-5 fw-bold text-dark d-flex align-items-center gap-2" id="addCheckModalLabel">
                        <i class="bi bi-card-checklist text-danger fs-4"></i>
                        <span>ثبت و درج چک جدید</span>
                    </h5>
                    <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>

                <form action="add_check.php" method="POST" id="addCheckForm">
                    <!-- فیلدهای سیستمی ضروری -->
                    <input type="hidden" name="action" value="add_check_output">
                    <input type="hidden" name="project_id" value="<?= htmlspecialchars((string)($selectedProjectId ?? $_GET['project_id'] ?? '')) ?>">

                    <div class="modal-body p-4" id="addCheckModalBody">
                        <div class="row g-3">

                            <!-- زیر فصل -->
                            <div class="col-md-6">
                                <label class="form-label fw-medium text-secondary">
                                    زیر فصل <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-folder2-open text-muted"></i></span>
                                    <input type="text"
                                           name="subchapter"
                                           class="form-control"
                                           placeholder="مثال: خرید مصالح، دستمزد، تأسیسات"
                                           required>
                                </div>
                            </div>

                            <!-- سرفصل -->
                            <div class="col-md-6">
                                <label class="form-label fw-medium text-secondary">سرفصل <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-tag text-muted"></i></span>
                                    <input type="text"
                                           name="transaction"
                                           id="transaction"
                                           class="form-control"
                                           placeholder="مثال: چک پرداختنی، هزینه‌های جاری"
                                           required>
                                </div>
                            </div>

                            <!-- بابت -->
                            <div class="col-12">
                                <label class="form-label fw-medium text-secondary">بابت</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-card-text text-muted"></i></span>
                                    <input type="text"
                                           name="behalf"
                                           class="form-control"
                                           placeholder="مثال: خرید میلگرد، پیش‌پرداخت قرارداد، تسویه فاکتور...">
                                </div>
                            </div>

                            <!-- دریافت‌کننده -->
                            <div class="col-md-6">
                                <label class="form-label fw-medium text-secondary">دریافت‌کننده (در وجه) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-person text-muted"></i></span>
                                    <input type="text"
                                           name="recipient"
                                           class="form-control"
                                           placeholder="نام شخص یا شرکت"
                                           required>
                                </div>
                            </div>

                            <!-- کد ملی دریافت‌کننده -->
                            <div class="col-md-6">
                                <label class="form-label fw-medium text-secondary">کد ملی دریافت‌کننده</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-person-vcard text-muted"></i></span>
                                    <input type="text"
                                           name="recipient's_national_iD"
                                           class="form-control text-start font-monospace"
                                           placeholder="۱۰ یا ۱۱ رقمی"
                                           maxlength="11">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium text-secondary">مبلغ چک (ریال) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">ریال</span>
                                    <input type="text"
                                           name="check_amount"
                                           id="check_amount"
                                           class="form-control text-start fw-bold"
                                           placeholder="۰"
                                           required>
                                </div>
                            </div>

                            <script>
                                (function() {
                                    function applyMask() {
                                        var el = document.getElementById('check_amount');
                                        if (!el) return;

                                        el.addEventListener('input', function() {
                                            // ۱. تبدیل ارقام فارسی و عربی به انگلیسی
                                            var val = this.value.toString()
                                                .replace(/[۰-۹]/g, function(d) { return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d); })
                                                .replace(/[٠-٩]/g, function(d) { return '٠١٢٣٤٥٦٧٨٩'.indexOf(d); });

                                            // ۲. حذف هر کاراکتر غیرعددی
                                            val = val.replace(/\D/g, '');

                                            // ۳. ۳ رقم ۳ رقم جدا کردن
                                            this.value = val ? val.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '';
                                        });
                                    }

                                    if (document.readyState === 'loading') {
                                        document.addEventListener('DOMContentLoaded', applyMask);
                                    } else {
                                        applyMask();
                                    }
                                })();
                            </script>




                            <!-- شماره چک -->
                            <div class="col-md-6">
                                <label class="form-label fw-medium text-secondary">شماره چک <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-card-text text-muted"></i></span>
                                    <input type="text"
                                           name="check_number"
                                           class="form-control text-start font-monospace"
                                           placeholder="مثال: ۱۲۳۴۵۶ یا شناسه ۱۶ رقمی"
                                           required>
                                </div>
                            </div>

                            <!-- تاریخ صدور چک -->
                            <div class="col-md-6">
                                <label class="form-label fw-medium text-secondary">تاریخ صدور چک</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-calendar3 text-muted"></i></span>
                                    <input type="text"
                                           data-jdp
                                           name="check_issuance_date"
                                           class="form-control text-start pwt-datepicker"
                                           placeholder="۱۴۰۳/۰۸/۰۱">
                                </div>
                            </div>

                            <!-- موعد چک -->
                            <div class="col-md-6">
                                <label class="form-label fw-medium text-secondary">موعد چک <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-calendar-check text-danger"></i></span>
                                    <input type="text"
                                           name="check_due_date"
                                           class="form-control text-start pwt-datepicker"
                                           data-jdp
                                           placeholder="۱۴۰۳/۰۸/۱۵"
                                           required>
                                </div>
                            </div>

                            <!-- بانک -->
                            <div class="col-md-4">
                                <label class="form-label fw-medium text-secondary">بانک</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-bank text-muted"></i></span>
                                    <input type="text"
                                           name="bank"
                                           class="form-control"
                                           placeholder="مثال: ملی، صادرات، تجارت">
                                </div>
                            </div>

                            <!-- پروژه -->
                            <div class="col-md-4">
                                <label class="form-label fw-medium text-secondary">پروژه</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-building text-muted"></i></span>
                                    <input type="text"
                                           name="project"
                                           class="form-control"
                                           placeholder="مثال: نگین / مروارید">
                                </div>
                            </div>

                            <!-- وضعیت چک -->
                            <div class="col-md-4">
                                <label class="form-label fw-medium text-secondary">وضعیت چک</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-check2-circle text-muted"></i></span>
                                    <select name="passed/failed" class="form-select">
                                        <option value="پاس نشده">پاس نشده</option>
                                        <option value="پاس شده">پاس شده</option>
                                    </select>
                                </div>
                            </div>

                            <!-- از کجا پاس شد؟ -->
                            <div class="col-md-6">
                                <label class="form-label fw-medium text-secondary">از کجا پاس شد؟</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-bank2 text-muted"></i></span>
                                    <input type="text"
                                           name="Where_did_it_get_approved?"
                                           class="form-control"
                                           placeholder="مثال: حساب جاری بانک ملی شعبه مرکزی">
                                </div>
                            </div>

                            <!-- هزینه -->
                            <div class="col-md-6">
                                <label class="form-label fw-medium text-secondary">هزینه </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-cash-stack text-muted"></i></span>
                                    <input type="text"
                                           name="cost"
                                           class="form-control"
                                           placeholder=" هزینه">
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- پاورقی و دکمه‌ها -->
                    <div class="modal-footer bg-light border-top py-2 px-3 d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-danger px-4 d-flex align-items-center gap-2">
                            <i class="bi bi-check2-circle"></i>
                            <span>ثبت و ذخیره چک</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="editCheckModal" tabindex="-1" aria-labelledby="editCheckModalLabel" aria-hidden="true">
        <!-- گیومه اضافه بعد از modal-dialog-scrollable حذف شد -->
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-light py-2">
                    <h6 class="modal-title fw-bold" id="editCheckModalLabel">
                        <i class="bi bi-pencil-square text-primary me-2"></i> ویرایش اطلاعات چک
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body p-4" id="editCheckModalBody">
                    <!-- محتوای فرم از طریق AJAX بارگذاری می‌شود -->
                </div>
            </div>
        </div>
    </div>


    <!-- مودال تأیید حذف چک -->
    <!-- مودال تأیید حذف چک -->
    <div class="modal fade" id="deleteCheckModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <!-- توجه: اکشن به delete_check.php تغییر یافت -->
                <form id="deleteCheckForm" action="delete_check_output.php" method="POST">
                    <input type="hidden" name="check_id" id="delete_check_id" value="">
                    <input type="hidden" name="project_id" id="delete_project_id" value="<?= htmlspecialchars((string)($selectedProjectId ?? $_GET['project_id'] ?? '')) ?>">
                    <input type="hidden" name="tab" value="check_out">

                    <div class="modal-header">
                        <h5 class="modal-title text-danger">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> تأیید حذف چک
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        آیا از حذف چک <b id="delete_check_info_text" class="text-dark"></b> اطمینان دارید؟
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" id="btnConfirmDelete" class="btn btn-danger">
                            <i class="bi bi-trash3 me-1"></i> حذف نهایی
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewCheckModal" tabindex="-1" aria-labelledby="viewCheckModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fs-6 fw-bold" id="viewCheckModalLabel">
                        <i class="bi bi-receipt me-1 text-primary"></i> جزئیات چک
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>

                <div class="modal-body p-4" id="viewCheckModalBody">
                    <!-- اسپینر پیش‌فرض در زمان لودینگ -->
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">در حال بارگذاری...</span>
                        </div>
                        <p class="text-muted mt-2 small">در حال دریافت اطلاعات چک...</p>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">بستن</button>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($activeTab === 'remittance_input'): ?>
<h1>لیست حواله</h1>
    <div class="table-responsive shadow-sm rounded border">
        <table class="table table-bordered table-hover text-center align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>تاریخ</th>


                <!-- ستون سورت‌شونده زیرفصل -->
                <th class="text-center align-middle">

                    <span>سند</span>


                </th>

                <th>

                    <span> برداشت مبلغ(ریال)</span>


                </th>

                <!-- ستون سورت‌شونده مبلغ برداشت -->

                <th class="text-center align-middle">
                    ذینفع
                </th>

                <th>سرفصل</th>
                <th>زیرفصل</th>
                <th>بانک/حساب</th>
                <th>
                    ورودی/خروجی
                </th>

            </tr>
            </thead>
            <tbody>


            </tbody>
        </table>

        <!-- نوار صفحه‌بندی -->

    </div>
<?php endif; ?>
<script id="php-vars">
    window.PHP_VARS = {
        csrfToken: "<?php echo addslashes($csrfToken); ?>",
        activeTab: "<?php echo addslashes($activeTab); ?>",
        selectedProjectId: <?php echo (int)$selectedProjectId; ?>,
        memberStatusFilter: "<?php echo addslashes($memberStatusFilter); ?>",
        searchQuery: "<?php echo addslashes($searchQuery); ?>",
        sessionMessage: <?php
        if(isset($_SESSION['message']) && !empty($_SESSION['message'])) {
            echo json_encode($_SESSION['message'], JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG);
            unset($_SESSION['message']);
        } else {
            echo '""';
        }
        ?>,
        sessionMessageType: <?php
        if(isset($_SESSION['message_type']) && !empty($_SESSION['message_type'])) {
            echo json_encode($_SESSION['message_type'], JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG);
            unset($_SESSION['message_type']);
        } else {
            echo '"success"';
        }
        ?>
    };
</script>
<input type="hidden" id="csrfTokenInput" value="<?php echo htmlspecialchars($csrfToken); ?>">
 <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
 <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
 <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
 <script src="../js/memberSearch.js"></script>
 <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- ۱. ابتدا جی‌کوئری (فقط یک بار در کل پروژه!) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- ۲. سپس اسکریپت بوت‌استرپ -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- ۳. سپس فایل JS مربوط به Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Bootstrap -->


    <script>
        // ==========================================
        // ۱. تعریف توابع عمومی و سراسری (Global Scope)
        // ==========================================

        // تابع فرمت سه رقم سه رقم مبالغ با پشتیبانی از ارقام فارسی و انگلیسی
        window.formatCheckAmount = function(input) {
            if (!input) return;
            let val = input.value.replace(/[۰-۹]/g, d => "۰۱۲۳۴۵۶۷۸۹".indexOf(d)).replace(/\D/g, '');
            input.value = val ? Number(val).toLocaleString('en-US') : '';
        };
        window.formatCurrency = window.formatCheckAmount;

        // توابع باز و بسته کردن سایدبار و اکاردئون‌ها
        window.toggleCollapse = function(elementId, toggler) {
            const element = typeof elementId === 'string' ? document.getElementById(elementId) : elementId;
            if (!element) return;

            if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(element);
                const icon = toggler ? toggler.querySelector('.transition-icon, .toggle-icon, .bi-plus-lg') : null;

                element.addEventListener('shown.bs.collapse', () => {
                    if (icon) icon.classList.add('rotate-45', 'rotated');
                }, { once: true });

                element.addEventListener('hidden.bs.collapse', () => {
                    if (icon) icon.classList.remove('rotate-45', 'rotated');
                }, { once: true });

                bsCollapse.toggle();
            } else {
                element.classList.toggle('open');
                element.classList.toggle('show');
                const icon = toggler ? toggler.querySelector('.transition-icon, .toggle-icon') : null;
                if (icon) icon.classList.toggle('rotated');
            }
        };
        window.toggleCollapse1 = window.toggleCollapse;
        window.toggleCollapse2 = window.toggleCollapse;

        window.toggleSubmenu = function(submenuId, triggerElement, event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            const submenu = document.getElementById(submenuId);
            if (!submenu) return;

            const bsCollapse = bootstrap.Collapse.getOrCreateInstance(submenu, { toggle: false });
            const icon = triggerElement ? triggerElement.querySelector('.transition-icon, .toggle-icon') : null;

            if (submenu.classList.contains('show')) {
                bsCollapse.hide();
                if (triggerElement) triggerElement.setAttribute('aria-expanded', 'false');
                if (icon) icon.classList.remove('rotate-45', 'rotated');
            } else {
                bsCollapse.show();
                if (triggerElement) triggerElement.setAttribute('aria-expanded', 'true');
                if (icon) icon.classList.add('rotate-45', 'rotated');
            }
        };
        document.addEventListener('DOMContentLoaded', function () {
            const editModalEl = document.getElementById('editRemitanceOutModal');
            if (!editModalEl) return;

            const editModal = new bootstrap.Modal(editModalEl);
            const modalBody = document.getElementById('editRemitanceOutModalBody');
            let triggerButton = null;

            // تابع تبدیل ارقام فارسی و عربی به انگلیسی
            function normalizeDigits(str) {
                if (!str) return '';
                const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
                const arabicDigits  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
                return str.toString()
                    .replace(/[۰-۹]/g, w => persianDigits.indexOf(w))
                    .replace(/[٠-٩]/g, w => arabicDigits.indexOf(w));
            }

            // ۱. رفع خطای aria-hidden هنگام بستن مودال
            editModalEl.addEventListener('hide.bs.modal', function () {
                if (document.activeElement && editModalEl.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
            });

            editModalEl.addEventListener('hidden.bs.modal', function () {
                if (triggerButton && typeof triggerButton.focus === 'function') {
                    triggerButton.focus();
                } else {
                    document.body.focus();
                }
            });

            // ۲. باز کردن مودال و دریافت فرم ویرایش حواله (GET)
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.btn-edit-remitance-out');
                if (!btn) return;

                e.preventDefault();
                triggerButton = btn;

                const remitanceOutId = btn.getAttribute('data-remitance-out-id') || btn.getAttribute('data-id');
                const projectId = btn.getAttribute('data-project-id') || 0;

                if (!remitanceOutId) return;

                modalBody.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">در حال دریافت...</span>
                    </div>
                    <div class="mt-2 text-muted small">در حال بارگذاری اطلاعات حواله...</div>
                </div>
            `;

                editModal.show();

                fetch(`edit_remitance_out.php?id=${encodeURIComponent(remitanceOutId)}&project_id=${encodeURIComponent(projectId)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(response => {
                        if (!response.ok) throw new Error('خطای سرور: ' + response.status);
                        return response.text();
                    })
                    .then(html => {
                        modalBody.innerHTML = html;
                    })
                    .catch(err => {
                        modalBody.innerHTML = `
                        <div class="alert alert-danger mb-0" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            خطا در دریافت اطلاعات حواله. لطفاً مجدداً تلاش کنید.
                        </div>
                    `;
                        console.error('Error fetching remittance edit form:', err);
                    });
            });

            // ۳. فرمت ۳ رقمی زنده برای فیلدهای مبالغ (harvest, remainder, diposit)
            document.addEventListener('input', function (e) {
                const target = e.target;
                if (target && (target.id === 'harvest' || target.id === 'remainder' || target.id === 'diposit' || target.classList.contains('money-mask'))) {
                    let rawVal = normalizeDigits(target.value).replace(/[^0-9]/g, '');
                    if (rawVal) {
                        target.value = Number(rawVal).toLocaleString('en-US');
                    } else {
                        target.value = '';
                    }
                }
            });

            // ۴. ثبت فرم با AJAX (POST)
            document.addEventListener('submit', function (e) {
                const form = e.target.closest('#ajaxEditRemitanceOutForm');
                if (!form) return;

                e.preventDefault();

                const alertBox = form.querySelector('#editRemitanceAlert') || document.getElementById('editRemitanceAlert');
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';

                if (alertBox) {
                    alertBox.classList.add('d-none');
                    alertBox.classList.remove('alert-danger');
                    alertBox.innerHTML = '';
                }

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> در حال ذخیره...`;
                }

                const formData = new FormData(form);

                fetch('edit_remitance_out.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(res => {
                        if (!res.ok) throw new Error('خطای سرور (' + res.status + ')');
                        return res.json();
                    })
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            throw new Error(data.message || 'خطا در ثبت اطلاعات.');
                        }
                    })
                    .catch(err => {
                        if (alertBox) {
                            alertBox.classList.remove('d-none');
                            alertBox.classList.add('alert-danger');
                            alertBox.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i> ${err.message || 'خطای غیرمنتظره رخ داد.'}`;
                        }

                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalBtnHtml;
                        }
                    });
            });
        });
        document.addEventListener('DOMContentLoaded', function () {
            const editModalEl = document.getElementById('editCheckModal');
            if (!editModalEl) return;

            const editModal = new bootstrap.Modal(editModalEl);
            const modalBody = document.getElementById('editCheckModalBody');
            let triggerButton = null; // نگهداری دکمه‌ای که مودال را باز کرده است

            // تابع تبدیل ارقام فارسی و عربی به انگلیسی
            function normalizeDigits(str) {
                if (!str) return '';
                const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
                const arabicDigits  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
                return str.toString()
                    .replace(/[۰-۹]/g, w => persianDigits.indexOf(w))
                    .replace(/[٠-٩]/g, w => arabicDigits.indexOf(w));
            }

            // ۱. رفع خطای Blocked aria-hidden با مدیریت فوکوس هنگام بستن مودال
            editModalEl.addEventListener('hide.bs.modal', function () {
                if (document.activeElement && editModalEl.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
            });

            editModalEl.addEventListener('hidden.bs.modal', function () {
                if (triggerButton && typeof triggerButton.focus === 'function') {
                    triggerButton.focus();
                } else {
                    document.body.focus();
                }
            });

            // ۲. باز کردن مودال و واکشی فرم (GET) با Event Delegation
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.btn-edit-check');
                if (!btn) return;

                e.preventDefault();
                triggerButton = btn;

                const checkId = btn.getAttribute('data-check-id');
                const projectId = btn.getAttribute('data-project-id') || 0;

                if (!checkId) return;

                modalBody.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">در حال دریافت...</span>
                </div>
                <div class="mt-2 text-muted small">در حال بارگذاری اطلاعات چک...</div>
            </div>
        `;

                editModal.show();

                fetch(`edit_check_out.php?id=${encodeURIComponent(checkId)}&project_id=${encodeURIComponent(projectId)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(response => {
                        if (!response.ok) throw new Error('خطای سرور: ' + response.status);
                        return response.text();
                    })
                    .then(html => {
                        modalBody.innerHTML = html;
                    })
                    .catch(err => {
                        modalBody.innerHTML = `
                <div class="alert alert-danger mb-0" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    خطا در دریافت اطلاعات چک. لطفاً مجدداً تلاش کنید.
                </div>
            `;
                        console.error('Error fetching check edit form:', err);
                    });
            });

            // ۳. فرمت ۳ رقمی زنده برای فیلد مبلغ
            document.addEventListener('input', function (e) {
                if (e.target && e.target.id === 'check_amount') {
                    let rawVal = normalizeDigits(e.target.value).replace(/[^0-9]/g, '');
                    if (rawVal) {
                        e.target.value = Number(rawVal).toLocaleString('en-US');
                    } else {
                        e.target.value = '';
                    }
                }
            });

            // ۴. ثبت فرم با AJAX (POST) - بدون وابستگی به متغیرهای خارجی
            document.addEventListener('submit', function (e) {
                const form = e.target.closest('#ajaxEditCheckForm');
                if (!form) return;

                e.preventDefault();

                const alertBox = form.querySelector('#editCheckAlert') || document.getElementById('editCheckAlert');
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';

                if (alertBox) {
                    alertBox.classList.add('d-none');
                    alertBox.classList.remove('alert-danger');
                    alertBox.innerHTML = '';
                }

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> در حال ذخیره...`;
                }

                // تمام مقادیر (از جمله id و project_id) به صورت خودکار از طریق فیلدهای هیدن درون فرم در formData قرار می‌گیرند
                const formData = new FormData(form);

                fetch('edit_check_out.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            throw new Error(data.message || 'خطا در ثبت اطلاعات.');
                        }
                    })
                    .catch(err => {
                        if (alertBox) {
                            alertBox.classList.remove('d-none');
                            alertBox.classList.add('alert-danger');
                            alertBox.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i> ${err.message || 'خطای غیرمنتظره رخ داد.'}`;
                        }

                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalBtnHtml;
                        }
                    });
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            const viewRemitanceOutModalEl = document.getElementById('viewRemitanceOutModal');
            const viewRemitanceOutModalBody = document.getElementById('viewRemitanceOutModalBody');
            if (!viewRemitanceOutModalEl || !viewRemitanceOutModalBody) return;

            const remitanceOutModal = bootstrap.Modal.getOrCreateInstance(viewRemitanceOutModalEl);
            let triggerButton = null;

            const defaultLoaderHtml = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">در حال بارگذاری...</span>
                </div>
                <p class="text-muted mt-2 small">در حال دریافت جزئیات حواله...</p>
            </div>
        `;

            // مدیریت فوکوس برای عدم تداخل aria-hidden
            viewRemitanceOutModalEl.addEventListener('hide.bs.modal', function () {
                if (document.activeElement && viewRemitanceOutModalEl.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
            });

            viewRemitanceOutModalEl.addEventListener('hidden.bs.modal', function () {
                viewRemitanceOutModalBody.innerHTML = defaultLoaderHtml;
                if (triggerButton && typeof triggerButton.focus === 'function') {
                    try {
                        triggerButton.focus();
                    } catch (e) {}
                }
            });

            // شنونده رویداد با Event Delegation روی کل سند
            document.addEventListener('click', function (e) {
                // پشتیبانی از هر دو کلاس اختصاصی حواله و کلاس مشترک
                const btn = e.target.closest('.btn-open-remittance, .btn-view-remittance, .btn-open-check');
                if (!btn) return;

                // اگر دکمه مربوط به مودال حواله نیست، نادیده بگیر
                const targetModal = btn.getAttribute('data-bs-target') || btn.getAttribute('data-target');
                if (targetModal && targetModal !== '#viewRemitanceOutModal') {
                    return;
                }

                e.preventDefault();
                triggerButton = btn;

                // اولویت استخراج شناسه حواله
                const rawId = btn.dataset.remitanceOutId
                    || btn.dataset.id
                    || btn.getAttribute('data-remitance-out-id')
                    || btn.dataset.checkId
                    || '';

                const rawProjectId = btn.dataset.projectId
                    || btn.getAttribute('data-project-id')
                    || '0';

                const remitanceOutId = parseInt(rawId, 10);
                const projectId = parseInt(rawProjectId, 10) || 0;

                if (isNaN(remitanceOutId) || remitanceOutId <= 0) {
                    viewRemitanceOutModalBody.innerHTML = `
                    <div class="alert alert-warning d-flex align-items-center mb-0" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div>شناسه حواله نامعتبر است (مقدار دریافتی: ${rawId ? String(rawId) : 'خالی'}).</div>
                    </div>
                `;
                    remitanceOutModal.show();
                    return;
                }

                // نمایش حالت لودینگ و باز کردن مودال
                viewRemitanceOutModalBody.innerHTML = defaultLoaderHtml;
                remitanceOutModal.show();

                const url = `view_remitance_out.php?id=${encodeURIComponent(remitanceOutId)}&project_id=${encodeURIComponent(projectId)}&ajax=1`;

                fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('کد خطای سرور: ' + response.status);
                        }
                        return response.text();
                    })
                    .then(html => {
                        viewRemitanceOutModalBody.innerHTML = html;
                    })
                    .catch(err => {
                        viewRemitanceOutModalBody.innerHTML = `
                    <div class="alert alert-danger d-flex align-items-center mb-0" role="alert">
                        <i class="bi bi-x-circle-fill me-2 fs-5"></i>
                        <div>خطا در دریافت اطلاعات حواله: ${err.message}</div>
                    </div>
                `;
                    });
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            const checkModalEl = document.getElementById('viewCheckModal');
            const checkModalBody = document.getElementById('viewCheckModalBody');
            if (!checkModalEl || !checkModalBody) return;

            const checkModal = bootstrap.Modal.getOrCreateInstance(checkModalEl);
            let triggerButton = null;

            const defaultLoaderHtml = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">در حال بارگذاری...</span>
            </div>
            <p class="text-muted mt-2 small">در حال دریافت اطلاعات چک...</p>
        </div>
    `;

            checkModalEl.addEventListener('hide.bs.modal', function () {
                if (document.activeElement && checkModalEl.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
            });

            checkModalEl.addEventListener('hidden.bs.modal', function () {
                checkModalBody.innerHTML = defaultLoaderHtml;
                if (triggerButton && typeof triggerButton.focus === 'function') {
                    triggerButton.focus();
                } else {
                    document.body.focus();
                }
            });

            // رویداد کلیک
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.btn-open-check');
                if (!btn) return;

                e.preventDefault();
                triggerButton = btn;

                // خواندن شناسه چک (پشتیبانی هم‌زمان از data-check-id و data-id)
                const rawId = btn.dataset.checkId || btn.dataset.id || btn.getAttribute('data-check-id') || btn.getAttribute('data-id');
                const rawProjectId = btn.dataset.projectId || btn.getAttribute('data-project-id') || 0;

                const checkId = parseInt(rawId, 10);
                const projectId = parseInt(rawProjectId, 10) || 0;

                if (isNaN(checkId) || checkId <= 0) {
                    checkModalBody.innerHTML = `
                <div class="alert alert-warning d-flex align-items-center mb-0" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <div>شناسه چک نامعتبر است (مقدار دریافتی: ${rawId || 'خالی'}).</div>
                </div>
            `;
                    checkModal.show();
                    return;
                }

                checkModalBody.innerHTML = defaultLoaderHtml;
                checkModal.show();

                const url = `view_check.php?id=${encodeURIComponent(checkId)}&project_id=${encodeURIComponent(projectId)}&ajax=1`;

                fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('کد خطای سرور: ' + response.status);
                        }
                        return response.text();
                    })
                    .then(html => {
                        checkModalBody.innerHTML = html;
                    })
                    .catch(err => {
                        checkModalBody.innerHTML = `
                <div class="alert alert-danger d-flex align-items-center mb-0" role="alert">
                    <i class="bi bi-x-circle-fill me-2 fs-5"></i>
                    <div>خطا در دریافت اطلاعات: ${err.message}</div>
                </div>
            `;
                    });
            });
        });




        document.addEventListener('DOMContentLoaded', function () {
            const deleteRemitanceOutModalEl = document.getElementById('deleteRemitanceOutModal');
            const deleteRemitanceOutForm    = document.getElementById('deleteRemitanceOutForm');
            const inputRemitanceOutId       = document.getElementById('delete_remitance_out_id');
            const inputProjectId            = document.getElementById('delete_project_id');
            const textRemitanceOutInfo      = document.getElementById('delete_remitance_out_info_text');
            const btnConfirmDelete          = document.getElementById('btnConfirmDelete');

            if (!deleteRemitanceOutModalEl || !deleteRemitanceOutForm) {
                return;
            }

            let isDeleting = false;
            let lastActiveTrigger = null;

            // پر کردن اطلاعات مودال قبل از نمایش
            deleteRemitanceOutModalEl.addEventListener('show.bs.modal', function (event) {
                lastActiveTrigger = event.relatedTarget || document.activeElement;
                const button = event.relatedTarget;
                if (!button) return;

                if (inputRemitanceOutId) {
                    inputRemitanceOutId.value = button.getAttribute('data-remitance-out-id') || '';
                }

                if (inputProjectId) {
                    const pId = button.getAttribute('data-project-id');
                    if (pId) inputProjectId.value = pId;
                }

                if (textRemitanceOutInfo) {
                    textRemitanceOutInfo.textContent = button.getAttribute('data-remitance-out-info') || 'حواله انتخاب‌شده';
                }

                // بازنشانی وضعیت دکمه
                isDeleting = false;
                if (btnConfirmDelete) {
                    btnConfirmDelete.disabled = false;
                    btnConfirmDelete.innerHTML = '<i class="bi bi-trash3 me-1"></i> حذف نهایی';
                }
            });

            // مدیریت فوکوس هنگام بسته شدن مودال جهت جلوگیری از خطای aria-hidden
            deleteRemitanceOutModalEl.addEventListener('hide.bs.modal', function () {
                if (document.activeElement && deleteRemitanceOutModalEl.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
            });

            deleteRemitanceOutModalEl.addEventListener('hidden.bs.modal', function () {
                isDeleting = false;
                if (lastActiveTrigger && typeof lastActiveTrigger.focus === 'function') {
                    try {
                        lastActiveTrigger.focus();
                    } catch (e) {
                        // نادیده‌گرفتن خطای فوکوس در صورت حذف عنصر از DOM
                    }
                }
            });

            // ارسال فرم با AJAX
            deleteRemitanceOutForm.addEventListener('submit', function (e) {
                e.preventDefault();

                if (isDeleting) return;
                isDeleting = true;

                if (btnConfirmDelete) {
                    btnConfirmDelete.disabled = true;
                    btnConfirmDelete.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> در حال حذف...';
                }

                const formData = new FormData(deleteRemitanceOutForm);
                const actionUrl = deleteRemitanceOutForm.getAttribute('action') || 'delete_remitance_out.php';

                fetch(actionUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                    .then(res => {
                        if (!res.ok) {
                            throw new Error(`خطای سرور: ${res.status}`);
                        }
                        return res.json();
                    })
                    .then(data => {
                        if (data && data.success) {
                            // رفرش صفحه جهت نمایش Flash Message در سشن
                            window.location.reload();
                        } else {
                            alert((data && data.message) ? data.message : 'خطا در حذف رکورد حواله');
                            resetButtonState();
                        }
                    })
                    .catch(err => {
                        console.error('Delete Remittance Error:', err);
                        // فال‌بک (Fallback): در صورت خطای شبکه یا ساختار پاسخ، فرم به صورت سنتی سابمیت می‌شود
                        deleteRemitanceOutForm.submit();
                    });
            });

            function resetButtonState() {
                isDeleting = false;
                if (btnConfirmDelete) {
                    btnConfirmDelete.disabled = false;
                    btnConfirmDelete.innerHTML = '<i class="bi bi-trash3 me-1"></i> حذف نهایی';
                }
            }
        });
        document.addEventListener('DOMContentLoaded', function () {
            const deleteCheckModal = document.getElementById('deleteCheckModal');
            const deleteCheckForm = document.getElementById('deleteCheckForm');
            const inputCheckId = document.getElementById('delete_check_id');
            const inputProjectId = document.getElementById('delete_project_id');
            const textCheckInfo = document.getElementById('delete_check_info_text');
            const btnConfirmDelete = document.getElementById('btnConfirmDelete');

            let isDeleting = false;

            // پر کردن مودال هنگام کلیک روی دکمه سه‌نقطه جدول
            if (deleteCheckModal) {
                deleteCheckModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    if (!button) return;

                    inputCheckId.value = button.getAttribute('data-check-id') || '';
                    const pId = button.getAttribute('data-project-id');
                    if (pId) inputProjectId.value = pId;
                    textCheckInfo.textContent = button.getAttribute('data-check-info') || 'چک انتخاب‌شده';

                    isDeleting = false;
                    btnConfirmDelete.disabled = false;
                    btnConfirmDelete.innerHTML = '<i class="bi bi-trash3 me-1"></i> حذف نهایی';
                });
            }

            // ارسال فرم
            if (deleteCheckForm) {
                // هنگام ارسال فرم با AJAX:
                deleteRemitanceOutForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    // بررسی اینکه آیا ID مقدار گرفته است یا خیر
                    const remitId = inputRemitanceOutId ? inputRemitanceOutId.value.trim() : '';
                    if (!remitId || remitId === '0') {
                        alert('خطا: شناسه حواله یافت نشد! لطفاً صفحه را رفرش کنید.');
                        return;
                    }

                    if (isDeleting) return;
                    isDeleting = true;

                    if (btnConfirmDelete) {
                        btnConfirmDelete.disabled = true;
                        btnConfirmDelete.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> در حال حذف...';
                    }

                    const formData = new FormData(deleteRemitanceOutForm);
                    const actionUrl = deleteRemitanceOutForm.getAttribute('action') || 'delete_remitance_out.php';

                    fetch(actionUrl, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                        .then(res => {
                            if (!res.ok) {
                                throw new Error(`خطای سرور: ${res.status}`);
                            }
                            return res.json();
                        })
                        .then(data => {
                            if (data && data.success) {
                                window.location.reload();
                            } else {
                                alert((data && data.message) ? data.message : 'خطا در حذف رکورد حواله');
                                resetButtonState();
                            }
                        })
                        .catch(err => {
                            console.error('Delete Remittance Error:', err);
                            // اگر خطای JSON یا شبکه داد، فرم را عادی ارسال کن
                            deleteRemitanceOutForm.submit();
                        });
                });
            }
        });






        // ==========================================
        // ۲. رویداد لود شدن DOM (مقداردهی اولیه)
        // ==========================================
        document.addEventListener('DOMContentLoaded', function () {

            // انتقال مودال‌ها به انتهای body جهت پیشگیری از خطاهای z-index
            document.querySelectorAll('.modal').forEach(function (modalEl) {
                if (modalEl.parentNode !== document.body) {
                    document.body.appendChild(modalEl);
                }
            });

            // الف) راه‌اندازی تقویم جلالی (@majidh1/jalalidatepicker)
            if (typeof jalaliDatepicker !== 'undefined') {
                jalaliDatepicker.startWatch({
                    minDate: "attr",
                    maxDate: "attr",
                    zIndex: 100050
                });
            }

            // ب) مدیریت کامل مودال افزودن چک و تقویم آن
            const checkModalEl = document.getElementById('addCheckModal');
            const checkForm = document.getElementById('addCheckForm');

            if (checkModalEl) {
                const addCheckModalInstance = bootstrap.Modal.getOrCreateInstance(checkModalEl);

                // وقتی مودال چک کامل باز شد
                checkModalEl.addEventListener('shown.bs.modal', function () {
                    if (typeof jalaliDatepicker !== 'undefined') {
                        jalaliDatepicker.startWatch({ zIndex: 100050 });
                    } else if (window.jQuery && $.fn.persianDatepicker) {
                        $(checkModalEl).find('.pwt-datepicker').persianDatepicker({
                            format: 'YYYY/MM/DD',
                            autoClose: true
                        });
                    }
                });

                // باز شدن قطعی تقویم با کلیک روی فیلد یا آیکون تقویم
                const dateInput = checkModalEl.querySelector('input[name="check_issuance_date"]');
                if (dateInput && typeof jalaliDatepicker !== 'undefined') {
                    ['click', 'focus'].forEach(function(evt) {
                        dateInput.addEventListener(evt, function () {
                            jalaliDatepicker.show(this);
                        });
                    });

                    const calendarIcon = dateInput.closest('.input-group')?.querySelector('.input-group-text');
                    if (calendarIcon) {
                        calendarIcon.style.cursor = 'pointer';
                        calendarIcon.addEventListener('click', function () {
                            dateInput.focus();
                            jalaliDatepicker.show(dateInput);
                        });
                    }
                }

                // ارسال فرم چک با AJAX
                if (checkForm) {
                    checkForm.addEventListener('submit', function (e) {
                        e.preventDefault();

                        const submitBtn = checkForm.querySelector('button[type="submit"]');
                        const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';

                        const oldAlert = checkForm.querySelector('.form-feedback-alert');
                        if (oldAlert) oldAlert.remove();

                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> در حال ذخیره...';
                        }

                        const formData = new FormData(checkForm);

                        fetch(checkForm.getAttribute('action') || 'add_check.php', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                            .then(async response => {
                                const text = await response.text();
                                try {
                                    return JSON.parse(text);
                                } catch (err) {
                                    if (response.ok) {
                                        return { success: true, message: 'چک با موفقیت ثبت شد.' };
                                    }
                                    throw new Error(text || 'پاسخ دریافتی از سرور معتبر نیست.');
                                }
                            })
                            .then(data => {
                                const alertDiv = document.createElement('div');
                                alertDiv.className = `alert ${data.success ? 'alert-success' : 'alert-danger'} m-3 form-feedback-alert`;
                                alertDiv.innerHTML = `<i class="bi ${data.success ? 'bi-check-circle' : 'bi-exclamation-triangle'} me-1"></i> ${data.message || (data.success ? 'چک با موفقیت ثبت شد.' : 'خطا در ثبت اطلاعات چک.')}`;
                                checkForm.querySelector('.modal-body').prepend(alertDiv);

                                if (data.success) {
                                    checkForm.reset();
                                    setTimeout(() => {
                                        addCheckModalInstance.hide();
                                        location.reload();
                                    }, 1000);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                const alertDiv = document.createElement('div');
                                alertDiv.className = 'alert alert-danger m-3 form-feedback-alert';
                                alertDiv.innerHTML = `<i class="bi bi-exclamation-triangle me-1"></i> ${error.message || 'خطا در برقراری ارتباط با سرور.'}`;
                                checkForm.querySelector('.modal-body').prepend(alertDiv);
                            })
                            .finally(() => {
                                if (submitBtn) {
                                    submitBtn.disabled = false;
                                    submitBtn.innerHTML = originalBtnHtml;
                                }
                            });
                    });
                }
            }

            // ج) دکمه باز و بسته کردن ساب‌منوی هزینه‌ها (expenseToggleBtn)
            const toggleBtn = document.getElementById('expenseToggleBtn');
            const submenuContent = document.getElementById('expenseSubmenu') || document.getElementById('expenseSubmenuContent');
            if (toggleBtn && submenuContent) {
                const icon = toggleBtn.querySelector('.transition-icon, .toggle-icon');
                toggleBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    submenuContent.classList.toggle('open');
                    if (icon) {
                        icon.classList.toggle('rotated');
                        icon.classList.toggle('rotate-45');
                    }
                });
            }
        });

        // ==========================================
        // ۳. لاجیک فیلترهای حساب، ذینفع و سرفصل‌ها
        // ==========================================
        document.addEventListener('DOMContentLoaded', function () {
            // مپ داده‌های حساب بانکی و ذی‌نفع
            const bankMap = <?= json_encode($bank_hesabMap ?? [], JSON_UNESCAPED_UNICODE) ?>;
            const savedBank = "<?= htmlspecialchars($current_bank_hesab ?? '', ENT_QUOTES, 'UTF-8') ?>";
            const savedBen  = "<?= htmlspecialchars($current_beneficiary ?? '', ENT_QUOTES, 'UTF-8') ?>";

            const $bankList   = $('#bankItemsList');
            const $benList    = $('#beneficiaryItemsList');
            const $bankHidden = $('#selectedBankInput');
            const $benHidden  = $('#selectedbeneficiaryInput');

            function bindSearch(searchId, listId) {
                $(document).on('input', '#' + searchId, function () {
                    const q = $(this).val().trim().toLowerCase();
                    $('#' + listId + ' .dropdown-item').each(function () {
                        const text = $(this).text().trim().toLowerCase();
                        $(this).toggle(text.includes(q));
                    });
                });
            }
            bindSearch('bankSearchInput', 'bankItemsList');
            bindSearch('beneficiarySearchInput', 'beneficiaryItemsList');

            function populateBeneficiaries(bank, selectedBen) {
                $benList.empty();
                $benList.append('<a class="dropdown-item py-2 rounded ' + (!selectedBen ? 'active' : '') + '" href="javascript:void(0)" data-val="">-- همه ذی‌نفع‌ها --</a>');

                const items = (bank && bankMap[bank]) ? bankMap[bank] : [];

                if (items.length > 0) {
                    items.forEach(function (b) {
                        const isSelected = (String(b).trim() === String(selectedBen).trim());
                        const cls = isSelected ? ' active' : '';
                        $benList.append(
                            $('<a/>', {
                                class: 'dropdown-item py-2 rounded' + cls,
                                href: 'javascript:void(0)',
                                'data-val': b,
                                text: b
                            })
                        );
                    });
                } else {
                    $benList.append('<span class="dropdown-item-text text-muted small">موردی یافت نشد</span>');
                }

                if (selectedBen && !items.includes(selectedBen)) {
                    $benHidden.val('');
                    $('#beneficiaryBtnText').text('انتخاب یا جست‌وجوی ذینفع...');
                } else if (selectedBen) {
                    $('#beneficiaryBtnText').text(selectedBen);
                    $benHidden.val(selectedBen);
                }
            }

            $(document).on('click', '#bankItemsList .dropdown-item', function (e) {
                e.preventDefault();
                const val = $(this).attr('data-val') || '';

                $('#bankItemsList .dropdown-item').removeClass('active');
                $(this).addClass('active');

                $bankHidden.val(val);
                $('#bankBtnText').text(val ? val : 'انتخاب یا جست‌وجوی حساب...');

                $benHidden.val('');
                $('#beneficiaryBtnText').text('انتخاب یا جست‌وجوی ذینفع...');
                populateBeneficiaries(val, '');

                const bankDropdown = bootstrap.Dropdown.getInstance(document.getElementById('bankDropdownBtn'));
                if (bankDropdown) bankDropdown.hide();
            });

            $(document).on('click', '#beneficiaryItemsList .dropdown-item', function (e) {
                e.preventDefault();
                const val = $(this).attr('data-val') || '';

                $('#beneficiaryItemsList .dropdown-item').removeClass('active');
                $(this).addClass('active');

                $benHidden.val(val);
                $('#beneficiaryBtnText').text(val ? val : 'انتخاب یا جست‌وجوی ذینفع...');

                const benDropdown = bootstrap.Dropdown.getInstance(document.getElementById('beneficiaryDropdownBtn'));
                if (benDropdown) benDropdown.hide();
            });

            if (savedBank) {
                populateBeneficiaries(savedBank, savedBen);
            }
        });

        // مدیریت سرفصل و زیرفصل‌ها
        document.addEventListener('DOMContentLoaded', function () {
            const transactionSubMap = <?= json_encode($transactionSubMap ?? [], JSON_UNESCAPED_UNICODE) ?> || {};
            const hiddenInput      = document.getElementById('selectedTransactionInput');
            const btnText          = document.getElementById('transactionBtnText');
            const searchInput      = document.getElementById('transactionSearchInput');
            const itemsList        = document.getElementById('transactionItemsList');
            const subchapterSelect = document.getElementById('filterSubchapter');

            if (!itemsList || !subchapterSelect) return;

            function updateSubchapters(selectedTr, preselectedSub = '') {
                subchapterSelect.innerHTML = '<option value="">-- همه زیرفصل‌ها --</option>';

                if (!selectedTr || !transactionSubMap[selectedTr] || transactionSubMap[selectedTr].length === 0) {
                    subchapterSelect.disabled = true;
                    return;
                }

                subchapterSelect.disabled = false;
                transactionSubMap[selectedTr].forEach(function (sub) {
                    const opt = document.createElement('option');
                    opt.value = sub;
                    opt.textContent = sub;
                    if (preselectedSub && sub === preselectedSub) {
                        opt.selected = true;
                    }
                    subchapterSelect.appendChild(opt);
                });

                if (typeof jQuery !== 'undefined' && jQuery(subchapterSelect).hasClass('select2-hidden-accessible')) {
                    jQuery(subchapterSelect).trigger('change');
                }
            }

            itemsList.addEventListener('click', function (e) {
                const target = e.target.closest('a[data-val]');
                if (!target) return;
                e.preventDefault();

                const val = target.getAttribute('data-val');
                if (hiddenInput) hiddenInput.value = val;
                if (btnText) btnText.textContent = val !== '' ? val : '-- همه سرفصل‌ها --';

                itemsList.querySelectorAll('a').forEach(a => a.classList.remove('active', 'bg-primary', 'text-white'));
                if (val !== '') {
                    target.classList.add('active', 'bg-primary', 'text-white');
                }

                const dropdownBtn = document.getElementById('transactionDropdownBtn');
                if (dropdownBtn && typeof bootstrap !== 'undefined') {
                    const dropdownInstance = bootstrap.Dropdown.getInstance(dropdownBtn);
                    if (dropdownInstance) dropdownInstance.hide();
                }

                if (searchInput) {
                    searchInput.value = '';
                    itemsList.querySelectorAll('a[data-val]').forEach(el => el.style.display = '');
                }

                updateSubchapters(val);
            });

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const q = this.value.trim().toLowerCase();
                    itemsList.querySelectorAll('a[data-val]').forEach(function (item) {
                        const text = item.textContent.toLowerCase();
                        item.style.display = (item.getAttribute('data-val') === '' || text.includes(q)) ? '' : 'none';
                    });
                });
            }
        });

        // ==========================================
        // ۴. سایر توابع داشبورد (سندها، اسناد و انتقال)
        // ==========================================
        function showDocumentDetails(button) {
            const modalEl = document.getElementById('documentDetailModal');
            if (!modalEl) return;

            if (modalEl.parentElement !== document.body) {
                document.body.appendChild(modalEl);
            }

            const doc = button.getAttribute('data-doc') || '-';
            const history = button.getAttribute('data-history') || '-';
            const amount = button.getAttribute('data-amount') || '-';
            const beneficiary = button.getAttribute('data-beneficiary') || '-';
            const chapter = button.getAttribute('data-transaction') || '-';
            const subchapter = button.getAttribute('data-subchapter') || '-';
            const desc = button.getAttribute('data-description') || '';

            document.getElementById('modalDocTitle').textContent = doc;
            document.getElementById('modalDocDate').textContent = history;
            document.getElementById('modalDocAmount').textContent = amount;
            document.getElementById('modalDocBeneficiary').textContent = beneficiary;
            document.getElementById('modalDocChapter').textContent = chapter;
            document.getElementById('modalDocSubchapter').textContent = subchapter;

            const descBox = document.getElementById('modalDocDescription');
            if (desc && desc.trim() !== '') {
                descBox.textContent = desc;
                descBox.classList.remove('text-muted');
            } else {
                descBox.textContent = 'توضیحات و شرحی برای این سند ثبت نشده است.';
                descBox.classList.add('text-muted');
            }

            const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            modalInstance.show();
        }

        const docState = {
            page: 1,
            perPage: 10,
            sortField: 'upload_date',
            sortOrder: 'desc',
            search: '',
            status: '',
            loading: false
        };

        function loadDocuments() {
            if (docState.loading) return;
            docState.loading = true;

            const params = new URLSearchParams({
                ajax: 1,
                user_id: typeof userId !== "undefined" ? userId : 0,
                page: docState.page,
                per_page: docState.perPage,
                doc_sort: docState.sortField,
                doc_order: docState.sortOrder,
                search: docState.search
            });

            if (docState.status) {
                params.append('status', docState.status);
            }

            fetch('/admin/loadDocuments.php?' + params.toString())
                .then(res => res.text())
                .then(html => {
                    const tbody = document.getElementById('documentsBody');
                    const pagination = document.getElementById('documentsPagination');
                    if (!tbody) return;

                    const parts = html.split('<!--PAGINATION-->');
                    tbody.innerHTML = parts[0];

                    if (pagination && parts[1]) {
                        pagination.innerHTML = parts[1];
                    }
                    updateSortIcons();
                })
                .catch(err => console.error(err))
                .finally(() => docState.loading = false);
        }

        document.querySelectorAll('.table-documents th.sortable').forEach(th => {
            th.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const field = this.dataset.field;
                if (docState.sortField === field) {
                    docState.sortOrder = docState.sortOrder === 'asc' ? 'desc' : 'asc';
                } else {
                    docState.sortField = field;
                    docState.sortOrder = 'asc';
                }
                docState.page = 1;
                updateSortIcons();
                loadDocuments();
            });
        });

        function updateSortIcons() {
            document.querySelectorAll('.sortable i').forEach(icon => {
                icon.className = 'bi bi-arrow-down-up';
                icon.style.opacity = '0.4';
            });

            const active = document.querySelector(`.sortable[data-field="${docState.sortField}"] i`);
            if (active) {
                active.className = docState.sortOrder === 'asc' ? 'bi bi-arrow-up' : 'bi bi-arrow-down';
                active.style.opacity = '1';
            }
        }

        let searchTimer = null;
        function debouncedSearch() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                const input = document.getElementById('searchInput');
                docState.search = input ? input.value.trim() : '';
                docState.page = 1;
                loadDocuments();
            }, 400);
        }
        function searchDocuments() { debouncedSearch(); }
        function filterDocuments(status) { docState.page = 1; docState.status = status || ''; loadDocuments(); }
        function goToPage(page) { docState.page = page; loadDocuments(); }

        function updateDocumentStatus(documentId, status) {
            if (!documentId || !status) return;
            if (!confirm('آیا از این عملیات اطمینان دارید؟')) return;

            fetch('/maskansazan/admin/change_document_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ document_id: documentId, status: status })
            })
                .then(res => res.json())
                .then(data => {
                    alert(data.message || 'وضعیت مدرک بروزرسانی شد');
                    if (data.success) loadDocuments();
                })
                .catch(() => alert('خطا در ارتباط با سرور'));
        }

        function uploadDocument() {
            const form = document.getElementById('uploadDocumentForm');
            const type = document.getElementById('documentType');
            const file = document.getElementById('documentFile');

            if (!type.value) {
                showToast('نوع مدرک را انتخاب کنید', 'danger');
                return;
            }
            if (!file.files.length) {
                showToast('فایل مدرک را انتخاب کنید', 'danger');
                return;
            }

            fetch('upload_document.php', {
                method: 'POST',
                body: new FormData(form)
            })
                .then(r => r.json())
                .then(d => {
                    showToast(d.message, d.status ? 'success' : 'danger');
                    if (d.status) {
                        bootstrap.Modal.getInstance(document.getElementById('uploadDocumentModal')).hide();
                        form.reset();
                        loadDocuments();
                    }
                });
        }

        function deleteDocument(documentId) {
            if (!confirm('آیا از حذف این مدرک اطمینان دارید؟')) return;
            const formData = new FormData();
            formData.append('document_id', documentId);

            fetch('/admin/delete_document.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) loadDocuments();
                })
                .catch(() => alert('خطا در ارتباط با سرور'));
        }

        function updateTransferStatus(id, status) {
            if (!confirm('آیا مطمئن هستید؟')) return;
            fetch('transfer_update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, status })
            })
                .then(r => r.json())
                .then(d => {
                    alert(d.message);
                    if (d.status) location.reload();
                });
        }

        function deleteTransfer(id) {
            if (!confirm('آیا از حذف این حواله مطمئن هستید؟')) return;
            fetch('/admin/transfer_delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            })
                .then(res => res.json())
                .then(data => {
                    showToast(data.message, data.status ? 'success' : 'danger');
                    location.reload();
                })
                .catch(() => showToast('خطا در ارتباط با سرور', 'danger'));
        }

        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) sidebar.classList.toggle('active');
        }

        function openReceiptModal(imageSrc) {
            const modalImage = document.getElementById('receiptImage');
            if (modalImage) modalImage.src = imageSrc;
            const myModal = new bootstrap.Modal(document.getElementById('receiptModal'));
            myModal.show();
        }

        function showToast(message, type = 'success') {
            const positionClass = window.innerWidth < 768
                ? 'top-0 start-50 translate-middle-x'
                : 'top-0 end-0';

            const toastHTML = `
        <div class="toast-container position-fixed ${positionClass} p-3" style="z-index:1060">
            <div class="toast show text-bg-${type}">
                <div class="toast-body text-center">
                    ${message}
                </div>
            </div>
        </div>`;

            document.body.insertAdjacentHTML('beforeend', toastHTML);
            setTimeout(() => {
                document.querySelector('.toast-container')?.remove();
            }, 4000);
        }

        // انتخاب پروژه
        document.addEventListener('DOMContentLoaded', function () {
            const projectSelect = document.getElementById('project_id_selector');
            const menuLinks = document.querySelectorAll('.menu-link');
            if (!projectSelect) return;

            function updateMenuLinks() {
                const projectId = projectSelect.value;
                menuLinks.forEach(link => {
                    const tab = link.getAttribute('data-tab');
                    if (projectId && tab) {
                        link.href = `?tab=${encodeURIComponent(tab)}&project_id=${encodeURIComponent(projectId)}`;
                    } else {
                        link.href = '#';
                    }
                });
            }
            projectSelect.addEventListener('change', updateMenuLinks);
            updateMenuLinks();
        });
    </script>


<?php
// بستن اتصال دیتابیس
SafeDatabase::close();
ob_end_flush();
?>
<?php
$content = ob_get_clean();
$title = "گروه مسکن سازان";
include '../partials/layout.php';
?>
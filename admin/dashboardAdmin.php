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
    $stmt = $pdo->prepare("
        SELECT * FROM 
            member_statements AS ms
            INNER JOIN projects AS p ON ms.project_id=p.id
            INNER JOIN users_project AS up ON ms.users_project_id=up.id
            WHERE ms.project_id=:project_id
            AND ms.users_project_id=:user_project_id
    ");
    $stmt->execute([
            ':project_id' => $selectedProjectId,
            ':user_project_id' => $user_project_id
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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


// ========== اتمام کد PHP ==========
ob_start();
?>
<!-- ۱. فونت‌ها و آیکون‌ها -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- ۲. استایل اصلی قالب (Bootstrap 5 RTL) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">

<!-- ۳. استایل دیتاتیبل مخصوص بوت‌استرپ ۵ -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

<!-- ۴. کتابخانه jQuery (باید قبل از اسکریپت‌های دیگر لود شود) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<!-- ۵. جاوااسکریپت بوت‌استرپ ۵ (نسخه ۳ حذف شد تا تداخل رفع شود) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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
    <h6 class="text-white mb-3">منو</h6>
    <div class="list-group">


        <a href="dashboardAdmin.php?tab=members&project_id=<?= $selectedProjectId ?>"
           class="list-group-item list-group-item-action <?= $activeTab === 'members' ? 'active' : '' ?>">
            <i class="bi bi-people me-2"></i>اعضای سایت
        </a>
        <div class="list-group">
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                    <a href="dashboardAdmin.php?tab=expenses&project_id=<?= (int)$selectedProjectId ?>"
                       class="text-decoration-none <?= $activeTab === 'expenses' ? 'fw-bold text-primary' : 'text-dark' ?>">
                        <i class="bi bi-receipt me-2"></i> هزینه
                    </a>

                    <button
                            class="btn btn-sm border-0 shadow-none"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#collapse1"
                            aria-expanded="false"
                            aria-controls="collapse1"
                    >
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>

                <div id="collapse1" class="collapse mt-2">
                    <div class="ps-3 border-start border-2 border-primary">
                        <div class="py-1">چک</div>
                        <div class="py-1">حواله</div>
                    </div>
                </div>
            </div>
        </div>




        <a href="dashboardAdmin.php?tab=income&project_id=<?= $selectedProjectId ?>"
           class="list-group-item list-group-item-action <?= $activeTab === 'income' ? 'active' : '' ?>">
            <i class="bi bi-graph-up-arrow me-2"></i>درآمد
        </a>

        <a href="dashboardAdmin.php?tab=finance&project_id=<?= $selectedProjectId ?>"
           class="list-group-item list-group-item-action <?= $activeTab === 'finance' ? 'active' : '' ?>">
            <i class="bi bi-cash-coin me-2"></i>بیلان مالی
        </a>
        <a href="dashboardAdmin.php?tab=suppliers&project_id=<?= $selectedProjectId ?>"
           class="list-group-item list-group-item-action <?= $activeTab === 'suppliers' ? 'active' : '' ?>">
            <i class="bi bi-truck me-2"></i>تامین‌کنندگان/پیمانکاران
        </a>


        <a href="dashboardAdmin.php?tab=tickets&project_id=<?= $selectedProjectId ?>"
           class="list-group-item list-group-item-action <?= $activeTab === 'tickets' ? 'active' : '' ?>">
            <i class="bi bi-chat-dots me-2"></i>تیکت
        </a>
    </div>
</div>
<?php if ($activeTab === 'members'): ?>

    <div class="card mt-4">
        <div class="card-body">


            <h5 class="mb-3">اعضای پروژه</h5>
            <div class="row">
                <div class="mb-3 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="memberSearchInput" class="form-control" placeholder="جستجوی آنی نام یا نام خانوادگی...">
                    </div>
                </div>
            </div>
            <?php if ($selectedProjectId <= 0): ?>
                <div class="alert alert-warning">لطفاً ابتدا یک پروژه انتخاب کنید.</div>

            <?php elseif (empty($projectMembers)): ?>
                <div class="alert alert-info">برای این پروژه عضوی ثبت نشده است.</div>

            <?php else: ?>
                <div class="table-responsive">
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
    <div class="card mt-4">
        <div class="card-body">
            <h5 class="mb-3">لیست قست های پرداخت شده</h5>

            <?php if ($selectedProjectId <= 0): ?>
                <div class="alert alert-warning">لطفاً ابتدا یک پروژه انتخاب کنید.</div>
            <?php elseif (empty($rows)): ?>
                <div class="alert alert-info">برای این پروژه عضوی ثبت نشده است.</div>
            <?php else: ?>
                <div class="table-responsive mt-4">
                    <table class="table table-bordered table-hover shadow-sm">
                        <thead class="table-light">
                        <tr style="text-align: left">
                            <th>ردیف</th>
                            <th>نام عضو</th>
                            <th>عنوان قسط</th>
                            <th>تاریخ قسط</th>
                            <th>مبلغ قسط (ریال)</th>
                            <th>مبلغ پرداختی (ریال)</th>
                            <th>روش پرداخت</th>
                            <th>توضیحات</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rows as $i=> $row): ?>
                            <tr>
                                <td><?= $i+1 ?></td>
                                <td><?= htmlspecialchars($row['member_name']) ?></td>
                                <td><?= htmlspecialchars($row['installment_title']) ?></td>
                                <td class="text-nowrap"><?= htmlspecialchars($row['installment_date']) ?></td>
                                <td><?= number_format($row['installment_amount']) ?></td>
                                <td><?= number_format($row['paid_amount']) ?></td>
                                <td><?= htmlspecialchars($row['payment_method'] ?? '---') ?></td>
                                <td><?= htmlspecialchars($row['description'] ?? '---') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>
        </div>
    </div>
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
<!-- jQuery اول -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- memberSearch -->
<script src="../js/memberSearch.js"></script>

<!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    $(document).ready(function () {

        console.log('jQuery:', typeof $);
        console.log('DataTable:', typeof $.fn.DataTable);

        // اگر خواستید بعداً فعال کنید
        /*
        $('#membersTable').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[0, 'asc']],
            columnDefs: [
                { orderable: false, targets: [3] }
            ],
            language: {
                search: "جستجو:",
                lengthMenu: "نمایش _MENU_ عضو",
                info: "نمایش _START_ تا _END_ از _TOTAL_ عضو",
                infoEmpty: "عضوی برای نمایش وجود ندارد",
                zeroRecords: "نتیجه‌ای یافت نشد",
                paginate: {
                    first: "اول",
                    last: "آخر",
                    next: "بعدی",
                    previous: "قبلی"
                }
            }
        });
        */

    });


    /* ===============================
       STATE
    ================================ */
    const docState = {
        page: 1,
        perPage: 10,
        sortField: 'upload_date',
        sortOrder: 'desc',
        search: '',
        status: '',
        loading: false
    };


    /* ===============================
       LOAD DOCUMENTS (AJAX)
    ================================ */
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


    /* ===============================
       SORT
    ================================ */
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

        const active = document.querySelector(
            `.sortable[data-field="${docState.sortField}"] i`
        );

        if (active) {

            active.className =
                docState.sortOrder === 'asc'
                    ? 'bi bi-arrow-up'
                    : 'bi bi-arrow-down';

            active.style.opacity = '1';
        }

    }


    /* ===============================
       SEARCH
    ================================ */
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

    function searchDocuments() {
        debouncedSearch();
    }


    /* ===============================
       FILTER STATUS
    ================================ */
    function filterDocuments(status) {

        docState.page = 1;
        docState.status = status || '';

        loadDocuments();

    }


    /* ===============================
       PAGINATION
    ================================ */
    function goToPage(page) {

        docState.page = page;

        loadDocuments();

    }


    /* ===============================
       DOCUMENT STATUS UPDATE
    ================================ */
    function updateDocumentStatus(documentId, status) {

        if (!documentId || !status) return;

        if (!confirm('آیا از این عملیات اطمینان دارید؟')) return;

        fetch('/maskansazan/admin/change_document_status.php', {

            method: 'POST',

            headers: {
                'Content-Type': 'application/json'
            },

            body: JSON.stringify({
                document_id: documentId,
                status: status
            })

        })
            .then(res => res.json())
            .then(data => {

                alert(data.message || 'وضعیت مدرک بروزرسانی شد');

                if (data.success) {
                    loadDocuments();
                }

            })
            .catch(() => alert('خطا در ارتباط با سرور'));

    }


    /* ===============================
       UPLOAD DOCUMENT
    ================================ */
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

                    bootstrap.Modal.getInstance(
                        document.getElementById('uploadDocumentModal')
                    ).hide();

                    form.reset();

                    loadDocuments();
                }

            });

    }


    /* ===============================
       DELETE DOCUMENT
    ================================ */
    function deleteDocument(documentId) {

        if (!confirm('آیا از حذف این مدرک اطمینان دارید؟')) return;

        const formData = new FormData();

        formData.append('document_id', documentId);

        fetch('/admin/delete_document.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {

                alert(data.message);

                if (data.success) {
                    loadDocuments();
                }

            })
            .catch(() => alert('خطا در ارتباط با سرور'));

    }


    /* ===============================
       TRANSFER
    ================================ */
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


    /* ===============================
       UI
    ================================ */
    function toggleSidebar() {

        const sidebar = document.querySelector('.sidebar');

        if (sidebar) sidebar.classList.toggle('active');

    }


    function openReceiptModal(imageSrc) {

        const modalImage = document.getElementById('receiptImage');

        modalImage.src = imageSrc;

        const myModal = new bootstrap.Modal(
            document.getElementById('receiptModal')
        );

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


    /* ===============================
       PROJECT SELECTOR
    ================================ */
    document.addEventListener('DOMContentLoaded', function () {

        const projectSelect = document.getElementById('project_id_selector');
        const menuLinks = document.querySelectorAll('.menu-link');

        if (!projectSelect) return;

        function updateMenuLinks() {

            const projectId = projectSelect.value;

            menuLinks.forEach(link => {

                const tab = link.getAttribute('data-tab');

                if (projectId && tab) {

                    link.href =
                        `?tab=${encodeURIComponent(tab)}&project_id=${encodeURIComponent(projectId)}`;

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
include '../partials/slider.php';
?>

<?php
// process_register.php - نسخه نهایی
// تاریخ: ۱۴۰۳/۱۰/۲۱

// ==================== تنظیمات اولیه ====================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
error_log("========== START process_register.php ==========");

header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('Asia/Tehran');

// ==================== توابع کمکی ====================

/**
 * اعتبارسنجی شماره ملی
 */
function isValidNationalId($national_id) {
    $national_id = preg_replace('/[^\d]/', '', $national_id);
    
    if (strlen($national_id) !== 10) {
        return false;
    }
    
    // بررسی اینکه همه ارقام یکسان نباشند
    if (count(array_unique(str_split($national_id))) === 1) {
        return false;
    }
    
    // الگوریتم کنترل شماره ملی
    $sum = 0;
    for ($i = 0; $i < 9; $i++) {
        $sum += (int)$national_id[$i] * (10 - $i);
    }
    
    $remainder = $sum % 11;
    $controlDigit = (int)$national_id[9];
    
    if ($remainder < 2) {
        return $controlDigit === $remainder;
    } else {
        return $controlDigit === (11 - $remainder);
    }
}

/**
 * اعتبارسنجی شماره همراه
 */
function isValidMobile($mobile) {
    $mobile = preg_replace('/[^\d]/', '', $mobile);
    
    if (strlen($mobile) !== 11 || substr($mobile, 0, 2) !== '09') {
        return false;
    }
    
    return true;
}

/**
 * اعتبارسنجی کد پستی
 */
function isValidPostalCode($postal_code) {
    $postal_code = preg_replace('/[^\d]/', '', $postal_code);
    
    if (strlen($postal_code) !== 10) {
        return false;
    }
    
    return true;
}

/**
 * ایجاد نام امن برای فایل
 */
function generateSafeFilename($original_name, $field_name) {
    $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
    
    if (!in_array($extension, $allowed_extensions)) {
        $extension = 'jpg';
    }
    
    $timestamp = time();
    $random = bin2hex(random_bytes(4));
    $safe_name = "{$field_name}_{$timestamp}_{$random}.{$extension}";
    
    return preg_replace('/[^a-zA-Z0-9._-]/', '', $safe_name);
}

/**
 * تبدیل تاریخ شمسی به میلادی
 */
function jalaliToGregorian($jalali_date) {
    if (empty($jalali_date)) return null;
    
    // فرمت: 1403/10/01
    $parts = explode('/', $jalali_date);
    if (count($parts) !== 3) return null;
    
    $j_y = (int)$parts[0];
    $j_m = (int)$parts[1];
    $j_d = (int)$parts[2];
    
    // بررسی محدوده صحیح
    if ($j_y < 1300 || $j_y > 1500 || $j_m < 1 || $j_m > 12 || $j_d < 1 || $j_d > 31) {
        return null;
    }
    
    // تبدیل ساده
    $g_y = $j_y + 621;
    
    // اعتبارسنجی روزهای ماه
    $jalali_months_31 = [1, 2, 3, 4, 5, 6];
    $jalali_months_30 = [7, 8, 9, 10, 11];
    
    if (in_array($j_m, $jalali_months_31) && $j_d > 31) return null;
    if (in_array($j_m, $jalali_months_30) && $j_d > 30) return null;
    if ($j_m == 12 && $j_d > 29) {
        // بررسی سال کبیسه شمسی
        if (($j_y % 4) != 3 && $j_d > 29) return null;
    }
    
    return sprintf('%04d-%02d-%02d', $g_y, $j_m, $j_d);
}

/**
 * اعتبارسنجی تاریخ شمسی چک
 */
function isValidJalaliDate($date) {
    if (empty($date)) return false;
    
    // فرمت: 1403/10/01
    if (!preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $date)) {
        return false;
    }
    
    $parts = explode('/', $date);
    if (count($parts) !== 3) return false;
    
    $year = (int)$parts[0];
    $month = (int)$parts[1];
    $day = (int)$parts[2];
    
    // بررسی محدوده سال (1300 تا 1500)
    if ($year < 1300 || $year > 1500) {
        return false;
    }
    
    // بررسی ماه (1 تا 12)
    if ($month < 1 || $month > 12) {
        return false;
    }
    
    // بررسی روز بر اساس ماه
    $months_31 = [1, 2, 3, 4, 5, 6];
    $months_30 = [7, 8, 9, 10, 11];
    
    if (in_array($month, $months_31) && $day > 31) {
        return false;
    }
    
    if (in_array($month, $months_30) && $day > 30) {
        return false;
    }
    
    if ($month == 12) {
        // اسفند: 29 روز (در سال کبیسه 30 روز)
        if (($year % 4) == 3) { // سال کبیسه شمسی
            if ($day > 30) return false;
        } else {
            if ($day > 29) return false;
        }
    }
    
    // بررسی تاریخ آینده نباشد
    $gregorian_date = jalaliToGregorian($date);
    if (!$gregorian_date) return false;
    
    $check_date = strtotime($gregorian_date);
    $today = strtotime(date('Y-m-d'));
    
    return $check_date >= $today;
}

/**
 * تبدیل اعداد انگلیسی به فارسی
 */
function toPersianNumbers($text) {
    $english = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    $persian = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
    return str_replace($english, $persian, $text);
}

/**
 * تبدیل اعداد فارسی به انگلیسی
 */
function toEnglishNumbers($text) {
    $persian = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
    $english = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    return str_replace($persian, $english, $text);
}

// ==================== پردازش اصلی ====================

try {
    // بررسی متد درخواست
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('لطفاً فرم را از طریق صفحه ثبت نام ارسال کنید.');
    }
    
    error_log("POST data received");
    
    // ==================== دریافت و اعتبارسنجی داده‌ها ====================
    
    // فیلدهای الزامی اصلی
    $required_fields = [
        'first_name' => 'نام',
        'last_name' => 'نام خانوادگی',
        'national_id' => 'شماره ملی',
        'birth_certificate_number' => 'شماره شناسنامه',
        'father_name' => 'نام پدر',
        'birth_date_fa' => 'تاریخ تولد',
        'mobile' => 'شماره همراه',
        'address' => 'آدرس',
        'postalCode' => 'کد پستی',
        'project_id_reg' => 'پروژه',
        'not_robot' => 'تأیید ربات',
        'confirm_info' => 'تأیید اطلاعات'
    ];
    
    $errors = [];
    $form_data = [];
    
    foreach ($required_fields as $field => $label) {
        if ($field === 'not_robot' || $field === 'confirm_info') {
            $form_data[$field] = $_POST[$field] ?? 'off';
            if ($form_data[$field] !== 'on') {
                $errors[] = "لطفاً گزینه '{$label}' را انتخاب کنید.";
            }
        } else {
            $value = trim($_POST[$field] ?? '');
            if (empty($value)) {
                $errors[] = "{$label} الزامی است.";
            }
            $form_data[$field] = $value;
        }
    }
    
    // فیلدهای اختیاری
    $optional_fields = ['phone'];
    $optional_fields = ['project_id_reg'];
    foreach ($optional_fields as $field) {
        $form_data[$field] = trim($_POST[$field] ?? '');
    }
    
    // اعتبارسنجی شماره ملی
    if (!empty($form_data['national_id']) && !isValidNationalId(toEnglishNumbers($form_data['national_id']))) {
        $errors[] = 'شماره ملی وارد شده معتبر نیست.';
    }
    
    // اعتبارسنجی شماره همراه
    if (!empty($form_data['mobile']) && !isValidMobile(toEnglishNumbers($form_data['mobile']))) {
        $errors[] = 'شماره همراه معتبر نیست. فرمت صحیح: ۰۹۱۲۳۴۵۶۷۸۹';
    }
    
    // اعتبارسنجی کد پستی
    if (!empty($form_data['postalCode']) && !isValidPostalCode(toEnglishNumbers($form_data['postalCode']))) {
        $errors[] = 'کد پستی باید ۱۰ رقم باشد.';
    }
    
    // اعتبارسنجی تاریخ تولد
    
    // ==================== پردازش اطلاعات چک‌ها (تاریخ شمسی) ====================
    
    $check_data = [];
    for ($i = 1; $i <= 3; $i++) {
        $check_number = trim($_POST["check{$i}_number"] ?? '');
        $check_date = trim($_POST["check{$i}_date"] ?? '');
        $check_amount = trim($_POST["check{$i}_amount"] ?? '');
        $check_bank = trim($_POST["check{$i}_bank"] ?? '');
        
        // اگر حداقل یکی از فیلدها پر شده
        if (!empty($check_number) || !empty($check_date) || !empty($check_amount) || !empty($check_bank)) {
            
            if (empty($check_number)) {
                $errors[] = "شماره چک {$i} الزامی است.";
            }
            
            if (empty($check_date)) {
                $errors[] = "تاریخ چک {$i} الزامی است.";
            }
            
            if (empty($check_amount)) {
                $errors[] = "مبلغ چک {$i} الزامی است.";
            } elseif (!is_numeric(toEnglishNumbers($check_amount)) || toEnglishNumbers($check_amount) <= 0) {
                $errors[] = "مبلغ چک {$i} باید عدد مثبت باشد.";
            }
            
            if (empty($check_bank)) {
                $errors[] = "بانک صادرکننده چک {$i} الزامی است.";
            }
            
            $check_data["check{$i}"] = [
                'number' => $check_number,
                'date' => $check_date,
                'amount' => $check_amount,
                'bank' => $check_bank
            ];
        }
    }
    
    // ==================== پردازش فایل‌ها ====================
    
    $uploaded_files = [];
    $upload_dir = __DIR__ . '/uploads/';
    
    if (empty($errors)) {
        // ایجاد پوشه آپلود
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                $errors[] = 'خطا در ایجاد پوشه آپلود.';
            }
        }
        
        if (file_exists($upload_dir) && is_writable($upload_dir)) {
            // لیست فایل‌های مورد نیاز
            $file_fields = [
                'national_card_image' => 'کارت ملی',
                'birth_certificate_image' => 'شناسنامه',
                'check1_image' => 'چک شماره ۱',
                'check2_image' => 'چک شماره ۲',
                'check3_image' => 'چک شماره ۳'
            ];
            
            foreach ($file_fields as $field => $description) {
                if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                    // بررسی حجم فایل (حداکثر 2MB)
                    if ($_FILES[$field]['size'] > 2 * 1024 * 1024) {
                        $errors[] = "حجم فایل {$description} بیش از ۲ مگابایت است.";
                        continue;
                    }
                    
                    // بررسی پسوند فایل
                    $extension = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
                    
                    if (!in_array($extension, $allowed_extensions)) {
                        $errors[] = "پسوند فایل {$description} مجاز نیست. (jpg, jpeg, png, pdf)";
                        continue;
                    }
                    
                    // ایجاد نام فایل امن
                    $safe_filename = generateSafeFilename($_FILES[$field]['name'], $field);
                    $destination = $upload_dir . $safe_filename;
                    
                    // آپلود فایل
                    if (move_uploaded_file($_FILES[$field]['tmp_name'], $destination)) {
                        $uploaded_files[$field] = $safe_filename;
                        chmod($destination, 0644);
                    } else {
                        $errors[] = "خطا در آپلود فایل {$description}.";
                    }
                } else {
                    // فایل‌های کارت ملی و شناسنامه الزامی هستند
                    if ($field === 'national_card_image' || $field === 'birth_certificate_image') {
                        $errors[] = "فایل {$description} الزامی است.";
                    }
                }
            }
        } else {
            $errors[] = 'پوشه آپلود قابل نوشتن نیست.';
        }
    }
    
    error_log("Errors after validation: " . print_r($errors, true));
    
    // ==================== ذخیره در دیتابیس ====================
    
    $member_id = null;
    $success = false;
    
    if (empty($errors)) {
        try {
            // اتصال به دیتابیس
            $db_host = 'localhost';
            $db_user = 'maskanhb_maskansazan';
            $db_pass = 'NJ)B*$@y3R9P0Oyg';
            $db_name = 'maskanhb_maskansazan';
            
            error_log("Connecting to database: {$db_name}");
            
            $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
            
            if ($conn->connect_error) {
                throw new Exception("خطا در اتصال به دیتابیس: " . $conn->connect_error);
            }
            
            $conn->set_charset('utf8mb4');
            
            // بررسی تکراری نبودن شماره ملی
            $check_sql = "SELECT id FROM members WHERE national_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            if ($check_stmt) {
                $national_id_english = toEnglishNumbers($form_data['national_id']);
                $check_stmt->bind_param("s", $national_id_english);
                $check_stmt->execute();
                $check_stmt->store_result();
                
                if ($check_stmt->num_rows > 0) {
                    throw new Exception('شماره ملی وارد شده قبلاً ثبت شده است.');
                }
                $check_stmt->close();
            }
            
            // ========== ساخت کوئری INSERT بر اساس ساختار جدول ==========
            
            $insert_sql = "INSERT INTO members (
                first_name, last_name, national_id, birth_certificate_number,
                father_name, birth_date_fa, mobile, phone, address,
                postal_code, project_id,
                
                check1_number, check1_date, check1_amount, check1_bank,
                check2_number, check2_date, check2_amount, check2_bank,
                check3_number, check3_date, check3_amount, check3_bank,
                
                national_card_image, birth_certificate_image,
                check1_image, check2_image, check3_image,
                ip_address, status
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?
            )";
            
            error_log("Insert SQL: " . $insert_sql);
            
            $stmt = $conn->prepare($insert_sql);
            if (!$stmt) {
                throw new Exception("خطا در آماده‌سازی کوئری: " . $conn->error);
            }
            
            // ========== تعریف متغیرها برای bind_param ==========
            
            // اطلاعات شخصی
            $first_name = $form_data['first_name'];
            $last_name = $form_data['last_name'];
            $national_id = toEnglishNumbers($form_data['national_id']);
            $birth_certificate_number = toEnglishNumbers($form_data['birth_certificate_number']);
            $father_name = $form_data['father_name'];
            $birth_date_fa = toEnglishNumbers($form_data['birth_date_fa']);
            $mobile = toEnglishNumbers($form_data['mobile']);
            $phone = !empty($form_data['phone']) ? $form_data['phone'] : null;
            $address = $form_data['address'];
            $postal_code = toEnglishNumbers($form_data['postalCode']);
            $project_id = (int)$form_data['project_id_reg'];
            
            // اطلاعات چک‌ها
            $check1_number = isset($check_data['check1']['number']) ? $check_data['check1']['number'] : null;
            $check1_date = isset($check_data['check1']['date']) ? $check_data['check1']['date'] : null;
            $check1_amount = isset($check_data['check1']['amount']) ? (float)toEnglishNumbers($check_data['check1']['amount']) : null;
            $check1_bank = isset($check_data['check1']['bank']) ? $check_data['check1']['bank'] : null;
            
            $check2_number = isset($check_data['check2']['number']) ? $check_data['check2']['number'] : null;
            $check2_date = isset($check_data['check2']['date']) ? $check_data['check2']['date'] : null;
            $check2_amount = isset($check_data['check2']['amount']) ? (float)toEnglishNumbers($check_data['check2']['amount']) : null;
            $check2_bank = isset($check_data['check2']['bank']) ? $check_data['check2']['bank'] : null;
            
            $check3_number = isset($check_data['check3']['number']) ? $check_data['check3']['number'] : null;
            $check3_date = isset($check_data['check3']['date']) ? $check_data['check3']['date'] : null;
            $check3_amount = isset($check_data['check3']['amount']) ? (float)toEnglishNumbers($check_data['check3']['amount']) : null;
            $check3_bank = isset($check_data['check3']['bank']) ? $check_data['check3']['bank'] : null;
            
            // فایل‌ها
            $national_card_image = $uploaded_files['national_card_image'] ?? '';
            $birth_certificate_image = $uploaded_files['birth_certificate_image'] ?? '';
            $check1_image = $uploaded_files['check1_image'] ?? null;
            $check2_image = $uploaded_files['check2_image'] ?? null;
            $check3_image = $uploaded_files['check3_image'] ?? null;
            
            // اطلاعات اضافی
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $status = 'pending';
            
            // ========== bind_param با پارامترهای صحیح ==========
            
            $stmt->bind_param(
                // 11 پارامتر اطلاعات شخصی
                "ssssssssssi" .
                // 12 پارامتر چک‌ها (d برای decimal/float)
                "ssdsssdsssds" .
                // 5 پارامتر فایل‌ها
                "sssss" .
                // 2 پارامتر پایانی
                "ss",
                
                // اطلاعات شخصی (11)
                $first_name,
                $last_name,
                $national_id,
                $birth_certificate_number,
                $father_name,
                $birth_date_fa,
                $mobile,
                $phone,
                $address,
                $postal_code,
                $project_id,
                
                // چک 1 (4)
                $check1_number,
                $check1_date,
                $check1_amount,
                $check1_bank,
                
                // چک 2 (4)
                $check2_number,
                $check2_date,
                $check2_amount,
                $check2_bank,
                
                // چک 3 (4)
                $check3_number,
                $check3_date,
                $check3_amount,
                $check3_bank,
                
                // فایل‌ها (5)
                $national_card_image,
                $birth_certificate_image,
                $check1_image,
                $check2_image,
                $check3_image,
                
                // اطلاعات پایانی (2)
                $ip_address,
                $status
            );
            
            if ($stmt->execute()) {
                $member_id = $stmt->insert_id;
                $success = true;
                error_log("Registration successful. Member ID: {$member_id}");
            } else {
                throw new Exception("خطا در ذخیره اطلاعات: " . $stmt->error);
            }
            
            $stmt->close();
            $conn->close();
            
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
            error_log("Database error: " . $e->getMessage());
            
            // حذف فایل‌های آپلود شده در صورت خطا
            foreach ($uploaded_files as $filename) {
                $file_path = $upload_dir . $filename;
                if (file_exists($file_path)) {
                    @unlink($file_path);
                }
            }
        }
    }
    
} catch (Exception $e) {
    $errors[] = $e->getMessage();
    error_log("General error: " . $e->getMessage());
}

// ==================== نمایش نتیجه ====================

// تاریخ ثبت فعلی برای نمایش
$current_date = date('Y/m/d H:i');
$registration_date_display = toPersianNumbers($current_date);

// تبدیل مبالغ به فرمت فارسی
function formatAmount($amount) {
    if (empty($amount)) return '';
    $amount_english = toEnglishNumbers($amount);
    return toPersianNumbers(number_format($amount_english)) . ' ریال';
}

// تابع تبدیل تاریخ میلادی به شمسی برای نمایش
function miladiToShamsiDisplay($miladi_date) {
    if (empty($miladi_date)) return '';
    // تبدیل ساده برای نمایش
    return toPersianNumbers(date('Y/m/d', strtotime($miladi_date)));
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $success ? 'ثبت نام موفق' : 'خطا در ثبت نام'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Tahoma', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .result-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .result-header {
            padding: 30px;
            text-align: center;
            color: white;
        }
        
        .result-header.success {
            background: linear-gradient(135deg, #27ae60 0%, #219653 100%);
        }
        
        .result-header.error {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
        
        .result-body {
            padding: 30px;
        }
        
        .tracking-code {
            background: #f1c40f;
            color: #2c3e50;
            padding: 20px;
            border-radius: 10px;
            font-size: 24px;
            text-align: center;
            margin: 20px 0;
            font-weight: bold;
            border: 3px dashed #2c3e50;
        }
        
        .info-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-right: 5px solid #3498db;
        }
        
        .info-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .info-list {
            list-style: none;
            padding: 0;
        }
        
        .info-list li {
            padding: 8px 0;
            border-bottom: 1px dashed #ddd;
            display: flex;
            justify-content: space-between;
        }
        
        .info-list li:last-child {
            border-bottom: none;
        }
        
        .label {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .check-card {
            background: #e3f2fd;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-right: 4px solid #2196f3;
        }
        
        .check-title {
            color: #1565c0;
            font-weight: bold;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-custom {
            padding: 12px 30px;
            border-radius: 8px;
            border: none;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .btn-print {
            background: linear-gradient(135deg, #27ae60 0%, #219653 100%);
            color: white;
        }
        
        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        
        .btn-new {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }
        
        .btn-new:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .btn-back {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            color: white;
        }
        
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(149, 165, 166, 0.3);
        }
        
        .error-alert {
            background: #ffeaea;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-right: 5px solid #e74c3c;
        }
        
        .error-title {
            color: #c0392b;
            font-weight: bold;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .error-list {
            list-style: none;
            padding: 0;
        }
        
        .error-list li {
            padding: 10px;
            background: #fff5f5;
            margin: 5px 0;
            border-radius: 5px;
            border-right: 3px solid #e74c3c;
        }
        
        .project-badge {
            background: #9b59b6;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        
        @media print {
            body {
                background: white !important;
            }
            .btn-custom {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="result-card">
        
        <?php if ($success): ?>
            <!-- حالت موفقیت -->
            <div class="result-header success">
                <h1><i class="bi bi-check-circle-fill"></i> ثبت نام با موفقیت انجام شد</h1>
                <p class="mb-0">اطلاعات شما با موفقیت در سیستم ثبت شد</p>
            </div>
            
            <div class="result-body">
                <!-- کد پیگیری -->
                <div class="tracking-code">
                    <i class="bi bi-qr-code"></i>
                    کد پیگیری: 
                    <strong>#<?php echo toPersianNumbers(sprintf('%08d', $member_id)); ?></strong>
                </div>
                
                <!-- اطلاعات شخصی -->
                <div class="info-section">
                    <h3 class="info-title">
                        <i class="bi bi-person-badge"></i> اطلاعات شخصی
                    </h3>
                    <ul class="info-list">
                        <li>
                            <span class="label">نام کامل:</span>
                            <span><?php echo htmlspecialchars($form_data['first_name'] . ' ' . $form_data['last_name']); ?></span>
                        </li>
                        <li>
                            <span class="label">شماره ملی:</span>
                            <span><?php echo htmlspecialchars($form_data['national_id']); ?></span>
                        </li>
                        <li>
                            <span class="label">شماره شناسنامه:</span>
                            <span><?php echo htmlspecialchars($form_data['birth_certificate_number']); ?></span>
                        </li>
                        <li>
                            <span class="label">نام پدر:</span>
                            <span><?php echo htmlspecialchars($form_data['father_name']); ?></span>
                        </li>
                        <li>
                            <span class="label">تاریخ تولد:</span>
                            <span><?php echo htmlspecialchars($form_data['birth_date_fa']); ?></span>
                        </li>
                        <li>
                            <span class="label">شماره همراه:</span>
                            <span><?php echo htmlspecialchars($form_data['mobile']); ?></span>
                        </li>
                        <?php if (!empty($form_data['phone'])): ?>
                        <li>
                            <span class="label">شماره ثابت:</span>
                            <span><?php echo htmlspecialchars($form_data['phone']); ?></span>
                        </li>
                        <?php endif; ?>
                        <li>
                            <span class="label">آدرس:</span>
                            <span><?php echo htmlspecialchars($form_data['address']); ?></span>
                        </li>
                        <li>
                            <span class="label">کد پستی:</span>
                            <span><?php echo htmlspecialchars($form_data['postalCode']); ?></span>
                        </li>
                        <li>
                            <span class="label">پروژه انتخاب شده:</span>
                            <span class="project-badge"><?php echo htmlspecialchars($form_data['project_id_reg']); ?></span>
                        </li>
                    </ul>
                </div>
                
                <!-- اطلاعات چک‌ها -->
                <?php for ($i = 1; $i <= 3; $i++): ?>
                    <?php if (isset($check_data["check{$i}"])): ?>
                        <div class="check-card">
                            <h4 class="check-title">
                                <i class="bi bi-credit-card"></i> چک شماره <?php echo $i; ?>
                            </h4>
                            <ul class="info-list">
                                <li>
                                    <span class="label">شماره چک:</span>
                                    <span><?php echo htmlspecialchars($check_data["check{$i}"]['number']); ?></span>
                                </li>
                                <li>
                                    <span class="label">تاریخ چک (شمسی):</span>
                                    <span><?php echo htmlspecialchars($check_data["check{$i}"]['date']); ?></span>
                                </li>
                                <li>
                                    <span class="label">مبلغ چک:</span>
                                    <span><?php echo formatAmount($check_data["check{$i}"]['amount']); ?></span>
                                </li>
                                <li>
                                    <span class="label">بانک صادرکننده:</span>
                                    <span><?php echo htmlspecialchars($check_data["check{$i}"]['bank']); ?></span>
                                </li>
                            </ul>
                        </div>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <!-- اطلاعات ثبت -->
                <div class="info-section">
                    <h3 class="info-title">
                        <i class="bi bi-info-circle"></i> اطلاعات ثبت
                    </h3>
                    <ul class="info-list">
                        <li>
                            <span class="label">تاریخ و زمان ثبت:</span>
                            <span><?php echo htmlspecialchars($registration_date_display); ?></span>
                        </li>
                        <li>
                            <span class="label">کد پیگیری:</span>
                            <span class="text-primary fw-bold">#<?php echo toPersianNumbers(sprintf('%08d', $member_id)); ?></span>
                        </li>
                        <li>
                            <span class="label">وضعیت:</span>
                            <span class="badge bg-warning text-dark">در انتظار تأیید</span>
                        </li>
                    </ul>
                </div>
                
                <!-- تذکرات مهم -->
                <div class="info-section" style="background: #fff3cd; border-right-color: #f39c12;">
                    <h3 class="info-title" style="color: #856404;">
                        <i class="bi bi-exclamation-triangle"></i> تذکرات مهم
                    </h3>
                    <ul class="info-list">
                        <li>✅ کد پیگیری <strong>#<?php echo toPersianNumbers(sprintf('%08d', $member_id)); ?></strong> را حفظ کنید.</li>
                        <li>✅ اطلاعات شما در انتظار تأیید نهایی می‌باشد.</li>
                        <li>✅ برای پیگیری، کد پیگیری و شماره ملی خود را ارائه دهید.</li>
                        <li>✅ مدارک ارسالی را نزد خود نگهداری کنید.</li>
                        <li>✅ کارشناسان ما به زودی با شما تماس خواهند گرفت.</li>
                    </ul>
                </div>
                
                <!-- دکمه‌های عملیات -->
                <div class="text-center mt-4 pt-3 border-top">
                    <button onclick="window.print()" class="btn-custom btn-print me-2">
                        <i class="bi bi-printer"></i> چاپ رسید
                    </button>
                    <a href="groupmembers.php" class="btn-custom btn-new">
                        <i class="bi bi-plus-circle"></i> ثبت نام جدید
                    </a>
                </div>
            </div>
            
        <?php else: ?>
            <!-- حالت خطا -->
            <div class="result-header error">
                <h1><i class="bi bi-exclamation-triangle-fill"></i> خطا در ثبت نام</h1>
                <p class="mb-0">لطفاً خطاهای زیر را اصلاح کنید</p>
            </div>
            
            <div class="result-body">
                <?php if (!empty($errors)): ?>
                    <div class="error-alert">
                        <h3 class="error-title">
                            <i class="bi bi-x-circle-fill"></i> خطاهای شناسایی شده
                        </h3>
                        <ul class="error-list">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="text-center mt-4">
                    <button onclick="history.back()" class="btn-custom btn-back me-2">
                        <i class="bi bi-arrow-right"></i> بازگشت و اصلاح فرم
                    </button>
                    <a href="groupmembers.php" class="btn-custom btn-new">
                        <i class="bi bi-house-door"></i> شروع مجدد
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if ($success): ?>
        // جلوگیری از ارسال مجدد فرم
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // نمایش پیام موفقیت
        setTimeout(() => {
            alert('✅ ثبت نام موفق!\n\nکد پیگیری: #<?php echo sprintf("%08d", $member_id); ?>\n\nلطفاً این کد را برای پیگیری حفظ کنید.');
        }, 500);
        <?php endif; ?>
    </script>
</body>
</html>
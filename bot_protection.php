<?php
// bot_protection.php
// سیستم تشخیص فعالیت مشکوک

class BotProtection {
    private $mysqli;
    private $score = 0;
    private $threshold = 10; // آستانه برای تشخیص ربات
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    /**
     * بررسی درخواست برای تشخیص فعالیت ربات
     * @param string $username نام کاربری وارد شده
     * @return bool true اگر درخواست معتبر باشد، false اگر مشکوک باشد
     */
    public function validateRequest($username) {
        $this->score = 0;
        
        // 1. بررسی سرعت ارسال فرم
        $this->checkSubmissionSpeed();
        
        // 2. بررسی هدرهای HTTP
        $this->checkHeaders();
        
        // 3. بررسی الگوهای ورودی
        $this->checkInputPatterns($username);
        
        // 4. بررسی هانی‌پات (فیلد مخفی)
        $this->checkHoneypot();
        
        // 5. بررسی IP (اختیاری - اگر جدول login_attempts وجود دارد)
        $this->checkIPHistory();
        
        // لاگ کردن امتیاز برای دیباگ
        error_log("Bot Protection Score: " . $this->score . " for username: " . $username);
        
        // اگر امتیاز از آستانه بیشتر شد، درخواست مشکوک است
        return $this->score < $this->threshold;
    }
    
    /**
     * بررسی سرعت ارسال فرم
     */
    private function checkSubmissionSpeed() {
        if (!isset($_SESSION['form_loaded_at'])) {
            $_SESSION['form_loaded_at'] = microtime(true);
            return;
        }
        
        $load_time = $_SESSION['form_loaded_at'];
        $submit_time = microtime(true);
        $time_taken = $submit_time - $load_time;
        
        // حذف تایمینگ از سشن بعد از استفاده
        unset($_SESSION['form_loaded_at']);
        
        // امتیازدهی بر اساس سرعت
        if ($time_taken < 1.5) {
            $this->score += 5; // خیلی سریع - احتمال ربات بالا
        } elseif ($time_taken < 3) {
            $this->score += 2; // نسبتاً سریع
        } elseif ($time_taken > 30) {
            $this->score += 1; // خیلی آهسته - ممکن است مشکوک باشد
        }
    }
    
    /**
     * بررسی هدرهای HTTP
     */
    private function checkHeaders() {
        // بررسی User-Agent
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            $this->score += 4; // ربات‌ها معمولاً User-Agent ندارند
        } elseif (preg_match('/bot|crawl|spider|scraper|curl|wget/i', $_SERVER['HTTP_USER_AGENT'])) {
            $this->score += 3; // ممکن است ربات باشد
        }
        
        // بررسی Accept-Language
        if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $this->score += 2;
        }
        
        // بررسی Accept
        if (empty($_SERVER['HTTP_ACCEPT'])) {
            $this->score += 2;
        }
    }
    
    /**
     * بررسی الگوهای ورودی
     */
    private function checkInputPatterns($username) {
        // بررسی طول نام کاربری
        $username_len = strlen($username);
        if ($username_len > 100) {
            $this->score += 3; // طول غیرمعمول
        } elseif ($username_len < 2) {
            $this->score += 2; // خیلی کوتاه
        }
        
        // بررسی کاراکترهای غیرمجاز
        if (preg_match('/[\x00-\x1F\x7F]/', $username)) {
            $this->score += 4; // کاراکترهای کنترلی
        }
        
        // بررسی الگوهای تزریق SQL (ساده)
        $sql_patterns = ['union', 'select', 'insert', 'update', 'delete', 'drop', 'create', 'exec', '--', '/*', '*/'];
        foreach ($sql_patterns as $pattern) {
            if (stripos($username, $pattern) !== false) {
                $this->score += 5;
                break;
            }
        }
    }
    
    /**
     * بررسی فیلد هانی‌پات (مخفی)
     */
    private function checkHoneypot() {
        // اگر فیلد 'website' پر شده باشد (فقط ربات‌ها آن را می‌بینند و پر می‌کنند)
        if (!empty($_POST['website']) && trim($_POST['website']) !== '') {
            $this->score += 15; // قطعاً ربات
            error_log("Honeypot triggered: " . $_POST['website']);
        }
    }
    
    /**
     * بررسی تاریخچه IP از جدول login_attempts
     */
    private function checkIPHistory() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        // فقط اگر IP خصوصی نباشد بررسی کن
        if (!$this->isPrivateIP($ip)) {
            $stmt = $this->mysqli->prepare("
                SELECT COUNT(*) as attempts 
                FROM login_attempts 
                WHERE ip = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                AND successful = 0
            ");
            
            if ($stmt) {
                $stmt->bind_param("s", $ip);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $attempts = $row['attempts'] ?? 0;
                $stmt->close();
                
                if ($attempts >= 10) {
                    $this->score += 6;
                } elseif ($attempts >= 5) {
                    $this->score += 3;
                } elseif ($attempts >= 3) {
                    $this->score += 1;
                }
            }
        }
    }
    
    /**
     * بررسی اینکه آیا IP در محدوده IP‌های خصوصی است
     */
    private function isPrivateIP($ip) {
        $private_ranges = [
            '10.0.0.0/8',
            '172.16.0.0/12', 
            '192.168.0.0/16',
            '127.0.0.0/8',
            '169.254.0.0/16'
        ];
        
        foreach ($private_ranges as $range) {
            list($subnet, $mask) = explode('/', $range);
            $subnet = ip2long($subnet);
            $ip_long = ip2long($ip);
            $mask = -1 << (32 - $mask);
            
            if (($ip_long & $mask) == ($subnet & $mask)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * دریافت امتیاز فعلی (برای دیباگ)
     */
    public function getScore() {
        return $this->score;
    }
}
?>
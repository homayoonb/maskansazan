<?php
// admin/bot_protection.php

class BotProtection {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function validateRequest($username) {
        // بررسی سرعت ارسال فرم (زیر 2 ثانیه مشکوک است)
        if (isset($_POST['form_loaded_at'])) {
            $loadTime = floatval($_POST['form_loaded_at']);
            $submitTime = microtime(true);
            $timeDiff = $submitTime - $loadTime;
            
            if ($timeDiff < 2) {
                error_log("Suspicious fast form submission: $timeDiff seconds for user: $username");
                return false;
            }
        }
        
        // بررسی فیلد هانی‌پات
        if (!empty($_POST['website'])) {
            error_log("Honeypot field filled for user: $username");
            return false;
        }
        
        // بررسی تعداد درخواست‌ها از این IP
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $this->mysqli->prepare("SELECT COUNT(*) as count FROM login_attempts WHERE ip = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        if ($stmt) {
            $stmt->bind_param("s", $ip);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            if ($row['count'] > 20) {
                error_log("Too many requests from IP: $ip");
                return false;
            }
        }
        
        return true;
    }
}
?>
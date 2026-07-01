<?php
class OTPManager {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function generateOTP($user_id, $length = 6) {
        $otp = '';
        $characters = '0123456789';

        for ($i = 0; $i < $length; $i++) {
            $otp .= $characters[rand(0, 9)];
        }

        // ذخیره OTP در دیتابیس
        $expires_at = date('Y-m-d H:i:s', time() + OTP_EXPIRE);

        $stmt = $this->db->prepare("
            INSERT INTO user_otps (user_id, otp_code, expires_at, created_at) 
            VALUES (?, ?, ?, NOW())
        ");

        if ($stmt) {
            $stmt->bind_param("iss", $user_id, $otp, $expires_at);
            $stmt->execute();
            $stmt->close();
        }

        return $otp;
    }

    public function sendOTP($phone, $otp) {
        // نرمال سازی شماره تلفن
        $phone = $this->normalizePhone($phone);

        // در حالت تست، OTP را در session ذخیره کن
        $_SESSION['debug_otp'] = "کد تایید: " . $otp;
        $_SESSION['debug_phone'] = $phone;
        $_SESSION['debug_time'] = date('Y-m-d H:i:s');

        error_log("DEBUG - OTP Generated: {$otp} for phone: {$phone}");

        // اگر API_KEY واقعی است، سعی کن SMS بفرستی
        if (SMS_API_KEY !== 'your-sms-api-key' && !empty(SMS_API_KEY)) {
            return $this->sendRealSMS($phone, $otp);
        }

        // در غیر این صورت حالت تست
        return true;
    }

    private function normalizePhone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (substr($phone, 0, 1) === '0') {
            $phone = '98' . substr($phone, 1);
        }

        if (substr($phone, 0, 2) !== '98') {
            $phone = '98' . $phone;
        }

        return $phone;
    }

    private function sendRealSMS($phone, $otp) {
        $message = "کد تایید پروژه مهر: {$otp}\nاین کد 10 دقیقه معتبر است.";

        $url = "https://api.kavenegar.com/v1/" . SMS_API_KEY . "/sms/send.json";

        $data = [
            'receptor' => $phone,
            'message' => $message,
            'sender' => SMS_SENDER
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        error_log("SMS API Response - Code: {$http_code}, Response: {$response}");

        return ($http_code == 200);
    }

    public function verifyOTP($user_id, $otp) {
        // اول OTP دیباگ را بررسی کن
        if (isset($_SESSION['debug_otp'])) {
            $debug_otp = preg_replace('/[^0-9]/', '', $_SESSION['debug_otp']);
            if ($debug_otp === $otp) {
                unset($_SESSION['debug_otp'], $_SESSION['debug_phone'], $_SESSION['debug_time']);
                return true;
            }
        }

        // سپس از دیتابیس بررسی کن
        $stmt = $this->db->prepare("
            SELECT id FROM user_otps 
            WHERE user_id = ? AND otp_code = ? AND expires_at > NOW() AND used = 0
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param("is", $user_id, $otp);
            $stmt->execute();
            $result = $stmt->get_result();
            $otp_record = $result->fetch_assoc();
            $stmt->close();

            if ($otp_record) {
                $stmt = $this->db->prepare("UPDATE user_otps SET used = 1 WHERE id = ?");
                $stmt->bind_param("i", $otp_record['id']);
                $stmt->execute();
                $stmt->close();
                return true;
            }
        }

        return false;
    }
}
?>
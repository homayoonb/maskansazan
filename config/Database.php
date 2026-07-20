<?php
// config/Database.php

class Database {
    private static $connection = null;

    public static function connect() {

        if (self::$connection === null) {

            $DB_HOST = 'localhost';
            $DB_NAME = 'maskanhb_maskansazan';
            $DB_USER = 'root';
            $DB_PASS = '';

            self::$connection = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

            if (self::$connection->connect_errno) {
                die("خطا در اتصال به دیتابیس: " . self::$connection->connect_error);
            }

            self::$connection->set_charset("utf8mb4");
        }

        return self::$connection;
    }


    public static function close() {
        if (self::$connection !== null) {
            self::$connection->close();
            self::$connection = null;
        }
    }
    
    // متد استاتیک برای گرفتن لیست پروژه‌ها
    public static function getProjectsList() {
        $mysqli = self::connect();
        if (!$mysqli) return [];

        $projects = [];
        $result = $mysqli->query("SELECT id, name FROM projects ORDER BY id DESC");

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $projects[] = $row;
            }
        }

        return $projects;
    }
}

// توابع کمکی - فقط اگر از قبل تعریف نشده باشند
if (!function_exists('safeEcho')) {
    function safeEcho($value) {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time']) || time() - $_SESSION['csrf_token_time'] > 3600) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }
}
?>
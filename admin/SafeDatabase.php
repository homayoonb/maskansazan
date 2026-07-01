<?php
// admin/SafeDatabase.php
if (!class_exists('SafeDatabase')) {
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
        
        public static function escape($string) {
            $conn = self::connect();
            return $conn ? $conn->real_escape_string($string) : addslashes($string);
        }
    }
}
?>
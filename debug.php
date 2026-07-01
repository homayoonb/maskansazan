<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
echo "<h2>PHP Debug Mode Active</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Current Directory: " . __DIR__ . "<br><hr>";

// تست فایل‌های مورد نیاز
echo "<h3>Checking Required Files:</h3>";
$files = ['helpers.php', 'bot_protection.php', 'loginAdmin.php'];
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✓ File '$file' exists.<br>";
    } else {
        echo "✗ ERROR: File '$file' NOT FOUND!<br>";
    }
}

echo "<hr><h3>Trying to include loginAdmin.php:</h3>";
try {
    require_once 'loginAdmin.php';
    echo "✅ loginAdmin.php loaded successfully!";
} catch (Throwable $e) {
    echo "<div style='color:red; background:#fee; padding:10px; border:2px solid red;'>";
    echo "<strong>FATAL ERROR:</strong><br>";
    echo "Message: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "</div>";
}
?>
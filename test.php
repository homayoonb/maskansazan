<?php
// test.php
echo "PHP is working!<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

// تست اتصال دیتابیس
$host = 'localhost';
$user = 'maskanhb_maskansazan';
$pass = 'NJ)B*$@y3R9P0Oyg';
$db = 'maskanhb_maskansazan';

echo "<br>Trying to connect to database...<br>";

$link = mysqli_connect($host, $user, $pass, $db);

if ($link) {
    echo "✓ Database connection successful!<br>";
    
    // تست کوئری ساده
    $result = mysqli_query($link, "SELECT 1 as test");
    if ($result) {
        echo "✓ Query execution successful!<br>";
    } else {
        echo "✗ Query failed: " . mysqli_error($link) . "<br>";
    }
    
    mysqli_close($link);
} else {
    echo "✗ Database connection failed!<br>";
    echo "Error: " . mysqli_connect_error() . "<br>";
}

// بررسی وجود فایل‌ها
echo "<br>Checking required files...<br>";
$files = [
    'dashboardAdmin.php',
    'loginAdmin.php',
    'index.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✓ $file exists<br>";
    } else {
        echo "✗ $file not found<br>";
    }
}

// بررسی سشن
echo "<br>Session test...<br>";
session_start();
$_SESSION['test'] = 'working';
if (isset($_SESSION['test'])) {
    echo "✓ Session is working<br>";
} else {
    echo "✗ Session not working<br>";
}

// بررسی memory limit
echo "<br>PHP Settings:<br>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "Max Execution Time: " . ini_get('max_execution_time') . "<br>";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "<br>";
?>
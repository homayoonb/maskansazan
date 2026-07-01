<?php
// check_tables.php
$host = 'localhost';
$db   = 'maskanhb_maskansazan';
$user = 'maskanhb_maskansazan';
$pass = 'NJ)B*$@y3R9P0Oyg';

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die("خطا در اتصال دیتابیس: " . $mysqli->connect_error);
}

echo "<h3>بررسی جداول دیتابیس</h3>";

// بررسی جدول admins
$result = $mysqli->query("SHOW TABLES LIKE 'admins'");
if ($result->num_rows > 0) {
    echo "✅ جدول <strong>admins</strong> وجود دارد.<br>";
    
    // نمایش ساختار جدول
    $structure = $mysqli->query("DESCRIBE admins");
    echo "<h4>ساختار جدول admins:</h4>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // بررسی رکوردهای موجود
    $users = $mysqli->query("SELECT id, username, status FROM admins");
    echo "<h4>کاربران موجود در جدول admins:</h4>";
    if ($users->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Status</th></tr>";
        while($row = $users->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['username'] . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ هیچ کاربری در جدول admins وجود ندارد.<br>";
    }
} else {
    echo "❌ جدول <strong>admins</strong> وجود ندارد!<br>";
}

// بررسی جدول login_attempts
$result = $mysqli->query("SHOW TABLES LIKE 'login_attempts'");
if ($result->num_rows > 0) {
    echo "✅ جدول <strong>login_attempts</strong> وجود دارد.<br>";
} else {
    echo "⚠️ جدول <strong>login_attempts</strong> وجود ندارد (اختیاری).<br>";
}

$mysqli->close();
?>
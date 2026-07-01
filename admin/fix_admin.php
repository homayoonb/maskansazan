<?php
// fix_admin.php
session_start();
$DB_HOST = 'localhost';
$DB_NAME = 'maskanhb_maskansazan';
$DB_USER = 'maskanhb_maskansazan';
$DB_PASS = 'NJ)B*$@y3R9P0Oyg';

echo "<!DOCTYPE html>
<html dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <title>تعمیر سیستم ادمین</title>
    <style>
        body { 
            font-family: Tahoma; 
            padding: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container { 
            background: white; 
            padding: 30px; 
            border-radius: 15px; 
            max-width: 800px; 
            margin: 0 auto; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .success { 
            background: #d4edda; 
            color: #155724; 
            padding: 15px; 
            border-radius: 8px; 
            margin: 15px 0; 
            border: 1px solid #c3e6cb;
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 15px; 
            border-radius: 8px; 
            margin: 15px 0; 
            border: 1px solid #f5c6cb;
        }
        .warning { 
            background: #fff3cd; 
            color: #856404; 
            padding: 15px; 
            border-radius: 8px; 
            margin: 15px 0; 
            border: 1px solid #ffeaa7;
        }
        .info { 
            background: #d1ecf1; 
            color: #0c5460; 
            padding: 15px; 
            border-radius: 8px; 
            margin: 15px 0; 
            border: 1px solid #bee5eb;
        }
        pre { 
            background: #f8f9fa; 
            padding: 15px; 
            border-radius: 8px; 
            border: 1px solid #e9ecef;
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin: 5px;
            border: none;
            cursor: pointer;
        }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0; 
            font-size: 14px;
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 10px; 
            text-align: center; 
        }
        th { 
            background: #f8f9fa; 
            font-weight: bold;
        }
        tr:hover { background: #f5f5f5; }
        .step { 
            background: #e9ecef; 
            padding: 10px; 
            border-radius: 5px; 
            margin: 10px 0; 
            font-weight: bold;
        }
        .code {
            font-family: monospace;
            background: #2b2b2b;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h2 style='color: #333; margin-bottom: 20px;'>🛠️ تعمیر سیستم مدیریت ادمین</h2>";

try {
    // اتصال به دیتابیس
    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    
    if ($mysqli->connect_error) {
        echo "<div class='error'>❌ خطا در اتصال به دیتابیس: " . $mysqli->connect_error . "</div>";
        exit;
    }
    
    echo "<div class='success'>✅ اتصال به دیتابیس موفقیت‌آمیز بود</div>";
    
    // مرحله 1: بررسی دیتابیس
    echo "<div class='step'>مرحله 1: بررسی دیتابیس و جدول‌ها</div>";
    
    $result = $mysqli->query("SELECT DATABASE() as db_name");
    $db_info = $result->fetch_assoc();
    echo "<div class='info'>📊 دیتابیس فعلی: <strong>" . $db_info['db_name'] . "</strong></div>";
    
    // لیست جدول‌ها
    $result = $mysqli->query("SHOW TABLES");
    $tables = [];
    echo "<table>";
    echo "<tr><th>ردیف</th><th>نام جدول</th></tr>";
    $i = 1;
    while($row = $result->fetch_array()) {
        $tables[] = $row[0];
        echo "<tr><td>$i</td><td>" . $row[0] . "</td></tr>";
        $i++;
    }
    echo "</table>";
    
    // مرحله 2: بررسی جدول admins
    echo "<div class='step'>مرحله 2: بررسی جدول admins</div>";
    
    if (in_array('admins', $tables)) {
        echo "<div class='success'>✅ جدول admins وجود دارد</div>";
        
        // بررسی ساختار جدول
        $result = $mysqli->query("DESCRIBE admins");
        echo "<table>";
        echo "<tr><th>فیلد</th><th>نوع</th><th>NULL</th><th>پیش‌فرض</th><th>کلید</th></tr>";
        $has_id = false;
        $has_username = false;
        $has_password = false;
        
        while($col = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $col['Field'] . "</td>";
            echo "<td>" . $col['Type'] . "</td>";
            echo "<td>" . $col['Null'] . "</td>";
            echo "<td>" . ($col['Default'] ?: 'NULL') . "</td>";
            echo "<td>" . $col['Key'] . "</td>";
            echo "</tr>";
            
            if ($col['Field'] == 'id') $has_id = true;
            if ($col['Field'] == 'username') $has_username = true;
            if ($col['Field'] == 'password') $has_password = true;
        }
        echo "</table>";
        
        // بررسی فیلدهای ضروری
        if (!$has_id || !$has_username || !$has_password) {
            echo "<div class='warning'>⚠️ برخی فیلدهای ضروری وجود ندارند</div>";
            
            // ایجاد فیلدهای missing
            if (!$has_id) {
                echo "<div class='info'>➕ در حال افزودن فیلد id...</div>";
                $mysqli->query("ALTER TABLE admins ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST");
            }
            
            if (!$has_username) {
                echo "<div class='info'>➕ در حال افزودن فیلد username...</div>";
                $mysqli->query("ALTER TABLE admins ADD COLUMN username VARCHAR(50) UNIQUE NOT NULL AFTER id");
            }
            
            if (!$has_password) {
                echo "<div class='info'>➕ در حال افزودن فیلد password...</div>";
                $mysqli->query("ALTER TABLE admins ADD COLUMN password VARCHAR(255) NOT NULL AFTER username");
            }
        }
        
        // مرحله 3: بررسی کاربران
        echo "<div class='step'>مرحله 3: بررسی کاربران ادمین</div>";
        
        $result = $mysqli->query("SELECT COUNT(*) as count FROM admins");
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            echo "<div class='success'>✅ " . $row['count'] . " کاربر ادمین وجود دارد</div>";
            
            // نمایش کاربران
            $result = $mysqli->query("SELECT * FROM admins");
            echo "<table>";
            echo "<tr><th>ID</th><th>نام کاربری</th><th>رمز عبور</th><th>نوع رمز</th></tr>";
            
            while($admin = $result->fetch_assoc()) {
                $password = $admin['password'] ?? '';
                $password_type = 'نامشخص';
                
                if (password_verify('test', $password)) {
                    $password_type = 'Hash شده';
                } elseif (strlen($password) < 60) {
                    $password_type = 'Plain Text';
                }
                
                echo "<tr>";
                echo "<td>" . ($admin['id'] ?? '') . "</td>";
                echo "<td>" . ($admin['username'] ?? '') . "</td>";
                echo "<td>" . substr($password, 0, 20) . "...</td>";
                echo "<td>" . $password_type . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<div class='info'>";
            echo "<p><strong>راه‌حل:</strong> اگر نمی‌توانید وارد شوید:</p>";
            echo "<ol>";
            echo "<li>رمز عبور یکی از کاربران را با دستور زیر تغییر دهید:</li>";
            echo "</ol>";
            echo "<div class='code'>UPDATE admins SET password = '" . password_hash('admin123', PASSWORD_DEFAULT) . "' WHERE username = 'admin';</div>";
            echo "</div>";
            
        } else {
            echo "<div class='warning'>⚠️ هیچ کاربر ادمینی وجود ندارد</div>";
            
            // ایجاد کاربر پیش‌فرض
            echo "<div class='info'>➕ در حال ایجاد کاربر ادمین پیش‌فرض...</div>";
            
            $username = 'admin';
            $password = password_hash('admin123', PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO admins (username, password) VALUES (?, ?)";
            $stmt = $mysqli->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("ss", $username, $password);
                if ($stmt->execute()) {
                    echo "<div class='success'>✅ کاربر ادمین ایجاد شد</div>";
                    echo "<div class='info'>";
                    echo "<p><strong>اطلاعات ورود:</strong></p>";
                    echo "<p>نام کاربری: <strong>admin</strong></p>";
                    echo "<p>رمز عبور: <strong>admin123</strong></p>";
                    echo "</div>";
                }
                $stmt->close();
            }
        }
        
    } else {
        echo "<div class='error'>❌ جدول admins وجود ندارد</div>";
        
        // ایجاد جدول admins
        echo "<div class='step'>مرحله 2: ایجاد جدول admins</div>";
        
        $sql = "CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci";
        
        if ($mysqli->query($sql)) {
            echo "<div class='success'>✅ جدول admins ایجاد شد</div>";
            
            // ایجاد کاربر پیش‌فرض
            $username = 'admin';
            $password = password_hash('admin123', PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO admins (username, password) VALUES (?, ?)";
            $stmt = $mysqli->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("ss", $username, $password);
                if ($stmt->execute()) {
                    echo "<div class='success'>✅ کاربر ادمین پیش‌فرض ایجاد شد</div>";
                    echo "<div class='info'>";
                    echo "<p><strong>اطلاعات ورود:</strong></p>";
                    echo "<p>نام کاربری: <strong>admin</strong></p>";
                    echo "<p>رمز عبور: <strong>admin123</strong></p>";
                    echo "<p><strong>⚠️ توجه:</strong> بعد از ورود، حتماً رمز عبور را تغییر دهید</p>";
                    echo "</div>";
                }
                $stmt->close();
            }
        } else {
            echo "<div class='error'>❌ خطا در ایجاد جدول: " . $mysqli->error . "</div>";
        }
    }
    
    // مرحله 4: تست کوئری لاگین
    echo "<div class='step'>مرحله 4: تست سیستم ورود</div>";
    
    $test_sql = "SELECT id, username, password FROM admins WHERE username = 'admin' LIMIT 1";
    $result = $mysqli->query($test_sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<div class='success'>✅ کوئری ورود تست شد و کار می‌کند</div>";
        $admin = $result->fetch_assoc();
        echo "<div class='info'>";
        echo "کاربر تست: " . $admin['username'];
        echo "</div>";
    } else {
        echo "<div class='warning'>⚠️ کوئری ورود کاربری برنگرداند</div>";
    }
    
    // مرحله 5: دستورات SQL مفید
    echo "<div class='step'>مرحله 5: دستورات SQL مفید</div>";
    
    echo "<div class='info'>";
    echo "<p><strong>برای رفع مشکلات احتمالی:</strong></p>";
    
    echo "<div class='code'>";
    echo "-- مشاهده ساختار جدول\n";
    echo "DESCRIBE admins;\n\n";
    
    echo "-- مشاهده کاربران\n";
    echo "SELECT id, username, LEFT(password, 20) as pass_preview FROM admins;\n\n";
    
    echo "-- تغییر رمز یک کاربر\n";
    echo "UPDATE admins SET password = '" . password_hash('رمزجدید', PASSWORD_DEFAULT) . "' WHERE username = 'admin';\n\n";
    
    echo "-- ایجاد کاربر جدید\n";
    echo "INSERT INTO admins (username, password) VALUES ('newadmin', '" . password_hash('رمزجدید', PASSWORD_DEFAULT) . "');";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='success' style='margin-top: 30px; padding: 20px; text-align: center;'>";
    echo "<h3>🎉 عملیات تکمیل شد!</h3>";
    echo "<p>سیستم آماده استفاده است. برای ورود روی لینک زیر کلیک کنید:</p>";
    echo "<p><a href='loginAdmin.php' class='btn btn-success' style='font-size: 18px; padding: 12px 30px;'>ورود به پنل مدیریت</a></p>";
    echo "</div>";
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "<div class='error'>خطا: " . $e->getMessage() . "</div>";
}

echo "</div>
<script>
    // افزودن دکمه‌های عملیاتی
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('.container');
        
        const btnRefresh = document.createElement('button');
        btnRefresh.className = 'btn';
        btnRefresh.innerHTML = '🔄 به روزرسانی صفحه';
        btnRefresh.onclick = function() { location.reload(); };
        
        const btnBack = document.createElement('a');
        btnBack.className = 'btn btn-danger';
        btnBack.href = 'loginAdmin.php';
        btnBack.innerHTML = '🔙 بازگشت به صفحه ورود';
        
        const actionDiv = document.createElement('div');
        actionDiv.style.marginTop = '20px';
        actionDiv.style.textAlign = 'center';
        actionDiv.appendChild(btnRefresh);
        actionDiv.appendChild(btnBack);
        
        container.appendChild(actionDiv);
    });
</script>
</body>
</html>";
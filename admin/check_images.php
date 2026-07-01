<?php
// fix_all_image_paths.php
session_start();
if (empty($_SESSION['admin_id'])) die("دسترسی غیرمجاز");

$mysqli = new mysqli('localhost', 'maskanhb_maskansazan', 'NJ)B*$@y3R9P0Oyg', 'maskanhb_maskansazan');

// مسیر اصلی که فایل‌ها در آن قرار دارند (بر اساس جستجو مشخص کنید)
$basePath = '/uploads/'; // این را با مسیر واقعی عوض کنید

$result = $mysqli->query("SELECT id, national_card_image, birth_certificate_image, check1_image, check2_image, check3_image FROM members");

$updatedCount = 0;

while ($row = $result->fetch_assoc()) {
    $updates = [];
    $id = $row['id'];
    
    // بررسی هر فیلد تصویر
    $imageFields = [
        'national_card_image',
        'birth_certificate_image',
        'check1_image', 
        'check2_image',
        'check3_image'
    ];
    
    foreach ($imageFields as $field) {
        $filename = $row[$field];
        if (!empty($filename) && strpos($filename, '/') === false) {
            // اگر فقط نام فایل است و مسیر ندارد
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . $basePath . $filename;
            
            if (file_exists($fullPath)) {
                $newPath = $basePath . $filename;
                $updates[$field] = $newPath;
            }
        }
    }
    
    // اگر آپدیتی لازم است
    if (!empty($updates)) {
        $setParts = [];
        $params = [];
        $types = '';
        
        foreach ($updates as $field => $value) {
            $setParts[] = "$field = ?";
            $params[] = $value;
            $types .= 's';
        }
        
        $params[] = $id;
        $types .= 'i';
        
        $sql = "UPDATE members SET " . implode(', ', $setParts) . " WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $updatedCount++;
            echo "<div style='color:green'>✓ عضو #$id: " . count($updates) . " فیلد آپدیت شد</div>";
        }
        
        $stmt->close();
    }
}

echo "<h3>خلاصه:</h3>";
echo "<div>تعداد اعضای آپدیت شده: $updatedCount</div>";

$mysqli->close();
?>
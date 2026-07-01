<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die('دسترسی غیرمجاز');
}

$db = new mysqli('localhost', 'maskanhb_maskansazan', 'NJ)B*$@y3R9P0Oyg', 'maskanhb_maskansazan');
$result = $db->query("SELECT id, file_path FROM user_documents");

echo "<h3>بررسی فایل‌ها</h3>";
echo "<table border='1'><tr><th>ID</th><th>Path in DB</th><th>Exists</th></tr>";

while($row = $result->fetch_assoc()) {
    $exists = file_exists($_SERVER['DOCUMENT_ROOT'] . $row['file_path']) ? '✅' : '❌';
    echo "<tr>
        <td>{$row['id']}</td>
        <td>{$row['file_path']}</td>
        <td>$exists</td>
    </tr>";
}

echo "</table>";
$db->close();
?>
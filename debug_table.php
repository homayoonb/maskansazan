<?php
// debug_table.php
$mysqli = new mysqli('localhost', 'maskanhb_maskansazan', 'NJ)B*$@y3R9P0Oyg', 'maskanhb_maskansazan');

$result = $mysqli->query("SHOW CREATE TABLE user_documents");
$row = $result->fetch_assoc();

echo "<h2>ساختار جدول user_documents</h2>";
echo "<pre>" . htmlspecialchars($row['Create Table']) . "</pre>";

// همچنین فیلدهای اجباری را مشخص می‌کنیم
$result2 = $mysqli->query("DESCRIBE user_documents");
echo "<h2>لیست فیلدها:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($field = $result2->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $field['Field'] . "</td>";
    echo "<td>" . $field['Type'] . "</td>";
    echo "<td>" . $field['Null'] . "</td>";
    echo "<td>" . $field['Key'] . "</td>";
    echo "<td>" . ($field['Default'] ?? 'NULL') . "</td>";
    echo "<td>" . $field['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

$mysqli->close();
?>
<?php
// check_table_structure.php
$host = 'localhost';
$user = 'maskanhb_maskansazan';
$pass = 'NJ)B*$@y3R9P0Oyg';
$db = 'maskanhb_maskansazan';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>ساختار جدول members:</h2>";
$result = $conn->query("DESCRIBE members");

if ($result->num_rows > 0) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
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
} else {
    echo "جدول members وجود ندارد.";
}

$conn->close();
?>
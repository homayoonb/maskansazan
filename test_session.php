<?php
// test_session.php
session_start();

echo "<h3>بررسی Session</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";
echo "Session Name: " . session_name() . "<br>";

echo "<h4>محتوای Session:</h4>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h4>کوکی‌ها:</h4>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<h4>سرآیندها:</h4>";
foreach (headers_list() as $header) {
    echo htmlspecialchars($header) . "<br>";
}
?>
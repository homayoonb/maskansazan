<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// پاک کردن سشن
$_SESSION = [];
session_destroy();

// پاک کردن کوکی remember
setcookie('remember_token', '', time() - 3600, '/');

header('Location: /maskansazan/index.php');
exit;
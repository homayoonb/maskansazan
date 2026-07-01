<?php
// config_session.php
ini_set('session.save_path', __DIR__ . '/sessions');
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_lifetime', 0);
ini_set('session.cookie_secure', false);
ini_set('session.cookie_httponly', true);
ini_set('session.use_strict_mode', true);
ini_set('session.use_only_cookies', 1);

// نام session مشترک
session_name('MASKAN_SESSION');

// ایجاد پوشه sessions اگر وجود ندارد
if (!file_exists(__DIR__ . '/sessions')) {
    mkdir(__DIR__ . '/sessions', 0755, true);
}
?>
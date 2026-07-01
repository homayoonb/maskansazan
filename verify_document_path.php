<?php
// verify_document_path.php
error_reporting(0); // غیرفعال کردن نمایش خطاها
ini_set('display_errors', 0);

// تنظیم هدر JSON قبل از هر خروجی
header('Content-Type: application/json; charset=utf-8');

// جلوگیری از خروجی اضافی
ob_start();

try {
    // بررسی متد درخواست
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed', 405);
    }

    // دریافت و اعتبارسنجی داده‌های ورودی
    $input = file_get_contents('php://input');
    if (empty($input)) {
        throw new Exception('No input data', 400);
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON format', 400);
    }

    $docId = $data['doc_id'] ?? 0;
    $filePath = $data['file_path'] ?? '';
    $csrfToken = $data['csrf_token'] ?? '';

    // اعتبارسنجی CSRF
    session_start();
    if (empty($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
        throw new Exception('Invalid CSRF token', 403);
    }

    // اعتبارسنجی ورودی‌ها
    if ($docId <= 0) {
        throw new Exception('Invalid document ID', 400);
    }

    // توابع کمکی
    function searchFileEverywhere($fileName) {
        $foundPaths = [];
        $searchDirectories = [
            $_SERVER['DOCUMENT_ROOT'] . '/',
            $_SERVER['DOCUMENT_ROOT'] . '/uploads/',
            $_SERVER['DOCUMENT_ROOT'] . '/uploads/documents/',
            $_SERVER['DOCUMENT_ROOT'] . '/documents/',
            dirname($_SERVER['DOCUMENT_ROOT']) . '/',
        ];

        foreach ($searchDirectories as $directory) {
            if (!is_dir($directory)) continue;

            try {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getFilename() === $fileName) {
                        $foundPaths[] = str_replace($_SERVER['DOCUMENT_ROOT'], '', $file->getPathname());
                    }
                }
            } catch (Exception $e) {
                // ignore permission errors
            }
        }

        return $foundPaths;
    }

    function checkCommonPaths($fileName) {
        $commonPaths = [
            '/uploads/documents/' . $fileName,
            '/uploads/' . $fileName,
            '/documents/' . $fileName,
            '/' . $fileName,
            '../uploads/documents/' . $fileName,
            '../uploads/' . $fileName,
        ];

        foreach ($commonPaths as $path) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . $path;
            if (file_exists($fullPath)) {
                return $path;
            }
        }

        return null;
    }

    // بررسی مسیر اصلی
    $result = [
        'success' => true,
        'doc_id' => $docId,
        'original_path' => $filePath,
        'found' => false,
        'new_path' => '',
        'suggestions' => [],
        'searched_at' => date('Y-m-d H:i:s'),
        'search_duration' => 0
    ];

    $startTime = microtime(true);

    // بررسی مسیر فعلی
    if (!empty($filePath)) {
        // استانداردسازی مسیر
        $normalizedPath = $filePath;
        if (strpos($normalizedPath, '/') !== 0) {
            $normalizedPath = '/' . $normalizedPath;
        }

        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $normalizedPath;

        if (file_exists($fullPath)) {
            $result['found'] = true;
            $result['new_path'] = $normalizedPath;
            $result['suggestions'][] = [
                'type' => 'path_valid',
                'message' => 'مسیر فعلی معتبر است'
            ];
        }
    }

    // اگر در مسیر اصلی پیدا نشد، در مسیرهای رایج جستجو کن
    if (!$result['found'] && !empty($fileName = basename($filePath))) {
        $commonPath = checkCommonPaths($fileName);

        if ($commonPath) {
            $result['found'] = true;
            $result['new_path'] = $commonPath;
            $result['suggestions'][] = [
                'type' => 'found_in_common_path',
                'message' => 'فایل در مسیر رایج یافت شد',
                'common_path' => $commonPath
            ];
        }
    }

    // اگر هنوز پیدا نشد، جستجوی کامل انجام بده
    if (!$result['found'] && !empty($fileName)) {
        $foundPaths = searchFileEverywhere($fileName);

        if (!empty($foundPaths)) {
            $result['found'] = true;
            $result['new_path'] = $foundPaths[0];
            $result['suggestions'][] = [
                'type' => 'found_in_deep_search',
                'message' => 'فایل پس از جستجوی کامل یافت شد',
                'total_found' => count($foundPaths),
                'all_paths' => $foundPaths
            ];
        } else {
            $result['suggestions'][] = [
                'type' => 'not_found_anywhere',
                'message' => 'فایل در هیچ یک از مسیرهای سیستم یافت نشد'
            ];
        }
    }

    $result['search_duration'] = round(microtime(true) - $startTime, 3);

    // اگر فایل پیدا شد و ID سند معتبر است، پیشنهاد بروزرسانی بده
    if ($result['found'] && $result['new_path'] !== $filePath && $docId > 0) {
        $result['suggestions'][] = [
            'type' => 'update_recommended',
            'message' => 'پیشنهاد بروزرسانی مسیر در دیتابیس',
            'current_path' => $filePath,
            'recommended_path' => $result['new_path']
        ];
    }

    // پاک کردن بافر خروجی
    ob_end_clean();

    // ارسال پاسخ JSON
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // پاک کردن بافر خروجی در صورت خطا
    ob_end_clean();

    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $e->getCode()
    ], JSON_UNESCAPED_UNICODE);
}

exit;
?>
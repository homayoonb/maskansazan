<?php
// در هر جایی از صفحه (برای تست)
if (isset($_GET['test_images']) && $activeTab === 'documents' && isset($_GET['user_id'])) {
    echo '<div class="alert alert-warning">
            <h5>تست مستقیم تصاویر</h5>';
    
    if (!empty($documentsArray)) {
        foreach ($documentsArray as $index => $doc) {
            $filePath = $doc['document_path'] ?? '';
            $filename = basename($filePath);
            
            echo "<p><strong>مدرک {$index}:</strong> {$doc['document_type']}</p>";
            echo "<p>مسیر: {$filePath}</p>";
            echo "<p>فایل: {$filename}</p>";
            
            // تست چندین URL
            $baseUrl = 'https://maskan-hbncc.com';
            $testUrls = [
                "{$baseUrl}/uploads/{$filename}",
                "{$baseUrl}/uploads/documents/{$filename}",
                "{$baseUrl}/admin/uploads/{$filename}"
            ];
            
            foreach ($testUrls as $testUrl) {
                echo "<p>";
                echo "<a href='{$testUrl}' target='_blank'>{$testUrl}</a>";
                echo " <button onclick=\"testUrl('{$testUrl}')\" class='btn btn-xs btn-info'>تست</button>";
                echo "</p>";
            }
            
            echo "<hr>";
        }
    }
    
    echo '</div>';
}
?>

<script>
function testUrl(url) {
    const img = new Image();
    img.onload = function() {
        alert('✅ تصویر بارگذاری شد\n' + url);
        window.open(url, '_blank');
    };
    img.onerror = function() {
        alert('❌ خطا در بارگذاری\n' + url);
    };
    img.src = url;
}
</script>
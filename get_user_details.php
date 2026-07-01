<?php
session_start();
include 'config/Database.php';

if(isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);

    $database = new Database();
    $db = $database->connect();

    $stmt = $db->prepare("
        SELECT * FROM project_members 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        echo '
        <div class="user-details">
            <div class="row">
                <div class="col-md-6">
                    <div class="detail-item">
                        <span class="detail-label">کد ملی:</span>
                        <span class="detail-value">'.$user['national_code'].'</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">نام کامل:</span>
                        <span class="detail-value">'.$user['full_name'].'</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">شماره شناسنامه:</span>
                        <span class="detail-value">'.$user['birth_certificate'].'</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="detail-item">
                        <span class="detail-label">شماره تلفن:</span>
                        <span class="detail-value">'.$user['phone'].'</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">نام پروژه:</span>
                        <span class="detail-value">'.$user['project_name'].'</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">تاریخ ثبت نام:</span>
                        <span class="detail-value">'.($user['created_at'] ?? 'ثبت نشده').'</span>
                    </div>
                </div>
            </div>';

        // اگر فیلدهای اضافی دارید
        if(isset($user['email']) && !empty($user['email'])) {
            echo '
            <div class="row mt-3">
                <div class="col-12">
                    <div class="detail-item">
                        <span class="detail-label">ایمیل:</span>
                        <span class="detail-value">'.$user['email'].'</span>
                    </div>
                </div>
            </div>';
        }

        echo '</div>';

    } else {
        echo '
        <div class="alert alert-warning text-center">
            <i class="fas fa-exclamation-triangle"></i>
            کاربر مورد نظر یافت نشد
        </div>';
    }
} else {
    echo '
    <div class="alert alert-danger text-center">
        <i class="fas fa-exclamation-circle"></i>
        پارامترهای لازم ارسال نشده است
    </div>';
}
?>
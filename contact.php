<?php
$title = 'ارتباط با ما';
$baseUrl = '/maskansazan';

// شروع ذخیره‌سازی محتوا در حافظه موقت
ob_start();

// اگر اسلایدر دارید، اینجا اینکلوود کنید تا داخل main-content بیاید
// include 'partials/slider.php';
?>

<!-- استایل‌های اختصاصی صفحه تماس با ما -->
<style>
    .contact-card-wrapper {
        width: 100%;
        display: flex;
        justify-content: center;
        padding: 20px 0 50px;
    }

    .contact-card {
        width: 100%;
        max-width: 650px;
        background: #ffffff;
        border-radius: 20px;
        padding: 35px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.05);
    }

    .info-group {
        background: #fff;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.03);
        margin-bottom: 20px;
        border: 1px solid #edf2f7;
    }

    .info-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 17px;
        font-weight: 700;
        color: #2563eb;
        margin-bottom: 15px;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px dashed #e2e8f0;
    }

    .info-item:last-child { border-bottom: none; }

    .phone {
        font-weight: 700;
        color: #1e293b;
        text-decoration: none;
        direction: ltr;
    }

    .address-box {
        background: #f8fafc;
        padding: 15px;
        border-right: 4px solid #2563eb;
        border-radius: 8px;
        line-height: 1.8;
    }

    .email { font-family: sans-serif; direction: ltr; text-align: left; display: block; margin: 5px 0; }
</style>


<table class="table table-bordered table-borderless" style="text-align: center">
    <thead>
    <tr>
        <th>هیأت امنا</th>
        <th>شماره‌های تماس</th>
        <th>پست الکترونیک</th>
        <th>آدرس دفتر</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>ابوالفضل بلندیان کاشی</td>
        <td>09126028115</td>
        <td>bolandian@gmail.com</td>
        <td rowspan="2" class="align-middle text-center"> کرج، شهرک وحدت، بلوار بخشی، نسترن غربی، ساختمان پلاریس، طبقه همکف</td>
    </tr>
    <tr>
        <td>هاشم حاجی قاسمی</td>
        <td>09125102150</td>
        <td>H.Hagighasemi@yahoo.com</td>
    </tr>
    </tbody>
</table>
<?php
// تمام محتوای بالا در متغیر content ریخته می‌شود
$content = ob_get_clean();

// حالا لایوت را صدا می‌زنیم تا content را در دل خود نمایش دهد
include 'partials/layout.php';
?>
<!--<div class="contact-card-wrapper">-->
<!--    <div class="contact-card">-->
<!---->
<!--        <!-- هیأت امنا -->-->
<!--        <div class="info-group">-->
<!--            <div class="info-title">-->
<!--                <i class="fa-solid fa-users"></i> هیأت امنا-->
<!--            </div>-->
<!--            <p class="mb-1 text-secondary">ابوالفضل بلندیان</p>-->
<!--            <p class="mb-0 text-secondary">هاشم حاجی قاسمی</p>-->
<!--        </div>-->
<!---->
<!--        <!-- تلفن‌ها -->-->
<!--        <div class="info-group">-->
<!--            <div class="info-title">-->
<!--                <i class="fa-solid fa-phone"></i> شماره‌های تماس-->
<!--            </div>-->
<!--            <div class="info-item">-->
<!--                <span class="person">ابوالفضل بلندیان</span>-->
<!--                <a href="tel:09126028115" class="phone">۰۹۱۲۶۰۲۸۱۱۵</a>-->
<!--            </div>-->
<!--            <div class="info-item">-->
<!--                <span class="person">هاشم حاجی قاسمی</span>-->
<!--                <a href="tel:09125102151" class="phone">۰۹۱۲۵۱۰۲۱۵۱</a>-->
<!--            </div>-->
<!--        </div>-->
<!---->
<!--        <!-- ایمیل -->-->
<!--        <div class="info-group">-->
<!--            <div class="info-title">-->
<!--                <i class="fa-solid fa-envelope"></i> پست الکترونیک-->
<!--            </div>-->
<!--            <span class="email">bolandian@gmail.com</span>-->
<!--            <span class="email">H.Hagighasemi@yahoo.com</span>-->
<!--        </div>-->
<!---->
<!--        <!-- آدرس -->-->
<!--        <div class="info-group">-->
<!--            <div class="info-title">-->
<!--                <i class="fa-solid fa-location-dot"></i> آدرس دفتر-->
<!--            </div>-->
<!--            <div class="address-box">-->
<!--                کرج، شهرک وحدت، بلوار بخشی، نسترن غربی، ساختمان پلاریس، طبقه همکف-->
<!--            </div>-->
<!--        </div>-->
<!--    </div>-->
<!--</div>-->
<?php
// این خطوط (به جز session_start) مربوط به مدیریت وضعیت در PHP هستند
// و معادل مستقیم سینتکس Razor در ASP.NET نیستند، اما برای PHP لازمند.
// session_start();

include "partials/layout.php";
$page_title = "اهداف - تاریخچه گروه";
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>

    <style>
        /* کانتینر اصلی */
        .history-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        /* عنوان صفحه */
        .page-title {
            color: #2c3e50;
            font-size: 28px;
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #3498db;
        }

        /* محتوای تاریخچه */
        .history-content {
            text-align: justify;
            font-size: 16px;
        }

        .history-content p {
            margin-bottom: 20px;
        }

        /* هایلایت موارد مهم */
        .highlight {
            background-color: rgba(52, 152, 219, 0.2);
            padding: 2px 5px;
            border-radius: 4px;
            font-weight: bold;
            display: inline; /* اطمینان از اینکه فقط متن را هایلایت کند */
        }

        /* لیست پروژه‌ها */
        .projects-list {
            margin: 20px 0;
            padding-right: 20px;
            list-style-type: disc; /* اضافه کردن یک دیسک برای وضوح بیشتر */
        }

        .projects-list li {
            margin-bottom: 10px;
            position: relative;
        }

        /* بخش سرپرستان */
        .managers {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 25px;
            border-right: 3px solid #3498db;
        }

        /* واکنش‌گرایی */
        @media (max-width: 768px) {
            .history-container {
                margin: 10px;
                padding: 15px;
                border-radius: 0;
            }

            .page-title {
                font-size: 22px;
            }

            .history-content {
                font-size: 15px;
            }
        }
    </style>
</head>
<body>

<div class="history-container">
    <h1 class="page-title">تاریخچه</h1>

    <div class="history-content">
        <p>
            هدایت این گروه را <span class="highlight">هیات امنای سه نفره</span> که توسط اعضا انتخاب شده بودند، بر عهده داشتند. این گروه سابقه اجرای پروژه‌های متعددی در کارنامه کاری خود دارد:
        </p>

        <ul class="projects-list">
            <li>پروژه مسکونی 35 واحدی در گرمدره</li>
            <li>پروژه چهل واحدی شهرک نگارستان</li>
            <li>دو پروژه در حال اجرا</li>
        </ul>

        <div class="managers">
            <p>
                در حال حاضر این گروه با سرپرستی <span class="highlight">آقایان هاشم حاجی قاسمی و ابوالفضل بلندیان</span> به فعالیت خود ادامه می‌دهد.
            </p>
        </div>
    </div>
</div>

</body>
</html>
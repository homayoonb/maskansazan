
<?php
ob_start();

?>

<!-- اسلایدر -->
<style>
    .page-text-content {
        max-width: 950px;
        margin: 0 auto;
        padding: 35px 25px;
        direction: rtl;
        text-align: right;
    }

    .page-text-content p {
        height: auto !important;
        min-height: auto !important;
        max-height: none !important;
        overflow: visible !important;

        font-size: 16px;
        line-height: 2.2;
        color: #333;
        margin-bottom: 20px;

        white-space: normal;
        word-break: normal;
        overflow-wrap: break-word;
        text-align: justify;
    }

    .page-text-content .h3 {
        font-size: 26px;
        font-weight: 700;
        color: #222;
        margin-top: 28px;
        margin-bottom: 16px;
    }

    @media (max-width: 768px) {
        .page-text-content {
            width: 100%;
            max-width: 100%;
            padding: 20px 16px;
        }

        .page-text-content p {
            font-size: 15px;
            line-height: 2;
            text-align: justify;
            margin-bottom: 18px;
        }

        .page-text-content .h3 {
            font-size: 21px;
            line-height: 1.6;
            margin-top: 22px;
            margin-bottom: 12px;
        }
    }

    @media (max-width: 480px) {
        .page-text-content {
            padding: 18px 14px;
        }

        .page-text-content p {
            font-size: 14.5px;
            line-height: 2;
        }

        .page-text-content .h3 {
            font-size: 19px;
        }
    }



</style>

<div class="container-fluid page-text-content">

    <div class="h3 mb-3">
        سابقه فعالیت
    </div>

    <p>
        این گروه از سال 1392 با هدف تولید مسکن برای کارکنان سازمان نقشه برداری کشور فعالیت خود را آغاز کرد. هیات امنا سه نفره هدایت این گروه را بعهده گرفت و در گام اول، احداث اولین پروژه 35 واحدی با نام نقشه‌برداری ۱ را در گرمدره کرج آغاز کرد. با افزایش تقاضا، با فاصله زمانی کمتر از یک سال، زمین دومین پروژه نیز در مجاورت پروژه اول در محدوده گرمدره خریداری و با نام نقشه برداری 2 اجرای آن شروع شد.
    </p>

    <p>
        از سال 1392 تاکنون این فعالیت بطور متناوب ادامه یافته و این گروه تاکنون بیش از 305 واحد مسکونی در قالب ۶ پروژه ساختمانی احداث و با سند تفکیکی به اعضا تحویل نموده است.
    </p>

    <div class="h3 mb-3">
        دیدگاه‌ها و اصول بنیادی گروه
    </div>

    <p>
        حقوق اعضا: در همه حال حق با اعضاء بوده و این گروه پس از گزارش دهی، ارائه مشاوره تخصصی و در ادامه تقدیم راهکار، به رای اعضاء احترام گذاشته و فقط مجری می باشد.
    </p>

    <p>
        شفافیت اسناد: شفاف سازی در مستندات و اسناد حق مسلم اعضاء بوده همواره باید انجام گیرد و به لحاظ حقوقی، ثبت سند به نام افراد اصل ابتدایی می باشد.
    </p>

    <p>
        کیفیت و اصول: اگرچه هدف اصلی سود رسانی به نفع اعضاء می باشد، لیکن همواره اصول و ضوابط و قوانین و مقررات ساختمان‌سازی مطابق استانداردها و دستورالعملهای نظام مهندسی انجام شده و کیفیت در الویت است.
    </p>

</div>

<?php
$content = ob_get_clean();
$title = "گروه مسکن سازان";
include 'partials/layout.php';
include 'partials/slider.php';
?>

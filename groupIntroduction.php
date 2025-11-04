<?php
include 'partials/layout.php';
// ۱. آرایه داده‌ها: تعریف اصول، عنوان، متن و آیکون‌ها
$principles = [
    [
        'icon' => 'fas fa-users',
        'title' => 'حقوق اعضا',
        'text' => 'در همه حال حق با اعضاء بوده و این گروه پس از گزارش دهی، ارائه مشاوره تخصصی و در ادامه تقدیم راهکار به رای اعضاء احترام گذاشته و فقط مجری می باشد.',
    ],
    [
        'icon' => 'fas fa-file-contract',
        'title' => 'شفافیت اسناد',
        'text' => 'شفاف سازی در مستندات و اسناد حق مسلم اعضاء بوده همواره باید انجام گیرد و به لحاظ حقوقی، ثبت سند به نام افراد اصل ابتدایی می باشد.',
    ],
    [
        'icon' => 'fas fa-medal',
        'title' => 'کیفیت و اصول',
        'text' => 'اگرچه هدف اصلی سود رسانی به نفع اعضاء می باشد لیکن همواره اصول و ضوابط و قوانین و مقررات ساختمان سازی مطابق نظام مهندسی انجام و کیفیت کار در الویت می باشد.',
    ],
    // اگر اصول بیشتری دارید، می‌توانید به همین ترتیب اینجا اضافه کنید
];

// ۲. تولید محتوای HTML با استفاده از حلقه PHP
?>

<section class="principles-section bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-5 section-title">دیدگاه‌های بنیادی و اصولی گروه</h2>

        <div class="row">

            <?php
            // شروع حلقه برای تولید کارت‌ها
            foreach ($principles as $principle) {
                // htmlspecialchars برای جلوگیری از مشکلات امنیتی در نمایش متن
                $icon = htmlspecialchars($principle['icon']);
                $title = htmlspecialchars($principle['title']);
                $text = htmlspecialchars($principle['text']);
                ?>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card principle-card h-100">
                        <div class="card-body text-center">
                            <div class="principle-icon">
                                <i class="<?php echo $icon; ?>"></i>
                            </div>
                            <h4 class="card-title"><?php echo $title; ?></h4>
                            <p class="card-text">
                                <?php echo $text; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <?php
            } // پایان حلقه foreach
            ?>

        </div>
    </div>
</section>
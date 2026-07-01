<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// اگر جلسه فعال نیست، شروع شود
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// عنوان صفحه
$title = "اصول و دیدگاه‌ها";

// آرایه داده‌ها: تعریف اصول، عنوان، متن و آیکون‌ها
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
    // اصول بیشتر می‌توانید اضافه کنید
];

// محتوای HTML به صورت داینامیک
ob_start();
?>

    <style>
        /* کارت‌های اصول */
        .principles-section .principle-card {
            border: none;
            border-radius: 15px;
            padding: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
        }

        .principles-section .principle-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .principle-icon {
            width: 80px;
            height: 80px;
            line-height: 80px;
            margin: 0 auto;
            border-radius: 50%;
            background: #ff6b6b;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            transition: background 0.3s ease;
        }

        .principles-section .principle-card:hover .principle-icon {
            background: #ee5a24;
        }

        .section-title {
            font-weight: 700;
            font-size: 2rem;
            color: #2c3e50;
        }

        .principle-card h4 {
            font-weight: 600;
            margin-top: 15px;
            color: #2c3e50;
        }

        .principle-card p {
            color: #555;
            font-size: 0.95rem;
        }

        /* انیمیشن هنگام ورود کارت‌ها */
        @keyframes fadeUp {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .animate-card {
            animation: fadeUp 0.6s ease forwards;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .principles-section .principle-card {
                margin-bottom: 20px;
            }
        }
    </style>
<?php
include '../partials/slider.php';
?>
    <section class="principles-section py-5">
        <div class="container">
            <h2 class="text-center mb-5 section-title">دیدگاه‌های بنیادی و اصولی گروه</h2>
            <div class="row">
                <?php foreach ($principles as $principle): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card principle-card h-100 shadow-sm animate-card">
                            <div class="card-body text-center">
                                <div class="principle-icon mb-3">
                                    <i class="<?php echo htmlspecialchars($principle['icon']); ?>"></i>
                                </div>
                                <h4 class="card-title"><?php echo htmlspecialchars($principle['title']); ?></h4>
                                <p class="card-text"><?php echo htmlspecialchars($principle['text']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

<?php
$content = ob_get_clean();

// فراخوانی layout.php
include '../partials/layout.php';
include '../partials/footer.php';
<?php
ob_start();

$slides = [
        ["نقشه برداری۱(گرمدره).jpg", "پروژه نقشه برداری ۱ (گرمدره)", "۱۳۹۴"],
        ["نقشه برداری۲(گرمدره).jpg", "پروژه نقشه برداری ۲ (گرمدره)", "۱۳۹۵"],
        ["ترنج(شهرک وحدت-نگارستان).jpg", "پروژه ترنج (شهرک وحدت)", "۱۳۹۷"],
        ["پلاریس(شهرک وحدت-نگارستان).jpg", "پروژه پلاریس (شهرک وحدت)", "۱۴۰۰"],
        ["اردیبهشت(شهرک وحدت-نگارستان).jpg", "پروژه اردیبهشت (شهرک وحدت)", "۱۳۹۹"],
        ["ساباط(شهرک وحدت نگارستان).jpg", "پروژه ساباط (شهرک وحدت)", "۱۴۰۱"]
];

// گروه‌بندی ۳ تایی برای دسکتاپ
$slideGroups = array_chunk($slides, 3);
?>

    <!-- استایل‌های اختصاصی UI/UX اسلایدر پروژه‌ها -->
    <style>
        .projects-slider-wrapper {
            position: relative;
            padding: 20px 0 50px 0; /* فضای پایین برای نشانگرها */
        }

        /* طراحی کارت پروژه */
        .project-card {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            aspect-ratio: 4/3; /* نسبت تصویر ثابت برای همه کارت‌ها */
            cursor: pointer;
        }

        .project-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        /* تنظیمات عکس */
        .project-image {
            width: 100%;
            height: 100%;
        }

        .project-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .project-card:hover .project-image img {
            transform: scale(1.08); /* زوم ملایم عکس در هاور */
        }

        /* لایه گرادیانت و متن روی عکس */
        .project-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 50px 20px 20px;
            background: linear-gradient(to top, rgba(15, 23, 42, 0.9) 0%, rgba(15, 23, 42, 0.6) 50%, transparent 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            opacity: 0.9;
            transition: padding 0.4s ease;
        }

        .project-card:hover .project-overlay {
            padding-bottom: 30px; /* متن در هاور کمی به بالا می‌آید */
            opacity: 1;
        }

        .project-overlay h5 {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: #ffffff;
        }

        .project-overlay .badge {
            background-color: var(--accent-gold, #f1ad1d);
            color: #fff;
            font-size: 0.85rem;
            align-self: flex-start;
            padding: 5px 10px;
            border-radius: 6px;
        }

        /* سفارشی‌سازی دکمه‌های کنترل اسلایدر */
        .custom-carousel-control {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0; /* مخفی در حالت عادی */
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .custom-carousel-control .carousel-control-prev-icon,
        .custom-carousel-control .carousel-control-next-icon {
            filter: invert(1) grayscale(100); /* سیاه کردن آیکون پیش‌فرض */
            width: 1.5rem;
            height: 1.5rem;
        }

        .projects-slider-wrapper:hover .custom-carousel-control {
            opacity: 1; /* نمایش دکمه‌ها وقتی موس روی کل اسلایدر است */
        }

        .custom-carousel-control:hover {
            background: var(--accent-gold, #f1ad1d);
        }

        .custom-carousel-control:hover .carousel-control-prev-icon,
        .custom-carousel-control:hover .carousel-control-next-icon {
            filter: invert(0); /* سفید شدن آیکون در هاور */
        }

        /* تنظیم محل دکمه‌ها در راست چین (RTL) */
        .carousel-control-prev { left: -15px; }
        .carousel-control-next { right: -15px; }

        /* نشانگرهای نقطه‌ای پایین */
        .custom-indicators {
            bottom: -40px;
        }
        .custom-indicators [data-bs-target] {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #cbd5e1;
            border: none;
            margin: 0 6px;
            transition: all 0.3s ease;
        }
        .custom-indicators .active {
            background-color: var(--primary-blue, #1b365d);
            width: 25px;
            border-radius: 10px;
        }

        @media (max-width: 768px) {
            .custom-carousel-control { display: none; } /* مخفی کردن دکمه‌ها در موبایل (استفاده از تاچ/سوایپ) */
            .project-card { margin-bottom: 20px; }
        }
    </style>

    <div class="projects-slider-wrapper px-3">
        <div id="projectCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="4000">

            <!-- نشانگرها (Dots) -->
            <div class="carousel-indicators custom-indicators">
                <?php foreach ($slideGroups as $index => $group): ?>
                    <button type="button"
                            data-bs-target="#projectCarousel"
                            data-bs-slide-to="<?= $index ?>"
                            class="<?= $index === 0 ? 'active' : '' ?>"
                            aria-current="<?= $index === 0 ? 'true' : 'false' ?>"
                            aria-label="Slide <?= $index + 1 ?>"></button>
                <?php endforeach; ?>
            </div>

            <div class="carousel-inner pb-4">
                <?php foreach ($slideGroups as $index => $group): ?>
                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                        <div class="container-fluid px-0">
                            <!-- استفاده از g-4 برای فاصله استاندارد بین کارت‌ها -->
                            <div class="row g-4">
                                <?php foreach ($group as $slide): ?>
                                    <div class="col-lg-4 col-md-6 col-12">
                                        <div class="project-card">
                                            <div class="project-image">
                                                <img src="<?= $baseUrl ?>/images/<?= rawurlencode($slide[0]) ?>"
                                                     alt="<?= htmlspecialchars($slide[1]) ?>"
                                                     loading="lazy">
                                            </div>
                                            <div class="project-overlay">
                                                <h5><?= htmlspecialchars($slide[1]) ?></h5>
                                                <?php if (!empty($slide[2])): ?>
                                                    <span class="badge"><?= htmlspecialchars($slide[2]) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- دکمه‌های کنترل (Prev / Next) -->
            <button class="carousel-control-prev custom-carousel-control" type="button" data-bs-target="#projectCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">قبلی</span>
            </button>
            <button class="carousel-control-next custom-carousel-control" type="button" data-bs-target="#projectCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">بعدی</span>
            </button>
        </div>
    </div>

<?php
$content = ob_get_clean();
$title = "پروژه‌ها - گروه مسکن سازان";
?>
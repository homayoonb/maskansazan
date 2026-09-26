<?php
$baseUrl = $baseUrl ?? '/maskansazan';
$imagesUrl = $baseUrl . '/images/';

$slides = [
        [
                'image' => 'نقشه برداری۱(گرمدره).jpg',
                'title' => 'پروژه نقشه برداری ۱ (گرمدره)',
                'link' => $baseUrl . '/garmdareh1.php',
        ],
        [
                'image' => 'نقشه برداری۲(گرمدره).jpg',
                'title' => 'پروژه نقشه برداری ۲ (گرمدره)',
                'link' => '#',
        ],
        [
                'image' => 'ترنج(شهرک وحدت-نگارستان).jpg',
                'title' => 'پروژه ترنج (شهرک وحدت)',
                'link' => '#',
        ],
        [
                'image' => 'پلاریس(شهرک وحدت-نگارستان).jpg',
                'title' => 'پروژه پلاریس (شهرک وحدت)',
                'link' => '#',
        ],
        [
                'image' => 'اردیبهشت(شهرک وحدت نگارستان).jpg',
                'title' => 'پروژه اردیبهشت (شهرک وحدت)',
                'link' => '#',
        ],
        [
                'image' => 'ساباط(شهرک وحدت نگارستان).jpg',
                'title' => 'پروژه ساباط (شهرک وحدت)',
                'link' => '#',
        ],
];
?>

<style>
    .hero-slider {
        width: 100%;
        padding: 18px 0 26px;
        background: #f8fafc;
    }

    .hero-slider .hero-inner {
        width: min(1320px, calc(100% - 32px));
        margin: 0 auto;
    }

    .hero-carousel {
        position: relative;
        overflow: hidden;
        border-radius: 18px;
        background: #0f172a;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .10);
    }

    .hero-carousel .carousel-item {
        position: relative;
        height: 440px;
    }

    .hero-carousel .carousel-item > img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .hero-caption {
        position: absolute;
        z-index: 5;
        right: 28px;
        bottom: 26px;
        max-width: calc(100% - 56px);
        padding: 14px 26px;
        overflow: hidden;
        color: #fff;
        font-size: 1.05rem;
        font-weight: 800;
        text-decoration: none;
        text-overflow: ellipsis;
        white-space: nowrap;
        background: #22306b;
        border-radius: 10px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, .25);
    }

    .hero-caption:hover {
        color: #fff;
        background: #1b2656;
    }

    .hero-carousel .carousel-indicators {
        right: 26px;
        bottom: 10px;
        left: auto;
        gap: 8px;
        margin: 0;
    }

    .hero-carousel .carousel-indicators [data-bs-target] {
        width: 10px;
        height: 10px;
        margin: 0;
        opacity: 1;
        background: rgba(255, 255, 255, .75);
        border: 0;
        border-radius: 50%;
    }

    .hero-carousel .carousel-indicators .active {
        background: #f97316;
        transform: scale(1.15);
    }

    @media (max-width: 992px) {
        .hero-carousel .carousel-item {
            height: 320px;
        }

        .hero-caption {
            right: 14px;
            bottom: 18px;
            left: 14px;
            text-align: center;
            white-space: normal;
        }

        .hero-carousel .carousel-indicators {
            right: 50%;
            transform: translateX(50%);
        }
    }

    @media (max-width: 576px) {
        .hero-slider .hero-inner {
            width: calc(100% - 20px);
        }

        .hero-carousel .carousel-item {
            height: 230px;
        }

        .hero-caption {
            padding: 10px 14px;
            font-size: .9rem;
        }
    }
</style>

<section class="hero-slider">
    <div class="hero-inner">
        <div id="mainHeroCarousel"
             class="carousel slide hero-carousel"
             data-bs-ride="carousel"
             data-bs-interval="4500">

            <div class="carousel-indicators">
                <?php foreach ($slides as $index => $slide): ?>
                    <button
                            type="button"
                            data-bs-target="#mainHeroCarousel"
                            data-bs-slide-to="<?= $index ?>"
                            class="<?= $index === 0 ? 'active' : '' ?>"
                            <?= $index === 0 ? 'aria-current="true"' : '' ?>
                            aria-label="اسلاید <?= $index + 1 ?>">
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="carousel-inner">
                <?php foreach ($slides as $index => $slide): ?>
                    <?php $imageUrl = $imagesUrl . rawurlencode($slide['image']); ?>

                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                        <img
                                src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>"
                                alt="<?= htmlspecialchars($slide['title'], ENT_QUOTES, 'UTF-8') ?>"
                        >

                        <a
                                class="hero-caption"
                                href="<?= htmlspecialchars($slide['link'], ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <?= htmlspecialchars($slide['title'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

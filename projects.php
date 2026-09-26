<?php
ob_start();
?>

<style>
    .card {
        transition: all 0.3s ease;
    }

    .card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.12) !important;
    }

    .card-img-top {
        border-bottom: 1px solid #f1f1f1;
    }

    .card-text {
        font-size: 1rem;
        color: #243b6b !important;
    }
}

    /* استایل اختصاصی برای شبیه‌سازی طرح تصویر */
    .project-card {
        border: none;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .project-card img {
        border-radius: 15px; /* گوشه‌های گرد */
        object-fit: cover;
        height: 200px; /* ارتفاع ثابت برای یکدستی */
        width: 100%;
        transition: transform 0.3s ease;
    }
    .project-card:hover img {
        transform: scale(1.03); /* افکت زوم ملایم */
    }
    .project-title {
        font-weight: 700;
        color: #2c3e50; /* رنگ سرمه‌ای تیره */
        margin-top: 15px;
        text-align: center;
        font-size: 1.1rem;
    }
</style>
<div class="container-fluid py-3">
    <div class="row g-4">

        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden">
                <a href="pages/garmdareh1.php" class="text-decoration-none">
                    <img src="pages/1.jpg" class="card-img-top" alt="پروژه نقشه برداری ۱" style="height: 220px; object-fit: cover;">
                    <div class="card-body p-3 text-center">
                        <p class="card-text fw-bold text-dark mb-0">پروژه نقشه برداری ۱</p>
                    </div>
                </a>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden">
                <a href="pages/garmdareh2.php" class="text-decoration-none">
                    <img src="pages/2.jpg" class="card-img-top" alt="پروژه نقشه برداری ۲" style="height: 220px; object-fit: cover;">
                    <div class="card-body p-3 text-center">
                        <p class="card-text fw-bold text-dark mb-0">پروژه نقشه برداری ۲</p>
                    </div>
                </a>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden">
                <a href="pages/toranj.php" class="text-decoration-none">
                    <img src="pages/Picture6.jpg" class="card-img-top" alt="پروژه ترنج" style="height: 220px; object-fit: cover;">
                    <div class="card-body p-3 text-center">
                        <p class="card-text fw-bold text-dark mb-0">پروژه ترنج</p>
                    </div>
                </a>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden">
                <a href="pages/ordibehesht.php" class="text-decoration-none">
                    <img src="pages/Picture57.jpg" class="card-img-top" alt="پروژه اردیبهشت" style="height: 220px; object-fit: cover;">
                    <div class="card-body p-3 text-center">
                        <p class="card-text fw-bold text-dark mb-0">پروژه اردیبهشت</p>
                    </div>
                </a>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden">
                <a href="pages/polaris.php" class="text-decoration-none">
                    <img src="pages/Picture39.jpg" class="card-img-top" alt="پروژه پلاریس" style="height: 220px; object-fit: cover;">
                    <div class="card-body p-3 text-center">
                        <p class="card-text fw-bold text-dark mb-0">پروژه پلاریس</p>
                    </div>
                </a>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden">
                <a href="pages/sabat.php" class="text-decoration-none">
                    <img src="pages/Picture73.jpg" class="card-img-top" alt="پروژه ساباط" style="height: 220px; object-fit: cover;">
                    <div class="card-body p-3 text-center">
                        <p class="card-text fw-bold text-dark mb-0">پروژه ساباط</p>
                    </div>
                </a>
            </div>
        </div>


        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden">
                <a href="pages/mehr.php" class="text-decoration-none">
                    <img src="pages/1781091183952.jpg" class="card-img-top" alt="پروژه مهرپادین" style="height: 220px; object-fit: cover;">
                    <div class="card-body p-3 text-center">
                        <p class="card-text fw-bold text-dark mb-0"> پروژه مهرپادین در دست اجرا</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>





<?php

$content = ob_get_clean();
$title = "لیست پروژه ها";
include 'partials/layout.php';
?>


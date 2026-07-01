<?php
// تعریف متغیرهای سمت سرور PHP
$project_title = "موقعیت مکانی پروژه ترنج";
$project_latitude = 35.75911717811881;
$project_longitude = 51.02485854232872;
$images = [
    "Picture6.jpg",
    "Picture7.jpg",
];
$images2 = [
    "Picture8.jpg",
    "Picture9.jpg",
    "Picture10.jpg",
    "Picture11.jpg",
    "Picture12.jpg",
    "Picture13.jpg",
    "Picture14.jpg",
    "Picture15.jpg",
    "Picture16.jpg",
    "Picture17.jpg",
    "Picture18.jpg",
    "Picture19.jpg",
    "Picture20.jpg"
];
$images3 = [
    "Picture21.jpg",
    "Picture22.jpg",
    "Picture23.jpg",
    "Picture24.jpg",
    "Picture25.jpg",
    "Picture26.jpg",
    "Picture27.jpg",
    "Picture28.jpg",
    "Picture29.jpg",
    "Picture31.jpg",
    "Picture33.jpg",
    "Picture34.jpg",
    "Picture35.jpg",
    "Picture36.jpg",
    "Picture37.jpg"
];
ob_start();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $project_title; ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        body {
            margin: 0;
            font-family: Tahoma, Arial, sans-serif;
            background: #f5f5f5;
        }
        /* ===== Root ===== */
        :root {
            --primary: #5ec9f3;
            --border: #dcdcdc;
            --bg-soft: #f7f9fc;
            --text-main: #222;
            --text-muted: #666;
        }
        .font-vazir { font-family: 'Tahoma', sans-serif; } /* حتما فونت فارسی جایگزین شود */
        .card-header { background: linear-gradient(135deg, #1a1a1a 0%, #373b3e 100%); }
        .table thead th { font-size: 0.85rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
        .btn-check:checked + .btn { background-color: #0d6efd !important; color: white !important; }
        .border-start { border-color: #dee2e6 !important; }

        @media (max-width: 768px) {
            .border-start { border-left: none !important; border-bottom: 1px solid #dee2e6; padding-bottom: 10px; }
        }

        /* ===== Card (Glass Style) ===== */
        .glass-project {
            direction: rtl;
            max-width: 1200px;
            margin: 30px auto;
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(14px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
            overflow: hidden;
            font-family: Vazirmatn, IRANSans, Tahoma, sans-serif;
        }

        /* ===== Header ===== */
        .project-header {
            background: linear-gradient(135deg, #a6f3df, var(--primary));
            text-align: center;
            padding: 22px;
        }

        .project-header h3 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            color: var(--text-dark);
        }

        .project-header span {
            display: block;
            margin-top: 6px;
            font-size: 14px;
            color: #044;
        }

        /* ===== Table Wrapper ===== */
        .table-wrapper {
            overflow-x: auto;
            background: #f8fafc;
        }

        /* ===== Table ===== */
        .project-table {
            width: 100%;
            min-width: 950px;
            border-collapse: collapse;
            background: #fff;
        }

        /* ===== Cells ===== */
        .project-table th,
        .project-table td {
            border: 1px solid var(--border);
            padding: 14px 10px;
            text-align: center;
            font-size: 14px;
            white-space: nowrap;
        }

        /* ===== Head ===== */
        .project-table thead th {
            background: #f1f3f6;
            font-weight: 700;
            color: var(--text-dark);
        }

        /* ===== Section Row ===== */
        .project-table .section th {
            background: #eef1f5;
            font-weight: 800;
        }

        /* ===== Zebra ===== */
        .project-table tbody tr:nth-child(even):not(.section) {
            background: #fafafa;
        }

        /* ===== Hover ===== */
        .project-table tbody tr:hover:not(.section) {
            background: #eaf6ff;
        }

        /* ======================
           ✅ Mobile UX
        ====================== */
        @media (max-width: 768px) {
            .glass-project {
                margin: 20px 12px;
                border-radius: 16px;
            }

            .project-header h3 {
                font-size: 18px;
            }

            .project-header span {
                font-size: 13px;
            }

            .project-table {
                min-width: 720px;
            }
        }

        body {
            margin: 0;
            font-family: Tahoma, Arial, sans-serif;
            background: #f5f5f5;
        }

        /* ===============================
           Map Section
        ================================ */
        .map-section {
            display: flex;
            justify-content: flex-end;
            align-items: flex-end;
            width: 100%;
            min-height: 500px;
            padding: 20px;
            box-sizing: border-box;
        }

        /* ===============================
           Map Card
        ================================ */
        .map-wrapper {
            position: relative;
            width: 420px;
            height: 320px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(0,0,0,.25);
            direction: rtl;
        }

        /* ===============================
           Main Map
        ================================ */
        #map {
            width: 100%;
            height: 100%;
        }

        /* ===============================
           Mini Map
        ================================ */
        #map-mini {
            position: absolute;
            bottom: 12px;
            left: 12px;
            width: 120px;
            height: 90px;
            border-radius: 10px;
            border: 2px solid #fff;
            background: #fff;
            z-index: 500;
        }

        /* ===============================
           Layer Controls
        ================================ */
        .layer-controls {
            position: absolute;
            top: 16px;
            right: 16px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .control-btn {
            background: rgba(255,255,255,.9);
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
        }

        .control-btn.active {
            background: #2563eb;
            color: #fff;
        }

        /* ===============================
           Coordinates
        ================================ */
        .coords-box {
            position: absolute;
            bottom: 12px;
            right: 12px;
            background: rgba(0,0,0,.6);
            color: #fff;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 12px;
            z-index: 1000;
        }

        /* ===============================
           Leaflet Fixes
        ================================ */
        .leaflet-marker-icon,
        .leaflet-marker-shadow {
            overflow: visible !important;
        }

        /* ===============================
           Zoom (LEFT)
        ================================ */
        .leaflet-top.leaflet-right {
            right: auto !important;
            left: 14px !important;
        }

        .leaflet-control-zoom {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 6px 18px rgba(0,0,0,.25);
        }

        .leaflet-control-zoom a {
            width: 34px;
            height: 34px;
            line-height: 34px;
            background: #fff !important;
            color: #111 !important;
            border: none !important;
        }

        .leaflet-control-zoom a:hover {
            background: #2563eb !important;
            color: #fff !important;
        }

        /* ===============================
           Responsive
        ================================ */
        @media (max-width: 768px) {
            .map-wrapper {
                width: 100%;
                height: 360px;
            }
        }
        /* ===============================
           Glass Info Panel
        ================================ */
        .info-panel {
            margin: 30px auto;
            max-width: 1100px;
            padding: 24px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.18);
        }

        /* کل ظرف پنل */
        .info-panel.glass-panel {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
            margin: 20px auto;
            max-width: 1200px;
            direction: rtl;
        }

        /* هدر و آیکون */
        .info-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .info-header h5 {
            font-weight: 800;
            color: #2c3e50;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .info-header i {
            color: #3498db;
            font-size: 1.6rem;
        }

        /* استایل جدول در دسکتاپ */
        .custom-table {
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }

        .custom-table table {
            margin-bottom: 0;
            border: none !important;
        }

        .custom-table thead th {
            background: rgba(248, 249, 250, 0.7) !important;
            color: #5d6d7e;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 15px;
            border-bottom: 2px solid #edf2f7 !important;
            white-space: nowrap;
        }

        .custom-table tbody td {
            padding: 20px 15px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f4f8;
            color: #2d3748;
            font-weight: 500;
        }

        /* نشان‌ها (Badges) */
        .badge {
            padding: 8px 14px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        .bg-success { background-color: #2ecc71 !important; }
        .bg-primary { background-color: #3498db !important; }
        .bg-danger { background-color: #e74c3c !important; }

        /* 📱 واکنش‌گرایی مخصوص موبایل (حالت کارت) */
        @media (max-width: 991px) {
            .custom-table thead {
                display: none; /* مخفی کردن هدر در موبایل */
            }

            .custom-table table,
            .custom-table tbody,
            .custom-table tr,
            .custom-table td {
                display: block;
                width: 100%;
            }

            .custom-table tr {
                margin-bottom: 20px;
                border: 1px solid #edf2f7;
                border-radius: 15px;
                background: #fff;
                padding: 10px;
            }

            .custom-table td {
                text-align: left;
                padding-left: 50%;
                position: relative;
                border-bottom: 1px solid #f7fafc;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            /* اضافه کردن عنوان ستون‌ها در کنار مقادیر در موبایل */
            .custom-table td::before {
                content: attr(data-label);
                font-weight: 700;
                color: #718096;
                font-size: 0.85rem;
            }

            .custom-table td:last-child {
                border-bottom: 0;
            }
        }
        @font-face {
            font-family: "IranNastaliq";
        }
        <style>
         :root {
             --primary: #5ec9f3;
             --text-dark: #1a1a1a;
         }

        body { margin: 0; font-family: Tahoma, Arial, sans-serif; background: #f5f5f5; }

        /* جدول اصلی */
        .table-responsive-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 2rem;
        }

        /* نقشه */
        .map-container {
            position: relative;
            width: 100%;
            height: 350px; /* پیش‌فرض دسکتاپ */
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            background: #e5e7eb;
        }

        #map { width: 100%; height: 100%; }

        /* دکمه‌های لایه */
        .map-layer-buttons {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
        }

        /* اصلاح برای موبایل */
        @media (max-width: 768px) {
            .map-container {
                height: 300px; /* ارتفاع کمتر در موبایل */
                border-radius: 12px;
            }

            /* دکمه‌ها در موبایل کوچک‌تر شوند */
            .map-layer-buttons .btn {
                padding: 4px 8px !important;
                font-size: 12px !important;
            }
        }

        /* استایل کارت‌های گالری */
        .img-ratio-box {
            position: relative;
            width: 100%;
            padding-top: 75%; /* نسبت 4:3 */
            overflow: hidden;
            border-radius: 12px;
        }

        .img-ratio-box img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* اصلاحات فونت */
        .font-vazir { font-family: Tahoma, sans-serif; }
    </style>


    </style>

</head>
<body>

<div class="container my-5">
    <div class="container my-5 font-vazir" dir="rtl">
        <div class="container">
            <table class="table table-bordered text-center align-middle">
                <!-- عنوان اصلی -->
                <tr style="background-color: #0d6efd; color: white;">
                    <th colspan="8" style="padding: 10px;">پروژه ترنج
                        <br>
                        تاریخ شروع ساخت 1394/7/1
                    </th>
                </tr>

                <!-- استفاده از rowspan واقعی در یک بخش واحد -->
                <tr>
                    <td rowspan="2" class="align-middle" style="background-color: #f8f9fa; font-weight: bold; width: 150px;">
                        مشخصات پروژه <br>
                    </td>
                    <td class="bg-light" style="font-weight: bold;">متراژ زمین</td>
                    <td class="bg-light" style="font-weight: bold;">تعهدات اولیه</td>
                    <td class="bg-light" style="font-weight: bold;">واحد مسکونی</td>
                    <td class="bg-light" style="font-weight: bold;">متراژ هر واحد</td>
                    <td class="bg-light" style="font-weight: bold;">تجاری ساخته شده</td>
                    <td class="bg-light" style="font-weight: bold;">مجموع تجاری</td>
                    <td class="bg-light" style="font-weight: bold;">طبقات</td>
                </tr>
                <tr>
                    <td>1,770</td>
                    <!-- ردیف دوم داده‌ها -->
                    <td>40 واحد 100 متری</td>
                    <td>40</td>
                    <td>110</td>
                    <td>1</td>
                    <td>40</td>
                    <td>6</td>
                </tr>
                <!-- استفاده از rowspan واقعی در یک بخش واحد -->
                <tr>
                    <td rowspan="2" class="align-middle" style="background-color: #f8f9fa; font-weight: bold; width: 150px;">
                        هزینه و نحوه پرداخت اقساط<br>
                    </td>
                    <td class="bg-light" style="font-weight: bold;">قیمت زمین(تومان)</td>
                    <td colspan="2" class="bg-light" style="font-weight: bold;">مبلغ جواز(تومان)</td>
                    <td colspan="2" class="bg-light" style="font-weight: bold;">مجموع هزینه  ساخت</td>
                    <td colspan="2" class="bg-light" style="font-weight: bold;">سهم هر عضو</td>
                </tr>
                <tr>
                    <td>1,600,000,000</td>
                    <!-- ردیف دوم داده‌ها -->
                    <td colspan="2">253,000,000</td>
                    <td colspan="2">3,645,000,000</td>
                    <td colspan="2">111,500,000</td>
                </tr>
            </table>
        </div>
        <div class="container">
            <div class="row flex-row-reverse">
                <div class="col-lg-4 ">
                    <div class="card shadow border-0 rounded-4 overflow-hidden h-100">
                        <div class="position-relative h-100" style="min-height: 350px;">
                            <div id="map" class="h-100 w-100" style="position: absolute; top: 0; bottom: 0;"></div>

                            <div class="position-absolute top-50 end-0 translate-middle-y me-2" style="z-index: 1000;">
                                <div class="btn-group-vertical shadow bg-white p-1 rounded-3">
                                    <button type="button" class="btn btn-xs btn-outline-primary active" id="btn-std" onclick="changeLayer('standard', this)">
                                        <i class="fas fa-map"></i>
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-primary my-1" id="btn-sat" onclick="changeLayer('satellite', this)">
                                        <i class="fas fa-satellite"></i>
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-primary" id="btn-hyb" onclick="changeLayer('hybrid', this)">
                                        <i class="fas fa-layer-group"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- کارت ۱: نمایی از پروژه -->
    <div class="card shadow-lg border-0 rounded-4 mb-5">
        <div class="card-body p-4 p-md-5">

            <h4 class="text-center fw-bold mb-2">نمایی از پروژه ترنج</h4>
            <div class="mx-auto mb-4 bg-primary rounded" style="width:80px;height:4px;"></div>

            <div class="row g-4">
                <?php foreach ($images as $img): ?>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="ratio ratio-4x3 rounded-3 overflow-hidden shadow-sm">
                            <img src="<?= $img ?>" class="img-fluid object-fit-cover" alt="پروژه ساختمانی">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <p class="text-center text-muted mt-4 mb-0">
                پروژه مسکونی ترنج واقع در شهرک نگارستان، بلوار شهیدان بخشی، بیست متری بهارستان شرقی، قطعه ۱۸۳ واقع شده است.
            </p>

        </div>
    </div>

    <!-- کارت ۲: مشاعات -->
    <div class="card shadow-lg border-0 rounded-4 mb-5">
        <div class="card-body p-4 p-md-5">

            <h4 class="text-center fw-bold mb-2">نمایی از مشاعات پروژه ترنج</h4>
            <div class="mx-auto mb-4 bg-primary rounded" style="width:80px;height:4px;"></div>

            <div class="row g-4">
                <?php foreach ($images as $img): ?>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="ratio ratio-4x3 rounded-3 overflow-hidden shadow-sm">
                            <img src="<?= $img ?>" class="img-fluid object-fit-cover" alt="پروژه ساختمانی">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

    <!-- کارت ۳: داخل واحدها -->
    <div class="card shadow-lg border-0 rounded-4 mb-5">
        <div class="card-body p-4 p-md-5">

            <h4 class="text-center fw-bold mb-2">نمایی از داخل واحدهای پروژه ترنج</h4>
            <div class="mx-auto mb-4 bg-primary rounded" style="width:80px;height:4px;"></div>

            <div class="row g-4">
                <?php foreach ($images2 as $img): ?>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="ratio ratio-4x3 rounded-3 overflow-hidden shadow-sm">
                            <img src="<?= $img ?>" class="img-fluid object-fit-cover" alt="پروژه ساختمانی">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</div>





<script>
    (function () {

        function initMap() {

            /* 1. بررسی وجود Leaflet و المنت نقشه */
            if (typeof L === 'undefined') {
                console.error('❌ Leaflet is not loaded');
                return;
            }

            const mapContainer = document.getElementById('map');
            if (!mapContainer) return;

            /* 2. دریافت مختصات از PHP (امن و بدون null) */
            const phpLat = "<?php echo $project_latitude ?? ''; ?>";
            const phpLng = "<?php echo $project_longitude ?? ''; ?>";

            const lat = isNaN(parseFloat(phpLat)) ? 35.7000 : parseFloat(phpLat);
            const lng = isNaN(parseFloat(phpLng)) ? 51.0000 : parseFloat(phpLng);

            const projectLocation = [lat, lng];

            if (isNaN(lat) || isNaN(lng)) {
                console.warn('⚠️ Invalid coordinates, map not initialized');
                return;
            }

            /* 3. لایه‌های نقشه */
            const baseLayers = {
                standard: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap'
                }),
                satellite: L.tileLayer(
                    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                    { attribution: '© Esri' }
                ),
                hybrid: L.tileLayer(
                    'https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
                        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                        attribution: '© Google'
                    }
                )
            };

            /* 4. ساخت نقشه */
            let map;
            try {
                map = L.map('map', {
                    center: projectLocation,
                    zoom: 16,
                    zoomControl: false,
                    layers: [baseLayers.standard]
                });
            } catch (e) {
                console.error('❌ Leaflet init failed:', e);
                return;
            }

            /* 5. آیکون پین (رفع کامل خطای Point.js) */
            // آیکون پین قرمز حرفه‌ای که خودت ساختی
            const redPinIcon = L.divIcon({
                html: `
                <div style="position: relative; display: inline-block;">
                    <div style="width: 24px; height: 24px; background: #dc2626; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(45deg); color: white; font-size: 14px;">📍</div>
                </div>`,
                className: 'red-pin-marker',
                iconSize: [30, 30],
                iconAnchor: [15, 30]
            });
            let text = "پروژه نقشه ترنج";
            const textLength = text.length;
            const minWidth = 80;
            const maxWidth = 200;
            const calculatedWidth = Math.min(maxWidth, Math.max(minWidth, textLength * 8 + 30));

            infoLabel = L.marker(projectLocation, {
                icon: L.divIcon({
                    html: `
                    <div style="
                        background: white;
                        padding: 8px 12px;
                        border-radius: 6px;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                        border: 2px solid #dc2626;
                        font-family: Tahoma, Arial, sans-serif;
                        font-size: 12px;
                        font-weight: bold;
                        color: #1f2937;
                        white-space: nowrap;
                        width:${calculatedWidth}px
                    ">
                        📍 پروژه ترنج
                    </div>
                `,
                    className: 'text-label',
                    iconSize: [120, 30],
                    iconAnchor: [60, -10]
                })
            }).addTo(map)
            L.marker(projectLocation, { icon: redPinIcon }).addTo(map);


// تنظیم دکمه‌های مثبت و منفی در سمت راست بالا
            L.control.zoom({
                position: 'topright'
            }).addTo(map);

            /* 8. تغییر لایه (برای دکمه‌های HTML) */
            window.changeLayer = function (type, btn) {
                Object.values(baseLayers).forEach(layer => {
                    if (map.hasLayer(layer)) map.removeLayer(layer);
                });
                baseLayers[type].addTo(map);

                if (btn) {
                    btn.parentElement
                        .querySelectorAll('.btn')
                        .forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                }
            };
        }

        /* اجرای امن بعد از لود کامل صفحه */
        if (document.readyState === 'complete') {
            initMap();
        } else {
            window.addEventListener('load', initMap);
        }

    })();
</script>

</body>
</html>
<?php
// ۶. پایان ذخیره در بافر و ارسال به لایوت
$content = ob_get_clean();
include '../partials/layout.php';
include '../partials/footer.php';
?>

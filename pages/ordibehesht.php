<?php
$project_latitude = 35.76400156982197;
$project_longitude = 51.023260084657494;
$images = [
        "Picture55.jpg",
        "Picture56.jpg"
];
$images2 = [
        "Picture57.jpg",
        "Picture58.jpg",
        "Picture59.jpg",
        "Picture60.jpg",
        "Picture61.jpg",
        "Picture62.jpg",
        "Picture63.jpg",
        "Picture64.jpg",
];
$images3 = [
        "Picture65.jpg",
        "Picture66.jpg",
        "Picture67.jpg",
        "Picture68.jpg",
        "Picture69.jpg",
        "Picture70.jpg",
        "Picture71.jpg",
        "Picture72.jpg"
];
ob_start();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>موقعیت مکانی پروژه اردیبهشت</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        :root {
            --border: rgba(0,0,0,.12);
            --text-dark: #2c3e50;
        }

        /* ===== Card ===== */
        .glass-project {
            direction: rtl;
            max-width: 1200px;
            margin: 30px auto;
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(14px);
            border-radius: 22px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
            overflow: hidden;
            font-family: Vazirmatn, IRANSans, Tahoma, sans-serif;
        }

        /* ===== Header ===== */
        .project-header {
            text-align: center;
            padding: 22px;
        }

        .project-header.beige {
            background: linear-gradient(135deg, #f6ecd9, #efe2c6);
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
            color: #444;
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
            background: #f2f2f2;
            font-weight: 700;
        }

        /* ===== Section Row ===== */
        .project-table .section th {
            background: #f6f7fa;
            font-weight: 800;
        }

        /* ===== Zebra ===== */
        .project-table tbody tr:nth-child(even):not(.section) {
            background: #fafafa;
        }

        /* ===== Hover ===== */
        .project-table tbody tr:hover:not(.section) {
            background: #eef6ff;
        }

        /* ===== Mobile ===== */
        @media (max-width: 768px) {
            .glass-project {
                margin: 18px 12px;
                border-radius: 18px;
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
                text-align: right;
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


    </style>
</head>
<body>

<div class="container my-5">
    <div class="container">
        <table class="table table-bordered text-center align-middle">
            <!-- عنوان اصلی -->
            <tr style="background-color: #0d6efd; color: white;">
                <th colspan="8" style="padding: 10px;">پروژه اردیبهشت
                    <br>
                    تاریخ شروع ساخت 1397/2/1
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
                <td>2,400</td>
                <!-- ردیف دوم داده‌ها -->
                <td>65 واحد 75متری</td>
                <td>56</td>
                <td>87</td>
                <td>4</td>
                <td>110</td>
                <td>5</td>
            </tr>
            <!-- استفاده از rowspan واقعی در یک بخش واحد -->
            <tr>
                <td rowspan="2" class="align-middle" style="background-color: #f8f9fa; font-weight: bold; width: 150px;">
                    هزینه و نحوه پرداخت اقساط<br>
                </td>
                <td class="bg-light" style="font-weight: bold;">قیمت زمین(تومان)</td>
                <td colspan="2" class="bg-light" style="font-weight: bold;">مبلغ جواز(تومان)</td>
                <td colspan="2" class="bg-light" style="font-weight: bold;">مجموع هزینه  ساخت</td>
                <td colspan="2" class="bg-light" style="font-weight: bold;"> سهم هر عضو با وام های 70 و 50 میلیونی</td>
            </tr>
            <tr>
                <td>2,100,000,000</td>
                <!-- ردیف دوم داده‌ها -->
                <td colspan="2">665,000,000</td>
                <td colspan="2">18,000,000,000</td>
                <td colspan="2">282,500,000</td>
            </tr>
        </table>
    </div>
    <div class="container">
        <div class="row flex-row-reverse">
            <div class="col-lg-4 order-1 order-lg-2">
                <div class="card shadow border-0 rounded-4 overflow-hidden h-100">
                    <div class="position-relative h-100" style="min-height: 350px;">
                        <div id="map" class="h-100 w-100" style="position: absolute; top: 0; bottom: 0;"></div>

                        <div class="position-absolute top-50 start-0 translate-middle-y ms-2" style="z-index: 1000;">
                            <div class="btn-group-vertical shadow bg-white p-1 rounded-3">
                                <button type="button" class="control-btn btn btn-outline-primary active btn-primary"
                                        onclick="changeLayer('standard', this)" title="نقشه">
                                    <i class="fas fa-map"></i>
                                </button>
                                <button type="button" class="control-btn btn btn-outline-primary"
                                        onclick="changeLayer('satellite', this)" title="ماهواره">
                                    <i class="fas fa-satellite"></i>
                                </button>
                                <button type="button" class="control-btn btn btn-outline-primary"
                                        onclick="changeLayer('hybrid', this)" title="ترکیبی">
                                    <i class="fas fa-layer-group"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br>
    <br>
    <!-- ===== پروژه اردیبهشت ===== -->
    <div class="row g-4">





        <br/>
        <br/>
        <div class="card shadow-lg border-0 rounded-4 mb-5">
            <div class="card-body p-4 p-md-5">

                <h4 class="text-center fw-bold mb-4">
                    نمایی از پروژه اردیبهشت
                    <div class="mx-auto mt-2 bg-primary rounded"
                         style="width:80px;height:4px;"></div>
                </h4>

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
                    پروژه اردیبهشت واقع در شهرک وحدت، خیابان فیروزآبادی، قطعه ۷۰۳۱
                </p>

            </div>
        </div>

        <!-- ===== مشاعات ===== -->
        <div class="card shadow-lg border-0 rounded-4 mb-5">
            <div class="card-body p-4 p-md-5">

                <h4 class="text-center fw-bold mb-4">
                    از مشاعات پروژه اردیبهشت
                    <div class="mx-auto mt-2 bg-primary rounded"
                         style="width:80px;height:4px;"></div>
                </h4>

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

        <!-- ===== داخل واحدها ===== -->
        <div class="card shadow-lg border-0 rounded-4 mb-5">
            <div class="card-body p-4 p-md-5">

                <h4 class="text-center fw-bold mb-4">
                    نمایی از داخل واحدهای پروژه اردیبهشت
                    <div class="mx-auto mt-2 bg-primary rounded"
                         style="width:80px;height:4px;"></div>
                </h4>

                <div class="row g-4">
                    <?php foreach ($images3 as $img): ?>
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

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        // مختصات پروژه (دریافت از PHP)
        const projectLocation = [<?php echo $project_latitude; ?>, <?php echo $project_longitude; ?>];
        let map, marker, infoLabel;

        // لایه‌ها
        const baseLayers = {
            standard: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }),

            satellite: L.tileLayer(
                'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                {
                    attribution: 'Tiles © Esri',
                    maxZoom: 19
                }
            ),

            hybrid: L.tileLayer('https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
                subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                attribution: '© Google Maps',
                maxZoom: 20
            })
        };

        function initMap() {
            map = L.map('map', {
                center: projectLocation,
                zoom: 16,
                zoomControl: false,
                layers: [baseLayers.standard]
            });

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

            marker = L.marker(projectLocation, { icon: redPinIcon }).addTo(map);

            let text = "پروژه اردیبهشت";
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
          ">📍 ${text}</div>
        `,
                    className: 'text-label',
                    iconSize: [120, 30],
                    iconAnchor: [60, -10]
                })
            }).addTo(map);

            L.control.zoom({ position: 'topright' }).addTo(map);
            L.control.scale({ metric: true, imperial: false, position: 'bottomright' }).addTo(map);
        }

        // نسخه نهایی: فقط دکمه کلیک‌شده active می‌شود
        function changeLayer(layerType, btnEl) {
            // حذف تمام لایه‌های فعلی
            Object.values(baseLayers).forEach(layer => {
                if (map.hasLayer(layer)) map.removeLayer(layer);
            });

            // اضافه کردن لایه جدید
            baseLayers[layerType].addTo(map);

            // ریست وضعیت همه دکمه‌ها
            document.querySelectorAll('.control-btn').forEach(btn => {
                btn.classList.remove('active', 'btn-primary');
                btn.classList.add('btn-outline-primary');
            });

            // فعال کردن فقط همین دکمه
            if (btnEl) {
                btnEl.classList.add('active', 'btn-primary');
                btnEl.classList.remove('btn-outline-primary');
            }
        }

        document.addEventListener('DOMContentLoaded', initMap);
    </script>

</body>
</html>
<?php

$content = ob_get_clean();
include '../partials/layout.php';
include '../partials/footer.php';
?>

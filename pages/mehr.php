<?php
$project_title = "موقعیت مکانی پروژه مهرپادین";
$project_latitude = 35.764895;
$project_longitude = 51.023180;
ob_start();
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $project_title ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
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
            position: sticky;
            left: 20px;
            bottom: 20px;

            /* ✅ اندازه اتوماتیک */
            width: clamp(280px, 26vw, 380px);
            aspect-ratio: 4 / 3;
            height: auto;

            margin-top: auto; /* هل دادن به پایین container */

            z-index: 10;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(0,0,0,.25);
            direction: rtl;
            background: #fff;

            transition: opacity .35s ease, transform .35s ease;
        }


        /* ===============================
           Main Map
        ================================ */
        #map {
            width: 100%;
            height: 100%;

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
                left: 16px;
                right: 16px;
                width: auto;
                aspect-ratio: 16 / 9;
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

        /* Map container */
        #map{
            width:100%;
            height:100%;
        }

        /* کارت نقشه */
        .card .position-relative{
            min-height:350px;
        }

        /* موبایل */
        @media (max-width:768px){

            .col-lg-4{
                width:100%;
            }

            .card .position-relative{
                min-height:auto;
                aspect-ratio:16/9;   /* ارتفاع اتوماتیک متناسب با عرض */
            }

            #map{
                position:absolute;
                inset:0;
                width:100%;
                height:100%;
            }

        }

    </style>
</head>
<body>
<div class="container">
    <div class="row flex-row-reverse">
        <div class="col-lg-10">
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

        let text = "پروژه مهرپادین";
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
<?php
// ۶. پایان ذخیره در بافر و ارسال به لایوت
$content = ob_get_clean();
include '../partials/layout.php';
?>

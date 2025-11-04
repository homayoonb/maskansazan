<?php
include "partials/layout.php";
// تعریف متغیرها (معادل @ViewData و @Model در Razor Pages)
$view_title = "موقعیت مکانی پروژه پلاریس";

// در Razor Pages، Model.Latitude و Model.Longitude استفاده شده بود.
// فرض می‌کنیم مقادیر ثابت زیر معادل آنها هستند.
// در یک برنامه واقعی PHP، اینها از یک دیتابیس یا متغیر تعریف شده می‌آیند.
$project_latitude = 35.76147881529619;
$project_longitude = 51.01434749154714;

?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $view_title; ?></title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* Map Container */
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #10b981;
            --dark: #1f2937;
            --light: #f8fafc;
            --gray: #64748b;
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        body {
            /* اضافه کردن یک بک‌گراند تیره برای بهتر دیده شدن استایل گلاسمورفیسم */
            background-color: #1a202c;
            color: var(--light);
        }

        .container {
            padding-top: 20px;
        }

        .map-wrapper {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            margin-bottom: 20px;
            height: 600px;
        }

        #map {
            height: 100%;
            width: 100%;
            z-index: 1;
        }

        /* Glassmorphism Controls */
        .glass-panel {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .layer-controls {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .control-btn {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            color: var(--light);
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            min-width: 120px;
        }

        .control-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .control-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.4);
        }

        .control-btn i {
            font-size: 1.1rem;
        }

        /* Info Panel (حذف شده از HTML اصلی اما استایل‌هایش حفظ می‌شود) */
        .info-panel {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .info-card {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.1) 0%, rgba(16, 185, 129, 0.1) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 20px;
        }

        .info-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .info-header i {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .info-header h3 {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .coordinate-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .coordinate-label {
            color: var(--gray);
            font-weight: 500;
        }

        .coordinate-value {
            color: var(--light);
            font-family: 'Courier New', monospace;
            direction: ltr;
        }

        /* Custom Map Controls */
        .leaflet-control-container .leaflet-top {
            top: 20px;
            right: 20px;
            left: auto;
        }

        .leaflet-control-zoom {
            background: var(--glass-bg) !important;
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border) !important;
            border-radius: 8px !important;
            overflow: hidden;
        }

        .leaflet-control-zoom a {
            background: transparent !important;
            border: none !important;
            color: var(--light) !important;
            font-size: 1.2rem !important;
            transition: all 0.3s ease !important;
        }

        .leaflet-control-zoom a:hover {
            background: rgba(255, 255, 255, 0.1) !important;
        }

        .leaflet-control-scale {
            background: var(--glass-bg) !important;
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border) !important;
            border-radius: 6px !important;
            color: var(--light) !important;
            margin-bottom: 20px !important;
            margin-right: 10px !important;
        }

        /* Custom Popup */
        .leaflet-popup-content-wrapper {
            background: var(--glass-bg) !important;
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border) !important;
            border-radius: 12px !important;
            color: var(--dark) !important; /* تغییر رنگ متن به تیره برای خوانایی بهتر روی پس‌زمینه شفاف */
        }

        .leaflet-popup-content h4 {
            color: #1f2937 !important; /* رنگ تیره برای عنوان */
        }

        .leaflet-popup-content p {
            color: #374151 !important; /* رنگ تیره برای متن */
        }

        .leaflet-popup-content {
            margin: 12px !important;
            font-size: 14px !important;
        }

        .leaflet-popup-tip {
            background: var(--glass-bg) !important;
            border: 1px solid var(--glass-border) !important;
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-in {
            animation: fadeIn 0.6s ease-out;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8rem;
            }

            .map-wrapper {
                height: 450px;
            }

            .layer-controls {
                top: 10px;
                left: 10px;
            }

            .control-btn {
                min-width: auto;
                padding: 8px 12px;
                font-size: 0.9rem;
            }

            .info-panel {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h1 class="text-center mb-4"><?php echo $view_title; ?></h1>

    <div class="map-wrapper glass-panel animate-in">
        <div id="map"></div>

        <div class="layer-controls">
            <button class="control-btn active" onclick="changeLayer('standard')">
                <i class="fas fa-map"></i>
                نقشه عادی
            </button>
            <button class="control-btn" onclick="changeLayer('satellite')">
                <i class="fas fa-satellite"></i>
                ماهواره‌ای
            </button>
            <button class="control-btn" onclick="changeLayer('hybrid')">
                <i class="fas fa-layer-group"></i>
                ترکیبی
            </button>
        </div>
    </div>


</div>

<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

<script>
    // مختصات پروژه (استفاده از متغیرهای PHP)
    const projectLocation = [<?php echo $project_latitude; ?>, <?php echo $project_longitude; ?>];
    let currentLayer = 'standard';
    let map, marker, infoLabel;

    // تعریف لایه‌های مختلف
    const baseLayers = {
        'standard': L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }),

        'satellite': L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
            maxZoom: 19
        }),

        'hybrid': L.tileLayer('https://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}', {
            attribution: '© Google',
            maxZoom: 20,
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
        })
    };

    // ایجاد نقشه
    function initMap() {
        map = L.map('map', {
            center: projectLocation,
            zoom: 16,
            zoomControl: false
        });

        // اضافه کردن لایه پیش‌فرض
        baseLayers.standard.addTo(map);

        // آیکون پین قرمز سفارشی
        const redPinIcon = L.divIcon({
            html: `
                <div style="
                    position: relative;
                    display: inline-block;
                ">
                    <div style="
                        width: 24px;
                        height: 24px;
                        background: #dc2626;
                        border-radius: 50% 50% 50% 0;
                        transform: rotate(-45deg);
                        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                    "></div>
                    <div style="
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%) rotate(45deg);
                        color: white;
                        font-weight: bold;
                        font-size: 14px;
                    ">📍</div>
                </div>
            `,
            className: 'red-pin-marker',
            iconSize: [30, 30],
            iconAnchor: [15, 30]
        });

        // اضافه کردن marker به نقشه
        marker = L.marker(projectLocation, {icon: redPinIcon})
            .addTo(map);

        // ایجاد لیبل متنی کنار پین
        let text = "پروژه پلاریس";
        const textLength = text.length;
        const minWidth = 80; // حداقل عرض
        const maxWidth = 200; // حداکثر عرض
        // محاسبه عرض بر اساس طول متن (تخمین)
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
                        پلاریس
                    </div>
                `,
                className: 'text-label',
                iconSize: [120, 30],
                iconAnchor: [60, -10]
            })
        }).addTo(map);

        // کنترل zoom
        L.control.zoom({
            position: 'topright'
        }).addTo(map);

        // مقیاس نقشه
        L.control.scale({
            metric: true,
            imperial: false,
            position: 'bottomright'
        }).addTo(map);

        // رویداد کلیک روی نقشه (برای دریافت مختصات جدید)
        map.on('click', function(e) {
            const newCoords = e.latlng;
            const message = `عرض: ${newCoords.lat.toFixed(6)}, طول: ${newCoords.lng.toFixed(6)}`;

            // نمایش موقعیت جدید با پاپ‌آپ
            marker.setLatLng(newCoords);
            marker.bindPopup(`
                <div style="text-align: right; direction: rtl;">
                    <h4 style="margin: 5px 0; color: #2c5282;">موقعیت جدید</h4>
                    <p style="margin: 3px 0; font-size: 12px; direction: ltr;">${message}</p>
                </div>
            `).openPopup();
        });
    }

    // تغییر لایه نقشه
    function changeLayer(layerType) {
        // حذف تمام لایه‌های پایه
        Object.values(baseLayers).forEach(layer => {
            if (map.hasLayer(layer)) {
                map.removeLayer(layer);
            }
        });

        // اضافه کردن لایه جدید
        baseLayers[layerType].addTo(map);

        // به‌روزرسانی وضعیت دکمه‌ها
        document.querySelectorAll('.control-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // پیدا کردن دکمه فعال و افزودن کلاس active
        const activeBtn = document.querySelector(`[onclick="changeLayer('${layerType}')"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }

        currentLayer = layerType;
    }

    // راه‌اندازی نقشه
    document.addEventListener('DOMContentLoaded', initMap);
</script>

</body>
</html>


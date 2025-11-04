<?php
include "partials/layout.php";
// تعریف متغیرهای سمت سرور PHP
$project_title = "موقعیت مکانی پروژه ترنج";
$project_latitude = 35.75911717811881;
$project_longitude = 51.02485854232872;
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $project_title; ?></title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
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

        /* Info Panel */
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
            color: var(--light) !important;
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
        @@keyframes fadeIn {
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
        @@media (max-width: 768px) {
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
        /* Map Container */
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

        /* Info Panel */
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
            color: var(--light) !important;
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
        @@keyframes fadeIn {
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
        @@media (max-width: 768px) {
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

        .header {
            background: linear-gradient(135deg, #2c3e50, #4a6491);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 1.5rem; /* کوچکتر کردن فونت هدر */
            margin-bottom: 0.5rem;
        }

        .header p {
            font-size: 0.9rem;
            margin-bottom: 0;
        }

        .custom-table {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 30px;
            background-color: white;
        }

        .custom-table thead {
            background: linear-gradient(135deg, #3498db, #2c3e50);
            color: white;
        }

        .custom-table th {
            font-weight: 600;
            padding: 14px;
            text-align: center;
            vertical-align: middle;
            font-size: 0.9rem; /* کوچکتر کردن فونت هدر جدول */
        }

        .custom-table td {
            padding: 12px;
            text-align: center;
            vertical-align: middle;
        }

        .badge {
            font-size: 0.85rem;
            padding: 7px 10px;
            border-radius: 5px;
        }

        @@media (max-width: 768px) {
            .custom-table

            {
                display: block;
                overflow-x: auto;
            }

            .header h1 {
                font-size: 1.3rem;
            }

            .custom-table th {
                font-size: 0.8rem;
                padding: 10px;
            }

        }
    </style>
</head>
<body>

<div class="container">
    <h1 class="text-center mb-4"><?php echo $project_title; ?></h1>

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

    <div class="info-panel glass-panel animate-in">
        <div class="info-header">
            <div class="info-icon">
                <div class="info-icon-circle">
                    <div class="header text-center">
                        <i class="bi bi-building"></i> <h5 class="card-title">مشخصات پروژه</h5>
                    </div>
                    <div class="table-responsive custom-table">
                        <table class="table table-bordered table-hover" style="direction: rtl; text-align: center;">
                            <thead>
                            <tr>
                                <th>مبلغ پرداخت جهت زمین و جواز یک عضو(تومان)</th>
                                <th>مبلغ پرداخت جهت ساخت عضو(تومان)</th>
                                <th>میزان وام اختصاص یافته(تومان)</th>
                                <th>تاریخ شروع ساخت</th>
                                <th>تعهدات اولیه</th>
                                <th>اجرا شده</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>
                                    <span class="badge bg-success">50,000,000</span>
                                </td>
                                <td>
                                    <span class="badge bg-success">61,500,000 تومان</span>
                                </td>
                                <td>
                                    <span class="badge bg-danger">-</span>
                                </td>
                                <td>
                                    <span class="badge bg-primary">1394/7/1</span>
                                </td>
                                <td>
                                    <span class="badge bg-success">40 واحد 100 متری</span>
                                </td>
                                <td>
                                    <span class="badge bg-success">40 واحد 110متری</span>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

<script>
    // مختصات پروژه
    const projectLocation = [35.75911717811881, 51.02485854232872];
    let currentLayer = 'standard';
    let map, marker;

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

        // ایجاد پین قرمز بدون کادر
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

        // اضافه کردن دایره نشانگر
        // L.circle(projectLocation, {
        //     color: '#2563eb',
        //     fillColor: 'rgba(37, 99, 235, 0.2)',
        //     fillOpacity: 0.3,
        //     radius: 150
        // }).addTo(map);

        // ایجاد لیبل متنی کنار پین
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
                        ">
                            📍 پروژه ترنج
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

        // رویداد کلیک روی نقشه
        map.on('click', function(e) {
            const newCoords = e.latlng;
            const message = `موقعیت انتخاب شده: ${newCoords.lat.toFixed(6)}, ${newCoords.lng.toFixed(6)}`;

            // نمایش موقعیت جدید
            marker.setLatLng(newCoords);
            marker.bindPopup(`
                <div style="text-align: center;">
                    <h4 style="margin: 5px 0; color: #2c5282;">موقعیت جدید</h4>
                    <p style="margin: 3px 0; font-size: 12px;">${message}</p>
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

        // پیدا کردن دکمه فعال
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
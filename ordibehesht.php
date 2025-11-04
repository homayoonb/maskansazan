<?php
include "partials/layout.php";
$project_latitude = 35.76400156982197;
$project_longitude = 51.023260084657494;
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>موقعیت مکانی پروژه اردیبهشت</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* ... (تمام استایل‌های CSS شما بدون تغییر) ... */
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
            background-color: #1f2937;
            color: var(--light);
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
            text-align: right;
            text-decoration: none;
        }

        .control-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            color: var(--light);
        }

        .control-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.4);
        }

        .control-btn i {
            font-size: 1.1rem;
        }

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
    </style>
</head>
<body>

<div class="container">
    <h1 class="text-center mb-4">موقعیت مکانی پروژه اردیبهشت</h1>

    <div class="map-wrapper glass-panel animate-in">
        <div id="map"></div>

        <div class="layer-controls">
            <a href="#" class="control-btn active" onclick="changeLayer('standard'); return false;">
                <i class="fas fa-map"></i>
                نقشه عادی
            </a>
            <a href="#" class="control-btn" onclick="changeLayer('satellite'); return false;">
                <i class="fas fa-satellite"></i>
                ماهواره‌ای
            </a>
            <a href="#" class="control-btn" onclick="changeLayer('hybrid'); return false;">
                <i class="fas fa-layer-group"></i>
                ترکیبی
            </a>
        </div>
    </div>

</div>

<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<script>
    const projectLocation = [<?php echo $project_latitude; ?>, <?php echo $project_longitude; ?>];

    let currentLayer = 'standard';
    let map, marker, infoLabel;

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

    function initMap() {
        // اکنون L تعریف شده است، زیرا فایل Leaflet.js بارگذاری خواهد شد.
        map = L.map('map', {
            center: projectLocation,
            zoom: 16,
            zoomControl: false
        });

        baseLayers.standard.addTo(map);

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

        marker = L.marker(projectLocation, { icon: redPinIcon })
            .addTo(map);

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
                    ">
                        📍 پروژه اردیبهشت
                    </div>
                `,
                className: 'text-label',
                iconSize: [120, 30],
                iconAnchor: [60, -10]
            })
        }).addTo(map)

        L.control.zoom({
            position: 'topright'
        }).addTo(map);

        L.control.scale({
            metric: true,
            imperial: false,
            position: 'bottomright'
        }).addTo(map);

        map.on('click', function(e) {
            const newCoords = e.latlng;
            const message = `موقعیت انتخاب شده: ${newCoords.lat.toFixed(6)}, ${newCoords.lng.toFixed(6)}`;

            marker.setLatLng(newCoords);
            marker.bindPopup(`
                <div style="text-align: center;">
                    <h4 style="margin: 5px 0; color: #2c5282;">موقعیت جدید</h4>
                    <p style="margin: 3px 0; font-size: 12px;">${message}</p>
                </div>
            `).openPopup();

            infoLabel.setLatLng(newCoords);
        });
    }

    function changeLayer(layerType) {
        Object.values(baseLayers).forEach(layer => {
            if (map.hasLayer(layer)) {
                map.removeLayer(layer);
            }
        });

        baseLayers[layerType].addTo(map);

        document.querySelectorAll('.control-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        const activeBtn = document.querySelector(`[onclick*="changeLayer('${layerType}')"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }

        currentLayer = layerType;
    }

    document.addEventListener('DOMContentLoaded', initMap);
</script>

</body>
</html>
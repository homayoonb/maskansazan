<?php
$project_latitude = 35.76416831617799;
$project_longitude = 51.0238270027102;
$images = [
        "Picture73.jpg"
];
$images2 = [
        "Picture74.jpg",
        "Picture75.jpg",
        "Picture76.jpg"
];
$images3 = [
        "Picture77.jpg",
        "Picture78.jpg",
        "Picture79.jpg",
        "Picture80.jpg",
        "Picture81.jpg",
        "Picture82.jpg"
];
ob_start();
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    .project-page {
        direction: rtl;
        max-width: 1320px;
        margin: 32px auto 64px;
        padding: 0 16px;
        font-family: Tahoma, Arial, sans-serif;
    }

    .project-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 20px;
        padding-bottom: 14px;
        border-bottom: 2px solid #0d6efd;
    }

    .project-heading h1 {
        margin: 0;
        color: #172554;
        font-family: "B Nazanin", Tahoma, Arial, sans-serif;
        font-size: 30px;
        font-weight: 700;
    }

    .project-date {
        flex-shrink: 0;
        color: #5f6b7a;
        font-size: 14px;
        white-space: nowrap;
    }

    .project-card {
        margin-bottom: 24px;
        background: #fff;
        border: 1px solid #e7eaf0;
        border-radius: 8px;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06);
    }

    .project-card-header,
    .gallery-title,
    .map-toolbar h2 {
        color: #172554;
        font-size: 16px;
        font-weight: 700;
    }

    .project-card-header {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 14px 18px;
        border-bottom: 1px solid #e7eaf0;
    }

    .project-card-header i,
    .gallery-title i {
        color: #0d6efd;
    }

    .project-table {
        margin: 0;
        min-width: 900px;
    }

    .project-table th,
    .project-table td {
        padding: 13px 10px;
        vertical-align: middle;
        text-align: center;
        white-space: nowrap;
    }

    .project-table .section-cell {
        width: 150px;
        background: #f4f7fb;
        color: #172554;
        font-weight: 700;
    }

    .project-table .label-cell {
        background: #f8fafc;
        color: #475569;
        font-size: 13px;
        font-weight: 700;
    }

    .map-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 18px;
        border-bottom: 1px solid #e7eaf0;
    }

    .map-toolbar h2 {
        margin: 0;
    }

    .map-actions {
        display: flex;
        gap: 6px;
    }

    .map-actions .control-btn {
        width: 38px;
        height: 36px;
        padding: 0;
    }

    #map {
        width: 100%;
        height: 440px;
    }

    .gallery-card {
        padding: 20px;
    }

    .gallery-title {
        display: flex;
        align-items: center;
        gap: 9px;
        margin: 0 0 18px;
    }

    .gallery-image {
        display: block;
        width: 100%;
        aspect-ratio: 4 / 3;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #e7eaf0;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .gallery-image:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.15);
    }

    .project-address {
        margin: 18px 0 0;
        padding-top: 16px;
        border-top: 1px solid #edf0f4;
        color: #64748b;
        font-size: 14px;
    }

    .leaflet-control-zoom {
        border: 1px solid #dbe2ea !important;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.12) !important;
    }

    .red-pin-marker {
        background: transparent;
        border: 0;
    }

    .text-label {
        background: transparent;
        border: 0;
    }

    @media (max-width: 767.98px) {
        .project-page {
            margin-top: 20px;
            padding: 0 12px;
        }

        .project-heading {
            align-items: flex-start;
            flex-direction: column;
            gap: 6px;
        }

        .project-heading h1 {
            font-size: 25px;
        }

        .project-date {
            font-size: 13px;
        }

        .project-table th,
        .project-table td {
            padding: 11px 8px;
            font-size: 13px;
        }

        #map {
            height: 330px;
        }

        .map-toolbar {
            align-items: flex-start;
            flex-direction: column;
        }

        .map-actions .control-btn {
            width: 36px;
            height: 34px;
        }

        .gallery-card {
            padding: 16px;
        }
    }
</style>

<div class="project-page">

    <header class="project-heading">
        <h1>پروژه ساباط</h1>

        <span class="project-date">
            <i class="fa-regular fa-calendar ms-1"></i>
                 تاريخ شروع ساخت ١٣٩٨/٢/١
        </span>
    </header>

    <section class="project-card" style="width:100% ">
        <div class="project-card-header">
            <i class="fa-solid fa-building"></i>
            مشخصات و هزینه‌های پروژه
        </div>

        <div class="table-responsive" >
            <table class="table table-bordered project-table">
                <tbody>
                <tr>
                    <td rowspan="2" class="section-cell">مشخصات پروژه</td>
                    <td class="label-cell">متراژ زمین</td>
                    <td class="label-cell">تعهدات اولیه</td>
                    <td class="label-cell">واحد مسکونی</td>
                    <td class="label-cell">متراژ هر واحد</td>
                    <td class="label-cell">تجاری ساخته شده</td>
                    <td class="label-cell">مجموع تجاری</td>
                    <td class="label-cell">طبقات</td>
                </tr>

                <tr>
                    <td>1,700</td>
                    <!-- ردیف دوم داده‌ها -->
                    <td>٦٠ واحد ٧٠ مترى</td>
                    <td>60</td>
                    <td>85</td>
                    <td>3</td>
                    <td>120</td>
                    <td>5</td>
                </tr>
                </tbody>
            </table>
            <br>
            <br>
            <table class="table table-responsive table-bordered project-table">
                <tbody>
                <tr>
                    <td rowspan="2" class="section-cell">هزینه و نحوه پرداخت اقساط</td>
                    <td class="label-cell">قیمت زمین</td>
                    <td colspan="2" class="label-cell">مبلغ جواز</td>
                    <td colspan="2" class="label-cell">مجموع هزینه ساخت</td>
                    <td colspan="2" class="label-cell">سهم هر عضو با وام های 70 و 50 میلیونی</td>
                </tr>

                <tr>
                    <td>4,250,000,000</td>
                    <td colspan="2">1,158,000,000</td>
                    <td colspan="2">35,000,000,000</td>
                    <td colspan="2">7,360,000,000</td>
                </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="project-card map-card">
        <div class="map-toolbar">
            <h2>
                <i class="fa-solid fa-location-dot ms-1 text-danger"></i>
                موقعیت مکانی پروژه
            </h2>

            <div class="map-actions" role="group" aria-label="انتخاب لایه نقشه">
                <button type="button"
                        class="control-btn btn btn-primary active"
                        onclick="changeLayer('standard', this)"
                        title="نقشه معمولی">
                    <i class="fas fa-map"></i>
                </button>

                <button type="button"
                        class="control-btn btn btn-outline-primary"
                        onclick="changeLayer('satellite', this)"
                        title="تصویر ماهواره‌ای">
                    <i class="fas fa-satellite"></i>
                </button>

                <button type="button"
                        class="control-btn btn btn-outline-primary"
                        onclick="changeLayer('hybrid', this)"
                        title="نقشه ترکیبی">
                    <i class="fas fa-layer-group"></i>
                </button>
            </div>
        </div>

        <div id="map"></div>
    </section>

    <section class="project-card gallery-card">
        <h2 class="gallery-title">
            <i class="fa-solid fa-images"></i>
            نمایی از پروژه ساباط
        </h2>

        <div class="row g-3">
            <?php foreach ($images as $img): ?>
                <div class="col-12 col-sm-6 col-lg-4">
                    <a href="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>" target="_blank">
                        <img
                            src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>"
                            class="gallery-image"
                            alt="نمای پروژه ساباط"
                            loading="lazy"
                        >
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <p class="project-address">
            <i class="fa-solid fa-location-dot text-danger ms-1"></i>

        </p>
    </section>

    <section class="project-card gallery-card">
        <h2 class="gallery-title">
            <i class="fa-solid fa-building"></i>
            نمایی از مشاعات پروژه ساباط
        </h2>

        <div class="row g-3">
            <?php foreach ($images2 as $img): ?>
                <div class="col-12 col-sm-6 col-lg-4">
                    <a href="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>" target="_blank">
                        <img
                            src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>"
                            class="gallery-image"
                            alt="مشاعات پروژه ساباط"
                            loading="lazy"
                        >
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    const projectLocation = [<?= $project_latitude ?>, <?= $project_longitude ?>];

    let map;
    let marker;
    let infoLabel;

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
        const mapElement = document.getElementById('map');

        if (!mapElement || typeof L === 'undefined') {
            return;
        }

        map = L.map('map', {
            center: projectLocation,
            zoom: 16,
            zoomControl: false,
            layers: [baseLayers.standard]
        });

        const redPinIcon = L.divIcon({
            html: `
                <div style="position: relative; display: inline-block;">
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
                        font-size: 14px;
                    ">●</div>
                </div>
            `,
            className: 'red-pin-marker',
            iconSize: [30, 30],
            iconAnchor: [15, 30]
        });

        marker = L.marker(projectLocation, {
            icon: redPinIcon
        }).addTo(map);

        const labelText = 'پروژه ساباط';
        const calculatedWidth = Math.min(220, Math.max(130, labelText.length * 9 + 40));

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
                        width: ${calculatedWidth}px;
                        text-align: center;
                    ">پروژه ساباط </div>
                `,
                className: 'text-label',
                iconSize: [calculatedWidth, 34],
                iconAnchor: [calculatedWidth / 2, -10]
            })
        }).addTo(map);

        L.control.zoom({
            position: 'topleft'
        }).addTo(map);

        L.control.scale({
            metric: true,
            imperial: false,
            position: 'bottomright'
        }).addTo(map);

        setTimeout(function () {
            map.invalidateSize();
        }, 250);
    }

    function changeLayer(layerType, btnEl) {
        if (!map || !baseLayers[layerType]) {
            return;
        }

        Object.values(baseLayers).forEach(function (layer) {
            if (map.hasLayer(layer)) {
                map.removeLayer(layer);
            }
        });

        baseLayers[layerType].addTo(map);

        document.querySelectorAll('.control-btn').forEach(function (btn) {
            btn.classList.remove('active', 'btn-primary');
            btn.classList.add('btn-outline-primary');
        });

        if (btnEl) {
            btnEl.classList.add('active', 'btn-primary');
            btnEl.classList.remove('btn-outline-primary');
        }
    }

    document.addEventListener('DOMContentLoaded', initMap);
</script>

<?php
$content = ob_get_clean();
include '../partials/layout.php';
?>

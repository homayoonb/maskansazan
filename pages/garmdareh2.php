<?php
$images = ["1.jpg","2.jpg","4.jpg"];
$images2 = ["Picture3.jpg","Picture4.jpg","Picture5.jpg"];

$title = "موقعیت مکانی پروژه نقشه برداری ۲";
$latitude = "35.74939035365549";
$longitude = "51.060020271164355";
ob_start();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $title ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        /* ===== Card ===== */
        /*.project-card {*/
        /*    direction: rtl;*/
        /*    max-width: 1200px;*/
        /*    margin: 32px auto;*/
        /*    background: #fff;*/
        /*    border-radius: 18px;*/
        /*    box-shadow: 0 12px 30px rgba(0,0,0,.08);*/
        /*    overflow: hidden;*/
        /*    font-family: Vazirmatn, IRANSans, Tahoma, sans-serif;*/
        /*}*/

        /* ===== Header ===== */
        /*.project-header {*/
        /*    background: linear-gradient(135deg, #8fd9f7, var(--primary));*/
        /*    padding: 22px;*/
        /*    text-align: center;*/
        /*}*/

        /*.project-header h2 {*/
        /*    margin: 0;*/
        /*    font-size: 22px;*/
        /*    font-weight: 700;*/
        /*    color: #000;*/
        /*}*/

        /*.project-header p {*/
        /*    margin-top: 8px;*/
        /*    font-size: 14px;*/
        /*    color: #003344;*/
        /*}*/

        /* ===== Table Wrapper ===== */
        /*.project-table-wrapper {*/
        /*    overflow-x: auto;*/
        /*    background: var(--bg-soft);*/
        /*}*/

        /* ===== Table ===== */
        /*.project-table {*/
        /*    width: 100%;*/
        /*    min-width: 900px;*/
        /*    border-collapse: collapse;*/
        /*    background: #fff;*/
        /*}*/

        /* ===== Cells ===== */
        /*.project-table th,*/
        /*.project-table td {*/
        /*    border: 1px solid var(--border);*/
        /*    padding: 14px 10px;*/
        /*    text-align: center;*/
        /*    font-size: 14px;*/
        /*    color: var(--text-main);*/
        /*    white-space: nowrap;*/
        /*}*/

        /* ===== Head ===== */
        /*.project-table thead th {*/
        /*    background: #f1f1f1;*/
        /*    font-weight: 600;*/
        /*}*/

        /* ===== Section Row ===== */
        /*.section-row th {*/
        /*    background: #f5f7fa;*/
        /*    font-weight: 700;*/
        /*}*/

        /* ===== Zebra ===== */
        /*.project-table tbody tr:nth-child(even):not(.section-row) {*/
        /*    background: #fafafa;*/
        /*}*/

        /* ===== Hover ===== */
        /*.project-table tbody tr:hover:not(.section-row) {*/
        /*    background: #eef7ff;*/
        /*}*/

        /* ==========================
           ✅ Mobile UX (Card Style)
        ========================== */
        /*@media (max-width: 768px) {*/
        /*    .project-card {*/
        /*        border-radius: 14px;*/
        /*        margin: 20px 12px;*/
        /*    }*/

        /*    .project-header h2 {*/
        /*        font-size: 18px;*/
        /*    }*/

        /*    .project-header p {*/
        /*        font-size: 13px;*/
        /*    }*/

        /*    .project-table {*/
        /*        min-width: 700px;*/
        /*    }*/
        /*}*/


        /* ===============================
           Map Section
        ================================ */
        /*.map-section {*/
        /*    display: flex;*/
        /*    justify-content: flex-end;*/
        /*    align-items: flex-end;*/
        /*    width: 100%;*/
        /*    min-height: 500px;*/
        /*    padding: 20px;*/
        /*    box-sizing: border-box;*/
        /*}*/

        /* ===============================
           Map Card
        ================================ */
        /*.map-wrapper {*/
        /*    position: relative;*/
        /*    width: 420px;*/
        /*    height: 320px;*/
        /*    border-radius: 16px;*/
        /*    overflow: hidden;*/
        /*    box-shadow: 0 12px 30px rgba(0,0,0,.25);*/
        /*    direction: rtl;*/
        /*}*/

        /* ===============================
           Main Map
        ================================ */
        /*#map {*/
        /*    width: 100%;*/
        /*    height: 100%;*/
        /*}*/

        /* ===============================
           Mini Map
        ================================ */
        /*#map-mini {*/
        /*    position: absolute;*/
        /*    bottom: 12px;*/
        /*    left: 12px;*/
        /*    width: 120px;*/
        /*    height: 90px;*/
        /*    border-radius: 10px;*/
        /*    border: 2px solid #fff;*/
        /*    background: #fff;*/
        /*    z-index: 500;*/
        /*}*/

        /* ===============================
           Layer Controls
        ================================ */
        /*.layer-controls {*/
        /*    position: absolute;*/
        /*    top: 16px;*/
        /*    right: 16px;*/
        /*    z-index: 1000;*/
        /*    display: flex;*/
        /*    flex-direction: column;*/
        /*    gap: 8px;*/
        /*}*/

        /*.control-btn {*/
        /*    background: rgba(255,255,255,.9);*/
        /*    border: none;*/
        /*    padding: 8px 12px;*/
        /*    border-radius: 8px;*/
        /*    cursor: pointer;*/
        /*    font-size: 13px;*/
        /*}*/

        /*.control-btn.active {*/
        /*    background: #2563eb;*/
        /*    color: #fff;*/
        /*}*/

        /* ===============================
           Coordinates
        ================================ */
        /*.coords-box {*/
        /*    position: absolute;*/
        /*    bottom: 12px;*/
        /*    right: 12px;*/
        /*    background: rgba(0,0,0,.6);*/
        /*    color: #fff;*/
        /*    padding: 6px 10px;*/
        /*    border-radius: 6px;*/
        /*    font-size: 12px;*/
        /*    z-index: 1000;*/
        /*}*/

        /* ===============================
           Leaflet Fixes
        ================================ */
        /*.leaflet-marker-icon,*/
        /*.leaflet-marker-shadow {*/
        /*    overflow: visible !important;*/
        /*}*/

        /* ===============================
           Zoom (LEFT)
        ================================ */
        /*.leaflet-top.leaflet-right {*/
        /*    right: auto !important;*/
        /*    left: 14px !important;*/
        /*}*/

        /*.leaflet-control-zoom {*/
        /*    border-radius: 10px;*/
        /*    overflow: hidden;*/
        /*    box-shadow: 0 6px 18px rgba(0,0,0,.25);*/
        /*}*/

        /*.leaflet-control-zoom a {*/
        /*    width: 34px;*/
        /*    height: 34px;*/
        /*    line-height: 34px;*/
        /*    background: #fff !important;*/
        /*    color: #111 !important;*/
        /*    border: none !important;*/
        /*}*/

        /*.leaflet-control-zoom a:hover {*/
        /*    background: #2563eb !important;*/
        /*    color: #fff !important;*/
        /*}*/

        /* ===============================
           Responsive
        ================================ */
        /*@media (max-width: 768px) {*/
        /*    .map-wrapper {*/
        /*        width: 100%;*/
        /*        height: 360px;*/
        /*    }*/
        /*}*/
        /* ===============================
           Glass Info Panel
        ================================ */
        /*.info-panel {*/
        /*    margin: 30px auto;*/
        /*    max-width: 1100px;*/
        /*    padding: 24px;*/
        /*    border-radius: 20px;*/
        /*    background: rgba(255, 255, 255, 0.18);*/
        /*    backdrop-filter: blur(14px);*/
        /*    -webkit-backdrop-filter: blur(14px);*/
        /*    border: 1px solid rgba(255, 255, 255, 0.25);*/
        /*    box-shadow: 0 20px 45px rgba(0, 0, 0, 0.18);*/
        /*}*/

        /* کل ظرف پنل */
        /*.info-panel.glass-panel {*/
        /*    background: rgba(255, 255, 255, 0.8);*/
        /*    backdrop-filter: blur(15px);*/
        /*    -webkit-backdrop-filter: blur(15px);*/
        /*    border: 1px solid rgba(255, 255, 255, 0.4);*/
        /*    border-radius: 24px;*/
        /*    padding: 30px;*/
        /*    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);*/
        /*    margin: 20px auto;*/
        /*    max-width: 1200px;*/
        /*    direction: rtl;*/
        /*}*/

        /* هدر و آیکون */
        /*.info-header {*/
        /*    text-align: center;*/
        /*    margin-bottom: 25px;*/
        /*}*/

        /*.info-header h5 {*/
        /*    font-weight: 800;*/
        /*    color: #2c3e50;*/
        /*    font-size: 1.25rem;*/
        /*    display: flex;*/
        /*    align-items: center;*/
        /*    justify-content: center;*/
        /*    gap: 12px;*/
        /*}*/

        /*.info-header i {*/
        /*    color: #3498db;*/
        /*    font-size: 1.6rem;*/
        /*}*/

        /* استایل جدول در دسکتاپ */
        /*.custom-table {*/
        /*    border: none;*/
        /*    border-radius: 15px;*/
        /*    overflow: hidden;*/
        /*}*/

        /*.custom-table table {*/
        /*    margin-bottom: 0;*/
        /*    border: none !important;*/
        /*}*/

        /*.custom-table thead th {*/
        /*    background: rgba(248, 249, 250, 0.7) !important;*/
        /*    color: #5d6d7e;*/
        /*    font-weight: 600;*/
        /*    font-size: 0.9rem;*/
        /*    padding: 15px;*/
        /*    border-bottom: 2px solid #edf2f7 !important;*/
        /*    white-space: nowrap;*/
        /*}*/

        /*.custom-table tbody td {*/
        /*    padding: 20px 15px;*/
        /*    vertical-align: middle;*/
        /*    border-bottom: 1px solid #f0f4f8;*/
        /*    color: #2d3748;*/
        /*    font-weight: 500;*/
        /*}*/

        /* نشان‌ها (Badges) */
        /*.badge {*/
        /*    padding: 8px 14px;*/
        /*    border-radius: 10px;*/
        /*    font-weight: 600;*/
        /*    font-size: 0.85rem;*/
        /*    box-shadow: 0 4px 10px rgba(0,0,0,0.05);*/
        /*}*/

        /*.bg-success { background-color: #2ecc71 !important; }*/
        /*.bg-primary { background-color: #3498db !important; }*/
        /*.bg-danger { background-color: #e74c3c !important; }*/

        /* 📱 واکنش‌گرایی مخصوص موبایل (حالت کارت) */
        /*@media (max-width: 991px) {*/
        /*    .custom-table thead {*/
        display: none; /* مخفی کردن هدر در موبایل */
        /*    }*/

        /*    .custom-table table, */
        /*    .custom-table tbody, */
        /*    .custom-table tr, */
        /*    .custom-table td {*/
        /*        display: block;*/
        /*        width: 100%;*/
        /*    }*/

        /*    .custom-table tr {*/
        /*        margin-bottom: 20px;*/
        /*        border: 1px solid #edf2f7;*/
        /*        border-radius: 15px;*/
        /*        background: #fff;*/
        /*        padding: 10px;*/
        /*    }*/

        /*    .custom-table td {*/
        /*        text-align: left;*/
        /*        padding-left: 50%;*/
        /*        position: relative;*/
        /*        border-bottom: 1px solid #f7fafc;*/
        /*        display: flex;*/
        /*        justify-content: space-between;*/
        /*        align-items: center;*/
        /*        text-align: right;*/
        /*    }*/

        /* اضافه کردن عنوان ستون‌ها در کنار مقادیر در موبایل */
        /*    .custom-table td::before {*/
        /*        content: attr(data-label);*/
        /*        font-weight: 700;*/
        /*        color: #718096;*/
        /*        font-size: 0.85rem;*/
        /*    }*/

        /*    .custom-table td:last-child {*/
        /*        border-bottom: 0;*/
        /*    }*/
        /*.project-wrapper {*/
        /*    max-width: 1200px;*/
        /*    margin: 80px auto;*/
        /*    padding: 50px 40px;*/
        /*    background: rgba(255,255,255,0.75);*/
        /*    backdrop-filter: blur(12px);*/
        /*    border-radius: 24px;*/
        /*    box-shadow: 0 30px 60px rgba(0,0,0,0.12);*/
        /*}*/

        /* ===== Title ===== */
        /* ===============================
           Global Container
        ================================ */
        /*.container {*/
        /*    max-width: 1400px;*/
        /*    margin: 0 auto;*/
        /*    padding: 40px 20px;*/
        /*}*/

        /* ===============================
           Project Wrapper (Gallery)
        ================================ */
        /*.project-title {*/
        /*    position: relative;*/
        /*    text-align: center;*/
        /*    font-size: 36px;*/
        /*    font-weight: 900;*/
        /*    letter-spacing: 0.5px;*/
        /*    color: #2b1b3f;*/
        /*    margin-bottom: 60px;*/
        /*    padding-bottom: 10px;*/
        /*}*/

        /* خط گرادیانی زیر عنوان */
        /*.project-title .title-line {*/
        /*    display: block;*/
        /*    width: 180px;*/
        /*    height: 5px;*/
        /*    margin: 18px auto 0;*/
        /*    border-radius: 6px;*/
        /*    background: linear-gradient(*/
        /*        90deg,*/
        /*        #6a2ca0,*/
        /*        #8e44ad,*/
        /*        #00bcd4*/
        /*    );*/
        /*    box-shadow: 0 8px 20px rgba(0, 188, 212, 0.35);*/
        /*    position: relative;*/
        /*    overflow: hidden;*/
        /*}*/

        /* افکت نور متحرک روی خط */
        /*.project-title .title-line::after {*/
        /*    content: "";*/
        /*    position: absolute;*/
        /*    top: 0;*/
        /*    left: -40%;*/
        /*    width: 40%;*/
        /*    height: 100%;*/
        /*    background: linear-gradient(*/
        /*        120deg,*/
        /*        transparent,*/
        /*        rgba(255,255,255,0.8),*/
        /*        transparent*/
        /*    );*/
        /*    animation: shine 2.8s infinite;*/
        /*}*/

        /* افکت ظریف هنگام هاور */
        /*.project-title:hover {*/
        /*    color: #6a2ca0;*/
        /*    transition: 0.4s ease;*/
        /*}*/

        /*.project-title:hover .title-line {*/
        /*    width: 220px;*/
        /*    transition: 0.4s ease;*/
        /*}*/

        /* انیمیشن ورود */
        /*.project-title {*/
        /*    opacity: 0;*/
        /*    transform: translateY(30px);*/
        /*    animation: titleFadeUp 0.9s ease forwards;*/
        /*}*/

        /* Animations */
        /*@keyframes titleFadeUp {*/
        /*    to {*/
        /*        opacity: 1;*/
        /*        transform: translateY(0);*/
        /*    }*/
        /*}*/

        /*@keyframes shine {*/
        /*    0%   { left: -40%; }*/
        /*    100% { left: 120%; }*/
        /*}*/

        /* Responsive */
        /*@media (max-width: 768px) {*/
        /*    .project-title {*/
        /*        font-size: 24px;*/
        /*    }*/

        /*    .project-title .title-line {*/
        /*        width: 120px;*/
        /*    }*/
        /*}*/


        /* ===============================
           Gallery Grid
        ================================ */
        /*.project-images {*/
        /*    display: grid;*/
        /*    grid-template-columns: repeat(3, 1fr);*/
        /*    gap: 30px;*/
        /*}*/

        /*.image-card {*/
        /*    position: relative;*/
        /*    overflow: hidden;*/
        /*    border-radius: 24px;*/
        /*    cursor: pointer;*/
        /*    box-shadow: 0 25px 50px rgba(0,0,0,0.25);*/
        /*    transform: translateY(40px);*/
        /*    opacity: 0;*/
        /*    animation: fadeUp 0.9s ease forwards;*/
        /*    animation-delay: calc(var(--i) * 0.15s);*/
        /*}*/

        /*.image-card img {*/
        /*    width: 100%;*/
        /*    height: 100%;*/
        /*    object-fit: cover;*/
        /*    transition: 0.7s ease;*/
        /*}*/

        /*.image-card:hover img {*/
        /*    transform: scale(1.15) rotate(0.5deg);*/
        /*}*/

        /*.image-overlay {*/
        /*    position: absolute;*/
        /*    inset: 0;*/
        /*    background: linear-gradient(to top, rgba(0,0,0,0.75), rgba(0,0,0,0.15));*/
        /*    display: flex;*/
        /*    justify-content: center;*/
        /*    align-items: flex-end;*/
        /*    padding-bottom: 30px;*/
        /*    opacity: 0;*/
        /*    transition: 0.4s ease;*/
        /*}*/

        /*.image-card:hover .image-overlay {*/
        /*    opacity: 1;*/
        /*}*/

        /*.image-overlay span {*/
        /*    padding: 10px 26px;*/
        /*    border-radius: 40px;*/
        /*    font-size: 14px;*/
        /*    color: #fff;*/
        /*    background: rgba(255,255,255,0.2);*/
        /*    backdrop-filter: blur(10px);*/
        /*    letter-spacing: 1px;*/
        /*}*/

        /* ===============================
           Location
        ================================ */
        /*.project-location {*/
        /*    margin-top: 45px;*/
        /*    text-align: center;*/
        /*    font-size: 18px;*/
        /*    font-weight: 700;*/
        /*    color: #444;*/
        /*}*/

        /* ===============================
           Project Card (Info Section)
        ================================ */
        /*.project-card {*/
        /*    margin: 90px auto;*/
        /*    padding: 45px;*/
        /*    background: #ffffff;*/
        /*    border-radius: 26px;*/
        /*    box-shadow: 0 30px 60px rgba(0,0,0,0.12);*/
        /*}*/

        /*.project-header {*/
        /*    text-align: center;*/
        /*    margin-bottom: 35px;*/
        /*}*/

        /*.project-header h2 {*/
        /*    font-size: 30px;*/
        /*    font-weight: 900;*/
        /*    color: #2c2c54;*/
        /*}*/

        /*.project-header p {*/
        /*    margin-top: 10px;*/
        /*    font-size: 15px;*/
        /*    color: #666;*/
        /*}*/

        /* ===============================
           Table Styling
        ================================ */
        /*.project-table-wrapper {*/
        /*    overflow-x: auto;*/
        /*}*/

        /*.project-table {*/
        /*    width: 100%;*/
        /*    border-collapse: collapse;*/
        /*    min-width: 900px;*/
        /*}*/

        /*.project-table thead th {*/
        /*    background: linear-gradient(90deg,#6a2ca0,#00bcd4);*/
        /*    color: #fff;*/
        /*    padding: 16px;*/
        /*    font-size: 14px;*/
        /*}*/

        /*.project-table tbody td,*/
        /*.project-table tbody th {*/
        /*    padding: 15px;*/
        /*    text-align: center;*/
        /*    font-size: 14px;*/
        /*    border-bottom: 1px solid #e6e6e6;*/
        /*}*/

        /*.project-table tbody tr:hover {*/
        /*    background: #f5f9ff;*/
        /*}*/

        /*.section-row th {*/
        /*    background: #f1f3f6;*/
        /*    font-weight: 800;*/
        /*    color: #333;*/
        /*}*/

        /* ===============================
           Map Wrapper
        ================================ */
        /*.map-wrapper {*/
        /*    margin: 100px auto 0;*/
        /*    position: relative;*/
        /*    height: 520px;*/
        /*    border-radius: 28px;*/
        /*    overflow: hidden;*/
        /*    box-shadow: 0 30px 60px rgba(0,0,0,0.25);*/
        /*}*/

        /*#map {*/
        /*    width: 100%;*/
        /*    height: 100%;*/
        /*}*/

        /* Layer Controls */
        /*.layer-controls {*/
        /*    position: absolute;*/
        /*    top: 25px;*/
        /*    right: 25px;*/
        /*    display: flex;*/
        /*    gap: 10px;*/
        /*    z-index: 999;*/
        /*}*/

        /*.control-btn {*/
        /*    padding: 10px 18px;*/
        /*    border-radius: 30px;*/
        /*    border: none;*/
        /*    font-size: 13px;*/
        /*    cursor: pointer;*/
        /*    background: rgba(255,255,255,0.85);*/
        /*    backdrop-filter: blur(10px);*/
        /*    transition: 0.3s ease;*/
        /*}*/

        /*.control-btn.active,*/
        /*.control-btn:hover {*/
        /*    background: linear-gradient(90deg,#6a2ca0,#00bcd4);*/
        /*    color: #fff;*/
        /*}*/

        /* ===============================
           Animations
        ================================ */
        /*@keyframes fadeUp {*/
        /*    to {*/
        /*        opacity: 1;*/
        /*        transform: translateY(0);*/
        /*    }*/
        /*}*/

        /* ===============================
           Responsive
        ================================ */
        /*@media (max-width: 992px) {*/
        /*    .project-images {*/
        /*        grid-template-columns: 1fr;*/
        /*    }*/

        /*    .project-wrapper,*/
        /*    .project-card {*/
        /*        padding: 30px 25px;*/
        /*    }*/

        /*    .project-title {*/
        /*        font-size: 24px;*/
        /*    }*/

        /*    .map-wrapper {*/
        /*        height: 380px;*/
        /*    }*/
        /*}*/

        /* === FRAME BOX === */
        /*.frame-box {*/
        /*    background: linear-gradient(180deg, #ffffff, #f5f7fa);*/
        /*    border-radius: 28px;*/
        /*    padding: 50px 40px 60px;*/
        /*    box-shadow:*/
        /*        0 20px 50px rgba(0,0,0,0.08),*/
        /*        inset 0 0 0 1px rgba(255,255,255,0.6);*/
        /*    position: relative;*/
        /*    overflow: hidden;*/
        /*}*/

        /* subtle decorative corners */
        /*.frame-box::before,*/
        /*.frame-box::after {*/
        /*    content: "";*/
        /*    position: absolute;*/
        /*    width: 120px;*/
        /*    height: 120px;*/
        /*    border-radius: 50%;*/
        /*    background: radial-gradient(circle, rgba(30,144,255,0.15), transparent 70%);*/
        /*}*/

        /*.frame-box::before {*/
        /*    top: -40px;*/
        /*    right: -40px;*/
        /*}*/

        /*.frame-box::after {*/
        /*    bottom: -40px;*/
        /*    left: -40px;*/
        /*}*/

        /* === IMAGES GRID === */
        /*.project-images {*/
        /*    display: grid;*/
        /*    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));*/
        /*    gap: 28px;*/
        /*    margin-top: 30px;*/
        /*}*/

        /* === IMAGE CARD === */
        /*.image-card {*/
        /*    position: relative;*/
        /*    overflow: hidden;*/
        /*    border-radius: 18px;*/
        /*    background: #000;*/
        /*    box-shadow: 0 15px 35px rgba(0,0,0,0.18);*/
        /*    transform: translateY(40px);*/
        /*    opacity: 0;*/
        /*    animation: frameFadeUp 0.8s ease forwards;*/
        /*    animation-delay: calc(var(--i) * 0.12s);*/
        /*}*/

        /*.image-card img {*/
        /*    width: 100%;*/
        /*    height: 250px;*/
        /*    object-fit: cover;*/
        /*    transition: transform 0.6s ease;*/
        /*}*/

        /* overlay */
        /*.image-overlay {*/
        /*    position: absolute;*/
        /*    inset: 0;*/
        /*    background: linear-gradient(*/
        /*        to top,*/
        /*        rgba(0,0,0,0.6),*/
        /*        rgba(0,0,0,0.15),*/
        /*        transparent*/
        /*    );*/
        /*    opacity: 0;*/
        /*    transition: opacity 0.4s ease;*/
        /*}*/

        /* hover */
        /*.image-card:hover img {*/
        /*    transform: scale(1.12);*/
        /*}*/

        /*.image-card:hover .image-overlay {*/
        /*    opacity: 1;*/
        /*}*/

        /* === LOCATION === */
        /*.project-location {*/
        /*    margin-top: 35px;*/
        /*    text-align: center;*/
        /*    font-size: 16px;*/
        /*    color: #444;*/
        /*}*/

        /* animation */
        /*@keyframes frameFadeUp {*/
        /*    from {*/
        /*        opacity: 0;*/
        /*        transform: translateY(50px);*/
        /*    }*/
        /*    to {*/
        /*        opacity: 1;*/
        /*        transform: translateY(0);*/
        /*    }*/
        /*}*/

        /* responsive */
        /*@media (max-width: 600px) {*/
        /*    .frame-box {*/
        /*        padding: 35px 20px 45px;*/
        /*    }*/

        /*    .image-card img {*/
        /*        height: 220px;*/
        /*    }*/
        /*}*/

        /*.info-box,*/
        /*.price-box {*/
        /*    background: #f8f9fa;*/
        /*    border-radius: 16px;*/
        /*    padding: 18px;*/
        /*    text-align: center;*/
        /*    height: 100%;*/
        /*    box-shadow: 0 10px 25px rgba(0,0,0,0.06);*/
        /*}*/

        /*.info-box span,*/
        /*.price-box span {*/
        /*    display: block;*/
        /*    font-size: 13px;*/
        /*    color: #6c757d;*/
        /*    margin-bottom: 6px;*/
        /*}*/

        /*.info-box strong,*/
        /*.price-box strong {*/
        /*    display: block;*/
        /*    font-size: 22px;*/
        /*    color: #212529;*/
        /*}*/

        /*.info-box small,*/
        /*.price-box small {*/
        /*    font-size: 12px;*/
        /*    color: #868e96;*/
        /*}*/

        /*.info-box.beige {*/
        /*    background: #fff7e6;*/
        /*}*/

        /*.price-box.highlight {*/
        /*    background: linear-gradient(135deg, #0d6efd, #0b5ed7);*/
        /*    color: #fff;*/
        /*}*/

        /*.price-box.highlight span,*/
        /*.price-box.highlight small,*/
        /*.price-box.highlight strong {*/
        /*    color: #fff;*/
        /*}*/

        /*@media (max-width: 576px) {*/
        /*    .info-box strong,*/
        /*    .price-box strong {*/
        /*        font-size: 18px;*/
        /*    }*/
        /*}*/



    </style>
</head>

<body>

<!-- فقط یک container، و همه چیز داخلش -->
<div class="container my-5">
    <div class="container my-5 font-vazir" dir="rtl">
        <div class="container">
            <div class="container">
                <table class="table table-bordered text-center align-middle">
                    <!-- عنوان اصلی -->
                    <tr style="background-color: #0d6efd; color: white;">
                        <th colspan="8" style="padding: 10px;">
                            پروژه نقشه برداری 2
                            <br>
                            تاریخ شروع ساخت 1392/3/1
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
                        <td>1,000</td>
                        <!-- ردیف دوم داده‌ها -->
                        <td>30 واحد 75 متری</td>
                        <td>35</td>
                        <td>86</td>
                        <td>2</td>
                        <td>100</td>
                        <td>9</td>
                    </tr>
                    <!-- استفاده از rowspan واقعی در یک بخش واحد -->
                    <tr>
                        <td rowspan="2" class="align-middle" style="background-color: #f8f9fa; font-weight: bold; width: 150px;">
                            هزینه و نحوه پرداخت اقساط<br>
                        </td>
                        <td class="bg-light" style="font-weight: bold;">قیمت زمین(تومان)</td>
                        <td colspan="2" class="bg-light" style="font-weight: bold;">مبلغ جواز(تومان)</td>
                        <td colspan="2" class="bg-light" style="font-weight: bold;">مجموع هزینه  ساخت</td>
                        <td colspan="2" class="bg-light" style="font-weight: bold;">سهم هر عضو با وام 25 میلیون تومان</td>
                    </tr>
                    <tr>
                        <td>550,000,000</td>
                        <!-- ردیف دوم داده‌ها -->
                        <td colspan="2">214,000,000</td>
                        <td colspan="2">3,044,275,787</td>
                        <td colspan="2">89,000,000</td>
                    </tr>
                </table>
            </div>

        </div>
        <div class="container">
            <div class="row flex-row-reverse">
                <div class="col-lg-4">
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
    </div>
    <div class="card shadow-lg border-0 rounded-4 mb-5">
        <div class="card-body p-4 p-md-5">

            <h4 class="text-center fw-bold mb-4">
                نمایی از پروژه نقشه برداری ۲
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
                پروژه نقشه برداری ۲ واقع در شهرک وحدت، خیابان فیروزآبادی، قطعه ۷۰۳۱
            </p>

        </div>
    </div>

    <div class="card shadow-lg border-0 rounded-4 mb-5">
        <div class="card-body p-4 p-md-5">

            <h4 class="text-center fw-bold mb-4">
                نمایی از مشاعات پروژه نقشه برداری ۲
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
</div>



<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const projectLocation = [<?= $latitude ?>, <?= $longitude ?>];
    let map, marker, infoLabel;

    const baseLayers = {
        standard: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }),

        satellite: L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            { attribution: 'Tiles © Esri', maxZoom: 19 }
        ),

        hybrid: L.tileLayer('https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
            subdomains: ['mt0','mt1','mt2','mt3'],
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

        const text = "پروژه نقشه برداری ۲";
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

    function changeLayer(layerType, btnEl) {
        Object.values(baseLayers).forEach(layer => {
            if (map.hasLayer(layer)) map.removeLayer(layer);
        });

        baseLayers[layerType].addTo(map);

        document.querySelectorAll('.control-btn').forEach(btn => {
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
include("../partials/layout.php");
include("../partials/footer.php");
?>

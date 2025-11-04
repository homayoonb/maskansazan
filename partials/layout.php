<?php
session_start();
if(!isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === false){
    header('location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo isset($title) ? $title : 'SurveyorHousingDevelopersGroup'; ?></title>

    <link rel="stylesheet" href="/maskan-sazan-naghsheh-bardary/lib/bootstrap/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="/maskan-sazan-naghsheh-bardary/css/site.css" />
    <link rel="stylesheet" href="/maskan-sazan-naghsheh-bardary/fontsource/vazir/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/vazir-font@29.1.0/dist/font-face.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Styles بخش اضافی -->
    <?php if (isset($extraStyles)) echo $extraStyles; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <style>
        @import url('https://cdn.jsdelivr.net/font-face/vazirmatn/vazirmatn-font-face.css');
        #mainCarousel { transition: all 0.8s ease-in-out; overflow: hidden; }
        #mainCarousel.hidden { opacity: 0; height: 0; transform: scale(0.8); pointer-events: none; }
        #loginButton { position: absolute; top: 20px; right: 20px; z-index: 1000; padding: 12px 24px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); color: white; border: none;
            border-radius: 25px; cursor: pointer; font-family: 'Vazir', sans-serif; font-size: 16px;
            font-weight: bold; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s ease; }
        #loginButton:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.3); }
        #mainContent { display: none; opacity: 0; transition: opacity 0.8s ease; }
        #mainContent.visible { display: block; opacity: 1; }
    </style>
</head>

<body>
<header>
    <!-- نوار ناوبری -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark custom-navbar-style">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <a class="navbar-brand" href="/maskan-sazan-naghsheh-bardary/index.php">سامانه مسکن سازان نقشه برداری</a>

            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">

                    <li class="nav-item"><a class="nav-link active custom-active-link" href="/maskan-sazan-naghsheh-bardary/index.php">صفحه اصلی</a></li>

                    <li class="nav-item"><a class="nav-link" href="/maskan-sazan-naghsheh-bardary/groupIntroduction.php">معرفی گروه</a></li>
                    <li class="nav-item"><a class="nav-link" href="/maskan-sazan-naghsheh-bardary/goals.php">اهداف</a></li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#"  id="projectsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">پروژه‌ها</a>
                        <ul class="dropdown-menu" aria-labelledby="projectsDropdown">
                            <li class="nav-item"><a class="dropdown-item" href="/maskan-sazan-naghsheh-bardary/garmdareh1.php">نقشه برداری ۱</a></li>
                            <li><a class="dropdown-item"  href="/maskan-sazan-naghsheh-bardary/garmdareh2.php">نقشه برداری ۲</a></li>
                            <li><a class="dropdown-item" href="/maskan-sazan-naghsheh-bardary/toranj.php" >ترنج</a></li>
                            <li><a class="dropdown-item" href="/maskan-sazan-naghsheh-bardary/ordibehesht.php" >اردیبهشت</a></li>
                            <li><a class="dropdown-item" href="/maskan-sazan-naghsheh-bardary/polaris.php" >پلاریس</a></li>
                            <li><a class="dropdown-item" href="/maskan-sazan-naghsheh-bardary/sabat.php">ساباط</a></li>
                            <li><a class="dropdown-item" href="/maskan-sazan-naghsheh-bardary/mehr.php" >مهر</a></li>
                        </ul>
                    </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="membersDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                اعضا
                            </a>
                            <ul class="dropdown-menu">
                                <li class="nav-item dropdown"><a href="/maskan-sazan-naghsheh-bardary/groupmembers.php" class="dropdown-item" >ورود اعضا پروژه مهر</a></li>
                                <li class="nav-item dropdown"><a class="dropdown-item" >ثبت نام عضو جدید</a></li>
                            </ul>
                        </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- اسلایدر -->
    <div id="mainCarousel" class="carousel slide" data-bs-ride="carousel" dir="rtl">
        <div class="carousel-inner">
            <?php
            $slides = [
                ["1.jpg", "پروژه گرمدره شماره 1", "توضیحات پروژه اول"],
                ["2.jpg", "پروژه گرمدره شماره 2", "توضیحات پروژه دوم"],
                ["3.jpg", "پروژه نگارستان(ترنج) 1", "توضیحات پروژه نگارستان(ترنج) 1"],
                ["4.jpg", "پروژه نگارستان(ترنج) 2", "توضیحات پروژه نگارستان(ترنج) 2"],
                ["5.jpg", "پروژه نگارستان(ترنج) 3", "توضیحات پروژه نگارستان(ترنج) 3"],
                ["7.jpg", "پروژه پلاریس 1", "توضیحات پروژه پلاریس 1"],
                ["8.jpg", "پروژه پلاریس 2", "توضیحات پروژه پلاریس 2"],
                ["9.jpg", "پروژه اردیبهشت 1", "توضیحات پروژه اردیبهشت 1"],
                ["10.jpg", "پروژه اردیبهشت 2", "توضیحات پروژه اردیبهشت 2"],
                ["Picture11.jpg", "پروژه ساباط 1", "توضیحات پروژه ساباط 1"],
                ["Picture12.jpg", "پروژه ساباط 2", "توضیحات پروژه ساباط 2"],
                ["Picture13.jpg", "پروژه ساباط 3", "توضیحات پروژه ساباط 3"],
                ["Picture14.jpg", "پروژه ساباط 4", "توضیحات پروژه ساباط 4"],
                ["Picture15.jpg", "پروژه ساباط 5", "توضیحات پروژه ساباط 5"],
            ];

            $active = true;
            foreach ($slides as $slide):
                ?>
                <div class="carousel-item <?php echo $active ? 'active' : ''; ?>">
                    <img src="images/<?php echo $slide[0]; ?>" class="d-block w-100" alt="<?php echo $slide[1]; ?>" style="height: 400px; object-fit: cover;">
                    <div class="carousel-caption d-none d-md-block" style="text-align:right; background:transparent; max-width:50%;">
                        <h3 style="color:whitesmoke;"><?php echo $slide[1]; ?></h3>
                        <p style="color:whitesmoke;"><?php echo $slide[2]; ?></p>
                    </div>
                </div>
                <?php
                $active = false;
            endforeach;
            ?>
        </div>

        <button class="carousel-control-prev" type="button" data-bs-target="#mainCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#mainCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>
</header>

<!-- محتوای صفحات -->
<div class="container">
    <main role="main" class="pb-3">
        <?php echo isset($content) ? $content : ''; ?>
    </main>
</div>

<footer class="fixed-footer">
    <div class="footer-container">
        <div class="footer-right">
            <div class="company-name">گروه مهندسین نقشه بردار</div>
        </div>
        <div class="footer-left">
            <div class="copyright">کلیه حقوق مادی و معنوی این سایت متعلق به شرکت مهندسین ساختمان ساز می‌باشد</div>
            <div class="contact-info">
                <span>1402 ©</span>
                <span>info@example.com</span>
            </div>
        </div>
    </div>
</footer>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/maskan-sazan-naghsheh-bardary/js/site.js"></script>
<?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>


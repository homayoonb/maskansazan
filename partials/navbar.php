<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root{
        --sidebar-width:260px;
    }

    /* ===== دسکتاپ ===== */

    .sidebar{
        position:fixed;
        top:0;
        right:0;
        width:var(--sidebar-width);
        height:100vh;
        background:#f8fdff;
        border-left:1px solid #d1e9f0;
        z-index:1100;
        overflow-y:auto;
        transition:transform .3s ease;
    }

    .sidebar-nav{
        padding-top:20px;
    }

    .sidebar .nav-link{
        padding:12px 18px;
        color:#2c3e50;
        font-size:14px;
        border-radius:6px;
        margin:2px 8px;
        transition:.2s;
    }

    .sidebar .nav-link:hover{
        background:#e8f6fb;
    }

    .sidebar .nav-link.active{
        background:#0d6efd;
        color:#fff;
    }

    .sidebar .dropdown-item{
        padding:10px 35px 10px 15px;
        font-size:14px;
    }

    /* ===== موبایل ===== */

    .sidebar-overlay,
    .mobile-menu-toggle{
        display:none;
    }

    @media (max-width:768px){

        :root{
            --sidebar-width:85vw;
        }

        body{
            overflow-x:hidden;
        }

        .sidebar{
            width:var(--sidebar-width);
            max-width:320px;
            min-width:240px;
            transform:translateX(100%);
            z-index:2000;
        }

        .sidebar.is-open{
            transform:translateX(0);
        }

        body.sidebar-open{
            overflow:hidden;
        }

        .sidebar-nav{
            padding-top:70px;
        }

        /* overlay */

        .sidebar-overlay{
            display:none;
            position:fixed;
            inset:0;
            background:rgba(0,0,0,.45);
            z-index:1990;
        }

        .sidebar-overlay.is-open{
            display:block;
        }

        /* دکمه همبرگری */

        .mobile-menu-toggle{
            display:flex;
            align-items:center;
            justify-content:center;
            position:fixed;
            top:12px;
            right:12px;
            width:42px;
            height:42px;
            border:0;
            border-radius:10px;
            background:#0d6efd;
            color:#fff;
            font-size:20px;
            z-index:2100;
            transition:.3s;
        }

        body.sidebar-open .mobile-menu-toggle{
            background:#dc3545;
        }

        body.sidebar-open .mobile-menu-toggle i:before{
            content:"\f00d";
            font-family:"Font Awesome 6 Free";
            font-weight:900;
        }

        /* dropdown در موبایل */

        .sidebar .dropdown-menu{
            position:static !important;
            width:100%;
            border:0;
            box-shadow:none;
            background:transparent;
            transform:none !important;
            margin:0;
        }

        .sidebar .dropdown-item{
            white-space:normal;
            padding:10px 35px 10px 15px;
        }

        .sidebar .nav-link{
            white-space:normal;
            line-height:1.8;
        }
    }


</style>

<nav class="sidebar-nav">
    <ul class="nav flex-column p-0">
        <li class="nav-item">
            <a class="nav-link <?= $currentPage=='index.php'?'active':'' ?>" href="<?= $baseUrl ?>/index.php">
                <i class="fa fa-home ms-2"></i> صفحه اصلی
            </a>
        </li>

        <!-- پروژه‌ها -->
        <li class="nav-item">
            <a class="nav-link d-flex justify-content-between align-items-center"
               data-bs-toggle="collapse"
               href="#projectsSub"
               role="button"
               aria-expanded="false"
               aria-controls="projectsSub">
                <span><i class="fa fa-building ms-2"></i> پروژه‌ها</span>
                <i class="fa fa-chevron-down small"></i>
            </a>
            <div class="collapse <?= in_array($currentPage, array_keys($projectPages))?'show':'' ?>" id="projectsSub">
                <ul class="nav flex-column dropdown-menu show">
                    <?php foreach($projectPages as $file => $name): ?>
                        <li><a class="dropdown-item" href="<?= $baseUrl ?>/pages/<?= $file ?>"><?= $name ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="<?= $baseUrl ?>/groupmembers.php">
                <i class="fa fa-users ms-2"></i> اعضا
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="<?= $baseUrl ?>/peymankaran_taminkonandegan_login.php">
                <i class="fa fa-hard-hat ms-2"></i> پیمانکاران
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="/maskansazan/admin/loginAdmin.php">
                <i class="fa fa-user-shield ms-2"></i> مدیریت
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="/maskansazan/contact.php">
                <i class="fa fa-phone ms-2"></i> ارتباط با ما
            </a>
        </li>
    </ul>
</nav>

<div class="sidebar-footer">
    <div class="search-btn-sidebar" data-bs-toggle="modal" data-bs-target="#searchModal">
        <i class="fa fa-search ms-1"></i> جستجو در سامانه
    </div>
</div>
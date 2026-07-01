<?php
ob_start();

$slides = [
        ["نقشه برداری۱(گرمدره).jpg","پروژه نقشه برداری۱(گرمدره) ۱۳۹۴",""],
        ["نقشه برداری۲(گرمدره).jpg","پروژه نقشه برداری ۲(گرمدره) ۱۳۹۵",""],
        ["ترنج(شهرک وحدت-نگارستان).jpg","پروژه ترنج (شهرک وحدت-نگارستان) ۱۳۹۷",""],
        ["پلاریس(شهرک وحدت-نگارستان).jpg","پروژه پلاریس (شهرک وحدت-نگارستان) ۱۴۰۰",""],
        ["اردیبهشت(شهرک وحدت-نگارستان).jpg","پروژه اردیبهشت (شهرک وحدت-نگارستان) ۱۳۹۹",""],
        ["ساباط(شهرک وحدت نگارستان).jpg","پروژه ساباط (شهرک وحدت-نگارستان) ۱۴۰۱",""]
];

$slideGroups = array_chunk($slides, 3);
?>

<div id="projectCarousel"
     class="carousel slide carousel-fade"
     data-bs-ride="carousel"
     data-bs-interval="5000">

    <div class="carousel-inner">

        <?php foreach ($slideGroups as $index => $group): ?>
            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                <div class="container-fluid">
                    <div class="row g-4">

                        <?php foreach ($group as $slide): ?>
                            <div class="col-lg-4 col-md-6">

                                <div class="project-card">

                                    <div class="project-image">
                                        <img
                                                src="<?= $baseUrl ?>/images/<?= rawurlencode($slide[0]) ?>"
                                                class="img-fluid"
                                                alt="<?= htmlspecialchars($slide[1]) ?>"
                                                loading="lazy">
                                    </div>

                                    <div class="project-overlay">
                                        <h5><?= htmlspecialchars($slide[1]) ?></h5>

                                        <?php if (!empty($slide[2])): ?>
                                            <p><?= htmlspecialchars($slide[2]) ?></p>
                                        <?php endif; ?>

                                    </div>

                                </div>

                            </div>
                        <?php endforeach; ?>

                    </div>
                </div>
            </div>
        <?php endforeach; ?>

    </div>

    <button class="carousel-control-prev"
            type="button"
            data-bs-target="#projectCarousel"
            data-bs-slide="prev">

        <span class="carousel-control-prev-icon"></span>

    </button>

    <button class="carousel-control-next"
            type="button"
            data-bs-target="#projectCarousel"
            data-bs-slide="next">

        <span class="carousel-control-next-icon"></span>

    </button>

</div>

<?php
$content = ob_get_clean();
$title = "گروه مسکن سازان";

?>

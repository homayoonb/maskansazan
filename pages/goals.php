<?php
$page_title = "اهداف - تاریخچه گروه";
include "../partials/layout.php";
?>

<style>
    /* Variables */
    :root {
        --primary: #2563eb;
        --light: #f8fafc;
        --gray: #64748b;
        --glass-bg: rgba(255, 255, 255, 0.1);
        --glass-border: rgba(255, 255, 255, 0.2);
    }

    /* Header */
    .header {
        background: linear-gradient(135deg, #2c3e50, #4a6491);
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 25px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    .header h1 { font-size: 1.5rem; margin-bottom: 0.5rem; }
    .header p { font-size: 0.9rem; }

    /* History Content */
    .history-container { margin: 20px 0; }
    .history-content p, .history-content ul { margin-bottom: 15px; }
    .projects-list { list-style: disc; padding-right: 20px; }

    /* Highlight */
    .highlight { font-weight: 600; color: var(--primary); }

    /* Animation */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-in { animation: fadeIn 0.6s ease-out; }

    /* Responsive */
    @media (max-width: 768px) {
        .header h1 { font-size: 1.3rem; }
    }
</style>
<?php
include '../partials/slider.php';
?>
<div class="history-container animate-in">
    <h1 class="page-title">تاریخچه</h1>
    <div class="history-content">
        <p>
            هدایت این گروه را <span class="highlight">هیات امنای سه نفره</span> که توسط اعضا انتخاب شده بودند، بر عهده داشتند. این گروه سابقه اجرای پروژه‌های متعددی در کارنامه کاری خود دارد:
        </p>
        <ul class="projects-list">
            <li>پروژه مسکونی 35 واحدی در گرمدره</li>
            <li>پروژه چهل واحدی شهرک نگارستان</li>
            <li>دو پروژه در حال اجرا</li>
        </ul>
        <div class="managers">
            <p>
                در حال حاضر این گروه با سرپرستی <span class="highlight">آقایان هاشم حاجی قاسمی و ابوالفضل بلندیان</span> به فعالیت خود ادامه می‌دهد.
            </p>
        </div>
    </div>
</div>

<?php include '../partials/footer.php'; ?>

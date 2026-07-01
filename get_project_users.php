<?php
// admin/dashboardAdmin.php
$DB_HOST = 'localhost';
$DB_NAME = 'maskansazan';
$DB_USER = 'root';
$DB_PASS = '';

require_once __DIR__ . '/helpers.php';

// امنیت سشن
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // اگر localhost هست
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

if(session_status()===PHP_SESSION_NONE) session_start();

if(empty($_SESSION['user_id']) || empty($_SESSION['username'])){
    header('Location: loginAdmin.php'); exit;
}

// اتصال DB
$mysqli = new mysqli($DB_HOST,$DB_USER,$DB_PASS,$DB_NAME);
if($mysqli->connect_error) die("DB connection error: ".$mysqli->connect_error);

// بررسی وضعیت ادمین
$stmt=$mysqli->prepare("SELECT status FROM admins WHERE id=? LIMIT 1");
$stmt->bind_param("i",$_SESSION['user_id']);
$stmt->execute();
$admin=$stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$admin || strtolower($admin['status'])!=='active'){
    session_destroy(); header('Location: loginAdmin.php'); exit;
}

// کاربران و پروژه‌ها
$usersRes = $mysqli->query("SELECT * FROM project_members ORDER BY id DESC");
$projectsRes = $mysqli->query("SELECT id,name FROM projects ORDER BY id DESC");
$projectCount = $mysqli->query("SELECT COUNT(*) as c FROM projects")->fetch_assoc()['c'] ?? 0;

// پروژه انتخاب‌شده
$selectedProjectId = (int)($_GET['project_id'] ?? 0);

// لیست چک‌ها
$chequesRes = $mysqli->query("SELECT * FROM cheque_transactions ORDER BY cheque_date DESC,id DESC LIMIT 100");
$statsRes = $mysqli->query("
    SELECT 
        COUNT(*) as total_cheques,
        SUM(amount) as total_amount,
        SUM(CASE WHEN cheque_date < CURDATE() THEN 1 ELSE 0 END) as overdue_count,
        SUM(CASE WHEN cheque_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_count
    FROM cheque_transactions
");
$stats = $statsRes->fetch_assoc() ?? [];
$activeTab = $_GET['tab'] ?? 'tickets';
$csrfToken = generate_csrf_token();

$mysqli->close();
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>داشبورد ادمین</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root{--sidebar-width:280px;--primary-color:#4f46e5;--secondary-color:#6b7280;--bg-card:rgba(255,255,255,0.05);--bg-message-user:#1f2937;--bg-message-admin:#4338ca;--overdue-color:#ef4444;--upcoming-color:#3b82f6;--normal-color:#10b981}
        body{background:linear-gradient(180deg,#0f172a,#0b1220);color:#e6eef8;font-family:Tahoma,sans-serif;min-height:100vh;margin:0;padding:0;transition:background 0.3s}
        .sidebar{width:var(--sidebar-width);min-height:100vh;background:linear-gradient(180deg,#0b1220,#0a1226);position:fixed;top:0;right:0;overflow-y:auto;transition:width 0.3s}
        .main-content{margin-right:var(--sidebar-width);padding:20px;width:calc(100% - var(--sidebar-width))}
        .card-ghost{background:var(--bg-card);border:none;border-radius:15px;padding:20px;margin-bottom:20px;backdrop-filter:blur(10px);transition:transform 0.2s,box-shadow 0.2s}
        .card-ghost:hover{transform:translateY(-3px);box-shadow:0 8px 20px rgba(0,0,0,0.3)}
        .table-wrap{max-height:60vh;overflow:auto;border-radius:10px}
        .table-dark{background:rgba(30,30,47,0.8);border-radius:10px}
        .table-hover tbody tr:hover{background:rgba(79,70,229,0.2)}
        .badge-overdue{background:var(--overdue-color);color:white}
        .badge-upcoming{background:var(--upcoming-color);color:white}
        .badge-normal{background:var(--normal-color);color:white}
        .stats-card{text-align:center;padding:15px;border-radius:10px;margin-bottom:15px}
        .stats-number{font-size:1.8rem;font-weight:bold;margin-bottom:5px}
        .stats-label{font-size:.85rem;opacity:.9}
        .nav-tabs{border-bottom:1px solid rgba(255,255,255,0.1);margin-bottom:20px}
        .nav-tabs .nav-link{color:#e6eef8;border:none;padding:12px 25px;margin-left:5px;border-radius:8px 8px 0 0}
        .nav-tabs .nav-link.active{background:var(--primary-color);color:white}
        .nav-tabs .nav-link:hover{background:rgba(79,70,229,0.3);color:white}
        .cheque-status{padding:4px 8px;border-radius:6px;font-size:.75rem;font-weight:bold}
        .btn-primary{background:var(--primary-color);border:none;transition:background 0.2s,transform 0.2s}
        .btn-primary:hover{background:#4338ca;transform:translateY(-1px)}
        .modal-content{background:#1e1e2f;color:#e6eef8;border-radius:12px;border:1px solid rgba(255,255,255,0.1)}
        .list-group-item{background:transparent;border:1px solid rgba(255,255,255,0.1);color:#e6eef8}
        .list-group-item.active{background:var(--primary-color);border-color:var(--primary-color)}
        .list-group-item:hover{background:rgba(255,255,255,0.1)}
        @media(max-width:991px){.sidebar{width:100%;position:relative;height:auto;min-height:auto}.main-content{margin-right:0;width:100%}.nav-tabs .nav-link{padding:10px 15px;font-size:.9rem}}
        ::-webkit-scrollbar{width:8px}::-webkit-scrollbar-track{background:rgba(255,255,255,0.1);border-radius:10px}::-webkit-scrollbar-thumb{background:var(--primary-color);border-radius:10px}::-webkit-scrollbar-thumb:hover{background:#4338ca}
    </style>
</head>
<body>
<div class="d-flex">
    <nav class="sidebar p-4 d-flex flex-column">
        <div class="mb-4 d-flex align-items-center gap-3">
            <div class="bg-white/10 rounded-3 p-2"><i class="bi bi-shield-lock-fill fs-4 text-white"></i></div>
            <div><div class="text-white fw-bold">پنل مدیریت</div>
                <div class="text-white-50 small">خوش آمدید، <?= htmlspecialchars($_SESSION['username'] ?? 'ادمین') ?></div></div>
        </div>
        <h6 class="text-white mb-3">منو</h6>
        <div class="list-group">
            <a href="dashboardAdmin.php?tab=tickets" class="list-group-item list-group-item-action <?= $activeTab==='tickets'?'active':'' ?>"><i class="bi bi-ticket-detailed me-2"></i>تیکت‌ها</a>
            <a href="dashboardAdmin.php?tab=cheques" class="list-group-item list-group-item-action <?= $activeTab==='cheques'?'active':'' ?>"><i class="bi bi-credit-card me-2"></i>مدیریت چک‌ها</a>
        </div>
        <h6 class="text-white mt-4 mb-3">پروژه‌ها</h6>
        <div class="list-group">
            <a href="dashboardAdmin.php?tab=<?= $activeTab ?>" class="list-group-item list-group-item-action <?= $selectedProjectId===0?'active':'' ?>"><i class="bi bi-grid-3x3-gap me-2"></i>همه پروژه‌ها</a>
            <?php $projectsRes->data_seek(0); while($p=$projectsRes->fetch_assoc()): ?>
                <a href="dashboardAdmin.php?project_id=<?= $p['id'] ?>&tab=<?= $activeTab ?>" class="list-group-item list-group-item-action <?= $selectedProjectId==$p['id']?'active':'' ?>"><i class="bi bi-folder me-2"></i><?= htmlspecialchars($p['name']) ?></a>
            <?php endwhile; ?>
        </div>
        <div class="mt-auto">
            <a href="logoutAdmin.php" class="btn btn-outline-light w-100 mb-2"><i class="bi bi-box-arrow-right me-2"></i>خروج</a>
            <div class="text-white-50 small text-center">نسخه پنل:1.2</div>
        </div>
    </nav>
    <div class="main-content">
        <ul class="nav nav-tabs">
            <li class="nav-item"><a class="nav-link <?= $activeTab==='tickets'?'active':'' ?>" href="dashboardAdmin.php?tab=tickets"><i class="bi bi-ticket-detailed me-2"></i>تیکت‌ها</a></li>
            <li class="nav-item"><a class="nav-link <?= $activeTab==='cheques'?'active':'' ?>" href="dashboardAdmin.php?tab=cheques"><i class="bi bi-credit-card me-2"></i>چک‌ها</a></li>
        </ul>

        <div id="content-area"></div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const csrfToken='<?= $csrfToken ?>';
    const selectedProjectId=<?= $selectedProjectId ?>;
    const activeTab='<?= $activeTab ?>';
    function loadTickets(){
        fetch('get_project_tickets.php?project_id='+selectedProjectId)
            .then(r=>r.json())
            .then(data=>{
                if(!data.success){document.getElementById('content-area').innerHTML='<div class="text-center text-white-50">خطا در بارگذاری تیکت‌ها</div>'; return;}
                let html='';
                if(data.tickets.length===0){html='<div class="text-center text-white-50">هیچ تیکتی موجود نیست</div>';}
                else{
                    html+='<div class="table-wrap"><table class="table table-dark table-hover align-middle"><thead><tr><th>#</th><th>کاربر</th><th>عنوان</th><th>وضعیت</th><th>ایجاد شده</th><th>بروزرسانی</th><th>عملیات</th></tr></thead><tbody>';
                    data.tickets.forEach(t=>{
                        let badge='باز',badgeClass='bg-warning text-dark';
                        if(t.status==='closed'){badge='بسته'; badgeClass='bg-secondary';}
                        else if(t.status==='answered'){badge='پاسخ داده شد'; badgeClass='bg-success';}
                        html+='<tr><td>'+t.id+'</td><td>'+t.user_name+'</td><td>'+t.title+'</td><td><span class="badge '+badgeClass+'">'+badge+'</span></td><td>'+t.created_at+'</td><td>'+t.updated_at+'</td><td><button class="btn btn-sm btn-primary">پاسخ</button></td></tr>';
                    });
                    html+='</tbody></table></div>';
                }
                document.getElementById('content-area').innerHTML=html;
            });
    }
    function loadCheques(){
        fetch('get_cheques.php')
            .then(r=>r.json())
            .then(data=>{
                if(!data.success){document.getElementById('content-area').innerHTML='<div class="text-center text-white-50">خطا در بارگذاری چک‌ها</div>'; return;}
                let html='<div class="table-wrap"><table class="table table-dark table-hover align-middle"><thead><tr><th>#</th><th>شماره چک</th><th>صاحب حساب</th><th>مبلغ</th><th>تاریخ</th><th>وضعیت</th></tr></thead><tbody>';
                data.cheques.forEach((c,i)=>{
                    let statusClass='badge-normal',statusText='عادی';
                    const chequeTime=new Date(c.cheque_date),now=new Date(),diff=Math.floor((chequeTime-now)/(1000*60*60*24));
                    if(diff<0){statusClass='badge-overdue'; statusText='معوقه';}
                    else if(diff<=7){statusClass='badge-upcoming'; statusText='نزدیک';}
                    html+='<tr><td>'+(i+1)+'</td><td>'+c.cheque_number+'</td><td>'+c.account_owner+'</td><td>'+c.amount+'</td><td>'+c.cheque_date+'</td><td><span class="cheque-status '+statusClass+'">'+statusText+'</span></td></tr>';
                });
                html+='</tbody></table></div>';
                document.getElementById('content-area').innerHTML=html;
            });
    }
    if(activeTab==='tickets') loadTickets();
    else loadCheques();
</script>
</body>
</html>

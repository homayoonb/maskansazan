<?php
session_start();
$servername = "localhost";
$username = "maskanhb_maskansazan";
$password = "NJ)B*$@y3R9P0Oyg";
$dbname = "maskanhb_maskansazan";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$national_code = $_POST['national_code'] ?? $_GET['national_code'] ?? '';
$total_amount = 0;
$installments = [];

if (!empty($national_code)) {
    $stmt = $conn->prepare("SELECT * FROM cheque_transactions WHERE national_code=? ORDER BY cheque_date DESC");
    $stmt->bindParam(1, $national_code);
    $stmt->execute();
    $installments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($installments as $installment) {
        $total_amount += $installment['amount'];
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قسط‌های پرداخت شده</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root{
            /* فونت و ابعاد خودکار */
            --card-radius: clamp(8px, 1vw, 24px);
            --padding: clamp(12px, 2vw, 50px);
            --font-small: clamp(12px, 1.2vw, 16px);
            --font-medium: clamp(14px, 1.5vw, 20px);
            --font-large: clamp(18px, 2.5vw, 28px);
            --shadow-light: rgba(0,0,0,0.05);
            --shadow-dark: rgba(0,0,0,0.15);
        }
        body{
            font-family:'Vazir',sans-serif;
            margin:0;
            background:#f2f3f7;
            display:flex;
            justify-content:center;
            padding:var(--padding);
        }
        .container{
            background:white;
            border-radius:var(--card-radius);
            box-shadow:0 5px 20px var(--shadow-dark);
            width:100%;
            max-width:1200px; /* دسکتاپ */
            padding:var(--padding);
            position:relative;
        }

        /* دکمه برگشت */
        .back-btn{
            position:absolute;
            top:var(--padding);
            right:var(--padding);
            background:linear-gradient(135deg,#8b5cf6,#7c3aed);
            color:white;
            border:none;
            padding:0.8em 1.5em;
            border-radius:50px;
            display:flex;
            align-items:center;
            gap:0.5em;
            cursor:pointer;
            transition:all 0.3s ease;
            box-shadow:0 5px 15px var(--shadow-light);
        }
        .back-btn:hover{transform:translateY(-3px);}
        .back-btn i{font-size:1.2em;}

        /* عنوان‌ها */
        .title{font-size:var(--font-large); font-weight:bold; color:#1f2937; text-align:center; margin-bottom:0.5em;}
        .subtitle{font-size:var(--font-medium); color:#6b7280; text-align:center; margin-bottom:1.5em;}

        /* مجموع مبلغ */
        .total-section{
            background:linear-gradient(135deg,#10b981,#059669);
            color:white;
            padding:var(--padding);
            border-radius:var(--card-radius);
            margin-bottom:1.5em;
            position:relative;
            box-shadow:0 5px 15px var(--shadow-light);
        }
        .total-label{font-size:var(--font-medium); opacity:0.9;}
        .total-amount{font-size:calc(var(--font-large)*1.5); font-weight:bold; margin-top:0.2em;}

        /* فرم جستجو */
        .search-section{text-align:center; margin-bottom:1em;}
        .search-input-container{position:relative; max-width:90%; margin:0 auto;}
        .search-input{
            width:100%;
            padding:0.8em 3em 0.8em 1em;
            border-radius:var(--card-radius);
            border:2px solid #e5e7eb;
            font-size:var(--font-medium);
            text-align:center;
            transition:all 0.3s ease;
        }
        .search-input:focus{
            outline:none;
            border-color:#4f46e5;
            box-shadow:0 0 0 0.2em rgba(79,70,229,0.1);
            transform:scale(1.02);
        }
        .search-icon{position:absolute; left:1em; top:50%; transform:translateY(-50%); color:#9ca3af; font-size:1.2em;}

        /* اطلاعات کاربر */
        .user-info{
            background:linear-gradient(135deg,#3b82f6,#1d4ed8);
            color:white;
            padding:var(--padding);
            border-radius:var(--card-radius);
            margin-bottom:1em;
            box-shadow:0 5px 15px var(--shadow-light);
        }
        .user-info h3{font-size:var(--font-medium); margin-bottom:0.5em;}
        .user-details{font-size:var(--font-small); opacity:0.9; line-height:1.5;}

        /* اقساط */
        .results-section h3{margin-bottom:1em; color:#374151; font-size:var(--font-medium); text-align:right;}
        .installment-item{
            background:white;
            border-radius:var(--card-radius);
            padding:var(--padding);
            margin-bottom:0.8em;
            box-shadow:0 4px 12px var(--shadow-light);
            display:flex;
            flex-direction:column;
            transition:all 0.3s ease;
        }
        .installment-item:hover{transform:translateY(-2px); box-shadow:0 6px 18px var(--shadow-dark);}
        .installment-header{display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom:0.5em;}
        .cheque-number{font-weight:600; font-size:var(--font-medium);}
        .amount{font-weight:700; font-size:var(--font-medium); color:#10b981;}
        .installment-details{font-size:var(--font-small); color:#6b7280; display:flex; flex-wrap:wrap; gap:0.5em;}

        /* پیام‌ها */
        .info-section{background:#fef3f2; border:1px solid #fca5a5; border-radius:var(--card-radius); padding:var(--padding); text-align:center; color:#b91c1c; margin-top:1em;}

        /* خط جداکننده */
        .divider{height:2px; background:linear-gradient(90deg,transparent,#e5e7eb,transparent); margin:1em 0;}
    </style>
</head>
<body>
<div class="container">
    <a href="dashboard.php" class="back-btn"><i class="bi bi-arrow-right"></i> بازگشت</a>

    <h1 class="title">قسط‌های پرداخت شده</h1>
    <p class="subtitle">لیست تمام اقساط پرداخت شده بر اساس کد ملی</p>

    <div class="total-section">
        <div class="total-label">مجموع مبلغ پرداختی</div>
        <div class="total-amount"><?php echo number_format($total_amount); ?> ریال</div>
    </div>

    <div class="divider"></div>

    <?php if(!empty($national_code)): ?>
        <?php if(!empty($installments)): ?>
            <div class="user-info">
                <h3>اطلاعات مشتری</h3>
                <div class="user-details">
                    <strong>کد ملی:</strong> <?php echo $national_code; ?><br>
                    <strong>نام:</strong> <?php echo htmlspecialchars($installments[0]['member_name']); ?><br>
                    <strong>صاحب حساب:</strong> <?php echo htmlspecialchars($installments[0]['account_owner']); ?>
                </div>
            </div>

            <div class="results-section">
                <h3>لیست اقساط (<?php echo count($installments); ?> مورد)</h3>
                <?php foreach($installments as $installment): ?>
                    <div class="installment-item">
                        <div class="installment-header">
                            <div class="cheque-number"><?php echo htmlspecialchars($installment['cheque_number']); ?></div>
                            <div class="amount"><?php echo number_format($installment['amount']); ?> ریال</div>
                        </div>
                        <div class="installment-details">
                            <span>بانک: <?php echo htmlspecialchars($installment['bank_name']); ?></span>
                            <span>تاریخ: <?php echo htmlspecialchars($installment['cheque_date']); ?></span>
                            <span>بابت: <?php echo htmlspecialchars($installment['purpose']); ?></span>
                            <?php if(!empty($installment['receiver_name'])): ?>
                                <span>دریافت کننده: <?php echo htmlspecialchars($installment['receiver_name']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="info-section">هیچ قسطی برای کد ملی <?php echo htmlspecialchars($national_code); ?> یافت نشد.</div>
        <?php endif; ?>
    <?php else: ?>
        <div class="info-section">لطفاً کد ملی خود را وارد کنید تا اقساط نمایش داده شود.</div>
    <?php endif; ?>
</div>

<script>
    const input=document.querySelector('input[name="national_code"]');
    if(input){input.addEventListener('input',function(){if(this.value.length===10)this.form.submit();});}
</script>
</body>
</html>
<?php $conn=null; ?>

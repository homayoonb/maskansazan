<?php
header('Content-Type: application/json');
ini_set('display_errors',1);
error_reporting(E_ALL);

if($_SERVER['REQUEST_METHOD']!=='POST'){
    echo json_encode(['status'=>false,'message'=>'درخواست نامعتبر']);
    exit;
}

try{
    $pdo=new PDO(
        "mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4",
        "maskanhb_maskansazan",
        "NJ)B*$@y3R9P0Oyg",
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
    );
}catch(PDOException $e){
    echo json_encode(['status'=>false,'message'=>'خطا در اتصال دیتابیس']);
    exit;
}

$transferDate=$_POST['transfer_date']??'';
$amount=(int)($_POST['amount']??0);
$bank=$_POST['bank_name']??'';
$account=$_POST['destination_account']??'';
$desc=$_POST['description']??'';

$errors=[];

if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$transferDate))
    $errors['transfer_date']='تاریخ معتبر نیست';

if($amount<=0)
    $errors['amount']='مبلغ نامعتبر است';

if(!$bank)
    $errors['bank_name']='بانک انتخاب نشده';

if(!$account)
    $errors['destination_account']='حساب مقصد وارد نشده';

if($errors){
    echo json_encode(['status'=>false,'errors'=>$errors]);
    exit;
}

$image=null;
if(!empty($_FILES['receipt_image']['name'])){
    $ext=strtolower(pathinfo($_FILES['receipt_image']['name'],PATHINFO_EXTENSION));
    if(!in_array($ext,['jpg','jpeg','png','webp'])){
        echo json_encode(['status'=>false,'errors'=>['receipt_image'=>'فرمت تصویر نامعتبر']]);
        exit;
    }
    $dir=__DIR__.'/uploads/transfers/';
    if(!is_dir($dir)) mkdir($dir,0755,true);
    $name=uniqid().".$ext";
    move_uploaded_file($_FILES['receipt_image']['tmp_name'],$dir.$name);
    $image='uploads/transfers/'.$name;
}

$stmt=$pdo->prepare("
INSERT INTO transfers
(transfer_date,amount,bank_name,destination_account,receipt_image,description,status)
VALUES (?,?,?,?,?,?,'pending')
");

$stmt->execute([
    $transferDate,$amount,$bank,$account,$image,$desc
]);

echo json_encode(['status'=>true,'message'=>'حواله با موفقیت ثبت شد']);

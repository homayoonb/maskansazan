<?php
require_once __DIR__ . '/helpers.php';
if(session_status()===PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

if(empty($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit;
}

$mysqli = new mysqli('localhost','root','','maskansazan');
if($mysqli->connect_error){
    echo json_encode(['success'=>false,'message'=>'DB connection error']); exit;
}

// لیست چک‌ها
$sql = "SELECT id, cheque_number, account_owner, amount, cheque_date FROM cheque_transactions ORDER BY cheque_date DESC,id DESC LIMIT 100";
$res = $mysqli->query($sql);
$cheques=[];
if($res){
    while($row=$res->fetch_assoc()){
        $cheques[]=$row;
    }
}
$mysqli->close();
echo json_encode(['success'=>true,'cheques'=>$cheques]);

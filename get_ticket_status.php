<?php
session_start();
include 'config/Database.php';
header('Content-Type: application/json');

if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin']!==true){
    echo json_encode(['success'=>false,'message'=>'لطفا وارد شوید.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$ticket_id = (int)($_GET['ticket_id'] ?? 0);
if(!$ticket_id){
    echo json_encode(['success'=>false,'message'=>'تیکت معتبر نیست.']);
    exit;
}

$db = new Database();
$conn = $db->connect();
$stmt = $conn->prepare("SELECT status FROM tickets WHERE id=? AND user_id=?");
$stmt->bind_param("ii",$ticket_id,$user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
if($result){
    echo json_encode(['success'=>true,'status'=>$result['status']]);
}else{
    echo json_encode(['success'=>false,'message'=>'تیکت پیدا نشد.']);
}

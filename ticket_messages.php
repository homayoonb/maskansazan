<?php
session_start();
include 'config/Database.php';
header('Content-Type: application/json');

if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin']!==true){
    echo json_encode(['success'=>false,'message'=>'لطفا وارد شوید.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$db = new Database();
$conn = $db->connect();

if($_SERVER['REQUEST_METHOD']==='GET' && isset($_GET['ticket_id'])){
    $ticket_id = (int)$_GET['ticket_id'];
    $stmt = $conn->prepare("SELECT * FROM ticket_messages WHERE ticket_id=? ORDER BY created_at ASC");
    $stmt->bind_param("i",$ticket_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($messages);
    exit;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    if(!$ticket_id || !$message){
        echo json_encode(['success'=>false,'message'=>'پیام یا تیکت معتبر نیست.']);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO ticket_messages (ticket_id,sender,message,created_at) VALUES (?,?,?,NOW())");
    $sender='user';
    $stmt->bind_param("iss",$ticket_id,$sender,$message);
    if($stmt->execute()){
        // همچنین وضعیت تیکت را به pending تغییر بده تا پشتیبانی مطلع شود
        $conn->query("UPDATE tickets SET status='pending' WHERE id=$ticket_id");
        echo json_encode(['success'=>true]);
    }else{
        echo json_encode(['success'=>false,'message'=>'خطا در ارسال پیام.']);
    }
}

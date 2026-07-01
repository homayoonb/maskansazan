<?php
require_once __DIR__ . '/helpers.php';
if(session_status()===PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

if(empty($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit;
}

$project_id = (int)($_GET['project_id'] ?? 0);

$mysqli = new mysqli('localhost','root','','maskansazan');
if($mysqli->connect_error){
    echo json_encode(['success'=>false,'message'=>'DB connection error']); exit;
}

// لیست تیکت‌ها
$sql = "SELECT t.id, t.title, t.status, t.created_at, t.updated_at, 
        CONCAT(u.first_name,' ',u.last_name) as user_name
        FROM tickets t
        LEFT JOIN project_members u ON t.user_id=u.id";
if($project_id>0) $sql.=" WHERE t.project_id=$project_id";
$sql.=" ORDER BY t.created_at DESC LIMIT 100";

$res = $mysqli->query($sql);
$tickets=[];
if($res){
    while($row=$res->fetch_assoc()){
        $tickets[]=$row;
    }
}
$mysqli->close();
echo json_encode(['success'=>true,'tickets'=>$tickets]);

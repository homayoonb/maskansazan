<?php
require_once 'helpers.php';
header('Content-Type: application/json');

$mysqli = new mysqli('localhost','root','','maskansazan');
if($mysqli->connect_error){
    echo json_encode(['success'=>false,'message'=>'DB error']);
    exit;
}

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

if($project_id==0){
    $res = $mysqli->query("SELECT id, first_name, last_name, national_code FROM project_members");
}else{
    $stmt = $mysqli->prepare("SELECT id, first_name, last_name, national_code FROM project_members WHERE project_id=?");
    $stmt->bind_param('i',$project_id);
    $stmt->execute();
    $res = $stmt->get_result();
}

$members = [];
while($row=$res->fetch_assoc()) $members[]=$row;

echo json_encode(['success'=>true,'members'=>$members]);

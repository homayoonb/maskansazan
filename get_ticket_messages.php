<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/config/Database.php';

$ticketId = intval($_GET['ticket_id'] ?? 0);
if ($ticketId <= 0) {
    echo json_encode(['success'=>false,'message'=>'Invalid ticket id']);
    exit;
}

$db = new Database();
$mysqli = $db->connect();

if (!$mysqli) {
    echo json_encode(['success'=>false,'message'=>'DB connection failed']);
    exit;
}

$stmt = $mysqli->prepare("SELECT * FROM ticket_messages WHERE ticket_id=? ORDER BY created_at ASC");
$stmt->bind_param("i",$ticketId);
$stmt->execute();
$res = $stmt->get_result();

$messages = [];
while($row = $res->fetch_assoc()){
    $messages[] = $row;
}

echo json_encode(['success'=>true,'messages'=>$messages]);
$stmt->close();
$mysqli->close();

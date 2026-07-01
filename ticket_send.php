<?php
session_start();
include 'config/Database.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) exit();

$ticket_id = $_POST['ticket_id'] ?? 0;
$message = trim($_POST['message'] ?? '');

if($message === '') exit();

$db = new Database();
$conn = $db->connect();

// بررسی مالک تیکت
$check = $conn->prepare("SELECT id FROM tickets WHERE id=? AND user_id=?");
$check->bind_param("ii", $ticket_id, $_SESSION['user_id']);
$check->execute();
$check_res = $check->get_result();
if($check_res->num_rows === 0) exit();

// درج پیام
$stmt = $conn->prepare("INSERT INTO ticket_messages (ticket_id,sender,message) VALUES (?, 'user', ?)");
$stmt->bind_param("is", $ticket_id, $message);
$stmt->execute();

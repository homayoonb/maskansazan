<?php
// delete_member.php
session_start();

if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_username'])) {
    echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز']);
    exit;
}

if (empty($_POST['member_id'])) {
    echo json_encode(['success' => false, 'message' => 'پارامترهای ناقص']);
    exit;
}

$member_id = (int)$_POST['member_id'];

$mysqli = new mysqli('localhost', 'maskanhb_maskansazan', 'NJ)B*$@y3R9P0Oyg', 'maskanhb_maskansazan');
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'message' => 'خطا در اتصال به دیتابیس']);
    exit;
}

$stmt = $mysqli->prepare("DELETE FROM members WHERE id = ?");
$stmt->bind_param("i", $member_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'عضو حذف شد']);
} else {
    echo json_encode(['success' => false, 'message' => 'خطا در حذف عضو']);
}

$stmt->close();
$mysqli->close();
?>
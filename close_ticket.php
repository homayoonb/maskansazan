<?php
session_start();
include 'config/Database.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز']);
    exit();
}

$db = new Database();
$conn = $db->connect();
$user_name = $_SESSION['full_name'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id'])) {
    $ticket_id = (int)$_POST['ticket_id'];

    $check_stmt = $conn->prepare("SELECT id FROM tickets WHERE id = ? AND user_name = ? AND status != 'closed'");
    if ($check_stmt) {
        $check_stmt->bind_param("is", $ticket_id, $user_name);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $update_stmt = $conn->prepare("UPDATE tickets SET status = 'closed', updated_at = NOW() WHERE id = ?");
            if ($update_stmt) {
                $update_stmt->bind_param("i", $ticket_id);

                if ($update_stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'تیکت با موفقیت بسته شد']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'خطا در بستن تیکت']);
                }
                $update_stmt->close();
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'تیکت یافت نشد یا قبلاً بسته شده است']);
        }
        $check_stmt->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر']);
}
?>
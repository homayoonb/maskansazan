<?php
session_start();
include 'config/Database.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

// گرفتن شناسه تیکت
$ticket_id = (int)($_GET['id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

// گرفتن تیکت
$stmt = $conn->prepare("SELECT t.*, p.name AS project_name FROM tickets t
                        JOIN projects p ON t.project_id=p.id
                        WHERE t.id=? AND t.user_id=?");
$stmt->bind_param("ii", $ticket_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$ticket = $result->fetch_assoc();
if (!$ticket) die("تیکت پیدا نشد.");

// دریافت پیام‌های تیکت
$msg_stmt = $conn->prepare("SELECT * FROM ticket_messages WHERE ticket_id=? ORDER BY created_at ASC");
$msg_stmt->bind_param("i", $ticket_id);
$msg_stmt->execute();
$msg_result = $msg_stmt->get_result();
$messages = $msg_result->fetch_all(MYSQLI_ASSOC);

// ارسال پیام جدید
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $message = trim($_POST['message']);
    if ($message !== '') {
        $insert_stmt = $conn->prepare("INSERT INTO ticket_messages (ticket_id, sender, message) VALUES (?, 'user', ?)");
        $insert_stmt->bind_param("is", $ticket_id, $message);
        $insert_stmt->execute();
        header("Location: ticket_chat.php?id=$ticket_id");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>چت تیکت</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { font-family: 'Vazir', sans-serif; background:#f4f6f9; }
        .chat-container { max-width: 800px; margin: 20px auto; display:flex; flex-direction:column; gap:20px; }
        .card { border-radius:10px; box-shadow:0 3px 10px rgba(0,0,0,0.05); }
        .chat-box { display:flex; flex-direction:column; height:500px; }
        .chat-header { padding:15px; background:#4e73df; color:white; font-weight:bold; border-radius:10px 10px 0 0; }
        .chat-messages { flex:1; overflow-y:auto; padding:15px; display:flex; flex-direction:column; gap:10px; }
        .chat-message { display:flex; flex-wrap:wrap; max-width:80%; }
        .chat-message.user { justify-content:flex-end; align-self:flex-end; }
        .chat-message.user .message-content { background:#4e73df; color:white; border-radius:15px 15px 0 15px; }
        .chat-message.support { justify-content:flex-start; align-self:flex-start; }
        .chat-message.support .message-content { background:#e9ecef; color:#212529; border-radius:15px 15px 15px 0; }
        .message-content { padding:10px 15px; word-wrap:break-word; }
        .message-time { font-size:10px; color:#666; text-align:right; margin-top:5px; }
        .chat-footer { display:flex; gap:10px; padding:10px; border-top:1px solid #e3e6f0; }
        .chat-footer input { flex:1; border-radius:20px; padding:10px 15px; border:1px solid #ced4da; }
        .chat-footer button { border-radius:20px; }
        @media(max-width:576px){
            .chat-box { height:400px; }
            .chat-message { max-width:100%; }
        }
    </style>
</head>
<body>

<div class="chat-container">

    <div class="card chat-box">
        <div class="chat-header">
            تیکت: <?php echo htmlspecialchars($ticket['title']); ?> | پروژه: <?php echo htmlspecialchars($ticket['project_name']); ?>
        </div>

        <div class="chat-messages" id="chatMessages">
            <?php foreach($messages as $msg): ?>
                <div class="chat-message <?php echo ($msg['sender']=='user') ? 'user' : 'support'; ?>">
                    <div class="message-content">
                        <?php echo htmlspecialchars($msg['message']); ?>
                        <div class="message-time">
                            <?php echo date('Y-m-d H:i', strtotime($msg['created_at'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <form class="chat-footer" method="post">
            <input type="text" name="message" placeholder="پیام خود را بنویسید..." required>
            <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> ارسال</button>
        </form>
    </div>

</div>

<script>
    var chatMessages = document.getElementById('chatMessages');
    if(chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

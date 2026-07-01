<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'config/Database.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit();
}

$db = new Database();
$conn = $db->connect();
// اطلاعات کاربر
$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['full_name'] ?? '';
$project_id = $_SESSION['project_id'] ?? 0;
$project_name = $_SESSION['project_name'] ?? '';

// گرفتن پروژه‌ها (در صورت نیاز)
$projects = [];
$project_stmt = $conn->prepare("SELECT * FROM projects ORDER BY id ASC");
if ($project_stmt) {
    $project_stmt->execute();
    $projects = $project_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $project_stmt->close();
}

// گرفتن تیکت‌های کاربر
$tickets = [];
$tickets_stmt = $conn->prepare("SELECT t.*, p.name AS project_name 
                                FROM tickets t 
                                JOIN projects p ON t.project_id = p.id 
                                WHERE t.user_name = ? 
                                ORDER BY t.created_at DESC");
if ($tickets_stmt) {
    $tickets_stmt->bind_param("s", $user_name);
    $tickets_stmt->execute();
    $tickets = $tickets_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $tickets_stmt->close();
}

// انتخاب تیکت برای مشاهده
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$ticket = null;
$messages = [];

if ($ticket_id > 0) {
    $stmt = $conn->prepare("SELECT t.*, p.name AS project_name 
                            FROM tickets t 
                            JOIN projects p ON t.project_id = p.id 
                            WHERE t.id = ? AND t.user_name = ?");
    if ($stmt) {
        $stmt->bind_param("is", $ticket_id, $user_name);
        $stmt->execute();
        $ticket = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($ticket) {
            $msg_stmt = $conn->prepare("SELECT * FROM ticket_messages WHERE ticket_id = ? ORDER BY created_at ASC");
            if ($msg_stmt) {
                $msg_stmt->bind_param("i", $ticket_id);
                $msg_stmt->execute();
                $messages = $msg_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $msg_stmt->close();
            }
        }
    }
}

// ایجاد تیکت جدید
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['project_id'])) {
    $title = trim($_POST['title']);
    $proj_id = (int)$_POST['project_id'];

    if ($title && $proj_id && !empty($user_name)) {
        $insert_stmt = $conn->prepare("INSERT INTO tickets (project_id, user_name, title, status) VALUES (?, ?, ?, 'open')");
        if ($insert_stmt) {
            $insert_stmt->bind_param("iss", $proj_id, $user_name, $title);
            if ($insert_stmt->execute()) {
                header("Location: ?id=" . $conn->insert_id);
                exit();
            } else {
                die("خطا در ایجاد تیکت: " . $conn->error);
            }
            $insert_stmt->close();
        } else {
            die("خطا در آماده‌سازی کوئری: " . $conn->error);
        }
    } else {
        $error = "لطفاً تمام فیلدها را پر کنید";
    }
}

// ارسال پیام جدید
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'], $_POST['ticket_id'])) {
    $ticket_id_post = (int)$_POST['ticket_id'];
    $message = trim($_POST['message']);

    $check_stmt = $conn->prepare("SELECT id FROM tickets WHERE id = ? AND user_name = ? AND status != 'closed'");
    if ($check_stmt) {
        $check_stmt->bind_param("is", $ticket_id_post, $user_name);
        $check_stmt->execute();
        $res_check = $check_stmt->get_result();

        if ($res_check->num_rows > 0 && $message !== '') {
            $conn->begin_transaction();
            try {
                $insert_stmt = $conn->prepare("INSERT INTO ticket_messages (ticket_id, sender, message) VALUES (?, 'user', ?)");
                $insert_stmt->bind_param("is", $ticket_id_post, $message);
                $insert_stmt->execute();

                $update_stmt = $conn->prepare("UPDATE tickets SET status = 'pending', updated_at = NOW() WHERE id = ?");
                $update_stmt->bind_param("i", $ticket_id_post);
                $update_stmt->execute();

                $conn->commit();
                echo json_encode(['success'=>true]);

                $insert_stmt->close();
                $update_stmt->close();
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success'=>false,'message'=>'خطا در ارسال پیام']);
            }
        } else {
            echo json_encode(['success'=>false,'message'=>'امکان ارسال پیام وجود ندارد']);
        }
        $check_stmt->close();
    } else {
        echo json_encode(['success'=>false,'message'=>'خطا در بررسی دسترسی']);
    }
    exit();
}

// بازگرداندن پیام‌ها برای AJAX
if (isset($_GET['ticket_id'])) {
    $ticket_id_get = (int)$_GET['ticket_id'];
    $check_stmt = $conn->prepare("SELECT id FROM tickets WHERE id = ? AND user_name = ?");
    if ($check_stmt) {
        $check_stmt->bind_param("is", $ticket_id_get, $user_name);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows > 0) {
            $msg_stmt = $conn->prepare("SELECT * FROM ticket_messages WHERE ticket_id = ? ORDER BY created_at ASC");
            $msg_stmt->bind_param("i", $ticket_id_get);
            $msg_stmt->execute();
            $msgs = $msg_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            echo json_encode($msgs);
            $msg_stmt->close();
        } else {
            echo json_encode([]);
        }
        $check_stmt->close();
    }
    exit();
}
?>


    <?php if(isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ایجاد تیکت جدید -->
    <div class="card ticket-card p-4 mb-4">
        <h5 class="section-title">ایجاد تیکت جدید</h5>

        <form method="post" id="createTicketForm">
            <div class="mb-3">
                <label for="project_id" class="form-label">پروژه</label>
                <input
                        type="text"
                        class="form-control"
                        name="project_name"
                        value="<?= htmlspecialchars($project_name) ?>"
                        readonly
                >
                <input type="hidden" name="project_id" value="<?= htmlspecialchars($project_id) ?>">
            </div>

            <div class="mb-3">
                <label for="title" class="form-label">عنوان تیکت</label>
                <input type="text" name="title" id="title" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary">ایجاد تیکت</button>
        </form>
    </div>

    <!-- لیست تیکت‌ها -->
    <div class="card ticket-card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="section-title m-0">تیکت‌های شما</h5>
        </div>

        <div class="table-responsive ticket-table-wrapper">
            <table class="table table-bordered table-hover align-middle text-end mb-0">
                <thead>
                <tr>
                    <th>شناسه</th>
                    <th>عنوان</th>
                    <th>پروژه</th>
                    <th>وضعیت</th>
                    <th>تاریخ ایجاد</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                <?php if(empty($tickets)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">تیکتی ثبت نشده است.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach($tickets as $t):
                        $colors = [
                                'open'     => '#1cc88a',
                                'pending'  => '#f6c23e',
                                'closed'   => '#e74a3b',
                                'answered' => '#36b9cc'
                        ];

                        $texts = [
                                'open'     => 'باز',
                                'pending'  => 'در انتظار پاسخ',
                                'closed'   => 'بسته',
                                'answered' => 'پاسخ داده شد'
                        ];

                        $color = $colors[$t['status']] ?? '#858796';
                        $text  = $texts[$t['status']] ?? 'نامشخص';
                        ?>
                        <tr>
                            <td><?= (int)$t['id'] ?></td>
                            <td><?= htmlspecialchars($t['title']) ?></td>
                            <td><?= htmlspecialchars($t['project_name']) ?></td>
                            <td>
                                <span class="status-badge" style="background:<?= htmlspecialchars($color) ?>">
                                    <?= htmlspecialchars($text) ?>
                                </span>
                            </td>
                            <td><?= date('Y-m-d H:i', strtotime($t['created_at'])) ?></td>
                            <td>
                                <div class="d-flex flex-wrap gap-2 justify-content-center">
                                    <a href="?id=<?= (int)$t['id'] ?>" class="btn btn-sm btn-primary">مشاهده</a>

                                    <?php if($t['status'] !== 'closed'): ?>
                                        <button
                                                type="button"
                                                class="btn btn-sm btn-danger close-ticket"
                                                data-ticket="<?= (int)$t['id'] ?>"
                                        >
                                            بستن
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if($ticket):
        $color = [
                'open'    => '#1cc88a',
                'pending' => '#f6c23e',
                'closed'  => '#e74a3b',
                'answered'=> '#36b9cc'
        ][$ticket['status']] ?? '#858796';

        $text = [
                'open'    => 'باز',
                'pending' => 'در انتظار پاسخ',
                'closed'  => 'بسته',
                'answered'=> 'پاسخ داده شد'
        ][$ticket['status']] ?? 'نامشخص';
        ?>
        <div class="card ticket-card p-3 p-md-4 mb-4">
            <div class="ticket-header-box">
                <div>
                    <h5 class="section-title mb-2">
                        تیکت: <?= htmlspecialchars($ticket['title']) ?>
                    </h5>
                    <div class="text-muted small">
                        پروژه: <?= htmlspecialchars($ticket['project_name']) ?>
                    </div>
                </div>

                <span class="status-badge" id="ticket-status" style="background:<?= htmlspecialchars($color) ?>">
                    <?= htmlspecialchars($text) ?>
                </span>
            </div>

            <?php if($ticket['status'] !== 'closed'): ?>
                <div class="chat-box mt-3">
                    <div class="chat-messages" id="chatMessages">
                        <?php foreach($messages as $msg): ?>
                            <div
                                    class="chat-message <?= $msg['sender'] === 'user' ? 'user' : 'support' ?>"
                                    id="msg-<?= (int)$msg['id'] ?>"
                            >
                                <div class="message-content"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                                <div class="message-time"><?= htmlspecialchars($msg['created_at']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <form id="chatForm" class="chat-footer mt-2">
                        <input type="text" name="message" placeholder="پیام خود را بنویسید..." required>

                        <button type="submit" class="btn btn-primary" id="sendBtn">
                            <span class="btn-text">ارسال</span>
                            <span class="btn-loading" style="display: none;">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                در حال ارسال...
                            </span>
                        </button>

                        <button type="button" class="btn btn-warning" id="closeTicketBtn">
                            بستن تیکت
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="alert alert-warning mt-3">
                    این تیکت بسته شده و امکان ارسال پیام وجود ندارد.
                </div>

                <div class="chat-messages mt-3 closed-chat">
                    <?php foreach($messages as $msg): ?>
                        <div class="chat-message <?= $msg['sender'] === 'user' ? 'user' : 'support' ?>">
                            <div class="message-content"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                            <div class="message-time"><?= htmlspecialchars($msg['created_at']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>


<div id="newMessageNotification">پیام جدید دریافت شد</div>


<style>


    .ticket-card{
        border: none;
        border-radius: 16px;
        box-shadow: 0 8px 30px rgba(0,0,0,.06);
        background: #fff;
    }

    .section-title{
        font-size: 18px;
        font-weight: 700;
        color: #2c3e50;
    }

    .ticket-header-box{
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .ticket-table-wrapper{
        border-radius: 12px;
        overflow: hidden;
    }

    .ticket-table-wrapper table thead th{
        background: #f8f9fc;
        white-space: nowrap;
        font-size: 14px;
    }

    .status-badge{
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 14px;
        border-radius: 999px;
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .chat-box{
        border: 1px solid #e9ecef;
        border-radius: 16px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        background: #fff;
        position: relative;
    }

    .chat-messages{
        flex: 1;
        overflow-y: auto;
        padding: 15px;
        background: linear-gradient(to bottom, #f8f9fc, #eef2f7);
        min-height: 320px;
        max-height: 420px;
        scroll-behavior: smooth;
    }

    .closed-chat{
        border: 1px solid #eee;
        border-radius: 12px;
    }

    .chat-message{
        display: flex;
        flex-direction: column;
        margin-bottom: 12px;
    }

    .chat-message.user{
        align-items: flex-end;
    }

    .chat-message.support{
        align-items: flex-start;
    }

    .message-content{
        padding: 10px 14px;
        border-radius: 18px;
        max-width: min(75%, 520px);
        word-wrap: break-word;
        overflow-wrap: break-word;
        line-height: 1.8;
        font-size: 14px;
        box-shadow: 0 2px 10px rgba(0,0,0,.05);
    }

    .chat-message.user .message-content{
        background: #1cc88a;
        color: #fff;
        border-bottom-right-radius: 6px;
    }

    .chat-message.support .message-content{
        background: #4e73df;
        color: #fff;
        border-bottom-left-radius: 6px;
    }

    .message-time{
        font-size: 11px;
        color: #6c757d;
        margin-top: 4px;
        padding: 0 6px;
    }

    .chat-footer{
        display: flex;
        gap: 8px;
        padding: 12px;
        border-top: 1px solid #edf0f2;
        background: #fff;
        flex-wrap: wrap;
    }

    .chat-footer input[type="text"]{
        flex: 1 1 240px;
        min-width: 0;
        padding: 10px 14px;
        border-radius: 999px;
        border: 1px solid #d0d7de;
        outline: none;
        transition: .2s ease;
    }

    .chat-footer input[type="text"]:focus{
        border-color: #4e73df;
        box-shadow: 0 0 0 3px rgba(78,115,223,.12);
    }

    .chat-footer button{
        border-radius: 999px;
        font-size: 14px;
        padding: 9px 16px;
        position: relative;
        white-space: nowrap;
    }

    #newMessageNotification{
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: #f6c23e;
        color: #000;
        padding: 8px 16px;
        border-radius: 999px;
        display: none;
        cursor: pointer;
        font-size: 12px;
        z-index: 1050;
        box-shadow: 0 8px 24px rgba(0,0,0,.12);
    }

    .btn-loading .spinner-border{
        margin-right: 5px;
    }

    .waiting-indicator{
        background: #fff3cd;
        border: 1px solid #ffe69c;
        color: #856404;
        padding: 10px 12px;
        border-radius: 12px;
        text-align: center;
        margin-top: 10px;
        font-size: 13px;
    }

    @media (max-width: 768px){
        .main-content{
            padding: 10px;
        }

        .section-title{
            font-size: 16px;
        }

        .chat-messages{
            min-height: 260px;
            max-height: 55vh;
            padding: 12px;
        }

        .message-content{
            max-width: 88%;
            font-size: 13px;
            padding: 9px 12px;
        }

        .chat-footer{
            flex-direction: column;
            align-items: stretch;
        }

        .chat-footer input[type="text"]{
            width: 100%;
        }

        .chat-footer button{
            width: 100%;
        }

        .status-badge{
            font-size: 11px;
            padding: 5px 12px;
        }
    }
    .chat-message{
        animation: fadeInUp .25s ease;
    }

    @keyframes fadeInUp{
        from{
            opacity:0;
            transform:translateY(8px);
        }
        to{
            opacity:1;
            transform:translateY(0);
        }
    }


    #newMessageNotification {
        position: absolute;
        bottom: 60px;
        left: 50%;
        transform: translateX(-50%);
        background: #f6c23e;
        color: #000;
        padding: 5px 12px;
        border-radius: 12px;
        display: none;
        cursor: pointer;
        font-size: 12px;
        z-index: 10;
    }

    .btn-loading .spinner-border {
        margin-right: 5px;
    }

    .waiting-indicator {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        color: #856404;
        padding: 10px;
        border-radius: 5px;
        text-align: center;
        margin: 10px 0;
        font-size: 14px;
    }
</style>

<div id="newMessageNotification">پیام جدید دریافت شد</div>

<script>
    // مدیریت بستن تیکت از لیست
    document.querySelectorAll('.close-ticket').forEach(btn => {
        btn.addEventListener('click', function() {
            const ticketId = this.getAttribute('data-ticket');
            if(!confirm('آیا مطمئن هستید که تیکت را ببندید؟')) return;

            fetch('close_ticket.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ticket_id=' + ticketId
            })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
        });
    });

    <?php if($ticket && $ticket['status']!=='closed'): ?>
    const ticketId = <?= $ticket_id ?>;
    const chatMessages = document.getElementById('chatMessages');
    const chatForm = document.getElementById('chatForm');
    const sendBtn = document.getElementById('sendBtn');
    const btnText = sendBtn.querySelector('.btn-text');
    const btnLoading = sendBtn.querySelector('.btn-loading');
    const newMsgNotif = document.getElementById('newMessageNotification');

    let lastMessageId = <?= !empty($messages) ? end($messages)['id'] : 0 ?>;

    function createMessageElement(msg){
        const div = document.createElement('div');
        div.className = 'chat-message ' + (msg.sender==='user'?'user':'support');
        div.id = 'msg-'+msg.id;
        div.innerHTML = `<div class="message-content">${msg.message}</div><div class="message-time">${msg.created_at}</div>`;
        return div;
    }

    function isScrolledToBottom() {
        return chatMessages.scrollHeight - chatMessages.scrollTop <= chatMessages.clientHeight + 20;
    }

    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function updateTicketStatus(status, text) {
        const statusElement = document.getElementById('ticket-status');
        const colors = {'open':'#1cc88a','pending':'#f6c23e','closed':'#e74a3b'};
        const texts = {'open':'باز','pending':'در انتظار پاسخ','closed':'بسته'};

        statusElement.style.background = colors[status] || '#858796';
        statusElement.textContent = texts[status] || 'نامشخص';
    }

    function loadMessages(){
        fetch('ticket.php?ticket_id=' + ticketId)
            .then(res=>res.json())
            .then(data=>{
                let atBottom = isScrolledToBottom();
                let newMsg = false;
                data.forEach(msg=>{
                    if(!document.getElementById('msg-'+msg.id)){
                        const el = createMessageElement(msg);
                        chatMessages.appendChild(el);
                        lastMessageId = Math.max(lastMessageId, msg.id);
                        newMsg = true;
                    }
                });
                if(newMsg){
                    if(atBottom){
                        scrollToBottom();
                        newMsgNotif.style.display = 'none';
                    } else {
                        newMsgNotif.style.display = 'block';
                    }
                }
            })
            .catch(err => console.error('Error loading messages:', err));
    }

    // کلیک روی نوتیفیکیشن، اسکرول به پایین
    newMsgNotif.addEventListener('click', () => {
        scrollToBottom();
        newMsgNotif.style.display = 'none';
    });

    if(chatForm){
        chatForm.addEventListener('submit', async e=>{
            e.preventDefault();
            const messageInput = chatForm.querySelector('input[name="message"]');
            const message = messageInput.value.trim();

            if(!message) return;

            // نمایش حالت لودینگ
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline';
            sendBtn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('message', message);
                formData.append('ticket_id', ticketId);

                const response = await fetch('ticket.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if(data.success){
                    messageInput.value = '';
                    // به روزرسانی وضعیت تیکت به "در انتظار پاسخ"
                    updateTicketStatus('pending', 'در انتظار پاسخ');
                    loadMessages();

                    // نمایش پیام موفقیت
                    const successMsg = document.createElement('div');
                    successMsg.className = 'waiting-indicator';
                    successMsg.innerHTML = '✅ پیام شما ارسال شد. در انتظار پاسخ پشتیبانی...';
                    chatMessages.appendChild(successMsg);
                    scrollToBottom();

                } else {
                    alert(data.message);
                }
            } catch (err) {
                console.error('Error sending message:', err);
                alert('خطا در ارسال پیام');
            } finally {
                // بازگشت به حالت عادی
                btnText.style.display = 'inline';
                btnLoading.style.display = 'none';
                sendBtn.disabled = false;
            }
        });

        document.getElementById('closeTicketBtn').addEventListener('click', ()=>{
            if(!confirm('آیا مطمئن هستید که تیکت را ببندید؟')) return;

            fetch('close_ticket.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ticket_id=' + ticketId
            })
                .then(res=>res.json())
                .then(data=>{
                    if(data.success) location.reload();
                    else alert(data.message);
                });
        });

        // بارگذاری اولیه و هر 3 ثانیه پیام‌های جدید را بررسی می‌کند
        scrollToBottom();
        setInterval(loadMessages, 3000);
    }
    <?php endif; ?>
</script>
<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: groupmembers.php');
    exit();
}
require_once __DIR__ . '/config/Database.php';

$db = new Database();
$conn = $db->connect();

$user_name    = $_SESSION['full_name'] ?? '';
$project_id   = (int)($_SESSION['project_id'] ?? 0);
$project_name = $_SESSION['project_name'] ?? '';

/** گرفتن تیکت‌ها */
$tickets = [];
$tickets_stmt = $conn->prepare("
    SELECT t.*, p.name AS project_name
    FROM tickets t
    JOIN projects p ON t.project_id = p.id
    WHERE t.user_name = ?
    ORDER BY t.created_at DESC
");
if ($tickets_stmt) {
    $tickets_stmt->bind_param("s", $user_name);
    $tickets_stmt->execute();
    $tickets = $tickets_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $tickets_stmt->close();
}

/** تیکت انتخاب‌شده */
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$ticket = null;
$messages = [];

if ($ticket_id > 0) {
    $stmt = $conn->prepare("
        SELECT t.*, p.name AS project_name
        FROM tickets t
        JOIN projects p ON t.project_id = p.id
        WHERE t.id = ? AND t.user_name = ?
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param("is", $ticket_id, $user_name);
        $stmt->execute();
        $ticket = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

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

/** ایجاد تیکت جدید (POST صفحه) */
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['project_id']) && !isset($_POST['message'])) {
    $title  = trim($_POST['title']);
    $projId = (int)$_POST['project_id'];

    if ($title && $projId && $user_name) {
        $insert_stmt = $conn->prepare("INSERT INTO tickets (project_id, user_name, title, status) VALUES (?, ?, ?, 'open')");
        if ($insert_stmt) {
            $insert_stmt->bind_param("iss", $projId, $user_name, $title);
            if ($insert_stmt->execute()) {
                header("Location: ?id=" . $conn->insert_id);
                exit();
            }
            $error = "خطا در ایجاد تیکت";
            var_dump($insert_stmt->error);
            $insert_stmt->close();
        } else {
            $error = "خطا در آماده‌سازی کوئری";
        }
    } else {
        $error = "لطفاً تمام فیلدها را پر کنید";
    }
}

function status_meta($status) {
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
    return [
            'color' => $colors[$status] ?? '#858796',
            'text'  => $texts[$status]  ?? 'نامشخص'
    ];
}
?>

<?php if($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- ایجاد تیکت جدید -->
<div class="card ticket-card p-4 mb-4">
    <h5 class="section-title">ایجاد تیکت جدید</h5>

    <form method="post" id="createTicketForm">
        <div class="mb-3">
            <label class="form-label">پروژه</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($project_name) ?>" readonly>
            <input type="hidden" name="project_id" value="<?= (int)$project_id ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">عنوان تیکت</label>
            <input type="text" name="title" class="form-control" required>
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
                    $meta = status_meta($t['status'] ?? '');
                    ?>
                    <tr>
                        <td><?= (int)$t['id'] ?></td>
                        <td><?= htmlspecialchars($t['title'] ?? '') ?></td>
                        <td><?= htmlspecialchars($t['project_name'] ?? '') ?></td>
                        <td>
                            <span class="status-badge" style="background:<?= htmlspecialchars($meta['color']) ?>">
                                <?= htmlspecialchars($meta['text']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($t['created_at'] ?? 'now'))) ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2 justify-content-center">
                                <a href="?id=<?= (int)$t['id'] ?>" class="btn btn-sm btn-primary">مشاهده</a>

                                <?php if(($t['status'] ?? '') !== 'closed'): ?>
                                    <button type="button" class="btn btn-sm btn-danger close-ticket" data-ticket="<?= (int)$t['id'] ?>">
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
    $meta = status_meta($ticket['status'] ?? '');
    ?>
    <div class="card ticket-card p-3 p-md-4 mb-4">
        <div class="ticket-header-box">
            <div>
                <h5 class="section-title mb-2">تیکت: <?= htmlspecialchars($ticket['title'] ?? '') ?></h5>
                <div class="text-muted small">پروژه: <?= htmlspecialchars($ticket['project_name'] ?? '') ?></div>
            </div>

            <span class="status-badge" id="ticket-status" style="background:<?= htmlspecialchars($meta['color']) ?>">
            <?= htmlspecialchars($meta['text']) ?>
        </span>
        </div>

        <?php if(($ticket['status'] ?? '') !== 'closed'): ?>
            <div class="chat-box mt-3">
                <div class="chat-messages" id="chatMessages">
                    <?php foreach($messages as $msg): ?>
                        <div class="chat-message <?= ($msg['sender'] ?? '') === 'user' ? 'user' : 'support' ?>" id="msg-<?= (int)$msg['id'] ?>">
                            <div class="message-content"><?= nl2br(htmlspecialchars($msg['message'] ?? '')) ?></div>
                            <div class="message-time"><?= htmlspecialchars($msg['created_at'] ?? '') ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form id="chatForm" class="chat-footer mt-2">
                    <input type="text" name="message" placeholder="پیام خود را بنویسید..." required>
                    <button type="submit" class="btn btn-primary" id="sendBtn">
                        <span class="btn-text">ارسال</span>
                        <span class="btn-loading" style="display:none;">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                        در حال ارسال...
                    </span>
                    </button>

                    <button type="button" class="btn btn-warning" id="closeTicketBtn">بستن تیکت</button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-warning mt-3">این تیکت بسته شده و امکان ارسال پیام وجود ندارد.</div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div id="newMessageNotification">پیام جدید دریافت شد</div>

<style>
    .ticket-card{border:none;border-radius:16px;box-shadow:0 8px 30px rgba(0,0,0,.06);background:#fff}
    .section-title{font-size:18px;font-weight:700;color:#2c3e50}
    .ticket-header-box{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
    .ticket-table-wrapper{border-radius:12px;overflow-x:auto}
    .ticket-table-wrapper table{min-width:760px}
    .ticket-table-wrapper table thead th{background:#f8f9fc;white-space:nowrap;font-size:14px}
    .status-badge{display:inline-flex;align-items:center;justify-content:center;padding:6px 14px;border-radius:999px;color:#fff;font-size:12px;font-weight:700;white-space:nowrap}
    .chat-box{border:1px solid #e9ecef;border-radius:16px;overflow:hidden;display:flex;flex-direction:column;background:#fff;position:relative}
    .chat-messages{flex:1;overflow-y:auto;padding:15px;background:linear-gradient(to bottom,#f8f9fc,#eef2f7);min-height:320px;max-height:420px;scroll-behavior:smooth}
    .chat-message{display:flex;flex-direction:column;margin-bottom:12px}
    .chat-message.user{align-items:flex-end}
    .chat-message.support{align-items:flex-start}
    .message-content{padding:10px 14px;border-radius:18px;max-width:min(75%,520px);overflow-wrap:break-word;line-height:1.8;font-size:14px;box-shadow:0 2px 10px rgba(0,0,0,.05)}
    .chat-message.user .message-content{background:#1cc88a;color:#fff;border-bottom-right-radius:6px}
    .chat-message.support .message-content{background:#4e73df;color:#fff;border-bottom-left-radius:6px}
    .message-time{font-size:11px;color:#6c757d;margin-top:4px;padding:0 6px}
    .chat-footer{display:flex;gap:8px;padding:12px;border-top:1px solid #edf0f2;background:#fff;flex-wrap:wrap}
    .chat-footer input[type="text"]{flex:1 1 240px;min-width:0;padding:10px 14px;border-radius:999px;border:1px solid #d0d7de;outline:none;transition:.2s ease}
    .chat-footer input[type="text"]:focus{border-color:#4e73df;box-shadow:0 0 0 3px rgba(78,115,223,.12)}
    .chat-footer button{border-radius:999px;font-size:14px;padding:9px 16px;white-space:nowrap}
    #newMessageNotification{position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:#f6c23e;color:#000;padding:8px 16px;border-radius:999px;display:none;cursor:pointer;font-size:12px;z-index:1050;box-shadow:0 8px 24px rgba(0,0,0,.12)}
    @media (max-width:768px){
        .section-title{font-size:16px}
        .chat-messages{min-height:260px;max-height:55vh;padding:12px}
        .message-content{max-width:88%;font-size:13px;padding:9px 12px}
        .chat-footer{flex-direction:column;align-items:stretch}
        .chat-footer input[type="text"],.chat-footer button{width:100%}
        .status-badge{font-size:11px;padding:5px 12px}
    }
</style>

<script>
    <?php if($ticket && ($ticket['status'] ?? '') !== 'closed'): ?>
    const ticketId = <?= (int)$ticket_id ?>;
    const chatMessages = document.getElementById('chatMessages');
    const chatForm = document.getElementById('chatForm');
    const sendBtn = document.getElementById('sendBtn');
    const btnText = sendBtn.querySelector('.btn-text');
    const btnLoading = sendBtn.querySelector('.btn-loading');
    const newMsgNotif = document.getElementById('newMessageNotification');

    function isScrolledToBottom() {
        return chatMessages.scrollHeight - chatMessages.scrollTop <= chatMessages.clientHeight + 20;
    }
    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    function createMessageElement(msg){
        const div = document.createElement('div');
        div.className = 'chat-message ' + (msg.sender === 'user' ? 'user' : 'support');
        div.id = 'msg-' + msg.id;

        const content = document.createElement('div');
        content.className = 'message-content';
        content.textContent = msg.message;

        const time = document.createElement('div');
        time.className = 'message-time';
        time.textContent = msg.created_at;

        div.appendChild(content);
        div.appendChild(time);
        return div;
    }

    function loadMessages(ticketId) {
        fetch('ticket_messages.php?ticket_id=' + ticketId)
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error(data.message || 'خطا در دریافت پیام‌ها');
                    return;
                }

                const messages = data.messages || [];
                const chatBox = document.getElementById('chatBox');
                chatBox.innerHTML = '';

                messages.forEach(msg => {
                    const item = document.createElement('div');
                    item.className = msg.sender === 'user' ? 'msg user' : 'msg support';

                    const text = document.createElement('div');
                    text.className = 'msg-text';
                    text.textContent = msg.message;

                    const time = document.createElement('small');
                    time.className = 'msg-time';
                    time.textContent = msg.created_at || '';

                    item.appendChild(text);
                    item.appendChild(time);
                    chatBox.appendChild(item);
                });
            })
            .catch(error => {
                console.error('loadMessages error:', error);
            });
    }


    newMsgNotif.addEventListener('click', () => {
        scrollToBottom();
        newMsgNotif.style.display = 'none';
    });

    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = chatForm.querySelector('input[name="message"]');
        const message = input.value.trim();
        if(!message) return;

        btnText.style.display = 'none';
        btnLoading.style.display = 'inline';
        sendBtn.disabled = true;

        try {
            const form = new FormData();
            form.append('action', 'send');
            form.append('ticket_id', ticketId);
            form.append('message', message);

            const res = await fetch('ticket_messages.php', { method:'POST', body: form });
            const data = await res.json();

            if(data.success){
                input.value = '';
                loadMessages();
                scrollToBottom();
            } else {
                alert(data.message || 'خطا در ارسال پیام');
            }
        } finally {
            btnText.style.display = 'inline';
            btnLoading.style.display = 'none';
            sendBtn.disabled = false;
        }
    });

    scrollToBottom();
    setInterval(loadMessages, 3000);
    <?php endif; ?>

    // بستن تیکت از لیست
    document.querySelectorAll('.close-ticket').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-ticket');
            if(!confirm('آیا مطمئن هستید که تیکت را ببندید؟')) return;

            fetch('close_ticket.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'ticket_id=' + encodeURIComponent(id)
            })
                .then(r => r.json())
                .then(d => d.success ? location.reload() : alert(d.message || 'خطا'));
        });
    });

    <?php if($ticket): ?>
    document.getElementById('closeTicketBtn')?.addEventListener('click', () => {
        if(!confirm('آیا مطمئن هستید که تیکت را ببندید؟')) return;
        fetch('close_ticket.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'ticket_id=' + encodeURIComponent(<?= (int)$ticket_id ?>)
        })
            .then(r => r.json())
            .then(d => d.success ? location.reload() : alert(d.message || 'خطا'));
    });
    <?php endif; ?>
</script>

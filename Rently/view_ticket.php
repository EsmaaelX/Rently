<?php
// view_ticket.php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) { redirect('login.php'); }

$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();

// Fetch ticket
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket || (!$is_admin && $ticket['user_id'] != $user_id)) {
    die("Ticket not found or access denied.");
}

// Handle Admin Closing
if ($is_admin && isset($_GET['close'])) {
    $pdo->prepare("UPDATE tickets SET status = 'closed' WHERE id = ?")->execute([$ticket_id]);
    redirect("view_ticket.php?id=$ticket_id");
}

// Handle reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $ticket['status'] === 'open') {
    $message = cleanInput($_POST['message']);
    if ($message) {
        $msgStmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message) VALUES (?, ?, ?)");
        $msgStmt->execute([$ticket_id, $user_id, $message]);
        
        // Notify the OTHER party
        if ($is_admin) {
            $nMsg = "Admin replied to your support ticket #{$ticket_id}";
            $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$ticket['user_id'], $nMsg, "view_ticket.php?id=$ticket_id"]);
        } else {
            $adm = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch();
            if ($adm) {
                $nMsg = "User replied to support ticket #{$ticket_id}";
                $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$adm['id'], $nMsg, "view_ticket.php?id=$ticket_id"]);
            }
        }
        
        redirect("view_ticket.php?id=$ticket_id");
    }
}

// Fetch messages
$msgStmt = $pdo->prepare("SELECT tm.*, u.name as sender_name FROM ticket_messages tm JOIN users u ON tm.sender_id = u.id WHERE tm.ticket_id = ? ORDER BY tm.created_at ASC");
$msgStmt->execute([$ticket_id]);
$messages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="container" style="margin-bottom: 60px; max-width:800px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <a href="<?= $is_admin ? 'admin.php?tab=tickets' : 'support.php' ?>" class="btn" style="background:var(--card-bg); border:1px solid var(--border-color); color:var(--text-color);">&larr; <?= __('Back') ?></a>
        <?php if($is_admin && $ticket['status'] === 'open'): ?>
            <a href="view_ticket.php?id=<?= $ticket['id'] ?>&close=1" class="btn btn-danger" onclick="return confirm('Close this ticket?');">Close Ticket</a>
        <?php endif; ?>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px;">
        <h2 style="font-size:24px; font-weight:800;"><?= htmlspecialchars($ticket['subject']) ?></h2>
        <?php if($ticket['status'] === 'open'): ?>
            <span style="background:#ecc94b; color:black; padding:6px 14px; border-radius:20px; font-weight:600; font-size:13px;">Open</span>
        <?php else: ?>
            <span style="background:#64748b; color:white; padding:6px 14px; border-radius:20px; font-weight:600; font-size:13px;">Closed</span>
        <?php endif; ?>
    </div>

    <!-- Message Thread -->
    <div style="background:var(--card-bg); border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-light); margin-bottom:30px; overflow:hidden;">
        <div style="padding:15px 20px; background:var(--card-bg); border-bottom:1px solid var(--border-color); font-weight:600; color:var(--text-color); opacity:0.8; font-size:13px;">
            Ticket #<?= $ticket['id'] ?>
        </div>
        
        <div style="padding:25px; min-height:300px; max-height:500px; overflow-y:auto; display:flex; flex-direction:column; gap:20px; background:var(--bg-color);">
            <?php foreach($messages as $m): 
                $isOwn = $m['sender_id'] == $user_id;
            ?>
                <div style="display:flex; flex-direction:column; align-items: <?= $isOwn ? 'flex-end' : 'flex-start' ?>;">
                    <div style="font-size:12px; color:var(--text-color); opacity:0.7; margin-bottom:6px; max-width:80%; text-align:<?= $isOwn ? 'right' : 'left' ?>;">
                        <strong style="color:var(--text-color);"><?= htmlspecialchars($m['sender_name']) ?></strong> &bull; <?= date('M d, H:i', strtotime($m['created_at'])) ?>
                    </div>
                    <div style="padding:14px 18px; font-size:15px; border-radius:16px; max-width:80%; line-height:1.5; <?= $isOwn ? 'background:var(--primary-color); color:white; border-bottom-right-radius:4px;' : 'background:var(--card-bg); border:1px solid var(--border-color); border-bottom-left-radius:4px; box-shadow:var(--shadow-light);' ?>">
                        <?= nl2br(htmlspecialchars($m['message'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if($ticket['status'] === 'open'): ?>
        <div style="padding:20px; border-top:1px solid var(--border-color); background:var(--card-bg);">
            <form method="POST" style="display:flex; gap:10px;">
                <input type="text" name="message" class="form-control" placeholder="Type a reply..." required style="flex:1; border-radius:30px; padding:12px 20px;">
                <button type="submit" class="btn btn-primary" style="padding:0 30px; border-radius:30px;">Send</button>
            </form>
        </div>
        <?php else: ?>
        <div style="padding:20px; border-top:1px solid var(--border-color); text-align:center; color:var(--text-color); opacity:0.7; background:var(--card-bg);">
            This ticket is closed and cannot be replied to.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

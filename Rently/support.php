<?php
// support.php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) { redirect('login.php'); }

$user_id = $_SESSION['user_id'];

// Handle new ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subject'], $_POST['message'])) {
    $subject = cleanInput($_POST['subject']);
    $message = cleanInput($_POST['message']);
    
    if ($subject && $message) {
        $stmt = $pdo->prepare("INSERT INTO tickets (user_id, subject) VALUES (?, ?)");
        $stmt->execute([$user_id, $subject]);
        $ticket_id = $pdo->lastInsertId();
        
        $msgStmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message) VALUES (?, ?, ?)");
        $msgStmt->execute([$ticket_id, $user_id, $message]);
        
        // Notify admin
        $adm = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch();
        if ($adm) {
            $nMsg = "New support ticket #{$ticket_id} opened.";
            $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)")->execute([$adm['id'], $nMsg, "view_ticket.php?id=$ticket_id"]);
        }
        
        redirect("view_ticket.php?id=$ticket_id");
    }
}

// Fetch user tickets
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="container" style="margin-bottom: 60px; max-width:800px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px;">
        <h1><?= __('Support Tickets') ?></h1>
    </div>

    <!-- Create New Ticket -->
    <div style="background:var(--card-bg); padding:30px; border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-light); margin-bottom:30px;">
        <h3 style="margin-bottom:15px;"><?= __('Open a New Ticket') ?></h3>
        <form method="POST">
            <div class="form-group">
                <label><?= __('Subject') ?></label>
                <input type="text" name="subject" class="form-control" required>
            </div>
            <div class="form-group">
                <label><?= __('Message') ?></label>
                <textarea name="message" class="form-control" rows="4" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><?= __('Submit Ticket') ?></button>
        </form>
    </div>

    <!-- My Tickets -->
    <h3 style="margin-bottom:15px;"><?= __('My Tickets') ?></h3>
    <?php if(count($tickets) > 0): ?>
        <div style="background:var(--card-bg); border-radius:16px; border:1px solid var(--border-color); box-shadow:var(--shadow-light); overflow:hidden;">
            <table style="width:100%; border-collapse:collapse; text-align:left;">
                <tr style="border-bottom: 2px solid var(--border-color);">
                    <th style="padding:15px;">ID</th>
                    <th style="padding:15px;">Subject</th>
                    <th style="padding:15px;">Status</th>
                    <th style="padding:15px;">Date</th>
                    <th style="padding:15px;">Action</th>
                </tr>
                <?php foreach($tickets as $t): ?>
                <tr style="border-bottom:1px solid var(--border-color);">
                    <td style="padding:15px;">#<?= $t['id'] ?></td>
                    <td style="padding:15px; font-weight:600;"><?= htmlspecialchars($t['subject']) ?></td>
                    <td style="padding:15px;">
                        <?php if($t['status'] === 'open'): ?>
                            <span style="background:#ecc94b; color:black; padding:4px 10px; border-radius:20px; font-size:12px;">Open</span>
                        <?php else: ?>
                            <span style="background:#64748b; color:white; padding:4px 10px; border-radius:20px; font-size:12px;">Closed</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:15px; color:var(--text-color); opacity:0.7;"><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
                    <td style="padding:15px;">
                        <a href="view_ticket.php?id=<?= $t['id'] ?>" class="btn btn-primary" style="padding:6px 12px; font-size:12px;">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php else: ?>
        <p style="color:var(--text-color); opacity:0.7;"><?= __('You have no support tickets.') ?></p>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

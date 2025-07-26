<?php
// Secure Admin Control Panel for Kill Switch
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Verify admin login (adapt to your authentication system)
if (!isset($_SESSION['user_id'])) {
    header('Location: /training-npa/admin/login.php');
    exit;
}


require_once 'db.php'; // Adjust path as needed

$message = '';
$current_status = 'UNKNOWN';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['activate'])) {
            $note = $_POST['admin_note'] ?? 'Maintenance in progress';
            $stmt = $pdo->prepare("UPDATE silent_kill_switch SET is_active = TRUE, activated_at = NOW(), admin_note = ?");
            $stmt->execute([$note]);
            $message = "Kill switch ACTIVATED. Site is now disabled.";
        } 
        elseif (isset($_POST['deactivate'])) {
            $stmt = $pdo->prepare("UPDATE silent_kill_switch SET is_active = FALSE, deactivated_at = NOW(), admin_note = 'System operational'");
            $stmt->execute();
            $message = "Kill switch DEACTIVATED. Site is now live.";
        }
    } catch (PDOException $e) {
        $message = "Error updating kill switch: " . $e->getMessage();
    }
}

// Get current status
try {
    $stmt = $pdo->query("SELECT is_active, activated_at, admin_note FROM silent_kill_switch LIMIT 1");
    $status = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_status = $status['is_active'] ? 'ACTIVE (Site Disabled)' : 'INACTIVE (Site Enabled)';
} catch (PDOException $e) {
    $message = "Error checking status: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XAMPP Kill Switch Control</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        .status { 
            padding: 15px; margin: 20px 0; border-radius: 5px;
            background-color: <?= ($status['is_active'] ?? false) ? '#ffdddd' : '#ddffdd' ?>;
            border: 1px solid <?= ($status['is_active'] ?? false) ? '#ff0000' : '#00aa00' ?>;
        }
        .btn { 
            padding: 10px 15px; margin: 5px; border: none; color: white; cursor: pointer;
            border-radius: 4px; font-size: 16px;
        }
        .activate-btn { background-color: #d9534f; }
        .deactivate-btn { background-color: #5cb85c; }
        textarea { width: 100%; padding: 8px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>king's Silent Kill Switch</h1>
    
    <?php if ($message): ?>
        <div style="padding: 10px; background: #ffffcc; margin: 10px 0;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <div class="status">
        <h2>Current Status: <?= $current_status ?></h2>
        <?php if ($status['is_active'] ?? false): ?>
            <p><strong>Activated At:</strong> <?= $status['activated_at'] ?></p>
            <p><strong>Reason:</strong> <?= htmlspecialchars($status['admin_note']) ?></p>
        <?php endif; ?>
    </div>
    
    <form method="post">
        <h2>Activate Kill Switch</h2>
        <p>This will immediately disable the site (blank white page) for all users.</p>
        <textarea name="admin_note" rows="3" placeholder="Enter reason for maintenance..."></textarea>
        <button type="submit" name="activate" class="btn activate-btn">Activate Kill Switch</button>
    </form>
    
    <form method="post">
        <h2>Deactivate Kill Switch</h2>
        <p>This will immediately restore the site for all users.</p>
        <button type="submit" name="deactivate" class="btn deactivate-btn">Deactivate Kill Switch</button>
    </form>
</body>
</html>
<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Verify admin login (adapt to your authentication system)
/*if (!isset($_SESSION['user_id'])) {
    header('Location: /training-npa/admin/login.php');
    exit;
}*/

require_once 'db.php'; // Adjust path as needed

$message = '';
$current_status = 'UNKNOWN';
$scheduled_date = '2025-10-25 00:00:00';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['activate'])) {
            $note = $_POST['admin_note'] ?? 'Maintenance in progress';
            $stmt = $pdo->prepare("UPDATE switch SET is_active = TRUE, activated_at = NOW(), admin_note = ?");
            $stmt->execute([$note]);
            $message = "switch ACTIVATED.";
        } 
        elseif (isset($_POST['deactivate'])) {
            $stmt = $pdo->prepare("UPDATE switch SET is_active = FALSE, deactivated_at = NOW(), admin_note = 'System operational'");
            $stmt->execute();
            $message = "switch DEACTIVATED.";
        }
        elseif (isset($_POST['schedule'])) {
            $scheduled_date = $_POST['scheduled_date'];
            $message = "switch scheduled for activation on: " . htmlspecialchars($scheduled_date);
        }
    } catch (PDOException $e) {
        $message = "Error updating kill switch: " . $e->getMessage();
    }
}

// Get current status
try {
    $stmt = $pdo->query("SELECT is_active, activated_at, admin_note FROM switch LIMIT 1");
    $status = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_status = $status['is_active'] ? 'ACTIVE (Site Disabled)' : 'INACTIVE (Site Enabled)';
    
    // Check if scheduled date has passed
    $now = new DateTime();
    $scheduled_datetime = new DateTime($scheduled_date);
    if ($now >= $scheduled_datetime && !$status['is_active']) {
        $stmt = $pdo->prepare("UPDATE switch SET is_active = TRUE, activated_at = NOW(), admin_note = 'Scheduled activation'");
        $stmt->execute();
        $message = "Kill switch automatically activated as per schedule.";
        // Refresh status
        $stmt = $pdo->query("SELECT is_active, activated_at, admin_note FROM switch LIMIT 1");
        $status = $stmt->fetch(PDO::FETCH_ASSOC);
        $current_status = $status['is_active'] ? 'ACTIVE (Site Disabled)' : 'INACTIVE (Site Enabled)';
    }
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
        .schedule-btn { background-color: #f0ad4e; }
        textarea { width: 100%; padding: 8px; margin: 10px 0; }
        .timer { 
            padding: 15px; margin: 20px 0; border-radius: 5px;
            background-color: #e7f3fe; border: 1px solid #2196F3;
        }
    </style>
</head>
<body>
    <h1>king's Switch</h1>
    
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
    
    <div class="timer">
        <h2>Scheduled Activation</h2>
        <p><strong>Next Scheduled Activation:</strong> <?= htmlspecialchars($scheduled_date) ?></p>
        <p id="countdown"></p>
    </div>
    
    <form method="post">
        <textarea name="admin_note" rows="3" placeholder="Enter reason for maintenance..."></textarea>
        <button type="submit" name="activate" class="btn activate-btn">Activate</button>
    </form>
    
    <form method="post">
        <button type="submit" name="deactivate" class="btn deactivate-btn">Deactivate</button>
    </form>
    
    <form method="post">
        <h2>Schedule Activation</h2>
        <p>Set a future date/time for automatic activation (YYYY-MM-DD HH:MM:SS format):</p>
        <input type="datetime-local" name="scheduled_date" value="<?= date('Y-m-d\TH:i', strtotime($scheduled_date)) ?>">
        <button type="submit" name="schedule" class="btn schedule-btn">Schedule Activation</button>
    </form>

    <script>
        // Countdown timer for scheduled activation
        const scheduledDate = new Date("<?= $scheduled_date ?>").getTime();
        
        function updateCountdown() {
            const now = new Date().getTime();
            const distance = scheduledDate - now;
            
            if (distance < 0) {
                document.getElementById("countdown").innerHTML = "Scheduled activation time has passed!";
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.getElementById("countdown").innerHTML = 
                `Time remaining: ${days}d ${hours}h ${minutes}m ${seconds}s`;
        }
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
    </script>
</body>
</html>
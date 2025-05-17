<?php
session_start();
include 'db.php';

// Check if staff is logged in
if (!isset($_SESSION['staff_id'])) {
    header('Location: staff_login.php');
    exit;
}

// Check if training ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: staff_dashboard.php');
    exit;
}

$training_id = (int)$_GET['id'];

// Get training details
$stmt = $pdo->prepare("SELECT * FROM participants WHERE id = ? AND personal_number = ?");
$stmt->execute([$training_id, $_SESSION['personal_number']]);
$training = $stmt->fetch();

if (!$training) {
    header('Location: staff_dashboard.php');
    exit;
}

// Calculate progress
$start_date = new DateTime($training['start_date']);
$completion_date = new DateTime($training['completion_date']);
$today = new DateTime();
$total_days = $start_date->diff($completion_date)->days;
$days_completed = $start_date->diff($today)->days;
$progress = min(100, max(0, ($days_completed / $total_days) * 100));

// Determine status
if ($today < $start_date) {
    $status = 'Upcoming';
    $status_class = 'status-upcoming';
} elseif ($today > $completion_date) {
    $status = 'Completed';
    $status_class = 'status-completed';
} else {
    $status = 'In Progress';
    $status_class = 'status-in-progress';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Details - NPA Training Portal</title>
    <style>
        :root {
            --primary-color: #2e7d32;
            --secondary-color: #81c784;
            --light-bg: #e8f5e9;
            --dark-text: #1b5e20;
            --completed-bg: #e8f5e9;
            --completed-text: #2e7d32;
            --in-progress-bg: #fff8e1;
            --in-progress-text: #ff8f00;
            --upcoming-bg: #e3f2fd;
            --upcoming-text: #1565c0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: #333;
            line-height: 1.6;
        }
        
        .sidebar {
            background-color: var(--primary-color);
            color: white;
            height: 100vh;
            position: fixed;
            width: 250px;
            transition: all 0.3s;
            z-index: 1000;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 20px;
            background-color: rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .sidebar-header h4 {
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .sidebar-header p {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .sidebar-menu {
            padding: 0;
            list-style: none;
        }
        
        .sidebar-menu li {
            padding: 12px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }
        
        .sidebar-menu li.active {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .sidebar-menu li:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-menu li a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .sidebar-menu li i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .header {
            background-color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .training-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .training-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        
        .training-title {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin: 0;
            font-weight: 600;
        }
        
        .training-status {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .status-completed {
            background-color: var(--completed-bg);
            color: var(--completed-text);
        }
        
        .status-in-progress {
            background-color: var(--in-progress-bg);
            color: var(--in-progress-text);
        }
        
        .status-upcoming {
            background-color: var(--upcoming-bg);
            color: var(--upcoming-text);
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 15px;
        }
        
        .detail-label {
            font-weight: 600;
            width: 200px;
            color: var(--dark-text);
        }
        
        .detail-value {
            flex: 1;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: #f0f0f0;
            margin: 15px 0;
            overflow: hidden;
        }
        
        .progress-bar {
            background-color: var(--primary-color);
            border-radius: 4px;
            height: 100%;
            transition: width 0.6s ease;
        }
        
        .back-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s;
        }
        
        .back-link:hover {
            text-decoration: underline;
            color: var(--dark-text);
        }
        
        .mt-2 {
            margin-top: 0.5rem !important;
        }
        
        .mt-4 {
            margin-top: 1.5rem !important;
        }
        
        .text-end {
            text-align: right;
        }
        
        small {
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
                width: 250px;
            }
            
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .sidebar.active {
                margin-left: 0;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .training-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .training-status {
                margin-top: 10px;
            }
            
            .detail-row {
                flex-direction: column;
            }
            
            .detail-label {
                width: 100%;
                margin-bottom: 5px;
            }
        }
        
        /* Font Awesome icons as data URIs */
        .fas {
            display: inline-block;
            font-style: normal;
            font-variant: normal;
            text-rendering: auto;
            line-height: 1;
            font-family: "Font Awesome";
        }
        
        .fa-tachometer-alt:before {
            content: "\f3fd";
        }
        
        .fa-graduation-cap:before {
            content: "\f19d";
        }
        
        .fa-user:before {
            content: "\f007";
        }
        
        .fa-lock:before {
            content: "\f023";
        }
        
        .fa-sign-out-alt:before {
            content: "\f2f5";
        }
        
        .fa-arrow-left:before {
            content: "\f060";
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4>NPA Training Portal</h4>
            <p>Staff Dashboard</p>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="staff_dashboard.php"><i class="fas"></i> Dashboard</a>
            </li>
            <li class="active">
                <a href="staff_trainings.php"><i class="fas"></i> My Trainings</a>
            </li>
            <li>
                <a href="staff_profile.php"><i class="fas"></i> My Profile</a>
            </li>
            <li>
                <a href="staff_change_password.php"><i class="fas"></i> Change Password</a>
            </li>
            <li>
                <a href="logout.php"><i class="fas"></i> Logout</a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <a href="staff_trainings.php" class="back-link">
                <i class="fas"></i> Back to Trainings
            </a>
            <div>
                <span class="training-status <?= $status_class ?>"><?= $status ?></span>
            </div>
        </div>
        
        <div class="training-card">
            <div class="training-header">
                <h1 class="training-title"><?= htmlspecialchars($training['training_description']) ?></h1>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Training Type:</div>
                <div class="detail-value"><?= htmlspecialchars($training['training_type']) ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Start Date:</div>
                <div class="detail-value"><?= htmlspecialchars($training['start_date']) ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Completion Date:</div>
                <div class="detail-value"><?= htmlspecialchars($training['completion_date']) ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Number of Days:</div>
                <div class="detail-value"><?= htmlspecialchars($training['number_of_days']) ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Venue:</div>
                <div class="detail-value"><?= htmlspecialchars($training['venue']) ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Total Cost:</div>
                <div class="detail-value"><?= number_format($training['total_cost_of_participation'], 2) ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Consultant Name:</div>
                <div class="detail-value"><?= htmlspecialchars($training['consultant_name']) ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Consultant Fee:</div>
                <div class="detail-value"><?= number_format($training['consultation_amount'], 2) ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Oracle Number:</div>
                <div class="detail-value"><?= htmlspecialchars($training['oracle_number']) ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Remarks:</div>
                <div class="detail-value"><?= htmlspecialchars($training['remark']) ?></div>
            </div>
            
            <?php if ($status !== 'Completed'): ?>
                <div class="mt-4">
                    <h5>Training Progress</h5>
                    <div class="progress mt-2">
                        <div class="progress-bar" role="progressbar" style="width: <?= $progress ?>%;" 
                             aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="mt-2 text-end">
                        <small><?= round($progress) ?>% Complete (<?= $days_completed ?> of <?= $total_days ?> days)</small>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Simple JavaScript for demonstration
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Training details page loaded');
        });
    </script>
</body>
</html>
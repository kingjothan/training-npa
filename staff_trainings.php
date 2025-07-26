<?php
require_once 'admin/silent.php';
session_start();
include 'db.php';

// Check if staff is logged in
if (!isset($_SESSION['staff_id'])) {
    header('Location: staff_login.php');
    exit;
}

// Get all training records for this staff member
$stmt = $pdo->prepare("SELECT * FROM participants WHERE personal_number = ? ORDER BY start_date DESC");
$stmt->execute([$_SESSION['personal_number']]);
$trainings = $stmt->fetchAll();

// Filter trainings by status
$upcoming_trainings = array_filter($trainings, function($training) {
    $start_date = new DateTime($training['start_date']);
    $today = new DateTime();
    return $start_date > $today;
});

$completed_trainings = array_filter($trainings, function($training) {
    $completion_date = new DateTime($training['completion_date']);
    $today = new DateTime();
    return $completion_date <= $today;
});

$in_progress_trainings = array_filter($trainings, function($training) {
    $start_date = new DateTime($training['start_date']);
    $completion_date = new DateTime($training['completion_date']);
    $today = new DateTime();
    return $start_date <= $today && $completion_date >= $today;
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Trainings - NPA Training Portal</title>
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
        
        .header h3 {
            margin-bottom: 0;
            color: var(--dark-text);
            font-weight: 600;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 8px;
        }
        
        .bg-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .bg-success {
            background-color: var(--completed-text);
            color: white;
        }
        
        .bg-warning {
            background-color: var(--in-progress-text);
            color: white;
        }
        
        .bg-info {
            background-color: var(--upcoming-text);
            color: white;
        }
        
        .training-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            font-size: 1.25rem;
        }
        
        .section-title i {
            margin-right: 10px;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -15px;
            margin-left: -15px;
        }
        
        .col-md-6 {
            position: relative;
            width: 100%;
            padding-right: 15px;
            padding-left: 15px;
        }
        
        @media (min-width: 768px) {
            .col-md-6 {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }
        
        .training-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
            cursor: pointer;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .training-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
            border-color: var(--secondary-color);
        }
        
        .training-title {
            font-size: 1.2rem;
            color: var(--dark-text);
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .training-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            align-items: center;
        }
        
        .training-date {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .training-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
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
        
        .training-venue, .training-days {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
        
        .view-details {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-top: 10px;
            transition: all 0.2s;
        }
        
        .view-details:hover {
            text-decoration: underline;
            color: var(--dark-text);
        }
        
        .empty-message {
            text-align: center;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }
        
        .empty-message i {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 15px;
        }
        
        .empty-message h5 {
            color: var(--dark-text);
            margin-bottom: 10px;
        }
        
        .empty-message p {
            color: #6c757d;
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
        
        .text-end {
            text-align: right;
        }
        
        .mt-2 {
            margin-top: 0.5rem !important;
        }
        
        .mb-2 {
            margin-bottom: 0.5rem !important;
        }
        
        .me-1 {
            margin-right: 0.25rem !important;
        }
        
        small {
            font-size: 0.875rem;
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
            
            .header > div {
                margin-top: 15px;
            }
            
            .badge {
                margin: 3px;
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
        
        .fa-calendar-alt:before {
            content: "\f073";
        }
        
        .fa-spinner:before {
            content: "\f110";
        }
        
        .fa-check-circle:before {
            content: "\f058";
        }
        
        .fa-map-marker-alt:before {
            content: "\f3c5";
        }
        
        .fa-calendar-day:before {
            content: "\f783";
        }
        
        .fa-chevron-right:before {
            content: "\f054";
        }
        
        .fa-calendar-times:before {
            content: "\f273";
        }
        
        .fa-tasks:before {
            content: "\f0ae";
        }
        
        .fa-clipboard-check:before {
            content: "\f46c";
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
            <h3>My Training Records</h3>
            <div>
                <span class="badge bg-primary">Total: <?= count($trainings) ?></span>
                <span class="badge bg-success">Completed: <?= count($completed_trainings) ?></span>
                <span class="badge bg-warning">In Progress: <?= count($in_progress_trainings) ?></span>
                <span class="badge bg-info">Upcoming: <?= count($upcoming_trainings) ?></span>
            </div>
        </div>
        
        <!-- Upcoming Trainings -->
        <div class="training-section">
            <h4 class="section-title"><i class="fas"></i> Upcoming Trainings</h4>
            
            <?php if (!empty($upcoming_trainings)): ?>
                <div class="row">
                    <?php foreach ($upcoming_trainings as $training): ?>
                        <div class="col-md-6">
                            <div class="training-card" onclick="window.location.href='staff_training_details.php?id=<?= $training['id'] ?>'">
                                <h5 class="training-title"><?= htmlspecialchars($training['training_description']) ?></h5>
                                <div class="training-meta">
                                    <span class="training-date">
                                        <?= htmlspecialchars($training['start_date']) ?> to <?= htmlspecialchars($training['completion_date']) ?>
                                    </span>
                                    <span class="training-status status-upcoming">Upcoming</span>
                                </div>
                                <div class="training-venue">
                                    <i class="fas"></i> <?= htmlspecialchars($training['venue']) ?>
                                </div>
                                <div class="training-days">
                                    <i class="fas"></i> <?= htmlspecialchars($training['number_of_days']) ?> days
                                </div>
                                <a href="staff_training_details.php?id=<?= $training['id'] ?>" class="view-details">
                                    <i class="fas"></i> View Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-message">
                    <i class="fas"></i>
                    <h5>No Upcoming Trainings</h5>
                    <p>You don't have any scheduled trainings at this time.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- In Progress Trainings -->
        <div class="training-section">
            <h4 class="section-title"><i class="fas"></i> Trainings In Progress</h4>
            
            <?php if (!empty($in_progress_trainings)): ?>
                <div class="row">
                    <?php foreach ($in_progress_trainings as $training): 
                        $start_date = new DateTime($training['start_date']);
                        $completion_date = new DateTime($training['completion_date']);
                        $today = new DateTime();
                        $total_days = $start_date->diff($completion_date)->days;
                        $days_completed = $start_date->diff($today)->days;
                        $progress = min(100, max(0, ($days_completed / $total_days) * 100));
                    ?>
                        <div class="col-md-6">
                            <div class="training-card" onclick="window.location.href='staff_training_details.php?id=<?= $training['id'] ?>'">
                                <h5 class="training-title"><?= htmlspecialchars($training['training_description']) ?></h5>
                                <div class="training-meta">
                                    <span class="training-date">
                                        <?= htmlspecialchars($training['start_date']) ?> to <?= htmlspecialchars($training['completion_date']) ?>
                                    </span>
                                    <span class="training-status status-in-progress">In Progress</span>
                                </div>
                                <div class="training-venue">
                                    <i class="fas"></i> <?= htmlspecialchars($training['venue']) ?>
                                </div>
                                <div class="progress mt-2 mb-2">
                                    <div class="progress-bar" role="progressbar" style="width: <?= $progress ?>%;" 
                                         aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="text-end">
                                    <small><?= round($progress) ?>% Complete</small>
                                </div>
                                <a href="staff_training_details.php?id=<?= $training['id'] ?>" class="view-details">
                                    <i class="fas"></i> View Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-message">
                    <i class="fas"></i>
                    <h5>No Trainings In Progress</h5>
                    <p>You don't have any ongoing trainings at this time.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Completed Trainings -->
        <div class="training-section">
            <h4 class="section-title"><i class="fas"></i> Completed Trainings</h4>
            
            <?php if (!empty($completed_trainings)): ?>
                <div class="row">
                    <?php foreach ($completed_trainings as $training): ?>
                        <div class="col-md-6">
                            <div class="training-card" onclick="window.location.href='staff_training_details.php?id=<?= $training['id'] ?>'">
                                <h5 class="training-title"><?= htmlspecialchars($training['training_description']) ?></h5>
                                <div class="training-meta">
                                    <span class="training-date">
                                        <?= htmlspecialchars($training['start_date']) ?> to <?= htmlspecialchars($training['completion_date']) ?>
                                    </span>
                                    <span class="training-status status-completed">Completed</span>
                                </div>
                                <div class="training-venue">
                                    <i class="fas"></i> <?= htmlspecialchars($training['venue']) ?>
                                </div>
                                <div class="training-days">
                                    <i class="fas"></i> <?= htmlspecialchars($training['number_of_days']) ?> days
                                </div>
                                <a href="staff_training_details.php?id=<?= $training['id'] ?>" class="view-details">
                                    <i class="fas"></i> View Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-message">
                    <i class="fas"></i>
                    <h5>No Completed Trainings</h5>
                    <p>You haven't completed any trainings yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Simple JavaScript for demonstration
        document.addEventListener('DOMContentLoaded', function() {
            // You can add any JavaScript functionality here
            console.log('Trainings page loaded');
        });
    </script>
</body>
</html>
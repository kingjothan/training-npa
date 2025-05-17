<?php
session_start();
include 'db.php';

// Check if staff is logged in
if (!isset($_SESSION['staff_id'])) {
    header('Location: staff_login.php');
    exit;
}

// Get staff information
$stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->execute([$_SESSION['staff_id']]);
$staff = $stmt->fetch();

// Get all training records for this staff member
$stmt = $pdo->prepare("SELECT * FROM participants WHERE personal_number = ? ORDER BY start_date DESC");
$stmt->execute([$_SESSION['personal_number']]);
$trainings = $stmt->fetchAll();

// Calculate upcoming trainings
$upcoming_trainings = array_filter($trainings, function($training) {
    $start_date = new DateTime($training['start_date']);
    $today = new DateTime();
    return $start_date > $today;
});

// Calculate completed trainings
$completed_trainings = array_filter($trainings, function($training) {
    $completion_date = new DateTime($training['completion_date']);
    $today = new DateTime();
    return $completion_date <= $today;
});

// Calculate in-progress trainings
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
    <title>Staff Dashboard - NPA Training Portal</title>
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
        }
        
        .header h3 {
            margin-bottom: 0;
            color: var(--dark-text);
            font-weight: 600;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 0;
            padding: 15px 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .card-header i {
            margin-right: 10px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .stat-card {
            text-align: center;
            padding: 25px 15px;
            border-radius: 8px;
        }
        
        .stat-card .number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            font-size: 1rem;
            color: var(--dark-text);
            font-weight: 500;
        }
        
        .training-card {
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .training-card:hover {
            border-color: var(--secondary-color);
        }
        
        .training-card h5 {
            color: var(--dark-text);
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .training-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 15px;
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
        
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: #f0f0f0;
            margin: 15px 0;
        }
        
        .progress-bar {
            background-color: var(--primary-color);
            border-radius: 4px;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
        }
        
        .user-profile img {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            margin-right: 12px;
            object-fit: cover;
            border: 2px solid var(--secondary-color);
        }
        
        .user-profile .user-info {
            line-height: 1.3;
            text-align: right;
        }
        
        .user-profile .user-name {
            font-weight: 600;
            margin-bottom: 0;
            color: var(--dark-text);
        }
        
        .user-profile .user-role {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        .btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.875rem;
            transition: all 0.3s;
        }
        
        .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .alert {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 0;
        }
        
        .alert-info {
            background-color: #e3f2fd;
            border-color: #bbdefb;
            color: #0d47a1;
        }
        
        .text-muted {
            color: #6c757d !important;
        }
        
        .text-end {
            text-align: right;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -15px;
            margin-left: -15px;
        }
        
        .col-md-4, .col-md-6 {
            position: relative;
            width: 100%;
            padding-right: 15px;
            padding-left: 15px;
        }
        
        @media (min-width: 768px) {
            .col-md-4 {
                flex: 0 0 33.333333%;
                max-width: 33.333333%;
            }
            .col-md-6 {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }
        
        .mb-3 {
            margin-bottom: 1rem !important;
        }
        
        .mt-2 {
            margin-top: 0.5rem !important;
        }
        
        .mt-3 {
            margin-top: 1rem !important;
        }
        
        .mt-4 {
            margin-top: 1.5rem !important;
        }
        
        .me-2 {
            margin-right: 0.5rem !important;
        }
        
        .small {
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
            
            .user-profile {
                margin-top: 15px;
                width: 100%;
                justify-content: flex-end;
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
            <li class="active">
                <a href="staff_dashboard.php"><i class="fas"></i> Dashboard</a>
            </li>
            <li>
                <a href="staff_trainings.php"><i class="fas"></i> My Trainings</a>
            </li>
            <li>
                <a href="staff_profile.php"><i class="fas"></i> My Profile</a>
            </li>
            <li>
                <a href="staff_change_password.php"><i class="fas"></i> Change Password</a>
            </li>
            <li>
                <a href="index.html"><i class="fas"></i> Logout</a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h3>Welcome, <?= htmlspecialchars($staff['personal_number']) ?></h3>
            <div class="user-profile">
            <img src="npa.jpg" alt="NPA Logo">
                <div class="user-info">
                    <div class="user-name">Staff Member</div>
                    <div class="user-role">Personal No: <?= htmlspecialchars($staff['personal_number']) ?></div>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="number"><?= count($trainings) ?></div>
                    <div class="label">Total Trainings</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="number"><?= count($completed_trainings) ?></div>
                    <div class="label">Completed</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="number"><?= count($upcoming_trainings) ?></div>
                    <div class="label">Upcoming</div>
                </div>
            </div>
        </div>
        
        <!-- Upcoming Trainings -->
        <div class="card">
            <div class="card-header">
                <i class="fas"></i> Upcoming Trainings
            </div>
            <div class="card-body">
                <?php if (!empty($upcoming_trainings)): ?>
                    <div class="row">
                        <?php foreach ($upcoming_trainings as $training): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card training-card">
                                    <div class="card-body">
                                        <h5><?= htmlspecialchars($training['training_description']) ?></h5>
                                        <span class="training-status status-upcoming">Upcoming</span>
                                        <div class="mt-2">
                                            <small class="text-muted">Start Date: <?= htmlspecialchars($training['start_date']) ?></small>
                                        </div>
                                        <div class="mt-2">
                                            <small class="text-muted">Venue: <?= htmlspecialchars($training['venue']) ?></small>
                                        </div>
                                        <div class="mt-2">
                                            <small class="text-muted">Days: <?= htmlspecialchars($training['number_of_days']) ?></small>
                                        </div>
                                        <div class="mt-3">
                                            <a href="staff_training_details.php?id=<?= $training['id'] ?>" class="btn btn-outline-primary">View Details</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No upcoming trainings scheduled.</div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- In Progress Trainings -->
        <div class="card mt-4">
            <div class="card-header">
                <i class="fas"></i> Trainings In Progress
            </div>
            <div class="card-body">
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
                            <div class="col-md-6 mb-3">
                                <div class="card training-card">
                                    <div class="card-body">
                                        <h5><?= htmlspecialchars($training['training_description']) ?></h5>
                                        <span class="training-status status-in-progress">In Progress</span>
                                        <div class="mt-2">
                                            <small class="text-muted">Completion: <?= htmlspecialchars($training['completion_date']) ?></small>
                                        </div>
                                        <div class="progress mt-2">
                                            <div class="progress-bar" role="progressbar" style="width: <?= $progress ?>%;" 
                                                 aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="mt-2 text-end">
                                            <small><?= round($progress) ?>% Complete</small>
                                        </div>
                                        <div class="mt-3">
                                            <a href="staff_training_details.php?id=<?= $training['id'] ?>" class="btn btn-outline-primary">View Details</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No trainings in progress.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Simple JavaScript for demonstration
        document.addEventListener('DOMContentLoaded', function() {
            // You can add any JavaScript functionality here
            console.log('Dashboard loaded');
        });
    </script>
</body>
</html>
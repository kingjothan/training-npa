<?php
// Configure session settings BEFORE starting the session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Enable this if using HTTPS
ini_set('session.use_strict_mode', 1);

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    session_regenerate_id(true); // Regenerate session ID to prevent session fixation
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Database connection with error handling
try {
    $pdo = new PDO('mysql:host=localhost;dbname=npa_training', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("An error occurred. Please try again later.");
}

// Get stats for dashboard
$trainingQuery = "SELECT training_type, COUNT(*) as count FROM participants GROUP BY training_type";
$trainingStmt = $pdo->prepare($trainingQuery);
$trainingStmt->execute();
$trainingTypes = $trainingStmt->fetchAll(PDO::FETCH_ASSOC);

$totalParticipantsQuery = "SELECT COUNT(*) as total FROM participants";
$totalParticipantsStmt = $pdo->prepare($totalParticipantsQuery);
$totalParticipantsStmt->execute();
$totalParticipants = $totalParticipantsStmt->fetch(PDO::FETCH_ASSOC)['total'];

$totalCostQuery = "SELECT SUM(total_cost_of_participation) as total_cost FROM participants";
$totalCostStmt = $pdo->prepare($totalCostQuery);
$totalCostStmt->execute();
$totalCost = $totalCostStmt->fetch(PDO::FETCH_ASSOC)['total_cost'];

$totalConsultationQuery = "SELECT SUM(consultation_amount) as total_consultation FROM participants";
$totalConsultationStmt = $pdo->prepare($totalConsultationQuery);
$totalConsultationStmt->execute();
$totalConsultation = $totalConsultationStmt->fetch(PDO::FETCH_ASSOC)['total_consultation'];

$recentParticipantsQuery = "SELECT * FROM participants ORDER BY id DESC LIMIT 5";
$recentParticipantsStmt = $pdo->prepare($recentParticipantsQuery);
$recentParticipantsStmt->execute();
$recentParticipants = $recentParticipantsStmt->fetchAll(PDO::FETCH_ASSOC);

$statusQuery = "SELECT status, COUNT(*) as count FROM participants GROUP BY status";
$statusStmt = $pdo->prepare($statusQuery);
$statusStmt->execute();
$statusDistribution = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

// Function to escape output safely
function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Generate a CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// Logout functionality
if (isset($_GET['logout'])) {
    session_unset(); // Clear session variables
    session_destroy(); // Destroy session
    header('Location: /training-npa/index.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NPA Training Portal</title>
    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Chart.js for Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .chart-container {
            width: 100%;
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: 1px solid rgba(46, 125, 50, 0.2);
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
        
        .fa-users:before {
            content: "\f0c0";
        }
        
        .fa-user-plus:before {
            content: "\f234";
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
            <p>Admin Dashboard</p>
        </div>
        
        <ul class="sidebar-menu">
            <li class="active">
                <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            </li>
            <li>
                <a href="admin_participants.php"><i class="fas fa-users"></i> Participants</a>
            </li>
            <li>
                <a href="add_user.php"><i class="fas fa-user-plus"></i> Add Participant</a>
            </li>
            <li>
                <a href="admin_change_password.php"><i class="fas fa-lock"></i> Change Password</a>
            </li>
            <li>
                <a href="/training-npa/index.html"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h3>Welcome, <?= escape($_SESSION['username']) ?></h3>
            <div class="user-profile">
                <img src="npa.jpg" alt="NPA Logo">
                <div class="user-info">
                    <div class="user-name">Administrator</div>
                    <div class="user-role">Admin Panel</div>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="number"><?= escape($totalParticipants) ?></div>
                    <div class="label">Total Participants</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="number"><?= escape(number_format($totalCost, 2)) ?></div>
                    <div class="label">Total Cost</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="number"><?= escape(number_format($totalConsultation, 2)) ?></div>
                    <div class="label">Consultant Fees</div>
                </div>
            </div>
        </div>
        
        <!-- Charts Container -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-bar"></i> Training Types
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="trainingChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-pie"></i> Status Distribution
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Participants -->
        <div class="card mt-4">
            <div class="card-header">
                <i class="fas fa-users"></i> Recent Participants
            </div>
            <div class="card-body">
                <?php if (!empty($recentParticipants)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Personal Number</th>
                                    <th>Training Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentParticipants as $participant): ?>
                                    <tr>
                                        <td><?= escape($participant['name']) ?></td>
                                        <td><?= escape($participant['personal_number']) ?></td>
                                        <td><?= escape($participant['training_type']) ?></td>
                                        <td><?= escape($participant['status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No recent participants found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Chart Script -->
    <script>
        const trainingTypes = <?= json_encode($trainingTypes) ?>;
        const labels = trainingTypes.map(item => item.training_type);
        const data = trainingTypes.map(item => item.count);

        const ctx = document.getElementById('trainingChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Number of Participants per Training Type',
                    data: data,
                    backgroundColor: '#81c784',
                    borderColor: '#66bb6a',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        const statusDistribution = <?= json_encode($statusDistribution) ?>;
        const statusLabels = statusDistribution.map(item => item.status);
        const statusData = statusDistribution.map(item => item.count);

        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: statusLabels,
                datasets: [{
                    label: 'Participant Status Distribution',
                    data: statusData,
                    backgroundColor: [
                        '#81c784', '#66bb6a', '#43a047', '#388e3c', '#2e7d32'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
            }
        });
    </script>
</body>
</html>
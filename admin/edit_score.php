<?php
require_once 'silent.php';

// Configure session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
    session_regenerate_id(true);
}

// Redirect if not logged in as admin
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Database connection
try {
    $pdo = new PDO('mysql:host=localhost;dbname=npa_training', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("An error occurred. Please try again later.");
}

// Check if IDs are provided
if (!isset($_GET['participant_id']) || !is_numeric($_GET['participant_id']) || 
    !isset($_GET['training_id']) || !is_numeric($_GET['training_id'])) {
    die("Invalid participant or training ID.");
}

$participant_id = (int)$_GET['participant_id'];
$training_id = (int)$_GET['training_id'];

// Get score details
$query = "SELECT s.*, p.name as participant_name, t.training_description 
          FROM scores s
          JOIN participants p ON s.participant_id = p.id
          JOIN participants t ON s.training_id = t.id
          WHERE s.participant_id = ? AND s.training_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$participant_id, $training_id]);
$score = $stmt->fetch(PDO::FETCH_ASSOC);

// If no score exists, create a new one
if (!$score) {
    $score = [
        'participant_id' => $participant_id,
        'training_id' => $training_id,
        'score' => null,
        'remarks' => null,
        'participant_name' => '',
        'training_description' => ''
    ];
    
    // Get participant and training details
    $detailsQuery = "SELECT p.name as participant_name, t.training_description 
                     FROM participants p, participants t
                     WHERE p.id = ? AND t.id = ?";
    $detailsStmt = $pdo->prepare($detailsQuery);
    $detailsStmt->execute([$participant_id, $training_id]);
    $details = $detailsStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($details) {
        $score['participant_name'] = $details['participant_name'];
        $score['training_description'] = $details['training_description'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_score = $_POST['score'];
    $remarks = $_POST['remarks'];
    
    // Validate score
    if (!is_numeric($new_score)) {
        $error = "Score must be a number";
    } elseif ($new_score < 0 || $new_score > 100) {
        $error = "Score must be between 0 and 100";
    } else {
        try {
            $pdo->beginTransaction();
            
            if (isset($score['id'])) {
                // Update existing score
                $updateQuery = "UPDATE scores SET score = ?, remarks = ? 
                               WHERE participant_id = ? AND training_id = ?";
                $updateStmt = $pdo->prepare($updateQuery);
                $updateStmt->execute([$new_score, $remarks, $participant_id, $training_id]);
            } else {
                // Insert new score
                $insertQuery = "INSERT INTO scores (participant_id, training_id, score, remarks) 
                               VALUES (?, ?, ?, ?)";
                $insertStmt = $pdo->prepare($insertQuery);
                $insertStmt->execute([$participant_id, $training_id, $new_score, $remarks]);
            }
            
            $pdo->commit();
            $success = "Score saved successfully!";
            
            // Refresh score data
            $stmt->execute([$participant_id, $training_id]);
            $score = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Database error: " . $e->getMessage());
            $error = "Failed to save score. Please try again.";
        }
    }
}

function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Score - NPA Training Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Include the same styles as admin_dashboard.php */
        :root {
            --primary-color: #2e7d32;
            --secondary-color: #81c784;
            --light-bg: #e8f5e9;
            --dark-text: #1b5e20;
            --success-bg: #d4edda;
            --success-text: #155724;
            --error-bg: #f8d7da;
            --error-text: #721c24;
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
        

        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input[type="number"], textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        textarea {
            min-height: 100px;
        }
        
        .btn {
            padding: 10px 15px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn:hover {
            background-color: var(--dark-text);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: var(--success-bg);
            color: var(--success-text);
        }
        
        .alert-error {
            background-color: var(--error-bg);
            color: var(--error-text);
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
            <li>
                <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            </li>
            <li>
                <a href="admin_participants.php"><i class="fas fa-users"></i> Participants</a>
            </li>
            <li>
                <a href="add_user.php"><i class="fas fa-user-plus"></i> Add Participant</a>
            </li>
            <li>
                <a href="admin_scores.php"><i class="fas fa-star"></i> Training Scores</a>
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
            <h3><?= isset($score['id']) ? 'Edit' : 'Add' ?> Training Score</h3>
            <div class="user-profile">
                <img src="npa.jpg" alt="NPA Logo">
                <div class="user-info">
                    <div class="user-name">Administrator</div>
                    <div class="user-role">Admin Panel</div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <i class="fas fa-star"></i> Score Details
            </div>
            <div class="card-body">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?= escape($success) ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?= escape($error) ?></div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Participant:</label>
                    <p><?= escape($score['participant_name']) ?></p>
                </div>
                
                <div class="form-group">
                    <label>Training:</label>
                    <p><?= escape($score['training_description']) ?></p>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="score">Score (0-100):</label>
                        <input type="number" id="score" name="score" min="0" max="100" step="0.01" 
                               value="<?= escape($score['score'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="remarks">Remarks:</label>
                        <textarea id="remarks" name="remarks"><?= escape($score['remarks'] ?? '') ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <!----<a href="view_user.php?id=<?= $participant_id ?>" class="btn" style="background-color: #6c757d;">
                        <i class="fas fa-arrow-left"></i> Back to Participant
                    </a>----->
                </form>
            </div>
        </div>
    </div>
</body>
</html>
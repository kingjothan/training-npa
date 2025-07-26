<?php
require_once 'admin/silent.php';
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

// Get participant information
$stmt = $pdo->prepare("SELECT * FROM participants WHERE personal_number = ? ORDER BY start_date DESC LIMIT 1");
$stmt->execute([$_SESSION['personal_number']]);
$participant = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    // Update staff profile
    $stmt = $pdo->prepare("UPDATE staff SET email = ?, phone = ? WHERE id = ?");
    if ($stmt->execute([$email, $phone, $_SESSION['staff_id']])) {
        $success = "Profile updated successfully";
        // Refresh staff data
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
        $stmt->execute([$_SESSION['staff_id']]);
        $staff = $stmt->fetch();
    } else {
        $error = "Failed to update profile";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - NPA Training Portal</title>
    <style>
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
        }
        
        .header h3 {
            color: var(--dark-text);
            font-weight: 600;
            margin-bottom: 0;
        }
        
        .profile-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 30px;
            margin-bottom: 20px;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 5px solid var(--light-bg);
        }
        
        .profile-name {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .profile-title {
            color: var(--dark-text);
            margin-bottom: 20px;
        }
        
        .profile-details {
            margin-bottom: 30px;
        }
        
        .profile-details h4 {
            color: var(--dark-text);
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .detail-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 5px;
        }
        
        .detail-value {
            color: #555;
        }
        
        form h4 {
            color: var(--dark-text);
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-text);
            display: block;
            margin-bottom: 8px;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 12px;
            border: 1px solid #ced4da;
            width: 100%;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.25);
        }
        
        .btn {
            display: inline-block;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: none;
            padding: 12px 25px;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 8px;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--dark-text);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .alert-success {
            background-color: var(--success-bg);
            color: var(--success-text);
            border: 1px solid rgba(21, 87, 36, 0.1);
        }
        
        .alert-danger {
            background-color: var(--error-bg);
            color: var(--error-text);
            border: 1px solid rgba(114, 28, 36, 0.1);
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
        
        .fa-save:before {
            content: "\f0c7";
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
            
            .profile-card {
                padding: 20px;
            }
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
            <li>
                <a href="staff_trainings.php"><i class="fas"></i> My Trainings</a>
            </li>
            <li class="active">
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
            <h3>My Profile</h3>
        </div>
        
        <div class="profile-card">
            <div class="profile-header">
            <img src="npa.jpg"
                     alt="Profile" class="profile-avatar">
                <h3 class="profile-name"><?= htmlspecialchars($participant['name'] ?? 'Staff Member') ?></h3>
                <p class="profile-title">Personal Number: <?= htmlspecialchars($staff['personal_number']) ?></p>
            </div>
            
            <div class="profile-details">
                <h4>Personal Information</h4>
                
                <div class="detail-item">
                    <div class="detail-label">Designation</div>
                    <div class="detail-value"><?= htmlspecialchars($participant['designation'] ?? 'Not specified') ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Location</div>
                    <div class="detail-value"><?= htmlspecialchars($participant['location'] ?? 'Not specified') ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Last Training</div>
                    <div class="detail-value">
                        <?php if ($participant): ?>
                            <?= htmlspecialchars($participant['training_description']) ?> 
                            (<?= htmlspecialchars($participant['start_date']) ?> to <?= htmlspecialchars($participant['completion_date']) ?>)
                        <?php else: ?>
                            No training records found
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <form method="POST">
                <h4>Contact Information</h4>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?= htmlspecialchars($staff['email'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" id="phone" name="phone" 
                           value="<?= htmlspecialchars($staff['phone'] ?? '') ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas"></i> Save Changes
                </button>
            </form>
        </div>
    </div>

    <script>
        // Simple JavaScript for form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            
            form.addEventListener('submit', function(e) {
                const email = document.getElementById('email').value;
                const phone = document.getElementById('phone').value;
                
                if (!email && !phone) {
                    alert('Please provide at least email or phone number');
                    e.preventDefault();
                    return false;
                }
                
                return true;
            });
            
            <?php if (isset($success)): ?>
                setTimeout(function() {
                    document.querySelector('.alert-success').style.display = 'none';
                }, 5000);
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                setTimeout(function() {
                    document.querySelector('.alert-danger').style.display = 'none';
                }, 5000);
            <?php endif; ?>
        });
    </script>
</body>
</html>
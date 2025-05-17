<?php
session_start();
include 'db.php';

// Check if staff is logged in
if (!isset($_SESSION['staff_id'])) {
    header('Location: staff_login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    $errors = [];
    
    if (empty($current_password)) {
        $errors[] = "Current password is required";
    }
    
    if (empty($new_password)) {
        $errors[] = "New password is required";
    } elseif (strlen($new_password) < 8) {
        $errors[] = "New password must be at least 8 characters";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "New passwords do not match";
    }
    
    if (empty($errors)) {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM staff WHERE id = ?");
        $stmt->execute([$_SESSION['staff_id']]);
        $staff = $stmt->fetch();
        
        if ($staff && password_verify($current_password, $staff['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE staff SET password = ? WHERE id = ?");
            
            if ($update->execute([$hashed_password, $_SESSION['staff_id']])) {
                $success = "Password changed successfully";
            } else {
                $errors[] = "Failed to update password";
            }
        } else {
            $errors[] = "Current password is incorrect";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - NPA Training Portal</title>
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
            --warning-bg: #fff3cd;
            --warning-text: #856404;
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
        
        .password-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .alert-danger {
            background-color: var(--error-bg);
            color: var(--error-text);
            border: 1px solid rgba(114, 28, 36, 0.1);
        }
        
        .alert-success {
            background-color: var(--success-bg);
            color: var(--success-text);
            border: 1px solid rgba(21, 87, 36, 0.1);
        }
        
        form {
            margin-top: 20px;
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
        
        .password-strength {
            height: 5px;
            background-color: #e9ecef;
            border-radius: 5px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background-color 0.3s;
        }
        
        .text-muted {
            color: #6c757d;
            font-size: 0.875rem;
            display: block;
            margin-top: 5px;
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
        
        .fa-key:before {
            content: "\f084";
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
            
            .password-card {
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
            <li>
                <a href="staff_profile.php"><i class="fas"></i> My Profile</a>
            </li>
            <li class="active">
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
            <h3>Change Password</h3>
        </div>
        
        <div class="password-card">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="current_password" class="form-label">Current Password</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="password-strength-bar"></div>
                    </div>
                    <small id="password-help" class="text-muted">Minimum 8 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas"></i> Change Password
                </button>
            </form>
        </div>
    </div>

    <script>
        // Password strength meter
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('new_password');
            const strengthBar = document.getElementById('password-strength-bar');
            
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                // Length check
                if (password.length >= 8) strength += 25;
                // Lowercase check
                if (password.match(/[a-z]/)) strength += 25;
                // Uppercase check
                if (password.match(/[A-Z]/)) strength += 25;
                // Number or special char check
                if (password.match(/[0-9]/) || password.match(/[^a-zA-Z0-9]/)) strength += 25;
                
                strengthBar.style.width = strength + '%';
                
                // Color coding
                if (strength < 50) {
                    strengthBar.style.backgroundColor = '#dc3545'; // Red
                } else if (strength < 75) {
                    strengthBar.style.backgroundColor = '#fd7e14'; // Orange
                } else {
                    strengthBar.style.backgroundColor = '#28a745'; // Green
                }
            });
            
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 5000);
            });
            
            // Form validation
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const currentPass = document.getElementById('current_password').value;
                const newPass = document.getElementById('new_password').value;
                const confirmPass = document.getElementById('confirm_password').value;
                
                if (!currentPass || !newPass || !confirmPass) {
                    alert('Please fill in all fields');
                    e.preventDefault();
                    return false;
                }
                
                if (newPass !== confirmPass) {
                    alert('New passwords do not match');
                    e.preventDefault();
                    return false;
                }
                
                if (newPass.length < 8) {
                    alert('Password must be at least 8 characters');
                    e.preventDefault();
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html>
<?php
require_once 'admin/silent.php';
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $personal_number = $_POST['personal_number'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($personal_number) || empty($password)) {
        $error = "Please enter both personal number and password";
    } else {
        // Check staff credentials
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE personal_number = ?");
        $stmt->execute([$personal_number]);
        $staff = $stmt->fetch();
        
        if ($staff && password_verify($password, $staff['password'])) {
            // Login successful
            $_SESSION['staff_id'] = $staff['id'];
            $_SESSION['personal_number'] = $staff['personal_number'];
            
            // Update last login time
            $update = $pdo->prepare("UPDATE staff SET last_login = NOW() WHERE id = ?");
            $update->execute([$staff['id']]);
            
            header('Location: staff_dashboard.php');
            exit;
        } else {
            $error = "Invalid personal number or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - NPA Training Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8f5e9 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
            line-height: 1.6;
        }
        
        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 420px;
            margin: 0 20px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin-bottom: 15px;
        }
        
        .login-header h2 {
            color: #2e7d32;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: #666;
            font-size: 15px;
        }
        
        .alert {
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 14px;
            display: flex;
            align-items: center;
        }
        
        .alert-danger {
            background-color: #fee;
            border-left: 4px solid #f44336;
            color: #f44336;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #444;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #2e7d32;
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.2);
            outline: none;
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background-color: #2e7d32;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            background-color: #1b5e20;
            transform: translateY(-2px);
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .remember-me input {
            margin-right: 8px;
            width: 16px;
            height: 16px;
            accent-color: #2e7d32;
        }
        
        .remember-me label {
            font-size: 14px;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="npa.jpg" alt="NPA Logo">
            <h2>Staff Portal Login</h2>
            <p>Enter your credentials to access your account</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 8px;">
                    <path d="M10 0C4.48 0 0 4.48 0 10C0 15.52 4.48 20 10 20C15.52 20 20 15.52 20 10C20 4.48 15.52 0 10 0ZM11 15H9V13H11V15ZM11 11H9V5H11V11Z" fill="#f44336"/>
                </svg>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="personal_number" class="form-label">Personal Number</label>
                <input type="text" class="form-control" id="personal_number" name="personal_number" required>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Remember me</label>
            </div>
            
            <button type="submit" class="btn-login">Login</button>

            <div class="login-footer">
                    <p>Don't have an account? <a href="staff_register.php">Register here</a></p>
                    <p><a href="forgot_password.php">Forgot password?</a></p>
            </div>
        </form>
    </div>
</body>
</html>
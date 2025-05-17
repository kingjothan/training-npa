<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $personal_number = $_POST['personal_number'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    // Validate inputs
    $errors = [];
    
    if (empty($personal_number)) {
        $errors[] = "Personal number is required";
    } else {
        // Check if personal number exists in participants table
        $stmt = $pdo->prepare("SELECT * FROM participants WHERE personal_number = ?");
        $stmt->execute([$personal_number]);
        $participant = $stmt->fetch();
        
        if (!$participant) {
            $errors[] = "This personal number is not registered in our training system";
        }
        
        // Check if staff already registered
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE personal_number = ?");
        $stmt->execute([$personal_number]);
        if ($stmt->fetch()) {
            $errors[] = "An account already exists with this personal number";
        }
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new staff
        $stmt = $pdo->prepare("INSERT INTO staff (personal_number, password, email, phone) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$personal_number, $hashed_password, $email, $phone])) {
            $_SESSION['registration_success'] = true;
            header('Location: staff_login.php');
            exit;
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Registration - NPA Training Portal</title>
    <style>
        /* Base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #f0f7f0, #d0e7d0);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
            color: #333;
            line-height: 1.5;
        }
        
        .container {
            width: 100%;
            max-width: 100%;
            padding: 0;
        }
        
        .register-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            padding: 25px;
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }
        
        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #2e7d32, #1b5e20);
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .register-header h2 {
            color: #2e7d32;
            font-weight: 600;
            margin: 15px 0 8px;
            font-size: 24px;
        }
        
        .register-header p {
            color: #666;
            font-size: 14px;
        }
        
        .register-header img {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }
        
        .form-label {
            font-weight: 500;
            color: #2e7d32;
            margin-bottom: 6px;
            display: block;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: all 0.2s;
            background-color: #f9f9f9;
        }
        
        .form-control:focus {
            border-color: #2e7d32;
            box-shadow: 0 0 0 2px rgba(46, 125, 50, 0.2);
            background-color: white;
            outline: none;
        }
        
        .mb-3 {
            margin-bottom: 16px;
        }
        
        .text-muted {
            color: #777 !important;
            font-size: 13px;
            display: block;
            margin-top: 4px;
        }
        
        .btn-register {
            background: linear-gradient(90deg, #2e7d32, #1b5e20);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px;
            font-weight: 500;
            width: 100%;
            transition: all 0.2s;
            font-size: 15px;
            cursor: pointer;
            margin-top: 8px;
        }
        
        .btn-register:hover {
            background: linear-gradient(90deg, #1b5e20, #2e7d32);
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-register:active {
            transform: translateY(0);
        }
        
        .register-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
        
        .register-footer a {
            color: #2e7d32;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .register-footer a:hover {
            color: #1b5e20;
            text-decoration: underline;
        }
        
        .password-strength {
            height: 4px;
            background-color: #eee;
            border-radius: 2px;
            margin-top: 6px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        
        .alert-danger {
            background-color: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
            font-size: 14px;
        }
        
        .alert-danger div {
            margin: 4px 0;
        }
        
        /* Responsive adjustments */
        @media (max-width: 576px) {
            body {
                padding: 15px;
                display: block;
            }
            
            .register-container {
                padding: 20px;
                border-radius: 8px;
            }
            
            .register-header h2 {
                font-size: 22px;
            }
            
            .register-header img {
                width: 60px;
                height: 60px;
            }
            
            .form-control {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="register-header">
            <img src="npa.jpg" alt="NPA Logo">
                <h2>Staff Portal Registration</h2>
                <p>Register to access your training records</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label for="personal_number" class="form-label">Personal Number</label>
                    <input type="text" class="form-control" id="personal_number" name="personal_number" required>
                    <small class="text-muted">Enter the same personal number used in training records</small>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email (Optional)</label>
                    <input type="email" class="form-control" id="email" name="email">
                </div>
                
                <div class="mb-3">
                    <label for="phone" class="form-label">Phone Number (Optional)</label>
                    <input type="tel" class="form-control" id="phone" name="phone">
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="password-strength-bar"></div>
                    </div>
                    <small id="password-help" class="text-muted">Minimum 8 characters</small>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-register mb-3">
                    Register Account
                </button>
                
                <div class="register-footer">
                    <p>Already have an account? <a href="staff_login.php">Login here</a></p>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('password-strength-bar');
            let strength = 0;
            
            if (password.length >= 8) strength += 25;
            if (password.match(/[a-z]/)) strength += 25;
            if (password.match(/[A-Z]/)) strength += 25;
            if (password.match(/[0-9]/) || password.match(/[^a-zA-Z0-9]/)) strength += 25;
            
            strengthBar.style.width = strength + '%';
            
            if (strength < 50) {
                strengthBar.style.backgroundColor = '#e74c3c'; // Weak (red)
            } else if (strength < 75) {
                strengthBar.style.backgroundColor = '#f39c12'; // Medium (orange)
            } else {
                strengthBar.style.backgroundColor = '#2ecc71'; // Strong (green)
            }
        });
    </script>
</body>
</html>
        <?php
// Start session for authentication and secure management
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database connection
include 'db.php';

// Function to sanitize input
function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    // Collect and sanitize form data
    $name = sanitizeInput($_POST['name'] ?? '');
    $personal_number = sanitizeInput($_POST['personal_number'] ?? '');
    $designation = sanitizeInput($_POST['designation'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $training_description = sanitizeInput($_POST['training_description'] ?? '');
    $start_date = sanitizeInput($_POST['start_date'] ?? '');
    $completion_date = sanitizeInput($_POST['completion_date'] ?? '');
    $number_of_days = sanitizeInput($_POST['number_of_days'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? '');
    $training_type = sanitizeInput($_POST['training_type'] ?? '');
    $total_cost_of_participation = sanitizeInput($_POST['total_cost_of_participation'] ?? '');
    $remark = sanitizeInput($_POST['remark'] ?? '');
    $oracle_number = sanitizeInput($_POST['oracle_number'] ?? '');
    $consultant_name = sanitizeInput($_POST['consultant_name'] ?? '');
    $consultation_amount = sanitizeInput($_POST['consultation_amount'] ?? '');
    $venue = sanitizeInput($_POST['venue'] ?? '');

    // Insert data into the database
    $sql = "INSERT INTO participants (
                name, personal_number, designation, location, training_description, 
                start_date, completion_date, number_of_days, status, training_type, 
                total_cost_of_participation, remark, oracle_number, consultant_name, consultation_amount, venue
            ) VALUES (
                :name, :personal_number, :designation, :location, :training_description, 
                :start_date, :completion_date, :number_of_days, :status, :training_type, 
                :total_cost_of_participation, :remark, :oracle_number, :consultant_name, :consultation_amount, :venue
            )";

    $stmt = $pdo->prepare($sql);
    
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':personal_number', $personal_number);
    $stmt->bindParam(':designation', $designation);
    $stmt->bindParam(':location', $location);
    $stmt->bindParam(':training_description', $training_description);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':completion_date', $completion_date);
    $stmt->bindParam(':number_of_days', $number_of_days);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':training_type', $training_type);
    $stmt->bindParam(':total_cost_of_participation', $total_cost_of_participation);
    $stmt->bindParam(':remark', $remark);
    $stmt->bindParam(':oracle_number', $oracle_number);
    $stmt->bindParam(':consultant_name', $consultant_name);
    $stmt->bindParam(':consultation_amount', $consultation_amount);
    $stmt->bindParam(':venue', $venue);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Participant added successfully!";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = "Failed to add participant. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Participant - NPA Training Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2e7d32;
            --secondary-color: #81c784;
            --light-bg: #e8f5e9;
            --dark-text: #1b5e20;
            --error-color: #d32f2f;
            --success-color: #388e3c;
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
        
        /* Rest of your existing styles... */
        :root {
            --primary-color: #28a745;
            --secondary-color: #6c757d;
            --light-green-bg: #f0fff4;
            --form-bg: #ffffff;
            --card-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-green-bg);
            background-image: linear-gradient(to bottom right, #f0fff4, #e6ffed);
            color: #333;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        
        .app-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background-color: var(--form-bg);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: var(--primary-color);
            font-size: 1.8rem;
            margin: 0;
            font-weight: 600;
        }
        
        .form-container {
            background-color: var(--form-bg);
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            padding: 2rem;
            margin-bottom: 20px;
        }
        
        .form-section {
            margin-bottom: 2rem;
        }
        
        .form-section h3 {
            color: var(--primary-color);
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
            color: #495057;
        }
        
        .form-control, .form-select {
            border-radius: 6px;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            transition: all 0.2s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
        }
        
        .action-bar {
            background-color: var(--form-bg);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn {
            border-radius: 6px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
        }
        
        .btn-primary:hover {
            background-color: #218838;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            border: none;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-1px);
        }
        
        .btn-outline-secondary {
            border: 1px solid var(--secondary-color);
            color: var(--secondary-color);
        }
        
        .btn-outline-secondary:hover {
            background-color: var(--secondary-color);
            color: white;
        }
        
        #alert-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            max-width: 400px;
        }
        
        .alert {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-radius: 8px;
        }
        
        .spinner-border {
            display: none;
            width: 1.5rem;
            height: 1.5rem;
            border-width: 0.2em;
        }
        
        .shortcut-hint {
            font-size: 0.85rem;
            color: var(--secondary-color);
            margin-top: 0.5rem;
        }
        
        /* Form grid layout */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        /* Utility buttons */
        .utility-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 1rem;
        }
        
        .btn-paste {
            background-color: #e2f0fd;
            color: #0d6efd;
            border: 1px solid #b6d4fe;
        }
        
        .btn-paste:hover {
            background-color: #cfe2ff;
            color: #0a58ca;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .action-bar {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .utility-buttons {
                flex-direction: column;
            }
        }

        /* Adjust the app-container to work with sidebar */
        .app-container {
            max-width: calc(100% - 250px);
            margin-left: 250px;
            padding: 20px;
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
            
            .app-container {
                max-width: 100%;
                margin-left: 0;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .action-bar {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .utility-buttons {
                flex-direction: column;
            }
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
            <li class="active">
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
    <div class="app-container">
        <div class="header">
            <h1><i class="fas fa-user-plus me-2"></i>Add New Participant</h1>
            <div>
                <button type="button" id="clearForm" class="btn btn-outline-danger">
                    <i class="fas fa-trash-alt me-1"></i> Clear Form
                </button>
            </div>
        </div>
        
        <div id="alert-container">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
        </div>
        
        <div class="form-container">
            <div class="utility-buttons">
                <button type="button" id="pasteFromClipboard" class="btn btn-paste">
                    <i class="fas fa-paste me-1"></i> Paste from Clipboard (Ctrl+Shift+V)
                </button>
            </div>
            
            <form id="participantForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                
                <!-- Personal Information Section -->
                <div class="form-section">
                    <h3><i class="fas fa-user me-2"></i>Personal Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" name="name" id="name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="personal_number" class="form-label">Personal Number</label>
                            <input type="text" name="personal_number" id="personal_number" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="designation" class="form-label">Designation</label>
                            <input type="text" name="designation" id="designation" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" name="location" id="location" class="form-control">
                        </div>
                    </div>
                </div>
                
                <!-- Training Details Section -->
                <div class="form-section">
                    <h3><i class="fas fa-graduation-cap me-2"></i>Training Details</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="training_description" class="form-label">Description</label>
                            <input type="text" name="training_description" id="training_description" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="training_type" class="form-label">Type</label>
                            <select name="training_type" id="training_type" class="form-select">
                                <option value="Short_COURSES">Short-COURSES</option>
                                <option value="Conference">Conference</option>
                                <option value="Mandatories">Mandatories</option>
                                <option value="In_House">In-House</option>
                                <option value="In_Plant">In-Plant</option>
                                <option value="Overseas_Short_COURSES">Overseas Short-COURSES</option>
                                <option value="Carrier_Growth">Carrier Growth</option>
                                <option value="Sensitization">Sensitization</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="other_training_type" style="display:none;">
                            <label for="other_training_type_input" class="form-label">Specify Type</label>
                            <input type="text" name="other_training_type" id="other_training_type_input" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="Completed">Completed</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Not Started">Not Started</option>
                                <option value="Rescheduled">Rescheduled</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Dates Section -->
                <div class="form-section">
                    <h3><i class="fas fa-calendar-alt me-2"></i>Dates</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="completion_date" class="form-label">Completion Date</label>
                            <input type="date" name="completion_date" id="completion_date" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="number_of_days" class="form-label">Number of Days</label>
                            <input type="number" name="number_of_days" id="number_of_days" class="form-control">
                        </div>
                    </div>
                </div>
                
                <!-- Financial Information Section -->
                <div class="form-section">
                    <h3><i class="fas fa-money-bill-wave me-2"></i>Financial Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="total_cost_of_participation" class="form-label">Total Cost</label>
                            <input type="number" name="total_cost_of_participation" id="total_cost_of_participation" class="form-control" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="oracle_number" class="form-label">Oracle Number</label>
                            <input type="text" name="oracle_number" id="oracle_number" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="consultant_name" class="form-label">Consultant Name</label>
                            <input type="text" name="consultant_name" id="consultant_name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="consultation_amount" class="form-label">Consultant Fee</label>
                            <input type="number" name="consultation_amount" id="consultation_amount" class="form-control" step="0.01" required>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Information Section -->
                <div class="form-section">
                    <h3><i class="fas fa-info-circle me-2"></i>Additional Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="venue" class="form-label">Venue</label>
                            <input type="text" name="venue" id="venue" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="remark" class="form-label">Remarks</label>
                            <textarea name="remark" id="remark" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="action-bar">
            <div>
                <button type="submit" form="participantForm" class="btn btn-primary me-3" id="submitBtn">
                    <i class="fas fa-save me-1"></i>
                    <span id="submitText">Save Participant</span>
                    <span id="submitSpinner" class="spinner-border spinner-border-sm"></span>
                </button>
                <a href="admin_participants.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Participants
                </a>
            </div>
            <div class="shortcut-hint">
                <strong>Keyboard Shortcuts:</strong> Tab to navigate between fields | Ctrl+Shift+V to paste data
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Toastr
            toastr.options = {
                positionClass: 'toast-top-right',
                preventDuplicates: true,
                progressBar: true,
                timeOut: 5000
            };
            
            // Show training type other field when "Other" is selected
            document.getElementById('training_type').addEventListener('change', function() {
                const otherField = document.getElementById('other_training_type');
                otherField.style.display = this.value === 'Other' ? 'block' : 'none';
            });
            
            // Clear form button
            document.getElementById('clearForm').addEventListener('click', function() {
                if (confirm('Are you sure you want to clear all fields?')) {
                    document.getElementById('participantForm').reset();
                    document.getElementById('other_training_type').style.display = 'none';
                    toastr.info('Form cleared');
                }
            });
            
            // Handle paste from clipboard button
            document.getElementById('pasteFromClipboard').addEventListener('click', async function() {
                try {
                    // Read clipboard contents
                    const clipboardItems = await navigator.clipboard.read();
                    
                    for (const clipboardItem of clipboardItems) {
                        for (const type of clipboardItem.types) {
                            if (type === 'text/plain') {
                                const blob = await clipboardItem.getType(type);
                                const text = await blob.text();
                                parseClipboardData(text);
                                return;
                            }
                        }
                    }
                    
                    toastr.info('No text data found in clipboard');
                } catch (err) {
                    console.error('Failed to read clipboard:', err);
                    toastr.error('Failed to access clipboard. Please ensure you have granted clipboard permissions.');
                    
                    // Fallback for browsers that don't support Clipboard API
                    const pasteTarget = document.createElement('textarea');
                    pasteTarget.style.position = 'absolute';
                    pasteTarget.style.left = '-9999px';
                    document.body.appendChild(pasteTarget);
                    pasteTarget.focus();
                    
                    // Try to execute paste command
                    const success = document.execCommand('paste');
                    if (success) {
                        parseClipboardData(pasteTarget.value);
                        document.body.removeChild(pasteTarget);
                    } else {
                        toastr.error('Please use Ctrl+V to paste into individual fields');
                    }
                }
            });
            
            // Function to parse clipboard data and fill form fields
            function parseClipboardData(data) {
                // Normalize line breaks and split into rows
                const rows = data.replace(/\r\n/g, '\n').replace(/\r/g, '\n').split('\n');
                
                // If we only have one line, try to split by tabs for Excel data
                if (rows.length === 1 && rows[0].includes('\t')) {
                    const values = rows[0].split('\t');
                    fillFieldsFromArray(values);
                    return;
                }
                
                // If we have multiple lines, try to find the one with the most fields
                let bestRow = null;
                let maxFields = 0;
                
                for (const row of rows) {
                    const fields = row.split('\t');
                    if (fields.length > maxFields) {
                        maxFields = fields.length;
                        bestRow = fields;
                    }
                }
                
                if (bestRow && bestRow.length > 1) {
                    fillFieldsFromArray(bestRow);
                } else if (rows[0]) {
                    // If no tabs, try to split by multiple spaces
                    const fields = rows[0].split(/\s{2,}/);
                    if (fields.length > 1) {
                        fillFieldsFromArray(fields);
                    } else {
                        toastr.info('Could not detect structured data in clipboard. Pasting into active field only.');
                    }
                }
            }
            
            // Function to map clipboard data to form fields
            function fillFieldsFromArray(values) {
                // Trim all values
                values = values.map(v => v.trim());
                
                // Define field mapping - adjust based on your form structure
                const fieldMapping = [
                    'name', 'personal_number', 'designation', 'location', 
                    'training_description', 'start_date', 'completion_date',
                    'number_of_days', 'status', 'training_type',
                    'total_cost_of_participation', 'remark', 'oracle_number',
                    'consultant_name', 'consultation_amount', 'venue'
                ];
                
                // Fill fields with corresponding values
                for (let i = 0; i < Math.min(values.length, fieldMapping.length); i++) {
                    const fieldName = fieldMapping[i];
                    const field = document.querySelector(`[name="${fieldName}"]`);
                    
                    if (field) {
                        field.value = values[i];
                        
                        // Trigger change event for select elements
                        if (field.tagName === 'SELECT') {
                            field.dispatchEvent(new Event('change'));
                        }
                    }
                }
                
                toastr.success(`Pasted ${Math.min(values.length, fieldMapping.length)} fields`);
            }
            
            // Add keyboard shortcut (Ctrl+Shift+V) for paste functionality
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.shiftKey && e.key === 'V') {
                    e.preventDefault();
                    document.getElementById('pasteFromClipboard').click();
                }
            });
            
            // Form submission handler
            document.getElementById('participantForm').addEventListener('submit', function(e) {
                let isValid = true;
                const requiredFields = [
                    'name', 'personal_number', 'start_date', 'completion_date',
                    'total_cost_of_participation', 'oracle_number',
                    'consultant_name', 'consultation_amount', 'venue'
                ];
                
                // Validate required fields
                requiredFields.forEach(fieldName => {
                    const field = document.querySelector(`[name="${fieldName}"]`);
                    if (field && !field.value.trim()) {
                        field.style.borderColor = '#dc3545';
                        isValid = false;
                    }
                });
                
                // Validate dates
                const startDate = new Date(document.getElementById('start_date').value);
                const completionDate = new Date(document.getElementById('completion_date').value);
                
                if (startDate && completionDate && completionDate < startDate) {
                    toastr.error('Completion date must be after the start date.');
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                    toastr.error('Please fill out all required fields correctly.');
                } else {
                    // Show loading state
                    document.getElementById('submitText').textContent = 'Saving...';
                    document.getElementById('submitSpinner').style.display = 'inline-block';
                    document.getElementById('submitBtn').disabled = true;
                }
            });
            
            // Remove error styling when typing in required fields
            document.querySelectorAll('input, select, textarea').forEach(control => {
                control.addEventListener('input', function() {
                    this.style.borderColor = '#ced4da';
                });
            });
        });
    </script>
</body>
</html>
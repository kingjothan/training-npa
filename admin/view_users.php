<?php
require_once 'silent.php';
// Start the session
session_start();

// Initialize the database connection
$conn = new mysqli('localhost', 'root', '', 'npa_training');

// Check for database connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch search and filter inputs
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_location = isset($_GET['location']) ? trim($_GET['location']) : '';
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';
$year = isset($_GET['year']) ? (int)$_GET['year'] : 0;
$from_month = isset($_GET['from_month']) ? (int)$_GET['from_month'] : 0;
$to_month = isset($_GET['to_month']) ? (int)$_GET['to_month'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10000000000; // Number of records per page
$offset = ($page - 1) * $per_page;

// Validate and sanitize inputs
$search = $conn->real_escape_string($search);
$filter_location = $conn->real_escape_string($filter_location);
$filter_status = $conn->real_escape_string($filter_status);

// Construct the base query with filters
$query = "SELECT * FROM participants WHERE 1";

if (!empty($search)) {
    $query .= " AND (name LIKE '%$search%' OR personal_number LIKE '%$search%' OR training_description LIKE '%$search%')";
}
if (!empty($filter_location)) {
    $query .= " AND location = '$filter_location'";
}
if (!empty($filter_status)) {
    $query .= " AND status = '$filter_status'";
}
if ($year > 0) {
    $query .= " AND YEAR(start_date) = $year";
}
if ($from_month > 0 && $to_month > 0) {
    $query .= " AND MONTH(start_date) BETWEEN $from_month AND $to_month";
} elseif ($from_month > 0) {
    $query .= " AND MONTH(start_date) >= $from_month";
} elseif ($to_month > 0) {
    $query .= " AND MONTH(start_date) <= $to_month";
}

// Add pagination to the query
$query .= " LIMIT $offset, $per_page";

// Execute the query
$result = $conn->query($query);

// Handle errors in the query
if (!$result) {
    die("Error executing query: " . $conn->error);
}

// Get total record count for pagination
$count_query = "SELECT COUNT(*) AS total FROM participants WHERE 1";
if (!empty($search)) {
    $count_query .= " AND (name LIKE '%$search%' OR personal_number LIKE '%$search%' OR training_description LIKE '%$search%')";
}
if (!empty($filter_location)) {
    $count_query .= " AND location = '$filter_location'";
}
if (!empty($filter_status)) {
    $count_query .= " AND status = '$filter_status'";
}
if ($year > 0) {
    $count_query .= " AND YEAR(start_date) = $year";
}
if ($from_month > 0 && $to_month > 0) {
    $count_query .= " AND MONTH(start_date) BETWEEN $from_month AND $to_month";
} elseif ($from_month > 0) {
    $count_query .= " AND MONTH(start_date) >= $from_month";
} elseif ($to_month > 0) {
    $count_query .= " AND MONTH(start_date) <= $to_month";
}

$count_result = $conn->query($count_query);
if (!$count_result) {
    die("Error executing count query: " . $conn->error);
}

$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);

// Initialize the total cost variables
$total_cost_of_participation_all = 0;
$total_consultation_amount_all = 0; // New variable for total consultation amount
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participants List - NPA Training Portal</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Animate.css for Animations -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <!-- Custom Styles -->
    <style>
        /* (Previous CSS styles remain the same) */
        /* Global Styles */
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            color: #2e7d32;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        header {
            text-align: center;
            padding: 20px 0;
            background: linear-gradient(135deg, #2e7d32, #1b5e20);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        header p {
            font-size: 0.7rem;
            font-weight: 300;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 10px;
        }

        /* Glassmorphism Card */
        .filter-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 10px;
            border: 1px solid rgba(46, 125, 50, 0.2);
        }

        .filter-card input,
        .filter-card select {
            margin-bottom: 8px;
            border-radius: 6px;
            border: 1px solid rgba(46, 125, 50, 0.3);
            padding: 6px;
            background: rgba(255, 255, 255, 0.9);
            color: #2e7d32;
            font-size: 0.8rem;
        }

        .filter-card input:focus,
        .filter-card select:focus {
            border-color: #2e7d32;
            box-shadow: 0 0 8px rgba(46, 125, 50, 0.5);
        }

        /* Responsive Table */
        .table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(46, 125, 50, 0.2);
        }

        .table th,
        .table td {
            padding: 6px;
            text-align: center;
            border-bottom: 1px solid rgba(46, 125, 50, 0.1);
            font-size: 0.8rem;
        }

        .table th {
            background-color: #2e7d32;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .table tr:hover {
            background-color: rgba(200, 230, 201, 0.3);
        }

        /* Buttons */
        .btn {
            font-size: 0.8rem;
            border-radius: 6px;
            padding: 6px 10px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: #2e7d32;
            border-color: #2e7d32;
        }

        .btn-primary:hover {
            background-color: #1b5e20;
            border-color: #1b5e20;
        }

        #print-btn {
            background-color: #388e3c;
            border-color: #388e3c;
            color: white;
            font-size: 0.8rem;
            margin-bottom: 8px;
        }

        #print-btn:hover {
            background-color: #2e7d32;
            border-color: #2e7d32;
        }

        .btn-info {
            background-color: #81c784;
            border-color: #81c784;
        }

        .btn-info:hover {
            background-color: #66bb6a;
            border-color: #66bb6a;
        }

        /* Pagination */
        .pagination {
            justify-content: center;
            margin-top: 10px;
        }

        .pagination .page-item.active .page-link {
            background-color: #2e7d32;
            border-color: #2e7d32;
        }

        .pagination .page-link {
            color: #2e7d32;
            font-size: 0.8rem;
            padding: 6px 10px;
        }

        /* Total Cost */
        .total-cost {
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 10px;
            text-align: right;
            color: #1b5e20;
        }

        /* Back Button */
        .back-button {
            display: inline-block;
            margin-top: 10px;
            padding: 6px 10px;
            background-color: #2e7d32;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background-color 0.3s ease;
            font-size: 0.8rem;
        }

        .back-button:hover {
            background-color: #1b5e20;
            color: white;
        }

        /* Floating Action Button */
        .fab {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background-color: #2e7d32;
            color: white;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            text-align: center;
            line-height: 35px;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .fab:hover {
            background-color: #1b5e20;
            transform: scale(1.1);
        }

        /* Status Badges */
        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .status-completed {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #2e7d32;
        }

        .status-in-progress {
            background-color: #fff8e1;
            color: #ff8f00;
            border: 1px solid #ff8f00;
        }

        .status-not-started {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #c62828;
        }

        .status-rescheduled {
            background-color: #e3f2fd;
            color: #1565c0;
            border: 1px solid #1565c0;
        }
    </style>
</head>
<body>
    <header class="animate__animated animate__fadeInDown">
        <h1>Participants List</h1>
        <p>Manage and view all training participants</p>
    </header>

    <div class="container animate__animated animate__fadeInUp">
        <!-- Search and Filter Form -->
        <div class="filter-card">
            <form class="row g-2" id="filter-form">
                <div class="col-12 col-md-6 col-lg-2">
                    <input 
                        type="text" 
                        name="search" 
                        class="form-control" 
                        placeholder="Search by Name, Number, or Training Description" 
                        value="<?= htmlspecialchars($search) ?>"
                    >
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <select name="location" class="form-select">
                        <option value="">Filter by Location</option>
                        <?php
                        $locations = $conn->query("SELECT DISTINCT location FROM participants");
                        while ($loc = $locations->fetch_assoc()):
                        ?>
                            <option value="<?= htmlspecialchars($loc['location']) ?>" <?= $filter_location === $loc['location'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($loc['location']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <select name="status" class="form-select">
                        <option value="">Filter by Status</option>
                        <option value="Completed" <?= $filter_status === 'Completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="In Progress" <?= $filter_status === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="Not Started" <?= $filter_status === 'Not Started' ? 'selected' : '' ?>>Not Started</option>
                        <option value="Rescheduled" <?= $filter_status === 'Rescheduled' ? 'selected' : '' ?>>Rescheduled</option>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <input type="number" name="year" class="form-control" placeholder="Year" min="1960" max="2100" value="<?= htmlspecialchars($year) ?>">
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <select name="from_month" class="form-select">
                        <option value="">From Month</option>
                        <option value="1" <?= $from_month == 1 ? 'selected' : '' ?>>January</option>
                        <option value="2" <?= $from_month == 2 ? 'selected' : '' ?>>February</option>
                        <option value="3" <?= $from_month == 3 ? 'selected' : '' ?>>March</option>
                        <option value="4" <?= $from_month == 4 ? 'selected' : '' ?>>April</option>
                        <option value="5" <?= $from_month == 5 ? 'selected' : '' ?>>May</option>
                        <option value="6" <?= $from_month == 6 ? 'selected' : '' ?>>June</option>
                        <option value="7" <?= $from_month == 7 ? 'selected' : '' ?>>July</option>
                        <option value="8" <?= $from_month == 8 ? 'selected' : '' ?>>August</option>
                        <option value="9" <?= $from_month == 9 ? 'selected' : '' ?>>September</option>
                        <option value="10" <?= $from_month == 10 ? 'selected' : '' ?>>October</option>
                        <option value="11" <?= $from_month == 11 ? 'selected' : '' ?>>November</option>
                        <option value="12" <?= $from_month == 12 ? 'selected' : '' ?>>December</option>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <select name="to_month" class="form-select">
                        <option value="">To Month</option>
                        <option value="1" <?= $to_month == 1 ? 'selected' : '' ?>>January</option>
                        <option value="2" <?= $to_month == 2 ? 'selected' : '' ?>>February</option>
                        <option value="3" <?= $to_month == 3 ? 'selected' : '' ?>>March</option>
                        <option value="4" <?= $to_month == 4 ? 'selected' : '' ?>>April</option>
                        <option value="5" <?= $to_month == 5 ? 'selected' : '' ?>>May</option>
                        <option value="6" <?= $to_month == 6 ? 'selected' : '' ?>>June</option>
                        <option value="7" <?= $to_month == 7 ? 'selected' : '' ?>>July</option>
                        <option value="8" <?= $to_month == 8 ? 'selected' : '' ?>>August</option>
                        <option value="9" <?= $to_month == 9 ? 'selected' : '' ?>>September</option>
                        <option value="10" <?= $to_month == 10 ? 'selected' : '' ?>>October</option>
                        <option value="11" <?= $to_month == 11 ? 'selected' : '' ?>>November</option>
                        <option value="12" <?= $to_month == 12 ? 'selected' : '' ?>>December</option>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>

        <!-- Results Table -->
        <button class="btn" id="print-btn">Print</button>
        <table class="table" id="printable-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>P/N</th>
                    <th>O/N</th>
                    <th>Participant Location</th>
                    <th>Course Location</th>
                    <th>Status</th>
                    <th>Training Description</th>
                    <th>Total Cost</th>
                    <th>Consultant fee</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        $total_cost = $row['total_cost_of_participation'] ?? $row['number_of_days'] * 50;
                        $total_cost_of_participation_all += $total_cost;
                        $total_consultation_amount_all += $row['consultation_amount'];
                        ?>
                        <tr>
                            <td class="participant-number"></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['personal_number']) ?></td>
                            <td><?= htmlspecialchars($row['oracle_number']) ?></td>
                            <td><?= htmlspecialchars($row['location']) ?></td>
                            <td><?= htmlspecialchars($row['venue']) ?></td>
                            <td>
                                <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $row['status'])) ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['training_description']) ?></td>
                            <td><?= number_format($total_cost, 2) ?></td>
                            <td><?= number_format($row['consultation_amount'], 2) ?></td>
                            <td><a href="view_user.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm">View</a></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" class="text-center">No records found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Display Total Cost and Total Consultation Amount -->
        <div class="total-cost" id="printable-total-cost">
            <p>Total Cost of Participation for All Participants: <strong><?= number_format($total_cost_of_participation_all, 2) ?></strong></p>
            <p>Total Consultant fee: <strong><?= number_format($total_consultation_amount_all, 2) ?></strong></p>
        </div>

        <!-- Pagination -->
        <nav>
            <ul class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= htmlspecialchars($search) ?>&location=<?= htmlspecialchars($filter_location) ?>&status=<?= htmlspecialchars($filter_status) ?>&year=<?= htmlspecialchars($year) ?>&from_month=<?= htmlspecialchars($from_month) ?>&to_month=<?= htmlspecialchars($to_month) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>

    <!-- Floating Action Button -->
    <a href="add_participant.php" class="fab animate__animated animate__fadeInUp">
        <i class="fas fa-plus"></i>
    </a>

    <!-- Back Button -->
    <section class="container">
        <a href="admin_dashboard.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </section>

    <!-- JavaScript for Printing and Numbering -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Number the participants
            $('.participant-number').each(function(index) {
                $(this).text(index + 1);
            });

            $('#print-btn').click(function() {
                var printWindow = window.open('', '', 'width=800,height=600');
                printWindow.document.write('<html><head><title>Participants List</title>');
                printWindow.document.write('<style> body { font-family: Arial, sans-serif; } .table { width: 100%; border-collapse: collapse; } .table th, .table td { padding: 8px 12px; text-align: center; border: 1px solid #ddd; } .total-cost { font-size: 1.2rem; font-weight: bold; text-align: right; margin-top: 20px; color: #1b5e20; } .status-badge { padding: 3px 8px; border-radius: 12px; font-size: 0.7rem; } </style></head><body>');
                printWindow.document.write('<h2 style="color: #2e7d32;">Participants List</h2>');
                
                // Clone the table for printing
                var printTable = $('#printable-table').clone();
                
                // Number the participants in the print version
                printTable.find('.participant-number').each(function(index) {
                    $(this).text(index + 1);
                });
                
                printWindow.document.write(printTable[0].outerHTML);
                printWindow.document.write(document.getElementById('printable-total-cost').outerHTML);
                printWindow.document.close();
                printWindow.print();
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>
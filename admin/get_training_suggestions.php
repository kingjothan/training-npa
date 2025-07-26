<?php
session_start();
require_once 'db.php';

// Set JSON header
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Validate session and permissions
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Validate and sanitize input
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
if (empty($searchTerm) || strlen($searchTerm) < 2) {
    echo json_encode([]);
    exit;
}

// Prepare and execute query with parameterized statement
try {
    $stmt = $pdo->prepare("SELECT 
        training_description, 
        training_type, 
        consultant_name, 
        venue,
        start_date,
        completion_date,
        COUNT(*) as occurrence_count
        FROM participants 
        WHERE training_description LIKE :search 
        GROUP BY training_description, training_type, consultant_name, venue
        ORDER BY occurrence_count DESC, training_description
        LIMIT 10");
    
    $searchParam = '%' . $searchTerm . '%';
    $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    $stmt->execute();

    // Fetch results and prepare response
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate average duration for each training
    foreach ($results as &$result) {
        if ($result['start_date'] && $result['completion_date']) {
            $start = new DateTime($result['start_date']);
            $end = new DateTime($result['completion_date']);
            $result['average_duration'] = $start->diff($end)->days + 1; // Inclusive count
        }
    }

    echo json_encode($results);
    
} catch (PDOException $e) {
    error_log("Database error in get_training_suggestions.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
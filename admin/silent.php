<?php
// Silent Kill Switch for XAMPP
// Include this at the very top of your PHP files (after db.php)

// Ensure no output has been sent
if (ob_get_level()) ob_end_clean();

// Connect to database (use your existing connection)
require_once 'db.php';

try {
    // Check kill switch status
    $stmt = $pdo->query("SELECT is_active FROM switch LIMIT 1");
    $status = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($status && $status['is_active']) {
        // Send minimal headers and exit cleanly
        header("HTTP/1.1 200 OK");
        header("Content-Type: text/html");
        header("Content-Length: 0");
        exit;
    }
} catch (PDOException $e) {
    // If database fails, allow site to continue running
    error_log("Silent Kill Switch DB Error: " . $e->getMessage());
}
?>
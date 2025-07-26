<?php
require_once 'admin/silent.php';
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: staff_login.php');
exit;
?>
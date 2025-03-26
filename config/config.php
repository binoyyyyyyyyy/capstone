<?php
// Prevent duplicate session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration
$host = 'localhost';
$dbname = 'schedulingmanagementdb';
$username = 'root'; // Change if using a different DB user
$password = ''; // Change if a password is set

$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Security Headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
?>

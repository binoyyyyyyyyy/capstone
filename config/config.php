<?php
// Prevent duplicate session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration
$host = 'localhost';
$dbname = 'schedulingmanagement';
$username = 'root'; // Change if using a different DB user
$password = ''; // Change if a password is set

$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Base URL Configuration
$base_url = '/capstone-main/';

// Security Headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");






// // Prevent duplicate session start
// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

// // Database Configuration
// $host = 'sql304.infinityfree.com';
// $dbname = 'if0_39817054_schedulingmanagementdb';
// $username = 'if0_39817054'; // Change if using a different DB user
// $password = 'ALan1cbgF4Xoijb'; // Change if a password is set

// $conn = new mysqli($host, $username, $password, $dbname);

// // Check connection
// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }

// // Security Headers
// header("X-Frame-Options: DENY");
// header("X-XSS-Protection: 1; mode=block");
// header("X-Content-Type-Options: nosniff");


?>





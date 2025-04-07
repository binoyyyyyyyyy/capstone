<?php
session_start();
require_once '../config/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if request ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid request ID!";
    header("Location: ../admin/manage_requests.php");
    exit();
}

$requestID = intval($_GET['id']);

// Delete request using prepared statement
$stmt = $conn->prepare("DELETE FROM RequestTable WHERE requestID = ?");
$stmt->bind_param("i", $requestID);

if ($stmt->execute()) {
    $_SESSION['message'] = "Request deleted successfully!";
} else {
    $_SESSION['error'] = "Failed to delete request.";
}

$stmt->close();
$conn->close();

// Debug before redirection
header("Location: /capstone/admin/manage_request.php");
exit();

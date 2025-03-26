<?php
session_start();
require_once '../config/config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if student ID is provided
if (!isset($_GET['id'])) {
    header("Location: manage_students.php");
    exit();
}

$studentID = $_GET['id'];

// Delete student record
$stmt = $conn->prepare("DELETE FROM studentInformation WHERE studentID = ?");
$stmt->bind_param("i", $studentID);

if ($stmt->execute()) {
    $_SESSION['message'] = "Student deleted successfully!";
} else {
    $_SESSION['error'] = "Failed to delete student.";
}

$stmt->close();
header("Location: manage_students.php");
exit();
?>

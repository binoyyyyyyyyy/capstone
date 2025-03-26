<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid student ID.";
    header("Location: manage_students.php");
    exit();
}

$studentID = intval($_GET['id']);

// Delete related requests first
$deleteRequests = $conn->prepare("DELETE FROM RequestTable WHERE studentID = ?");
$deleteRequests->bind_param("i", $studentID);
$deleteRequests->execute();
$deleteRequests->close();

// Now delete the student
$deleteStudent = $conn->prepare("DELETE FROM StudentInformation WHERE studentID = ?");
$deleteStudent->bind_param("i", $studentID);

if ($deleteStudent->execute()) {
    $_SESSION['message'] = "Student deleted successfully!";
} else {
    $_SESSION['error'] = "Failed to delete student.";
}

$deleteStudent->close();
header("Location: manage_students.php");
exit();
?>

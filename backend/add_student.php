<?php
session_start();
require_once '../config/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get currently logged-in user's full name
$loggedInUser = isset($_SESSION['first_name'], $_SESSION['last_name']) 
    ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name']
    : 'Unknown User';

// Handle adding a new student
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $studentNo = trim($_POST['studentNo']);
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $middlename = trim($_POST['middlename']);
    $birthDate = $_POST['birthDate'];
    $course_ID = $_POST['course_ID'];
    $majorID = $_POST['majorID'];
    $contactNo = trim($_POST['contactNo']);
    $addedBy = $_SESSION['user_id'];

    // Validate required fields
    if (empty($studentNo) || empty($firstname) || empty($lastname) || empty($birthDate) || empty($course_ID) || empty($majorID) || empty($contactNo)) {
        $_SESSION['error'] = "All fields are required!";
        header("Location: ../admin/add_student.php");
        exit();
    }

    // Insert into database (store the full name in added_By)
    $stmt = $conn->prepare("INSERT INTO studentInformation (studentNo, firstname, lastname, middlename, birthDate, course_ID, majorID, contactNo, added_By, dateCreated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssssiiss", $studentNo, $firstname, $lastname, $middlename, $birthDate, $course_ID, $majorID, $contactNo, $loggedInUser);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Student added successfully!";
    } else {
        $_SESSION['error'] = "Failed to add student.";
    }
    
    $stmt->close();
    header("Location: ../admin/add_student.php");
    exit();
}
?>

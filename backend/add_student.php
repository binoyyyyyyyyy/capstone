<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);




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
    $postedMajor = $_POST['majorID']; // May be numeric ID or a code/name
    $contactNo = trim($_POST['contactNo']);
    $studentStatus = $_POST['studentStatus'];
    $year = $_POST['yearLevel'];
    $addedBy = $loggedInUser;

    // Validate required fields
    if (empty($studentNo) || empty($firstname) || empty($lastname) || empty($birthDate) || 
        empty($course_ID) || empty($postedMajor) || empty($contactNo) || 
        empty($studentStatus) || empty($year)) {
        $_SESSION['error'] = "All fields are required!";
        header("Location: ../admin/add_student.php");
        exit();
    }

    // Check if student number already exists
    $checkStmt = $conn->prepare("SELECT studentID FROM StudentInformation WHERE studentNo = ?");
    $checkStmt->bind_param("s", $studentNo);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $_SESSION['error'] = "Student number already exists!";
        header("Location: ../admin/add_student.php");
        exit();
    }
    $checkStmt->close();

    // Determine majorID: accept numeric ID directly, otherwise look up by name/code
    if (ctype_digit((string)$postedMajor)) {
        $majorID = (int)$postedMajor;
        // verify it exists
        $verifyStmt = $conn->prepare("SELECT majorID FROM majortable WHERE majorID = ?");
        $verifyStmt->bind_param("i", $majorID);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();
        if ($verifyResult->num_rows === 0) {
            $_SESSION['error'] = "Selected major not found in database!";
            header("Location: ../admin/add_student.php");
            exit();
        }
        $verifyStmt->close();
    } else {
        $majorStmt = $conn->prepare("SELECT majorID FROM majortable WHERE majorName = ? OR majorCode = ?");
        $majorStmt->bind_param("ss", $postedMajor, $postedMajor);
        $majorStmt->execute();
        $majorResult = $majorStmt->get_result();
        if ($majorResult->num_rows === 0) {
            $_SESSION['error'] = "Selected major not found in database!";
            header("Location: ../admin/add_student.php");
            exit();
        }
        $majorID = $majorResult->fetch_assoc()['majorID'];
        $majorStmt->close();
    }

    // Insert into database (use $majorID now)
    $stmt = $conn->prepare("INSERT INTO StudentInformation 
        (studentNo, firstname, lastname, middlename, birthDate, course_ID, majorID, 
         contactNo, studentStatus, yearLevel, added_By, dateCreated) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $stmt->bind_param("sssssiissss", $studentNo, $firstname, $lastname, $middlename, 
        $birthDate, $course_ID, $majorID, $contactNo, $studentStatus, $year, $addedBy);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Student added successfully!";
        header("Location: ../admin/manage_students.php");
    } else {
        $_SESSION['error'] = "Failed to add student: " . $conn->error;
        header("Location: ../admin/add_student.php");
    }
    
    $stmt->close();
    exit();
}
?>
<?php
session_start();
require_once '../../config/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit();
}

// Fetch courses and majors
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $courses = $conn->query("SELECT courseID, courseName FROM coursetable ORDER BY courseName ASC");
    $majors = $conn->query("SELECT majorID, majorName FROM majortable ORDER BY majorName ASC");

    $courseList = [];
    while ($course = $courses->fetch_assoc()) {
        $courseList[] = $course;
    }

    $majorList = [];
    while ($major = $majors->fetch_assoc()) {
        $majorList[] = $major;
    }

    echo json_encode(["courses" => $courseList, "majors" => $majorList]);
    exit();
}

// Handle adding a new student
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentNo = trim($_POST['studentNo']);
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $middlename = trim($_POST['middlename']);
    $birthDate = $_POST['birthDate'];
    $course_ID = $_POST['course_ID'];
    $majorID = $_POST['majorID'];
    $contactNo = trim($_POST['contactNo']);
    $addedBy = $_SESSION['user_id'];

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO studentInformation (studentNo, firstname, lastname, middlename, birthDate, course_ID, majorID, contactNo, added_By, dateCreated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssssiisi", $studentNo, $firstname, $lastname, $middlename, $birthDate, $course_ID, $majorID, $contactNo, $addedBy);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Student added successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to add student."]);
    }

    $stmt->close();
    exit();
}
?>

<?php
session_start();
require_once '../config/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get currently logged-in user's name
$loggedInUser = isset($_SESSION['first_name'], $_SESSION['last_name']) 
    ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name']
    : 'Unknown User';

// Handle adding a new user
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role_type = $_POST['role_type'];
    $addedBy = $_SESSION['user_id'];

    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($role_type)) {
        $_SESSION['error'] = "All fields are required!";
        header("Location: ../admin/add_user.php");
        exit();
    }

    // Validate role type
    if (!in_array($role_type, ['admin', 'staff'])) {
        $_SESSION['error'] = "Invalid role type!";
        header("Location: ../admin/add_user.php");
        exit();
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Insert new user with "Added By" field
    $stmt = $conn->prepare("INSERT INTO UserTable (firstName, lastName, email, password, role_type, added_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $firstName, $lastName, $email, $hashedPassword, $role_type, $loggedInUser);

    if ($stmt->execute()) {
        $_SESSION['message'] = "User added successfully!";
    } else {
        $_SESSION['error'] = "Failed to add user.";
    }

    $stmt->close();
    header("Location: ../admin/add_user.php");
    exit();
}
?>

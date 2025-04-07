<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die(json_encode(["error" => "Invalid request"]));
}

$userID = intval($_GET['id']);
$loggedInUser = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

// Fetch user details
$stmt = $conn->prepare("SELECT firstName, lastName, email, role_type FROM UserTable WHERE userID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die(json_encode(["error" => "User not found"]));
}

// Update user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $email = trim($_POST['email']);
    $role_type = $_POST['role_type'];

    $stmt = $conn->prepare("UPDATE UserTable SET firstName = ?, lastName = ?, email = ?, role_type = ?, edited_by = ? WHERE userID = ?");
    $stmt->bind_param("sssssi", $firstName, $lastName, $email, $role_type, $loggedInUser, $userID);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "User updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update user.";
    }
    
    $stmt->close();
    header("Location: ../admin/manage_users.php");
    exit();
}

echo json_encode(["user" => $user, "loggedInUser" => $loggedInUser]);
?>
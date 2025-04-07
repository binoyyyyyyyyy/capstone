<?php
session_start();
require_once '../config/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid user ID.";
    header("Location: ../admin/manage_users.php");
    exit();
}

$userID = intval($_GET['id']);

// Prevent self-deletion
if ($_SESSION['user_id'] == $userID) {
    $_SESSION['error'] = "You cannot delete your own account.";
    header("Location: ../admin/manage_users.php");
    exit();
}

// Delete user
$stmt = $conn->prepare("DELETE FROM UserTable WHERE userID = ?");
$stmt->bind_param("i", $userID);

if ($stmt->execute()) {
    $_SESSION['message'] = "User deleted successfully!";
} else {
    $_SESSION['error'] = "Failed to delete user. Error: " . $stmt->error;
}

$stmt->close();
$conn->close();

// Redirect back
header("Location: ../admin/manage_users.php");
exit();

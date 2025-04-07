<?php
session_start();
require_once '../config/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Check if document ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../admin/manage_documents.php");
    exit();
}

$documentID = $_GET['id'];

// Soft delete by setting dateDeleted
$stmt = $conn->prepare("UPDATE DocumentsType SET dateDeleted = NOW() WHERE documentID = ?");
$stmt->bind_param("i", $documentID);

if ($stmt->execute()) {
    $_SESSION['message'] = "Document deleted successfully!";
} else {
    $_SESSION['error'] = "Failed to delete document.";
}

$stmt->close();
$conn->close();

header("Location: ../admin/manage_documents.php");
exit();
?>

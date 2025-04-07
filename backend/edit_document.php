<?php
session_start();
require_once '../config/config.php'; // Database connection

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get document ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../admin/manage_documents.php");
    exit();
}

$documentID = intval($_GET['id']);

// Fetch document details
$stmt = $conn->prepare("SELECT documentCode, documentName, documentDesc, documentStatus, procTime FROM DocumentsType WHERE documentID = ?");
$stmt->bind_param("i", $documentID);
$stmt->execute();
$result = $stmt->get_result();
$document = $result->fetch_assoc();
$stmt->close();

if (!$document) {
    header("Location: ../admin/manage_documents.php");
    exit();
}

// Update document
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documentCode = trim($_POST['documentCode']);
    $documentName = trim($_POST['documentName']);
    $documentDesc = trim($_POST['documentDesc']);
    $documentStatus = trim($_POST['documentStatus']);
    $procTime = trim($_POST['procTime']); // Processing time

    if (!empty($documentCode) && !empty($documentName) && !empty($documentDesc) && !empty($documentStatus) && !empty($procTime)) {
        $stmt = $conn->prepare("UPDATE DocumentsType SET documentCode = ?, documentName = ?, documentDesc = ?, documentStatus = ?, procTime = ? WHERE documentID = ?");
        $stmt->bind_param("sssssi", $documentCode, $documentName, $documentDesc, $documentStatus, $procTime, $documentID);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Document updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update document.";
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "All fields are required!";
    }

    header("Location: ../admin/edit_document.php?id=$documentID");
    exit();
}
$conn->close();
?>

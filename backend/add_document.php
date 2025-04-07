
<?php
session_start();
require_once '../config/config.php'; // Database connection

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documentCode = trim($_POST['documentCode']);
    $documentName = trim($_POST['documentName']);
    $documentDesc = trim($_POST['documentDesc']);
    $documentStatus = trim($_POST['documentStatus']);
    $processingTime = trim($_POST['processingTime']);

    if (!empty($documentCode) && !empty($documentName) && !empty($documentDesc) && !empty($documentStatus) && !empty($processingTime)) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM DocumentsType WHERE documentCode = ?");
        $stmt->bind_param("s", $documentCode);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            header("Location: ../admin/add_document.php?error=Document code already exists!");
            exit();
        } else {
            $stmt = $conn->prepare("INSERT INTO DocumentsType (documentCode, documentName, documentDesc, documentStatus, procTime) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $documentCode, $documentName, $documentDesc, $documentStatus, $processingTime);
            
            if ($stmt->execute()) {
                header("Location: ../admin/manage_documents.php");
                exit();
            } else {
                header("Location: ../admin/add_document.php?error=Failed to add document.");
            }
            $stmt->close();
        }
    } else {
        header("Location: ../admin/add_document.php?error=All fields are required!");
    }
}
?>

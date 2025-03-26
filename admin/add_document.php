<?php
session_start();
require_once '../config/config.php'; // Database connection

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documentCode = trim($_POST['documentCode']);
    $documentName = trim($_POST['documentName']);
    $documentDesc = trim($_POST['documentDesc']);
    $documentStatus = trim($_POST['documentStatus']);

    if (!empty($documentCode) && !empty($documentName) && !empty($documentDesc) && !empty($documentStatus)) {
        $stmt = $conn->prepare("INSERT INTO DocumentsType (documentCode, documentName, documentDesc, documentStatus) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $documentCode, $documentName, $documentDesc, $documentStatus);

        if ($stmt->execute()) {
            header("Location: manage_documents.php");
            exit();
        } else {
            $error = "Failed to add document.";
        }
        $stmt->close();
    } else {
        $error = "All fields are required!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Document</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="form-container">
        <h2>Add New Document</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="documentCode" placeholder="Document Code" required>
            <input type="text" name="documentName" placeholder="Document Name" required>
            <textarea name="documentDesc" placeholder="Description" required></textarea>
            <select name="documentStatus" required>
                <option value="available">available</option>
                <option value="unavailable">unavailable</option>
            </select>
            <button type="submit">Add Document</button>
        </form>
        <a href="manage_documents.php">Back to Documents</a><a href="dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>

<?php
session_start();
require_once '../config/config.php'; // Database connection

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$error = ""; // Initialize error message

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documentCode = trim($_POST['documentCode']);
    $documentName = trim($_POST['documentName']);
    $documentDesc = trim($_POST['documentDesc']);
    $documentStatus = trim($_POST['documentStatus']);

    // Validate input
    if (!empty($documentCode) && !empty($documentName) && !empty($documentDesc) && !empty($documentStatus)) {
        // Check if document code already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM DocumentsType WHERE documentCode = ?");
        $stmt->bind_param("s", $documentCode);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        
        if ($count > 0) {
            $error = "Document code already exists!";
        } else {
            // Insert new document
            $stmt = $conn->prepare("INSERT INTO DocumentsType (documentCode, documentName, documentDesc, documentStatus) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $documentCode, $documentName, $documentDesc, $documentStatus);

            if ($stmt->execute()) {
                header("Location: manage_documents.php");
                exit();
            } else {
                $error = "Failed to add document.";
            }
            $stmt->close();
        }
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow-lg p-4">
            <h2 class="text-center">Add New Document</h2>
            <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Document Code</label>
                    <input type="text" name="documentCode" class="form-control" placeholder="Enter Document Code" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Document Name</label>
                    <input type="text" name="documentName" class="form-control" placeholder="Enter Document Name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="documentDesc" class="form-control" placeholder="Enter Description" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="documentStatus" class="form-select" required>
                        <option value="available">Available</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100">Add Document</button>
            </form>
            <div class="text-center mt-3">
                <a href="manage_documents.php" class="btn btn-secondary">Back to Documents</a>
                <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
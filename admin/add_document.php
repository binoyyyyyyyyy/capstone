
<?php
session_start();
require_once '../config/config.php'; // Database connection

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
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
            <?php if (!empty($_GET['error'])) echo "<div class='alert alert-danger'>{$_GET['error']}</div>"; ?>
            <form method="POST" action="../backend/add_document.php">
                <div class="mb-3">
                    <label class="form-label">Document Code</label>
                    <input type="text" name="documentCode" class="form-control" placeholder="Enter Document Code" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Document Name</label>
                    <input type="text" name="documentName" class="form-control" placeholder="Enter Document Name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Processing Time:</label>
                    <select name="processingTime" class="form-select" required>
                        <option value="1 day">1 Day</option>
                        <option value="2 days">2 Days</option>
                        <option value="3 days">3 Days</option>
                        <option value="1 week">1 Week</option>
                        <option value="2 weeks">2 Weeks</option>
                        <option value="1 month">1 Month</option>
                    </select>
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

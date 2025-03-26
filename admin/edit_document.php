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
    header("Location: manage_documents.php");
    exit();
}

$documentID = $_GET['id'];

// Fetch document details
$stmt = $conn->prepare("SELECT documentCode, documentName, documentDesc, documentStatus FROM DocumentsType WHERE documentID = ?");
$stmt->bind_param("i", $documentID);
$stmt->execute();
$result = $stmt->get_result();
$document = $result->fetch_assoc();
$stmt->close();

if (!$document) {
    header("Location: manage_documents.php");
    exit();
}

// Update document
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documentCode = trim($_POST['documentCode']);
    $documentName = trim($_POST['documentName']);
    $documentDesc = trim($_POST['documentDesc']);
    $documentStatus = trim($_POST['documentStatus']);

    if (!empty($documentCode) && !empty($documentName) && !empty($documentDesc) && !empty($documentStatus)) {
        $stmt = $conn->prepare("UPDATE DocumentsType SET documentCode = ?, documentName = ?, documentDesc = ?, documentStatus = ? WHERE documentID = ?");
        $stmt->bind_param("ssssi", $documentCode, $documentName, $documentDesc, $documentStatus, $documentID);

        if ($stmt->execute()) {
            header("Location: manage_documents.php");
            exit();
        } else {
            $error = "Failed to update document.";
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
    <title>Edit Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white text-center">
                        <h4>Edit Document</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Document Code</label>
                                <input type="text" name="documentCode" class="form-control" value="<?php echo htmlspecialchars($document['documentCode']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Document Name</label>
                                <input type="text" name="documentName" class="form-control" value="<?php echo htmlspecialchars($document['documentName']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="documentDesc" class="form-control" required><?php echo htmlspecialchars($document['documentDesc']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="documentStatus" class="form-select" required>
                                    <option value="available" <?php if ($document['documentStatus'] == 'available') echo 'selected'; ?>>Available</option>
                                    <option value="unavailable" <?php if ($document['documentStatus'] == 'unavailable') echo 'selected'; ?>>Unavailable</option>
                                </select>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-success">Update Document</button>
                                <a href="manage_documents.php" class="btn btn-secondary">Back to Documents</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
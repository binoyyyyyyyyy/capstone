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
    header("Location: manage_documents.php");
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
    $_SESSION['error'] = "Document not found!";
    header("Location: manage_documents.php");
    exit();
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
                        <?php
                        if (isset($_SESSION['error'])) {
                            echo "<div class='alert alert-danger'>" . $_SESSION['error'] . "</div>";
                            unset($_SESSION['error']);
                        }
                        if (isset($_SESSION['message'])) {
                            echo "<div class='alert alert-success'>" . $_SESSION['message'] . "</div>";
                            unset($_SESSION['message']);
                        }
                        ?>

                        <form method="POST" action="../backend/edit_document.php?id=<?php echo $documentID; ?>">
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
                            <div class="mb-3">
                                <label class="form-label">Processing Time</label>
                                <select name="procTime" class="form-select" required>
                                    <option value="1 day" <?php if ($document['procTime'] == '1 day') echo 'selected'; ?>>1 Day</option>
                                    <option value="2 days" <?php if ($document['procTime'] == '2 days') echo 'selected'; ?>>2 Days</option>
                                    <option value="3 days" <?php if ($document['procTime'] == '3 days') echo 'selected'; ?>>3 Days</option>
                                    <option value="1 week" <?php if ($document['procTime'] == '1 week') echo 'selected'; ?>>1 Week</option>
                                    <option value="2 weeks" <?php if ($document['procTime'] == '2 weeks') echo 'selected'; ?>>2 Weeks</option>
                                    <option value="1 month" <?php if ($document['procTime'] == '1 month') echo 'selected'; ?>>1 Month</option>
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

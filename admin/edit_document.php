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
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="form-container">
        <h2>Edit Document</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="documentCode" value="<?php echo htmlspecialchars($document['documentCode']); ?>" required>
            <input type="text" name="documentName" value="<?php echo htmlspecialchars($document['documentName']); ?>" required>
            <textarea name="documentDesc" required><?php echo htmlspecialchars($document['documentDesc']); ?></textarea>
            <select name="documentStatus" required>
                <option value="available" <?php if ($document['documentStatus'] == 'available') echo 'selected'; ?>>available</option>
                <option value="unavailable" <?php if ($document['documentStatus'] == 'unavailable') echo 'selected'; ?>>unavailable</option>
            </select>
            <button type="submit">Update Document</button>
        </form>
        <a href="manage_documents.php">Back to Documents</a>
    </div>
</body>
</html>

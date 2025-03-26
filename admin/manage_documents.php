<?php
session_start();
require_once '../config/config.php'; // Database connection

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch documents from the database
$stmt = $conn->prepare("SELECT documentID, documentCode, documentName, documentDesc, documentStatus FROM DocumentsType WHERE dateDeleted IS NULL");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Documents</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <h2>Manage Documents</h2>
        <a href="dashboard.php">Back to Dashboard</a>
        <table>
            <thead>
                <tr>
                    <th>Document ID</th>
                    <th>Document Code</th>
                    <th>Document Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['documentID']); ?></td>
                        <td><?php echo htmlspecialchars($row['documentCode']); ?></td>
                        <td><?php echo htmlspecialchars($row['documentName']); ?></td>
                        <td><?php echo htmlspecialchars($row['documentDesc']); ?></td>
                        <td><?php echo htmlspecialchars($row['documentStatus']); ?></td>
                        <td>
                            <a href="edit_document.php?id=<?php echo $row['documentID']; ?>">Edit</a>
                            <a href="delete_document.php?id=<?php echo $row['documentID']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="add_document.php">Add New Document</a>
    </div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>

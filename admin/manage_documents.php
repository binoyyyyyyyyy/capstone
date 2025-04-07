<?php
session_start();
require_once '../config/config.php'; // Database connection

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch documents from the database
$stmt = $conn->prepare("SELECT documentID, documentCode, documentName, documentDesc, documentStatus, procTime FROM DocumentsType WHERE dateDeleted IS NULL");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Documents</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow-lg p-4">
            <h2 class="text-center">Manage Documents</h2>

            <!-- Display Success or Error Messages -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
            <?php elseif (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <div class="text-end mb-3">
                <a href="add_document.php" class="btn btn-success">Add New Document</a>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Document ID</th>
                        <th>Document Code</th>
                        <th>Document Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Processing Time</th> <!-- New Column -->
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
                            <td>
                                <span class="badge bg-<?php echo ($row['documentStatus'] == 'available') ? 'success' : 'danger'; ?>">
                                    <?php echo htmlspecialchars($row['documentStatus']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['procTime']); ?></td> <!-- Display Processing Time -->
                            <td>
    <a href="edit_document.php?id=<?php echo $row['documentID']; ?>" class="btn btn-warning btn-sm">Edit</a>
    <a href="../backend/delete_document.php?id=<?php echo $row['documentID']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</a>
</td>

                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>

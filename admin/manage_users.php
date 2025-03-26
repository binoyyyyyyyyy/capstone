<?php
session_start();
require_once '../config/config.php';

// Ensure session variables are set
$addedBy = isset($_SESSION['first_name']) && isset($_SESSION['last_name'])  
    ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] 
    : 'Unknown';

// Fetch all users
$stmt = $conn->prepare("SELECT userID, firstName, lastName, email, role_type, added_by, edited_by FROM UserTable");
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Manage Users</h2>

        <!-- Back & Add User Buttons -->
        <div class="d-flex justify-content-between mb-3">
            <a href="dashboard.php" class="btn btn-secondary">⬅ Back to Dashboard</a>
            <a href="add_user.php" class="btn btn-primary">➕ Add New User</a>
        </div>

        <!-- User Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Added By</th>
                        <th>Edited By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['firstName'] . ' ' . $row['lastName']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($row['role_type'])); ?></td>
                        <td><?php echo htmlspecialchars($row['added_by'] ?? 'Unknown'); ?></td>
                        <td><?php echo htmlspecialchars($row['edited_by'] ?? 'Not Edited'); ?></td>
                        <td>
                            <a href="edit_user.php?id=<?php echo $row['userID']; ?>" class="btn btn-sm btn-warning">✏ Edit</a>
                            <a href="delete_user.php?id=<?php echo $row['userID']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">🗑 Delete</a>
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

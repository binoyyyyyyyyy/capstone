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
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="container">
    <a href="dashboard.php">Back to Dashboard</a>
        <h2>Manage Users</h2>
       
        <a href="add_user.php">Add New User</a>
        <table border="1">
            <thead>
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
                        <a href="edit_user.php?id=<?php echo $row['userID']; ?>">Edit</a>
                        <a href="delete_user.php?id=<?php echo $row['userID']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

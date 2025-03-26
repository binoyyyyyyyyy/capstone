<?php
session_start();
require_once '../config/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get currently logged-in user's name
$loggedInUser = isset($_SESSION['first_name'], $_SESSION['last_name']) 
    ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name']
    : 'Unknown User';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role_type = $_POST['role_type'];

    if (!in_array($role_type, ['admin', 'staff'])) {
        die("Invalid role type");
    }

    // Insert new user with "Added By" field
    $stmt = $conn->prepare("INSERT INTO UserTable (firstName, lastName, email, password, role_type, added_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $firstName, $lastName, $email, $password, $role_type, $loggedInUser);

    if ($stmt->execute()) {
        $_SESSION['message'] = "User added successfully!";
    } else {
        $_SESSION['error'] = "Failed to add user.";
    }

    $stmt->close();
    header("Location: manage_users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Add New User</h2>

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="card shadow p-4">
            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label">First Name:</label>
                    <input type="text" name="firstName" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Last Name:</label>
                    <input type="text" name="lastName" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email:</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password:</label>
                    <div class="input-group">
                        <input type="password" id="password" name="password" class="form-control" required>
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">👁</button>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Role:</label>
                    <select name="role_type" class="form-select" required>
                        <option value="admin">Admin</option>
                        <option value="staff">Staff</option>
                    </select>
                </div>

                <p><strong>Added By:</strong> <?php echo htmlspecialchars($loggedInUser); ?></p>

                <div class="d-flex justify-content-between">
                    <a href="dashboard.php" class="btn btn-secondary">⬅ Back to Dashboard</a>
                    <button type="submit" class="btn btn-primary">➕ Add User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            password.type = password.type === 'password' ? 'text' : 'password';
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

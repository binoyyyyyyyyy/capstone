<?php
session_start();
require_once '../config/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Invalid request");
}

$userID = $_GET['id'];
$loggedInUser = $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; // Get the logged-in admin name

// Fetch user details
$stmt = $conn->prepare("SELECT firstName, lastName, email, role_type FROM UserTable WHERE userID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found");
}

// Update user
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $email = trim($_POST['email']);
    $role_type = $_POST['role_type'];

    // Update user details and set edited_by
    $stmt = $conn->prepare("UPDATE UserTable SET firstName = ?, lastName = ?, email = ?, role_type = ?, edited_by = ? WHERE userID = ?");
    $stmt->bind_param("sssssi", $firstName, $lastName, $email, $role_type, $loggedInUser, $userID);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "User updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update user.";
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
    <title>Edit User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Edit User</h2>

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
                    <input type="text" name="firstName" class="form-control" value="<?php echo htmlspecialchars($user['firstName']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Last Name:</label>
                    <input type="text" name="lastName" class="form-control" value="<?php echo htmlspecialchars($user['lastName']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email:</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Role:</label>
                    <select name="role_type" class="form-select" required>
                        <option value="admin" <?php echo ($user['role_type'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="staff" <?php echo ($user['role_type'] == 'staff') ? 'selected' : ''; ?>>Staff</option>
                    </select>
                </div>

                <p><strong>Edited By:</strong> <?php echo htmlspecialchars($loggedInUser); ?></p>

                <div class="d-flex justify-content-between">
                    <a href="dashboard.php" class="btn btn-secondary">⬅ Back to Dashboard</a>
                    <button type="submit" class="btn btn-primary">💾 Update User</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

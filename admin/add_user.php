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
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="container">
    <a href="dashboard.php">Back to Dashboard</a>
        <h2>Add User</h2>
        <form action="" method="POST">
            <label>First Name:</label>
            <input type="text" name="firstName" required>

            <label>Last Name:</label>
            <input type="text" name="lastName" required>

            <label>Email:</label>
            <input type="email" name="email" required>

            <label>Password:</label>
            <input type="password" name="password" required>

            <label>Role:</label>
            <select name="role_type" required>
                <option value="admin">Admin</option>
                <option value="staff">Staff</option>
            </select>

            <p><strong>Added By:</strong> <?php echo htmlspecialchars($loggedInUser); ?></p>

            <button type="submit">Add User</button>
        </form>
    </div>
</body>
</html>

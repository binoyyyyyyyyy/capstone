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
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="container">
    <a href="dashboard.php">Back to Dashboard</a>
        <h2>Edit User</h2>
        <form action="" method="POST">
            <label>First Name:</label>
            <input type="text" name="firstName" value="<?php echo htmlspecialchars($user['firstName']); ?>" required>
            
            <label>Last Name:</label>
            <input type="text" name="lastName" value="<?php echo htmlspecialchars($user['lastName']); ?>" required>
            
            <label>Email:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            
            <label>Role:</label>
            <select name="role_type" required>
                <option value="admin" <?php echo ($user['role_type'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                <option value="staff" <?php echo ($user['role_type'] == 'staff') ? 'selected' : ''; ?>>Staff</option>
            </select>
            
            <p><strong>Edited By:</strong> <?php echo htmlspecialchars($loggedInUser); ?></p>

            <button type="submit">Update User</button>
        </form>
    </div>
</body>
</html>

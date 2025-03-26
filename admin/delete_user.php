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

// Prevent self-deletion
if ($_SESSION['admin_id'] == $userID) {
    die("You cannot delete your own account.");
}

// Fetch user details
$stmt = $conn->prepare("SELECT firstName, lastName FROM UserTable WHERE userID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result->num_rows === 0) {
    die("User not found");
}

$user = $result->fetch_assoc();

// Confirm deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['confirm_delete'])) {
        $stmt = $conn->prepare("DELETE FROM UserTable WHERE userID = ?");
        $stmt->bind_param("i", $userID);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "User deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete user.";
        }
        
        $stmt->close();
        header("Location: manage_users.php");
        exit();
    } else {
        header("Location: manage_users.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete User</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="container">
        <h2>Confirm Deletion</h2>
        <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($user['firstName'] . " " . $user['lastName']); ?></strong>?</p>
        <form action="" method="POST">
            <button type="submit" name="confirm_delete">Yes, Delete</button>
            <a href="manage_users.php">Cancel</a>
        </form>
    </div>
</body>
</html>

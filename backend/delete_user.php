<?php
// Check if session is already started (config.php already starts it)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once '../config/config.php';

$response = ['success' => false];

try {
    if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'admin') {
        throw new Exception("Unauthorized access.");
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['userID'], $_POST['password'])) {
        throw new Exception("Invalid request.");
    }

    $userID = intval($_POST['userID']);
    $password = $_POST['password'];
    $currentUserID = $_SESSION['user_id'];

    // Prevent self-deletion
    if ($userID == $currentUserID) {
        throw new Exception("You cannot delete your own account.");
    }

    // Verify user's password
    $stmt = $conn->prepare("SELECT password FROM UserTable WHERE userID = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $currentUserID);
    if (!$stmt->execute()) {
        throw new Exception("Database error: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            // Delete user
            $deleteStmt = $conn->prepare("DELETE FROM UserTable WHERE userID = ?");
            if (!$deleteStmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            $deleteStmt->bind_param("i", $userID);
            
            if ($deleteStmt->execute()) {
                if ($deleteStmt->affected_rows > 0) {
                    $response['success'] = true;
                    $response['message'] = "User deleted successfully!";
                } else {
                    throw new Exception("No user found with that ID.");
                }
            } else {
                throw new Exception("Failed to delete user.");
            }
            
            $deleteStmt->close();
        } else {
            throw new Exception("Incorrect password.");
        }
    } else {
        throw new Exception("User not found.");
    }
    
    $stmt->close();
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

$conn->close();
echo json_encode($response);
exit();
?>
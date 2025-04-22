<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json'); // Add this line

require_once '../config/config.php';

$response = ['success' => false, 'message' => ''];

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User is not logged in.");
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['requestID'], $_POST['password'])) {
        throw new Exception("Invalid deletion request.");
    }

    $requestID = intval($_POST['requestID']);
    $password = $_POST['password'];
    $userID = $_SESSION['user_id'];

    // Verify user's password
    $stmt = $conn->prepare("SELECT password FROM userTable WHERE userID = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $userID);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            // Check if request exists
            $checkStmt = $conn->prepare("SELECT requestID FROM RequestTable WHERE requestID = ? AND dateDeleted IS NULL");
            $checkStmt->bind_param("i", $requestID);
            $checkStmt->execute();
            
            if ($checkStmt->get_result()->num_rows === 0) {
                throw new Exception("Request not found or already deleted.");
            } else {
                // Soft delete the request
                $deleteStmt = $conn->prepare("UPDATE RequestTable SET dateDeleted = NOW() WHERE requestID = ?");
                if (!$deleteStmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $deleteStmt->bind_param("i", $requestID);
                if ($deleteStmt->execute() && $deleteStmt->affected_rows > 0) {
                    $response['success'] = true;
                    $response['message'] = "Request deleted successfully!";
                } else {
                    throw new Exception("No rows affected - request may not exist or already be deleted.");
                }
                $deleteStmt->close();
            }
            $checkStmt->close();
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
<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once '../config/config.php';

$response = ['success' => false];

try {
    if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'admin') {
        throw new Exception("Unauthorized access.");
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['documentID'], $_POST['password'])) {
        throw new Exception("Invalid request.");
    }

    $documentID = intval($_POST['documentID']);
    $password = $_POST['password'];
    $userID = $_SESSION['user_id'];

    // Verify user's password
    $stmt = $conn->prepare("SELECT password FROM userTable WHERE userID = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $userID);
    if (!$stmt->execute()) {
        throw new Exception("Database error: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            // Soft delete the document
            $deleteStmt = $conn->prepare("UPDATE DocumentsType SET dateDeleted = NOW() WHERE documentID = ?");
            if (!$deleteStmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            $deleteStmt->bind_param("i", $documentID);
            
            if ($deleteStmt->execute()) {
                if ($deleteStmt->affected_rows > 0) {
                    $response['success'] = true;
                    $response['message'] = "Document type deleted successfully!";
                } else {
                    throw new Exception("No document found with that ID or already deleted.");
                }
            } else {
                throw new Exception("Failed to delete document.");
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
<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once '../config/config.php';

$response = ['success' => false];

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User is not logged in.");
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['studentID'], $_POST['password'])) {
        throw new Exception("Invalid deletion request.");
    }

    $studentID = intval($_POST['studentID']);
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
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Delete related requests
                $deleteRequests = $conn->prepare("DELETE FROM RequestTable WHERE studentID = ?");
                if (!$deleteRequests) {
                    throw new Exception("Database error: " . $conn->error);
                }
                $deleteRequests->bind_param("i", $studentID);
                $deleteRequests->execute();
                $deleteRequests->close();
                
                // Delete the student
                $deleteStudent = $conn->prepare("DELETE FROM StudentInformation WHERE studentID = ?");
                if (!$deleteStudent) {
                    throw new Exception("Database error: " . $conn->error);
                }
                $deleteStudent->bind_param("i", $studentID);
                $deleteStudent->execute();
                
                if ($deleteStudent->affected_rows > 0) {
                    $conn->commit();
                    $response['success'] = true;
                    $response['message'] = "Student and all associated requests deleted successfully!";
                } else {
                    throw new Exception("No student found with that ID.");
                }
                
                $deleteStudent->close();
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
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
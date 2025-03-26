<?php
header("Content-Type: application/json");
require_once '../config/config.php'; // Database connection

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET': // Fetch requests with student & document info
        $stmt = $conn->prepare("SELECT r.requestID, r.requestCode, 
    DATE_FORMAT(r.dateRequest, '%Y-%m-%d %H:%i:%s') AS dateRequest, 
    r.requestStatus, s.firstname, s.lastname, d.documentName 
    FROM RequestTable r 
    JOIN StudentInformation s ON r.studentID = s.studentID 
    JOIN DocumentsType d ON r.documentID = d.documentID 
    ORDER BY r.dateRequest DESC");


        
        $stmt->execute();
        $result = $stmt->get_result();
        $requests = $result->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode(["status" => "success", "data" => $requests]);
        break;

    case 'POST': // Create a new request
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['documentID'], $data['userID'], $data['studentID'], $data['datePickUp'])) {
            echo json_encode(["status" => "error", "message" => "Missing required fields"]);
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO RequestTable (requestCode, documentID, userID, studentID, dateRequest, datePickUp, requestStatus, dateCreated) 
                                VALUES (?, ?, ?, ?, NOW(), ?, 'pending', NOW())");
        $requestCode = uniqid("REQ-");
        $stmt->bind_param("siisi", $requestCode, $data['documentID'], $data['userID'], $data['studentID'], $data['datePickUp']);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Request submitted successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to submit request"]);
        }
        break;

    case 'DELETE': // Delete a request
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data['requestID'])) {
            echo json_encode(["status" => "error", "message" => "Missing request ID"]);
            exit();
        }

        $stmt = $conn->prepare("UPDATE RequestTable SET dateDeleted = NOW() WHERE requestID = ?");
        $stmt->bind_param("i", $data['requestID']);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Request deleted successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to delete request"]);
        }
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Invalid request method"]);
        break;
}
?>

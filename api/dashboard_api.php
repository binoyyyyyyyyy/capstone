<?php
header("Content-Type: application/json");
require_once '../config/config.php'; // Database connection

$response = ["status" => "error", "message" => "Invalid request"];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get total requests
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM RequestTable WHERE dateDeleted IS NULL");
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();

    // Get pending requests
    $stmt = $conn->prepare("SELECT COUNT(*) AS pending FROM RequestTable WHERE requestStatus = 'pending' AND dateDeleted IS NULL");
    $stmt->execute();
    $stmt->bind_result($pending);
    $stmt->fetch();
    $stmt->close();

    // Get approved requests
    $stmt = $conn->prepare("SELECT COUNT(*) AS approved FROM RequestTable WHERE requestStatus = 'approved' AND dateDeleted IS NULL");
    $stmt->execute();
    $stmt->bind_result($approved);
    $stmt->fetch();
    $stmt->close();

    // Get rejected requests
    $stmt = $conn->prepare("SELECT COUNT(*) AS rejected FROM RequestTable WHERE requestStatus = 'rejected' AND dateDeleted IS NULL");
    $stmt->execute();
    $stmt->bind_result($rejected);
    $stmt->fetch();
    $stmt->close();

    // Get completed requests
    $stmt = $conn->prepare("SELECT COUNT(*) AS completed FROM RequestTable WHERE requestStatus = 'completed' AND dateDeleted IS NULL");
    $stmt->execute();
    $stmt->bind_result($completed);
    $stmt->fetch();
    $stmt->close();

    // Get ready to pickup requests
    $stmt = $conn->prepare("SELECT COUNT(*) AS ready FROM RequestTable WHERE requestStatus = 'ready to pickup' AND dateDeleted IS NULL");
    $stmt->execute();
    $stmt->bind_result($ready);
    $stmt->fetch();
    $stmt->close();

    // Get recent activity (last 5 requests)
    $stmt = $conn->prepare("
        SELECT r.requestID, r.requestCode,
               DATE_FORMAT(r.dateRequest, '%Y-%m-%d %H:%i:%s') AS dateRequest,
               r.requestStatus, s.firstname, s.lastname, d.documentName
        FROM RequestTable r
        JOIN StudentInformation s ON r.studentID = s.studentID
        JOIN DocumentsType d ON r.documentID = d.documentID
        WHERE r.dateDeleted IS NULL
        ORDER BY r.dateRequest DESC
        LIMIT 5
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $recentRequests = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Final response
    $response = [
        "status" => "success",
        "total" => $total,
        "pending" => $pending,
        "approved" => $approved,
        "rejected" => $rejected,
        "completed" => $completed,
        "ready" => $ready,
        "recent" => $recentRequests
    ];
}

echo json_encode($response);
?>
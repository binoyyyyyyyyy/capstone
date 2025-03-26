<?php
header("Content-Type: application/json");
require_once '../config/config.php'; // Database connection

$response = ["status" => "error", "message" => "Invalid request"];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get total requests
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM RequestTable");
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();

    // Get pending requests
    $stmt = $conn->prepare("SELECT COUNT(*) AS pending FROM RequestTable WHERE requestStatus = 'pending'");
    $stmt->execute();
    $stmt->bind_result($pending);
    $stmt->fetch();
    $stmt->close();

    // Get approved requests
    $stmt = $conn->prepare("SELECT COUNT(*) AS approved FROM RequestTable WHERE requestStatus = 'approved'");
    $stmt->execute();
    $stmt->bind_result($approved);
    $stmt->fetch();
    $stmt->close();

    // Get rejected requests
    $stmt = $conn->prepare("SELECT COUNT(*) AS rejected FROM RequestTable WHERE requestStatus = 'rejected'");
    $stmt->execute();
    $stmt->bind_result($rejected);
    $stmt->fetch();
    $stmt->close();

    $response = [
        "status" => "success",
        "total" => $total,
        "pending" => $pending,
        "approved" => $approved,
        "rejected" => $rejected
    ];
}

echo json_encode($response);
?>

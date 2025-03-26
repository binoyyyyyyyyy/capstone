<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if a request ID is provided
$requestID = isset($_GET['id']) ? intval($_GET['id']) : null;

// If no request ID is provided, allow user to input one
if (!$requestID) {
    echo "<form method='GET' action='view_requests.php'>";
    echo "<label for='id'>Enter Request ID:</label>";
    echo "<input type='number' name='id' required>";
    echo "<button type='submit'>View Request</button>";
    echo "</form>";
    exit();
}

// Fetch the selected request
$stmt = $conn->prepare("SELECT r.requestID, r.requestCode, r.dateRequest, r.requestStatus, 
    s.firstname, s.lastname, d.documentName, r.datePickUp, r.nameOfReceiver, r.remarks
    FROM RequestTable r
    JOIN studentInformation s ON r.studentID = s.studentID
    JOIN DocumentsType d ON r.documentID = d.documentID
    WHERE r.requestID = ?");
$stmt->bind_param("i", $requestID);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();

// If no request is found
if (!$request) {
    echo "<p>No request found with ID: $requestID</p>";
    echo "<a href='manage_request.php'>Back to Manage Requests</a>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Request</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="container">
        <h2>Request Details</h2>
        <table border="1">
            <tr><th>Request Code</th><td><?php echo htmlspecialchars($request['requestCode']); ?></td></tr>
            <tr><th>Student Name</th><td><?php echo htmlspecialchars($request['firstname'] . ' ' . $request['lastname']); ?></td></tr>
            <tr><th>Document Name</th><td><?php echo htmlspecialchars($request['documentName']); ?></td></tr>
            <tr><th>Date Requested</th><td><?php echo htmlspecialchars($request['dateRequest']); ?></td></tr>
            <tr><th>Pick-up Date</th><td><?php echo htmlspecialchars($request['datePickUp']); ?></td></tr>
            <tr><th>Status</th><td><?php echo htmlspecialchars($request['requestStatus']); ?></td></tr>
            <tr><th>Receiver Name</th><td><?php echo htmlspecialchars($request['nameOfReceiver']); ?></td></tr>
            <tr><th>Remarks</th><td><?php echo htmlspecialchars($request['remarks']); ?></td></tr>
        </table>
        <a href="manage_request.php">Back to Manage Requests</a>
    </div>
</body>
</html>

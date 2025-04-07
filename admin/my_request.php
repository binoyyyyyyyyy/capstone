<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if a request code is provided
$requestCode = isset($_POST['code']) ? $_POST['code'] : null;

// If no request code is provided, allow user to input one
if (!$requestCode) {
    echo "<form method='POST' action='my_request.php'>";
    echo "<label for='code'>Enter Request Code:</label>";
    echo "<input type='text' name='code' required>";
    echo "<button type='submit'>View Request</button>";
    echo "</form>";
    exit();
}

// Fetch the selected request
$stmt = $conn->prepare("SELECT r.requestID, r.requestCode, r.dateRequest, r.requestStatus, 
    s.firstname, s.lastname, d.documentName, r.datePickUp, r.nameOfReceiver, r.remarks, 
    si.image
    FROM RequestTable r
    JOIN studentInformation s ON r.studentID = s.studentID
    JOIN DocumentsType d ON r.documentID = d.documentID
    LEFT JOIN supportingimage si ON r.requestID = si.requestID
    WHERE r.requestCode = ?");
$stmt->bind_param("s", $requestCode);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();

// If no request is found
if (!$request) {
    echo "<p>No request found with code: $requestCode</p>";
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white text-center">
                        <h4>Request Details</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr><th>Request Code</th><td><?php echo htmlspecialchars($request['requestCode']); ?></td></tr>
                            <tr><th>Student Name</th><td><?php echo htmlspecialchars($request['firstname'] . ' ' . $request['lastname']); ?></td></tr>
                            <tr><th>Document Name</th><td><?php echo htmlspecialchars($request['documentName']); ?></td></tr>
                            <tr><th>Date Requested</th><td><?php echo htmlspecialchars($request['dateRequest']); ?></td></tr>
                            <tr><th>Pick-up Date</th><td><?php echo htmlspecialchars($request['datePickUp']); ?></td></tr>
                            <tr><th>Status</th><td><?php echo htmlspecialchars($request['requestStatus']); ?></td></tr>
                            <tr><th>Receiver Name</th><td><?php echo htmlspecialchars($request['nameOfReceiver']); ?></td></tr>
                            <tr><th>Remarks</th><td><?php echo htmlspecialchars($request['remarks']); ?></td></tr>
                            <?php if (!empty($request['image'])): ?>
                                <tr>
                                    <th>Authorization Image</th>
                                    <td>
                                        <img src="../uploads/<?php echo htmlspecialchars($request['image']); ?>" class="img-fluid" style="max-width: 600px;">
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </table>
                        <div class="text-center">
                            <a href="manage_request.php" class="btn btn-secondary">Back to Manage Requests</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

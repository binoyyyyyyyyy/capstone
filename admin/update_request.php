<?php
session_start();
require_once '../config/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Validate request ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: No request ID provided.");
}

$requestID = intval($_GET['id']);

// Fetch request details
$stmt = $conn->prepare("SELECT requestID, requestStatus FROM RequestTable WHERE requestID = ?");
$stmt->bind_param("i", $requestID);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();

// Check if request exists
if (!$request) {
    die("Error: Request not found.");
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $newStatus = $_POST['requestStatus'];

    // Validate status
    $validStatuses = ['pending', 'approved', 'rejected'];
    if (!in_array($newStatus, $validStatuses)) {
        die("Error: Invalid status.");
    }

    // Update request in database
    $stmt = $conn->prepare("UPDATE RequestTable SET requestStatus = ? WHERE requestID = ?");
    $stmt->bind_param("si", $newStatus, $requestID);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Request updated successfully!";
        header("Location: manage_request.php");
        exit();
    } else {
        die("Error: Failed to update request.");
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Request</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="container">
        <h2>Update Request</h2>

        <form action="update_request.php?id=<?php echo $requestID; ?>" method="post">
            <label for="requestStatus">Request Status:</label>
            <select name="requestStatus" id="requestStatus">
                <option value="pending" <?php echo ($request['requestStatus'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?php echo ($request['requestStatus'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
                <option value="rejected" <?php echo ($request['requestStatus'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
            </select>
            <br><br>
            <button type="submit">Update</button>
        </form>

        <br>
        <a href="manage_request.php">Back to Manage Requests</a>
    </div>
</body>
</html>

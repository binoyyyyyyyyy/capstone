<?php
session_start();
require_once '../config/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch all requests with document names
$stmt = $conn->prepare("SELECT r.requestID, r.requestCode, r.dateRequest, r.requestStatus, 
    s.firstname, s.lastname, d.documentName 
    FROM RequestTable r 
    JOIN studentInformation s ON r.studentID = s.studentID 
    JOIN DocumentsType d ON r.documentID = d.documentID 
    ORDER BY r.dateRequest DESC");
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Requests</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="container">
    <a href="dashboard.php">Back to Dashboard</a>
        <h2>Manage Requests</h2>
        <table border="1">
    <thead>
        <tr>
            <th>Request Code</th>
            <th>Student Name</th>
            <th>Document</th>
            <th>Date Requested</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody id="requestTableBody">
        <!-- Requests will be dynamically loaded here -->
    </tbody>
</table>

<script>
document.addEventListener("DOMContentLoaded", function () {
    fetchRequests(); // Load requests when the page loads
});

function fetchRequests() {
    fetch('../api/request_api.php') // Call your API
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                let tableBody = document.getElementById("requestTableBody");
                tableBody.innerHTML = ""; // Clear existing rows

                data.data.forEach(request => {
                    let row = `<tr>
                        <td>${request.requestCode}</td>
                        <td>${request.firstname} ${request.lastname}</td>
                        <td>${request.documentName}</td>
                        <td>${request.dateRequest}</td>
                        <td>${request.requestStatus}</td>
                        <td>
                            <a href="update_request.php?id=${request.requestID}">Update</a>
                            <a href="view_requests.php?id=${request.requestID}">View</a>
                            <button onclick="deleteRequest(${request.requestID})">Delete</button>
                        </td>
                    </tr>`;
                    tableBody.innerHTML += row;
                });
            }
        })
        .catch(error => console.error("Error fetching requests:", error));
}

function deleteRequest(requestID) {
    if (!confirm("Are you sure you want to delete this request?")) return;

    fetch('../api/request_api.php', {
        method: "DELETE",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ requestID })
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        fetchRequests(); // Refresh table dynamically
    })
    .catch(error => console.error("Error deleting request:", error));
}
</script>

    </div>
</body>
</html>

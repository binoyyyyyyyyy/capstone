<?php
session_start();
require_once '../config/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Requests</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container mt-4">
        <h2 class="text-center">Manage Requests</h2>
        <a href="dashboard.php" class="btn btn-primary mb-3">Back to Dashboard</a>

        <table class="table table-bordered table-hover">
            <thead class="table-dark">
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
    </div>

    <script>
        $(document).ready(function () {
            fetchRequests();
            setInterval(fetchRequests, 5000); // Auto-refresh every 5 seconds
        });

        function fetchRequests() {
            $.getJSON("../api/request_api.php", function(data) {
                if (data.status === "success") {
                    let tableBody = $("#requestTableBody");
                    tableBody.empty();

                    data.data.forEach(request => {
                        let row = `<tr>
                            <td>${request.requestCode}</td>
                            <td>${request.firstname} ${request.lastname}</td>
                            <td>${request.documentName}</td>
                            <td>${request.dateRequest}</td>
                            <td><span class="badge bg-${getStatusClass(request.requestStatus)}">${request.requestStatus}</span></td>
                            <td>
                                <a href="update_request.php?id=${request.requestID}" class="btn btn-warning btn-sm">Update</a>
                                <a href="view_requests.php?id=${request.requestID}" class="btn btn-info btn-sm">View</a>
                                <button class="btn btn-danger btn-sm" onclick="deleteRequest(${request.requestID})">Delete</button>
                            </td>
                        </tr>`;
                        tableBody.append(row);
                    });
                }
            }).fail(function() {
                console.error("Error fetching requests.");
            });
        }

        function getStatusClass(status) {
            return status === "pending" ? "warning" : (status === "approved" ? "success" : "danger");
        }

        function deleteRequest(requestID) {
            if (!confirm("Are you sure you want to delete this request?")) return;

            $.ajax({
                url: '../api/request_api.php',
                method: "DELETE",
                contentType: "application/json",
                data: JSON.stringify({ requestID }),
                success: function(response) {
                    alert(response.message);
                    fetchRequests();
                },
                error: function() {
                    console.error("Error deleting request.");
                }
            });
        }
    </script>
</body>
</html>

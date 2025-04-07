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

        <!-- Display success/error messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <a href="dashboard.php" class="btn btn-primary mb-3">Back to Dashboard</a>
        <a href="request_form.php" class="btn btn-primary mb-3">make a request</a>

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
                        let formattedDate = new Date(request.dateRequest).toLocaleString();
                        let row = `<tr>
                            <td>${request.requestCode}</td>
                            <td>${request.firstname} ${request.lastname}</td>
                            <td>${request.documentName}</td>
                            <td>${formattedDate}</td>
                            <td><span class="badge bg-${getStatusClass(request.requestStatus)}">${request.requestStatus}</span></td>
                            <td>
                                <a href="update_request.php?id=${request.requestID}" class="btn btn-warning btn-sm">Update</a>
                                <a href="view_requests.php?id=${request.requestID}" class="btn btn-info btn-sm">View</a>
                                <a href="../backend/delete_request.php?id=${request.requestID}" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</a>
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
    </script>
</body>
</html>

<?php
session_start();
require_once '../config/config.php'; // Database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$role = $_SESSION['role_type']; // Get user role
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="manage_request.php">Manage Requests</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_students.php">View Student Records</a></li>
                    <?php if ($role === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="manage_documents.php">Manage Documents</a></li>
                        <li class="nav-item"><a class="nav-link" href="manage_users.php">Manage Admins</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link btn btn-danger text-white" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <div class="container mt-4">
        <h2 class="text-center">Welcome, <?php echo htmlspecialchars($_SESSION['user_email']); ?></h2>
        <p class="text-center">Role: <strong><?php echo htmlspecialchars($role); ?></strong></p>

        <!-- Dashboard Stats -->
        <div class="row text-center">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Requests</h5>
                        <p class="card-text fs-3" id="totalRequests">0</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Pending</h5>
                        <p class="card-text fs-3" id="pendingRequests">0</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Approved</h5>
                        <p class="card-text fs-3" id="approvedRequests">0</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h5 class="card-title">Rejected</h5>
                        <p class="card-text fs-3" id="rejectedRequests">0</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fetch Dashboard Stats -->
    <script>
        function fetchDashboardStats() {
            $.getJSON("../api/dashboard_api.php", function(data) {
                if (data.status === "success") {
                    $("#totalRequests").text(data.total);
                    $("#pendingRequests").text(data.pending);
                    $("#approvedRequests").text(data.approved);
                    $("#rejectedRequests").text(data.rejected);
                }
            }).fail(function() {
                console.error("Error fetching dashboard stats.");
            });
        }

        $(document).ready(function() {
            fetchDashboardStats();
            setInterval(fetchDashboardStats, 5000); // Refresh every 5 seconds
        });
    </script>
</body>
</html>

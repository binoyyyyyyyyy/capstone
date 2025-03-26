<?php
session_start();
require_once '../config/config.php'; // Database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$role = $_SESSION['role_type']; // Get user role

// Fetch request statistics
$stmt = $conn->prepare("SELECT 
    SUM(requestStatus = 'pending') AS pending, 
    SUM(requestStatus = 'approved') AS approved, 
    SUM(requestStatus = 'rejected') AS rejected 
    FROM RequestTable");
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="dashboard-container">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_email']); ?></h2>
        <p>Role: <?php echo htmlspecialchars($role); ?></p>
        
        <div id="dashboardStats">
    <p>Total Requests: <span id="totalRequests">0</span></p>
    <p>Pending Requests: <span id="pendingRequests">0</span></p>
    <p>Approved Requests: <span id="approvedRequests">0</span></p>
    <p>Rejected Requests: <span id="rejectedRequests">0</span></p>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    fetchDashboardStats(); // Load stats when page loads
    setInterval(fetchDashboardStats, 5000); // Auto-update every 5 seconds
});

function fetchDashboardStats() {
    fetch('../api/dashboard_api.php') // Call API
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                document.getElementById("totalRequests").innerText = data.total;
                document.getElementById("pendingRequests").innerText = data.pending;
                document.getElementById("approvedRequests").innerText = data.approved;
                document.getElementById("rejectedRequests").innerText = data.rejected;
            }
        })
        .catch(error => console.error("Error fetching dashboard stats:", error));
}
</script>

        
        <div class="nav-links">
        <a href="manage_request.php">Manage Requests</a>
<a href="manage_students.php">View Student Records</a>

<?php if ($role === 'admin'): ?>
    <a href="manage_documents.php">Manage Documents</a>
    <a href="manage_users.php">Manage Admins</a>
<?php endif; ?>

<a href="logout.php">Logout</a>

        </div>
    </div>
</body>
</html>

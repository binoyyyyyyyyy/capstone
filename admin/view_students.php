<?php
session_start();
require_once '../config/config.php'; // Database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$role = $_SESSION['role_type'];

// Fetch student records
$stmt = $conn->prepare("SELECT studentID, studentName, studentEmail, studentCourse, dateEnrolled FROM StudentTable");
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>View Students</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="container">
    <a href="dashboard.php">Back to Dashboard</a>
        <h2>Student Records</h2>
        <table border="1">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Date Enrolled</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['studentID']); ?></td>
                        <td><?php echo htmlspecialchars($row['studentName']); ?></td>
                        <td><?php echo htmlspecialchars($row['studentEmail']); ?></td>
                        <td><?php echo htmlspecialchars($row['studentCourse']); ?></td>
                        <td><?php echo htmlspecialchars($row['dateEnrolled']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="../admin/dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>
<?php
session_start();
require_once '../config/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch all students with course and major names
$sql = "SELECT s.*, c.courseName, m.majorName FROM studentInformation s 
        JOIN coursetable c ON s.course_ID = c.courseID 
        JOIN majortable m ON s.majorID = m.majorID 
        ORDER BY s.dateCreated DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="container">
        <h2>Manage Students</h2>
        <a href="dashboard.php">Back to Dashboard</a>
        <a href="add_student.php" class="btn">Add Student</a>
        <table border="1">
            <thead>
                <tr>
                    <th>Student No</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Course</th>
                    <th>Major</th>
                    <th>Contact No</th>
                    <th>Added By</th>
                    <th>Edited By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['studentNo']); ?></td>
                    <td><?php echo htmlspecialchars($row['firstName']); ?></td>
                    <td><?php echo htmlspecialchars($row['lastName']); ?></td>
                    <td><?php echo htmlspecialchars($row['courseName']); ?></td>
                    <td><?php echo htmlspecialchars($row['majorName']); ?></td>
                    <td><?php echo htmlspecialchars($row['contactNo']); ?></td>
                    <td><?php echo isset($row['added_by']) ? htmlspecialchars($row['added_by']) : 'N/A'; ?></td>
                    <td><?php echo isset($row['edited_by']) ? htmlspecialchars($row['edited_by']) : 'N/A'; ?></td>

                    <td>
                        <a href="edit_student.php?id=<?php echo $row['studentID']; ?>">Edit</a> |
                        <a href="delete_student.php?id=<?php echo $row['studentID']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

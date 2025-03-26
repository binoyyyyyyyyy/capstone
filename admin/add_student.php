<?php
session_start();
require_once '../config/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch courses and majors
$courses = $conn->query("SELECT courseID, courseName FROM coursetable ORDER BY courseName ASC");
$majors = $conn->query("SELECT majorID, majorName FROM majortable ORDER BY majorName ASC");

// Handle adding a new student
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $studentNo = trim($_POST['studentNo']);
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $middlename = trim($_POST['middlename']);
    $birthDate = $_POST['birthDate'];
    $course_ID = $_POST['course_ID'];
    $majorID = $_POST['majorID'];
    $contactNo = trim($_POST['contactNo']);
    $addedBy = $_SESSION['user_id'];

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO studentInformation (studentNo, firstname, lastname, middlename, birthDate, course_ID, majorID, contactNo, added_By, dateCreated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssssiisi", $studentNo, $firstname, $lastname, $middlename, $birthDate, $course_ID, $majorID, $contactNo, $addedBy);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Student added successfully!";
    } else {
        $_SESSION['error'] = "Failed to add student.";
    }
    
    $stmt->close();
    header("Location: manage_students.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <a href="dashboard.php" class="btn btn-secondary mb-3">Back to Dashboard</a>
        <h2 class="text-center">Add Student</h2>
        <div class="card p-4">
            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label">Student No:</label>
                    <input type="text" name="studentNo" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">First Name:</label>
                    <input type="text" name="firstname" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Last Name:</label>
                    <input type="text" name="lastname" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Middle Name:</label>
                    <input type="text" name="middlename" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Birth Date:</label>
                    <input type="date" name="birthDate" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Course:</label>
                    <select name="course_ID" class="form-select" required>
                        <option value="">Select Course</option>
                        <?php while ($course = $courses->fetch_assoc()): ?>
                            <option value="<?php echo $course['courseID']; ?>"><?php echo htmlspecialchars($course['courseName']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Major:</label>
                    <select name="majorID" class="form-select" required>
                        <option value="">Select Major</option>
                        <?php while ($major = $majors->fetch_assoc()): ?>
                            <option value="<?php echo $major['majorID']; ?>"><?php echo htmlspecialchars($major['majorName']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Contact No:</label>
                    <input type="text" name="contactNo" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Add Student</button>
            </form>
        </div>
    </div>
</body>
</html>
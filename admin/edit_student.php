<?php
session_start();
require_once '../config/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if student ID is provided
if (!isset($_GET['id'])) {
    header("Location: manage_students.php");
    exit();
}

$studentID = $_GET['id'];

// Fetch student details
$stmt = $conn->prepare("SELECT * FROM studentInformation WHERE studentID = ?");
$stmt->bind_param("i", $studentID);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    $_SESSION['error'] = "Student not found.";
    header("Location: manage_students.php");
    exit();
}

// Fetch courses and majors
$courses = $conn->query("SELECT courseID, courseName FROM coursetable ORDER BY courseName ASC");
$majors = $conn->query("SELECT majorID, majorName FROM majortable ORDER BY majorName ASC");

// Handle update request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $studentNo = trim($_POST['studentNo']);
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $middlename = trim($_POST['middlename']);
    $birthDate = $_POST['birthDate'];
    $course_ID = $_POST['course_ID'];
    $majorID = $_POST['majorID'];
    $contactNo = trim($_POST['contactNo']);
    $editedBy = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE studentInformation SET studentNo=?, firstname=?, lastname=?, middlename=?, birthDate=?, course_ID=?, majorID=?, contactNo=?, edited_By=? WHERE studentID=?");
    $stmt->bind_param("sssssiisii", $studentNo, $firstname, $lastname, $middlename, $birthDate, $course_ID, $majorID, $contactNo, $editedBy, $studentID);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Student updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update student.";
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
    <title>Edit Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Edit Student</h2>

        <!-- Display Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <a href="dashboard.php" class="btn btn-secondary mb-3">Back to Dashboard</a>

        <form action="" method="POST" class="border p-4 rounded shadow-sm bg-light">
            <div class="mb-3">
                <label class="form-label">Student No:</label>
                <input type="text" class="form-control" name="studentNo" value="<?php echo htmlspecialchars($student['studentNo']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">First Name:</label>
                <input type="text" class="form-control" name="firstname" value="<?php echo htmlspecialchars($student['firstName']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Last Name:</label>
                <input type="text" class="form-control" name="lastname" value="<?php echo htmlspecialchars($student['lastName']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Middle Name:</label>
                <input type="text" class="form-control" name="middlename" value="<?php echo htmlspecialchars($student['middleName']); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Birth Date:</label>
                <input type="date" class="form-control" name="birthDate" value="<?php echo htmlspecialchars($student['birthDate']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Course:</label>
                <select class="form-select" name="course_ID" required>
                    <option value="">Select Course</option>
                    <?php while ($course = $courses->fetch_assoc()): ?>
                        <option value="<?php echo $course['courseID']; ?>" <?php echo ($student['course_ID'] == $course['courseID']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['courseName']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Major:</label>
                <select class="form-select" name="majorID" required>
                    <option value="">Select Major</option>
                    <?php while ($major = $majors->fetch_assoc()): ?>
                        <option value="<?php echo $major['majorID']; ?>" <?php echo ($student['majorID'] == $major['majorID']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($major['majorName']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Contact No:</label>
                <input type="text" class="form-control" name="contactNo" value="<?php echo htmlspecialchars($student['contactNo']); ?>" required>
            </div>

            <button type="submit" class="btn btn-primary">Update Student</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

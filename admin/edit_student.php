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
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="container">
    <a href="dashboard.php">Back to Dashboard</a>
        <h2>Edit Student</h2>
        <form action="" method="POST">
            <label>Student No:</label>
            <input type="text" name="studentNo" value="<?php echo htmlspecialchars($student['studentNo']); ?>" required>
            
            <label>First Name:</label>
            <input type="text" name="firstname" value="<?php echo htmlspecialchars($student['firstName']); ?>" required>
            
            <label>Last Name:</label>
            <input type="text" name="lastname" value="<?php echo htmlspecialchars($student['lastName']); ?>" required>
            
            <label>Middle Name:</label>
            <input type="text" name="middlename" value="<?php echo htmlspecialchars($student['middleName']); ?>">
            
            <label>Birth Date:</label>
            <input type="date" name="birthDate" value="<?php echo htmlspecialchars($student['birthDate']); ?>" required>
            
            <label>Course:</label>
            <select name="course_ID" required>
                <option value="">Select Course</option>
                <?php while ($course = $courses->fetch_assoc()): ?>
                    <option value="<?php echo $course['courseID']; ?>" <?php echo ($student['course_ID'] == $course['courseID']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($course['courseName']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            
            <label>Major:</label>
            <select name="majorID" required>
                <option value="">Select Major</option>
                <?php while ($major = $majors->fetch_assoc()): ?>
                    <option value="<?php echo $major['majorID']; ?>" <?php echo ($student['majorID'] == $major['majorID']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($major['majorName']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            
            <label>Contact No:</label>
            <input type="text" name="contactNo" value="<?php echo htmlspecialchars($student['contactNo']); ?>" required>
            
            <button type="submit">Update Student</button>
        </form>
    </div>
</body>
</html>
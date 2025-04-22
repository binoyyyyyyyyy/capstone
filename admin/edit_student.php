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

// Get currently logged-in user's full name
$loggedInUser = isset($_SESSION['first_name'], $_SESSION['last_name']) 
    ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name']
    : 'Unknown User';

// Fetch courses and majors
$courses = $conn->query("SELECT courseID, courseName FROM coursetable ORDER BY courseName ASC");
$majors = $conn->query("SELECT majorID, majorName FROM majortable ORDER BY majorName ASC");

// Define status and year options
$statusOptions = ['Regular', 'Irregular', 'Transferee', 'Returnee', 'Graduated'];
$yearOptions = ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year','alumni'];

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
    $studentStatus = $_POST['studentStatus'];
    $year = $_POST['yearLevel'];
    $editedBy = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE studentInformation SET studentNo=?, firstname=?, lastname=?, middlename=?, birthDate=?, course_ID=?, majorID=?, contactNo=?, studentStatus=?, yearLevel=?, edited_By=? WHERE studentID=?");
    $stmt->bind_param("sssssiissssi", $studentNo, $firstname, $lastname, $middlename, $birthDate, $course_ID, $majorID, $contactNo, $studentStatus, $year, $loggedInUser, $studentID);
    
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
    <title>Edit Student | Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .form-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        .form-header {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        .form-control, .form-select {
            padding: 10px 15px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .btn-submit {
            padding: 10px 25px;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
        }
        .back-btn {
            transition: all 0.3s;
        }
        .back-btn:hover {
            transform: translateX(-3px);
        }
        .alert {
            border-radius: 6px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="form-container">
                    <div class="form-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="mb-0">
                                <i class="bi bi-person-gear me-2 text-primary"></i>Edit Student
                            </h2>
                            <a href="manage_students.php" class="btn btn-outline-secondary back-btn">
                                <i class="bi bi-arrow-left me-1"></i> Back to Students
                            </a>
                        </div>
                    </div>

                    <!-- Display Messages -->
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-success d-flex align-items-center">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <div><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Student Number</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-person-vcard"></i>
                                    </span>
                                    <input type="text" class="form-control" name="studentNo" 
                                           value="<?php echo htmlspecialchars($student['studentNo']); ?>" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Birth Date</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-calendar"></i>
                                    </span>
                                    <input type="date" class="form-control" name="birthDate" 
                                           value="<?php echo htmlspecialchars($student['birthDate']); ?>" required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">First Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text" class="form-control" name="firstname" 
                                           value="<?php echo htmlspecialchars($student['firstName']); ?>" required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Last Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text" class="form-control" name="lastname" 
                                           value="<?php echo htmlspecialchars($student['lastName']); ?>" required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Middle Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text" class="form-control" name="middlename" 
                                           value="<?php echo htmlspecialchars($student['middleName']); ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Course</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-book"></i>
                                    </span>
                                    <select class="form-select" name="course_ID" required>
                                        <option value="">Select Course</option>
                                        <?php while ($course = $courses->fetch_assoc()): ?>
                                            <option value="<?php echo $course['courseID']; ?>" 
                                                <?php echo ($student['course_ID'] == $course['courseID']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($course['courseName']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Major</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-mortarboard"></i>
                                    </span>
                                    <select class="form-select" name="majorID" required>
                                        <option value="">Select Major</option>
                                        <?php while ($major = $majors->fetch_assoc()): ?>
                                            <option value="<?php echo $major['majorID']; ?>" 
                                                <?php echo ($student['majorID'] == $major['majorID']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($major['majorName']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Student Status</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-info-circle"></i>
                                    </span>
                                    <select class="form-select" name="studentStatus" required>
                                        <option value="">Select Status</option>
                                        <?php foreach ($statusOptions as $status): ?>
                                            <option value="<?php echo $status; ?>" 
                                                <?php echo ($student['studentStatus'] == $status) ? 'selected' : ''; ?>>
                                                <?php echo $status; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Year Level</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-123"></i>
                                    </span>
                                    <select class="form-select" name="yearLevel" required>
                                        <option value="">Select Year Level</option>
                                        <?php foreach ($yearOptions as $year): ?>
                                            <option value="<?php echo $year; ?>" 
                                                <?php echo ($student['yearLevel'] == $year) ? 'selected' : ''; ?>>
                                                <?php echo $year; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Contact Number</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-telephone"></i>
                                    </span>
                                    <input type="text" class="form-control" name="contactNo" 
                                           value="<?php echo htmlspecialchars($student['contactNo']); ?>" required>
                                </div>
                            </div>

                            <div class="col-12 mt-4">
                                <div class="d-flex justify-content-between">
                                    <a href="manage_students.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle me-1"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-submit">
                                        <i class="bi bi-save me-1"></i> Update Student
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enable form validation
        (function () {
            'use strict'
            
            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            var forms = document.querySelectorAll('form')
            
            // Loop over them and prevent submission
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html>
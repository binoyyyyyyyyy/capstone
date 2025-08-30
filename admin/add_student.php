<?php
session_start();
require_once '../config/config.php';
include '../includes/sidevar.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get currently logged-in user's full name
$loggedInUser = isset($_SESSION['first_name'], $_SESSION['last_name']) 
    ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name']
    : 'Unknown User';

// Check if user status is pending - redirect to pending dashboard
if (isset($_SESSION['user_status']) && $_SESSION['user_status'] === 'pending') {
    header("Location: pending_user_dashboard.php");
    exit();
}

// Fetch courses and majors
$courses = $conn->query("SELECT courseID, courseName FROM coursetable ORDER BY courseName ASC");
// Majors will be fetched dynamically by course via AJAX

// Define status and year options
$statusOptions = ['Regular', 'Irregular', 'Transferee', 'Returnee', 'Graduated'];
$yearOptions = ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year','Alumni'];

// Handle manual student addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['excelFile'])) {
    // Validate required fields
    $requiredFields = ['studentNo', 'birthDate', 'firstname', 'lastname', 'course_ID', 'majorID', 'studentStatus', 'yearLevel', 'contactNo'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $missingFields[] = ucfirst(str_replace('_', ' ', $field));
        }
    }
    
    if (!empty($missingFields)) {
        $_SESSION['error'] = "Missing required fields: " . implode(', ', $missingFields);
        header("Location: add_student.php");
        exit();
    }
    
    $studentNo = trim($_POST['studentNo']);
    $birthDate = $_POST['birthDate'];
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $middlename = trim($_POST['middlename'] ?? '');
    $course_ID = $_POST['course_ID'];
    $majorID = $_POST['majorID'];
    $studentStatus = $_POST['studentStatus'];
    $yearLevel = $_POST['yearLevel'];
    $contactNo = trim($_POST['contactNo']);
    
    // Check if student already exists
    $checkStmt = $conn->prepare("SELECT studentID FROM StudentInformation WHERE studentNo = ? AND dateDeleted IS NULL");
    $checkStmt->bind_param("s", $studentNo);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        $_SESSION['error'] = "Student number $studentNo already exists";
        header("Location: add_student.php");
        exit();
    }
    $checkStmt->close();
    
    // Insert student
    $insertStmt = $conn->prepare("INSERT INTO StudentInformation 
        (studentNo, birthDate, firstname, lastname, middlename, course_ID, majorID, studentStatus, yearLevel, contactNo, added_By, dateCreated) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $insertStmt->bind_param("sssssiissss", $studentNo, $birthDate, $firstname, $lastname, $middlename, $course_ID, $majorID, $studentStatus, $yearLevel, $contactNo, $loggedInUser);
    
    if ($insertStmt->execute()) {
        $_SESSION['message'] = "Student $firstname $lastname added successfully!";
        header("Location: add_student.php");
        exit();
    } else {
        $_SESSION['error'] = "Error adding student: " . $conn->error;
        header("Location: add_student.php");
        exit();
    }
    $insertStmt->close();
}

// Handle Excel file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excelFile'])) {
    require_once '../vendor/autoload.php';
    
    try {
        $inputFileName = $_FILES['excelFile']['tmp_name'];
        $fileExtension = strtolower(pathinfo($_FILES['excelFile']['name'], PATHINFO_EXTENSION));
        
        if ($fileExtension === 'csv') {
            // Handle CSV file
            $rows = array_map('str_getcsv', file($inputFileName));
        } else {
            // Handle Excel file
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
        }
        
        // Remove header row
        $headers = array_shift($rows);
        
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        
        foreach ($rows as $index => $row) {
            if (empty(array_filter($row))) continue; // Skip empty rows
            
            try {
                // Map Excel columns to database fields
                $studentNo = trim($row[0] ?? '');
                $birthDate = trim($row[1] ?? '');
                $firstname = trim($row[2] ?? '');
                $lastname = trim($row[3] ?? '');
                $middlename = trim($row[4] ?? '');
                $courseName = trim($row[5] ?? '');
                $majorName = trim($row[6] ?? '');
                $studentStatus = trim($row[7] ?? '');
                $yearLevel = trim($row[8] ?? '');
                $contactNo = trim($row[9] ?? '');
                // Added By column (ignored in favor of session user)
                $addedByFromFile = trim($row[10] ?? '');
                
                // Validate required fields
                if (empty($studentNo) || empty($firstname) || empty($lastname)) {
                    $errors[] = "Row " . ($index + 2) . ": Missing required fields (Student No, First Name, or Last Name)";
                    $errorCount++;
                    continue;
                }
                
                // Check if student already exists
                $checkStmt = $conn->prepare("SELECT studentID FROM StudentInformation WHERE studentNo = ? AND dateDeleted IS NULL");
                $checkStmt->bind_param("s", $studentNo);
                $checkStmt->execute();
                if ($checkStmt->get_result()->num_rows > 0) {
                    $errors[] = "Row " . ($index + 2) . ": Student number $studentNo already exists";
                    $errorCount++;
                    $checkStmt->close();
                    continue;
                }
                $checkStmt->close();
                
                // Get course ID
                $courseStmt = $conn->prepare("SELECT courseID FROM coursetable WHERE courseName = ?");
                $courseStmt->bind_param("s", $courseName);
                $courseStmt->execute();
                $courseResult = $courseStmt->get_result();
                if ($courseResult->num_rows === 0) {
                    $errors[] = "Row " . ($index + 2) . ": Course '$courseName' not found";
                    $errorCount++;
                    $courseStmt->close();
                    continue;
                }
                $courseID = $courseResult->fetch_assoc()['courseID'];
                $courseStmt->close();
                
                // Get major ID - handle both major names and codes
                $majorStmt = $conn->prepare("SELECT majorID FROM majortable WHERE majorName = ? OR majorCode = ?");
                $majorStmt->bind_param("ss", $majorName, $majorName);
                $majorStmt->execute();
                $majorResult = $majorStmt->get_result();
                if ($majorResult->num_rows === 0) {
                    $errors[] = "Row " . ($index + 2) . ": Major '$majorName' not found";
                    $errorCount++;
                    $majorStmt->close();
                    continue;
                }
                $majorID = $majorResult->fetch_assoc()['majorID'];
                $majorStmt->close();
                
                // Insert student (added_By is set from logged-in user)
                $insertStmt = $conn->prepare("INSERT INTO StudentInformation 
                    (studentNo, birthDate, firstname, lastname, middlename, course_ID, majorID, studentStatus, yearLevel, contactNo, added_By, dateCreated) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $insertStmt->bind_param("sssssiissss", $studentNo, $birthDate, $firstname, $lastname, $middlename, $courseID, $majorID, $studentStatus, $yearLevel, $contactNo, $loggedInUser);
                
                if ($insertStmt->execute()) {
                    $successCount++;
                } else {
                    $errors[] = "Row " . ($index + 2) . ": Database error - " . $conn->error;
                    $errorCount++;
                }
                $insertStmt->close();
                
            } catch (Exception $e) {
                $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                $errorCount++;
            }
        }
        
        if ($successCount > 0) {
            $_SESSION['message'] = "Successfully imported $successCount students.";
            if ($errorCount > 0) {
                $_SESSION['message'] .= " $errorCount rows had errors.";
            }
        } else {
            $_SESSION['error'] = "No students were imported. $errorCount rows had errors.";
        }
        
        if (!empty($errors)) {
            $_SESSION['import_errors'] = $errors;
        }
        
        header("Location: add_student.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error processing Excel file: " . $e->getMessage();
        header("Location: add_student.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student | Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
         :root {
            --neust-blue: #0056b3;
            --neust-yellow: #FFD700;
            --sidebar-width: 280px;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--neust-blue), #003366);
            color: white;
            position: fixed;
            height: 100vh;
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        
        .sidebar-brand {
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-brand img {
            height: 40px;
            margin-right: 10px;
        }
        
        .sidebar-brand h4 {
            font-weight: 600;
            margin-bottom: 0;
            font-size: 1.1rem;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.5rem;
            margin: 0.25rem 0;
            border-radius: 0;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: 100vh;
        }
        
        .topbar {
            background-color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
        }
        
        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background-color: var(--neust-blue);
            color: white;
            border-bottom: none;
            padding: 15px;
            font-weight: 500;
        }
        
        .table tbody tr {
            transition: all 0.2s;
        }
        
        .table tbody tr:hover {
            background-color: rgba(0, 86, 179, 0.05);
        }
        
        .action-btn {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            margin: 0 3px;
        }
        
        .page-title {
            color: var(--neust-blue);
            font-weight: 600;
            margin-bottom: 0;
        }
        
        .empty-state {
            padding: 3rem;
            text-align: center;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .badge {
            padding: 0.5em 0.75em;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        /* Mobile overrides */
        @media (max-width: 768px) {
            .form-container {
                margin-left: 0;
                border-radius: 10px;
            }
            .form-header {
                border-radius: 10px 10px 0 0;
            }
        }
        .form-container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
            margin-left: 0;
            margin-top: 20px;
        }
        
        .form-header {
            background: linear-gradient(135deg, var(--neust-blue), #007bff);
            padding: 2rem 1.5rem;
            color: white;
            text-align: center;
            border-radius: 15px 15px 0 0;
        }
        
        .form-header h2 {
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 0;
        }
        
        .form-content {
            padding: 1.5rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .form-label.required::after {
            content: " *";
            color: #e74c3c;
        }
        
        .form-control, .form-select {
            padding: 8px 12px;
            border-radius: 6px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            height: auto;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--neust-blue);
            box-shadow: 0 0 0 0.15rem rgba(0, 86, 179, 0.15);
            transform: none;
        }
        
        .input-group-text {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 2px solid #e9ecef;
            border-right: none;
            color: #6c757d;
            font-weight: 500;
            padding: 8px 12px;
            font-size: 0.9rem;
        }
        
        .input-group:focus-within .input-group-text {
            border-color: var(--neust-blue);
            background: linear-gradient(135deg, #e3f2fd, #f0f8ff);
        }
        
        .btn-submit {
            padding: 10px 25px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, var(--neust-blue), #007bff);
            border: none;
        }
        
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0, 86, 179, 0.25);
        }
        
        .back-btn {
            transition: all 0.3s ease;
            border-radius: 6px;
            padding: 8px 16px;
            font-size: 0.9rem;
        }
        
        .back-btn:hover {
            transform: translateX(-2px);
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
        }
        
        .excel-upload {
            border: 3px dashed #dee2e6;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            background: linear-gradient(135deg, #f8f9fa, #ffffff);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .excel-upload:hover {
            border-color: var(--neust-blue);
            background: linear-gradient(135deg, #f0f8ff, #ffffff);
            transform: translateY(-1px);
        }
        
        .excel-upload.dragover {
            border-color: var(--neust-blue);
            background: linear-gradient(135deg, #e7f3ff, #ffffff);
            transform: scale(1.01);
        }
        
        .excel-upload i {
            color: #28a745;
            margin-bottom: 0.75rem;
            font-size: 2.5rem;
        }
        
        .excel-upload h4 {
            color: #2c3e50;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .template-download {
            color: var(--neust-blue);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 16px;
            background: rgba(0, 86, 179, 0.1);
            transition: all 0.3s ease;
        }
        
        .template-download:hover {
            color: white;
            background: var(--neust-blue);
            text-decoration: none;
        }
        
        .error-list {
            max-height: 200px;
            overflow-y: auto;
            font-size: 0.8rem;
            background: #fff5f5;
            padding: 0.75rem;
            border-radius: 6px;
            border-left: 3px solid #e74c3c;
        }
        
        .section-divider {
            border: none;
            height: 1px;
            background: linear-gradient(90deg, transparent, #dee2e6, transparent);
            margin: 1.5rem 0;
        }
        
        .form-section {
            background: #f8f9fa;
            padding: 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.25rem;
        }
        
        .form-section h4 {
            color: var(--neust-blue);
            font-weight: 600;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            font-size: 1.1rem;
        }
        
        .form-section h4 i {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }
        
        .btn-group-custom {
            display: flex;
            gap: 0.75rem;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
        }
        
        .btn-cancel {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
            background: #6c757d;
            border: none;
            color: white;
            font-size: 0.9rem;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-1px);
            color: white;
        }
        
        @media (max-width: 768px) {
            .form-container {
                margin: 10px;
                border-radius: 10px;
            }
            
            .form-header {
                padding: 1.25rem 1rem;
                border-radius: 10px 10px 0 0;
            }
            
            .form-content {
                padding: 1.25rem;
            }
            
            .excel-upload {
                padding: 1.25rem;
            }
            
            .btn-group-custom {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .btn-group-custom .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body class="bg-light">
    <div class="main-content">
        <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="form-container">
                    <div class="form-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="mb-0">
                                <i class="bi bi-person-plus me-2"></i>Add New Student
                            </h2>
                            <a href="manage_students.php" class="btn btn-outline-light back-btn">
                                <i class="bi bi-arrow-left me-1"></i> Back to Students
                            </a>
                        </div>
                    </div>

                    <div class="form-content">
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

                        <!-- Excel Import Section -->
                        <div class="form-section">
                            <h4>
                                <i class="bi bi-file-earmark-excel"></i>Bulk Import Students
                            </h4>
                            <div class="excel-upload" id="excelUpload">
                                <i class="bi bi-file-earmark-excel" style="font-size: 3rem;"></i>
                                <h4 class="mt-3 mb-3">Upload Excel File</h4>
                                <p class="text-muted mb-3">Upload an Excel file to import multiple students at once</p>
                                
                                <form action="add_student.php" method="POST" enctype="multipart/form-data" id="excelForm">
                                    <div class="mb-3">
                                        <input type="file" class="form-control" name="excelFile" id="excelFile" accept=".xlsx,.xls,.csv" required>
                                        <small class="text-muted">Supported formats: Excel (.xlsx, .xls) and CSV (.csv)</small>
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-upload me-1"></i> Import Students
                                    </button>
                                </form>
                                
                                <div class="mt-3">
                                    <a href="#" class="template-download" onclick="downloadTemplate()">
                                        <i class="bi bi-download me-1"></i> Download Excel Template
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Import Errors -->
                        <?php if (isset($_SESSION['import_errors'])): ?>
                            <div class="alert alert-warning">
                                <h6><i class="bi bi-exclamation-triangle me-2"></i>Import Errors:</h6>
                                <div class="error-list">
                                    <?php foreach ($_SESSION['import_errors'] as $error): ?>
                                        <div class="text-danger">• <?php echo htmlspecialchars($error); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php unset($_SESSION['import_errors']); ?>
                        <?php endif; ?>

                        <hr class="section-divider">

                        <!-- Manual Entry Form -->
                        <div class="form-section">
                            <h4>
                                <i class="bi bi-pencil-square"></i>Manual Entry
                            </h4>

                    <form action="add_student.php" method="POST" id="addStudentForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">Student Number</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-person-vcard"></i>
                                    </span>
                                    <input type="text" class="form-control" name="studentNo" required placeholder="Enter student number">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">Birth Date</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-calendar"></i>
                                    </span>
                                    <input type="date" class="form-control" name="birthDate" required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">First Name</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text" class="form-control" name="firstname" required placeholder="Enter first name">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">Last Name</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text" class="form-control" name="lastname" required placeholder="Enter last name">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Middle Name</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text" class="form-control" name="middlename" placeholder="Enter middle name (optional)">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">Course</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-book"></i>
                                    </span>
                                    <select class="form-select" name="course_ID" required>
                                        <option value="">Select Course</option>
                                        <?php while ($course = $courses->fetch_assoc()): ?>
                                            <option value="<?php echo $course['courseID']; ?>">
                                                <?php echo htmlspecialchars($course['courseName']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">Major</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-mortarboard"></i>
                                    </span>
                                    <select class="form-select" name="majorID" id="majorSelect" required disabled>
                                        <option value="">Select Major</option>
                                    </select>
                                </div>
                                <small class="text-muted">Select a course first to see available majors</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">Student Status</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-info-circle"></i>
                                    </span>
                                    <select class="form-select" name="studentStatus" required>
                                        <option value="">Select Status</option>
                                        <?php foreach ($statusOptions as $status): ?>
                                            <option value="<?php echo $status; ?>">
                                                <?php echo $status; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">Year Level</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-123"></i>
                                    </span>
                                    <select class="form-select" name="yearLevel" required>
                                        <option value="">Select Year Level</option>
                                        <?php foreach ($yearOptions as $year): ?>
                                            <option value="<?php echo $year; ?>">
                                                <?php echo $year; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label required">Contact Number</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-telephone"></i>
                                    </span>
                                    <input type="text" class="form-control" name="contactNo" required placeholder="Enter contact number">
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="btn-group-custom">
                                    <a href="manage_students.php" class="btn btn-cancel">
                                        <i class="bi bi-x-circle me-1"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-submit">
                                        <i class="bi bi-save me-1"></i> Add Student
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                        </div>
                    </div>
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

        // Drag and drop functionality
        const excelUpload = document.getElementById('excelUpload');
        const excelFile = document.getElementById('excelFile');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            excelUpload.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            excelUpload.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            excelUpload.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            excelUpload.classList.add('dragover');
        }

        function unhighlight(e) {
            excelUpload.classList.remove('dragover');
        }

        excelUpload.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            excelFile.files = files;
        }

        // Inject current user for template default
        const addedByDefault = "<?php echo htmlspecialchars($loggedInUser); ?>";

        // Populate majors by selected course via API
        async function populateMajorsByCourse() {
            const courseSelect = document.querySelector('select[name="course_ID"]');
            const majorSelect = document.getElementById('majorSelect');
            const courseId = courseSelect.value;

            majorSelect.innerHTML = '<option value="">Select Major</option>';
            majorSelect.disabled = true;

            if (!courseId) {
                return;
            }

            try {
                const res = await fetch(`../api/majors_api.php?course_ID=${encodeURIComponent(courseId)}`);
                const data = await res.json();
                if (Array.isArray(data)) {
                    data.forEach(m => {
                        const opt = document.createElement('option');
                        opt.value = m.majorID;
                        opt.textContent = m.majorName;
                        majorSelect.appendChild(opt);
                    });
                    majorSelect.disabled = false;
                }
            } catch (e) {
                console.error('Failed to load majors', e);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const courseSelect = document.querySelector('select[name="course_ID"]');
            if (courseSelect) {
                courseSelect.addEventListener('change', populateMajorsByCourse);
            }
        });

        // Download template function
        function downloadTemplate() {
            const templateData = [
                ['Student Number', 'Birth Date', 'First Name', 'Last Name', 'Middle Name', 'Course', 'Major', 'Student Status', 'Year Level', 'Contact Number', 'Added By'],
                ['2021-0001', '2000-01-15', 'John', 'Doe', 'Smith', 'BSIT', 'Web System Technology', 'Regular', '1st Year', '09123456789', addedByDefault],
                ['2021-0002', '2000-05-20', 'Jane', 'Smith', 'Johnson', 'BSIT', 'DATABASE', 'Regular', '1st Year', '09123456790', addedByDefault],
                ['2021-0003', '2000-03-10', 'Mike', 'Johnson', 'Brown', 'BEED', 'General Education', 'Regular', '1st Year', '09123456791', addedByDefault],
                ['2021-0004', '2000-07-25', 'Sarah', 'Wilson', 'Davis', 'BSBA', 'Marketing Management', 'Regular', '1st Year', '09123456792', addedByDefault]
            ];

            let csvContent = "data:text/csv;charset=utf-8,";
            templateData.forEach(row => {
                csvContent += row.join(",") + "\r\n";
            });

            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "student_import_template.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>
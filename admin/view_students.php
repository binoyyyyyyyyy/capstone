<?php
session_start();
require_once '../config/config.php'; // Database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$role = $_SESSION['role_type'];

// Handle export functionality
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    require_once '../vendor/autoload.php';
    
    // Fetch all student records
    $stmt = $conn->prepare("SELECT 
        s.studentNo, s.birthDate, s.firstname, s.lastname, s.middlename, 
        c.courseName, m.majorName, s.studentStatus, s.yearLevel, s.contactNo, s.dateCreated
        FROM StudentInformation s
        LEFT JOIN coursetable c ON s.course_ID = c.courseID
        LEFT JOIN majortable m ON s.majorID = m.majorID
        WHERE s.dateDeleted IS NULL
        ORDER BY s.dateCreated DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Create new Spreadsheet object
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set headers
    $headers = [
        'Student Number', 'Birth Date', 'First Name', 'Last Name', 'Middle Name',
        'Course', 'Major', 'Student Status', 'Year Level', 'Contact Number', 'Date Created'
    ];
    
    foreach ($headers as $colIndex => $header) {
        $sheet->setCellValueByColumnAndRow($colIndex + 1, 1, $header);
    }
    
    // Add data
    $rowIndex = 2;
    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValueByColumnAndRow(1, $rowIndex, $row['studentNo']);
        $sheet->setCellValueByColumnAndRow(2, $rowIndex, $row['birthDate']);
        $sheet->setCellValueByColumnAndRow(3, $rowIndex, $row['firstname']);
        $sheet->setCellValueByColumnAndRow(4, $rowIndex, $row['lastname']);
        $sheet->setCellValueByColumnAndRow(5, $rowIndex, $row['middlename']);
        $sheet->setCellValueByColumnAndRow(6, $rowIndex, $row['courseName']);
        $sheet->setCellValueByColumnAndRow(7, $rowIndex, $row['majorName']);
        $sheet->setCellValueByColumnAndRow(8, $rowIndex, $row['studentStatus']);
        $sheet->setCellValueByColumnAndRow(9, $rowIndex, $row['yearLevel']);
        $sheet->setCellValueByColumnAndRow(10, $rowIndex, $row['contactNo']);
        $sheet->setCellValueByColumnAndRow(11, $rowIndex, $row['dateCreated']);
        $rowIndex++;
    }
    
    // Auto-size columns
    foreach (range(1, count($headers)) as $colIndex) {
        $sheet->getColumnDimensionByColumn($colIndex)->setAutoSize(true);
    }
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="students_export_' . date('Y-m-d_H-i-s') . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit();
}

// Fetch student records for display
$stmt = $conn->prepare("SELECT 
    s.studentID, s.studentNo, s.firstname, s.lastname, s.middlename, 
    c.courseName, m.majorName, s.studentStatus, s.yearLevel, s.contactNo, s.dateCreated
    FROM StudentInformation s
    LEFT JOIN coursetable c ON s.course_ID = c.courseID
    LEFT JOIN majortable m ON s.majorID = m.majorID
    WHERE s.dateDeleted IS NULL
    ORDER BY s.dateCreated DESC");
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Students | Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
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
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--neust-blue), #007bff);
            color: white;
            border-radius: 10px 10px 0 0 !important;
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
        
        .status-badge {
            padding: 0.4em 0.8em;
            font-size: 0.8rem;
            font-weight: 500;
            border-radius: 20px;
        }
        
        .status-regular { background-color: #28a745; color: white; }
        .status-irregular { background-color: #ffc107; color: #212529; }
        .status-transferee { background-color: #17a2b8; color: white; }
        .status-returnee { background-color: #6f42c1; color: white; }
        .status-graduated { background-color: #6c757d; color: white; }
        
        .year-badge {
            background-color: #e9ecef;
            color: #495057;
            padding: 0.3em 0.6em;
            font-size: 0.75rem;
            border-radius: 15px;
        }
        
        .btn-export {
            background-color: var(--neust-blue);
            border-color: var(--neust-blue);
            transition: all 0.3s;
        }
        
        .btn-export:hover {
            background-color: #004085;
            border-color: #004085;
            transform: translateY(-2px);
        }
        
        .back-btn {
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            transform: translateX(-3px);
        }
        
        .student-count {
            background: linear-gradient(135deg, var(--neust-blue), #007bff);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
        }
        
        .student-count h3 {
            margin-bottom: 0;
            font-weight: 600;
        }
        
        .student-count p {
            margin-bottom: 0;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">
                                <i class="bi bi-people-fill me-2"></i>Student Records
                            </h4>
                            <div>
                                <a href="add_student.php" class="btn btn-light me-2">
                                    <i class="bi bi-person-plus me-1"></i> Add Student
                                </a>
                                <a href="?export=excel" class="btn btn-light btn-export">
                                    <i class="bi bi-download me-1"></i> Export to Excel
                                </a>
                                <a href="dashboard.php" class="btn btn-outline-light back-btn ms-2">
                                    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body p-0">
                        <!-- Student Count -->
                        <div class="row m-3">
                            <div class="col-md-4">
                                <div class="student-count">
                                    <h3><?php echo $result->num_rows; ?></h3>
                                    <p>Total Students</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="student-count">
                                    <h3><?php 
                                        $regularCount = 0;
                                        $result->data_seek(0);
                                        while ($row = $result->fetch_assoc()) {
                                            if ($row['studentStatus'] === 'Regular') $regularCount++;
                                        }
                                        echo $regularCount;
                                    ?></h3>
                                    <p>Regular Students</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="student-count">
                                    <h3><?php 
                                        $recentCount = 0;
                                        $result->data_seek(0);
                                        $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
                                        while ($row = $result->fetch_assoc()) {
                                            if ($row['dateCreated'] >= $thirtyDaysAgo) $recentCount++;
                                        }
                                        echo $recentCount;
                                    ?></h3>
                                    <p>Added Last 30 Days</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student No.</th>
                                        <th>Name</th>
                                        <th>Course</th>
                                        <th>Major</th>
                                        <th>Status</th>
                                        <th>Year Level</th>
                                        <th>Contact</th>
                                        <th>Date Added</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $result->data_seek(0);
                                    while ($row = $result->fetch_assoc()): 
                                        $fullName = $row['firstname'] . ' ' . $row['lastname'];
                                        if (!empty($row['middlename'])) {
                                            $fullName .= ' ' . $row['middlename'];
                                        }
                                    ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo htmlspecialchars($row['studentNo']); ?></td>
                                        <td><?php echo htmlspecialchars($fullName); ?></td>
                                        <td><?php echo htmlspecialchars($row['courseName'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['majorName'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($row['studentStatus']); ?>">
                                                <?php echo htmlspecialchars($row['studentStatus']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="year-badge">
                                                <?php echo htmlspecialchars($row['yearLevel']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['contactNo']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['dateCreated'])); ?></td>
                                        <td>
                                            <a href="edit_student.php?id=<?php echo $row['studentID']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="view_student_details.php?id=<?php echo $row['studentID']; ?>" 
                                               class="btn btn-sm btn-outline-info" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($result->num_rows === 0): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                            <h5 class="text-muted mt-3">No students found</h5>
                            <p class="text-muted">Start by adding some students to the system.</p>
                            <a href="add_student.php" class="btn btn-primary">
                                <i class="bi bi-person-plus me-1"></i> Add First Student
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add any additional JavaScript functionality here
        document.addEventListener('DOMContentLoaded', function() {
            // Enable tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>
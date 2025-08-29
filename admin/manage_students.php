<?php
session_start();
require_once '../config/config.php';
include '../includes/sidevar.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$role = $_SESSION['role_type'];
$addedBy = isset($_SESSION['first_name']) && isset($_SESSION['last_name'])  
    ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] 
    : 'Unknown';
    
$sql = "SELECT s.*, c.courseName, m.majorName 
        FROM StudentInformation s 
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
    <title>Manage Students | NEUST Registrar</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --neust-blue: #0056b3;
            --neust-yellow: #FFD700;
            --sidebar-width: 280px;
            --status-regular: #28a745;
            --status-irregular: #fd7e14;
            --status-transferee: #17a2b8;
            --status-returnee: #6f42c1;
            --status-graduated: #6c757d;
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
        
        .status-badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-radius: 50rem;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
        }
        
        .status-regular {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--status-regular);
        }
        
        .status-irregular {
            background-color: rgba(253, 126, 20, 0.1);
            color: var(--status-irregular);
        }
        
        .status-transferee {
            background-color: rgba(23, 162, 184, 0.1);
            color: var(--status-transferee);
        }
        
        .status-returnee {
            background-color: rgba(111, 66, 193, 0.1);
            color: var(--status-returnee);
        }
        
        .status-graduated {
            background-color: rgba(108, 117, 125, 0.1);
            color: var(--status-graduated);
        }
        
        .status-icon {
            margin-right: 5px;
            font-size: 0.9em;
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
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
                padding: 10px;
            }
            
            .topbar {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 10px;
            }
            
            .d-flex.justify-content-between > div {
                width: 100%;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 5px;
            }
            
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .table th, .table td {
                padding: 8px 4px;
                white-space: nowrap;
            }
            
            .action-btn {
                width: 28px;
                height: 28px;
                margin: 2px;
            }
            
            .status-badge {
                font-size: 0.7rem;
                padding: 0.3em 0.5em;
            }
            
            .mb-4.row.g-2 {
                margin-bottom: 1rem !important;
            }
            
            .col-md-3 {
                margin-bottom: 10px;
            }
            
            .input-group {
                flex-direction: column;
            }
            
            .input-group .form-control {
                border-radius: 0.375rem !important;
                margin-bottom: 10px;
            }
            
            .input-group .btn {
                border-radius: 0.375rem !important;
                width: 100%;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 5px;
            }
            
            .topbar {
                padding: 10px 15px;
            }
            
            .page-title {
                font-size: 1.1rem;
            }
            
            .table-responsive {
                font-size: 0.8rem;
            }
            
            .table th, .table td {
                padding: 6px 2px;
            }
            
            .action-btn {
                width: 26px;
                height: 26px;
                margin: 1px;
            }
            
            .status-badge {
                font-size: 0.65rem;
                padding: 0.25em 0.4em;
            }
            
            .mb-4.row.g-2 {
                margin-bottom: 0.5rem !important;
            }
        }
        .button{
            color:red;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="topbar">
            <h4 class="page-title">Manage Student Records</h4>
            <div class="user-profile">
                <img src="../assets/avatar.jpg" alt="User Avatar">
                <div>
                    <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['user_email']); ?></div>
                    <small class="text-muted"><?php echo htmlspecialchars(ucfirst($role)); ?></small>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between mb-4">
            <div>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div>
                <a href="add_student.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add New Student
                </a>
            </div>
        </div>
        <div class="mb-4 row g-2 align-items-end">
            <div class="col-md-3">
                <label for="filterStatus" class="form-label">Status</label>
                <select id="filterStatus" class="form-select">
                    <option value="">All</option>
                    <option value="Regular">Regular</option>
                    <option value="Irregular">Irregular</option>
                    <option value="Transferee">Transferee</option>
                    <option value="Returnee">Returnee</option>
                    <option value="Graduated">Graduated</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="filterYear" class="form-label">Year Level</label>
                <select id="filterYear" class="form-select">
                    <option value="">All</option>
                    <option value="1st Year">1st Year</option>
                    <option value="2nd Year">2nd Year</option>
                    <option value="3rd Year">3rd Year</option>
                    <option value="4th Year">4th Year</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="filterCourse" class="form-label">Course</label>
                <select id="filterCourse" class="form-select">
                    <option value="">All</option>
                    <?php
                    // Populate course dropdown
                    $courseQuery = $conn->query("SELECT courseName FROM coursetable");
                    while ($course = $courseQuery->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($course['courseName']) . '">' . htmlspecialchars($course['courseName']) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="searchInput" class="form-label">Search Student No / Name</label>
                <div class="input-group">
                    <input type="text" id="searchInput" class="form-control" placeholder="e.g. 2021-12345 or Juan Dela Cruz">
                    <button class="btn btn-outline-secondary" type="button" onclick="resetFilters()">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </button>
                </div>
            </div>
        </div>










        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                    <table class="table table-hover align-middle" style="min-width: 900px;">
                        <thead>
                            <tr>
                                <th style="min-width: 100px;">Student No</th>
                                <th style="min-width: 120px;">Name</th>
                                <th style="min-width: 80px;">Status</th>
                                <th style="min-width: 80px;">Year</th>
                                <th style="min-width: 120px;">Course</th>
                                <th style="min-width: 120px;">Major</th>
                                <th style="min-width: 100px;">Contact</th>
                                <th style="min-width: 100px;">Added By</th>
                                <th style="min-width: 100px;">Edited By</th>
                                <th style="min-width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): 
                                    $statusClass = '';
                                    $statusIcon = '';
                                    switch(strtolower($row['studentStatus'])) {
                                        case 'regular':
                                            $statusClass = 'status-regular';
                                            $statusIcon = 'bi-check-circle';
                                            break;
                                        case 'irregular':
                                            $statusClass = 'status-irregular';
                                            $statusIcon = 'bi-exclamation-triangle';
                                            break;
                                        case 'transferee':
                                            $statusClass = 'status-transferee';
                                            $statusIcon = 'bi-arrow-left-right';
                                            break;
                                        case 'returnee':
                                            $statusClass = 'status-returnee';
                                            $statusIcon = 'bi-arrow-return-right';
                                            break;
                                        case 'graduated':
                                            $statusClass = 'status-graduated';
                                            $statusIcon = 'bi-award';
                                            break;
                                        default:
                                            $statusClass = 'status-regular';
                                            $statusIcon = 'bi-person';
                                    }
                                ?>
                                <tr>
                                    <td class="fw-bold"><?php echo htmlspecialchars($row['studentNo']); ?></td>
                                    <td><?php echo htmlspecialchars($row['firstName'] . ' ' . $row['lastName']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <i class="bi <?php echo $statusIcon; ?> status-icon"></i>
                                            <?php echo htmlspecialchars($row['studentStatus']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['yearLevel']); ?></td>
                                    <td><?php echo htmlspecialchars($row['courseName']); ?></td>
                                    <td><?php echo htmlspecialchars($row['majorName']); ?></td>
                                    <td><?php echo htmlspecialchars($row['contactNo']); ?></td>
                                    <td><?php echo isset($row['added_by']) ? htmlspecialchars($row['added_by']) : '<span class="text-muted">N/A</span>'; ?></td>
                                    <td><?php echo isset($row['edited_by']) ? htmlspecialchars($row['edited_by']) : '<span class="text-muted">N/A</span>'; ?></td>
                                    <td>
                                        <a href="edit_student.php?id=<?php echo $row['studentID']; ?>" 
                                           class="btn btn-sm btn-outline-warning action-btn" 
                                           title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-danger action-btn delete-student-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#confirmDeleteModal" 
                                                data-student-id="<?php echo $row['studentID']; ?>"
                                                title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="empty-state">
                                        <i class="bi bi-people"></i>
                                        <h5>No Student Records Found</h5>
                                        <p>Add your first student by clicking the "Add New Student" button</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm Delete Modal -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="../backend/delete_student.php" id="deleteStudentForm">
                <input type="hidden" name="studentID" id="modalStudentIDInput">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmDeleteModalLabel">Confirm Student Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="deletePassword" class="form-label">Enter your password to confirm deletion:</label>
                            <input type="password" class="form-control" name="password" id="deletePassword" required>
                            <div id="passwordError" class="text-danger mt-2 d-none"></div>
                        </div>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill"></i> Warning: This will permanently delete the student and all their associated requests!
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Student</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize tooltips
            $('[title]').tooltip();
            
            // Handle delete modal show event
            $('#confirmDeleteModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var studentID = button.data('student-id');
                $('#modalStudentIDInput').val(studentID);
                $('#deletePassword').val('');
                $('#passwordError').text('').addClass('d-none');
            });

            // Handle form submission
            $('#deleteStudentForm').on('submit', function(e) {
                e.preventDefault();
                
                var formData = $(this).serialize();
                
                $.ajax({
                    type: 'POST',
                    url: $(this).attr('action'),
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            window.location.reload();
                        } else {
                            $('#passwordError').text(response.error || 'An error occurred').removeClass('d-none');
                            $('#deletePassword').val('').focus();
                        }
                    },
                    error: function() {
                        $('#passwordError').text('An error occurred. Please try again.').removeClass('d-none');
                    }
                });
            });
        });


        function resetFilters() {
    $('#filterStatus').val('');
    $('#filterYear').val('');
    $('#filterCourse').val('');
    $('#searchInput').val('');
    filterTable();
}




        function filterTable() {
    const status = $('#filterStatus').val().toLowerCase();
    const year = $('#filterYear').val().toLowerCase();
    const course = $('#filterCourse').val().toLowerCase();
    const search = $('#searchInput').val().toLowerCase();

    $('table tbody tr').each(function() {
        const row = $(this);
        const rowStatus = row.find('td:eq(2)').text().toLowerCase();
        const rowYear = row.find('td:eq(3)').text().toLowerCase();
        const rowCourse = row.find('td:eq(4)').text().toLowerCase();
        const studentNo = row.find('td:eq(0)').text().toLowerCase();
        const name = row.find('td:eq(1)').text().toLowerCase();

        const matchesStatus = !status || rowStatus.includes(status);
        const matchesYear = !year || rowYear.includes(year);
        const matchesCourse = !course || rowCourse.includes(course);
        const matchesSearch = !search || studentNo.includes(search) || name.includes(search);

        if (matchesStatus && matchesYear && matchesCourse && matchesSearch) {
            row.show();
        } else {
            row.hide();
        }
    });
}

$('#filterStatus, #filterYear, #filterCourse, #searchInput').on('input change', filterTable);

    </script>
</body>
</html>
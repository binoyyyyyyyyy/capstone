<?php
session_start();
require_once '../config/config.php';
include '../includes/sidevar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$role = $_SESSION['role_type'];

$stmt = $conn->prepare("SELECT documentID, documentCode, documentName, documentDesc, documentStatus, procTime FROM DocumentsType WHERE dateDeleted IS NULL");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Documents | NEUST Registrar</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
            
            .badge {
                font-size: 0.7rem;
                padding: 0.3em 0.5em;
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
            
            .badge {
                font-size: 0.65rem;
                padding: 0.25em 0.4em;
            }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="topbar">
            <h4 class="page-title">Manage Document Types</h4>
            <div class="user-profile">
                <img src="../assets/avatar.jpg" alt="User Avatar">
                <div>
                    <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['user_email']); ?></div>
                    <small class="text-muted"><?php echo htmlspecialchars(ucfirst($role)); ?></small>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between mb-4">
            <div>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div>
                <a href="add_document.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add New Document Type
                </a>
            </div>
        </div>
        

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                    <table class="table table-hover align-middle" style="min-width: 700px;">
                        <thead>
                            <tr>
                                <th style="min-width: 120px;">Document Code</th>
                                <th style="min-width: 150px;">Document Name</th>
                                <th style="min-width: 200px;">Description</th>
                                <th style="min-width: 120px;">Processing Time</th>
                                <th style="min-width: 80px;">Status</th>
                                <th style="min-width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo htmlspecialchars($row['documentCode']); ?></td>
                                    <td><?php echo htmlspecialchars($row['documentName']); ?></td>
                                    <td><?php echo htmlspecialchars($row['documentDesc']); ?></td>
                                    <td><?php echo htmlspecialchars($row['procTime']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($row['documentStatus'] == 'available') ? 'success' : 'danger'; ?>">
                                            <i class="bi bi-<?php echo ($row['documentStatus'] == 'available') ? 'check-circle' : 'x-circle'; ?> me-1"></i>
                                            <?php echo htmlspecialchars(ucfirst($row['documentStatus'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit_document.php?id=<?php echo $row['documentID']; ?>" 
                                           class="btn btn-sm btn-outline-warning action-btn" 
                                           title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-danger action-btn delete-document-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#confirmDeleteModal" 
                                                data-document-id="<?php echo $row['documentID']; ?>"
                                                title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        <i class="bi bi-file-earmark-text"></i>
                                        <h5>No Document Types Found</h5>
                                        <p>Add your first document type by clicking the "Add New Document Type" button</p>
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
            <form method="POST" action="../backend/delete_document.php" id="deleteDocumentForm">
                <input type="hidden" name="documentID" id="modalDocumentIDInput">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmDeleteModalLabel">Confirm Document Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="deletePassword" class="form-label">Enter your password to confirm deletion:</label>
                            <input type="password" class="form-control" name="password" id="deletePassword" required>
                            <div id="passwordError" class="text-danger mt-2 d-none"></div>
                        </div>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill"></i> Warning: This will permanently delete this document type!
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Document</button>
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
                var documentID = button.data('document-id');
                $('#modalDocumentIDInput').val(documentID);
                $('#deletePassword').val('');
                $('#passwordError').text('').addClass('d-none');
            });

            // Handle form submission
            $('#deleteDocumentForm').on('submit', function(e) {
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
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
<?php
session_start();
require_once '../config/config.php';
include '../includes/sidevar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$role = $_SESSION['role_type'];
$addedBy = isset($_SESSION['first_name']) && isset($_SESSION['last_name'])  
    ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] 
    : 'Unknown';

$stmt = $conn->prepare("SELECT userID, firstName, lastName, email, role_type, userStatus, added_by, edited_by FROM UserTable");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | NEUST Registrar</title>
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
        
        .role-badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 600;
            border-radius: 0.25rem;
        }
        
        .badge-admin {
            background-color: #6f42c1;
            color: white;
        }
        
        .badge-registrar {
            background-color: #20c997;
            color: white;
        }
        
        .badge-staff {
            background-color: #fd7e14;
            color: white;
        }
        
        .status-badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 600;
            border-radius: 0.25rem;
            text-transform: capitalize;
        }
        
        .badge-active {
            background-color: #28a745;
            color: white;
        }
        
        .badge-inactive {
            background-color: #dc3545;
            color: white;
        }
        
        .badge-pending {
            background-color: #ffc107;
            color: black;
        }
        
        .badge-suspended {
            background-color: #6c757d;
            color: white;
        }
        
        .user-initials {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--neust-blue);
            color: white;
            font-weight: 600;
            margin-right: 10px;
        }
        
        .user-name {
            display: inline-flex;
            align-items: center;
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
    </style>
</head>
<body>
    <div class="main-content">
        <div class="topbar">
            <h4 class="page-title">User Management</h4>
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
                <a href="add_user.php" class="btn btn-primary">
                    <i class="bi bi-person-plus"></i> Add New User
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Added By</th>
                                <th>Edited By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): 
                                    $firstNameInitial = !empty($row['firstName']) ? substr($row['firstName'], 0, 1) : '';
                                    $lastNameInitial = !empty($row['lastName']) ? substr($row['lastName'], 0, 1) : '';
                                    $initials = strtoupper($firstNameInitial . $lastNameInitial);
                                    
                                    $statusClass = '';
                                    switch(strtolower($row['userStatus'])) {
                                        case 'active':
                                            $statusClass = 'badge-active';
                                            break;
                                        case 'inactive':
                                            $statusClass = 'badge-inactive';
                                            break;
                                        case 'pending':
                                            $statusClass = 'badge-pending';
                                            break;
                                        case 'suspended':
                                            $statusClass = 'badge-suspended';
                                            break;
                                        default:
                                            $statusClass = 'badge-secondary';
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <div class="user-name">
                                            <span class="user-initials"><?php echo $initials; ?></span>
                                            <?php echo htmlspecialchars($row['firstName'] . ' ' . $row['lastName']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td>
                                        <span class="role-badge badge-<?php echo strtolower($row['role_type']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($row['role_type'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars(ucfirst($row['userStatus'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['added_by'] ?? '<span class="text-muted">Unknown</span>'); ?></td>
                                    <td><?php echo htmlspecialchars($row['edited_by'] ?? '----'); ?></td>
                                    <td>
                                        <a href="edit_user.php?id=<?php echo $row['userID']; ?>" 
                                           class="btn btn-sm btn-outline-warning action-btn" 
                                           title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($row['userID'] != $_SESSION['user_id']): ?>
                                        <button class="btn btn-sm btn-outline-danger action-btn delete-user-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#confirmDeleteModal" 
                                                data-user-id="<?php echo $row['userID']; ?>"
                                                title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary action-btn" 
                                                title="Cannot delete your own account" 
                                                disabled>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="empty-state">
                                        <i class="bi bi-people"></i>
                                        <h5>No Users Found</h5>
                                        <p>Add your first user by clicking the "Add New User" button</p>
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
            <form method="POST" action="../backend/delete_user.php" id="deleteUserForm">
                <input type="hidden" name="userID" id="modalUserIDInput">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmDeleteModalLabel">Confirm User Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="deletePassword" class="form-label">Enter your password to confirm deletion:</label>
                            <input type="password" class="form-control" name="password" id="deletePassword" required>
                            <div id="passwordError" class="text-danger mt-2 d-none"></div>
                        </div>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill"></i> Warning: This will permanently delete the user account!
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete User</button>
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
                var userID = button.data('user-id');
                $('#modalUserIDInput').val(userID);
                $('#deletePassword').val('');
                $('#passwordError').text('').addClass('d-none');
            });

            // Handle form submission
            $('#deleteUserForm').on('submit', function(e) {
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
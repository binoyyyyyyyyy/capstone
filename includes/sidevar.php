
<?php
  // Start session in sidevar.php if it's not already started
$role = isset($_SESSION['role_type']) ? $_SESSION['role_type'] : '';  // Get user role from session
?>

    <!-- Sidebar Navigation -->
    
    <div class="sidebar">
        <div class="sidebar-brand">
            <img src="../assets/neustlogo.png" alt="NEUST Logo">
            <h4>NEUST Registrar</h4>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_request.php">
                    <i class="bi bi-inbox"></i>
                    Manage Requests
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_students.php">
                    <i class="bi bi-people"></i>
                    Student Records
                </a>
            </li>
            <?php if ($role === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link" href="manage_documents.php">
                    <i class="bi bi-file-earmark-text"></i>
                    Manage Documents
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_users.php">
                    <i class="bi bi-person-gear"></i>
                    Manage Admins
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item mt-4">
                <a class="nav-link" href="../admin/logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>

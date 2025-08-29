<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user status is pending
if ($_SESSION['user_status'] !== 'pending') {
    header("Location: dashboard.php");
    exit();
}

$userName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$userEmail = $_SESSION['user_email'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Pending | NEUST Registrar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #003366;
            --secondary-color: #ffc107;
            --accent-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --warning-color: #ffc107;
        }

        body {
            background: linear-gradient(135deg, var(--primary-color) 40%, var(--accent-color) 100%);
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .pending-card {
            background: var(--light-color);
            border: none;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
            text-align: center;
            overflow: hidden;
        }

        .card-header {
            background: var(--warning-color);
            color: var(--dark-color);
            padding: 2rem;
            border-bottom: 4px solid var(--primary-color);
        }

        .status-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .card-body {
            padding: 2.5rem;
        }

        .user-info {
            background: var(--light-color);
            border: 2px solid var(--warning-color);
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }

        .contact-info {
            background: var(--primary-color);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }

        .btn-logout {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            background: var(--accent-color);
            transform: translateY(-2px);
            color: white;
        }

        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }

        .feature-disabled {
            opacity: 0.5;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="pending-card">
        <div class="card-header">
            <div class="status-icon">
                <i class="bi bi-clock-history"></i>
            </div>
            <h3 class="mb-2">Account Pending Approval</h3>
            <p class="mb-0">Your account is currently under review</p>
        </div>
        
        <div class="card-body">
            <div class="alert alert-warning" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Important:</strong> Your account is pending approval. You cannot access any system functions until an administrator approves your account.
            </div>

            <div class="user-info">
                <h5><i class="bi bi-person-circle me-2"></i>Account Information</h5>
                <div class="row text-start">
                    <div class="col-md-6">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($userName); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($userEmail); ?></p>
                    </div>
                </div>
                <p><strong>Status:</strong> <span class="badge bg-warning text-dark">Pending Approval</span></p>
            </div>

            <div class="contact-info">
                <h5><i class="bi bi-info-circle me-2"></i>What to do next?</h5>
                <ul class="text-start">
                    <li>Wait for an administrator to review and approve your account</li>
                    <li>If you need immediate access, contact the NEUST REGISTRAR office</li>
                    <li>You will receive an email notification once your account is approved</li>
                </ul>
            </div>

            <div class="mt-4">
                <a href="../logout.php" class="btn btn-logout">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

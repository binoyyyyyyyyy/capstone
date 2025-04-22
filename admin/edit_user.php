<?php
session_start();
require_once '../config/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Invalid request");
}

$userID = $_GET['id'];
$loggedInUser = $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; // Get the logged-in admin name

// Fetch user details
$stmt = $conn->prepare("SELECT firstName, lastName, email, role_type, userStatus FROM UserTable WHERE userID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found");
}

// Update user
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $email = trim($_POST['email']);
    $role_type = $_POST['role_type'];
    $userStatus = $_POST['userStatus'];

    // Update user details and set edited_by
    $stmt = $conn->prepare("UPDATE UserTable SET firstName = ?, lastName = ?, email = ?, role_type = ?, userStatus = ?, edited_by = ? WHERE userID = ?");
    $stmt->bind_param("ssssssi", $firstName, $lastName, $email, $role_type, $userStatus, $loggedInUser, $userID);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "User updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update user.";
    }
    
    $stmt->close();
    header("Location: manage_users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User | User Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .user-card {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: none;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 1.5rem;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        .form-control, .form-select {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        }
        .btn-submit {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        .btn-back {
            transition: all 0.3s;
        }
        .btn-back:hover {
            transform: translateX(-3px);
        }
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
        }
        .role-admin {
            background-color: #d1e7ff;
            color: #0a58ca;
        }
        .role-staff {
            background-color: #fff3cd;
            color: #664d03;
        }
        .status-active {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-inactive {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .editor-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 0.9rem;
        }
        .status-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status-toggle-label {
            font-weight: 500;
            color: #495057;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="user-card card">
                    <div class="card-header text-white text-center">
                        <h3><i class="bi bi-person-gear me-2"></i>Edit User Profile</h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($_SESSION['message'])): ?>
                            <div class="alert alert-success d-flex align-items-center">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <div><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
                            </div>
                        <?php elseif (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <div><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">
                                            <i class="bi bi-person"></i>
                                        </span>
                                        <input type="text" name="firstName" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['firstName']); ?>" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">
                                            <i class="bi bi-person"></i>
                                        </span>
                                        <input type="text" name="lastName" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['lastName']); ?>" required>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">
                                            <i class="bi bi-envelope"></i>
                                        </span>
                                        <input type="email" name="email" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">User Role</label>
                                    <div class="d-flex align-items-center">
                                        <span class="status-badge role-<?php echo strtolower($user['role_type']); ?> me-2">
                                            <?php echo ucfirst($user['role_type']); ?>
                                        </span>
                                        <select name="role_type" class="form-select flex-grow-1" required>
                                            <option value="admin" <?php echo ($user['role_type'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                            <option value="staff" <?php echo ($user['role_type'] == 'staff') ? 'selected' : ''; ?>>Staff</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Account Status</label>
                                    <div class="d-flex align-items-center">
                                        <span class="status-badge status-<?php echo strtolower($user['userStatus']); ?> me-2">
                                            <?php echo ucfirst($user['userStatus']); ?>
                                        </span>
                                        <select name="userStatus" class="form-select flex-grow-1" required>
                                            <option value="active" <?php echo ($user['userStatus'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo ($user['userStatus'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                            <option value="pending" <?php echo ($user['userStatus'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="editor-info">
                                        <i class="bi bi-pencil-square me-2"></i>
                                        <strong>Edited By:</strong> <?php echo htmlspecialchars($loggedInUser); ?>
                                    </div>
                                </div>

                                <div class="col-12 mt-4">
                                    <div class="d-flex justify-content-between">
                                        <a href="manage_users.php" class="btn btn-outline-secondary btn-back">
                                            <i class="bi bi-arrow-left me-1"></i> Back to Users
                                        </a>
                                        <button type="submit" class="btn btn-primary btn-submit">
                                            <i class="bi bi-save me-1"></i> Update User
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = this.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            // Email validation
            const emailField = this.querySelector('[type="email"]');
            if (emailField && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailField.value)) {
                emailField.classList.add('is-invalid');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                // Scroll to first invalid field
                const firstInvalid = this.querySelector('.is-invalid');
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.focus();
            }
        });

        // Live validation on blur
        document.querySelectorAll('[required]').forEach(field => {
            field.addEventListener('blur', function() {
                if (!this.value.trim()) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
                
                // Special validation for email
                if (this.type === 'email' && this.value.trim()) {
                    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value)) {
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-invalid');
                    }
                }
            });
        });

        // Update status badge when dropdown changes
        document.querySelector('select[name="userStatus"]').addEventListener('change', function() {
            const badge = this.previousElementSibling;
            badge.className = `status-badge status-${this.value} me-2`;
            badge.textContent = this.options[this.selectedIndex].text;
        });

        // Update role badge when dropdown changes
        document.querySelector('select[name="role_type"]').addEventListener('change', function() {
            const badge = this.previousElementSibling;
            badge.className = `status-badge role-${this.value} me-2`;
            badge.textContent = this.options[this.selectedIndex].text;
        });
    </script>
</body>
</html>
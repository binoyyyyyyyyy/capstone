<?php
session_start();
require_once '../config/config.php'; // Database connection
// Handle Login Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare("SELECT userID, firstName, lastName, email, password, role_type, userStatus FROM UserTable WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Check if account is inactive
            if ($user['userStatus'] === 'inactive') {
                $error = "This account is inactive. Please contact the NEUST REGISTRAR office for assistance.";
            }
            // Check if account is pending
            elseif ($user['userStatus'] === 'pending') {
                $error = "Your account is pending approval. Please wait for admin approval or contact the NEUST REGISTRAR office.";
            }
            // Check if password is correct
            elseif (password_verify($password, $user['password'])) {
                // Only allow active users to log in
                if ($user['userStatus'] === 'active') {
                    // Store user details in session
                    $_SESSION['user_id'] = $user['userID'];
                    $_SESSION['first_name'] = $user['firstName'];
                    $_SESSION['last_name'] = $user['lastName'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['role_type'] = $user['role_type'];
                    $_SESSION['user_status'] = $user['userStatus'];

                    // Redirect to dashboard
                    header("Location: dashboard.php");
                    exit();
                } else {
                    // This should not happen due to earlier checks, but just in case
                    $error = "Account status error. Please contact the NEUST REGISTRAR office.";
                }
            } else {
                $error = "Invalid password!";
            }
        } else {
            $error = "No account found with that email!";
        }
        
        $stmt->close();
    } else {
        $error = "Invalid email format!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | NEUST</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --primary-color: #003366;
      --secondary-color: #ffc107;
      --accent-color: #17a2b8;
      --light-color: #f8f9fa;
      --dark-color: #343a40;
    }

    body {
      background: linear-gradient(135deg, var(--primary-color) 40%, var(--accent-color) 100%);
      min-height: 100vh;
      font-family: 'Poppins', sans-serif;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-start;
      position: relative;
      padding-top: 100px; /* leave space for navbar */
    }

    /* Background watermark */
    body::before {
      content: "";
      background: url('nuestlogo.png') no-repeat center;
      background-size: 400px;
      opacity: 0.05;
      position: absolute;
      top: 50%;
      left: 50%;
      width: 500px;
      height: 500px;
      transform: translate(-50%, -50%);
      z-index: 0;
    }

    .login-card {
      border: none;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
      width: 100%;
      max-width: 420px;
      background: var(--light-color);
      position: relative;
      z-index: 1;
      margin-bottom: 2rem;
      margin-top:40px;
    }

    .navbar {
      background-color: var(--primary-color) !important;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      width: 100%;
      position: fixed;

      margin-bottom:20px;

    }

    .navbar-brand img {
      height: 80px;
      margin-right: 10px;
    }

    .nav-link {
      color: rgba(255, 255, 255, 0.9) !important;
      font-weight: 500;
      transition: all 0.3s;
    }
    .nav-link:hover { color: var(--secondary-color) !important; }

    .card-header {
      background: var(--primary-color);
      color: var(--light-color);
      text-align: center;
      padding: 2rem;
      border-bottom: 4px solid var(--secondary-color);
    }

    .brand-logo img { width: 60px; height: 60px; margin-bottom: 10px; }

    .card-body { padding: 2rem; }

    .btn-primary {
      background-color: var(--secondary-color);
      border: none;
      border-radius: 8px;
      padding: 12px;
      font-weight: 600;
      color: var(--dark-color);
      transition: all 0.3s;
    }
    .btn-primary:hover { background-color: #e0a800; transform: translateY(-2px); }

    /* Core values */
    .core-values {
      text-align: center;
      position: relative;
      z-index: 1;
      color: var(--light-color);
    }
    .core-values h5 {
      font-weight: 700;
      margin-bottom: 1.5rem;
      color: var(--light-color);
    }
    .values-list {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      justify-content: center;
    }
    .value-card {
      background: var(--light-color);
      color: var(--dark-color);
      border-radius: 12px;
      padding: 1.2rem 1.5rem;
      font-weight: 600;
      min-width: 140px;
      text-align: center;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      transition: transform 0.3s, background 0.3s;
    }
    .value-card i {
      display: block;
      font-size: 2rem;
      margin-bottom: 0.5rem;
      color: var(--primary-color);
    }
    .value-card:hover {
      transform: translateY(-6px);
      background: var(--secondary-color);
      color: var(--dark-color);
    }
    .value-card:hover i { color: var(--dark-color); }

    .title{
        text-align:center;
        display:flex;
    }
  </style>
</head>
<body>

  <!-- Navbar (moved here from head) -->
  <?php include '../includes/login_nav.php'; ?>

  <!-- Login Card -->
  <div class="login-card">
    <div class="card-header">
      <div class="brand-logo">
        <img src="../assets/neustlogo.png" alt="NEUST Logo">
      </div>
      <h4 class="mb-0">NEUST Admin Login</h4>
      <p class="mb-0">Enter your credentials to continue</p>
    </div>
    <div class="card-body">
      <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <?php echo $error; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <?php if (isset($_GET['error']) && $_GET['error'] === 'inactive'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <strong>Account Inactive:</strong> This account is inactive. Please contact the NEUST REGISTRAR office for assistance.
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="mb-3">
          <label for="email" class="form-label">Email Address</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" class="form-control input-with-icon" id="email" placeholder="your@email.com" required>
          </div>
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" class="form-control input-with-icon" id="password" placeholder="Enter your password" required>
          </div>
          <br><a href="forgot-password.php" class="forgot-link">Forgot password?</a>
        </div>

        <div class="d-grid mb-3">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-box-arrow-in-right me-2"></i> Login
          </button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

    <script>
        // Simple animation for input focus
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('.input-group-text').style.color = 'var(--primary-color)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.querySelector('.input-group-text').style.color = '';
            });
        });
    </script>
</body>
</html>
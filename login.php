<?php
session_start();
require_once 'config/config.php'; // Database connection

// Handle Login Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare("SELECT userID, firstName, lastName, email, password, role_type FROM UserTable WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Store user details in session
                $_SESSION['user_id'] = $user['userID'];
                $_SESSION['first_name'] = $user['firstName'];
                $_SESSION['last_name'] = $user['lastName'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role_type'] = $user['role_type'];

                // Redirect to dashboard
                header("Location: admin/dashboard.php");
                exit();
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
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center vh-100 bg-light">
    <div class="card shadow p-4" style="width: 350px;">
        <h3 class="text-center mb-4">Login</h3>
        <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" class="form-control" id="email" placeholder="Enter your email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" class="form-control" id="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

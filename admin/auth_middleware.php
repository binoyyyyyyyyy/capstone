<?php
// Authentication and Authorization Middleware
// Include this file at the top of admin pages after session_start()

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if user status is pending - redirect to pending dashboard
if (isset($_SESSION['user_status']) && $_SESSION['user_status'] === 'pending') {
    header("Location: pending_user_dashboard.php");
    exit();
}

// Check if user status is inactive - redirect to login with error
if (isset($_SESSION['user_status']) && $_SESSION['user_status'] === 'inactive') {
    session_destroy();
    header("Location: login.php?error=inactive");
    exit();
}

// Optional: Check if user has required role (for role-specific pages)
function requireRole($requiredRole) {
    if (!isset($_SESSION['role_type']) || $_SESSION['role_type'] !== $requiredRole) {
        header("Location: dashboard.php?error=unauthorized");
        exit();
    }
}
?>

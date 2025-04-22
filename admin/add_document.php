<?php
session_start();
require_once '../config/config.php'; // Database connection

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Document | Document Management System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-color: #2e59d9;
        }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.5rem;
            border-bottom: none;
        }
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #d1d3e2;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 10px 0;
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background-color: var(--accent-color);
            transform: translateY(-2px);
        }
        .btn-outline-secondary {
            transition: all 0.3s;
        }
        .btn-outline-secondary:hover {
            transform: translateY(-2px);
        }
        .floating-label {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .floating-label label {
            position: absolute;
            top: -10px;
            left: 15px;
            background-color: white;
            padding: 0 5px;
            font-size: 0.85rem;
            color: #6e707e;
        }
        .required-field::after {
            content: " *";
            color: #e74a3b;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header text-center">
                        <h3><i class="bi bi-file-earmark-plus me-2"></i> Add New Document</h3>
                    </div>
                    <div class="card-body p-5">
                        <?php if (!empty($_GET['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo htmlspecialchars($_GET['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($_GET['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?php echo htmlspecialchars($_GET['success']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="../backend/add_document.php" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="floating-label">
                                        <label for="documentCode" class="required-field">Document Code</label>
                                        <input type="text" id="documentCode" name="documentCode" class="form-control" 
                                               placeholder="DOC-001" required>
                                        <div class="invalid-feedback">
                                            Please provide a document code.
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="floating-label">
                                        <label for="documentName" class="required-field">Document Name</label>
                                        <input type="text" id="documentName" name="documentName" class="form-control" 
                                               placeholder="Certificate of Residency" required>
                                        <div class="invalid-feedback">
                                            Please provide a document name.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="floating-label">
                                <label for="processingTime" class="required-field">Processing Time</label>
                                <select id="processingTime" name="processingTime" class="form-select" required>
                                    <option value="" disabled selected>Select processing time</option>
                                    <option value="1 day">1 Day</option>
                                    <option value="2 days">2 Days</option>
                                    <option value="3 days">3 Days</option>
                                    <option value="1 week">1 Week</option>
                                    <option value="2 weeks">2 Weeks</option>
                                    <option value="1 month">1 Month</option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a processing time.
                                </div>
                            </div>
                            
                            <div class="floating-label">
                                <label for="documentDesc" class="required-field">Description</label>
                                <textarea id="documentDesc" name="documentDesc" class="form-control" 
                                          rows="3" placeholder="Enter detailed description of the document..." required></textarea>
                                <div class="invalid-feedback">
                                    Please provide a description.
                                </div>
                            </div>
                            
                            <div class="floating-label">
                                <label for="documentStatus" class="required-field">Status</label>
                                <select id="documentStatus" name="documentStatus" class="form-select" required>
                                    <option value="available">Available</option>
                                    <option value="unavailable">Unavailable</option>
                                </select>
                            </div>
                            
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-2"></i> Add Document
                                </button>
                            </div>
                        </form>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="manage_documents.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Back to Documents
                            </a>
                            <a href="dashboard.php" class="btn btn-outline-primary">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            
            var forms = document.querySelectorAll('.needs-validation')
            
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
        
        // Add animation to form elements
        document.querySelectorAll('.form-control, .form-select').forEach((element) => {
            element.addEventListener('focus', () => {
                element.parentElement.classList.add('animate__animated', 'animate__pulse')
            })
            
            element.addEventListener('blur', () => {
                element.parentElement.classList.remove('animate__animated', 'animate__pulse')
            })
        })
    </script>
</body>
</html>
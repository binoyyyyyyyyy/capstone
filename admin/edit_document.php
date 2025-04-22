<?php
session_start();
require_once '../config/config.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Check if document ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_documents.php");
    exit();
}

$documentID = intval($_GET['id']);

// Fetch document details
$stmt = $conn->prepare("SELECT documentCode, documentName, documentDesc, documentStatus, procTime FROM DocumentsType WHERE documentID = ?");
$stmt->bind_param("i", $documentID);
$stmt->execute();
$result = $stmt->get_result();
$document = $result->fetch_assoc();
$stmt->close();

if (!$document) {
    $_SESSION['error'] = "Document not found!";
    header("Location: manage_documents.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Document | Document Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .document-card {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: none;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #3a7bd5, #00d2ff);
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
            background: linear-gradient(135deg, #3a7bd5, #00d2ff);
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(58, 123, 213, 0.3);
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
        .status-available {
            background-color: #d4edda;
            color: #155724;
        }
        .status-unavailable {
            background-color: #f8d7da;
            color: #721c24;
        }
        textarea {
            min-height: 120px;
            resize: vertical;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="document-card card">
                    <div class="card-header text-white text-center">
                        <h3><i class="bi bi-file-earmark-text me-2"></i>Edit Document</h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <div><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['message'])): ?>
                            <div class="alert alert-success d-flex align-items-center">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <div><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="../backend/edit_document.php?id=<?php echo $documentID; ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Document Code</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">
                                            <i class="bi bi-upc-scan"></i>
                                        </span>
                                        <input type="text" name="documentCode" class="form-control" 
                                               value="<?php echo htmlspecialchars($document['documentCode']); ?>" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Current Status</label>
                                    <div class="d-flex align-items-center">
                                        <span class="status-badge status-<?php echo strtolower($document['documentStatus']); ?> me-2">
                                            <?php echo ucfirst($document['documentStatus']); ?>
                                        </span>
                                        <select name="documentStatus" class="form-select flex-grow-1" required>
                                            <option value="available" <?php if ($document['documentStatus'] == 'available') echo 'selected'; ?>>Available</option>
                                            <option value="unavailable" <?php if ($document['documentStatus'] == 'unavailable') echo 'selected'; ?>>Unavailable</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Document Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">
                                            <i class="bi bi-file-text"></i>
                                        </span>
                                        <input type="text" name="documentName" class="form-control" 
                                               value="<?php echo htmlspecialchars($document['documentName']); ?>" required>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea name="documentDesc" class="form-control" required><?php echo htmlspecialchars($document['documentDesc']); ?></textarea>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Processing Time</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">
                                            <i class="bi bi-clock"></i>
                                        </span>
                                        <select name="procTime" class="form-select" required>
                                            <option value="1 day" <?php if ($document['procTime'] == '1 day') echo 'selected'; ?>>1 Day</option>
                                            <option value="2 days" <?php if ($document['procTime'] == '2 days') echo 'selected'; ?>>2 Days</option>
                                            <option value="3 days" <?php if ($document['procTime'] == '3 days') echo 'selected'; ?>>3 Days</option>
                                            <option value="1 week" <?php if ($document['procTime'] == '1 week') echo 'selected'; ?>>1 Week</option>
                                            <option value="2 weeks" <?php if ($document['procTime'] == '2 weeks') echo 'selected'; ?>>2 Weeks</option>
                                            <option value="1 month" <?php if ($document['procTime'] == '1 month') echo 'selected'; ?>>1 Month</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12 mt-4">
                                    <div class="d-flex justify-content-between">
                                        <a href="manage_documents.php" class="btn btn-outline-secondary btn-back">
                                            <i class="bi bi-arrow-left me-1"></i> Back to Documents
                                        </a>
                                        <button type="submit" class="btn btn-primary btn-submit">
                                            <i class="bi bi-save me-1"></i> Update Document
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
            
            if (!isValid) {
                e.preventDefault();
                // Scroll to first invalid field
                const firstInvalid = this.querySelector('.is-invalid');
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.focus();
            }
        });
    </script>
</body>
</html>
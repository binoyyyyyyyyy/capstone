<?php
session_start();
require_once '../config/config.php';
include '../includes/sidevar.php';

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

// Check if a request ID is provided
$requestID = isset($_GET['id']) ? intval($_GET['id']) : null;

// Fetch the selected request
$stmt = $conn->prepare("SELECT r.requestID, r.requestCode, r.dateRequest, r.requestStatus, 
    s.firstname, s.lastname, d.documentName, r.datePickUp, r.nameOfReceiver, r.remarks
    FROM RequestTable r
    JOIN studentInformation s ON r.studentID = s.studentID
    JOIN DocumentsType d ON r.documentID = d.documentID
    WHERE r.requestID = ?");
$stmt->bind_param("i", $requestID);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();

// Fetch images separately to handle multiple images per request
$imageStmt = $conn->prepare("SELECT image, additionalimage FROM supportingimage WHERE requestID = ?");
$imageStmt->bind_param("i", $requestID);
$imageStmt->execute();
$imageResult = $imageStmt->get_result();

$authorizationImage = null;
$verificationImage = null;

while ($imageRow = $imageResult->fetch_assoc()) {
    if (!empty($imageRow['image'])) {
        $authorizationImage = $imageRow['image'];
    }
    if (!empty($imageRow['additionalimage'])) {
        $verificationImage = $imageRow['additionalimage'];
    }
}
$imageStmt->close();

// If no request is found
if (!$request) {
    echo "<p>No request found with ID: $requestID</p>";
    echo "<a href='manage_request.php'>Back to Manage Requests</a>";
    exit();
}

// Function to get status badge class
function getStatusBadge($status) {
    switch(strtolower($status)) {
        case 'pending':
            return 'bg-warning text-dark';
        case 'approved':
            return 'bg-success';
        case 'rejected':
            return 'bg-danger';
        case 'completed':
            return 'bg-primary';
        default:
            return 'bg-secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Request | Document Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
            }
        }
        /* Mobile overrides */
        @media (max-width: 768px) {
            .card .card-header {
                text-align: center;
            }
        }
        
        .card-header { position: relative; }
        .status-badge { position: absolute; top: 15px; right: 15px; font-size: 0.9rem; padding: 5px 10px; border-radius: 20px; }
        .detail-card { border-left: 4px solid #0d6efd; transition: transform 0.2s; }
        .detail-card:hover { transform: translateY(-3px); }
        .detail-label { font-weight: 600; color: #495057; }
        .img-preview { border: 1px solid #dee2e6; border-radius: 5px; cursor: pointer; transition: transform 0.3s; }
        .img-preview:hover { transform: scale(1.02); }
        .back-btn { transition: all 0.3s; }
        .back-btn:hover { transform: translateX(-3px); }
    </style>
</head>
<body class="bg-light">
    <div class="main-content">
        <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-sm border-0 overflow-hidden">
                    <div class="card-header bg-primary text-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Request Details</h4>
                            <span class="badge <?php echo getStatusBadge($request['requestStatus']); ?> status-badge">
                                <?php echo htmlspecialchars($request['requestStatus']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card detail-card mb-3 h-100">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary mb-4"><i class="bi bi-person-badge me-2"></i>Requester Information</h5>
                                        <div class="mb-3"><span class="detail-label">Request Code:</span><p class="mb-0"><?php echo htmlspecialchars($request['requestCode']); ?></p></div>
                                        <div class="mb-3"><span class="detail-label">Student Name:</span><p class="mb-0"><?php echo htmlspecialchars($request['firstname'] . ' ' . $request['lastname']); ?></p></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card detail-card mb-3 h-100">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary mb-4"><i class="bi bi-file-earmark me-2"></i>Document Information</h5>
                                        <div class="mb-3"><span class="detail-label">Document Type:</span><p class="mb-0"><?php echo htmlspecialchars($request['documentName']); ?></p></div>
                                        <div class="mb-3"><span class="detail-label">Date Requested:</span><p class="mb-0"><?php echo htmlspecialchars($request['dateRequest']); ?></p></div>
                                        <div class="mb-3"><span class="detail-label">Pick-up Date:</span><p class="mb-0"><?php echo htmlspecialchars($request['datePickUp']); ?></p></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card detail-card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary mb-4"><i class="bi bi-truck me-2"></i>Delivery Information</h5>
                                        <div class="mb-3"><span class="detail-label">Receiver Name:</span><p class="mb-0"><?php echo htmlspecialchars($request['nameOfReceiver']); ?></p></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card detail-card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary mb-4"><i class="bi bi-chat-square-text me-2"></i>Additional Information</h5>
                                        <div class="mb-3"><span class="detail-label">Remarks:</span><p class="mb-0"><?php echo !empty($request['remarks']) ? htmlspecialchars($request['remarks']) : 'No remarks provided'; ?></p></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($authorizationImage)): ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card detail-card">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary mb-4"><i class="bi bi-image me-2"></i>Authorization Image</h5>
                                        <div class="text-center">
                                            <img src="../uploads/<?php echo htmlspecialchars($authorizationImage); ?>" 
                                                 class="img-preview img-fluid rounded" 
                                                 style="max-height: 400px;"
                                                 data-bs-toggle="modal" 
                                                 data-bs-target="#imageModal">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($verificationImage)): ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card detail-card">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary mb-4"><i class="bi bi-image me-2"></i>Verification Image</h5>
                                        <div class="text-center">
                                            <img src="../uploads/<?php echo htmlspecialchars($verificationImage); ?>" 
                                                 class="img-preview img-fluid rounded" 
                                                 style="max-height: 400px;"
                                                 data-bs-toggle="modal" 
                                                 data-bs-target="#additionalImageModal">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="manage_request.php" class="btn btn-outline-primary back-btn">
                                <i class="bi bi-arrow-left me-1"></i> Back to Requests
                            </a>
                            <div>
                                <button class="btn btn-primary me-2"><i class="bi bi-printer me-1"></i> Print</button>
                                <button class="btn btn-success"><i class="bi bi-download me-1"></i> Export</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <?php if (!empty($authorizationImage)): ?>
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Authorization Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="../uploads/<?php echo htmlspecialchars($authorizationImage); ?>" class="img-fluid">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="../uploads/<?php echo htmlspecialchars($authorizationImage); ?>" download class="btn btn-primary"><i class="bi bi-download me-1"></i> Download</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($verificationImage)): ?>
    <div class="modal fade" id="additionalImageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Verification Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="../uploads/<?php echo htmlspecialchars($verificationImage); ?>" class="img-fluid">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="../uploads/<?php echo htmlspecialchars($verificationImage); ?>" download class="btn btn-primary"><i class="bi bi-download me-1"></i> Download</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>

<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if a request ID is provided
$requestID = isset($_GET['id']) ? intval($_GET['id']) : null;

// Fetch the selected request
$stmt = $conn->prepare("SELECT r.requestID, r.requestCode, r.dateRequest, r.requestStatus, 
    s.firstname, s.lastname, d.documentName, r.datePickUp, r.nameOfReceiver, r.remarks, 
    si.image, si.additionalimage
    FROM RequestTable r
    JOIN studentInformation s ON r.studentID = s.studentID
    JOIN DocumentsType d ON r.documentID = d.documentID
    LEFT JOIN supportingimage si ON r.requestID = si.requestID
    WHERE r.requestID = ?");
$stmt->bind_param("i", $requestID);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();

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

                        <?php if (!empty($request['image'])): ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card detail-card">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary mb-4"><i class="bi bi-image me-2"></i>Authorization Image</h5>
                                        <div class="text-center">
                                            <img src="../uploads/<?php echo htmlspecialchars($request['image']); ?>" 
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

                        <?php if (!empty($request['additionalimage'])): ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="card detail-card">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary mb-4"><i class="bi bi-image me-2"></i>Verification Image</h5>
                                        <div class="text-center">
                                            <img src="../uploads/<?php echo htmlspecialchars($request['additionalimage']); ?>" 
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

    <?php if (!empty($request['image'])): ?>
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Authorization Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="../uploads/<?php echo htmlspecialchars($request['image']); ?>" class="img-fluid">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="../uploads/<?php echo htmlspecialchars($request['image']); ?>" download class="btn btn-primary"><i class="bi bi-download me-1"></i> Download</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($request['additionalimage'])): ?>
    <div class="modal fade" id="additionalImageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Additional Supporting Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="../uploads/<?php echo htmlspecialchars($request['additionalimage']); ?>" class="img-fluid">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="../uploads/<?php echo htmlspecialchars($request['additionalimage']); ?>" download class="btn btn-primary"><i class="bi bi-download me-1"></i> Download</a>
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

<?php
session_start();
require_once '../config/config.php';

// Initialize the request code variable
$requestCode = isset($_POST['requestCode']) ? trim($_POST['requestCode']) : '';

// Initialize
$request = null;
$error_message = '';

// Fetch request details if request code is provided
if ($requestCode) {
    $stmt = $conn->prepare("SELECT 
        r.requestID, r.requestCode, r.dateRequest, r.dateRelease, r.requestStatus, 
        s.firstname, s.lastname, s.studentNo, s.contactNo,
        d.documentName, d.procTime,
        r.datePickUp, r.nameOfReceiver, r.remarks, 
        si.image, si.additionalimage, r.sVerify, u.email as processed_by
        FROM RequestTable r
        JOIN StudentInformation s ON r.studentID = s.studentID
        JOIN DocumentsType d ON r.documentID = d.documentID
        LEFT JOIN supportingimage si ON r.requestID = si.requestID
        LEFT JOIN UserTable u ON r.userID = u.userID
        WHERE r.requestCode = ?");
    $stmt->bind_param("s", $requestCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['additionalImage']) && $request) {
    // Handle additional image upload if sVerify == 1
    if ($request['sVerify'] == 1 && isset($_FILES['additionalImage']['name']) && $_FILES['additionalImage']['error'] === UPLOAD_ERR_OK) {

        $uploadDir = '../uploads/';
        $fileName = basename($_FILES['additionalImage']['name']);
        $targetFilePath = $uploadDir . time() . '_' . $fileName;

        if (move_uploaded_file($_FILES['additionalImage']['tmp_name'], $targetFilePath)) {
            $imageFileName = basename($targetFilePath);

            // Update the additionalimage in the supportingimage table
            $stmt = $conn->prepare("UPDATE supportingimage SET additionalimage = ? WHERE requestID = ?");
            $stmt->bind_param("si", $imageFileName, $request['requestID']);
            if ($stmt->execute()) {
                $request['additionalimage'] = $imageFileName; // Update the local variable for immediate display
            }
            $stmt->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$request) {
    $error_message = "Request not found. Please check the code and try again.";
}
?>

<?php include '../includes/index_nav.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Details | NEUST Registrar</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --neust-blue: #0056b3;
            --neust-yellow: #FFD700;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--neust-blue), #007bff);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 1.5rem;
        }
        
        .detail-card {
            border-left: 4px solid var(--neust-blue);
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .detail-card-header {
            background-color: rgba(0, 86, 179, 0.05);
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .detail-card-body {
            padding: 1.25rem;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 0.75rem;
        }
        
        .detail-label {
            font-weight: 500;
            color: #495057;
            min-width: 180px;
        }
        
        .detail-value {
            flex: 1;
            color: #212529;
        }
        
        .status-badge {
            padding: 0.5em 0.8em;
            font-size: 0.9em;
            font-weight: 500;
            border-radius: 0.25rem;
        }
        .badge-ready {
    background-color:#fd7e14  ;
    color: white;
}
        .badge-pending {
            background-color: #ffc107;
            color: #212529;
        }
        
        .badge-approved {
            background-color: #198754;
            color: white;
        }
        
        .badge-rejected {
            background-color: #dc3545;
            color: white;
        }
        
        .badge-completed {
            background-color: #0d6efd;
            color: white;
        }
        
        .document-image {
            max-width: 100%;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .back-btn {
            position: absolute;
            top: 1.5rem;
            left: 1.5rem;
        }



        :root {
            --primary-color: #003366;
            --secondary-color: #ffc107;
            --accent-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .navbar {
            background-color: var(--primary-color) !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 0.8rem 1rem;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            font-weight: 600;
        }
        
        .navbar-brand img {
            height: 40px;
            margin-right: 15px;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link:focus {
            color: white !important;
            transform: translateY(-2px);
        }
        
        .main-title {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 2.5rem;
            position: relative;
            display: inline-block;
        }
        
        .main-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--secondary-color);
            border-radius: 2px;
        }
        
        .choice-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            height: 100%;
            background: white;
        }
        
        .choice-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
        }
        
        .card-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
        }
        
        .card-title {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .card-body {
            padding: 2.5rem;
            text-align: center;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #002244;
            border-color: #002244;
        }
        
        .footer {
            background-color: var(--primary-color);
            color: white;
            padding: 2rem 0;
            margin-top: auto;
        }
        
        .footer a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .footer a:hover {
            color: white;
            text-decoration: underline;
        }
        
        .social-icons a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            margin-right: 10px;
            transition: all 0.3s;
        }
        
        .social-icons a:hover {
            background: var(--secondary-color);
            color: var(--dark-color) !important;
            transform: translateY(-3px);
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, #005588 100%);
            color: white;
            padding: 5rem 0;
            margin-bottom: 3rem;
            border-radius: 0 0 20px 20px;
        }
        
        .hero-title {
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        
        .hero-subtitle {
            font-weight: 300;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 3rem 0;
            }
            
            .main-title {
                font-size: 1.8rem;
            }
            
            .card-body {
                padding: 1.5rem;
            }
        }
    
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <div class="card mb-4">
                <div class="card-header text-center">
                    <h4 class="mb-0">Enter Request Code to View Details</h4>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" action="">
                        <div class="mb-3">
                            <label for="requestCode" class="form-label">Request Code</label>
                            <input type="text" id="requestCode" name="requestCode" class="form-control" required value="<?php echo htmlspecialchars($requestCode); ?>">
                        </div>

                        <?php if ($request && $request['sVerify'] == 1): ?>
                            <div class="mb-3">
                                <label for="additionalImage" class="form-label">Upload Additional Verification Image</label>
                                <input type="file" id="additionalImage" name="additionalImage" class="form-control" accept="image/*">
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary w-100">Submit</button>
                    </form>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger mt-3" role="alert">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($request): ?>
                <div class="card">
                    <div class="card-header text-center">
                        <h4 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Request Details</h4>
                    </div>
                    <div class="card-body p-4">

                        <!-- Request Overview -->
                        <div class="detail-card mb-4">
                            <div class="detail-card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Request Information</h5>
                                <span class="status-badge badge-<?php echo strtolower($request['requestStatus']); ?>">
                                    <?php echo htmlspecialchars(ucfirst($request['requestStatus'])); ?>
                                </span>
                            </div>
                            <div class="detail-card-body">
                                <div class="detail-row"><div class="detail-label">Request Code:</div><div class="detail-value fw-bold"><?php echo htmlspecialchars($request['requestCode']); ?></div></div>
                                <div class="detail-row"><div class="detail-label">Date Requested:</div><div class="detail-value"><?php echo date('F j, Y h:i A', strtotime($request['dateRequest'])); ?></div></div>
                                <div class="detail-row"><div class="detail-label">Date Release:</div><div class="detail-value"><?php echo date('F j, Y h:i A', strtotime($request['dateRelease'])); ?></div></div>
                            </div>
                        </div>

                        <!-- Document Information -->
                        <div class="detail-card mb-4">
                            <div class="detail-card-header"><h5 class="mb-0"><i class="bi bi-file-earmark me-2"></i>Document Information</h5></div>
                            <div class="detail-card-body">
                                <div class="detail-row"><div class="detail-label">Document Name:</div><div class="detail-value"><?php echo htmlspecialchars($request['documentName']); ?></div></div>
                                <div class="detail-row"><div class="detail-label">Processing Time:</div><div class="detail-value"><?php echo htmlspecialchars($request['procTime']); ?></div></div>
                                <div class="detail-row"><div class="detail-label">Scheduled Pickup:</div><div class="detail-value"><?php echo date('F j, Y', strtotime($request['datePickUp'])); ?></div></div>
                            </div>
                        </div>

                        <!-- Student Information -->
                        <div class="detail-card mb-4">
                            <div class="detail-card-header"><h5 class="mb-0"><i class="bi bi-person me-2"></i>Student Information</h5></div>
                            <div class="detail-card-body">
                                <div class="detail-row"><div class="detail-label">Student Number:</div><div class="detail-value"><?php echo htmlspecialchars($request['studentNo']); ?></div></div>
                                <div class="detail-row"><div class="detail-label">Full Name:</div><div class="detail-value"><?php echo htmlspecialchars($request['firstname'] . ' ' . $request['lastname']); ?></div></div>
                                <div class="detail-row"><div class="detail-label">Contact Number:</div><div class="detail-value"><?php echo htmlspecialchars($request['contactNo'] ?? 'N/A'); ?></div></div>
                            </div>
                        </div>

                        <!-- Receiver Information -->
                        <div class="detail-card mb-4">
                            <div class="detail-card-header"><h5 class="mb-0"><i class="bi bi-person-check me-2"></i>Receiver Information</h5></div>
                            <div class="detail-card-body">
                                <div class="detail-row"><div class="detail-label">Receiver Name:</div><div class="detail-value"><?php echo htmlspecialchars($request['nameOfReceiver']); ?></div></div>
                                <?php if (!empty($request['remarks'])): ?>
                                    <div class="detail-row"><div class="detail-label">Remarks:</div><div class="detail-value"><?php echo htmlspecialchars($request['remarks']); ?></div></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Authorization Document -->
                        <?php if (!empty($request['image'])): ?>
                        <div class="detail-card mb-4">
                            <div class="detail-card-header"><h5 class="mb-0"><i class="bi bi-file-image me-2"></i>Authorization Document</h5></div>
                            <div class="detail-card-body">
                                <img src="../uploads/<?php echo htmlspecialchars($request['image']); ?>" class="document-image" alt="Authorization Document" data-bs-toggle="modal" data-bs-target="#imageModal" style="cursor: pointer;">
                                <p class="text-muted mt-2">Click image to view larger version</p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Additional Uploaded Image -->
                        <?php if (!empty($request['additionalimage'])): ?>
                        <div class="detail-card mb-4">
                            <div class="detail-card-header"><h5 class="mb-0"><i class="bi bi-file-image me-2"></i>Uploaded Verification Image</h5></div>
                            <div class="detail-card-body">
                                <img src="../uploads/<?php echo htmlspecialchars($request['additionalimage']); ?>" class="document-image" alt="Additional Image" style="max-width: 100%; height: auto;">
                                <p class="text-muted mt-2">Additional Image</p>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/index_footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
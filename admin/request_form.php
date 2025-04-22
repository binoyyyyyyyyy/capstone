<?php
session_start();
date_default_timezone_set('Asia/Manila');
require_once '../config/config.php';

$documentQuery = $conn->query("SELECT documentID, documentName, procTime, documentStatus FROM DocumentsType WHERE dateDeleted IS NULL");
$documents = $documentQuery->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['documentID']) || empty($_POST['documentID'])) {
        $_SESSION['error'] = "Please select at least one document.";
        header("Location: request_form.php");
        exit();
    }

    $requestCodePrefix = uniqid('REQ-');
    $studentNo = $_POST['studentNo'];
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $dateRequest = date('Y-m-d H:i:s');
    $datePickUp = $_POST['datePickUp'];
    $nameOfReceiver = $_POST['nameOfReceiver'];
    $userID = $_SESSION['user_id'];
    $documentsToProcess = $_POST['documentID'];
    $fullName = $firstName . ' ' . $lastName;

    // Validate student information
    $studentCheck = $conn->prepare("SELECT studentID FROM StudentInformation WHERE studentNo = ? AND firstName = ? AND lastName = ? AND dateDeleted IS NULL");
    $studentCheck->bind_param("sss", $studentNo, $firstName, $lastName);
    $studentCheck->execute();
    $studentCheck->store_result();

    if ($studentCheck->num_rows == 0) {
        $_SESSION['error'] = "The provided student information is incorrect.";
        header("Location: request_form.php");
        exit();
    }

    $studentCheck->bind_result($studentID);
    $studentCheck->fetch();
    $studentCheck->close();

    // Check if TOR is among selected documents
    $torSelected = false;
    $placeholders = str_repeat('?,', count($documentsToProcess) - 1) . '?';
    $torCheck = $conn->prepare("SELECT documentID FROM DocumentsType WHERE documentName LIKE '%TOR%' AND documentID IN ($placeholders)");
    $torCheck->bind_param(str_repeat('i', count($documentsToProcess)), ...$documentsToProcess);
    $torCheck->execute();
    $torCheck->store_result();
    $torSelected = ($torCheck->num_rows > 0);
    $torCheck->close();

    // Handle file uploads
    $authorizationImage = NULL;
    $verificationImage = NULL;
    $targetDir = "../uploads/";
    $allowedTypes = array('jpg', 'jpeg', 'png', 'pdf');

    // Process authorization image (required if receiver isn't student OR TOR is selected)
    if (($nameOfReceiver !== $fullName) && empty($_FILES['authorizationImage']['name'])) {
        $_SESSION['error'] = $torSelected 
            ? "Authorization image is required for  requests." 
            : "Authorization image is required when receiver's name doesn't match the student's full name.";
        header("Location: request_form.php");
        exit();
    }

    if (!empty($_FILES['authorizationImage']['name'])) {
        $fileName = time() . "_auth_" . basename($_FILES['authorizationImage']['name']);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        if (in_array($fileType, $allowedTypes)) {
            if (!move_uploaded_file($_FILES['authorizationImage']['tmp_name'], $targetFilePath)) {
                $_SESSION['error'] = "Authorization file upload failed.";
                header("Location: request_form.php");
                exit();
            }
            $authorizationImage = $fileName;
        } else {
            $_SESSION['error'] = "Invalid authorization file type. Only JPG, JPEG, PNG, and PDF are allowed.";
            header("Location: request_form.php");
            exit();
        }
    }

    // Process verification image (required if TOR is selected)
    if ($torSelected && empty($_FILES['verificationImage']['name'])) {
        $_SESSION['error'] = "Verification image is required for Transcript of Records requests.";
        header("Location: request_form.php");
        exit();
    }

    if (!empty($_FILES['verificationImage']['name'])) {
        $fileName = time() . "_verif_" . basename($_FILES['verificationImage']['name']);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        if (in_array($fileType, $allowedTypes)) {
            if (!move_uploaded_file($_FILES['verificationImage']['tmp_name'], $targetFilePath)) {
                $_SESSION['error'] = "Verification file upload failed.";
                header("Location: request_form.php");
                exit();
            }
            $verificationImage = $fileName;
        } else {
            $_SESSION['error'] = "Invalid verification file type. Only JPG, JPEG, PNG, and PDF are allowed.";
            header("Location: request_form.php");
            exit();
        }
    }

    // Get all document details
    $documentDetails = [];
    $stmt = $conn->prepare("SELECT documentID, documentName, procTime FROM DocumentsType WHERE documentID IN ($placeholders) AND documentStatus != 'unavailable'");
    $stmt->bind_param(str_repeat('i', count($documentsToProcess)), ...$documentsToProcess);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $documentDetails[$row['documentID']] = $row;
    }
    $stmt->close();

    if (empty($documentDetails)) {
        $_SESSION['error'] = "All selected documents are unavailable.";
        header("Location: request_form.php");
        exit();
    }
    
    // Calculate maximum processing time
    $maxProcessingDays = 0;
    foreach ($documentDetails as $doc) {
        $days = 0;
        switch ($doc['procTime']) {
            case '1 day': $days = 1; break;
            case '2 days': $days = 2; break;
            case '3 days': $days = 3; break;
            case '1 week': $days = 7; break;
            case '2 weeks': $days = 14; break;
            case '1 month': $days = 30; break;
        }
        if ($days > $maxProcessingDays) $maxProcessingDays = $days;
    }

    // Validate pick-up date
    $minPickUpDate = (new DateTime($dateRequest))->modify("+$maxProcessingDays days")->format('Y-m-d');
    if ($datePickUp < $minPickUpDate) {
        $_SESSION['error'] = "Pick-up date must be at least $maxProcessingDays days from today.";
        header("Location: request_form.php");
        exit();
    }

    // Process each document request
    $successCount = 0;
    $duplicateDocuments = [];
    $generatedRequestCodes = [];

    foreach ($documentsToProcess as $documentID) {
        if (!isset($documentDetails[$documentID])) {
            continue;
        }    
        $duplicateCheck = $conn->prepare("SELECT requestID FROM RequestTable WHERE studentID = ? AND documentID = ? AND requestStatus IN ('pending', 'processing', 'rejected') AND dateDeleted IS NULL");
        $duplicateCheck->bind_param("ii", $studentID, $documentID);
        $duplicateCheck->execute();
        $duplicateCheck->store_result();

        if ($duplicateCheck->num_rows > 0) {
            $duplicateDocuments[] = $documentDetails[$documentID]['documentName'];
            continue;
        }
        $duplicateCheck->close();

        // Generate request code
        $requestCode = $requestCodePrefix . '-' . $documentID;

        // Insert into RequestTable
        $stmt = $conn->prepare("INSERT INTO RequestTable 
            (requestCode, documentID, userID, studentID, dateRequest, datePickUp, requestStatus, authorizationImage, nameOfReceiver, dateCreated) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW())");
        $stmt->bind_param("siiissss", $requestCode, $documentID, $userID, $studentID, 
            $dateRequest, $datePickUp, $authorizationImage, $nameOfReceiver);
        
        if ($stmt->execute()) {
            $successCount++;
            $requestID = $conn->insert_id;
            $generatedRequestCodes[] = $requestCode;

            // Insert into supportingimage table for authorization image if uploaded
            if (!empty($authorizationImage)) {
                $supNo = uniqid('SUP-');
                $insertImage = $conn->prepare("INSERT INTO supportingimage (supNo, requestID, image, additionalimage) VALUES (?, ?, ?, NULL)");
                $insertImage->bind_param("sis", $supNo, $requestID, $authorizationImage);
                $insertImage->execute();
                $insertImage->close();
            }

            // Insert into supportingimage table for verification image if uploaded
            if (!empty($verificationImage)) {
                $supNo = uniqid('SUP-');
                $insertImage = $conn->prepare("INSERT INTO supportingimage (supNo, requestID, image, additionalimage) VALUES (?, ?, NULL, ?)");
                $insertImage->bind_param("sis", $supNo, $requestID, $verificationImage);
                $insertImage->execute();
                $insertImage->close();
            }
        }
        $stmt->close();
    }

    if ($successCount > 0) {
        $message = "Successfully submitted $successCount document request(s).";
        if (!empty($duplicateDocuments)) {
            $message .= " Duplicates skipped: " . implode(', ', $duplicateDocuments);
        }
        $_SESSION['success'] = $message;
        $_SESSION['generatedCodes'] = $generatedRequestCodes;
    } else {
        $_SESSION['error'] = "No requests processed. All selected documents have pending or in-process requests: " . implode(', ', $duplicateDocuments);
    }

    header("Location: request_form.php");
    exit();
}

include '../includes/index_nav.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Request | NEUST Registrar</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --neust-blue: #0056b3;
            --neust-yellow: #FFD700;
            --sidebar-width: 280px;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color:rgb(250, 248, 248);
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
        }
        
        .form-label {
            font-weight: 500;
        }
        
        .document-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .document-card:hover {
            border-color: var(--neust-blue);
            box-shadow: 0 2px 8px rgba(0, 86, 179, 0.1);
        }
        
        .document-card .form-check-label {
            font-weight: 500;
        }
        
        .proc-time {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .required-field::after {
            content: " *";
            color: #dc3545;
        }
        
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            display: block;
            margin-top: 10px;
            border-radius: 4px;
        }
        
        #processingTime {
            font-weight: 500;
            color: var(--neust-blue);
            padding: 8px 12px;
            background-color: rgba(0, 86, 179, 0.1);
            border-radius: 6px;
            margin-top: 10px;
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
    </style>
<style>
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
                <div class="card">
                    <div class="card-header text-center py-3">
                        <h4 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Document Request Form</h4>
                    </div>
                    
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between mb-4">
                            <a href="../admin/my_request.php" class="btn btn-outline-primary">
                                <i class="bi bi-list-ul me-1"></i> View My Requests
                            </a>
                        </div>
                        
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="mb-4">
                                <h5 class="mb-3"><i class="bi bi-file-earmark-check me-2"></i>Select Documents</h5>
                                <div id="processingTime" class="mb-3"></div>
                                
                                <div class="row">
                                    <?php foreach ($documents as $doc): 
                                        $procDays = 0;
                                        switch ($doc['procTime']) {
                                            case '1 day': $procDays = 1; break;
                                            case '2 days': $procDays = 2; break;
                                            case '3 days': $procDays = 3; break;
                                            case '1 week': $procDays = 7; break;
                                            case '2 weeks': $procDays = 14; break;
                                            case '1 month': $procDays = 30; break;
                                        }
                                        $isAvailable = ($doc['documentStatus'] != 'unavailable');
                                        $isTOR = (strpos($doc['documentName'], 'TOR') !== false);
                                    ?>
                                    <div class="col-md-6">
                                        <div class="document-card <?php echo !$isAvailable ? 'bg-light' : ''; ?>">
                                            <?php if ($isAvailable): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                        name="documentID[]" 
                                                        value="<?php echo $doc['documentID']; ?>"
                                                        data-procdays="<?php echo $procDays; ?>"
                                                        data-is-tor="<?php echo $isTOR ? 'true' : 'false'; ?>"
                                                        id="doc<?php echo $doc['documentID']; ?>"
                                                        onchange="updateProcessingTime()">
                                                    <label class="form-check-label" for="doc<?php echo $doc['documentID']; ?>">
                                                        <?php echo htmlspecialchars($doc['documentName']); ?>
                                                        <span class="proc-time">(<?php echo $doc['procTime']; ?>)</span>
                                                    </label>
                                                </div>
                                            <?php else: ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" disabled style="pointer-events: none;">
                                                    <label class="form-check-label text-muted">
                                                        <?php echo htmlspecialchars($doc['documentName']); ?>
                                                        <span class="proc-time">(<?php echo $doc['procTime']; ?>)</span>
                                                        <span class="badge bg-danger ms-2">Unavailable</span>
                                                    </label>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <h5 class="mb-3"><i class="bi bi-person-lines-fill me-2"></i>Student Information</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label for="studentNo" class="form-label required-field">Student Number</label>
                                    <input type="text" class="form-control" id="studentNo" name="studentNo" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="firstName" class="form-label required-field">First Name</label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="lastName" class="form-label required-field">Last Name</label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" required>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <h5 class="mb-3"><i class="bi bi-calendar-check me-2"></i>Request Details</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="datePickUp" class="form-label required-field">Pick-up Date</label>
                                    <input type="date" class="form-control" id="datePickUp" name="datePickUp" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="nameOfReceiver" class="form-label required-field">Name of Receiver</label>
                                    <input type="text" class="form-control" id="nameOfReceiver" name="nameOfReceiver" required>
                                    <small class="text-muted">Must match student name unless authorization is provided</small>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="authorizationImage" class="form-label" id="authImageLabel">Authorization Letter/Image</label>
                                <input type="file" class="form-control" id="authorizationImage" name="authorizationImage" accept="image/*,.pdf">
                                <small class="text-muted" id="authImageNote">Required if receiver is not the student</small>
                                <div id="authImagePreview" class="mt-2"></div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="verificationImage" class="form-label" id="verifImageLabel">TOR Verification Image</label>
                                <input type="file" class="form-control" id="verificationImage" name="verificationImage" accept="image/*,.pdf">
                                <small class="text-muted" id="verifImageNote">Required for Transcript of Records</small>
                                <div id="verifImagePreview" class="mt-2"></div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-send-check me-2"></i> Submit Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Code Modal -->
    <div class="modal fade" id="requestCodeModal" tabindex="-1" aria-labelledby="requestCodeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="requestCodeModalLabel">
                        <i class="bi bi-check-circle-fill me-2"></i>Request Submitted
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Please save your request code(s) for tracking.
                    </div>
                    <div class="mb-3">
                        <p class="fw-bold">Your request code(s):</p>
                        <ul id="requestCodesList" class="list-group"></ul>
                    </div>
                    <p class="text-danger small">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        You will need these codes when claiming your documents.
                    </p>
                </div>
                <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        "Please check your request regularly, as it may be processed earlier than the given time."
                    </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                        <i class="bi bi-check-lg me-1"></i> Understood
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update processing time display and check for TOR selection
        function updateProcessingTime() {
            const checkboxes = document.querySelectorAll('input[name="documentID[]"]:checked');
            let maxDays = 0;
            let torSelected = false;

            checkboxes.forEach(checkbox => {
                const days = parseInt(checkbox.dataset.procdays) || 0;
                if (days > maxDays) maxDays = days;
                if (checkbox.dataset.isTor === 'true') torSelected = true;
            });

            const procTimeElement = document.getElementById('processingTime');
            procTimeElement.innerHTML = maxDays > 0 
                ? `<i class="bi bi-clock me-1"></i> Maximum processing time: <strong>${maxDays} day(s)</strong>` 
                : '';
                
            // Set min pick-up date
            if (maxDays > 0) {
                const today = new Date();
                today.setDate(today.getDate() + maxDays);
                const minDate = today.toISOString().split('T')[0];
                document.getElementById('datePickUp').min = minDate;
            }

            // Update authorization image requirements
            const authImageLabel = document.getElementById('authImageLabel');
            const authImageNote = document.getElementById('authImageNote');
            const verifImageLabel = document.getElementById('verifImageLabel');
            const verifImageNote = document.getElementById('verifImageNote');

            if (torSelected) {
                authImageLabel.classList.add('required-field');
                authImageNote.textContent = '';
                authImageNote.classList.remove('text-muted');
                authImageNote.classList.add('text-danger');
                
                verifImageLabel.classList.add('required-field');
                verifImageNote.textContent = 'Required for Transcript of Records';
                verifImageNote.classList.remove('text-muted');
                verifImageNote.classList.add('text-danger');
            } else {
                authImageLabel.classList.remove('required-field');
                authImageNote.textContent = 'Required if receiver is not the student';
                authImageNote.classList.add('text-muted');
                authImageNote.classList.remove('text-danger');
                
                verifImageLabel.classList.remove('required-field');
                verifImageNote.textContent = 'Only required for Transcript of Records';
                verifImageNote.classList.add('text-muted');
                verifImageNote.classList.remove('text-danger');
            }
        }

        // Image preview for authorization image
        document.getElementById('authorizationImage').addEventListener('change', function(e) {
            const preview = document.getElementById('authImagePreview');
            preview.innerHTML = '';
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'preview-image';
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Image preview for verification image
        document.getElementById('verificationImage').addEventListener('change', function(e) {
            const preview = document.getElementById('verifImagePreview');
            preview.innerHTML = '';
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'preview-image';
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Form validation
        (function () {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>

    <?php if (isset($_SESSION['generatedCodes'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const requestCodes = <?php echo json_encode($_SESSION['generatedCodes']); ?>;
            const list = document.getElementById('requestCodesList');
            
            requestCodes.forEach(code => {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center';
                li.innerHTML = `
                    <span>${code}</span>
                    <button class="btn btn-sm btn-outline-primary copy-btn" data-code="${code}">
                        <i class="bi bi-clipboard"></i>
                    </button>
                `;
                list.appendChild(li);
            });
            
            // Add copy functionality
            document.querySelectorAll('.copy-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const code = this.getAttribute('data-code');
                    navigator.clipboard.writeText(code).then(() => {
                        const icon = this.querySelector('i');
                        icon.className = 'bi bi-check';
                        setTimeout(() => {
                            icon.className = 'bi bi-clipboard';
                        }, 2000);
                    });
                });
            });
            
            const modal = new bootstrap.Modal(document.getElementById('requestCodeModal'));
            modal.show();
        });
    </script>
    <?php unset($_SESSION['generatedCodes']); endif; ?>

    <?php include '../includes/index_footer.php'; ?>
</body>
</html>
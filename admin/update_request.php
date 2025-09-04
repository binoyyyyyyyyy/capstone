<?php
// Check if session is already started (config.php already starts it)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';
require '../vendor/autoload.php';
require_once '../config/pusher_config.php';
include '../includes/sidevar.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Validate request ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No request ID provided";
    header("Location: manage_request.php");
    exit();
}

$requestID = intval($_GET['id']);

// Fetch request details with additional information
$stmt = $conn->prepare("SELECT 
    r.requestID, r.requestCode, r.requestStatus, r.remarks, r.datePickUp,
    s.firstname, s.lastname, s.studentNo,
    d.documentName
    FROM RequestTable r
    JOIN StudentInformation s ON r.studentID = s.studentID
    JOIN DocumentsType d ON r.documentID = d.documentID
    WHERE r.requestID = ?");
$stmt->bind_param("i", $requestID);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();

// Check if request exists
if (!$request) {
    $_SESSION['error'] = "Request not found";
    header("Location: manage_request.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $newStatus = $_POST['requestStatus'];
    $remarks = $_POST['remarks'] ?? null;
    $datePickUp = $_POST['datePickUp'] ?? null;
    $askForVerification = isset($_POST['askVerification']) ? 1 : 0;

    // Validate status
    $validStatuses = ['pending', 'approved', 'ready to pickup', 'rejected', 'completed'];
    if (!in_array($newStatus, $validStatuses)) {
        $_SESSION['error'] = "Invalid status selected";
        header("Location: update_request.php?id=" . $requestID);
        exit();
    }

    $editedBy = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

    if ($newStatus === 'completed') {
        $stmt = $conn->prepare("UPDATE RequestTable 
            SET requestStatus = ?, 
                remarks = COALESCE(?, remarks),
                datePickUp = ?,
                dateRelease = NOW(),
                dateUpdated = NOW(),
                edited_by = ?,
                sverify = ?
            WHERE requestID = ?");
        $stmt->bind_param("ssssii", $newStatus, $remarks, $datePickUp, $editedBy, $askForVerification, $requestID);
    } else {
        $stmt = $conn->prepare("UPDATE RequestTable 
            SET requestStatus = ?, 
                remarks = COALESCE(?, remarks),
                datePickUp = ?,
                dateUpdated = NOW(),
                edited_by = ?,
                sverify = ?
            WHERE requestID = ?");
        $stmt->bind_param("ssssii", $newStatus, $remarks, $datePickUp, $editedBy, $askForVerification, $requestID);
    }
    

    if ($stmt->execute()) {
        $_SESSION['message'] = "Request #" . $request['requestCode'] . " updated successfully!";

        // Send Pusher notification for status update
        $notificationData = array(
            'type' => 'status_update',
            'requestCode' => $request['requestCode'],
            'studentName' => $request['firstname'] . ' ' . $request['lastname'],
            'documentName' => $request['documentName'],
            'oldStatus' => $request['requestStatus'],
            'newStatus' => $newStatus,
            'message' => "Request status updated: $requestCode - $newStatus"
        );
        sendPusherNotification('admin-channel', 'status-update', $notificationData);

        // Fetch email, request code, status, and remarks from the database
        $stmt2 = $conn->prepare("SELECT email, requestCode, requestStatus, remarks FROM RequestTable WHERE requestID = ?");
        $stmt2->bind_param("i", $requestID);
        $stmt2->execute();
        $stmt2->bind_result($email, $requestCode, $requestStatus, $remarks);
        $stmt2->fetch();
        $stmt2->close();

        // Fetch document description
        $stmt3 = $conn->prepare("SELECT documentDesc FROM DocumentsType WHERE documentID = (SELECT documentID FROM RequestTable WHERE requestID = ?)");
        $stmt3->bind_param("i", $requestID);
        $stmt3->execute();
        $stmt3->bind_result($documentDesc);
        $stmt3->fetch();
        $stmt3->close();

        // Prepare email content based on status
        $subject = '';
        $body = '';
        $altBody = '';
        
        switch($newStatus) {
            case 'approved':
                $subject = 'Document Request Approved – NEUST-MGT Registrar';
                $body = "Dear " . $request['firstname'] . " " . $request['lastname'] . ",<br><br>";
                $body .= "Your request for <strong>" . $request['documentName'] . "</strong> (" . $documentDesc . ") (Request CODE: <strong>" . $requestCode . "</strong>) has been approved.<br>";
                $body .= "Our office will begin preparing your requested document. Please check your portal for updates on the processing timeline.<br><br>";
                $body .= "Best regards,<br>NEUST-MGT Registrar Admin";
                
                $altBody = "Dear " . $request['firstname'] . " " . $request['lastname'] . ",\n\n";
                $altBody .= "Your request for " . $request['documentName'] . " (" . $documentDesc . ") (Request CODE: " . $requestCode . ") has been approved.\n";
                $altBody .= "Our office will begin preparing your requested document. Please check your portal for updates on the processing timeline.\n\n";
                $altBody .= "Best regards,\nNEUST-MGT Registrar Admin";
                break;
                
            case 'rejected':
                $subject = 'Document Request Rejected – NEUST-MGT Registrar';
                $body = "Dear " . $request['firstname'] . " " . $request['lastname'] . ",<br><br>";
                $body .= "We regret to inform you that your request for <strong>" . $request['documentName'] . "</strong> (" . $documentDesc . ") (Request CODE: <strong>" . $requestCode . "</strong>) has been rejected.<br><br>";
                if ($remarks) {
                    $body .= "<strong>Reason:</strong> " . $remarks . "<br><br>";
                }
                $body .= "Should you have questions or wish to reapply, kindly reach out to our office.<br><br>";
                $body .= "Sincerely,<br>NEUST-MGT Registrar Admin";
                
                $altBody = "Dear " . $request['firstname'] . " " . $request['lastname'] . ",\n\n";
                $altBody .= "We regret to inform you that your request for " . $request['documentName'] . " (" . $documentDesc . ") (Request CODE: " . $requestCode . ") has been rejected.\n\n";
                if ($remarks) {
                    $altBody .= "Reason: " . $remarks . "\n\n";
                }
                $altBody .= "Should you have questions or wish to reapply, kindly reach out to our office.\n\n";
                $altBody .= "Sincerely,\nNEUST-MGT Registrar Admin";
                break;
                
            case 'ready to pickup':
                $subject = 'Requested Document Ready for Pickup – NEUST-MGT Registrar';
                $body = "Dear " . $request['firstname'] . " " . $request['lastname'] . ",<br><br>";
                $body .= "Your requested document <strong>" . $request['documentName'] . "</strong> (" . $documentDesc . ") (Request CODE: <strong>" . $requestCode . "</strong>) is now ready for pickup at the NEUST-MGT Registrar's Office.<br><br>";
                $body .= "📅 <strong>Pickup Date:</strong> " . ($datePickUp ? date('F j, Y', strtotime($datePickUp)) : 'To be announced') . "<br>";
                $body .= "📍 <strong>Location:</strong> NEUST-MGT Registrar's Office<br>";
                $body .= "🕒 <strong>Office Hours:</strong> Monday to Friday, 8:00 AM - 5:00 PM<br><br>";
                $body .= "Please bring a valid ID and your request reference number when claiming your document.<br><br>";
                $body .= "Thank you,<br>NEUST-MGT Registrar Admin";
                
                $altBody = "Dear " . $request['firstname'] . " " . $request['lastname'] . ",\n\n";
                $altBody .= "Your requested document " . $request['documentName'] . " (" . $documentDesc . ") (Request CODE: " . $requestCode . ") is now ready for pickup at the NEUST-MGT Registrar's Office.\n\n";
                $altBody .= "Pickup Date: " . ($datePickUp ? date('F j, Y', strtotime($datePickUp)) : 'To be announced') . "\n";
                $altBody .= "Location: NEUST-MGT Registrar's Office\n";
                $altBody .= "Office Hours: Monday to Friday, 8:00 AM - 5:00 PM\n\n";
                $altBody .= "Please bring a valid ID and your request reference number when claiming your document.\n\n";
                $altBody .= "Thank you,\nNEUST-MGT Registrar Admin";
                break;
                
            case 'completed':
                $subject = 'Document Request Completed – NEUST-MGT Registrar';
                $body = "Dear " . $request['firstname'] . " " . $request['lastname'] . ",<br><br>";
                $body .= "Your document request for <strong>" . $request['documentName'] . "</strong> (" . $documentDesc . ") (Request CODE: <strong>" . $requestCode . "</strong>) has been completed.<br><br>";
                $body .= "We hope the provided document will be of help to your academic or professional needs.<br><br>";
                $body .= "Thank you for using the NEUST-MGT Registrar's services.<br><br>";
                $body .= "Best regards,<br>NEUST-MGT Registrar Admin";
                
                $altBody = "Dear " . $request['firstname'] . " " . $request['lastname'] . ",\n\n";
                $altBody .= "Your document request for " . $request['documentName'] . " (" . $documentDesc . ") (Request CODE: " . $requestCode . ") has been completed.\n\n";
                $altBody .= "We hope the provided document will be of help to your academic or professional needs.\n\n";
                $altBody .= "Thank you for using the NEUST-MGT Registrar's services.\n\n";
                $altBody .= "Best regards,\nNEUST-MGT Registrar Admin";
                break;
                
            default:
                $subject = 'Update on Your Document Request – NEUST-MGT Registrar';
                $body = "Dear " . $request['firstname'] . " " . $request['lastname'] . ",<br><br>";
                $body .= "Your request for <strong>" . $request['documentName'] . "</strong> (" . $documentDesc . ") (Request CODE: <strong>" . $requestCode . "</strong>) status has been updated to: <strong>" . ucfirst($newStatus) . "</strong>.<br><br>";
                if ($remarks) {
                    $body .= "<strong>Remarks:</strong> " . $remarks . "<br><br>";
                }
                $body .= "Thank you for your patience.<br><br>";
                $body .= "Best regards,<br>NEUST-MGT Registrar Admin";
                
                $altBody = "Dear " . $request['firstname'] . " " . $request['lastname'] . ",\n\n";
                $altBody .= "Your request for " . $request['documentName'] . " (" . $documentDesc . ") (Request CODE: " . $requestCode . ") status has been updated to: " . ucfirst($newStatus) . ".\n\n";
                if ($remarks) {
                    $altBody .= "Remarks: " . $remarks . "\n\n";
                }
                $altBody .= "Thank you for your patience.\n\n";
                $altBody .= "Best regards,\nNEUST-MGT Registrar Admin";
                break;
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'emailtestingsendeer@gmail.com';
            $mail->Password   = 'gknv xrds xvqb drjz';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('emailtestingsendeer@gmail.com', 'NEUST-MGT Registrar');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $altBody;

            $mail->send();
        } catch (Exception $e) {
            // Optionally log error: $mail->ErrorInfo
        }

        header("Location: manage_request.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to update request: " . $conn->error;
        header("Location: update_request.php?id=" . $requestID);
        exit();
    }
    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Request | NEUST Registrar</title>
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
                padding: 10px;
            }
            
            .container {
                padding: 10px;
                max-width: 100%;
            }
            
            .col-lg-8 {
                padding: 0;
                max-width: 100%;
            }
            
            .card {
                margin-left: 0 !important;
                margin: 10px;
                width: calc(100% - 20px);
            }
            
            .card-header {
                padding: 1rem;
                text-align: center;
            }
            
            .card-header h4 {
                font-size: 1.2rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 10px;
                padding: 12px;
                font-size: 0.9rem;
            }
            
            .back-btn {
                position: relative;
                left: auto;
                top: auto;
                margin-bottom: 1rem;
            }
            
            .d-grid.gap-2.d-md-flex.justify-content-md-end {
                flex-direction: column;
                gap: 10px;
            }
            
            .d-grid.gap-2.d-md-flex.justify-content-md-end .btn {
                width: 100%;
            }
            
            .request-info {
                padding: 1rem;
            }
            
            .form-control, .form-select {
                padding: 10px 12px;
                font-size: 0.9rem;
            }
            
            .status-badge {
                font-size: 0.8rem;
                padding: 4px 8px;
            }
        }
        
        @media (max-width: 576px) {
            .container {
                padding: 5px;
            }
            
            .card {
                margin: 5px;
            }
            
            .card-header {
                padding: 0.75rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .request-info {
                padding: 0.75rem;
            }
            
            .form-label {
                font-size: 0.9rem;
            }
            
            .form-control, .form-select {
                padding: 10px 12px;
                font-size: 0.9rem;
            }
            
            .status-badge {
                font-size: 0.8rem;
                padding: 0.3em 0.6em;
            }
            
            .request-info-label {
                font-size: 0.9rem;
            }
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-left: 150px;
        }
        
        @media (max-width: 768px) {
            .card {
                margin-left: 0 !important;
                width: calc(100% - 20px);
            }
        }
        .card-header {
            background: linear-gradient(135deg, var(--neust-blue), #007bff);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 1.25rem;
        }
        .status-badge {
            padding: 0.5em 0.8em;
            font-size: 0.9em;
            font-weight: 500;
            border-radius: 0.25rem;
        }
        .badge-pending { background-color: #ffc107; color: #212529; }
        .badge-approved { background-color: #198754; color: white; }
        .badge-rejected { background-color: #dc3545; color: white; }
        .badge-completed { background-color: #0d6efd; color: white; }
        .badge-ready-to-pick { background-color: #0dcaf0; color: #000; }
        .form-select-status {
            padding: 0.5rem;
            font-weight: 500;
            border-width: 2px;
        }
        .form-select-status option {
            padding: 0.5rem;
        }
        .back-btn {
            position: absolute;
            left: 1.5rem;
            top: 1.25rem;
        }
        .request-info {
            background-color: rgba(0, 86, 179, 0.05);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .request-info-item {
            margin-bottom: 0.5rem;
        }
        .request-info-label {
            font-weight: 500;
            color: #495057;
        }
        #verificationCheckboxWrapper {
            display: none;
            margin-bottom: 1rem;
            padding: 0.75rem;
            border: 1px dashed #dc3545;
            border-radius: 5px;
            background: rgba(220, 53, 69, 0.05);
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header text-center position-relative">
                    
                    <h4 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Update Request Status</h4>
                </div>
                <div class="card-body p-4">

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="request-info">
                        <div class="request-info-item">
                            <span class="request-info-label">Request Code:</span>
                            <span class="fw-bold"><?php echo htmlspecialchars($request['requestCode']); ?></span>
                        </div>
                        <div class="request-info-item">
                            <span class="request-info-label">Student:</span>
                            <span><?php echo htmlspecialchars($request['firstname'] . ' ' . $request['lastname']); ?> (<?php echo htmlspecialchars($request['studentNo']); ?>)</span>
                        </div>
                        <div class="request-info-item">
                            <span class="request-info-label">Document:</span>
                            <span><?php echo htmlspecialchars($request['documentName']); ?></span>
                        </div>
                        <div class="request-info-item">
                            <span class="request-info-label">Current Status:</span>
                            <span class="status-badge badge-<?php echo strtolower($request['requestStatus']); ?>">
                                <?php echo htmlspecialchars(ucfirst($request['requestStatus'])); ?>
                            </span>
                        </div>
                    </div>

                    <form action="update_request.php?id=<?php echo $requestID; ?>" method="post">
                        <div class="mb-4">
                            <label for="requestStatus" class="form-label fw-bold">New Status:</label>
                            <select name="requestStatus" id="requestStatus" class="form-select form-select-status" required>
                                <option value="pending" <?php echo ($request['requestStatus'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo ($request['requestStatus'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo ($request['requestStatus'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                <option value="ready to pickup" <?php echo ($request['requestStatus'] == 'ready to pickup') ? 'selected' : ''; ?>>Ready to Pickup</option>
                                <option value="completed" <?php echo ($request['requestStatus'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>

                        <div id="verificationCheckboxWrapper">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="askVerification" name="askVerification">
                                <label class="form-check-label text-danger fw-bold" for="askVerification">
                                    Ask the student to send more verification.
                                </label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="datePickUp" class="form-label fw-bold">Pickup Date:</label>
                            <input type="date" name="datePickUp" id="datePickUp" class="form-control"
                                   value="<?php echo htmlspecialchars($request['datePickUp']); ?>">
                        </div>

                        <div class="mb-4">
                            <label for="remarks" class="form-label fw-bold">Remarks/Notes:</label>
                            <textarea name="remarks" id="remarks" class="form-control" rows="3" placeholder="Add any additional notes..."><?php echo htmlspecialchars($request['remarks']); ?></textarea>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="manage_request.php" class="btn btn-secondary me-md-2">
                                <i class="bi bi-x-circle me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i> Update Status
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    function handleStatusChange() {
        const statusSelect = document.getElementById('requestStatus');
        const checkboxWrapper = document.getElementById('verificationCheckboxWrapper');

        if (statusSelect.value === 'rejected') {
            checkboxWrapper.style.display = 'block';
        } else {
            checkboxWrapper.style.display = 'none';
        }

        // Reset styles
        statusSelect.className = 'form-select form-select-status';
        switch(statusSelect.value) {
            case 'pending':
                statusSelect.classList.add('border-warning', 'text-warning');
                break;
            case 'approved':
                statusSelect.classList.add('border-success', 'text-success');
                break;
            case 'ready to pickup':
                statusSelect.classList.add('border-info', 'text-info');
                break;
            case 'rejected':
                statusSelect.classList.add('border-danger', 'text-danger');
                break;
            case 'completed':
                statusSelect.classList.add('border-primary', 'text-primary');
                break;
        }
    }

    document.getElementById('requestStatus').addEventListener('change', handleStatusChange);
    document.addEventListener('DOMContentLoaded', function() {
        handleStatusChange();
    });

    // Pusher Configuration for Real-time Notifications
    // Enable pusher logging - don't include this in production
    Pusher.logToConsole = true;

    var pusher = new Pusher('ed1a40e7a469cee7f86c', {
        cluster: 'ap1'
    });

    var channel = pusher.subscribe('admin-channel');
    channel.bind('status-update', function(data) {
        // Show notification for status update
        if (data.type === 'status_update') {
            const notification = document.createElement('div');
            notification.className = 'alert alert-success alert-dismissible fade show position-fixed';
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 350px; max-width: 400px;';
            notification.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2" style="font-size: 1.2rem;"></i>
                    <div class="flex-grow-1">
                        <strong>Status Updated</strong><br>
                        <small>${data.message}</small><br>
                        <small class="text-muted">Student: ${data.studentName}</small><br>
                        <small class="text-muted">Document: ${data.documentName}</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            document.body.appendChild(notification);
            
            // Auto remove after 6 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 6000);
        }
    });

    // Mobile menu toggle functionality
    document.getElementById('mobileMenuToggle').addEventListener('click', function() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('show');
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        const sidebar = document.querySelector('.sidebar');
        const menuToggle = document.getElementById('mobileMenuToggle');
        
        if (window.innerWidth <= 768 && 
            !sidebar.contains(e.target) && 
            !menuToggle.contains(e.target)) {
            sidebar.classList.remove('show');
        }
    });
</script>
</body>
</html>

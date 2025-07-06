<?php
session_start();
require_once '../config/config.php';
require '../vendor/autoload.php';
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
    JOIN studentInformation s ON r.studentID = s.studentID
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

        // Fetch email, request code, status, and remarks from the database
        $stmt2 = $conn->prepare("SELECT email, requestCode, requestStatus, remarks FROM RequestTable WHERE requestID = ?");
        $stmt2->bind_param("i", $requestID);
        $stmt2->execute();
        $stmt2->bind_result($email, $requestCode, $requestStatus, $remarks);
        $stmt2->fetch();
        $stmt2->close();

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'emailtestingsendeer@gmail.com';
            $mail->Password   = 'gknv xrds xvqb drjz';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('emailtestingsendeer@gmail.com', 'NEUST Registrar');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Update on Your Document Request';
            $mail->Body    = "Dear Student,<br>Your request <b>$requestCode</b> status is now: <b>$requestStatus</b>.<br>Remarks: $remarks<br>Thank you!";
            $mail->AltBody = "Dear Student,\nYour request $requestCode status is now: $requestStatus.\nRemarks: $remarks\nThank you!";

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
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
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
                    <a href="manage_request.php" class="btn btn-light back-btn">
                        <i class="bi bi-arrow-left"></i>
                    </a>
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
</script>
</body>
</html>

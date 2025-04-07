
<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

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
    $dateRequest = date('Y-m-d');
    $datePickUp = $_POST['datePickUp'];
    $nameOfReceiver = $_POST['nameOfReceiver'];
    $remarks = $_POST['remarks'];
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

    // Check receiver name authorization
    if ($nameOfReceiver !== $fullName && empty($_FILES['authorizationImage']['name'])) {
        $_SESSION['error'] = "Authorization image is required when receiver's name doesn't match the student's full name.";
        header("Location: request_form.php");
        exit();
    }

    // Handle file upload
    $authorizationImage = NULL;
    if (!empty($_FILES['authorizationImage']['name'])) {
        $targetDir = "../uploads/";
        $fileName = time() . "_" . basename($_FILES['authorizationImage']['name']);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        $allowedTypes = array('jpg', 'jpeg', 'png', 'pdf');
        if (in_array($fileType, $allowedTypes)) {
            if (!move_uploaded_file($_FILES['authorizationImage']['tmp_name'], $targetFilePath)) {
                $_SESSION['error'] = "File upload failed.";
                header("Location: request_form.php");
                exit();
            }
            $authorizationImage = $fileName;
        } else {
            $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, PNG, and PDF are allowed.";
            header("Location: request_form.php");
            exit();
        }
    }

    // Get all document details
    $documentDetails = [];
    $placeholders = str_repeat('?,', count($documentsToProcess) - 1) . '?';
    $stmt = $conn->prepare("SELECT documentID, documentName, procTime FROM DocumentsType WHERE documentID IN ($placeholders) AND documentStatus != 'Unavailable'");
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
            continue; // Skip unavailable or invalid documents
        }    
        $duplicateCheck = $conn->prepare("SELECT requestID FROM RequestTable WHERE studentID = ? AND documentID = ? AND requestStatus = 'pending'");
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
            (requestCode, documentID, userID, studentID, dateRequest, datePickUp, requestStatus, authorizationImage, nameOfReceiver, remarks, dateCreated) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, NOW())");
        $stmt->bind_param("siiisssss", $requestCode, $documentID, $userID, $studentID, 
        $dateRequest, $datePickUp, $authorizationImage, $nameOfReceiver, $remarks);
        
        if ($stmt->execute()) {
            $successCount++;
            $requestID = $conn->insert_id;
            $generatedRequestCodes[] = $requestCode;

            // Insert into supportingimage table if image was uploaded
            if (!empty($authorizationImage)) {
                $supNo = uniqid('SUP-');
                $insertImage = $conn->prepare("INSERT INTO supportingimage (supNo, requestID, image) VALUES (?, ?, ?)");
                $insertImage->bind_param("sis", $supNo, $requestID, $authorizationImage);

                if (!$insertImage->execute()) {
                    error_log("Error inserting into supportingimage: " . $insertImage->error);
                }
                $insertImage->close();
            }
        } else {
            error_log("Error inserting into RequestTable: " . $stmt->error);
        }
        $stmt->close();
    }

    if ($successCount > 0) {
        $message = "Successfully submitted $successCount document request(s).";
        if (!empty($duplicateDocuments)) {
            $message .= " Duplicates skipped: " . implode(', ', $duplicateDocuments);
        }
        $_SESSION['success'] = $message;
        $_SESSION['generatedCodes'] = $generatedRequestCodes; // 👉 Store the codes here
    } else {
        $_SESSION['error'] = "No requests processed. All selected documents have pending or in-process requests: " . implode(', ', $duplicateDocuments);
    }

    header("Location: request_form.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
       function updateProcessingTime() {
    const checkboxes = document.querySelectorAll('input[name="documentID[]"]:checked');
    let maxDays = 0;
    
    checkboxes.forEach(checkbox => {
        const days = parseInt(checkbox.dataset.procdays) || 0;
        if (days > maxDays) maxDays = days;
    });

    const procTimeElement = document.getElementById('processingTime');
    procTimeElement.innerHTML = maxDays > 0 
        ? Maximum processing time: ${maxDays} day(s) 
        : '';  // Ensure this is using backticks for template literal
}

    </script>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header text-center bg-primary text-white">
                        <h4>Submit a Request</h4>
                    </div>
                    <a href="../admin/my_request.php">View my Request</a>
                    <a href=""></a>

                    <div class="card-body">
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success">
                                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label class="form-label fw-bold">Select Documents:</label>
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
                                    ?>

                                     
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                        name="documentID[]" 
                                        value="<?php echo $doc['documentID']; ?>"
                                        data-procdays="<?php echo $procDays; ?>"
                                        id="doc<?php echo $doc['documentID']; ?>"
                                        onchange="updateProcessingTime()"
                                        <?php echo $doc['documentStatus'] === 'unavailable' ? 'disabled' : ''; ?>>
                                        <?php if ($doc['documentStatus'] === 'unavailable'): ?>
                                            <small class="text-danger ms-2">(Unavailable)</small>
                                        <?php endif; ?>



                                            <label class="form-check-label" for="doc<?php echo $doc['documentID']; ?>">
                                                <?php echo htmlspecialchars($doc['documentName']); ?>
                                                <span class="text-muted">(<?php echo $doc['procTime']; ?>)</span>
                                            </label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-2 text-muted" id="processingTime"></div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Student Number:</label>
                                <input type="text" name="studentNo" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">First Name:</label>
                                <input type="text" name="firstName" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Last Name:</label>
                                <input type="text" name="lastName" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Pick-up Date:</label>
                                <input type="date" name="datePickUp" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Name of Receiver:</label>
                                <input type="text" name="nameOfReceiver" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Remarks:</label>
                                <textarea name="remarks" class="form-control"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Authorization Image (optional):</label>
                                <input type="file" name="authorizationImage" class="form-control">
                                <small class="text-muted">Required if receiver name doesn't match student name</small>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Submit Request</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Request Code Modal -->
<div class="modal fade" id="requestCodeModal" tabindex="-1" aria-labelledby="requestCodeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-primary">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="requestCodeModalLabel">Request Submitted</h5>
      </div>
      <div class="modal-body">
        <p><strong>Your request code(s):</strong></p>
        <ul id="requestCodesList" class="list-group mb-3"></ul>
        <p class="text-danger"><strong>Please take a screenshot or picture of these codes. They are required when claiming your documents.</strong></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" data-bs-dismiss="modal">Okay, Got it!</button>
      </div>
    </div>
  </div>
</div>

</body>
</html>
<?php if (isset($_SESSION['generatedCodes'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const requestCodes = <?php echo json_encode($_SESSION['generatedCodes']); ?>;
        const list = document.getElementById('requestCodesList');
        requestCodes.forEach(code => {
            const li = document.createElement('li');
            li.className = 'list-group-item';
            li.textContent = code;
            list.appendChild(li);
        });
        const modal = new bootstrap.Modal(document.getElementById('requestCodeModal'));
        modal.show();
    });
</script>
<?php unset($_SESSION['generatedCodes']); endif; ?>
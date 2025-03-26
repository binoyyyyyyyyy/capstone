<?php
session_start();
require_once '../config/config.php'; // Database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch available documents
$documentQuery = $conn->query("SELECT documentID, documentName FROM DocumentsType WHERE dateDeleted IS NULL");
$documents = $documentQuery->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestCode = uniqid('REQ-');
    $documentID = $_POST['documentID'];
    $studentNo = $_POST['studentNo'];
    $dateRequest = date('Y-m-d');
    $datePickUp = $_POST['datePickUp'];
    $requestStatus = 'pending';
    $nameOfReceiver = $_POST['nameOfReceiver'];
    $remarks = $_POST['remarks'];
    $userID = $_SESSION['user_id'];

    // Validate if student exists and retrieve studentID
    $studentCheck = $conn->prepare("SELECT studentID FROM StudentInformation WHERE studentNo = ? AND dateDeleted IS NULL");
    $studentCheck->bind_param("s", $studentNo);
    $studentCheck->execute();
    $studentCheck->store_result();

    if ($studentCheck->num_rows == 0) {
        $_SESSION['error'] = "Student number does not exist or has been deleted.";
        header("Location: request_form.php");
        exit();
    }

    $studentCheck->bind_result($studentID);
    $studentCheck->fetch();
    $studentCheck->close();

    // Handle file upload
    $authorizationImage = NULL;
    if (!empty($_FILES['authorizationImage']['name'])) {
        $targetDir = "../uploads/";
        $fileName = time() . "_" . basename($_FILES['authorizationImage']['name']);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        
        // Allowed file types
        $allowedTypes = array('jpg', 'jpeg', 'png', 'pdf');
        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['authorizationImage']['tmp_name'], $targetFilePath)) {
                $authorizationImage = $fileName;
            } else {
                $_SESSION['error'] = "File upload failed.";
                header("Location: request_form.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, PNG, and PDF are allowed.";
            header("Location: request_form.php");
            exit();
        }
    }

    // Insert request into database
    $stmt = $conn->prepare("INSERT INTO RequestTable 
        (requestCode, documentID, userID, studentID, dateRequest, datePickUp, requestStatus, authorizationImage, nameOfReceiver, remarks, dateCreated) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("siiissssss", $requestCode, $documentID, $userID, $studentID, $dateRequest, $datePickUp, $requestStatus, $authorizationImage, $nameOfReceiver, $remarks);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Request submitted successfully!";
    } else {
        $_SESSION['error'] = "Error submitting request.";
    }
    $stmt->close();
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
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="form-container">
        <h2>Submit a Request</h2>
        <?php if (isset($_SESSION['success'])): ?>
            <p class="success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <p class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <label>Document:</label>
            <select name="documentID" required>
                <option value="">Select a Document</option>
                <?php foreach ($documents as $doc): ?>
                    <option value="<?php echo $doc['documentID']; ?>">
                        <?php echo htmlspecialchars($doc['documentName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label>Student Number:</label>
            <input type="text" name="studentNo" required>
            
            <label>Pick-up Date:</label>
            <input type="date" name="datePickUp" required>
            
            <label>Name of Receiver:</label>
            <input type="text" name="nameOfReceiver" required>
            
            <label>Remarks:</label>
            <textarea name="remarks"></textarea>
            
            <label>Authorization Image (optional):</label>
            <input type="file" name="authorizationImage">
            
            <button type="submit">Submit Request</button>
        </form>
    </div>
</body>
</html>

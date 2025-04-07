<?php
session_start();
require_once '../config/config.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'You must be logged in.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = [];

    // Collect form data
    $documentID = $_POST['documentID'] ?? null;
    $studentNo = $_POST['studentNo'] ?? null;
    $firstName = $_POST['firstName'] ?? null;
    $lastName = $_POST['lastName'] ?? null;
    $datePickUp = $_POST['datePickUp'] ?? null;
    $nameOfReceiver = $_POST['nameOfReceiver'] ?? null;
    $remarks = $_POST['remarks'] ?? null;

    if (empty($documentID)) {
        $response['error'] = 'Please select at least one document.';
        echo json_encode($response);
        exit();
    }

    // Validate student info
    $studentCheck = $conn->prepare("SELECT studentID FROM StudentInformation WHERE studentNo = ? AND firstName = ? AND lastName = ? AND dateDeleted IS NULL");
    $studentCheck->bind_param("sss", $studentNo, $firstName, $lastName);
    $studentCheck->execute();
    $studentCheck->store_result();

    if ($studentCheck->num_rows == 0) {
        $response['error'] = "The provided student information is incorrect.";
        echo json_encode($response);
        exit();
    }

    $studentCheck->bind_result($studentID);
    $studentCheck->fetch();
    $studentCheck->close();

    // Handle authorization image
    $authorizationImage = NULL;
    if (!empty($_FILES['authorizationImage']['name'])) {
        $targetDir = "../uploads/";
        $fileName = time() . "_" . basename($_FILES['authorizationImage']['name']);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        $allowedTypes = array('jpg', 'jpeg', 'png', 'pdf');
        if (in_array($fileType, $allowedTypes)) {
            if (!move_uploaded_file($_FILES['authorizationImage']['tmp_name'], $targetFilePath)) {
                $response['error'] = "File upload failed.";
                echo json_encode($response);
                exit();
            }
            $authorizationImage = $fileName;
        } else {
            $response['error'] = "Invalid file type. Only JPG, JPEG, PNG, and PDF are allowed.";
            echo json_encode($response);
            exit();
        }
    }

    // Get document details
    $documentDetails = [];
    $placeholders = str_repeat('?,', count($documentID) - 1) . '?';
    $stmt = $conn->prepare("SELECT documentID, documentName, procTime FROM DocumentsType WHERE documentID IN ($placeholders) AND documentStatus != 'Unavailable'");
    $stmt->bind_param(str_repeat('i', count($documentID)), ...$documentID);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $documentDetails[$row['documentID']] = $row;
    }
    $stmt->close();

    if (empty($documentDetails)) {
        $response['error'] = "All selected documents are unavailable.";
        echo json_encode($response);
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
    $minPickUpDate = (new DateTime())->modify("+$maxProcessingDays days")->format('Y-m-d');
    if ($datePickUp < $minPickUpDate) {
        $response['error'] = "Pick-up date must be at least $maxProcessingDays days from today.";
        echo json_encode($response);
        exit();
    }

    // Process each document request
    $successCount = 0;
    $duplicateDocuments = [];
    $generatedRequestCodes = [];

    foreach ($documentID as $docID) {
        $duplicateCheck = $conn->prepare("SELECT requestID FROM RequestTable WHERE studentID = ? AND documentID = ? AND requestStatus = 'pending'");
        $duplicateCheck->bind_param("ii", $studentID, $docID);
        $duplicateCheck->execute();
        $duplicateCheck->store_result();

        if ($duplicateCheck->num_rows > 0) {
            $duplicateDocuments[] = $documentDetails[$docID]['documentName'];
            continue;
        }
        $duplicateCheck->close();

        // Generate request code
        $requestCode = uniqid('REQ-') . '-' . $docID;

        // Insert into RequestTable
        $stmt = $conn->prepare("INSERT INTO RequestTable 
            (requestCode, documentID, userID, studentID, dateRequest, datePickUp, requestStatus, authorizationImage, nameOfReceiver, remarks, dateCreated) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, NOW())");
        $stmt->bind_param("siiisssss", $requestCode, $docID, $_SESSION['user_id'], $studentID, date('Y-m-d'), $datePickUp, $authorizationImage, $nameOfReceiver, $remarks);

        if ($stmt->execute()) {
            $successCount++;
            $generatedRequestCodes[] = $requestCode;
            $requestID = $conn->insert_id;

            // Insert into supportingimage table if image was uploaded
            if (!empty($authorizationImage)) {
                $supNo = uniqid('SUP-');
                $insertImage = $conn->prepare("INSERT INTO supportingimage (supNo, requestID, image) VALUES (?, ?, ?)");
                $insertImage->bind_param("sis", $supNo, $requestID, $authorizationImage);
                $insertImage->execute();
                $insertImage->close();
            }
        }
        $stmt->close();
    }

    if ($successCount > 0) {
        $response['success'] = "Successfully submitted $successCount document request(s).";
        if (!empty($duplicateDocuments)) {
            $response['duplicates'] = "Duplicates skipped: " . implode(', ', $duplicateDocuments);
        }
        $response['generatedCodes'] = $generatedRequestCodes;
    } else {
        $response['error'] = "No requests processed. All selected documents have pending or in-process requests.";
    }

    echo json_encode($response);
    exit();
}
?>

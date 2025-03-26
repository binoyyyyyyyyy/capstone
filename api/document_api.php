<?php
header("Content-Type: application/json");
require_once '../config/config.php'; // Database connection

$response = ["success" => false, "message" => "Invalid request."];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch all documents
    $stmt = $conn->prepare("SELECT * FROM DocumentTable");
    $stmt->execute();
    $result = $stmt->get_result();
    $documents = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $response = ["success" => true, "documents" => $documents];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add a new document
    $data = json_decode(file_get_contents("php://input"), true);
    if (!empty($data['documentName']) && !empty($data['documentType'])) {
        $stmt = $conn->prepare("INSERT INTO DocumentTable (documentName, documentType) VALUES (?, ?)");
        $stmt->bind_param("ss", $data['documentName'], $data['documentType']);
        if ($stmt->execute()) {
            $response = ["success" => true, "message" => "Document added successfully."];
        } else {
            $response = ["success" => false, "message" => "Error adding document."];
        }
        $stmt->close();
    } else {
        $response = ["success" => false, "message" => "Missing required fields."];
    }
}

echo json_encode($response);
?>

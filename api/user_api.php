<?php
header("Content-Type: application/json");
require_once '../config/config.php'; // Database connection

$response = ["success" => false, "message" => "Invalid request."];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch all users
    $stmt = $conn->prepare("SELECT userID, fullName, email, role_type FROM UserTable");
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $response = ["success" => true, "users" => $users];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add a new user
    $data = json_decode(file_get_contents("php://input"), true);
    if (!empty($data['fullName']) && !empty($data['email']) && !empty($data['password']) && !empty($data['role_type'])) {
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO UserTable (fullName, email, password, role_type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $data['fullName'], $data['email'], $hashedPassword, $data['role_type']);
        if ($stmt->execute()) {
            $response = ["success" => true, "message" => "User added successfully."];
        } else {
            $response = ["success" => false, "message" => "Error adding user."];
        }
        $stmt->close();
    } else {
        $response = ["success" => false, "message" => "Missing required fields."];
    }
}

echo json_encode($response);
?>
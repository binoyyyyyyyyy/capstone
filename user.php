<?php
// Database Connection
$host = 'localhost';
$dbname = 'schedulingmanagementdb';
$username = 'root';
$password = ''; // Change if needed
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// User details
$firstName = 'Marvin';
$middleName = 'Mateo';
$lastName = 'Bermosa';
$roleType = 'admin';
$email = 'admin@example.com';
$rawPassword = 'admin123'; // Change to desired password
$hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);
$userStatus = 'active';
$dateCreated = date('Y-m-d H:i:s');

// SQL Insert Query
$stmt = $conn->prepare("INSERT INTO UserTable (firstName, middleName, lastName, role_type, email, password, userStatus, dateCreated) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("ssssssss", $firstName, $middleName, $lastName, $roleType, $email, $hashedPassword, $userStatus, $dateCreated);

if ($stmt->execute()) {
    echo "✅ Admin user inserted successfully!";
} else {
    echo "❌ Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>

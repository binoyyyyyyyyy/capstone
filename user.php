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






// // Enable error reporting for debugging
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// // Set time zone (adjust as needed)
// date_default_timezone_set('America/Los_Angeles');

// // Database Connection
// $host = 'sql303.byethost31.com';
// $dbname = 'b31_39817005_databaseyea';
// $username = 'b31_39817005';
// $password = 'testing123!';

// $conn = new mysqli($host, $username, $password, $dbname);

// // Check connection
// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }

// // User details


// $firstName = 'Marvin';
// $middleName = 'Mateo';
// $lastName = 'Bermosa';
// $fullName = trim("$firstName $middleName $lastName"); // Combine names for fullName
// $roleType = 'admin';
// $email = 'admin@example.com';
// $rawPassword = 'admin123'; // Change to a stronger password in production
// $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);
// $userStatus = 'active';
// $addedBy = 'system'; // Optional: who added the user
// $dateCreated = date('Y-m-d H:i:s'); // e.g., 2025-08-29 19:57:00

// // Check if email already exists
// $stmt = $conn->prepare("SELECT email FROM UserTable WHERE email = ?");
// $stmt->bind_param("s", $email);
// $stmt->execute();
// if ($stmt->get_result()->num_rows > 0) {
//     echo "❌ Error: Email '$email' already exists.";
//     $stmt->close();
//     $conn->close();
//     exit;
// }
// $stmt->close();

// // SQL Insert Query with Prepared Statement
// $stmt = $conn->prepare("INSERT INTO UserTable (firstName, middleName, lastName, fullName, role_type, email, password, userStatus, added_by, dateCreated) 
// VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
// $stmt->bind_param("ssssssssss", $firstName, $middleName, $lastName, $fullName, $roleType, $email, $hashedPassword, $userStatus, $addedBy, $dateCreated);

// if ($stmt->execute()) {
//     echo "✅ Admin user inserted successfully!";
// } else {
//     if ($stmt->errno == 1062) { // MySQL error code for duplicate entry
//         echo "❌ Error: Email '$email' already exists in the database.";
//     } else {
//         echo "❌ Error: " . $stmt->error;
//     }
// }

// $stmt->close();
// $conn->close();



?>

 
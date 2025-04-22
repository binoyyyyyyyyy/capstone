<?php
session_start();
header('Content-Type: application/json');

$response = [];

if (isset($_SESSION['error'])) {
    $response['error'] = $_SESSION['error'];
    unset($_SESSION['error']);
} elseif (isset($_SESSION['message'])) {
    $response['message'] = $_SESSION['message'];
    unset($_SESSION['message']);
}

echo json_encode($response);
?>
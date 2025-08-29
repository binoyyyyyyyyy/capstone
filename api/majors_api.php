<?php
session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isset($_GET['course_ID'])) {
    echo json_encode([]);
    exit();
}

$courseId = (int)$_GET['course_ID'];

// Get course name
$cstmt = $conn->prepare("SELECT courseName FROM coursetable WHERE courseID = ?");
$cstmt->bind_param("i", $courseId);
$cstmt->execute();
$cresult = $cstmt->get_result();
if ($cresult->num_rows === 0) {
    echo json_encode([]);
    exit();
}
$courseName = $cresult->fetch_assoc()['courseName'];
$cstmt->close();

// Map course name to allowed major names
$allowedMap = [
    'BEED' => ['General Education', 'Early Childhood Education'],
    'BSBA' => ['Marketing Management', 'Financial Management'],
    'BSIT' => ['DATABASE', 'WEB SYSTEM TECHNOLOGY']
];

if (!isset($allowedMap[$courseName])) {
    echo json_encode([]);
    exit();
}

$allowedMajors = $allowedMap[$courseName];

// Build dynamic placeholders for IN clause
$placeholders = implode(',', array_fill(0, count($allowedMajors), '?'));
$types = str_repeat('s', count($allowedMajors));

$sql = "SELECT majorID, majorName FROM majortable WHERE majorName IN ($placeholders) ORDER BY majorName ASC";
$mstmt = $conn->prepare($sql);
$mstmt->bind_param($types, ...$allowedMajors);
$mstmt->execute();
$mresult = $mstmt->get_result();

$majors = [];
while ($row = $mresult->fetch_assoc()) {
    $majors[] = [
        'majorID' => (int)$row['majorID'],
        'majorName' => $row['majorName']
    ];
}
$mstmt->close();

echo json_encode($majors);
exit();
?>



<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/config.php';

$type = $_POST['type'] ?? '';
$date = $_POST['date'] ?? '';

if (!$type || !$date) {
    exit;
}

// Build date range
$start = $end = '';
if ($type === 'daily') {
    $start = $date . ' 00:00:00';
    $end = $date . ' 23:59:59';
} elseif ($type === 'weekly') {
    $dt = new DateTime($date);
    $dt->modify('Monday this week');
    $start = $dt->format('Y-m-d') . ' 00:00:00';
    $dt->modify('Sunday this week');
    $end = $dt->format('Y-m-d') . ' 23:59:59';
} elseif ($type === 'monthly') {
    $start = $date . '-01 00:00:00';
    $dt = new DateTime($start);
    $dt->modify('last day of this month');
    $end = $dt->format('Y-m-d') . ' 23:59:59';
} else {
    exit;
}

// Query
$sql = "SELECT 
            r.requestID, 
            s.firstName, 
            s.lastName, 
            d.documentName, 
            r.requestStatus, 
            r.dateRequest
        FROM requesttable r
        JOIN studentinformation s ON r.studentID = s.studentID
        JOIN documentstype d ON r.documentID = d.documentID
        WHERE r.dateRequest BETWEEN ? AND ?
        ORDER BY r.dateRequest DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $start, $end);
$stmt->execute();
$result = $stmt->get_result();

// Output CSV headers
$filename = "requests_report_{$type}_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"$filename\"");

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV header
fputcsv($output, ['Request ID', 'Student Name', 'Document Type', 'Status', 'Date Requested']);

// Write data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['requestID'],
        $row['firstName'] . ' ' . $row['lastName'],
        $row['documentName'],
        $row['requestStatus'],
        $row['dateRequest']
    ]);
}

fclose($output);
$stmt->close();
exit;


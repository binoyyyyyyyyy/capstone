<?php
// Alternative report generation method for Byethost compatibility
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/config.php';

// Check if session is already started (config.php already starts it)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$type = $_POST['type'] ?? '';
$date = $_POST['date'] ?? '';

if (!$type || !$date) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
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
    http_response_code(400);
    echo json_encode(['error' => 'Invalid report type']);
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
        FROM RequestTable r
        JOIN StudentInformation s ON r.studentID = s.studentID
        JOIN DocumentsType d ON r.documentID = d.documentID
        WHERE r.dateRequest BETWEEN ? AND ?
        ORDER BY r.dateRequest DESC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query preparation failed']);
    exit;
}

$stmt->bind_param('ss', $start, $end);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query execution failed']);
    exit;
}

$result = $stmt->get_result();

// Create temporary file
$filename = "requests_report_{$type}_" . date('Ymd_His') . ".csv";
$tempFile = sys_get_temp_dir() . '/' . $filename;

// Try to create file in temp directory, fallback to current directory
if (!is_writable(sys_get_temp_dir())) {
    $tempFile = '../uploads/' . $filename;
    if (!is_dir('../uploads/')) {
        mkdir('../uploads/', 0755, true);
    }
}

$output = fopen($tempFile, 'w');
if (!$output) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not create temporary file']);
    exit;
}

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

// Clear any previous output
if (ob_get_level()) {
    ob_end_clean();
}

// Output file with proper headers
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
header('Pragma: no-cache');
header('Content-Length: ' . filesize($tempFile));

// Output file content
readfile($tempFile);

// Clean up temporary file
unlink($tempFile);
exit;

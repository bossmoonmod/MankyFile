<?php
require_once 'config.php';

// Authenticate
$headers = getallheaders();
$key = isset($headers['X-API-KEY']) ? $headers['X-API-KEY'] : ($_GET['key'] ?? ''); // Allow query param for easier download
if ($key !== API_KEY) {
    die("Access Denied");
}

$task_id = $_GET['task_id'] ?? '';
$file_path = OUTPUT_DIR . $task_id . '_unlocked.pdf';

if (file_exists($file_path)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="unlocked_document.pdf"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    exit;
} else {
    die("File not found or processing not complete.");
}
?>

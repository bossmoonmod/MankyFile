<?php
require_once 'config.php';
require_once 'db.php';

// Authenticate
$headers = getallheaders();
$key = isset($headers['X-API-KEY']) ? $headers['X-API-KEY'] : ($_GET['key'] ?? '');
if ($key !== API_KEY) {
    die("Access Denied");
}

$task_id = $_GET['task_id'] ?? '';
if (!$task_id) die("No Task ID");

// Get Task Info form DB
$db = getDB();
$stmt = $db->prepare("SELECT * FROM tasks WHERE id = :id");
$stmt->execute([':id' => $task_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) die("Task not found");

// Determine File Path & Name
$file_ext = 'pdf';
$mime_type = 'application/pdf';

if ($task['type'] == 'pdf-to-ppt') {
    $file_ext = 'pptx';
    $mime_type = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
} elseif ($task['type'] == 'pdf-to-excel') {
    $file_ext = 'xlsx';
    $mime_type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
}

// Logic: Converted files use same ID, Unlocked files have _unlocked suffix? 
// Actually, let's look at the worker logic.
// worker.py saves as: 
// unlock -> {task_id}_unlocked.pdf
// conversion -> {task_id}.pptx / .xlsx

$filename_on_disk = $task_id . '.' . $file_ext;
if ($task['type'] == 'unlock') {
    $filename_on_disk = $task_id . '_unlocked.pdf';
}

$file_path = OUTPUT_DIR . $filename_on_disk;

// Custom Download Name
$download_name = "processed_document." . $file_ext;
if (isset($_GET['name']) && !empty($_GET['name'])) {
    // Sanitize: Allow Unicode Letters, Numbers, AND Marks (Vital for Thai vowels/tones), spaces, underscore, dash
    $clean_name = preg_replace('/[^\p{L}\p{N}\p{M}_\- ]/u', '', $_GET['name']); 
    if (!empty($clean_name)) {
        $download_name = $clean_name . '.' . $file_ext;
    }
}

if (file_exists($file_path)) {
    // Found exact match
    serveFile($file_path, $download_name, $mime_type);
} else {
    // 🔍 Fallback: Try to find ANY file with this Task ID
    // (Fix for cases where DB type is wrong/empty but file exists)
    $candidates = [
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'pdf'  => 'application/pdf'
    ];

    foreach ($candidates as $ext => $mime) {
        $possible_paths = [
            OUTPUT_DIR . $task_id . '.' . $ext,             // Standard
            OUTPUT_DIR . $task_id . '_processed.' . $ext    // API Generated
        ];

        foreach ($possible_paths as $check_path) {
            if (file_exists($check_path)) {
                // Found it! Use this instead
                $new_name = str_replace(['.pdf', '.pptx', '.xlsx'], '.' . $ext, $download_name);
                serveFile($check_path, $new_name, $mime);
            }
        }
    }
    
    // Also check for unlocked format
    $unlocked_path = OUTPUT_DIR . $task_id . '_unlocked.pdf';
    if (file_exists($unlocked_path)) {
        serveFile($unlocked_path, str_replace(['.pptx','.xlsx'], '.pdf', $download_name), 'application/pdf');
    }

    die("File not found on server. Task ID: " . htmlspecialchars($task_id));
}

function serveFile($path, $name, $mime) {
    if (!file_exists($path)) die("File found in logic but missing on disk.");

    $encoded_name = rawurlencode($name);

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime);
    // Support UTF-8 Filenames in modern browsers
    header("Content-Disposition: attachment; filename=\"$name\"; filename*=UTF-8''$encoded_name");
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}
?>

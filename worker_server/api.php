<?php
require_once 'db.php';

// 1. Authenticate
// 1. Authenticate (Robust for Nginx/CloudPanel)
$headers = function_exists('getallheaders') ? getallheaders() : [];
$key = '';
if (isset($headers['X-API-KEY'])) $key = $headers['X-API-KEY'];
elseif (isset($_SERVER['HTTP_X_API_KEY'])) $key = $_SERVER['HTTP_X_API_KEY'];
elseif (isset($_GET['key'])) $key = $_GET['key'];
if ($key !== API_KEY) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid API Key']);
    exit;
}

// 2. Handle Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $job_id = uniqid('task_');
    $task_type = $_POST['type'] ?? 'unlock'; // Default to unlock if not specified
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $input_path = UPLOAD_DIR . $job_id . '.' . $ext;
    
    if (move_uploaded_file($file['tmp_name'], $input_path)) {
        // Save to DB
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO tasks (id, status, original_filename, type) VALUES (?, 'pending', ?, ?)");
        // Note: You might need to add 'type' column to DB manually or just ignore it in DB for now
        // For simplicity, we skip saving 'type' to DB unless you want to alter table. 
        // Let's stick to existing schema (status, original_filename) or just allow 'type' if you add column.
        // Assuming current DB schema doesn't have 'type', we'll rely on the Python script to know what to do.
        $stmt->execute([$job_id, $file['name'], $task_type]);
        
        // 3. Trigger Python Worker in Background
        // New Command: worker.py [input] [output] [job_id] [type]
        $output_ext = '.pdf'; // Default
        if ($task_type === 'pdf-to-ppt') $output_ext = '.pptx';
        if ($task_type === 'pdf-to-excel') $output_ext = '.xlsx';
        if ($task_type === 'pdf-to-word') $output_ext = '.docx';
        if ($task_type === 'pdf-to-image') $output_ext = '.zip';
        if ($task_type === 'image-to-pdf') $output_ext = '.pdf';
        if ($task_type === 'image-resize') $output_ext = '.jpg';
        if ($task_type === 'image-convert') {
            $format = $_POST['target_format'] ?? 'webp';
            $output_ext = '.' . $format;
        }
        
        $output_path = OUTPUT_DIR . $job_id . '_processed' . $output_ext;
        
        $script = __DIR__ . '/worker.py';
        
        // Escape args
        $cmd = sprintf(
            '%s %s %s %s %s %s > /dev/null 2>&1 &',
            PYTHON_PATH,
            escapeshellarg($script),
            escapeshellarg($input_path),
            escapeshellarg($output_path),
            escapeshellarg($job_id),
            escapeshellarg($task_type) // Pass type to Python
        );
        
        // Execute (Windows/Linux compatible fallback logic)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            pclose(popen("start /B " . $cmd, "r"));
        } else {
            exec($cmd); 
        }

        echo json_encode(['status' => 'ok', 'task_id' => $job_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save file']);
    }
} else {
    echo json_encode(['error' => 'No file uploaded']);
}
?>

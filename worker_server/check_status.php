<?php
require_once 'db.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$task_id = $_GET['task_id'] ?? '';

if (!$task_id) {
    echo json_encode(['error' => 'Missing task_id']);
    exit;
}

$db = getDB();

// Update Heartbeat (Signal that user is still watching)
$hStmt = $db->prepare("UPDATE tasks SET last_heartbeat = (DATETIME('now', 'localtime')) WHERE id = ?");
$hStmt->execute([$task_id]);

// Explicit Cancellation
if (isset($_GET['cancel']) && $_GET['cancel'] == '1') {
    $cStmt = $db->prepare("UPDATE tasks SET status = 'abandoned' WHERE id = ? AND status IN ('processing', 'pending')");
    $cStmt->execute([$task_id]);
    echo json_encode(['status' => 'abandoned', 'message' => 'Task cancelled by user']);
    exit;
}

// Handle Abandoned logic: 120 seconds of no heartbeat = dead task
$db->exec("UPDATE tasks SET status = 'failed' WHERE status = 'processing' AND (julianday('now') - julianday(last_heartbeat)) * 86400 > 600");

$stmt = $db->prepare("SELECT id, status, type, password_found FROM tasks WHERE id = ?");
$stmt->execute([$task_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if ($task) {
    echo json_encode($task);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Task not found']);
}
?>

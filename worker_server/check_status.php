<?php
require_once 'db.php';

$task_id = $_GET['task_id'] ?? '';

if (!$task_id) {
    echo json_encode(['error' => 'Missing task_id']);
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT id, status, password_found FROM tasks WHERE id = ?");
$stmt->execute([$task_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if ($task) {
    echo json_encode($task);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Task not found']);
}
?>

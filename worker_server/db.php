<?php
require_once 'config.php';

function getDB() {
    $db = new PDO('sqlite:' . DB_FILE);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create Table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS tasks (
        id TEXT PRIMARY KEY,
        status TEXT DEFAULT 'pending', -- pending, processing, completed, failed
        original_filename TEXT,
        password_found TEXT,
        type TEXT DEFAULT 'unlock',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // 🛠️ Auto-Migrate: Check if 'type' column exists (For existing users)
    $cols = $db->query("PRAGMA table_info(tasks)")->fetchAll(PDO::FETCH_ASSOC);
    $hasType = false;
    foreach ($cols as $col) {
        if ($col['name'] === 'type') {
            $hasType = true; 
            break;
        }
    }
    
    if (!$hasType) {
        try {
            $db->exec("ALTER TABLE tasks ADD COLUMN type TEXT DEFAULT 'unlock'");
        } catch (Exception $e) {
            // Ignore error if column already added
        }
    }
    
    return $db;
}
?>

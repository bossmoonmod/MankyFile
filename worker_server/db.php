<?php
require_once 'config.php';
require_once 'db.php';

function getDB() {
    $db = new PDO('sqlite:' . DB_FILE);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("PRAGMA journal_mode=WAL;"); // Faster concurrent access
    
    // Create Tables
    $db->exec("CREATE TABLE IF NOT EXISTS tasks (
        id TEXT PRIMARY KEY,
        status TEXT DEFAULT 'pending',
        original_filename TEXT,
        type TEXT,
        password_found TEXT,
        last_heartbeat DATETIME DEFAULT (DATETIME('now', 'localtime')),
        created_at DATETIME DEFAULT (DATETIME('now', 'localtime')),
        updated_at DATETIME DEFAULT (DATETIME('now', 'localtime'))
    )");

    // Auto-migrate columns
    $columns = ['last_heartbeat', 'type', 'password_found', 'original_filename'];
    foreach ($columns as $col) {
        try { $db->exec("ALTER TABLE tasks ADD COLUMN $col TEXT"); } catch (Exception $e) {}
    }

    $db->exec("CREATE TABLE IF NOT EXISTS notes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT,
        content TEXT,
        updated_at DATETIME DEFAULT (DATETIME('now', 'localtime'))
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS access_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip_address TEXT,
        user_agent TEXT,
        accessed_at DATETIME DEFAULT (DATETIME('now', 'localtime'))
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS login_brute_force (
        ip_address TEXT PRIMARY KEY,
        attempts INTEGER DEFAULT 0,
        last_attempt DATETIME,
        is_locked INTEGER DEFAULT 0
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS hosted_images (
        id TEXT PRIMARY KEY,
        filename TEXT,
        original_name TEXT,
        mime_type TEXT,
        auto_delete_at DATETIME,
        created_at DATETIME DEFAULT (DATETIME('now', 'localtime'))
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS short_links (
        id TEXT PRIMARY KEY,
        long_url TEXT,
        short_code TEXT UNIQUE,
        clicks INTEGER DEFAULT 0,
        expires_at DATETIME,
        created_at DATETIME DEFAULT (DATETIME('now', 'localtime'))
    )");
    
    return $db;
}
?>

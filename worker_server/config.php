<?php
// Config สำหรับ Worker Server
define('API_KEY', 'MANKY_SECRET_KEY_12345'); // **เปลี่ยน key นี้ให้ตรงกับใน Django**
define('UPLOAD_DIR', __DIR__ . '/storage/uploads/');
define('OUTPUT_DIR', __DIR__ . '/storage/processed/');
define('DB_FILE', __DIR__ . '/db/tasks.sqlite');
define('PYTHON_PATH', 'python3'); // หรือ path เต็ม เช่น /usr/bin/python3

// สร้าง Folder ถ้ายังไม่มี
if (!file_exists(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);
if (!file_exists(OUTPUT_DIR)) mkdir(OUTPUT_DIR, 0777, true);
if (!file_exists(__DIR__ . '/db')) mkdir(__DIR__ . '/db', 0777, true);
?>

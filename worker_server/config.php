<?php
// Config สำหรับ Worker Server
date_default_timezone_set('Asia/Bangkok');
define('MASTER_PIN', '2547'); // ** CHANGE YOUR PIN HERE **
define('MASTER_RECOVERY_KEY', 'MANKY-ADMIN-SUPER-SAFE-99');
define('API_KEY', 'MANKY_SECRET_KEY_12345'); 
define('UPLOAD_DIR', __DIR__ . '/storage/uploads/');
define('OUTPUT_DIR', __DIR__ . '/storage/processed/');
define('DB_FILE', __DIR__ . '/db/tasks.sqlite');
define('HOSTED_DIR', __DIR__ . '/storage/hosted/');
define('PYTHON_PATH', 'python3'); // หรือ path เต็ม เช่น /usr/bin/python3

// สร้าง Folder ถ้ายังไม่มี
if (!file_exists(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);
if (!file_exists(OUTPUT_DIR)) mkdir(OUTPUT_DIR, 0777, true);
if (!file_exists(HOSTED_DIR)) mkdir(HOSTED_DIR, 0777, true);
if (!file_exists(__DIR__ . '/db')) mkdir(__DIR__ . '/db', 0777, true);
?>

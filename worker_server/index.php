<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MankyWorker Node - Status</title>
    <style>
        :root {
            --bg: #0f1115;
            --card: #161b22;
            --text: #c9d1d9;
            --green: #238636;
            --red: #da3633;
            --border: #30363d;
        }
        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .dashboard {
            background: var(--card);
            padding: 40px;
            border-radius: 12px;
            border: 1px solid var(--border);
            width: 100%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        h1 { margin-top: 0; font-size: 1.5rem; border-bottom: 1px solid var(--border); padding-bottom: 15px; }
        .status-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid var(--border);
        }
        .status-row:last-child { border-bottom: none; }
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: bold;
        }
        .badge.ok { background: rgba(35, 134, 54, 0.2); color: #3fb950; border: 1px solid rgba(35, 134, 54, 0.5); }
        .badge.err { background: rgba(218, 54, 51, 0.2); color: #f85149; border: 1px solid rgba(218, 54, 51, 0.5); }
        .footer { margin-top: 20px; text-align: center; font-size: 0.8rem; color: #8b949e; }
        .blink { animation: blinker 1.5s linear infinite; }
        @keyframes blinker { 50% { opacity: 0; } }
    </style>
</head>
<body>

<div class="dashboard">
    <h1>🚀 MankyWorker Node</h1>

    <!-- 1. API Status -->
    <div class="status-row">
        <span>API Endpoint</span>
        <span class="badge ok">ACTIVE</span>
    </div>

    <!-- 2. Python Check -->
    <?php
    require_once 'config.php';
    $py_ver = shell_exec(PYTHON_PATH . " --version 2>&1");
    $py_ok = (strpos($py_ver, 'Python') !== false);
    ?>
    <div class="status-row">
        <span>Python Engine</span>
        <?php if($py_ok): ?>
            <span class="badge ok"><?php echo htmlspecialchars(trim($py_ver)); ?></span>
        <?php else: ?>
            <span class="badge err">NOT FOUND (<?php echo PYTHON_PATH; ?>)</span>
        <?php endif; ?>
    </div>

    <!-- 3. PikePDF Library Check -->
    <?php
    $lib_check = shell_exec(PYTHON_PATH . " -c \"import pikepdf; print('OK')\" 2>&1");
    $lib_ok = (trim($lib_check) == 'OK');
    ?>
    <div class="status-row">
        <span>Library (pikepdf)</span>
        <?php if($lib_ok): ?>
            <span class="badge ok">INSTALLED</span>
        <?php else: ?>
            <span class="badge err">MISSING</span>
        <?php endif; ?>
    </div>

    <!-- 4. Storage Write Check -->
    <?php
    $write_ok = is_writable(UPLOAD_DIR) && is_writable(OUTPUT_DIR);
    ?>
    <div class="status-row">
        <span>Storage Permission</span>
        <?php if($write_ok): ?>
            <span class="badge ok">WRITABLE</span>
        <?php else: ?>
            <span class="badge err">READ ONLY</span>
        <?php endif; ?>
    </div>

     <!-- 5. Connection -->
     <div class="status-row">
        <span>Node Connect</span>
        <span class="badge ok blink">Listinening...</span>
    </div>

    <div class="footer">
        System Ready | MankyFile Distributed System
    </div>
</div>

</body>
</html>

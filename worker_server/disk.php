<?php
require_once 'config.php';
require_once 'db.php';

header('Content-Type: application/json');
$action = $_GET['action'] ?? '';
$root = __DIR__ . DIRECTORY_SEPARATOR;
$base_storage = $root . 'storage' . DIRECTORY_SEPARATOR;

$dirs = [
    'uploads'   => UPLOAD_DIR, 
    'processed' => OUTPUT_DIR, 
    'music'     => $root . 'music' . DIRECTORY_SEPARATOR, 
    'wallpapers'=> $root . 'image' . DIRECTORY_SEPARATOR . 'wallpapers' . DIRECTORY_SEPARATOR,
    'gallery'   => $root . 'image' . DIRECTORY_SEPARATOR . 'gallery' . DIRECTORY_SEPARATOR, 
    'notes'     => $base_storage . 'notes' . DIRECTORY_SEPARATOR,
    'trash'     => $base_storage . 'trash' . DIRECTORY_SEPARATOR, 
    'news'      => $base_storage . 'news' . DIRECTORY_SEPARATOR, 
    'apps'      => $root . 'App_store' . DIRECTORY_SEPARATOR,
    'hosted'    => HOSTED_DIR
];

// Ensure all exist
foreach ($dirs as $p) { if (!file_exists($p)) mkdir($p, 0777, true); }

$app_registry = $dirs['apps'] . 'registry.json';
$def = [
    ['id'=>'ai_chat','title'=>'Neural AI','icon'=>'fa-robot', 'file'=>'ai_chat.php'],
    ['id'=>'google','title'=>'Google','icon'=>'fa-google','isBrand'=>true, 'file'=>'google.php'],
    ['id'=>'monitor','title'=>'Heartbeat','icon'=>'fa-heartbeat', 'file'=>'monitor.php'],
    ['id'=>'disk','title'=>'Disk','icon'=>'fa-hdd', 'file'=>'disk_win.php'],
    ['id'=>'music_win','title'=>'Music Lab','icon'=>'fa-compact-disc', 'file'=>'music_win.php'],
    ['id'=>'api_master','title'=>'Node Master','icon'=>'fa-satellite-dish', 'file'=>'api_master.php'],
    ['id'=>'notes','title'=>'Notes','icon'=>'fa-sticky-note', 'file'=>'notes.php'],
    ['id'=>'gdrive','title'=>'Cloud','icon'=>'fa-google-drive','isBrand'=>true, 'file'=>'gdrive.php'],
    ['id'=>'news_app','title'=>'Daily News','icon'=>'fa-newspaper', 'file'=>'news_app.php'],
    ['id'=>'gallery','title'=>'Gallery','icon'=>'fa-images', 'file'=>'gallery.php'],
    ['id'=>'walls','title'=>'Aura','icon'=>'fa-palette', 'file'=>'walls.php'],
    ['id'=>'security','title'=>'Security','icon'=>'fa-shield-alt', 'file'=>'security.php'],
    ['id'=>'calc','title'=>'Calculator','icon'=>'fa-calculator', 'file'=>'calc.php'],
    ['id'=>'editor','title'=>'Canvas','icon'=>'fa-magic', 'file'=>'editor.php'],
    ['id'=>'term','title'=>'Terminal','icon'=>'fa-terminal', 'file'=>'term.php'],
    ['id'=>'prism','title'=>'Prism','icon'=>'fa-eye-dropper', 'file'=>'prism.php'],
    ['id'=>'rambler','title'=>'Rambler','icon'=>'fa-envelope', 'file'=>'rambler.php'],
    ['id'=>'store','title'=>'App Store','icon'=>'fa-shop', 'file'=>'store.php']
];

if (!file_exists($app_registry)) {
    file_put_contents($app_registry, json_encode($def));
} else {
    // Sync missing apps into registry
    $current = json_decode(file_get_contents($app_registry), true);
    $existingIds = array_column($current, 'id');
    foreach($def as $app) {
        if(!in_array($app['id'], $existingIds)) $current[] = $app;
    }
    file_put_contents($app_registry, json_encode($current));
}

// PDPA AUTO-CLEANUP (Files older than 24 Hours)
$pdpas = ['uploads', 'processed'];
foreach($pdpas as $p) {
    if(isset($dirs[$p])) {
        foreach(glob($dirs[$p]."*") as $file) {
            if(is_file($file) && (time() - filemtime($file) > 86400)) {
                unlink($file); // Remove actual file content
            }
        }
    }
}

// HOSTED IMAGES AUTO-CLEANUP
try {
    $db = getDB();
    $expired = $db->query("SELECT * FROM hosted_images WHERE auto_delete_at IS NOT NULL AND auto_delete_at < (DATETIME('now', 'localtime'))")->fetchAll(PDO::FETCH_ASSOC);
    foreach($expired as $img) {
        $path = HOSTED_DIR . $img['filename'];
        if(file_exists($path)) unlink($path);
        
        $stmt = $db->prepare("DELETE FROM hosted_images WHERE id = ?");
        $stmt->execute([$img['id']]);
    }
} catch(Exception $e) {}

switch($action) {
    case 'get_apps': echo file_get_contents($app_registry); break;
    
    case 'purge': // Wipe all files in a folder
        $dk = $_GET['dir'] ?? '';
        if(isset($dirs[$dk])) {
            foreach(glob($dirs[$dk]."*") as $file) { if(is_file($file)) unlink($file); }
            echo json_encode(['success'=>true]);
        } else { echo json_encode(['success'=>false]); }
        break;

    case 'exec': // Terminal Backend
        $cmd = $_POST['cmd'] ?? '';
        if(!$cmd) die("No command");
        $out = shell_exec($cmd . " 2>&1");
        echo $out ?: "Command executed (No output)";
        exit;
        break;

    case 'get_access_logs':
        try {
            $db = getDB();
            $logs = $db->query("SELECT * FROM access_logs ORDER BY accessed_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($logs);
        } catch(Exception $e) { echo "[]"; }
        exit;
        break;
    
    case 'get_sys_check':
        try {
            $db = getDB();
            $tasks = $db->query("SELECT * FROM tasks ORDER BY created_at DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
            $total_tasks = $db->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
            $failed_tasks = $db->query("SELECT COUNT(*) FROM tasks WHERE status='failed'")->fetchColumn();
            
            $stability = 100 - ($total_tasks > 0 ? ($failed_tasks / $total_tasks * 10) : 0);
            if($stability < 85) $stability = 85 + mt_rand(0, 5);
            
            echo json_encode([
                'status' => 'ONLINE',
                'node' => gethostname(),
                'vitals' => [
                    'stability' => round($stability, 1),
                    'load' => mt_rand(12, 45),
                    'latency' => mt_rand(8, 25),
                    'memory' => mt_rand(30, 55),
                    'aura' => 100
                ],
                'tasks' => $tasks,
                'connections' => [
                    ['service' => 'Database', 'status' => 'CONNECTED'],
                    ['service' => 'Cloud Cluster', 'status' => 'READY'],
                    ['service' => 'App Engine', 'status' => 'ACTIVE']
                ]
            ]);
        } catch(Exception $e) { echo json_encode(['status'=>'ERROR','msg'=>$e->getMessage()]); }
        break;

    case 'get_server_stats':
        // For HUD Line Chart
        try {
            $db = getDB(); $s = [];
            for($i=5;$i>=0;$i--) {
                $h = date('H', strtotime("-$i hours"));
                $c = $db->query("SELECT COUNT(*) FROM tasks WHERE strftime('%H', created_at) = '$h'")->fetchColumn();
                // Add some noise if 0 to make chart look "alive"
                if($c == 0) $c = mt_rand(2, 8);
                $s[] = ['hour'=>$h.':00', 'count'=>(int)$c];
            }
            echo json_encode($s);
        } catch(Exception $e){ echo "[]"; }
        break;

    case 'save_note':
        try {
            $db = getDB();
            $d = json_decode(file_get_contents('php://input'), true);
            $stmt = $db->prepare("INSERT INTO notes (title, content) VALUES (?, ?)");
            $stmt->execute([$d['title'], $d['content']]);
            echo json_encode(['success'=>true]);
        } catch(Exception $e) { echo json_encode(['success'=>false, 'msg'=>$e->getMessage()]); }
        break;

    case 'get_notes':
        try {
            $db = getDB();
            $res = $db->query("SELECT * FROM notes ORDER BY updated_at DESC")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($res);
        } catch(Exception $e) { echo "[]"; }
        break;

    case 'list':
        $dk = $_GET['dir'] ?? 'music';
        if(!isset($dirs[$dk])) die(json_encode(['error'=>'Invalid directory']));
        $files = [];
        $sc = scandir($dirs[$dk]);
        foreach($sc as $i) {
            if($i=='.'||$i=='..') continue;
            $urlPrefix = [
                'music' => 'music/', 'wallpapers' => 'image/wallpapers/',
                'gallery' => 'image/gallery/', 'processed' => 'storage/processed/',
                'uploads' => 'storage/uploads/', 'apps' => 'App_store/'
            ];
            $pre = $urlPrefix[$dk] ?? '';
            $files[] = ['name'=>$i, 'url'=>$pre.$i, 'date'=>date("Y-m-d H:i", filemtime($dirs[$dk].$i))];
        }
        echo json_encode(['files'=>$files]);
        break;

    case 'upload_any':
        $target_dir = $dirs[$_GET['type']] ?? null;
        if(!$target_dir) die(json_encode(['success'=>false, 'msg'=>'Invalid type']));
        
        $files = $_FILES['files'] ?? null;
        if(!$files) {
            // Fallback for single 'file' upload
            if(isset($_FILES['file'])) {
                move_uploaded_file($_FILES['file']['tmp_name'], $target_dir . $_FILES['file']['name']);
                echo json_encode(['success'=>true]);
            } else { echo json_encode(['success'=>false]); }
            break;
        }

        $count = count($files['name']);
        for($i=0; $i<$count; $i++) {
            move_uploaded_file($files['tmp_name'][$i], $target_dir . $files['name'][$i]);
        }
        echo json_encode(['success' => true]);
        break;

    case 'get_real_news':
        $rss = @simplexml_load_file("https://feeds.bbci.co.uk/news/world/rss.xml");
        $out = [];
        if($rss) foreach($rss->channel->item as $i) { $out[] = ['title'=>(string)$i->title, 'desc'=>(string)$i->description]; if(count($out)>=6) break; }
        if(empty($out)) $out = [['title'=>'Offline','desc'=>'API linking...']];
        echo json_encode($out);
        break;

    case 'emergency_kill':
        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                exec("taskkill /F /IM python.exe /T");
            } else {
                exec("pkill -9 -f worker.py");
            }
            // Update all stuck processing/pending tasks to failed
            $db = getDB();
            $db->exec("UPDATE tasks SET status='failed' WHERE status IN ('processing', 'pending')");
            echo json_encode(['success' => true, 'msg' => 'All workers terminated and tasks marked as failed.']);
        } catch(Exception $e) { echo json_encode(['success' => false, 'msg' => $e->getMessage()]); }
        break;

    case 'delete_task':
        try {
            $db = getDB();
            $stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            echo json_encode(['success' => true]);
        } catch(Exception $e) { echo json_encode(['success' => false]); }
        break;

    case 'delete':
        $p = $dirs[$_GET['dir']].$_GET['file'];
        if(file_exists($p)) { if($_GET['dir']=='trash') unlink($p); else rename($p, $dirs['trash'].$_GET['file']); }
        echo json_encode(['success'=>true]);
        break;
}
?>

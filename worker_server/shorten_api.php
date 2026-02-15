<?php
require_once 'config.php';
require_once 'db.php';

// Handle Redirection if 'code' is provided as GET parameter
if (isset($_GET['code']) && !isset($_GET['action'])) {
    $code = $_GET['code'];
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM short_links WHERE short_code = ?");
        $stmt->execute([$code]);
        $link = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($link) {
            // Check expiry
            if ($link['expires_at'] && strtotime($link['expires_at']) < time()) {
                http_response_code(410);
                echo "Link Expired";
                exit;
            }
            
            // Increment clicks
            $stmt = $db->prepare("UPDATE short_links SET clicks = clicks + 1 WHERE short_code = ?");
            $stmt->execute([$code]);
            
            header("Location: " . $link['long_url']);
            exit;
        }
    } catch (Exception $e) {}
    http_response_code(404);
    echo "Link Not Found";
    exit;
}

// API Actions
header('Content-Type: application/json');

// Authenticate
$headers = function_exists('getallheaders') ? getallheaders() : [];
$key = $headers['X-API-KEY'] ?? $_SERVER['HTTP_X_API_KEY'] ?? $_GET['key'] ?? '';
if ($key !== API_KEY) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid API Key']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $long_url = $_POST['long_url'] ?? '';
    $duration = $_POST['duration'] ?? 'forever';
    
    if (empty($long_url)) {
        echo json_encode(['error' => 'URL is required']);
        exit;
    }

    $id = uniqid('url_');
    $short_code = substr(md5(uniqid(rand(), true)), 0, 6);
    
    $expires_at = null;
    if ($duration === '24h') {
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
    } elseif ($duration === '7d') {
        $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO short_links (id, long_url, short_code, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id, $long_url, $short_code, $expires_at]);

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $script_url = $protocol . "://" . $host . $_SERVER['SCRIPT_NAME'];
        $short_url = $script_url . "?code=" . $short_code;

        echo json_encode([
            'success' => true,
            'id' => $id,
            'short_code' => $short_code,
            'short_url' => $short_url,
            'long_url' => $long_url,
            'expires_at' => $expires_at
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} elseif ($action === 'list') {
    try {
        $db = getDB();
        $links = $db->query("SELECT * FROM short_links ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'links' => $links]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} elseif ($action === 'delete' && isset($_GET['id'])) {
    try {
        $db = getDB();
        $id = $_GET['id'];
        $stmt = $db->prepare("DELETE FROM short_links WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>

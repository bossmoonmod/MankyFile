<?php
require_once 'config.php';
require_once 'db.php';

// 1. serve image if id is provided via GET and no action
if (isset($_GET['id']) && !isset($_GET['action'])) {
    $id = $_GET['id'];
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM hosted_images WHERE id = ?");
        $stmt->execute([$id]);
        $img = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($img) {
            $path = HOSTED_DIR . $img['filename'];
            if (file_exists($path)) {
                // Check expiry
                if ($img['auto_delete_at'] && strtotime($img['auto_delete_at']) < time()) {
                    http_response_code(404);
                    echo "Image Expired";
                    exit;
                }
                if ($img['mime_type'] === 'image/gif') {
                    header('Content-Disposition: inline; filename="'.$img['id'].'.gif"');
                }
                header('Content-Type: ' . $img['mime_type']);
                readfile($path);
                exit;
            }
        }
    } catch (Exception $e) {}
    http_response_code(404);
    echo "Image Not Found";
    exit;
}

// 2. API Actions
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload') {
    if (!isset($_FILES['image'])) {
        echo json_encode(['error' => 'No image uploaded']);
        exit;
    }

    $file = $_FILES['image'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    
    if (!in_array($ext, $allowed)) {
        echo json_encode(['error' => 'Invalid file type']);
        exit;
    }

    $id = uniqid('img_');
    $filename = $id . '.' . $ext;
    $target = HOSTED_DIR . $filename;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        $auto_delete = $_POST['auto_delete'] ?? 'never';
        $expiry = null;
        
        if ($auto_delete !== 'never') {
            // Parse relative time like '5min', '1h', '1d', '1w'
            if (preg_match('/^(\d+)(min|h|d|w|m)$/', $auto_delete, $matches)) {
                $val = $matches[1];
                $unit = $matches[2];
                $time_str = "+$val ";
                switch ($unit) {
                    case 'min': $time_str .= "minutes"; break;
                    case 'h': $time_str .= "hours"; break;
                    case 'd': $time_str .= "days"; break;
                    case 'w': $time_str .= "weeks"; break;
                    case 'm': $time_str .= "months"; break;
                }
                $expiry = date('Y-m-d H:i:s', strtotime($time_str));
            }
        }

        try {
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO hosted_images (id, filename, original_name, mime_type, auto_delete_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id, $filename, $file['name'], $file['type'], $expiry]);

            // Construct URLs
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $script_url = $protocol . "://" . $host . $_SERVER['SCRIPT_NAME'];
            $viewer_url = $script_url . "?id=" . $id;
            $direct_url = $script_url . "?id=" . $id; // serving direct file

            echo json_encode([
                'success' => true,
                'id' => $id,
                'viewer_link' => $viewer_url,
                'direct_link' => $direct_url,
                'expiry' => $expiry,
                'codes' => [
                    'html' => '<img src="'.$direct_url.'" border="0">',
                    'markdown' => '!['.$file['name'].']('.$direct_url.')',
                    'bbcode' => '[img]'.$direct_url.'[/img]'
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['error' => 'Failed to save file']);
    }
} elseif ($action === 'list') {
    try {
        $db = getDB();
        $imgs = $db->query("SELECT * FROM hosted_images ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'images' => $imgs]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} elseif ($action === 'delete' && isset($_GET['id'])) {
    try {
        $db = getDB();
        $id = $_GET['id'];
        $stmt = $db->prepare("SELECT filename FROM hosted_images WHERE id = ?");
        $stmt->execute([$id]);
        $img = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($img) {
            $path = HOSTED_DIR . $img['filename'];
            if (file_exists($path)) unlink($path);
            
            $stmt = $db->prepare("DELETE FROM hosted_images WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Image not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>

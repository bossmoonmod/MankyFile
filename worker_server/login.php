<?php
require_once 'config.php';
require_once 'db.php';
session_start();

$db = getDB();
$ip = $_SERVER['REMOTE_ADDR'];
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];

// Check if currently locked
$stmt = $db->prepare("SELECT * FROM login_brute_force WHERE ip_address = ?");
$stmt->execute([$ip]);
$status = $stmt->fetch(PDO::FETCH_ASSOC);

$is_locked = ($status && $status['is_locked'] == 1);
$attempts = $status ? $status['attempts'] : 0;

// Recovery Logic
if (isset($_POST['recovery_key']) && $_POST['recovery_key'] === MASTER_RECOVERY_KEY) {
    $db->prepare("DELETE FROM login_brute_force WHERE ip_address = ?")->execute([$ip]);
    $is_locked = false;
    $attempts = 0;
    $recovery_success = "Neural Link Restored. You may now attempt PIN entry.";
}

if (isset($_POST['pin']) && !$is_locked) {
    $pin = $_POST['pin'];
    
    if ($pin === MASTER_PIN) {
        // Success: Reset attempts and log in
        $db->prepare("DELETE FROM login_brute_force WHERE ip_address = ?")->execute([$ip]);
        $_SESSION['m_os_auth'] = true;
        
        // Log successful access
        $ua = $_SERVER['HTTP_USER_AGENT'];
        $db->prepare("INSERT INTO access_logs (ip_address, user_agent, accessed_at) VALUES (?, ?, (DATETIME('now', 'localtime')))")->execute([$ip, $ua]);
        
        header('Location: index.php');
        exit();
    } else {
        // Failure: Increment attempts
        $attempts++;
        $lock_trigger = ($attempts >= 3) ? 1 : 0;
        
        $db->prepare("INSERT OR REPLACE INTO login_brute_force (ip_address, attempts, last_attempt, is_locked) VALUES (?, ?, (DATETIME('now', 'localtime')), ?)")
           ->execute([$ip, $attempts, $lock_trigger]);
        
        // Log failed attempt as security breach attempt
        $ua = "[ANTIHACK_BREACH_ATTEMPT] " . $_SERVER['HTTP_USER_AGENT'];
        $db->prepare("INSERT INTO access_logs (ip_address, user_agent, accessed_at) VALUES (?, ?, (DATETIME('now', 'localtime')))")->execute([$ip, $ua]);
        
        if ($lock_trigger) $is_locked = true;
        
        // Delay for security
        sleep($attempts); 
        $error = "INVALID_PIN. Attempt $attempts of 3. (Delay: {$attempts}s)";
        if ($is_locked) $error = "NEURAL_SYSTEM_LOCKED. Unauthorized access detected and logged.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MankyOS | Neural Security</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;600&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #ffafbd; --accent: #a1c4fd;
            --bg: #050510; --red: #ff6b6b;
        }
        body {
            margin: 0; background: var(--bg); color: #fff;
            font-family: 'Outfit', sans-serif;
            display: flex; align-items: center; justify-content: center; height: 100vh;
            overflow: hidden;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(40px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 50px; border-radius: 40px;
            width: 320px; text-align: center;
            box-shadow: 0 50px 100px rgba(0,0,0,0.8);
            animation: fadeIn 0.8s ease;
            position: relative;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        .logo { width: 80px; margin-bottom: 25px; filter: drop-shadow(0 0 20px var(--primary)); }
        h2 { font-weight: 600; font-size: 1.2rem; letter-spacing: 2px; margin-bottom: 30px; color: var(--accent); }
        
        .pin-display {
            display: flex; justify-content: center; gap: 15px; margin-bottom: 40px;
        }
        .dot { width: 15px; height: 15px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.2); transition: 0.3s; }
        .dot.fill { background: var(--primary); border-color: var(--primary); box-shadow: 0 0 15px var(--primary); }
        .dot.locked { background: var(--red); border-color: var(--red); box-shadow: 0 0 15px var(--red); }
        
        .numpad { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .num-btn {
            width: 70px; height: 70px; border-radius: 50%; background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1); color: #fff; font-size: 1.5rem; display: flex; 
            align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; font-family: 'JetBrains Mono';
        }
        .num-btn:active { background: var(--primary); color: #000; transform: scale(0.9); }
        .num-btn.disabled { opacity: 0.2; cursor: not-allowed; }

        #error-msg { color: var(--red); font-size: 0.75rem; margin-top: 25px; font-weight: 600; background: rgba(255,107,107,0.1); padding: 10px; border-radius: 10px; border: 1px solid rgba(255,107,107,0.2); }
        #success-msg { color: #88d8b0; font-size: 0.75rem; margin-top: 25px; font-weight: 600; background: rgba(136,216,176,0.1); padding: 10px; border-radius: 10px; border: 1px solid rgba(136,216,176,0.2); }
        
        .bg-pulse {
            position: absolute; width: 600px; height: 600px; background: radial-gradient(circle, rgba(255,175,189,0.1) 0%, transparent 70%);
            z-index: -1; animation: pulse 10s infinite alternate;
        }
        @keyframes pulse { from { transform: translate(-30%, -30%) scale(1); } to { transform: translate(30%, 30%) scale(1.5); } }

        .recovery-link { margin-top: 20px; font-size: 0.7rem; color: rgba(255,255,255,0.3); text-decoration: underline; cursor: pointer; }
    </style>
</head>
<body>
    <div class="bg-pulse"></div>
    <div class="login-card">
        <img src="MankyFileOS.png" class="logo" onerror="this.src='https://cdn-icons-png.flaticon.com/512/1006/1006363.png'">
        <h2 id="status-title"><?php echo $is_locked ? 'SYSTEM_LOCKED' : 'IDENTITY_CHECK'; ?></h2>
        
        <div class="pin-display" id="dots">
            <div class="dot <?php echo $is_locked ? 'locked' : ''; ?>"></div>
            <div class="dot <?php echo $is_locked ? 'locked' : ''; ?>"></div>
            <div class="dot <?php echo $is_locked ? 'locked' : ''; ?>"></div>
            <div class="dot <?php echo $is_locked ? 'locked' : ''; ?>"></div>
        </div>

        <div class="numpad" id="numpad">
            <?php for($i=1;$i<=9;$i++): ?>
                <div class="num-btn <?php echo $is_locked ? 'disabled' : ''; ?>" onclick="<?php echo $is_locked ? '' : "press($i)"; ?>"><?php echo $i; ?></div>
            <?php endfor; ?>
            <div class="num-btn" style="background:transparent; border:none;" onclick="clearPin()"><i class="fas fa-backspace"></i></div>
            <div class="num-btn <?php echo $is_locked ? 'disabled' : ''; ?>" onclick="<?php echo $is_locked ? '' : "press(0)"; ?>">0</div>
            <div class="num-btn" style="background:transparent; border:none;" onclick="submitPin()"><i class="fas fa-check"></i></div>
        </div>

        <?php if(isset($error)): ?><div id="error-msg"><?php echo $error; ?></div><?php endif; ?>
        <?php if(isset($recovery_success)): ?><div id="success-msg"><?php echo $recovery_success; ?></div><?php endif; ?>
        
        <?php if($is_locked): ?>
            <div class="recovery-link" onclick="showRecovery()">Emergency Recovery Protocol</div>
        <?php endif; ?>

        <form id="pin-form" method="POST" style="display:none;">
            <input type="password" name="pin" id="pin-input">
        </form>

        <form id="recovery-form" method="POST" style="display:none;">
            <input type="password" name="recovery_key" id="recovery-input">
        </form>
    </div>

    <script>
        let currentPin = "";
        const dots = document.querySelectorAll('.dot');
        const isLocked = <?php echo $is_locked ? 'true' : 'false'; ?>;
        
        function press(n) {
            if(isLocked || currentPin.length >= 4) return;
            currentPin += n;
            updateDots();
            if(currentPin.length === 4) setTimeout(submitPin, 300);
        }

        function clearPin() {
            currentPin = "";
            updateDots();
        }

        function updateDots() {
            dots.forEach((dot, i) => {
                if(i < currentPin.length) dot.classList.add('fill');
                else if(!isLocked) dot.classList.remove('fill');
            });
        }

        function submitPin() {
            if(currentPin.length < 4) return;
            document.getElementById('pin-input').value = currentPin;
            document.getElementById('pin-form').submit();
        }

        function showRecovery() {
            const key = prompt("ENTER EMERGENCY RECOVERY KEY:");
            if(key) {
                document.getElementById('recovery-input').value = key;
                document.getElementById('recovery-form').submit();
            }
        }
    </script>
</body>
</html>

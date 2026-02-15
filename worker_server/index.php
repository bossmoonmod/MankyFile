<?php
require_once 'config.php';
session_start();

// AUTO-LOGOUT SYSTEM (Session Timeout - 30 Minutes)
$timeout_seconds = 1800; 
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_seconds)) {
    session_unset();
    session_destroy();
    header('Location: login.php?msg=TIMEOUT');
    exit();
}
$_SESSION['last_activity'] = time();

if (!isset($_SESSION['m_os_auth']) || $_SESSION['m_os_auth'] !== true) {
    header('Location: login.php');
    exit();
}
require_once 'db.php';

// MOBILE DETECTION
$user_agent = $_SERVER['HTTP_USER_AGENT'];
$is_mobile = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $user_agent) 
           || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|it|30|11)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|it|30|11)|vzw\-|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($user_agent,0,4));

if ($is_mobile && !strpos($_SERVER['PHP_SELF'], 'mobile.php')) {
    header('Location: mobile.php');
    exit();
}

// SECURITY: Log Access IP
try {
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    $ua = $_SERVER['HTTP_USER_AGENT'];
    $stmt = $db->prepare("INSERT INTO access_logs (ip_address, user_agent, accessed_at) VALUES (?, ?, (DATETIME('now', 'localtime')))");
    $stmt->execute([$ip, $ua]);
} catch(Exception $e) {}
?>
<script>
    // ADVANCED IPAD DETECTION (iPadOS Safari reports as Macintosh)
    if (window.navigator.maxTouchPoints > 0 && /Macintosh/.test(navigator.userAgent) && !window.location.pathname.includes('mobile.php')) {
        window.location.href = 'mobile.php';
    }
</script>
<?php
$app_registry = 'App_store/registry.json';
$apps = [];
if (file_exists($app_registry)) {
    $apps = json_decode(file_get_contents($app_registry), true) ?: [];
}

// Fallback if registry is empty
if (empty($apps)) {
    $apps = [
        ['id'=>'monitor','title'=>'Heartbeat','icon'=>'fa-heartbeat', 'file'=>'monitor.php'],
        ['id'=>'disk','title'=>'Disk','icon'=>'fa-hdd', 'file'=>'disk_win.php'],
        ['id'=>'music_win','title'=>'Music Lab','icon'=>'fa-compact-disc', 'file'=>'music_win.php'],
        ['id'=>'api_master','title'=>'Node Master','icon'=>'fa-satellite-dish', 'file'=>'api_master.php'],
        ['id'=>'notes','title'=>'Notes','icon'=>'fa-sticky-note', 'file'=>'notes.php'],
        ['id'=>'news_app','title'=>'Daily News','icon'=>'fa-newspaper', 'file'=>'news_app.php'],
        ['id'=>'gallery','title'=>'Gallery','icon'=>'fa-images', 'file'=>'gallery.php'],
        ['id'=>'walls','title'=>'Aura','icon'=>'fa-palette', 'file'=>'walls.php'],
        ['id'=>'security','title'=>'Security','icon'=>'fa-shield-alt', 'file'=>'security.php'],
        ['id'=>'calc','title'=>'Calculator','icon'=>'fa-calculator', 'file'=>'calc.php'],
        ['id'=>'editor','title'=>'Canvas','icon'=>'fa-magic', 'file'=>'editor.php'],
        ['id'=>'term','title'=>'Terminal','icon'=>'fa-terminal', 'file'=>'term.php'],
        ['id'=>'prism','title'=>'Prism','icon'=>'fa-eye-dropper', 'file'=>'prism.php'],
        ['id'=>'store','title'=>'App Store','icon'=>'fa-shop', 'file'=>'store.php']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MankyFileOS Pro | Extreme Node Master</title>
    <link rel="icon" type="image/png" href="MankyFileOS.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #ffafbd; --accent: #a1c4fd; --glass: rgba(15, 15, 25, 0.75);
            --win-bg: rgba(18, 18, 32, 0.98); --glass-border: rgba(255, 255, 255, 0.12);
            --green: #88d8b0; --red: #ff6b6b; --text: #f0f0f0; --yellow: #ffd93d;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { 
            background: #000 url('image/wallpapers/default.jpg') center/cover fixed; 
            font-family: 'Outfit', sans-serif; color: var(--text); height: 100vh; overflow: hidden; display: flex; flex-direction: column;
            transition: background 0.5s ease;
        }
        #bg-video {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -1; display: none;
        }
        .desktop { flex: 1; position: relative; padding: 30px; }
        #icon-grid {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
            pointer-events: none;
        }
        .icon { position: absolute; width:100px; height:110px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; cursor:pointer; border-radius:20px; transition:0.2s; text-align:center; z-index:5; pointer-events:auto; }
        .icon:hover { background: rgba(255,255,255,0.1); box-shadow: 0 10px 30px rgba(0,0,0,0.5); transform: translateY(-3px); }
        .icon i { font-size: 2.8rem; color: var(--primary); filter: drop-shadow(0 0 12px rgba(255,175,189,0.4)); }
        .icon span { font-size: 0.8rem; font-weight: 500; text-shadow: 0 2px 5px #000; }

        .hud { position: absolute; top: 30px; right: 30px; width: 340px; display: flex; flex-direction: column; gap: 20px; z-index: 4; }
        .logo-box { text-align: center; margin-bottom: 5px; }
        .logo-box img { width: 95px; height: 95px; border-radius: 50%; border: 3px solid var(--accent); box-shadow: 0 0 35px rgba(161,196,253,0.3); }
        .hud-card { background: var(--glass); border: 1px solid var(--glass-border); border-radius: 20px; padding: 20px; backdrop-filter: blur(45px); box-shadow: 0 15px 45px rgba(0,0,0,0.4); }
        
        /* ADVANCED HUD SLIDESHOW - DUAL LAYER */
        .hud-slideshow { 
            position: relative; width: 100%; aspect-ratio: 4/3; border-radius: 18px; 
            overflow: hidden; border: 1px solid var(--glass-border); background: #000;
            box-shadow: inset 0 0 30px rgba(0,0,0,0.8);
        }
        .hud-slide-wrap { position: absolute; inset: 0; opacity: 0; transition: 1.5s ease-in-out; }
        .hud-slide-wrap.active { opacity: 1; }
        
        /* Blurred Background Layer */
        .hud-slide-bg { 
            position: absolute; inset: -10px; 
            background-size: cover; background-position: center; 
            filter: blur(20px) brightness(0.4); transform: scale(1.1);
        }
        
        /* Sharp Contained Layer */
        .hud-slide-img { 
            position: absolute; inset: 0; 
            width: 100%; height: 100%; object-fit: contain; 
            z-index: 2; filter: drop-shadow(0 0 20px rgba(0,0,0,0.5));
        }
        .hud-slide-overlay { position: absolute; inset: 0; z-index: 3; background: radial-gradient(circle, transparent 40%, rgba(0,0,0,0.4) 100%); pointer-events: none; }
        
        .window { 
            position: absolute; min-width: 450px; min-height: 400px; background: var(--win-bg); border: 1px solid var(--glass-border);
            border-radius: 28px; box-shadow: 0 40px 100px rgba(0,0,0,0.6); backdrop-filter: blur(25px);
            z-index: 100; display: none; flex-direction: column; animation: open 0.4s cubic-bezier(0.18, 0.89, 0.32, 1.28);
            overflow: hidden; will-change: transform, opacity;
        }
        .window.active { display: flex; }
        .win-resize { 
            position: absolute; bottom: 0; right: 0; width: 20px; height: 20px; 
            cursor: nwse-resize; z-index: 1000;
            background: linear-gradient(135deg, transparent 50%, var(--primary) 50%);
            border-radius: 0 0 28px 0; /* Match window border-radius */
            opacity: 0.3; transition: 0.3s;
        }
        .win-resize:hover { opacity: 1; }
        #drag-overlay { position: fixed; inset: 0; z-index: 9999; display: none; cursor: nwse-resize; }
        @keyframes open { from { opacity: 0; transform: scale(0.9) translateY(40px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        
        .win-head { padding: 18px 25px; background: rgba(255,255,255,0.03); display: flex; justify-content: space-between; align-items: center; cursor: move; border-radius: 28px 28px 0 0; }
        .win-title { font-weight: 700; color: var(--accent); font-size: 0.95rem; letter-spacing: 0.5px; }
        .win-ctrls { display: flex; gap: 12px; }
        .dot { width: 14px; height: 14px; border-radius: 50%; cursor: pointer; }
        .dot-red { background: var(--red); }
        .win-body { padding: 30px; flex: 1; overflow-y: auto; font-size: 0.95rem; }

        .btn { background: rgba(255,255,255,0.08); border: 1px solid var(--glass-border); padding: 10px 22px; border-radius: 30px; color: #fff; cursor: pointer; transition: 0.3s; font-family: inherit; font-size: 0.8rem; }
        .btn:hover, .btn.active { background: var(--primary); color: #000; font-weight: 700; box-shadow: 0 0 25px rgba(255,175,189,0.4); }

        .player { position: absolute; bottom: 85px; left: 50%; transform: translateX(-50%); width: 620px; background: rgba(5,5,15,0.85); border: 1px solid rgba(255,255,255,0.1); border-radius: 40px; padding: 12px 30px; display: flex; align-items: center; gap: 20px; backdrop-filter: blur(50px); box-shadow: 0 40px 100px rgba(0,0,0,0.8); z-index: 900; transition: 0.3s; }
        .player:hover { border-color: var(--primary); transform: translateX(-50%) translateY(-5px); }
        .vinyl-container { position: relative; width: 65px; height: 65px; display: flex; align-items: center; justify-content: center; }
        #vinyl-canvas { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 2; pointer-events: none; }
        .vinyl { width: 50px; height: 50px; background: #000; border-radius: 50%; border: 2px solid #222; animation: spin 4s linear infinite; animation-play-state: paused; box-shadow: 0 0 20px rgba(0,0,0,1); }
        .vinyl.playing { animation-play-state: running; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

        .taskbar { height: 75px; background: rgba(0,0,0,0.92); backdrop-filter: blur(20px); border-top: 1px solid var(--glass-border); display: flex; justify-content: space-between; padding: 0 50px; align-items: center; z-index: 2000; }
        .clock { font-family: 'JetBrains Mono'; font-size: 1.5rem; font-weight: 500; color: var(--primary); }
        .sys-row { display: flex; justify-content: space-between; padding: 14px; border-bottom: 1px solid rgba(255,255,255,0.06); align-items: center; transition: 0.2s; will-change: background; }
        .sys-row:hover { background: rgba(255,255,255,0.03); }
        .pill { padding: 5px 12px; border-radius: 10px; font-size: 0.75rem; font-weight: 800; }
        
        .gal-item { aspect-ratio:1; border-radius:15px; background-size:cover; background-position:center; cursor:pointer; border:2px solid transparent; transition:0.25s; }
        .gal-item:hover { border-color: var(--accent); transform: translateY(-5px); }

        .status-gauge { display:flex; flex-direction:column; align-items:center; gap:5px; }
        .status-gauge b { font-size: 1.4rem; color: var(--green); }
        .status-gauge small { font-size: 0.7rem; opacity: 0.6; }

        .top-widget { position: absolute; top: 40px; left: 50%; transform: translateX(-50%); text-align: center; z-index: 4; pointer-events: none; }
        .big-date { font-size: 3.2rem; font-weight: 700; color: var(--primary); letter-spacing: 8px; text-shadow: 0 0 30px rgba(255,175,189,0.5); text-transform: uppercase; line-height: 1; }
        .big-day { font-size: 1rem; opacity: 0.6; margin-top: 10px; letter-spacing: 4px; font-weight: 500; }

        /* Neural Context Menu */
        .ctx-menu { position: absolute; width: 220px; background: var(--glass); border: 1px solid var(--glass-border); border-radius: 18px; backdrop-filter: blur(50px); box-shadow: 0 10px 40px rgba(0,0,0,0.6); display: none; z-index: 5000; padding: 10px; }
        .ctx-item { padding: 12px 18px; border-radius: 12px; cursor: pointer; display: flex; align-items: center; gap: 12px; font-size: 0.85rem; transition: 0.2s; color: rgba(255,255,255,0.8); }
        .ctx-item:hover { background: var(--primary); color: #000; font-weight: 700; }
        .ctx-item i { font-size: 1rem; color: var(--accent); }
        .ctx-item:hover i { color: #000; }
        .ctx-sep { height: 1px; background: rgba(255,255,255,0.08); margin: 8px 10px; }

        /* SMOOTH RAINBOW PROPERTY SYNC */
        @property --primary { syntax: '<color>'; initial-value: #ffafbd; inherits: true; }
        @property --accent { syntax: '<color>'; initial-value: #a1c4fd; inherits: true; }

        @keyframes rainbowFlow {
            0% { --primary: hsl(0, 80%, 75%); --accent: hsl(30, 80%, 75%); }
            20% { --primary: hsl(72, 80%, 75%); --accent: hsl(102, 80%, 75%); }
            40% { --primary: hsl(144, 80%, 75%); --accent: hsl(174, 80%, 75%); }
            60% { --primary: hsl(216, 80%, 75%); --accent: hsl(246, 80%, 75%); }
            80% { --primary: hsl(288, 80%, 75%); --accent: hsl(318, 80%, 75%); }
            100% { --primary: hsl(360, 80%, 75%); --accent: hsl(390, 80%, 75%); }
        }
        .rainbow-active { animation: rainbowFlow 15s infinite linear !important; }

        /* NEURAL AURORA - RIGHT TO LEFT FLOW */
        @keyframes auroraFlow {
            0% { --primary: #ff00ff; --accent: #0015ff; }
            33% { --primary: #00f2ff; --accent: #37ff00; }
            66% { --primary: #ff9d00; --accent: #ff00ff; }
            100% { --primary: #ff00ff; --accent: #0015ff; }
        }
        .aurora-active { animation: auroraFlow 8s infinite ease-in-out !important; }

        ::-webkit-scrollbar { width: 6px; } ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 10px; }

        /* PORTRAIT GUARD */
        #portrait-warn {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #000; z-index: 99999; display: none;
            flex-direction: column; align-items: center; justify-content: center;
            text-align: center; padding: 40px;
        }
        #portrait-warn i { font-size: 5rem; color: var(--primary); margin-bottom: 30px; animation: rotate 2s infinite ease-in-out; }
        @keyframes rotate { 0% { transform: rotate(0deg); } 50% { transform: rotate(90deg); } 100% { transform: rotate(0deg); } }
        
        @media screen and (orientation: portrait) {
            #portrait-warn { display: flex; }
            body > *:not(#portrait-warn) { display: none !important; }
        }

        /* MOBILE & TABLET OPTIMIZATIONS */
        @media (max-width: 1280px) {
            #icon-grid { 
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(75px, 1fr)); 
                grid-auto-rows: 90px; 
                padding: 10px;
                gap: 10px;
            }
            .icon { position: relative !important; width: 100% !important; height: 100% !important; top: 0 !important; left: 0 !important; }
            .icon i { font-size: 1.8rem; }
            .icon span { font-size: 0.65rem; }

            .taskbar { padding: 0 15px; height: 50px; }
            .taskbar span { display: none; }
            .clock { font-size: 1rem; }
            
            .player { width: 480px; bottom: 65px; padding: 8px 15px; gap: 10px; }
            .vinyl-container { width: 45px; height: 45px; }
            .vinyl { width: 35px; height: 35px; }
            #vinyl-canvas { width: 70px; height: 70px; }
            .player-title { font-size: 0.7rem !important; }
            
            .window { 
                width: 98% !important; 
                left: 1% !important; 
                top: 2% !important; 
                height: 88% !important; 
                backdrop-filter: blur(15px);
                min-width: 0 !important; 
                min-height: 0 !important;
                border-radius: 15px; 
            }
            .win-head { padding: 10px 15px; }
            .win-body { padding: 15px; }
            .hud { display: none !important; }
            .top-widget { transform: translateX(-50%) scale(0.6); top: 5px; }
        }
    </style>
</head>
<body oncontextmenu="return false;">
    <div id="portrait-warn">
        <i class="fas fa-mobile-alt"></i>
        <h1 style="color:var(--primary); font-size:1.8rem;">ROTATE DEVICE</h1>
        <p style="opacity:0.7; margin-top:15px; font-weight:300;">MankyFileOS requires Landscape Mode<br>to maintain neural synchronization.</p>
    </div>
    <video id="bg-video" autoplay muted loop playsinline></video>

    <div class="desktop" id="desktop">
        <div id="icon-grid" style="position:absolute; top:0; left:0; width:100%; height:100%; pointer-events:none;"></div>

        <!-- TOP CENTER DATE WIDGET -->
        <div class="top-widget">
            <div class="big-date" id="big-date">LOADING_DATE</div>
            <div class="big-day" id="big-day">ESTABLISHING_NODE</div>
        </div>

        <!-- SIDE HUD -->
        <div class="hud" id="main-hud">
            <div class="logo-box"><img src="MankyFileOS.png" onerror="this.src='https://cdn-icons-png.flaticon.com/512/1006/1006363.png'"></div>
            <div class="hud-card">
                <h4 style="color:var(--accent); margin-bottom:12px; font-size:0.8rem;"><i class="fas fa-microchip"></i> NODE ENTROPY</h4>
                <canvas id="vitalChart" style="max-height: 100px;"></canvas>
            </div>
            <div class="hud-card">
                <h4 style="color:var(--primary); margin-bottom:12px; font-size:0.8rem;"><i class="fas fa-satellite"></i> LIVE STREAM</h4>
                <div id="news-hud-list" style="font-size:0.75rem; opacity:0.6; line-height:1.5;">Linking to Master...</div>
            </div>
            
            <!-- NEURAL VISIONARY AI -->
            <div class="hud-card" style="padding:12px;">
                <h4 style="color:var(--accent); margin:5px 0 12px 8px; font-size:0.8rem;"><i class="fas fa-microscope"></i> VISIONARY_AI</h4>
                <div class="hud-slideshow" id="hud-gallery-slideshow">
                    <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; opacity:0.3; font-size:0.7rem; color:var(--text);">INITIALIZING SCAN...</div>
                </div>
            </div>
        </div>

        <!-- MODULAR WINDOWS LOADED FROM APP_STORE -->
        <?php foreach($apps as $app): ?>
            <div id="win-<?php echo $app['id']; ?>" class="window" style="width:<?php echo (strpos($app['id'],'api')!==false?'900px':(strpos($app['id'],'disk')!==false?'800px':'700px')); ?>;">
                <?php include 'App_store/' . $app['file']; ?>
            </div>
        <?php endforeach; ?>

        <!-- PLAYER -->
        <div class="player">
            <div class="vinyl-container">
                <canvas id="vinyl-canvas" width="100" height="100"></canvas>
                <div class="vinyl" id="vinyl-rec"></div>
            </div>
            <div style="flex:1; min-width:0;">
                <div id="player-title" style="font-size:0.9rem; font-weight:700; color:var(--primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Syncing Frequencies...</div>
                <div style="height:5px; background:rgba(255,255,255,0.1); margin-top:8px; border-radius:10px;"><div id="player-bar" style="height:100%; background:var(--primary); width:0%; border-radius:10px;"></div></div>
            </div>
            
            <div style="display:flex; align-items:center; gap:15px; background:rgba(255,255,255,0.05); padding:5px 15px; border-radius:20px; margin: 0 10px;">
                <i class="fas fa-volume-down" style="font-size:0.8rem; opacity:0.5;"></i>
                <input type="range" min="0" max="1" step="0.01" value="1" oninput="setVol(this.value)" style="width:60px; accent-color:var(--primary); height:3px; cursor:pointer;">
                <i class="fas fa-volume-up" style="font-size:0.8rem; opacity:0.5;"></i>
            </div>

            <div style="display:flex; gap:18px; font-size:1.2rem; align-items:center;">
                <i class="fas fa-step-backward" style="cursor:pointer; opacity:0.7;" onclick="musicPrev()"></i>
                <i class="fas fa-play" style="cursor:pointer; font-size:1.6rem;" id="play-btn-ico" onclick="musicToggle()"></i>
                <i class="fas fa-step-forward" style="cursor:pointer; opacity:0.7;" onclick="musicNext()"></i>
                <i class="fas fa-list-ul" style="cursor:pointer; color:var(--accent); margin-left:10px;" onclick="toggleQueue()"></i>
            </div>
            
            <!-- FLOATING QUEUE NEXT TO PLAYER -->
            <div id="player-queue" class="hud-card" style="position:absolute; bottom:90px; right:0; width:300px; display:none; max-height:400px; overflow:auto; z-index:1000; animation: open 0.3s ease;">
                <h4 style="margin-bottom:15px; font-size:0.8rem; letter-spacing:1px;"><i class="fas fa-stream"></i> NEURAL QUEUE</h4>
                <div id="queue-list-items"></div>
            </div>
        </div>
    </div>

    <!-- DRAG/RESIZE SHIELD -->
    <div id="drag-overlay"></div>

    <!-- TASKBAR -->
    <div class="taskbar">
        <div style="display:flex; align-items:center; gap:20px;">
            <img src="MankyFileOS.png" onclick="allOff()" style="width:40px; height:40px; border-radius:50%; cursor:pointer; box-shadow:0 0 15px rgba(161,196,253,0.3);" onerror="this.src='https://cdn-icons-png.flaticon.com/512/1006/1006363.png'">
            <span style="font-weight:700; font-size:0.9rem; color:rgba(255,255,255,0.4); font-family:'JetBrains Mono';">MankyModularOS V3</span>
        </div>
        <div style="display:flex; align-items:center; gap:25px;">
            <div class="clock" id="clocker">00:00:00</div>
            <a href="logout.php" title="TERMINATE_NEURAL_LINK" style="color:var(--red); font-size:1.5rem; cursor:pointer; transition:0.3s; filter:drop-shadow(0 0 10px rgba(255,107,107,0.3));"><i class="fas fa-power-off"></i></a>
        </div>
    </div>

    <!-- CONTEXT MENU -->
    <div id="desktop-ctx" class="ctx-menu">
        <div class="ctx-item" onclick="autoArrange()"><i class="fas fa-th"></i> Auto Arrange Icons</div>
        <div class="ctx-item" onclick="location.reload()"><i class="fas fa-sync-alt"></i> Refresh Neural Links</div>
        <div class="ctx-sep"></div>
        <div class="ctx-item" onclick="toggleHUD()"><i class="fas fa-columns"></i> Toggle Sidebar HUD</div>
        <div class="ctx-item" onclick="openW('prism')"><i class="fas fa-palette"></i> Neural Personalize</div>
        <div class="ctx-sep"></div>
        <div class="ctx-item" onclick="location.href='logout.php'" style="color:var(--red)"><i class="fas fa-power-off"></i> Terminate Session</div>
        <div class="ctx-item" onclick="resetOS()" style="color:var(--red); opacity:0.5;"><i class="fas fa-trash-alt"></i> Wipe OS Layout</div>
    </div>

    <audio id="os-ae" ontimeupdate="aePulse()" onended="musicNext()"></audio>

    <!-- NEURAL VIEWER WINDOW -->
    <div id="win-viewer" class="window" style="width:1000px; height:700px; left:10%; top:50px;">
        <div class="win-head" onmousedown="wDrag(event,'win-viewer')">
            <div class="win-title"><i class="fas fa-eye"></i> Neural Viewer</div>
            <div class="win-ctrls"><div class="dot dot-red" onclick="closeW('viewer')"></div></div>
        </div>
        <div class="win-body" style="padding:0; overflow:hidden; background:#000; position:relative; flex:1;">
            <img id="view-img" style="width:100%; height:100%; object-fit:contain;">
            <div style="position:absolute; bottom:20px; left:50%; transform:translateX(-50%); display:flex; gap:10px;">
                <button class="btn active" onclick="downloadCurrent()"><i class="fas fa-download"></i> Download</button>
                <button class="btn" onclick="openW('editor', document.getElementById('view-img').src)">Edit</button>
                <button class="btn" onclick="closeW('viewer')">Close</button>
            </div>
        </div>
        <div class="win-resize" onmousedown="srStart(event,'win-viewer')"></div>
    </div>

    <script>
        let zI = 1000, di = null, dw = null, rs = null, ox, oy, sw, sh;
        let pList = [], cS = -1;
        const ae = document.getElementById('os-ae');
        let radarChart = null, lineChart = null;

        async function boot() {
            startTicker();
            try {
                const r = await fetch('disk.php?action=get_apps');
                const apps = await r.json();
                drawIcons(Array.isArray(apps) ? apps : []);
            } catch(e) {
                console.error("Boot Apps Error:", e);
                drawIcons([]);
            }
            
            try {
                initResizers();
                loadOSState();
                loadHUDNews();
                updateLiveStats();
                initHUDGallery();
                setInterval(updateLiveStats, 5000); 
                drawPlaylist();
                loadHUDChart();
            } catch(e) {
                console.error("Boot Modules Error:", e);
            }
        }

        function drawIcons(apps) {
            const g = document.getElementById('icon-grid');
            if(!g) return;
            g.innerHTML = '';
            const isMobile = window.innerWidth <= 1280;
            const posStr = localStorage.getItem('m_os_pos_v3');
            let pos = {};
            try { if(posStr) pos = JSON.parse(posStr); } catch(e) {}
            
            if(!Array.isArray(apps)) return;
            apps.forEach((a, i) => {
                const d = document.createElement('div'); d.className = 'icon'; d.id = 'ico-' + a.id;
                d.style.pointerEvents = 'auto';
                d.innerHTML = `<i class="${a.isBrand?'fab':'fas'} ${a.icon}"></i><span>${a.title}</span>`;
                
                if(!isMobile) {
                    if(pos[d.id]) { d.style.top = pos[d.id].t; d.style.left = pos[d.id].l; }
                    else { d.style.top = (40 + (i%6)*130) + 'px'; d.style.left = (40 + Math.floor(i/6)*130) + 'px'; }
                    d.onmousedown = (e) => iDragStart(e, d.id);
                }

                d.onclick = () => openW(a.id);
                g.appendChild(d);
            });
        }

        function openW(id, data = null) {
            console.log("Opening window:", id);
            const w = document.getElementById('win-' + id);
            if(!w) {
                console.error("Window not found: win-" + id);
                return;
            }
            w.classList.add('active'); focusW(w);
            if(id === 'api_master') masterDiag();
            if(id === 'disk') loadStorage('uploads');
            if(id === 'gallery') loadGalleryFull();
            if(id === 'notes') loadNotesFull();
            if(id === 'news_app') loadNewsFull();
            if(id === 'walls') loadWallsFull();
            if(id === 'music_win') loadMusicGrid();
            if(id === 'store') loadStoreApps();
            if(id === 'editor' && data) loadEditImg(data);
            saveOSState();
        }
        function closeW(id) { document.getElementById('win-' + id).classList.remove('active'); saveOSState(); }
        function focusW(w) { zI++; w.style.zIndex = zI; }
        function allOff() { document.querySelectorAll('.window').forEach(w => w.classList.remove('active')); saveOSState(); }

        function saveOSState() {
            const p = {}; document.querySelectorAll('.icon').forEach(i => p[i.id] = {t:i.style.top, l:i.style.left});
            localStorage.setItem('m_os_pos_v3', JSON.stringify(p));
            const ws = {}; document.querySelectorAll('.window.active').forEach(win => {
                ws[win.id.replace('win-','')] = {t:win.style.top, l:win.style.left, w:win.style.width, h:win.style.height};
            });
            localStorage.setItem('m_os_wins_v3', JSON.stringify(ws));
        }
        function loadOSState() {
            let ws = {};
            try { ws = JSON.parse(localStorage.getItem('m_os_wins_v3') || '{}'); } catch(e) {}
            Object.keys(ws).forEach(id => {
                const w = document.getElementById('win-' + id); 
                if(w) { 
                    w.classList.add('active'); 
                    w.style.top = ws[id].t; w.style.left = ws[id].l; 
                    if(ws[id].w) w.style.width = ws[id].w;
                    if(ws[id].h) w.style.height = ws[id].h;
                }
            });
            const wall = localStorage.getItem('m_os_aura');
            if(wall) setAura(wall);
            loadTheme();
        }

        function setTheme(p, a, mode = 'solid') {
            if(!document.documentElement) return;
            document.documentElement.classList.remove('rainbow-active', 'aurora-active');
            
            if(mode === 'rainbow' || mode === true) {
                document.documentElement.classList.add('rainbow-active');
                localStorage.setItem('m_os_theme_mode', 'rainbow');
            } else if(mode === 'aurora') {
                document.documentElement.classList.add('aurora-active');
                localStorage.setItem('m_os_theme_mode', 'aurora');
            } else if(p && a) {
                document.documentElement.style.setProperty('--primary', p);
                document.documentElement.style.setProperty('--accent', a);
                localStorage.setItem('m_os_p', p);
                localStorage.setItem('m_os_a', a);
                localStorage.setItem('m_os_theme_mode', 'solid');
            }
        }
        function loadTheme() {
            const mode = localStorage.getItem('m_os_theme_mode');
            if(mode === 'rainbow') return setTheme(null, null, 'rainbow');
            if(mode === 'aurora') return setTheme(null, null, 'aurora');
            const p = localStorage.getItem('m_os_p'), a = localStorage.getItem('m_os_a');
            if(p && a) setTheme(p, a);
        }

        function setAura(url) {
            if(!url || typeof url !== 'string') return;
            const v = document.getElementById('bg-video');
            const isVid = url.match(/\.(mp4|webm|mov)$/i);
            
            if(isVid) {
                if(v) {
                    v.src = url;
                    v.style.display = 'block';
                    v.play().catch(e => {});
                }
                document.body.style.backgroundImage = 'none';
            } else {
                if(v) {
                    v.pause();
                    v.style.display = 'none';
                }
                document.body.style.backgroundImage = `url('${url}')`;
            }
            localStorage.setItem('m_os_aura', url);
        }

        // DRAG ENGINE
        function iDragStart(e,id) { 
            di = document.getElementById(id); ox = e.clientX - di.offsetLeft; oy = e.clientY - di.offsetTop; 
        }
        function wDrag(e,id) { 
            dw = document.getElementById(id); ox = e.clientX - dw.offsetLeft; oy = e.clientY - dw.offsetTop; focusW(dw); 
        }
        function srStart(e,id) { 
            rs = document.getElementById(id); sw = rs.offsetWidth; sh = rs.offsetHeight; ox = e.clientX; oy = e.clientY; 
            e.stopPropagation(); 
        }

        document.onmousemove = (e) => {
            if(di || dw || rs) {
                // Only show overlay if we moved more than 5px to avoid blocking clicks
                if(Math.abs(e.clientX - ox) > 5 || Math.abs(e.clientY - oy) > 5) {
                    const overlay = document.getElementById('drag-overlay');
                    if(overlay.style.display !== 'block') {
                        overlay.style.display = 'block';
                        overlay.style.cursor = rs ? 'nwse-resize' : 'move';
                    }
                }
            }
            if(di) { di.style.left = (e.clientX-ox)+'px'; di.style.top = (e.clientY-oy)+'px'; }
            if(dw) { dw.style.left = (e.clientX-ox)+'px'; dw.style.top = (e.clientY-oy)+'px'; }
            if(rs) {
                rs.style.width = Math.max(450, sw + (e.clientX - ox)) + 'px';
                rs.style.height = Math.max(400, sh + (e.clientY - oy)) + 'px';
            }
        };
        document.onmouseup = () => { 
            if(di||dw||rs) saveOSState(); 
            di = null; dw = null; rs = null; 
            const overlay = document.getElementById('drag-overlay');
            if(overlay) overlay.style.display = 'none';
        };

        function initResizers() {
            document.querySelectorAll('.window').forEach(w => {
                if(!w.querySelector('.win-resize')) {
                    const r = document.createElement('div'); r.className = 'win-resize';
                    r.onmousedown = (e) => srStart(e, w.id);
                    w.appendChild(r);
                }
            });
        }

        // APPS LOGIC
        async function masterDiag() {
            const r = await fetch('disk.php?action=get_sys_check'); const d = await r.json();
            document.getElementById('api-sys-info').innerHTML = `<h3><i class="fas fa-microchip"></i> Node Intel</h3><div class="sys-row"><span>Hostname</span><b>${d.node}</b></div><div class="sys-row"><span>Status</span><b style="color:var(--green)">${d.status}</b></div>`;
            document.getElementById('api-con-info').innerHTML = `<h3><i class="fas fa-network-wired"></i> Links</h3>` + d.connections.map(c => `<div class="sys-row"><span>${c.service}</span><b style="color:var(--green)">CONNECTED</b></div>`).join('') + 
            `<button class="btn" style="background:var(--red); width:100%; margin-top:10px; font-weight:700;" onclick="emergencyKill()"><i class="fas fa-skull-crossbones"></i> EMERGENCY KILL ALL WORKERS</button>`;
            
            document.getElementById('api-task-list').innerHTML = d.tasks.map(t => {
                let ctrls = '';
                if (t.status === 'processing' || t.status === 'pending') {
                    ctrls = `<i class="fas fa-times-circle" style="color:var(--red); cursor:pointer; margin-left:10px; font-size:1.2rem;" onclick="cancelWork('${t.id}')" title="Stop Process"></i>`;
                } else {
                    ctrls = `<i class="fas fa-trash-alt" style="color:rgba(255,255,255,0.2); cursor:pointer; margin-left:10px;" onclick="deleteTaskHistory('${t.id}')" title="Remove History"></i>`;
                }
                
                return `<div class="sys-row" style="font-size:0.85rem;">
                    <div style="display:flex; flex-direction:column;">
                        <span style="font-weight:700; color:var(--accent);">[${t.created_at}]</span>
                        <span style="opacity:0.7;">${t.type || 'unlock'}</span>
                    </div>
                    <div style="display:flex; align-items:center;">
                        <b class="pill" style="background:${t.status=='completed'?'var(--green)':(t.status=='processing'?'var(--accent)':(t.status=='pending'?'var(--yellow)':'var(--red)'))}; color:#000; min-width:80px; text-align:center;">${t.status.toUpperCase()}</b>
                        ${ctrls}
                    </div>
                </div>`;
            }).join('');
        }

        async function emergencyKill() {
            if(!confirm('🚨 WARNING: นี้จะสั่ง "ฆ่า" ทุกกระบวนการ Python ที่รันงานอยู่ทิ้งทันที! เครื่องจะกลับมาเร็วขึ้นแต่ทุกงานที่ทำอยู่จะเสียทันที ยืนยันไหม?')) return;
            try {
                const r = await fetch('disk.php?action=emergency_kill');
                const d = await r.json();
                alert(d.msg);
                masterDiag();
            } catch(e) { alert('Kill command failed: ' + e); }
        }

        async function deleteTaskHistory(id) {
            await fetch('disk.php?action=delete_task&id=' + id);
            masterDiag();
        }

        async function cancelWork(id) {
            if(!confirm('🚨 Stop this process?')) return;
            try {
                const r = await fetch('check_status.php?task_id=' + id + '&cancel=1');
                const d = await r.json();
                masterDiag(); 
            } catch(e) { console.error('Error cancelling task:', e); }
        }

        let curDir = 'uploads';
        async function loadStorage(dir, b) {
            curDir = dir;
            if(b) { document.querySelectorAll('#win-disk .btn').forEach(x => x.classList.remove('active')); b.classList.add('active'); }
            const r = await fetch('disk.php?action=list&dir='+dir); const d = await r.json();
            const l = document.getElementById('disk-list-box'); l.innerHTML = '';
            (d.files||[]).forEach(f => {
                const row = document.createElement('div'); row.className = 'sys-row'; row.style.cursor='pointer';
                row.innerHTML = `<span><i class="fas fa-file-code" style="margin-right:12px; color:var(--lofi-blue)"></i> ${f.name}</span> <small>${f.date}</small> <i class="fas fa-trash" style="color:var(--red)" onclick="event.stopPropagation(); deleteUnit('${dir}','${f.name}')"></i>`;
                row.onclick = () => window.open(f.url,'_blank'); l.appendChild(row);
            });
        }
        async function purgeCurrent() {
            if(!confirm(`🚨 WARNING: นี้จะสั่งลบไฟล์ "ทั้งหมด" ในโฟลเดอร์ /${curDir.toUpperCase()} ทันที! ยืนยันไหม?`)) return;
            try {
                const r = await fetch('disk.php?action=purge&dir=' + curDir);
                const d = await r.json();
                if(d.success) loadStorage(curDir);
            } catch(e) { alert('Purge failed'); }
        }
        async function deleteUnit(d,f) { if(confirm('Delete?')) { await fetch(`disk.php?action=delete&dir=${d}&file=${f}`); loadStorage(d); } }
        async function uploadUnit(type, input, cb) {
            if(!input.files || input.files.length === 0) return;
            const fd = new FormData();
            if(input.files.length > 1) {
                for(let i=0; i<input.files.length; i++) {
                    fd.append('files[]', input.files[i]);
                }
            } else {
                fd.append('file', input.files[0]);
            }
            
            // Visual feedback
            const btn = input.previousElementSibling;
            const oldTxt = btn ? btn.innerText : null;
            if(btn) { btn.innerText = 'UPLOADING...'; btn.disabled = true; }

            try {
                const r = await fetch('disk.php?action=upload_any&type='+type, {method:'POST', body:fd});
                const d = await r.json();
                if(d.success) { if(cb) cb(); } 
                else { alert('Upload failed. Possible size limit or disk error.'); }
            } catch(e) { alert('Network Error during upload.'); }
            finally { if(btn) { btn.innerText = oldTxt; btn.disabled = false; } }
        }

        async function updateLiveStats() {
            try {
                const r = await fetch('disk.php?action=get_sys_check');
                const d = await r.json();
                if(!d || !d.vitals) return;
                const v = d.vitals;
                if(document.getElementById('val-stability')) {
                    document.getElementById('val-stability').innerText = v.stability + '%';
                    document.getElementById('val-load').innerText = (v.load||0) + '%';
                    document.getElementById('val-memory').innerText = (v.memory||0) + '%';
                    document.getElementById('val-latency').innerText = (v.latency||0) + 'ms';
                }
                if(radarChart && radarChart.data) {
                    radarChart.data.datasets[0].data = [v.stability, v.load, v.latency, v.memory, 100];
                    radarChart.update();
                } else if(document.getElementById('pulseRadarChart') && typeof Chart !== 'undefined') {
                    const ctx = document.getElementById('pulseRadarChart').getContext('2d');
                    radarChart = new Chart(ctx, { type:'radar', data:{ labels:['Stability','Load','Latency','Memory','Aura'], datasets:[{data:[v.stability, v.load, v.latency, v.memory, 100], borderColor:'var(--lofi-pink)', backgroundColor:'rgba(255,175,189,0.1)'}] }, options:{ scales:{r:{min:0, max:100, grid:{color:'rgba(255,255,255,0.05)'},ticks:{display:false}}}, plugins:{legend:{display:false}} } });
                }
            } catch(e) { console.error("Stats Error:", e); }
        }

        async function loadHUDChart() {
            try {
                const r = await fetch('disk.php?action=get_server_stats');
                const d = await r.json();
                if(!d || !Array.isArray(d)) return;
                const canvas = document.getElementById('vitalChart');
                if(canvas && typeof Chart !== 'undefined') {
                    const ctx = canvas.getContext('2d');
                    lineChart = new Chart(ctx, { type:'line', data:{ labels:d.map(x=>x.hour), datasets:[{data:d.map(x=>x.count), borderColor:'#a1c4fd', fill:true, backgroundColor:'rgba(161,196,253,0.1)', tension:0.4}] }, options:{plugins:{legend:{display:false}}, scales:{x:{display:false},y:{display:false}}} });
                }
            } catch(e) { console.error("HUD Chart Error:", e); }
        }

        async function saveNote() {
            const title = document.getElementById('n-t').value; const content = document.getElementById('n-c').value;
            if(!title) return alert('Title required');
            const r = await fetch('disk.php?action=save_note', {method:'POST', body:JSON.stringify({title, content})});
            const d = await r.json();
            if(d.success) alert('Echo Saved to Database'); else alert('Failed to save');
        }
        async function loadNotesFull() {
            const r = await fetch('disk.php?action=get_notes'); const d = await r.json();
            if(d.length>0) { document.getElementById('n-t').value = d[0].title; document.getElementById('n-c').value = d[0].content; }
        }
        function clearNote() { document.getElementById('n-t').value=''; document.getElementById('n-c').value=''; }

        // SHARED LOGIC
        function openViewer(url) {
            const w = document.getElementById('win-viewer');
            document.getElementById('view-img').src = url;
            w.classList.add('active');
            focusW(w);
        }
        function downloadCurrent() {
            const url = document.getElementById('view-img').src;
            const a = document.createElement('a'); a.href = url; a.download = url.split('/').pop(); a.click();
        }

        async function loadGalleryFull() { 
            const r = await fetch('disk.php?action=list&dir=gallery'); const d = await r.json(); 
            const g = document.getElementById('gallery-grid-full'); g.innerHTML = ''; 
            (d.files||[]).forEach(f => { 
                const div = document.createElement('div'); 
                div.className = 'gal-item'; 
                div.style.cssText = `background-image:url('${f.url}'); position:relative;`; 
                div.innerHTML = `<i class="fas fa-trash" style="position:absolute; top:10px; right:10px; color:var(--red); background:rgba(0,0,0,0.5); padding:8px; border-radius:50%; font-size:0.7rem; opacity:0; transition:0.3s;" onclick="event.stopPropagation(); deleteUnit('gallery','${f.name}'); loadGalleryFull();"></i>`;
                div.onmouseenter = () => div.querySelector('i').style.opacity = '1';
                div.onmouseleave = () => div.querySelector('i').style.opacity = '0';
                div.onclick = () => openViewer(f.url); 
                g.appendChild(div); 
            }); 
        }
        async function loadWallsFull() { 
            const r = await fetch('disk.php?action=list&dir=wallpapers'); const d = await r.json(); 
            const g = document.getElementById('aura-grid-full'); g.innerHTML = ''; 
            (d.files||[]).forEach(f => { 
                const isVid = f.name.match(/\.(mp4|webm|mov)$/i);
                const div = document.createElement('div'); 
                div.style.cssText = `aspect-ratio:1.7; border-radius:15px; position:relative; background:#111; border:2px solid transparent; cursor:pointer; overflow:hidden; transition:0.2s;`;
                
                if(isVid) {
                    div.innerHTML = `<video src="${f.url}" muted style="width:100%; height:100%; object-fit:cover;"></video><i class="fas fa-play-circle" style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); font-size:1.5rem; opacity:0.7;"></i>`;
                    div.onmouseenter = () => div.querySelector('video').play();
                    div.onmouseleave = () => div.querySelector('video').pause();
                } else {
                    div.style.background = `url('${f.url}') center/cover`;
                }
                
                div.onclick = () => setAura(f.url); 
                g.appendChild(div); 
            }); 
        }
        // MUSIC LOGIC
        let audioCtx, analyser, source, dataArray;
        
        function initAudioAnalyzer() {
            if(audioCtx) return;
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            analyser = audioCtx.createAnalyser();
            source = audioCtx.createMediaElementSource(ae);
            source.connect(analyser);
            analyser.connect(audioCtx.destination);
            analyser.fftSize = 64;
            dataArray = new Uint8Array(analyser.frequencyBinCount);
            drawVinylVisual();
        }

        function drawVinylVisual() {
            if(ae.paused) {
                const canvas = document.getElementById('vinyl-canvas');
                if(canvas) canvas.getContext('2d').clearRect(0, 0, 100, 100);
                return;
            }
            const canvas = document.getElementById('vinyl-canvas');
            if(!canvas) return;
            const ctx = canvas.getContext('2d');
            requestAnimationFrame(drawVinylVisual);
            if(!analyser) return;
            analyser.getByteFrequencyData(dataArray);
            
            ctx.clearRect(0, 0, 100, 100);
            const cx = 50, cy = 50, rad = 28;
            
            // Pulse Outer Aura
            let avg = 0; for(let v of dataArray) avg += v; avg /= dataArray.length;
            const pulse = avg / 255;
            
            ctx.beginPath();
            ctx.arc(cx, cy, rad + 5 + (pulse * 15), 0, Math.PI * 2);
            ctx.strokeStyle = `rgba(255, 175, 189, ${pulse * 0.3})`;
            ctx.lineWidth = 2;
            ctx.stroke();

            for(let i=0; i<dataArray.length; i++) {
                const barHeight = (dataArray[i]/255) * 25;
                const angle = (i/dataArray.length) * Math.PI * 2 - (Math.PI / 2);
                
                // Color Gradient based on frequency
                const hue = (i/dataArray.length) * 360;
                ctx.strokeStyle = `hsl(${hue}, 100%, 75%)`;
                ctx.lineCap = 'round';
                ctx.lineWidth = 3;
                
                const x1 = cx + Math.cos(angle) * rad;
                const y1 = cy + Math.sin(angle) * rad;
                const x2 = cx + Math.cos(angle) * (rad + barHeight);
                const y2 = cy + Math.sin(angle) * (rad + barHeight);
                
                ctx.beginPath();
                ctx.moveTo(x1, y1);
                ctx.lineTo(x2, y2);
                ctx.stroke();
                
                // Add a glow tip
                ctx.beginPath();
                ctx.arc(x2, y2, 2, 0, Math.PI * 2);
                ctx.fillStyle = '#fff';
                ctx.fill();
            }
        }

        async function drawPlaylist() { 
            const r = await fetch('disk.php?action=list&dir=music'); 
            const d = await r.json(); 
            pList = d.files||[]; 
            updateQueueUI();
        }
        function setVol(v) { ae.volume = v; }
        function loadMusicGrid() { 
            const l = document.getElementById('music-grid-full'); 
            if(!l) return;
            l.innerHTML = ''; 
            pList.forEach((f, i) => { 
                const row = document.createElement('div'); 
                row.className = 'sys-row'; 
                row.style.cursor='pointer'; 
                if(cS === i) row.style.background = 'rgba(255,175,189,0.1)';
                row.innerHTML = `<div style="display:flex; align-items:center; gap:12px;"><i class="fas ${cS === i ? 'fa-volume-up' : 'fa-play-circle'}" style="color:var(--primary)"></i> <span>${f.name}</span></div> <i class="fas fa-trash" style="color:var(--red); opacity:0.5;" onclick="event.stopPropagation(); deleteUnit('music','${f.name}'); drawPlaylist();"></i>`; 
                row.onclick = () => musicPlay(i); 
                l.appendChild(row); 
            }); 
        }
        function musicToggle() { 
            if(ae.paused) { 
                if(!ae.src && pList.length>0) musicPlay(0); 
                else { ae.play(); drawVinylVisual(); } 
            } else ae.pause(); 
            musicUI(); 
        }
        function musicPlay(i) { 
            initAudioAnalyzer();
            if(audioCtx.state === 'suspended') audioCtx.resume();
            
            if(i < 0) i = pList.length - 1;
            if(i >= pList.length) i = 0;
            cS = i; ae.src = pList[i].url; ae.play(); 
            document.getElementById('player-title').innerText = pList[i].name; 
            musicUI(); 
            updateQueueUI();
            if(document.getElementById('music-grid-full')) loadMusicGrid();
            drawVinylVisual(); // Kickstart visualizer
        }
        function musicUI() { 
            document.getElementById('play-btn-ico').className = ae.paused?'fas fa-play':'fas fa-pause'; 
            (ae.paused?document.getElementById('vinyl-rec').classList.remove('playing'):document.getElementById('vinyl-rec').classList.add('playing')); 
        }
        function musicNext() { if(pList.length>0) musicPlay((cS+1)%pList.length); }
        function musicPrev() { if(pList.length>0) musicPlay(cS-1 < 0 ? pList.length-1 : cS-1); }
        function aePulse() { 
            const pb = document.getElementById('player-bar');
            if(pb && ae.duration) {
                pb.style.width = (ae.currentTime/ae.duration*100)+'%';
            }
        }

        function toggleQueue() {
            const q = document.getElementById('player-queue');
            q.style.display = q.style.display === 'none' ? 'block' : 'none';
        }

        function updateQueueUI() {
            const l = document.getElementById('queue-list-items');
            if(!l) return;
            l.innerHTML = pList.map((f, i) => `
                <div class="sys-row" style="font-size:0.75rem; border:none; ${cS === i ? 'color:var(--primary); font-weight:bold;' : ''}" onclick="musicPlay(${i})">
                    <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${i+1}. ${f.name}</span>
                </div>
            `).join('');
        }
        async function loadHUDNews() { const r = await fetch('disk.php?action=get_real_news'); const d = await r.json(); document.getElementById('news-hud-list').innerHTML = d.slice(0, 3).map(n => `<p style="margin-bottom:10px;"><b>• ${n.title}</b></p>`).join(''); }
        async function loadNewsFull() { const r = await fetch('disk.php?action=get_real_news'); const d = await r.json(); document.getElementById('news-full-list').innerHTML = d.map(n => `<div class="hud-card"><h3>${n.title}</h3><p style="margin-top:10px; opacity:0.8;">${n.desc}</p></div>`).join(''); }
        let cExp = ''; function calcIn(n) { cExp += n; document.getElementById('c-disp').value = cExp; } function calcOp(o) { cExp += o; document.getElementById('c-disp').value = cExp; } function calcClr() { cExp = ''; document.getElementById('c-disp').value = ''; } function calcEval() { try { cExp = eval(cExp).toString(); document.getElementById('c-disp').value = cExp; } catch(e) { cExp = 'ERROR'; document.getElementById('c-disp').value = cExp; } }
        function startTicker() { 
            const tick = () => {
                const now = new Date();
                document.getElementById('clocker').innerText = now.toLocaleTimeString('en-GB');
                
                // Date Widget Update
                if(document.getElementById('big-date')) {
                    const dOpts = { month: 'long', day: '2-digit', year: 'numeric' };
                    document.getElementById('big-date').innerText = now.toLocaleDateString('en-US', dOpts).toUpperCase();
                    
                    const wOpts = { weekday: 'long' };
                    document.getElementById('big-day').innerText = now.toLocaleDateString('en-US', wOpts).toUpperCase() + ' | NODE_ACTIVE';
                }
            };
            tick();
            setInterval(tick, 1000); 
        }

        // CONTEXT MENU LOGIC
        window.oncontextmenu = (e) => {
            e.preventDefault();
            const ctx = document.getElementById('desktop-ctx');
            ctx.style.display = 'block';
            ctx.style.left = e.clientX + 'px';
            ctx.style.top = e.clientY + 'px';
        };
        window.onclick = () => { document.getElementById('desktop-ctx').style.display = 'none'; };

        function autoArrange() { localStorage.removeItem('m_os_pos_v3'); location.reload(); }
        function toggleHUD() { const hud = document.getElementById('main-hud'); hud.style.display = hud.style.display === 'none' ? 'flex' : 'none'; }
        function resetOS() { if(confirm('Wipe all Os layouts and settings?')) { localStorage.clear(); location.reload(); } }

        // SMART HUD GALLERY LOGIC
        let hudGalItems = [], hudGalIdx = 0;
        async function initHUDGallery() {
            try {
                const r = await fetch('disk.php?action=list&dir=gallery');
                const d = await r.json();
                hudGalItems = d.files || [];
                if(hudGalItems.length === 0) return;
                
                const container = document.getElementById('hud-gallery-slideshow');
                container.innerHTML = hudGalItems.map((f, i) => `
                    <div class="hud-slide-wrap ${i===0?'active':''}">
                        <div class="hud-slide-bg" style="background-image: url('${f.url}')"></div>
                        <img src="${f.url}" class="hud-slide-img">
                        <div class="hud-slide-overlay"></div>
                    </div>
                `).join('');

                if(hudGalItems.length > 1) {
                    setInterval(() => {
                        const wraps = container.querySelectorAll('.hud-slide-wrap');
                        wraps[hudGalIdx].classList.remove('active');
                        hudGalIdx = (hudGalIdx + 1) % wraps.length;
                        wraps[hudGalIdx].classList.add('active');
                    }, 5000);
                }
            } catch(e) { console.error("Slideshow Error:", e); }
        }

        // AUTO LOGOUT SYSTEM (Inactivity Detection - 30 Minutes)
        let idleTime = 0;
        const IDLE_LIMIT = 30; 
        function resetIdleTimer() { idleTime = 0; }
        window.onmousemove = resetIdleTimer;
        window.onkeypress = resetIdleTimer;
        window.onclick = resetIdleTimer;
        window.ontouchstart = resetIdleTimer;

        setInterval(() => {
            idleTime++;
            if (idleTime >= IDLE_LIMIT) {
                location.href = 'logout.php?reason=inactivity';
            }
        }, 60000); 

        window.onload = boot;
    </script>
</body>
</html>

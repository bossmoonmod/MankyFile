<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION['m_os_auth']) || $_SESSION['m_os_auth'] !== true) {
    header('Location: login.php');
    exit();
}
require_once 'db.php';

// SECURITY: Log Access IP
try {
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    $ua = $_SERVER['HTTP_USER_AGENT'];
    // Explicitly use local time in INSERT
    $stmt = $db->prepare("INSERT INTO access_logs (ip_address, user_agent, accessed_at) VALUES (?, ?, (DATETIME('now', 'localtime')))");
    $stmt->execute([$ip, $ua]);
} catch(Exception $e) {}

$app_registry = 'App_store/registry.json';
$apps = [];
if (file_exists($app_registry)) {
    $apps = json_decode(file_get_contents($app_registry), true) ?: [];
}
if (empty($apps)) {
    $apps = [
        ['id'=>'monitor','title'=>'Heartbeat','icon'=>'fa-heartbeat', 'file'=>'monitor.php'],
        ['id'=>'disk','title'=>'Disk','icon'=>'fa-hdd', 'file'=>'disk_win.php'],
        ['id'=>'music_win','title'=>'Music Lab','icon'=>'fa-compact-disc', 'file'=>'music_win.php'],
        ['id'=>'notes','title'=>'Notes','icon'=>'fa-sticky-note', 'file'=>'notes.php'],
        ['id'=>'gallery','title'=>'Gallery','icon'=>'fa-images', 'file'=>'gallery.php'],
        ['id'=>'walls','title'=>'Aura','icon'=>'fa-palette', 'file'=>'walls.php'],
        ['id'=>'calc','title'=>'Calculator','icon'=>'fa-calculator', 'file'=>'calc.php'],
        ['id'=>'editor','title'=>'Canvas','icon'=>'fa-magic', 'file'=>'editor.php'],
        ['id'=>'store','title'=>'App Store','icon'=>'fa-shop', 'file'=>'store.php']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>MankyOS Mobile | Touch Experience</title>
    <link rel="icon" type="image/png" href="MankyFileOS.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #ffafbd; --accent: #a1c4fd; --glass: rgba(15, 15, 25, 0.8);
            --win-bg: rgba(10, 10, 20, 0.98); --glass-border: rgba(255, 255, 255, 0.1);
            --green: #88d8b0; --red: #ff6b6b; --text: #f0f0f0;
        }
        * { margin:0; padding:0; box-sizing:border-box; -webkit-tap-highlight-color: transparent; }
        body { 
            background: #000 url('image/wallpapers/default.jpg') center/cover fixed; 
            font-family: 'Outfit', sans-serif; color: var(--text); height: 100vh; overflow: hidden; display: flex; flex-direction: column;
        }
        #bg-video { position: fixed; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -1; display: none; }

        .desktop { flex: 1; position: relative; padding: 20px; overflow-y: auto; overflow-x: hidden; }
        
        /* FIXED SQUARE GRID FOR IPAD/MOBILE - PREVENTS STRETCHING */
        #icon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, 105px);
            justify-content: center; /* Center icons on wide screens */
            grid-auto-rows: 125px;
            gap: 25px 20px;
            padding: 30px;
            padding-bottom: 180px;
        }
        .icon { 
            display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; 
            cursor:pointer; border-radius:15px; transition:0.2s; text-align:center;
            background: rgba(255,255,255,0.03); border: 1px solid transparent;
        }
        .icon:active { background: rgba(255,255,255,0.1); transform: scale(0.95); border-color: var(--primary); }
        .icon i { font-size: 2.8rem; color: var(--primary); filter: drop-shadow(0 0 10px rgba(255,175,189,0.3)); }
        .icon span { font-size: 0.8rem; font-weight: 500; text-shadow: 0 2px 4px #000; color: rgba(255,255,255,0.9); margin-top: 5px; }

        /* FULLSCREEN WINDOWS FOR MOBILE */
        .window { 
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: var(--win-bg); z-index: 1000; display: none; flex-direction: column;
            animation: slideUp 0.35s cubic-bezier(0.2, 0.8, 0.2, 1);
        }
        @keyframes slideUp { from { transform: translateY(100%); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .window.active { display: flex; }
        
        .win-head { padding: 15px 20px; border-bottom: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); }
        .win-title { font-weight: 700; color: var(--accent); font-size: 1rem; }
        .win-close { width: 35px; height: 35px; background: rgba(255,107,107,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--red); cursor: pointer; }
        .win-body { padding: 20px; flex: 1; overflow-y: auto; -webkit-overflow-scrolling: touch; }

        /* MOBILE PLAYER */
        .player { 
            position: fixed; bottom: 70px; left: 50%; transform: translateX(-50%); width: 92%; 
            background: rgba(10,10,25,0.9); border: 1px solid var(--glass-border); border-radius: 25px; 
            padding: 12px 20px; display: flex; align-items: center; gap: 15px; backdrop-filter: blur(25px); 
            box-shadow: 0 20px 50px rgba(0,0,0,0.6); z-index: 900;
        }
        .vinyl-container { width: 45px; height: 45px; position: relative; flex-shrink: 0; }
        #vinyl-canvas { position: absolute; top: -5px; left: -5px; z-index: 2; width: 55px; height: 55px; }
        .vinyl { width: 45px; height: 45px; background: #000; border-radius: 50%; border: 2px solid #222; animation: spin 4s linear infinite; animation-play-state: paused; }
        .vinyl.playing { animation-play-state: running; }
        .player-info { flex: 1; min-width: 0; }
        #player-title { font-size: 0.85rem; font-weight: 700; color: var(--primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .player-controls { display: flex; gap: 15px; font-size: 1.3rem; align-items: center; }

        /* BOTTOM DOCK */
        .taskbar { 
            position: fixed; bottom: 0; left: 0; width: 100%; height: 60px; 
            background: rgba(0,0,0,0.85); backdrop-filter: blur(30px); border-top: 1px solid var(--glass-border);
            display: flex; justify-content: space-around; align-items: center; z-index: 2000;
        }
        .taskbar-btn { color: rgba(255,255,255,0.6); font-size: 1.6rem; cursor: pointer; padding: 12px; transition: 0.3s; border-radius: 12px; }
        .taskbar-btn:active { background: rgba(255,255,255,0.1); color: var(--primary); }
        .taskbar-btn.active { color: var(--primary); transform: translateY(-3px); text-shadow: 0 0 20px var(--primary); }
        #fs-btn { background: rgba(255,175,189,0.1); border: 1px solid rgba(255,175,189,0.2); }

        /* ORIENTATION GUARD */
        #portrait-warn {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #000; z-index: 10000;
            display: none; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 30px;
        }
        @media screen and (orientation: portrait) {
            #portrait-warn { display: flex; }
        }

        .btn { background: rgba(255,255,255,0.08); border: 1px solid var(--glass-border); padding: 12px 20px; border-radius: 15px; color: #fff; text-align: center; font-size: 0.9rem; }
        .sys-row { display: flex; justify-content: space-between; padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.06); align-items: center; }
        
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div id="portrait-warn">
        <i class="fas fa-sync-alt" style="font-size: 4rem; color: var(--primary); margin-bottom: 20px;"></i>
        <h2>LANDSCAPE MODE ONLY</h2>
        <p style="opacity:0.6; margin-top:10px;">Please rotate your device for the full MankyOS experience.</p>
    </div>

    <video id="bg-video" autoplay muted loop playsinline></video>

    <div class="desktop">
        <div id="icon-grid"></div>
    </div>

    <!-- MOBILE PLAYER -->
    <div class="player">
        <div class="vinyl-container">
            <canvas id="vinyl-canvas" width="60" height="60"></canvas>
            <div class="vinyl" id="vinyl-rec"></div>
        </div>
        <div class="player-info">
            <div id="player-title">Welcome to MankyOS</div>
            <div style="height:3px; background:rgba(255,255,255,0.1); margin-top:5px; border-radius:10px;"><div id="player-bar" style="height:100%; background:var(--primary); width:0%; border-radius:10px;"></div></div>
        </div>
        <div class="player-controls">
            <i class="fas fa-play" id="play-btn-ico" onclick="musicToggle()"></i>
            <i class="fas fa-step-forward" onclick="musicNext()"></i>
        </div>
    </div>

    <!-- DOCK / TASKBAR -->
    <div class="taskbar">
        <i class="fas fa-th-large taskbar-btn active" onclick="allOff()"></i>
        <i class="fas fa-compact-disc taskbar-btn" onclick="openW('music_win')"></i>
        <i class="fas fa-expand taskbar-btn" id="fs-btn" onclick="toggleFS()"></i>
        <i class="fas fa-cog taskbar-btn" onclick="openW('prism')"></i>
        <div id="clock" style="font-family:'JetBrains Mono'; font-weight:700; color:var(--primary); font-size:0.9rem;">00:00</div>
    </div>

    <!-- APP WINDOWS -->
    <div id="win-viewer" class="window">
        <div class="win-head">
            <div class="win-title"><i class="fas fa-eye"></i> Neural Viewer</div>
            <div class="win-close" onclick="closeW('viewer')"><i class="fas fa-times"></i></div>
        </div>
        <div class="win-body" style="padding:0; background:#000; position:relative;">
            <img id="view-img" style="width:100%; height:100%; object-fit:contain;">
            <div style="position:absolute; bottom:20px; left:50%; transform:translateX(-50%); display:flex; gap:10px;">
                <button class="btn" onclick="downloadCurrent()"><i class="fas fa-download"></i></button>
                <button class="btn" onclick="closeW('viewer')">Close</button>
            </div>
        </div>
    </div>

    <?php foreach($apps as $app): ?>
        <div id="win-<?php echo $app['id']; ?>" class="window">
            <div class="win-head">
                <div class="win-title"><i class="fas <?php echo $app['icon']; ?>"></i> <?php echo $app['title']; ?></div>
                <div class="win-close" onclick="closeW('<?php echo $app['id']; ?>')"><i class="fas fa-times"></i></div>
            </div>
            <div class="win-body">
                <?php 
                    $file = 'App_store/' . $app['file'];
                    if(file_exists($file)) include $file; 
                ?>
            </div>
        </div>
    <?php endforeach; ?>

    <audio id="os-ae" ontimeupdate="aePulse()" onended="musicNext()"></audio>

    <script>
        let pList = [], cS = -1;
        const ae = document.getElementById('os-ae');
        let audioCtx, analyser, dataArray;

        async function boot() {
            try {
                const r = await fetch('disk.php?action=get_apps');
                const apps = await r.json();
                drawIcons(apps);
            } catch(e) { drawIcons(<?php echo json_encode($apps); ?>); }
            
            startTicker();
            drawPlaylist();
            loadOSState();
        }

        function drawIcons(apps) {
            const g = document.getElementById('icon-grid');
            if(!g) return;
            g.innerHTML = '';
            if(!Array.isArray(apps)) return;
            apps.forEach(a => {
                const d = document.createElement('div'); d.className = 'icon';
                const prefix = a.isBrand ? 'fab' : 'fas';
                d.innerHTML = `<i class="${prefix} ${a.icon}"></i><span>${a.title}</span>`;
                d.onclick = () => openW(a.id);
                g.appendChild(d);
            });
        }

        function openW(id) {
            const w = document.getElementById('win-' + id);
            if(w) w.classList.add('active');
            if(id === 'music_win') loadMusicGrid();
            if(id === 'gallery') loadGalleryFull();
            if(id === 'walls') loadWallsFull();
            if(id === 'disk') loadStorage('uploads');
            if(id === 'notes') loadNotesFull();
            if(id === 'news_app') loadNewsFull();
            if(id === 'store') loadStoreApps();
            saveOSState();
        }
        function closeW(id) { document.getElementById('win-' + id).classList.remove('active'); saveOSState(); }
        function allOff() { document.querySelectorAll('.window').forEach(w => w.classList.remove('active')); saveOSState(); }

        function saveOSState() {
            const active = [];
            document.querySelectorAll('.window.active').forEach(w => active.push(w.id.replace('win-','')));
            localStorage.setItem('m_os_mobile_active', JSON.stringify(active));
        }
        function loadOSState() {
            const active = JSON.parse(localStorage.getItem('m_os_mobile_active') || '[]');
            active.forEach(id => openW(id));
            const aura = localStorage.getItem('m_os_aura');
            if(aura) setAura(aura);
        }

        function setAura(url) {
            if(!url || typeof url !== 'string') return;
            const v = document.getElementById('bg-video');
            const isVid = url.match(/\.(mp4|webm|mov)$/i);
            if(isVid) { 
                if(v) {
                    v.src = url; v.style.display = 'block'; 
                    v.play().catch(e=>{}); 
                }
                document.body.style.backgroundImage = 'none'; 
            }
            else { 
                if(v) { v.pause(); v.style.display = 'none'; }
                document.body.style.backgroundImage = `url('${url}')`; 
            }
            localStorage.setItem('m_os_aura', url);
        }

        // SHARED DATA LOGIC (SYNCED WITH PC)
        async function loadGalleryFull() { 
            const r = await fetch('disk.php?action=list&dir=gallery'); const d = await r.json(); 
            const g = document.getElementById('gallery-grid-full'); if(!g) return;
            g.innerHTML = ''; 
            (d.files||[]).forEach(f => { 
                const div = document.createElement('div'); div.className = 'gal-item'; 
                div.style.backgroundImage = `url('${f.url}')`;
                div.onclick = () => openViewer(f.url); 
                g.appendChild(div); 
            }); 
        }
        async function loadWallsFull() { 
            const r = await fetch('disk.php?action=list&dir=wallpapers'); const d = await r.json(); 
            const g = document.getElementById('aura-grid-full'); if(!g) return;
            g.innerHTML = ''; 
            (d.files||[]).forEach(f => { 
                const div = document.createElement('div'); 
                div.style.cssText = `aspect-ratio:1.7; border-radius:12px; background:#111; overflow:hidden; border:1px solid var(--glass-border);`;
                if(f.name.match(/\.(mp4|webm|mov)$/i)) {
                    div.innerHTML = `<video src="${f.url}" muted style="width:100%; height:100%; object-fit:cover;"></video>`;
                } else {
                    div.style.background = `url('${f.url}') center/cover`;
                }
                div.onclick = () => setAura(f.url); 
                g.appendChild(div); 
            }); 
        }
        function openViewer(url) { document.getElementById('view-img').src = url; openW('viewer'); }
        function downloadCurrent() { const url = document.getElementById('view-img').src; const a = document.createElement('a'); a.href = url; a.download = url.split('/').pop(); a.click(); }

        async function loadStorage(dir) {
            const r = await fetch('disk.php?action=list&dir='+dir); const d = await r.json();
            const l = document.getElementById('disk-list-box'); if(!l) return;
            l.innerHTML = (d.files||[]).map(f => `<div class="sys-row" onclick="window.open('${f.url}','_blank')"><span><i class="fas fa-file"></i> ${f.name}</span> <small>${f.date}</small></div>`).join('');
        }
        async function loadNotesFull() {
            const r = await fetch('disk.php?action=get_notes'); const d = await r.json();
            if(d.length>0 && document.getElementById('n-t')) { document.getElementById('n-t').value = d[0].title; document.getElementById('n-c').value = d[0].content; }
        }

        // MUSIC MOBILE VERSION
        async function drawPlaylist() { 
            const r = await fetch('disk.php?action=list&dir=music'); const d = await r.json(); 
            pList = d.files||[]; 
        }
        function musicToggle() { if(ae.paused) { if(!ae.src && pList.length>0) musicPlay(0); else ae.play(); } else ae.pause(); musicUI(); }
        function musicPlay(i) {
            initAudioAnalyzer();
            if(i < 0) i = pList.length - 1; if(i >= pList.length) i = 0;
            cS = i; ae.src = pList[i].url; ae.play();
            document.getElementById('player-title').innerText = pList[i].name;
            musicUI();
        }
        function musicNext() { if(pList.length>0) musicPlay((cS+1)%pList.length); }
        function musicUI() { 
            document.getElementById('play-btn-ico').className = ae.paused?'fas fa-play':'fas fa-pause'; 
            (ae.paused?document.getElementById('vinyl-rec').classList.remove('playing'):document.getElementById('vinyl-rec').classList.add('playing')); 
        }
        function aePulse() { if(ae.duration) document.getElementById('player-bar').style.width = (ae.currentTime/ae.duration*100)+'%'; }

        function initAudioAnalyzer() {
            if(audioCtx) return;
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            analyser = audioCtx.createAnalyser();
            const source = audioCtx.createMediaElementSource(ae);
            source.connect(analyser); analyser.connect(audioCtx.destination);
            analyser.fftSize = 64; dataArray = new Uint8Array(analyser.frequencyBinCount);
            drawVisual();
        }
        function drawVisual() {
            const canvas = document.getElementById('vinyl-canvas'); const ctx = canvas.getContext('2d');
            requestAnimationFrame(drawVisual); if(!analyser) return;
            analyser.getByteFrequencyData(dataArray);
            ctx.clearRect(0,0,60,60);
            for(let i=0; i<dataArray.length;i++){
                const h = dataArray[i]/10; const a = (i/dataArray.length)*Math.PI*2;
                ctx.strokeStyle = `hsl(${(i/dataArray.length)*360},100%,70%)`;
                ctx.lineWidth=2; ctx.beginPath(); ctx.moveTo(30+Math.cos(a)*20,30+Math.sin(a)*20);
                ctx.lineTo(30+Math.cos(a)*(20+h),30+Math.sin(a)*(20+h)); ctx.stroke();
            }
        }

        async function uploadUnit(type, input, cb) {
            if(!input.files || input.files.length === 0) return;
            const fd = new FormData();
            if(input.files.length > 1) {
                for(let i=0; i<input.files.length; i++) { fd.append('files[]', input.files[i]); }
            } else { fd.append('file', input.files[0]); }
            try {
                const r = await fetch('disk.php?action=upload_any&type='+type, {method:'POST', body:fd});
                const d = await r.json();
                if(d.success && cb) cb();
            } catch(e) { console.error("Upload failed"); }
        }

        function startTicker() {
            setInterval(() => {
                const now = new Date();
                document.getElementById('clock').innerText = now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0');
            }, 1000);
        }

        function toggleFS() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(e => {
                    alert(`Error attempting to enable full-screen mode: ${e.message}`);
                });
                document.getElementById('fs-btn').classList.add('active');
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                    document.getElementById('fs-btn').classList.remove('active');
                }
            }
        }

        window.onload = boot;
    </script>
</body>
</html>

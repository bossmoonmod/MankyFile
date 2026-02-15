<div class="win-head" onmousedown="wDrag(event,'win-prism')">
    <div class="win-title"><i class="fas fa-eye-dropper"></i> Prism Theme Engine</div>
    <div class="win-ctrls"><div class="dot dot-red" onclick="closeW('prism')"></div></div>
</div>
<div class="win-body">
    <h3 style="margin-bottom:20px; color:var(--accent);">Neural Color Palettes</h3>
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
        <div class="hud-card" style="cursor:pointer; display:flex; align-items:center; gap:15px;" onclick="setTheme('#ffafbd','#a1c4fd')">
            <div style="width:30px; height:30px; border-radius:50%; background:linear-gradient(45deg, #ffafbd, #a1c4fd);"></div>
            <span>Lofi Dream (Default)</span>
        </div>
        <div class="hud-card" style="cursor:pointer; display:flex; align-items:center; gap:15px;" onclick="setTheme('#00f2ff','#7000ff')">
            <div style="width:30px; height:30px; border-radius:50%; background:linear-gradient(45deg, #00f2ff, #7000ff);"></div>
            <span>Cyber Neon</span>
        </div>
        <div class="hud-card" style="cursor:pointer; display:flex; align-items:center; gap:15px;" onclick="setTheme('#50fa7b','#282a36')">
            <div style="width:30px; height:30px; border-radius:50%; background:linear-gradient(45deg, #50fa7b, #282a36);"></div>
            <span>Emerald Night</span>
        </div>
        <div class="hud-card" style="cursor:pointer; display:flex; align-items:center; gap:15px;" onclick="setTheme('#ff9d00','#ff4d00')">
            <div style="width:30px; height:30px; border-radius:50%; background:linear-gradient(45deg, #ff9d00, #ff4d00);"></div>
            <span>Solar Flare</span>
        </div>
        <div class="hud-card" style="cursor:pointer; display:flex; align-items:center; gap:15px;" onclick="setTheme('#ffffff','#00d2ff')">
            <div style="width:30px; height:30px; border-radius:50%; background:linear-gradient(45deg, #ffffff, #00d2ff);"></div>
            <span>Arctic Frost</span>
        </div>
        <div class="hud-card" style="cursor:pointer; display:flex; align-items:center; gap:15px;" onclick="setTheme('#ff79c6','#bd93f9')">
            <div style="width:30px; height:30px; border-radius:50%; background:linear-gradient(45deg, #ff79c6, #bd93f9);"></div>
            <span>Vampire Goth</span>
        </div>
        
        <!-- FLUID MODES -->
        <div class="hud-card" style="cursor:pointer; display:flex; align-items:center; gap:15px; grid-column: span 2; background: linear-gradient(90deg, #ff00ff, #0015ff, #00f2ff, #37ff00, #ff9d00, #ff00ff); background-size: 200% 100%; animation: auroraPreview 5s infinite linear;" onclick="setTheme(null, null, 'aurora')">
            <b style="color:#fff; text-shadow:0 0 10px rgba(0,0,0,0.5);">NEURAL AURORA FLOW</b>
        </div>
        <div class="hud-card" style="cursor:pointer; display:flex; align-items:center; gap:15px; grid-column: span 2; background: linear-gradient(90deg, #ff0000, #ffff00, #00ff00, #00ffff, #0000ff, #ff00ff, #ff0000); animation: rainbowFlow 5s infinite linear;" onclick="setTheme(null, null, 'rainbow')">
            <b style="color:#000; text-shadow:0 0 10px #fff;">RAINBOW SPECTRUM FLOW</b>
        </div>
    </div>
    
    <style>
        @keyframes auroraPreview {
            0% { background-position: 0% 50%; }
            100% { background-position: 200% 50%; }
        }
    </style>
    
    <div style="margin-top:30px; padding:20px; background:rgba(255,255,255,0.05); border-radius:20px; border:1px solid var(--glass-border);">
        <h4 style="margin-bottom:10px;">Custom Neural Sync</h4>
        <div style="display:flex; gap:15px; align-items:center;">
            <input type="color" id="p-pick" value="#ffafbd" style="width:50px; height:50px; border:none; background:none; cursor:pointer;" onchange="setTheme(this.value, document.getElementById('a-pick').value, 'solid')">
            <input type="color" id="a-pick" value="#a1c4fd" style="width:50px; height:50px; border:none; background:none; cursor:pointer;" onchange="setTheme(document.getElementById('p-pick').value, this.value, 'solid')">
            <small>Select manual hex frequencies</small>
        </div>
    </div>
</div>

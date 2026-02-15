<div class="win-head" onmousedown="wDrag(event,'win-notes')">
    <div class="win-title"><i class="fas fa-sticky-note"></i> Persistent Echoes</div>
    <div class="win-ctrls"><div class="dot dot-red" onclick="closeW('notes')"></div></div>
</div>
<div class="win-body">
    <input id="n-t" placeholder="TITLE_CHUNK" style="width:100%; padding:15px; background:rgba(255,255,255,0.05); border:1px solid var(--glass-border); color:#fff; border-radius:15px; margin-bottom:15px; outline:none; font-family:inherit; font-weight:700;">
    <textarea id="n-c" style="width:100%; height:300px; background:rgba(255,255,255,0.03); border:1px solid var(--glass-border); color:#fff; border-radius:15px; padding:20px; outline:none; resize:none; font-family:inherit;"></textarea>
    <div style="display:flex; justify-content:flex-end; margin-top:15px; gap:12px;">
        <button class="btn" onclick="clearNote()">NEW</button>
        <button class="btn active" onclick="saveNote()">SYNC</button>
    </div>
</div>

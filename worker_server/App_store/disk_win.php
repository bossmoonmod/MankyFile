<div class="win-head" onmousedown="wDrag(event,'win-disk')">
    <div class="win-title"><i class="fas fa-hdd"></i> Reality Disk Manager</div>
    <div class="win-ctrls"><div class="dot dot-red" onclick="closeW('disk')"></div></div>
</div>
<div class="win-body">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
        <div style="display:flex; gap:12px;">
            <button class="btn active" onclick="loadStorage('uploads',this)">/IN</button>
            <button class="btn" onclick="loadStorage('processed',this)">/OUT</button>
            <button class="btn" onclick="loadStorage('music',this)">/AUDIO</button>
            <button class="btn" onclick="loadStorage('wallpapers',this)">/AURA</button>
            <button class="btn" onclick="loadStorage('gallery',this)">/GALLERY</button>
        </div>
        <button class="btn" style="color:var(--red); font-weight:700;" onclick="purgeCurrent()"><i class="fas fa-biohazard"></i> Wipe Current Folder</button>
    </div>
    <div id="disk-list-box" style="max-height: 480px; overflow:auto; border-radius:15px; background:rgba(0,0,0,0.1);"></div>
</div>

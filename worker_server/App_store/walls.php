<div class="win-head" onmousedown="wDrag(event,'win-walls')">
    <div class="win-title"><i class="fas fa-palette"></i> Aura Visualizer</div>
    <div class="win-ctrls"><div class="dot dot-red" onclick="closeW('walls')"></div></div>
</div>
<div class="win-body">
    <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
        <button class="btn active" onclick="document.getElementById('w-up').click()">+ New Aura (Img/Vid/Gif)</button>
        <input type="file" id="w-up" hidden accept="image/*,video/*" onchange="uploadUnit('wallpapers',this,loadWallsFull)">
    </div>
    <div id="aura-grid-full" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;"></div>
</div>

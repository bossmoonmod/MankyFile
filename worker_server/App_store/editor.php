<div class="win-head" onmousedown="wDrag(event,'win-editor')">
    <div class="win-title"><i class="fas fa-magic"></i> Neural Canvas</div>
    <div class="win-ctrls"><div class="dot dot-red" onclick="closeW('editor')"></div></div>
</div>
<div class="win-body" style="display:flex; flex-direction:column; gap:20px; height:100%; overflow:hidden;">
    <div style="flex:1; background:#000; border-radius:15px; overflow:hidden; position:relative; border:1px solid var(--glass-border); display:flex; align-items:center; justify-content:center;">
        <canvas id="editor-canvas" style="max-width:100%; max-height:100%; object-fit:contain;"></canvas>
    </div>
    
    <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:center; background:rgba(0,0,0,0.3); padding:15px; border-radius:15px;">
        <button class="btn" onclick="applyFilt('brightness(1.2)')">Brightness</button>
        <button class="btn" onclick="applyFilt('contrast(1.4)')">Contrast</button>
        <button class="btn" onclick="applyFilt('grayscale(1)')">Grayscale</button>
        <button class="btn" onclick="applyFilt('sepia(1)')">Sepia</button>
        <button class="btn" onclick="applyFilt('invert(1)')">Invert</button>
        <button class="btn" onclick="applyFilt('hue-rotate(90deg)')">Hue Shift</button>
        <button class="btn" onclick="resetFilt()" style="color:var(--red)">Reset</button>
        <button class="btn active" onclick="saveEditorImg()">Export to Gallery</button>
    </div>
</div>

<script>
    let edCtx, edImg;
    function loadEditImg(url) {
        const can = document.getElementById('editor-canvas');
        edCtx = can.getContext('2d');
        edImg = new Image();
        edImg.crossOrigin = "anonymous";
        edImg.onload = () => {
            can.width = edImg.width;
            can.height = edImg.height;
            edCtx.filter = 'none';
            edCtx.drawImage(edImg, 0, 0);
        };
        edImg.src = url;
    }

    function applyFilt(f) {
        const can = document.getElementById('editor-canvas');
        edCtx.filter = f;
        edCtx.drawImage(edImg, 0, 0);
    }

    function resetFilt() {
        const can = document.getElementById('editor-canvas');
        edCtx.filter = 'none';
        edCtx.drawImage(edImg, 0, 0);
    }

    async function saveEditorImg() {
        const can = document.getElementById('editor-canvas');
        const blob = await new Promise(res => can.toBlob(res, 'image/jpeg', 0.9));
        const file = new File([blob], "edited_" + Date.now() + ".jpg", { type: "image/jpeg" });
        
        const fd = new FormData();
        fd.append('file', file);
        
        const r = await fetch('disk.php?action=upload_any&type=gallery', {method:'POST', body:fd});
        const d = await r.json();
        if(d.success) {
            alert('Image exported to Neural Gallery!');
            if(window.loadGalleryFull) loadGalleryFull();
        } else {
            alert('Export failed');
        }
    }
</script>

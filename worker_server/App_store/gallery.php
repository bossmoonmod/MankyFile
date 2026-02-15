<div class="win-head" onmousedown="wDrag(event,'win-gallery')">
    <div class="win-title"><i class="fas fa-images"></i> Neural Gallery</div>
    <div class="win-ctrls"><div class="dot dot-red" onclick="closeW('gallery')"></div></div>
</div>
<div class="win-body" id="gal-drop-zone">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:15px;">
        <div>
            <h5 style="color:var(--primary); margin:0;">IMAGE_VAULT</h5>
            <small style="opacity:0.5;">Drag & drop images here to sync with Visionary AI</small>
        </div>
        <button class="btn active" onclick="document.getElementById('gal-up').click()"><i class="fas fa-plus"></i> Upload Neural Images</button>
        <input type="file" id="gal-up" hidden accept="image/*" multiple onchange="uploadUnit('gallery',this,loadGalleryFull)">
    </div>
    
    <div id="gallery-grid-full" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap:15px;"></div>

    <style>
        #gal-drop-zone.drag-over { background: rgba(255,175,189,0.05); outline: 2px dashed var(--primary); outline-offset: -10px; }
    </style>

    <script>
        (function(){
            const zone = document.getElementById('gal-drop-zone');
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(e => {
                zone.addEventListener(e, (ev) => { ev.preventDefault(); ev.stopPropagation(); });
            });
            zone.addEventListener('dragover', () => zone.classList.add('drag-over'));
            zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
            zone.addEventListener('drop', (e) => {
                zone.classList.remove('drag-over');
                const files = e.dataTransfer.files;
                if(files.length > 0) {
                    const input = document.getElementById('gal-up');
                    input.files = files; 
                    uploadUnit('gallery', input, loadGalleryFull);
                }
            });
        })();
    </script>
</div>

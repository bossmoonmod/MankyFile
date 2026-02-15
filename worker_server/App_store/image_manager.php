<div class="win-head" onmousedown="wDrag(event,'win-image_manager')">
    <div class="win-title"><i class="fas fa-eye"></i> Image Hosting Moderator</div>
    <div class="win-ctrls"><div class="dot dot-red" onclick="closeW('image_manager')"></div></div>
</div>
<div class="win-body">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:15px;">
        <div>
            <h4 style="color:var(--accent); margin:0;">MODERATION_HUB</h4>
            <small style="opacity:0.6;">Monitor and manage hosted images from MankyFile</small>
        </div>
        <button class="btn" onclick="loadHostedImages()"><i class="fas fa-sync-alt"></i> Refresh</button>
    </div>

    <div id="hosted-images-list" style="display:flex; flex-direction:column; gap:12px;">
        <div style="text-align:center; padding:40px; opacity:0.4;">Scanning for hosted assets...</div>
    </div>
</div>

<script>
    async function loadHostedImages() {
        const container = document.getElementById('hosted-images-list');
        try {
            const api_key = "<?php echo API_KEY; ?>";
            const r = await fetch('image_api.php?action=list&key=' + api_key);
            const d = await r.json();
            
            if(!d.success || !d.images || d.images.length === 0) {
                container.innerHTML = '<div style="text-align:center; padding:40px; opacity:0.4;">No hosted images found.</div>';
                return;
            }

            container.innerHTML = d.images.map(img => `
                <div class="sys-row" style="background:rgba(255,255,255,0.03); border-radius:15px; padding:15px; border:1px solid rgba(255,255,255,0.05);">
                    <div style="display:flex; gap:20px; align-items:center;">
                        <div style="width:70px; height:70px; border-radius:10px; background:url('image_api.php?id=${img.id}') center/cover; border:1px solid rgba(255,255,255,0.1); cursor:pointer;" onclick="window.open('image_api.php?id=${img.id}')"></div>
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:bold; color:var(--primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="${img.original_name}">${img.original_name}</div>
                            <div style="font-size:0.7rem; opacity:0.5; margin-top:3px;">ID: ${img.id} | Type: ${img.mime_type}</div>
                            <div style="font-size:0.7rem; opacity:0.8; margin-top:5px;">
                                <i class="far fa-clock"></i> Uploaded: ${img.created_at} 
                                ${img.auto_delete_at ? `<br><span style="color:var(--red)"><i class="fas fa-hourglass-half"></i> Expiry: ${img.auto_delete_at}</span>` : ''}
                            </div>
                        </div>
                        <div>
                            <button class="btn" style="color:var(--red); padding:8px 15px;" onclick="deleteHostedImage('${img.id}')"><i class="fas fa-trash-alt"></i> Delete</button>
                        </div>
                    </div>
                </div>
            `).join('');
        } catch(e) {
            container.innerHTML = '<div style="text-align:center; padding:40px; color:var(--red);">Error connecting to Neural API.</div>';
        }
    }

    async function deleteHostedImage(id) {
        if(!confirm('TERMINATE_ASSET: Are you sure you want to permanently delete this image from the server?')) return;
        try {
            const api_key = "<?php echo API_KEY; ?>";
            const r = await fetch('image_api.php?action=delete&id=' + id + '&key=' + api_key);
            const d = await r.json();
            if(d.success) {
                loadHostedImages();
            } else {
                alert('Deletion Failed: ' + d.error);
            }
        } catch(e) {
            alert('Connection Error');
        }
    }

    // Load on open
    loadHostedImages();
</script>

<div class="win-head" onmousedown="wDrag(event,'win-image_manager')">
    <div class="win-title"><i class="fas fa-shield-alt"></i> Neural Asset Moderator</div>
    <div class="win-ctrls"><div class="dot dot-red" onclick="closeW('image_manager')"></div></div>
</div>
<div class="win-body">
    <!-- Tab Controls -->
    <div style="display:flex; gap:10px; margin-bottom:20px; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:10px;">
        <button class="btn active" id="tab-btn-images" onclick="switchManagerTab('images')"><i class="fas fa-images"></i> Images</button>
        <button class="btn" id="tab-btn-links" onclick="switchManagerTab('links')"><i class="fas fa-link"></i> Short Links</button>
        <div style="flex:1"></div>
        <button class="btn" onclick="refreshManagerData()"><i class="fas fa-sync-alt"></i> Refresh</button>
    </div>

    <!-- Images Section -->
    <div id="manager-section-images" class="manager-section">
        <div id="hosted-images-list" style="display:flex; flex-direction:column; gap:12px;">
            <div style="text-align:center; padding:40px; opacity:0.4;">Scanning for hosted assets...</div>
        </div>
    </div>

    <!-- Links Section -->
    <div id="manager-section-links" class="manager-section" style="display:none;">
        <div id="short-links-list" style="display:flex; flex-direction:column; gap:12px;">
            <div style="text-align:center; padding:40px; opacity:0.4;">Scanning for shortened links...</div>
        </div>
    </div>
</div>

<script>
    let currentManagerTab = 'images';

    function switchManagerTab(tab) {
        currentManagerTab = tab;
        document.querySelectorAll('.manager-section').forEach(s => s.style.display = 'none');
        document.querySelectorAll('[id^="tab-btn-"]').forEach(b => b.classList.remove('active'));
        
        document.getElementById('manager-section-' + tab).style.display = 'block';
        document.getElementById('tab-btn-' + tab).classList.add('active');
        
        refreshManagerData();
    }

    function refreshManagerData() {
        if(currentManagerTab === 'images') loadHostedImages();
        else loadShortLinks();
    }

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

    async function loadShortLinks() {
        const container = document.getElementById('short-links-list');
        try {
            const api_key = "<?php echo API_KEY; ?>";
            const r = await fetch('shorten_api.php?action=list&key=' + api_key);
            const d = await r.json();
            
            if(!d.success || !d.links || d.links.length === 0) {
                container.innerHTML = '<div style="text-align:center; padding:40px; opacity:0.4;">No short links found.</div>';
                return;
            }

            container.innerHTML = d.links.map(link => `
                <div class="sys-row" style="background:rgba(255,255,255,0.03); border-radius:15px; padding:15px; border:1px solid rgba(255,255,255,0.05);">
                    <div style="display:flex; gap:15px; align-items:center;">
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:bold; color:var(--accent); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="${link.long_url}">${link.long_url}</div>
                            <div style="font-size:0.8rem; color:var(--primary); margin-top:4px;">Code: <span style="background:rgba(255,255,255,0.1); padding:2px 6px; border-radius:4px;">${link.short_code}</span> | Clicks: ${link.clicks}</div>
                            <div style="font-size:0.7rem; opacity:0.8; margin-top:5px;">
                                <i class="far fa-clock"></i> Created: ${link.created_at} 
                                ${link.expires_at ? `<br><span style="color:var(--red)"><i class="fas fa-calendar-times"></i> Expires: ${link.expires_at}</span>` : '<br><span style="color:#2ecc71"><i class="fas fa-infinity"></i> Forever</span>'}
                            </div>
                        </div>
                        <div>
                            <button class="btn" style="color:var(--red); padding:8px 15px;" onclick="deleteShortLink('${link.id}')"><i class="fas fa-trash-alt"></i> Delete</button>
                        </div>
                    </div>
                </div>
            `).join('');
        } catch(e) {
            container.innerHTML = '<div style="text-align:center; padding:40px; color:var(--red);">Error connecting to Neural API.</div>';
        }
    }

    async function deleteHostedImage(id) {
        if(!confirm('TERMINATE_ASSET: Are you sure you want to permanently delete this image?')) return;
        try {
            const api_key = "<?php echo API_KEY; ?>";
            const r = await fetch('image_api.php?action=delete&id=' + id + '&key=' + api_key);
            const d = await r.json();
            if(d.success) loadHostedImages();
        } catch(e) { alert('Connection Error'); }
    }

    async function deleteShortLink(id) {
        if(!confirm('TERMINATE_LINK: Are you sure you want to permanently delete this short link?')) return;
        try {
            const api_key = "<?php echo API_KEY; ?>";
            const r = await fetch('shorten_api.php?action=delete&id=' + id + '&key=' + api_key);
            const d = await r.json();
            if(d.success) loadShortLinks();
        } catch(e) { alert('Connection Error'); }
    }

    // Initial load
    refreshManagerData();
</script>

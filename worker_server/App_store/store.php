<div class="win-head" onmousedown="wDrag(event,'win-store')">
    <div class="win-title"><i class="fas fa-shop"></i> Manky Registry</div>
    <div class="win-ctrls"><div class="dot dot-red" onclick="closeW('store')"></div></div>
</div>
<div class="win-body">
    <div style="text-align:center; margin-bottom:30px;">
        <i class="fas fa-shield-alt" style="font-size:4rem; color:var(--accent); margin-bottom:15px; filter:drop-shadow(0 0 15px rgba(161,196,253,0.3))"></i>
        <h2>App_Store Registry</h2>
        <p style="opacity:0.7; font-size:0.9rem;">Manage and sync your neural modules</p>
    </div>
    
    <div style="background:rgba(0,0,0,0.2); border-radius:15px; padding:5px; border:1px solid var(--glass-border);">
        <div id="store-app-list" style="max-height: 350px; overflow:auto;">
            <!-- Apps will be loaded here -->
        </div>
    </div>
    
    <div style="margin-top:20px; text-align:center;">
        <button class="btn active" onclick="loadStoreApps()">REFRESH_REGISTRY</button>
    </div>
</div>

<script>
async function loadStoreApps() {
    const r = await fetch('disk.php?action=get_apps');
    const apps = await r.json();
    const l = document.getElementById('store-app-list');
    l.innerHTML = apps.map(a => `
        <div class="sys-row" style="padding:15px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <i class="fas ${a.icon}" style="color:var(--primary); font-size:1.2rem;"></i>
                <div>
                    <div style="font-weight:700;">${a.title}</div>
                    <small style="opacity:0.5;">ID: ${a.id} | FILE: ${a.file}</small>
                </div>
            </div>
            <b style="color:var(--green); font-size:0.7rem;">INSTALLED</b>
        </div>
    `).join('');
}
// Trigger load when store opens
if(document.getElementById('win-store').classList.contains('active')) loadStoreApps();
</script>

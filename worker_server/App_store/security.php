<div class="win-head" onmousedown="wDrag(event,'win-security')">
    <div class="win-title"><i class="fas fa-shield-alt"></i> Security & Access Logs</div>
    <div class="win-ctrls"><div class="dot dot-red" onclick="closeW('security')"></div></div>
</div>
<div class="win-body" style="background:rgba(0,0,0,0.2);">
    <div style="padding:15px; background:rgba(255,0,0,0.1); border-radius:10px; margin-bottom:20px; border:1px solid rgba(255,0,0,0.2);">
        <h4 style="margin:0; color:var(--red);"><i class="fas fa-exclamation-triangle"></i> Monitoring Active</h4>
        <small style="opacity:0.7;">บันทึกหมายเลข IP ทุกเครื่องที่เข้าถึงเซิฟเวอร์ที่บ้านของคุณ</small>
    </div>

    <div id="security-log-list" style="max-height:400px; overflow-y:auto; display:flex; flex-direction:column; gap:8px;">
        <div style="text-align:center; padding:20px; opacity:0.5;">Loading logs...</div>
    </div>
    
    <button class="btn" style="width:100%; margin-top:20px; background:var(--accent); color:#000;" onclick="loadAccessLogs()">
        <i class="fas fa-sync"></i> Refresh Logs
    </button>
</div>

<script>
async function loadAccessLogs() {
    try {
        const r = await fetch('disk.php?action=get_access_logs');
        const d = await r.json();
        const list = document.getElementById('security-log-list');
        if(!d || d.length === 0) {
            list.innerHTML = '<div style="text-align:center; padding:20px; opacity:0.5;">No logs found yet.</div>';
            return;
        }
        list.innerHTML = d.map(log => `
            <div class="sys-row" style="font-size:0.85rem; background:rgba(255,255,255,0.05); padding:10px; border-radius:8px;">
                <div style="display:flex; flex-direction:column;">
                    <b style="color:var(--green); font-size:1rem;">${log.ip_address}</b>
                    <small style="opacity:0.5; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:300px;">${log.user_agent}</small>
                </div>
                <div style="text-align:right;">
                    <span style="display:block; font-weight:700;">${log.accessed_at.split(' ')[1]}</span>
                    <small style="opacity:0.5;">${log.accessed_at.split(' ')[0]}</small>
                </div>
            </div>
        `).join('');
    } catch(e) { console.error('Log load error:', e); }
}
loadAccessLogs(); // Initial load
</script>

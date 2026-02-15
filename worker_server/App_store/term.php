<div class="win-head" onmousedown="wDrag(event,'win-term')">
    <div class="win-title" style="display:flex; align-items:center; gap:10px;">
        <i class="fas fa-terminal" style="color:var(--primary);"></i> 
        <span>MANKY_TERMINAL_PRO v2.0</span>
        <span class="pill" style="font-size:0.6rem; background:rgba(0,255,0,0.2); color:#0f0; border:1px solid #0f0;">ENCRYPTED</span>
    </div>
    <div class="win-ctrls"><div class="dot dot-red" onclick="closeW('term')"></div></div>
</div>

<div class="win-body" style="background:linear-gradient(135deg, #050510 0%, #0a1020 100%); padding:0; display:flex; flex-direction:column; border:1px solid rgba(0,255,255,0.1); box-shadow:inset 0 0 50px rgba(0,255,255,0.05);">
    
    <!-- Quick Command Bar -->
    <div style="background:rgba(255,255,255,0.03); padding:10px 20px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; gap:10px; flex-wrap:wrap;">
        <button class="term-btn" onclick="runTerm('ip addr')"><i class="fas fa-network-wired"></i> IP ADDR</button>
        <button class="term-btn" onclick="runTerm('df -h')"><i class="fas fa-hdd"></i> DISK SPACE</button>
        <button class="term-btn" onclick="runTerm('uptime')"><i class="fas fa-clock"></i> UPTIME</button>
        <button class="term-btn" onclick="runTerm('ps aux | grep python')"><i class="fas fa-spider"></i> WORKER_STATE</button>
        <button class="term-btn" style="background:rgba(255,0,0,0.1);" onclick="document.getElementById('term-output').innerHTML=''"><i class="fas fa-trash-alt"></i> CLEAR</button>
    </div>

    <div id="term-output" style="flex:1; padding:25px; overflow-y:auto; font-family:'JetBrains Mono', 'Consolas', monospace; font-size:0.9rem; line-height:1.6; text-shadow: 0 0 5px rgba(0,255,0,0.3);">
        <div style="color:var(--primary); font-weight:700; margin-bottom:15px; border-left:3px solid var(--primary); padding-left:15px;">
            <i class="fas fa-shield-virus"></i> MankyOS Pro Security Kernel v3.0.1<br>
            <small style="opacity:0.6; font-weight:400;">(c) 2026 MankyFile Corporation. Permission Level: ROOT</small>
        </div>
    </div>

    <!-- Input Area -->
    <div style="background:rgba(0,0,0,0.4); padding:15px 25px; border-top:1px solid rgba(255,255,255,0.05); display:flex; align-items:center; gap:12px; backdrop-filter:blur(10px);">
        <div style="display:flex; align-items:center;">
            <span style="color:#0f0; font-weight:700;">root</span>
            <span style="color:#fff; opacity:0.5;">@</span>
            <span style="color:var(--primary); font-weight:700;">manky</span>
            <span style="color:#fff; margin:0 5px; opacity:0.8;">:</span>
            <span style="color:var(--accent); font-weight:700;">~#</span>
        </div>
        <input type="text" id="term-input" 
            style="flex:1; background:transparent; border:none; outline:none; color:#fff; font-family:'JetBrains Mono', monospace; font-size:0.95rem; caret-color:#0f0;" 
            placeholder="Type command and press enter..." 
            onkeydown="if(event.key==='Enter') runTerm(this.value)">
    </div>
</div>

<style>
    .term-btn { 
        background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); 
        padding: 5px 12px; border-radius: 6px; color: #fff; font-size: 0.7rem; 
        cursor: pointer; transition: 0.2s; font-family: 'JetBrains Mono', monospace;
    }
    .term-btn:hover { background: var(--primary); color: #000; font-weight: 700; box-shadow: 0 0 15px var(--primary); }
    
    #term-output pre { color: rgba(255,255,255,0.8); margin: 5px 0 15px 25px; border-left: 1px solid rgba(255,255,255,0.1); padding-left: 15px; }
    .term-cmd { color: #0f0; font-weight: 700; margin-top: 15px; display: block; }
</style>

<script>
async function runTerm(cmd) {
    if(!cmd) return;
    const out = document.getElementById('term-output');
    const input = document.getElementById('term-input');
    
    // Add command to output with style
    out.innerHTML += `<div class="term-cmd">root@manky:~# <span style="color:#fff; font-weight:400;">${cmd}</span></div>`;
    input.value = '';
    
    try {
        const fd = new FormData(); fd.append('cmd', cmd);
        const r = await fetch('disk.php?action=exec', {method:'POST', body:fd});
        const d = await r.text();
        
        // Render Output
        out.innerHTML += `<pre>${escapeHtml(d)}</pre>`;
    } catch(e) {
        out.innerHTML += `<div style="color:var(--red); padding-left:25px;">[ERROR] Transmission failed: ${e}</div>`;
    }
    out.scrollTop = out.scrollHeight;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<div class="win-head" onmousedown="wDrag(event,'win-music_win')">
    <div class="win-title"><i class="fas fa-compact-disc"></i> Music Lab</div>
    <div class="win-ctrls"><div class="dot dot-red" onclick="closeW('music_win')"></div></div>
</div>
<div class="win-body" style="display:flex; flex-direction:column; gap:25px;">
    <!-- UPLOAD & TOOLS -->
    <div style="display:flex; gap:15px; background:rgba(255,175,189,0.05); padding:20px; border-radius:20px; border:1px dashed var(--primary); align-items:center;">
        <div style="flex:1;">
            <h4 style="margin-bottom:5px;">Frequency Uplink</h4>
            <small style="opacity:0.6;">Upload new neural audio waves to the library.</small>
        </div>
        <button class="btn active" onclick="document.getElementById('m-up').click()"><i class="fas fa-plus"></i> Upload</button>
        <input type="file" id="m-up" hidden accept="audio/*" onchange="uploadUnit('music',this,drawPlaylist)">
    </div>

    <!-- DRUM PAD & FX -->
    <div class="hud-card" style="background:rgba(0,0,0,0.3);">
        <h4 style="margin-bottom:15px; font-size:0.8rem; color:var(--accent); letter-spacing:1px;"><i class="fas fa-drum"></i> SONIC DRUM PAD & FX</h4>
        <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:10px;">
            <div class="btn" style="height:60px; display:flex; flex-direction:column; align-items:center; justify-content:center;" onclick="playSynth('kick')"><b>KICK</b><small style="font-size:0.6rem;">Deep</small></div>
            <div class="btn" style="height:60px; display:flex; flex-direction:column; align-items:center; justify-content:center;" onclick="playSynth('snare')"><b>SNARE</b><small style="font-size:0.6rem;">Crisp</small></div>
            <div class="btn" style="height:60px; display:flex; flex-direction:column; align-items:center; justify-content:center;" onclick="playSynth('hihat')"><b>HAT</b><small style="font-size:0.6rem;">Metal</small></div>
            <div class="btn" style="height:60px; display:flex; flex-direction:column; align-items:center; justify-content:center;" onclick="playSynth('beep')"><b>BEEP</b><small style="font-size:0.6rem;">Echo</small></div>
        </div>
        <div style="margin-top:20px; display:flex; gap:10px;">
            <button class="btn" onclick="toggleFx('boost')" id="btn-boost">Bass Boost: OFF</button>
            <button class="btn" onclick="toggleFx('pulse')" id="btn-pulse">Pulse Mode: OFF</button>
        </div>
    </div>

    <!-- PLAYLIST -->
    <div id="music-grid-full"></div>
</div>

<script>
    let fxState = { boost: false, pulse: false };
    
    function playSynth(type) {
        if(!audioCtx) initAudioAnalyzer();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);

        const now = audioCtx.currentTime;
        if(type === 'kick') {
            osc.frequency.setValueAtTime(150, now);
            osc.frequency.exponentialRampToValueAtTime(0.01, now + 0.5);
            gain.gain.setValueAtTime(1, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.5);
            osc.start(now); osc.stop(now + 0.5);
        } else if(type === 'snare') {
            osc.type = 'square';
            osc.frequency.setValueAtTime(100, now);
            gain.gain.setValueAtTime(0.5, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.2);
            osc.start(now); osc.stop(now + 0.2);
        } else if(type === 'hihat') {
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(10000, now);
            gain.gain.setValueAtTime(0.3, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.1);
            osc.start(now); osc.stop(now + 0.1);
        } else {
            osc.frequency.setValueAtTime(880, now);
            gain.gain.setValueAtTime(0.2, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 1);
            osc.start(now); osc.stop(now + 1);
        }
    }

    function toggleFx(f) {
        fxState[f] = !fxState[f];
        document.getElementById('btn-'+f).innerText = (f==='boost'?'Bass Boost: ':'Pulse Mode: ') + (fxState[f]?'ON':'OFF');
        document.getElementById('btn-'+f).classList.toggle('active', fxState[f]);
        
        if(f === 'pulse') {
            if(fxState.pulse) document.body.style.animation = 'auroraFlow 2s infinite alternate';
            else document.body.style.animation = 'none';
        }
    }
</script>

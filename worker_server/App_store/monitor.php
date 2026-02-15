<div class="win-head" onmousedown="wDrag(event,'win-monitor')">
    <div class="win-title"><i class="fas fa-heartbeat"></i> System Heartbeat [Real-time]</div>
    <div class="win-ctrls"><div class="dot dot-red" onclick="closeW('monitor')"></div></div>
</div>
<div class="win-body">
    <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:15px; margin-bottom:30px;">
        <div class="hud-card status-gauge">
            <b id="val-stability">--%</b><small>STABILITY</small>
        </div>
        <div class="hud-card status-gauge">
            <b id="val-load" style="color:var(--lofi-blue)">--%</b><small>CPU LOAD</small>
        </div>
        <div class="hud-card status-gauge">
            <b id="val-memory" style="color:var(--lofi-pink)">--%</b><small>RAM USE</small>
        </div>
        <div class="hud-card status-gauge">
            <b id="val-latency" style="color:var(--yellow)">--ms</b><small>LATENCY</small>
        </div>
    </div>
    <div class="hud-card">
        <canvas id="pulseRadarChart" style="max-height: 300px;"></canvas>
    </div>
</div>

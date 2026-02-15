<div class="win-head" onmousedown="wDrag(event,'win-ai_chat')">
    <div class="win-title">
        <i class="fas fa-robot" style="margin-right: 8px; color: var(--primary);"></i>
        Neural Chat AI Master
    </div>
    <div class="win-ctrls">
        <div class="dot dot-red" onclick="closeW('ai_chat')"></div>
    </div>
</div>
<div class="win-body" style="padding: 0; display: flex; flex-direction: column; background: #000;">
    <!-- AI Status Bar -->
    <div style="padding: 10px 20px; background: rgba(255,175,189,0.05); border-bottom: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div class="pulse-dot"></div>
            <span style="font-size: 0.75rem; font-weight: 700; letter-spacing: 1px; color: var(--primary);">SYSTEM_ONLINE</span>
        </div>
        <div style="font-size: 0.7rem; opacity: 0.6; font-family: 'JetBrains Mono';">MODEL: OPEN_WEBUI_V1</div>
    </div>

    <!-- Main Container -->
    <div style="flex: 1; position: relative; overflow: hidden;">
        <!-- Loading Overlay -->
        <div id="ai-loading" style="position: absolute; inset: 0; background: #000; z-index: 10; display: flex; flex-direction: column; align-items: center; justify-content: center; transition: 0.5s;">
            <div class="spinner" style="width: 50px; height: 50px; border-top-color: var(--primary);"></div>
            <p style="margin-top: 20px; font-size: 0.8rem; letter-spacing: 2px; color: var(--primary);">ESTABLISHING NEURAL LINK...</p>
        </div>

        <!-- The Chat Interface -->
        <iframe 
            src="https://ai.blilnkdex.biz.id" 
            id="ai-frame"
            style="width: 100%; height: 100%; border: none; background: #fff;"
            onload="document.getElementById('ai-loading').style.opacity='0'; setTimeout(()=>document.getElementById('ai-loading').style.display='none', 500)"
        ></iframe>
    </div>

    <!-- Footer / Quick Actions -->
    <div style="padding: 12px 20px; background: rgba(0,0,0,0.8); border-top: 1px solid var(--glass-border); display: flex; gap: 10px;">
        <button class="btn" style="font-size: 0.7rem;" onclick="refreshAI()">
            <i class="fas fa-sync-alt"></i> REFRESH_ENGINE
        </button>
        <button class="btn" style="font-size: 0.7rem;" onclick="popOutAI()">
            <i class="fas fa-external-link-alt"></i> EXPLODE_VIEW
        </button>
        <div style="flex:1"></div>
        <div style="display: flex; align-items: center; gap: 8px; font-size: 0.7rem; opacity: 0.5;">
            <i class="fas fa-lock"></i> SECURE_CONNECTION
        </div>
    </div>
</div>

<style>
    .pulse-dot {
        width: 8px;
        height: 8px;
        background: var(--green);
        border-radius: 50%;
        box-shadow: 0 0 10px var(--green);
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.5); opacity: 0.5; }
        100% { transform: scale(1); opacity: 1; }
    }
</style>

<script>
function refreshAI() {
    const frame = document.getElementById('ai-frame');
    const loading = document.getElementById('ai-loading');
    loading.style.display = 'flex';
    loading.style.opacity = '1';
    frame.src = frame.src;
}
function popOutAI() {
    window.open('https://ai.blilnkdex.biz.id', '_blank');
}
</script>

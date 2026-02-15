<div class="win-head" onmousedown="wDrag(event,'win-rambler')">
    <div class="win-title">
        <i class="fas fa-envelope" style="margin-right: 8px; color: #5c7be0;"></i>
        Rambler Mail
    </div>
    <div class="win-ctrls">
        <div class="dot dot-red" onclick="closeW('rambler')"></div>
    </div>
</div>
<div class="win-body" style="padding: 0; display: flex; flex-direction: column; background: #fff;">
    <!-- Simple Browser Bar -->
    <div style="background: #f1f3f4; padding: 8px 15px; border-bottom: 1px solid #ddd; display: flex; align-items: center; gap: 10px;">
        <div style="background: #fff; border-radius: 20px; border: 1px solid #ced4da; flex: 1; padding: 5px 15px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-lock" style="color: #28a745; font-size: 0.8rem;"></i>
            <span style="font-size: 0.85rem; color: #444; flex: 1;">https://mail.rambler.ru/</span>
            <i class="fas fa-redo-alt" style="color: #666; font-size: 0.8rem; cursor: pointer;" onclick="document.getElementById('rambler-frame').src += ''"></i>
        </div>
        <button class="btn active" style="padding: 4px 12px; font-size: 0.8rem; background: #5c7be0; border-radius: 4px;" onclick="window.open('https://mail.rambler.ru/', '_blank')">OPEN IN TAB</button>
    </div>

    <div style="flex: 1; position: relative;">
        <!-- Loading Spinner -->
        <div id="rambler-loading" style="position: absolute; inset: 0; background: #fff; z-index: 10; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <div class="spinner" style="width: 40px; height: 40px; border-top-color: #5c7be0;"></div>
            <p style="margin-top: 15px; color: #666; font-size: 0.9rem;">Connecting to Rambler Servers...</p>
        </div>

        <iframe 
            src="https://mail.rambler.ru/" 
            id="rambler-frame"
            style="width: 100%; height: 100%; border: none;"
            onload="document.getElementById('rambler-loading').style.display='none'; document.getElementById('drag-overlay').style.display='none';"
        ></iframe>
    </div>
</div>

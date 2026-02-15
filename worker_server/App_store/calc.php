<div class="win-head" onmousedown="wDrag(event,'win-calc')">
    <div class="win-title"><i class="fas fa-calculator"></i> Logic Unit</div>
    <div class="win-ctrls"><div class="dot dot-red" onclick="closeW('calc')"></div></div>
</div>
<div class="win-body" style="padding:15px;">
    <input id="c-disp" readonly style="width:100%; padding:20px; background:rgba(0,0,0,0.4); border:none; color:var(--lofi-pink); font-size:2rem; text-align:right; border-radius:15px; margin-bottom:15px;">
    <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:12px;">
        <button class="btn" onclick="calcIn('7')">7</button><button class="btn" onclick="calcIn('8')">8</button><button class="btn" onclick="calcIn('9')">9</button><button class="btn" onclick="calcOp('/')">/</button>
        <button class="btn" onclick="calcIn('4')">4</button><button class="btn" onclick="calcIn('5')">5</button><button class="btn" onclick="calcIn('6')">6</button><button class="btn" onclick="calcOp('*')">*</button>
        <button class="btn" onclick="calcIn('1')">1</button><button class="btn" onclick="calcIn('2')">2</button><button class="btn" onclick="calcIn('3')">3</button><button class="btn" onclick="calcOp('-')">-</button>
        <button class="btn" onclick="calcIn('0')">0</button><button class="btn" onclick="calcIn('.')">.</button><button class="btn active" onclick="calcEval()">=</button><button class="btn" onclick="calcOp('+')">+</button>
        <button class="btn" style="grid-column: span 4; border-color:var(--red);" onclick="calcClr()">CLEAR</button>
    </div>
</div>

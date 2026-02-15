<?php
/**
 * Manky Neural Search Engine - v17 (Neural Hybrid Prime)
 * Optimized for high-resiliency and Ubuntu 20/22/24 server architectures.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_GET['q'])) {
    $query = $_GET['q'];
    
    // Using Ecosia/Bing sources which are very stable for server-side nodes
    $url = "https://www.ecosia.org/search?q=" . urlencode($query);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $html = curl_exec($ch);
    $curl_err = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // curl_close is deprecated in PHP 8.5+ and optional since 8.0

    echo "<html><head><meta charset='utf-8'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
    <style>
        :root {
            --g-bg: #202124;
            --g-header: #303134;
            --g-link: #8ab4f8;
            --g-text: #bdc1c6;
            --g-url: #9aa0a6;
            --g-border: #3c4043;
        }
        body { background: var(--g-bg); color: var(--g-text); font-family: 'Segoe UI', Tahoma, sans-serif; margin: 0; padding: 0; overflow-x: hidden; }
        .g-header { position: sticky; top: 0; background: var(--g-bg); border-bottom: 1px solid var(--g-border); padding: 15px 30px; z-index: 100; }
        .g-search-box { width: 100%; max-width: 600px; display: flex; align-items: center; background: var(--g-header); border-radius: 24px; padding: 8px 18px; box-shadow: 0 1px 6px rgba(0,0,0,0.3); border: 1px solid #5f6368; }
        .g-search-box input { flex: 1; background: transparent; border: none; outline: none; color: #fff; font-size: 0.95rem; }
        .g-container { max-width: 1200px; margin: 20px 30px; display: flex; gap: 40px; }
        .g-main { flex: 1; max-width: 650px; }
        .g-side { width: 360px; }
        .g-result { margin-bottom: 28px; transition: 0.2s; }
        .g-result:hover { background: rgba(255,255,255,0.02); }
        .g-result-title { font-size: 1.2rem; color: var(--g-link); text-decoration: none; display: block; margin-bottom: 4px; }
        .g-result-title:hover { text-decoration: underline; }
        .g-result-snippet { font-size: 0.9rem; line-height: 1.5; color: var(--g-text); }
        .g-result-url { font-size: 0.8rem; color: var(--g-url); margin-bottom: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        
        .g-k-card { background: var(--g-header); border: 1px solid var(--g-border); border-radius: 12px; padding: 20px; }
        .g-k-title { font-size: 1.5rem; color: #fff; margin-bottom: 10px; }
        .g-badge { background: #4285F4; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; }
    </style></head><body>";

    echo "<div class='g-header'>
            <div class='g-search-box'>
                <input type='text' value='".htmlspecialchars($query)."' id='inner-q'>
                <i class='fas fa-search' style='color:#8ab4f8; cursor:pointer;' onclick='window.parent.performInternalSearch()'></i>
            </div>
          </div>";

    echo "<div class='g-container'><div class='g-main'>";

    if (!$html || $http_code != 200) {
        echo "<div style='padding:50px; text-align:center;'>
                <i class='fas fa-plug-circle-xmark' style='font-size:3rem; color:#ff4d4d; margin-bottom:20px; display:block;'></i>
                <h3 style='color:#fff'>NEURAL_NODE_FAIL (HTTP $http_code)</h3>
                <p style='color:#666;'>CURL: $curl_err</p>
                <p>โปรดตรวจสอบการติดตั้ง php-curl และการตั้งค่า Firewall ของเครื่อง Ubuntu ของคุณ</p>
                <a href='https://www.google.com/search?q=".urlencode($query)."' target='_blank' style='color:#8ab4f8;'>พยายามเปิดผ่านหน้าต่างใหม่</a>
              </div>";
    } else {
        // ULTRA ROBUST PARSING (Regex Fallback)
        $count = 0;
        // Looking for the pattern of search results (Link + Title + Snippet)
        // This is a broad regex designed to capture results from Ecosia/Bing/Mojeek
        preg_match_all('/<a[^>]+class="result-link"[^>]+href="([^"]+)"[^>]*>(.*?)<\/a>.*?<p[^>]+class="result-snippet"[^>]*>(.*?)<\/p>/is', $html, $matches, PREG_SET_ORDER);
        
        // If specific classes fail, try a very broad anchor search
        if (count($matches) == 0) {
             preg_match_all('/<h2[^>]*>.*?<a[^>]+href="(http[^"]+)"[^>]*>(.*?)<\/a>.*?<\/h2>.*?<p[^>]*>(.*?)<\/p>/is', $html, $matches, PREG_SET_ORDER);
        }

        foreach ($matches as $m) {
            $link = $m[1];
            $title = strip_tags($m[2]);
            $snippet = strip_tags($m[3]);
            
            if (strlen($title) < 5 || strpos($link, 'ecosia.org') !== false) continue;

            echo "<div class='g-result'>
                    <div class='g-result-url'>".htmlspecialchars(parse_url($link, PHP_URL_HOST))."</div>
                    <a class='g-result-title' href='".htmlspecialchars($link)."' target='_blank'>".htmlspecialchars($title)."</a>
                    <div class='g-result-snippet'>".htmlspecialchars($snippet)."</div>
                  </div>";
            $count++;
            if ($count >= 12) break;
        }

        if ($count == 0) {
            // Last resort: Just find any links with titles
            preg_match_all('/<a[^>]+href="(http[^"]+)"[^>]*>([^<]{15,80})<\/a>/i', $html, $fallback, PREG_SET_ORDER);
            foreach ($fallback as $f) {
                if ($count >= 15) break;
                if (strpos($f[1], 'ecosia') !== false) continue;
                echo "<div class='g-result'>
                        <div class='g-result-url'>".htmlspecialchars($f[1])."</div>
                        <a class='g-result-title' href='".htmlspecialchars($f[1])."' target='_blank'>".htmlspecialchars(trim($f[2]))."</a>
                      </div>";
                $count++;
            }
        }
    }

    echo "</div>
          <div class='g-side'>
             <div class='g-k-card'>
                <div class='g-k-title'>".htmlspecialchars($query)." <span class='g-badge'>ACTUAL_NODE</span></div>
                <p style='font-size:0.85rem; color:#888;'>Knowledge extraction derived from neural fusion.</p>
                <div style='border-top:1px solid #3c4043; padding-top:15px; font-size:0.9rem;'>
                    ข้อมูลเกี่ยวกับ ".htmlspecialchars($query)." กำลังถูกดึงมาจากแหล่งข้อมูลภายนอกและประมวลผลผ่านเซิร์ฟเวอร์ Ubuntu ของคุณ...
                </div>
                <button class='g-btn' style='background:#4285F4; color:#fff; border:none; padding:8px 15px; border-radius:5px; margin-top:15px; width:100%; cursor:pointer;' onclick='window.parent.open(\"https://www.google.com/search?q=".urlencode($query)."\", \"_blank\")'>ดูบน GOOGLE.COM</button>
             </div>
          </div>
    </div>";

    echo "</body></html>";
    exit;
}
?>
<div class="win-head" onmousedown="wDrag(event,'win-google')">
    <div class="win-title">
        <i class="fab fa-google" style="margin-right: 8px; color: #4285F4;"></i>
        Google Search v17
    </div>
    <div class="win-ctrls">
        <div class="dot dot-red" onclick="closeW('google')"></div>
    </div>
</div>
<div class="win-body" style="padding: 0; display: flex; flex-direction: column; background: #202124;">
    <div style="padding: 15px 30px; display: flex; gap: 15px; align-items: center; background: #202124; border-bottom: 1px solid #3c4043;">
        <div style="flex: 1; position: relative;">
            <input type="text" id="google-search-input" 
                style="width: 100%; padding: 12px 25px; border-radius: 30px; border: 1px solid #5f6368; outline: none; font-size: 1rem; background: #303134; color: #fff;"
                placeholder="ค้นหาบน Google..."
                onkeypress="if(event.key === 'Enter') performInternalSearch()"
            >
            <i class="fas fa-search" style="position: absolute; right: 20px; top: 15px; color: #9aa0a6; cursor:pointer;" onclick="performInternalSearch()"></i>
        </div>
        <button class="btn active" style="background:#4285F4; border-radius:20px; padding:10px 20px;" onclick="performInternalSearch()">SEARCH</button>
    </div>

    <div style="flex: 1; position: relative; background: #202124; overflow: hidden;">
        <div id="google-loading" style="position: absolute; inset: 0; background: #202124; z-index: 5; display: none; flex-direction: column; align-items: center; justify-content: center;">
            <div class="spinner" style="width: 40px; height: 40px; border-top-color: #4285F4;"></div>
        </div>
        
        <div id="search-welcome" style="position: absolute; inset: 0; background: #202124; z-index: 4; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; text-align: center;">
            <img src="https://www.google.com/images/branding/googlelogo/2x/googlelogo_light_color_272x92dp.png" height="50" alt="Google" style="margin-bottom: 30px;">
            <p style="color: #9aa0a6; font-size: 0.9rem;">ยินดีต้อนรับสู่ระบบค้นหาข้อมูลอัจฉริยะ MankyOS</p>
        </div>

        <iframe 
            src="about:blank" 
            id="google-frame"
            name="google-frame"
            style="width: 100%; height: 100%; border: none;"
            onload="document.getElementById('google-loading').style.display='none'; document.getElementById('drag-overlay').style.display='none';"
        ></iframe>
    </div>
</div>

<script>
function performInternalSearch() {
    const query = document.getElementById('google-search-input').value;
    if(!query) return;
    
    document.getElementById('search-welcome').style.display = 'none';
    document.getElementById('google-loading').style.display = 'flex';
    document.getElementById('google-frame').src = 'App_store/google.php?q=' + encodeURIComponent(query);
}
</script>

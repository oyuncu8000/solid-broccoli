<?php
// ─── Playlist Listesi ──────────────────────────────
$PLAYLISTS = [
    [
        'id'    => 'PLTLXNxXgTfEz5rZnXpx9uPx8LbENHN3_A',
        'name'  => 'Playlist 1',
        'icon'  => '🎬',
    ],
    [
        'id'    => 'PLmno5MLTdw2fx7_wsOWhazQIh2HWwgPVM',
        'name'  => 'Playlist 2',
        'icon'  => '🎬',
    ],
    // Buraya yeni playlist ekleyebilirsiniz:
    // ['id' => 'PL_PLAYLIST_ID_2', 'name' => 'Playlist 2', 'icon' => '📺'],
    // ['id' => 'PL_PLAYLIST_ID_3', 'name' => 'Dizi Arşivi', 'icon' => '🎞️'],
];

// ─── Aktif Playlist Seçimi ─────────────────────────
$playlistParam = isset($_GET['pl']) ? intval($_GET['pl']) : 0;
if ($playlistParam < 0 || $playlistParam >= count($PLAYLISTS)) $playlistParam = 0;

// ─── YouTube API Ayarları ──────────────────────────
$API_KEY      = 'AIzaSyCLBWLHTxyPo4VHEHo6E7JhR7AZKopWUfc';
$PLAYLIST_ID  = $PLAYLISTS[$playlistParam]['id'];

// ─── Tüm Playlist Videolarını Çeken Fonksiyon ───────
function fetchPlaylistVideos($playlistId, $apiKey) {
    $videos    = [];
    $pageToken = '';

    do {
        $url = 'https://www.googleapis.com/youtube/v3/playlistItems'
             . '?part=snippet'
             . '&maxResults=50'
             . '&playlistId=' . urlencode($playlistId)
             . '&key='        . urlencode($apiKey)
             . ($pageToken ? '&pageToken=' . urlencode($pageToken) : '');

        $ctx = stream_context_create(['http' => ['timeout' => 15]]);
        $raw = @file_get_contents($url, false, $ctx);

        if ($raw === false) break;

        $data = json_decode($raw, true);
        if (empty($data['items'])) break;

        foreach ($data['items'] as $item) {
            $snippet = $item['snippet'];
            if (($snippet['title'] ?? '') === 'Deleted video'
             || ($snippet['title'] ?? '') === 'Private video') continue;

            $videoId = $snippet['resourceId']['videoId'] ?? '';
            if (!$videoId) continue;

            $videos[] = [
                'id'    => $videoId,
                'title' => $snippet['title'] ?? 'Video',
                'pos'   => $snippet['position'] ?? 0
            ];
        }

        $pageToken = $data['nextPageToken'] ?? '';

    } while ($pageToken);

    usort($videos, function($a, $b) {
        return $a['pos'] <=> $b['pos'];
    });

    foreach ($videos as $key => &$v) {
        $v['ep'] = 'Bölüm ' . ($key + 1);
    }

    return $videos;
}

// ─── Canlı Veri Çekimi ─────────────────────────────
$videos = fetchPlaylistVideos($PLAYLIST_ID, $API_KEY);

if (empty($videos)) {
    $videos = [
        ['id' => 'dQw4w9WgXcQ', 'title' => 'Playlist yüklenemedi', 'ep' => 'Bölüm 1'],
    ];
}

$index = isset($_GET["v"]) ? intval($_GET["v"]) : 0;
if ($index < 0 || $index >= count($videos)) $index = 0;

$video   = $videos[$index];
$videoId = $video["id"];
$total   = count($videos);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>StreamBox – <?= htmlspecialchars($video["title"]) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
/* ── RESET ──────────────────────────────────────── */
*{margin:0;padding:0;box-sizing:border-box}
html,body{width:100%;height:100%;background:#080a0f;overflow:hidden;font-family:'Syne',sans-serif;color:#fff}

/* ── STAGE ──────────────────────────────────────── */
.stage{position:fixed;inset:0;display:flex;flex-direction:column}

/* ── IFRAME ─────────────────────────────────────── */
#yt-player{
    position:absolute;
    top:-60px; left:-10px;
    width:calc(100% + 20px);
    height:calc(100% + 120px);
    pointer-events:none;
    z-index:0;
}

/* ── GRADIENTS ──────────────────────────────────── */
.grad{position:absolute;left:0;right:0;z-index:2;pointer-events:none}
.grad-top{top:0;height:140px;background:linear-gradient(to bottom,rgba(8,10,15,.95),transparent)}
.grad-bot{bottom:0;height:220px;background:linear-gradient(to top,rgba(8,10,15,.98) 30%,transparent)}

/* ── UI WRAPPER ─────────────────────────────────── */
.ui{
    position:absolute;inset:0;z-index:10;
    display:flex;flex-direction:column;
    justify-content:space-between;
    padding:24px 28px 28px;
    transition:opacity .35s ease;
}
.ui.hide{opacity:0;pointer-events:none}

/* ── TOP BAR ────────────────────────────────────── */
.top{display:flex;justify-content:space-between;align-items:center}
.logo{
    font-family:'DM Mono',monospace;font-size:13px;letter-spacing:4px;
    color:rgba(255,255,255,.55);text-transform:uppercase;
}
.episode-badge{
    font-size:11px;letter-spacing:2px;color:rgba(255,255,255,.45);
    font-family:'DM Mono',monospace;
    background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.12);
    padding:4px 10px;border-radius:20px;
}

/* ── CENTER PLAY ─────────────────────────────────── */
.center{
    position:absolute;inset:0;display:flex;
    align-items:center;justify-content:center;pointer-events:none;
}
.center-ring{
    width:76px;height:76px;border-radius:50%;
    background:rgba(255,255,255,.08);
    border:1.5px solid rgba(255,255,255,.22);
    display:flex;align-items:center;justify-content:center;
    cursor:pointer;pointer-events:all;
    transition:transform .18s,background .18s,opacity .3s;
    opacity:1;
}
.center-ring.playing{opacity:0;pointer-events:none}
.center-ring:hover{transform:scale(1.1);background:rgba(255,255,255,.15)}
.center-ring:active{transform:scale(.93)}
.center-ring svg{margin-left:4px}

/* ── BOTTOM ──────────────────────────────────────── */
.bottom{display:flex;flex-direction:column;gap:14px}

/* title */
.meta-title{font-size:15px;font-weight:600;opacity:.85;letter-spacing:.3px;
    overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:80%}

/* progress */
.progress-track{
    height:4px;background:rgba(255,255,255,.12);
    border-radius:4px;cursor:pointer;position:relative;
    transition:height .2s;
}
.progress-track:hover{height:6px}
.progress-fill{
    height:100%;border-radius:4px;width:0%;
    background:linear-gradient(to right,#4f8ef7,#a78bfa);
    transition:width .25s linear;pointer-events:none;
    position:relative;
}
.progress-fill::after{
    content:'';position:absolute;right:-5px;top:50%;
    transform:translateY(-50%);
    width:10px;height:10px;border-radius:50%;
    background:#fff;box-shadow:0 0 6px #4f8ef7;
    opacity:0;transition:opacity .2s;
}
.progress-track:hover .progress-fill::after{opacity:1}

/* controls row */
.controls{display:flex;align-items:center;justify-content:space-between}
.ctrl-left{display:flex;align-items:center;gap:10px}
.ctrl-right{display:flex;align-items:center;gap:10px}

/* icon buttons */
.ibtn{
    background:rgba(255,255,255,.07);
    border:1px solid rgba(255,255,255,.14);
    color:#fff;width:36px;height:36px;
    border-radius:10px;cursor:pointer;
    display:flex;align-items:center;justify-content:center;
    transition:background .18s,transform .15s;flex-shrink:0;
}
.ibtn:hover{background:rgba(255,255,255,.16);transform:scale(1.05)}
.ibtn:active{transform:scale(.92)}
.ibtn svg{flex-shrink:0}

/* play/pause text-icon */
#playBtn{width:auto;padding:0 14px;gap:6px;font-family:'DM Mono',monospace;font-size:12px;letter-spacing:1px}

/* time */
.time{font-family:'DM Mono',monospace;font-size:12px;opacity:.5;letter-spacing:.5px;white-space:nowrap}

/* volume */
.vol-wrap{display:flex;align-items:center;gap:8px}
.vol-slider{
    -webkit-appearance:none;appearance:none;
    width:70px;height:3px;border-radius:3px;
    background:rgba(255,255,255,.2);outline:none;cursor:pointer;
}
.vol-slider::-webkit-slider-thumb{
    -webkit-appearance:none;width:12px;height:12px;
    border-radius:50%;background:#fff;cursor:pointer;
}

/* ── EP NAV DOTS ─────────────────────────────────── */
.ep-dots{display:flex;gap:6px;align-items:center;flex-wrap:wrap;max-width:200px}
.ep-dot{
    width:6px;height:6px;border-radius:50%;
    background:rgba(255,255,255,.2);cursor:pointer;
    transition:background .2s,transform .2s;border:none;padding:0;
}
.ep-dot.active{background:#4f8ef7;transform:scale(1.4)}
.ep-dot:hover:not(.active){background:rgba(255,255,255,.5)}

/* ── BUFFERING SPINNER ───────────────────────────── */
.spinner{
    position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
    z-index:6;pointer-events:none;opacity:0;transition:opacity .3s;
}
.spinner.show{opacity:1}
.spin-ring{
    width:44px;height:44px;border-radius:50%;
    border:3px solid rgba(255,255,255,.1);
    border-top-color:#4f8ef7;
    animation:spin .8s linear infinite;
}
@keyframes spin{to{transform:rotate(360deg)}}
@keyframes pulse{0%,100%{opacity:.4}50%{opacity:.9}}

/* ── PLAYLIST MENU BUTTON ────────────────────────── */
.pl-btn{
    background:rgba(255,255,255,.07);
    border:1px solid rgba(255,255,255,.14);
    color:rgba(255,255,255,.7);
    height:36px;padding:0 12px;
    border-radius:10px;cursor:pointer;
    display:flex;align-items:center;gap:7px;
    font-family:'DM Mono',monospace;font-size:11px;letter-spacing:1.5px;
    transition:background .18s,transform .15s,color .18s;
    white-space:nowrap;flex-shrink:0;
}
.pl-btn:hover{background:rgba(255,255,255,.14);color:#fff;transform:scale(1.04)}
.pl-btn:active{transform:scale(.93)}
.pl-btn svg{flex-shrink:0;opacity:.7}

/* ── PLAYLIST OVERLAY ────────────────────────────── */
.pl-overlay{
    position:fixed;inset:0;z-index:100;
    display:flex;align-items:center;justify-content:center;
    background:rgba(8,10,15,.7);
    backdrop-filter:blur(14px);
    -webkit-backdrop-filter:blur(14px);
    opacity:0;pointer-events:none;
    transition:opacity .28s ease;
}
.pl-overlay.open{opacity:1;pointer-events:all}

.pl-panel{
    background:rgba(18,20,28,.92);
    border:1px solid rgba(255,255,255,.10);
    border-radius:20px;
    padding:32px 28px 28px;
    min-width:300px;max-width:420px;width:90%;
    box-shadow:0 32px 80px rgba(0,0,0,.7);
    transform:translateY(18px) scale(.97);
    transition:transform .28s cubic-bezier(.22,1,.36,1);
}
.pl-overlay.open .pl-panel{transform:translateY(0) scale(1)}

.pl-header{
    display:flex;justify-content:space-between;align-items:center;
    margin-bottom:22px;
}
.pl-header-title{
    font-family:'DM Mono',monospace;
    font-size:11px;letter-spacing:3px;
    color:rgba(255,255,255,.4);text-transform:uppercase;
}
.pl-close{
    background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);
    color:rgba(255,255,255,.5);width:30px;height:30px;border-radius:8px;
    cursor:pointer;display:flex;align-items:center;justify-content:center;
    transition:background .15s,color .15s;
}
.pl-close:hover{background:rgba(255,255,255,.15);color:#fff}

.pl-list{display:flex;flex-direction:column;gap:8px}

.pl-item{
    display:flex;align-items:center;gap:14px;
    padding:13px 16px;border-radius:12px;
    background:rgba(255,255,255,.04);
    border:1px solid rgba(255,255,255,.08);
    cursor:pointer;text-decoration:none;color:#fff;
    transition:background .18s,border-color .18s,transform .15s;
    position:relative;overflow:hidden;
}
.pl-item:hover{
    background:rgba(79,142,247,.12);
    border-color:rgba(79,142,247,.35);
    transform:translateX(4px);
}
.pl-item:active{transform:translateX(2px) scale(.98)}
.pl-item.current{
    background:rgba(79,142,247,.15);
    border-color:rgba(79,142,247,.5);
}
.pl-item.current::before{
    content:'';position:absolute;left:0;top:0;bottom:0;
    width:3px;background:linear-gradient(to bottom,#4f8ef7,#a78bfa);
    border-radius:3px 0 0 3px;
}
.pl-icon{font-size:20px;flex-shrink:0;line-height:1}
.pl-info{display:flex;flex-direction:column;gap:2px;flex:1;min-width:0}
.pl-name{font-size:14px;font-weight:600;letter-spacing:.2px;
    overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
.pl-sub{font-family:'DM Mono',monospace;font-size:10px;
    color:rgba(255,255,255,.35);letter-spacing:1px}
.pl-arrow{opacity:.3;flex-shrink:0;transition:opacity .15s,transform .15s}
.pl-item:hover .pl-arrow{opacity:.7;transform:translateX(3px)}
.pl-item.current .pl-arrow{opacity:0}
.pl-item.current .pl-now{
    display:flex;align-items:center;
    font-family:'DM Mono',monospace;font-size:9px;
    color:#4f8ef7;letter-spacing:1.5px;gap:4px;
}
.pl-now{display:none}
.pl-now-dot{
    width:5px;height:5px;border-radius:50%;
    background:#4f8ef7;
    animation:pulse 1.6s ease-in-out infinite;
}
</style>
</head>
<body>

<div class="stage" id="stage">
    <div id="yt-player"></div>

    <div class="grad grad-top"></div>
    <div class="grad grad-bot"></div>

    <!-- Buffering -->
    <div class="spinner" id="spinner"><div class="spin-ring"></div></div>

    <!-- UI -->
    <div class="ui" id="ui">

        <!-- TOP -->
        <div class="top">
            <div class="logo">StreamBox</div>
            <div style="display:flex;align-items:center;gap:10px">
                <!-- ── PLAYLIST MENU BUTTON ── -->
                <button class="pl-btn" id="plMenuBtn" title="Playlist seç">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/>
                        <circle cx="3" cy="6" r="1.5" fill="currentColor" stroke="none"/>
                        <circle cx="3" cy="12" r="1.5" fill="currentColor" stroke="none"/>
                        <circle cx="3" cy="18" r="1.5" fill="currentColor" stroke="none"/>
                    </svg>
                    PLAYLIST
                </button>
                <div class="episode-badge" id="epBadge"><?= htmlspecialchars($video["ep"]) ?></div>
            </div>
        </div>

        <!-- CENTER play ring -->
        <div class="center">
            <div class="center-ring" id="centerRing">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="#fff">
                    <polygon points="6,3 20,12 6,21"/>
                </svg>
            </div>
        </div>

        <!-- BOTTOM -->
        <div class="bottom">
            <div class="meta-title" id="metaTitle"><?= htmlspecialchars($video["title"]) ?></div>

            <!-- Progress bar -->
            <div class="progress-track" id="progressTrack">
                <div class="progress-fill" id="progressFill"></div>
            </div>

            <!-- Controls -->
            <div class="controls">
                <div class="ctrl-left">
                    <button class="ibtn" id="playBtn">
                        <svg id="playIcon" width="14" height="14" viewBox="0 0 24 24" fill="#fff"><polygon points="6,3 20,12 6,21"/></svg>
                        <svg id="pauseIcon" width="14" height="14" viewBox="0 0 24 24" fill="#fff" style="display:none"><rect x="5" y="3" width="4" height="18"/><rect x="15" y="3" width="4" height="18"/></svg>
                        <span id="playLabel">OYNAT</span>
                    </button>

                    <button class="ibtn" id="prevBtn" title="Önceki bölüm" <?= $index===0?'style="opacity:.35;cursor:default"':'' ?>>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2">
                            <polygon points="19,20 9,12 19,4"/><line x1="5" y1="19" x2="5" y2="5"/>
                        </svg>
                    </button>

                    <button class="ibtn" id="nextBtn" title="Sonraki bölüm" <?= $index===$total-1?'style="opacity:.35;cursor:default"':'' ?>>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2">
                            <polygon points="5,4 15,12 5,20"/><line x1="19" y1="5" x2="19" y2="19"/>
                        </svg>
                    </button>

                    <span class="time" id="timeDisplay">0:00 / 0:00</span>
                </div>

                <div class="ctrl-right">
                    <div class="vol-wrap">
                        <button class="ibtn" id="muteBtn" title="Ses kapat/aç">
                            <svg id="volIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2">
                                <polygon points="11,5 6,9 2,9 2,15 6,15 11,19"/><path d="M19.07,4.93a10,10,0,0,1,0,14.14"/><path d="M15.54,8.46a5,5,0,0,1,0,7.07"/>
                            </svg>
                        </button>
                        <input type="range" class="vol-slider" id="volSlider" min="0" max="100" value="100">
                    </div>

                    <!-- Episode dots -->
                    <div class="ep-dots" id="epDots">
                        <?php foreach($videos as $i => $v): ?>
                        <button class="ep-dot <?= $i===$index?'active':'' ?>"
                                data-index="<?= $i ?>"
                                title="<?= htmlspecialchars($v['ep']) ?>"></button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Fullscreen -->
                    <button class="ibtn" id="fsBtn" title="Tam ekran">
                        <svg id="fsIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2">
                            <polyline points="15,3 21,3 21,9"/><polyline points="9,21 3,21 3,15"/>
                            <line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/>
                        </svg>
                        <svg id="fsExitIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" style="display:none">
                            <polyline points="4,14 10,14 10,20"/><polyline points="20,10 14,10 14,4"/>
                            <line x1="10" y1="14" x2="3" y2="21"/><line x1="21" y1="3" x2="14" y2="10"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

    </div><!-- /.ui -->
</div><!-- /.stage -->

<!-- ── PLAYLIST MENU OVERLAY ───────────────────────── -->
<div class="pl-overlay" id="plOverlay">
    <div class="pl-panel">
        <div class="pl-header">
            <span class="pl-header-title">Playlist Seç</span>
            <button class="pl-close" id="plClose">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="pl-list">
            <?php foreach($PLAYLISTS as $pi => $pl): ?>
            <a class="pl-item <?= $pi === $playlistParam ? 'current' : '' ?>"
               href="?pl=<?= $pi ?>&v=0">
                <span class="pl-icon"><?= htmlspecialchars($pl['icon']) ?></span>
                <div class="pl-info">
                    <span class="pl-name"><?= htmlspecialchars($pl['name']) ?></span>
                    <span class="pl-sub">PLAYLIST <?= $pi + 1 ?></span>
                </div>
                <?php if($pi === $playlistParam): ?>
                <span class="pl-now">
                    <span class="pl-now-dot"></span>
                    OYNATILIYOR
                </span>
                <?php else: ?>
                <svg class="pl-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2">
                    <polyline points="9,18 15,12 9,6"/>
                </svg>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="https://www.youtube.com/iframe_api"></script>
<script>
// ─── Config ───────────────────────────────────────
const VIDEOS = <?= json_encode(array_values($videos)) ?>;
let currentIndex = <?= $index ?>;
let player, isPlaying = false, isMuted = false, ticker;

// ─── YT API Ready ─────────────────────────────────
window.onYouTubeIframeAPIReady = function(){
    createPlayer(VIDEOS[currentIndex].id);
};

function createPlayer(videoId){
    if(player){ player.destroy(); }
    player = new YT.Player('yt-player',{
        videoId: videoId,
        playerVars:{
            controls: 0,
            rel: 0,
            modestbranding: 1,
            playsinline: 1,
            disablekb: 1,
            iv_load_policy: 3,
            fs: 0,
            enablejsapi: 1,
        },
        events:{
            onReady: onPlayerReady,
            onStateChange: onStateChange,
        }
    });
}

function onPlayerReady(e){
    const key = 'sb_pos_' + VIDEOS[currentIndex].id;
    const saved = parseFloat(localStorage.getItem(key));
    if(saved > 0) player.seekTo(saved, true);
    clearInterval(ticker);
    ticker = setInterval(tick, 500);
}

function onStateChange(e){
    const S = YT.PlayerState;
    if(e.data === S.PLAYING){
        setPlaying(true);
        showSpinner(false);
    } else if(e.data === S.PAUSED || e.data === S.ENDED){
        setPlaying(false);
    } else if(e.data === S.BUFFERING){
        showSpinner(true);
    }
    if(e.data === S.ENDED){
        const next = currentIndex + 1;
        if(next < VIDEOS.length) goTo(next);
    }
}

// ─── Tick ─────────────────────────────────────────
function tick(){
    if(!player || typeof player.getCurrentTime !== 'function') return;
    const cur = player.getCurrentTime();
    const dur = player.getDuration() || 1;
    const pct = (cur / dur * 100).toFixed(2);

    document.getElementById('progressFill').style.width = pct + '%';
    document.getElementById('timeDisplay').textContent = fmt(cur) + ' / ' + fmt(dur);

    localStorage.setItem('sb_pos_' + VIDEOS[currentIndex].id, cur);
}

// ─── Helpers ──────────────────────────────────────
function fmt(s){
    s = Math.floor(s);
    const m = Math.floor(s/60), sec = s%60;
    return m + ':' + (sec < 10 ? '0' : '') + sec;
}

function setPlaying(val){
    isPlaying = val;
    document.getElementById('playIcon').style.display    = val ? 'none'  : '';
    document.getElementById('pauseIcon').style.display   = val ? ''     : 'none';
    document.getElementById('playLabel').textContent      = val ? 'DURAKLAT' : 'OYNAT';
    document.getElementById('centerRing').classList.toggle('playing', val);
    showSpinner(false);
}

function showSpinner(v){
    document.getElementById('spinner').classList.toggle('show', v);
}

function toggle(){
    if(!player) return;
    if(isPlaying) player.pauseVideo();
    else          player.playVideo();
}

// ─── Episode navigation ───────────────────────────
function goTo(idx){
    if(idx < 0 || idx >= VIDEOS.length) return;
    currentIndex = idx;
    const v = VIDEOS[idx];
    document.getElementById('metaTitle').textContent = v.title;
    document.getElementById('epBadge').textContent   = v.ep;
    document.getElementById('progressFill').style.width = '0%';
    document.getElementById('timeDisplay').textContent  = '0:00 / 0:00';

    document.querySelectorAll('.ep-dot').forEach((d,i) =>
        d.classList.toggle('active', i === idx)
    );

    document.getElementById('prevBtn').style.opacity = idx === 0 ? '.35' : '1';
    document.getElementById('nextBtn').style.opacity = idx === VIDEOS.length-1 ? '.35' : '1';

    setPlaying(false);
    if(player){
        player.loadVideoById(v.id);
        setTimeout(()=>{
            const saved = parseFloat(localStorage.getItem('sb_pos_' + v.id));
            if(saved > 0) player.seekTo(saved, true);
        }, 800);
    }
}

// ─── Controls ─────────────────────────────────────
document.getElementById('playBtn').addEventListener('click', toggle);
document.getElementById('centerRing').addEventListener('click', toggle);

document.getElementById('prevBtn').addEventListener('click', ()=> goTo(currentIndex - 1));
document.getElementById('nextBtn').addEventListener('click', ()=> goTo(currentIndex + 1));

document.querySelectorAll('.ep-dot').forEach(btn => {
    btn.addEventListener('click', ()=> goTo(parseInt(btn.dataset.index)));
});

document.getElementById('progressTrack').addEventListener('click', function(e){
    if(!player) return;
    const r = this.getBoundingClientRect();
    const pct = (e.clientX - r.left) / r.width;
    player.seekTo(player.getDuration() * pct, true);
});

document.getElementById('volSlider').addEventListener('input', function(){
    if(!player) return;
    const v = parseInt(this.value);
    player.setVolume(v);
    if(v === 0){ player.mute(); isMuted = true; }
    else        { player.unMute(); isMuted = false; }
});

document.getElementById('muteBtn').addEventListener('click', function(){
    if(!player) return;
    isMuted = !isMuted;
    if(isMuted){ player.mute(); document.getElementById('volSlider').value = 0; }
    else        { player.unMute(); document.getElementById('volSlider').value = 100; player.setVolume(100); }
});

// ─── Fullscreen ───────────────────────────────────
const stage = document.getElementById('stage');
document.getElementById('fsBtn').addEventListener('click', function(){
    if(!document.fullscreenElement){
        stage.requestFullscreen && stage.requestFullscreen();
    } else {
        document.exitFullscreen && document.exitFullscreen();
    }
});

document.addEventListener('fullscreenchange', function(){
    const fs = !!document.fullscreenElement;
    document.getElementById('fsIcon').style.display     = fs ? 'none' : '';
    document.getElementById('fsExitIcon').style.display = fs ? '' : 'none';
});

// ─── UI auto-hide ─────────────────────────────────
let hideTimer;
const ui = document.getElementById('ui');

function showUI(){
    ui.classList.remove('hide');
    clearTimeout(hideTimer);
    hideTimer = setTimeout(()=>{ if(isPlaying) ui.classList.add('hide'); }, 3000);
}

stage.addEventListener('mousemove', showUI);
stage.addEventListener('touchstart', ()=>{ ui.classList.contains('hide') ? showUI() : null; });

// ─── Playlist Menu ─────────────────────────────────
const plOverlay = document.getElementById('plOverlay');

function openPlaylistMenu(){
    plOverlay.classList.add('open');
    // Menü açıkken UI gizlenmesini engelle
    clearTimeout(hideTimer);
    ui.classList.remove('hide');
}

function closePlaylistMenu(){
    plOverlay.classList.remove('open');
}

document.getElementById('plMenuBtn').addEventListener('click', function(e){
    e.stopPropagation();
    openPlaylistMenu();
});

document.getElementById('plClose').addEventListener('click', closePlaylistMenu);

// Overlay arka planına tıklayınca kapat
plOverlay.addEventListener('click', function(e){
    if(e.target === plOverlay) closePlaylistMenu();
});

// ESC tuşuyla kapat
document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closePlaylistMenu();
});
</script>
</body>
</html>
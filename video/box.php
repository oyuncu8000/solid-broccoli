<?php
// ─── Playlist Listesi ──────────────────────────────
$PLAYLISTS = [
    [
        'id'    => 'PLSg9uHxhv5-5z0dk2Wq4Z9P2dcsrBZGvM',
        'name'  => 'Playlist 1',
        'icon'  => '🎬',
    ],
    [
        'id'    => 'PLmno5MLTdw2dgR9NhE9RD7SSfh5nC205s',
        'name'  => 'Playlist 2',
        'icon'  => '🎬',
    ],
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
    usort($videos, function($a, $b) { return $a['pos'] <=> $b['pos']; });
    foreach ($videos as $key => &$v) {
        $v['ep'] = 'Bolum ' . ($key + 1);
    }
    return $videos;
}

$videos = fetchPlaylistVideos($PLAYLIST_ID, $API_KEY);
if (empty($videos)) {
    $videos = [['id' => 'dQw4w9WgXcQ', 'title' => 'Playlist yuklenemedi', 'ep' => 'Bolum 1']];
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
<title>StreamBox - <?= htmlspecialchars($video["title"]) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
html,body{width:100%;height:100%;background:#080a0f;overflow:hidden;font-family:'Syne',sans-serif;color:#fff}
.stage{position:fixed;inset:0;display:flex;flex-direction:column}
#yt-player{position:absolute;top:-60px;left:-10px;width:calc(100% + 20px);height:calc(100% + 120px);pointer-events:none;z-index:0;}
.grad{position:absolute;left:0;right:0;z-index:2;pointer-events:none}
.grad-top{top:0;height:140px;background:linear-gradient(to bottom,rgba(8,10,15,.95),transparent)}
.grad-bot{bottom:0;height:220px;background:linear-gradient(to top,rgba(8,10,15,.98) 30%,transparent)}
.ui{position:absolute;inset:0;z-index:10;display:flex;flex-direction:column;justify-content:space-between;padding:24px 28px 28px;transition:opacity .35s ease;}
.ui.hide{opacity:0;pointer-events:none}
.top{display:flex;justify-content:space-between;align-items:center}
.logo{font-family:'DM Mono',monospace;font-size:13px;letter-spacing:4px;color:rgba(255,255,255,.55);text-transform:uppercase;}
.episode-badge{font-size:11px;letter-spacing:2px;color:rgba(255,255,255,.45);font-family:'DM Mono',monospace;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);padding:4px 10px;border-radius:20px;}
.center{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;}
.center-ring{width:76px;height:76px;border-radius:50%;background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.22);display:flex;align-items:center;justify-content:center;cursor:pointer;pointer-events:all;transition:transform .18s,background .18s,opacity .3s;opacity:1;}
.center-ring.playing{opacity:0;pointer-events:none}
.center-ring:hover{transform:scale(1.1);background:rgba(255,255,255,.15)}
.center-ring:active{transform:scale(.93)}
.center-ring svg{margin-left:4px}
.bottom{display:flex;flex-direction:column;gap:14px}
.meta-title{font-size:15px;font-weight:600;opacity:.85;letter-spacing:.3px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:80%}
.progress-track{height:4px;background:rgba(255,255,255,.12);border-radius:4px;cursor:pointer;position:relative;transition:height .2s;}
.progress-track:hover{height:6px}
.progress-fill{height:100%;border-radius:4px;width:0%;background:linear-gradient(to right,#4f8ef7,#a78bfa);transition:width .25s linear;pointer-events:none;position:relative;}
.progress-fill::after{content:'';position:absolute;right:-5px;top:50%;transform:translateY(-50%);width:10px;height:10px;border-radius:50%;background:#fff;box-shadow:0 0 6px #4f8ef7;opacity:0;transition:opacity .2s;}
.progress-track:hover .progress-fill::after{opacity:1}
.controls{display:flex;align-items:center;justify-content:space-between}
.ctrl-left{display:flex;align-items:center;gap:10px}
.ctrl-right{display:flex;align-items:center;gap:10px}
.ibtn{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.14);color:#fff;width:36px;height:36px;border-radius:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .18s,transform .15s;flex-shrink:0;}
.ibtn:hover{background:rgba(255,255,255,.16);transform:scale(1.05)}
.ibtn:active{transform:scale(.92)}
.ibtn svg{flex-shrink:0}
#playBtn{width:auto;padding:0 14px;gap:6px;font-family:'DM Mono',monospace;font-size:12px;letter-spacing:1px}
.time{font-family:'DM Mono',monospace;font-size:12px;opacity:.5;letter-spacing:.5px;white-space:nowrap}
.vol-wrap{display:flex;align-items:center;gap:8px}
.vol-slider{-webkit-appearance:none;appearance:none;width:70px;height:3px;border-radius:3px;background:rgba(255,255,255,.2);outline:none;cursor:pointer;}
.vol-slider::-webkit-slider-thumb{-webkit-appearance:none;width:12px;height:12px;border-radius:50%;background:#fff;cursor:pointer;}
.ep-dots{display:flex;gap:6px;align-items:center;flex-wrap:wrap;max-width:200px}
.ep-dot{width:6px;height:6px;border-radius:50%;background:rgba(255,255,255,.2);cursor:pointer;transition:background .2s,transform .2s;border:none;padding:0;}
.ep-dot.active{background:#4f8ef7;transform:scale(1.4)}
.ep-dot:hover:not(.active){background:rgba(255,255,255,.5)}
.spinner{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;z-index:6;pointer-events:none;opacity:0;transition:opacity .3s;}
.spinner.show{opacity:1}
.spin-ring{width:44px;height:44px;border-radius:50%;border:3px solid rgba(255,255,255,.1);border-top-color:#4f8ef7;animation:spin .8s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}
@keyframes pulse{0%,100%{opacity:.4}50%{opacity:.9}}

/* ── PLAYLIST BUTTON ─────────────────────────────── */
.pl-btn{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.14);color:rgba(255,255,255,.7);height:36px;padding:0 12px;border-radius:10px;cursor:pointer;display:flex;align-items:center;gap:7px;font-family:'DM Mono',monospace;font-size:11px;letter-spacing:1.5px;transition:background .18s,transform .15s,color .18s;white-space:nowrap;flex-shrink:0;}
.pl-btn:hover{background:rgba(255,255,255,.14);color:#fff;transform:scale(1.04)}
.pl-btn:active{transform:scale(.93)}
.pl-btn svg{flex-shrink:0;opacity:.7}

/* ── ORTAK OVERLAY ───────────────────────────────── */
.overlay{position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;background:rgba(8,10,15,.7);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);opacity:0;pointer-events:none;transition:opacity .28s ease;}
.overlay.open{opacity:1;pointer-events:all}
.panel{background:rgba(18,20,28,.92);border:1px solid rgba(255,255,255,.10);border-radius:20px;padding:28px 24px 24px;min-width:300px;max-width:400px;width:90%;box-shadow:0 32px 80px rgba(0,0,0,.7);transform:translateY(18px) scale(.97);transition:transform .28s cubic-bezier(.22,1,.36,1);}
.overlay.open .panel{transform:translateY(0) scale(1)}
.panel-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;}
.panel-title{font-family:'DM Mono',monospace;font-size:11px;letter-spacing:3px;color:rgba(255,255,255,.4);text-transform:uppercase;}
.panel-close{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:rgba(255,255,255,.5);width:30px;height:30px;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s,color .15s;}
.panel-close:hover{background:rgba(255,255,255,.15);color:#fff}
.item-list{display:flex;flex-direction:column;gap:7px}
.list-item{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:11px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);cursor:pointer;color:#fff;transition:background .18s,border-color .18s,transform .15s;position:relative;text-decoration:none;}
.list-item:hover{background:rgba(79,142,247,.12);border-color:rgba(79,142,247,.35);transform:translateX(3px);}
.list-item.active{background:rgba(79,142,247,.15);border-color:rgba(79,142,247,.5);}
.list-item.active::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:linear-gradient(to bottom,#4f8ef7,#a78bfa);border-radius:3px 0 0 3px;}
.item-icon{font-size:18px;flex-shrink:0;line-height:1}
.item-info{display:flex;flex-direction:column;gap:2px;flex:1;min-width:0}
.item-name{font-size:13px;font-weight:600;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;}
.item-sub{font-family:'DM Mono',monospace;font-size:9px;color:rgba(255,255,255,.35);letter-spacing:1px;}
.item-check{width:18px;height:18px;border-radius:50%;background:rgba(79,142,247,.2);border:1.5px solid rgba(79,142,247,.6);display:flex;align-items:center;justify-content:center;flex-shrink:0;opacity:0;transition:opacity .18s;}
.list-item.active .item-check{opacity:1}
.item-arrow{opacity:.3;flex-shrink:0;transition:opacity .15px,transform .15s;}
.list-item:hover .item-arrow{opacity:.7;transform:translateX(3px);}

/* ── YENİ: KÜÇÜK BUTONLAR ────────────────────────── */
.ibtn-sm{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.14);color:rgba(255,255,255,.75);height:36px;padding:0 11px;border-radius:10px;cursor:pointer;display:flex;align-items:center;gap:6px;font-family:'DM Mono',monospace;font-size:10px;letter-spacing:1.2px;transition:background .18s,transform .15s,color .18s;white-space:nowrap;flex-shrink:0;}
.ibtn-sm:hover{background:rgba(255,255,255,.14);color:#fff;transform:scale(1.04)}
.ibtn-sm:active{transform:scale(.93)}
.ibtn-sm svg{flex-shrink:0;opacity:.75}
.ibtn-sm.btn-active{background:rgba(79,142,247,.18);border-color:rgba(79,142,247,.5);color:#4f8ef7;}
.ibtn-sm.btn-active svg{opacity:1}

/* ── CAST ÖZEL ───────────────────────────────────── */
.cast-scanning{display:flex;flex-direction:column;align-items:center;gap:14px;padding:18px 0 8px;}
.cast-scan-ring{width:52px;height:52px;border-radius:50%;border:2px solid rgba(79,142,247,.2);border-top-color:#4f8ef7;animation:spin .9s linear infinite;display:flex;align-items:center;justify-content:center;}
.cast-scan-label{font-family:'DM Mono',monospace;font-size:11px;color:rgba(255,255,255,.4);letter-spacing:2px;animation:pulse 1.6s ease-in-out infinite;}
.cast-status-dot{width:5px;height:5px;border-radius:50%;background:#4f8ef7;animation:pulse 1.6s ease-in-out infinite;display:inline-block;margin-right:4px;vertical-align:middle;}
.cast-connected-label{font-family:'DM Mono',monospace;font-size:9px;color:#4f8ef7;letter-spacing:1.5px;display:flex;align-items:center;justify-content:center;}
.cast-tip{font-family:'DM Mono',monospace;font-size:9px;color:rgba(255,255,255,.25);letter-spacing:1px;text-align:center;margin-top:14px;line-height:1.6;}
.cast-disconnect-btn{margin-top:12px;width:100%;padding:10px;border-radius:10px;background:rgba(255,60,60,.12);border:1px solid rgba(255,60,60,.3);color:rgba(255,120,120,.8);font-family:'DM Mono',monospace;font-size:10px;letter-spacing:1.5px;cursor:pointer;transition:background .18s;}
.cast-disconnect-btn:hover{background:rgba(255,60,60,.22);}

/* ── PLAYLIST OVERLAY OZGUN STIL ─────────────────── */
.pl-item{display:flex;align-items:center;gap:14px;padding:13px 16px;border-radius:12px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);cursor:pointer;text-decoration:none;color:#fff;transition:background .18s,border-color .18s,transform .15s;position:relative;overflow:hidden;}
.pl-item:hover{background:rgba(79,142,247,.12);border-color:rgba(79,142,247,.35);transform:translateX(4px);}
.pl-item.current{background:rgba(79,142,247,.15);border-color:rgba(79,142,247,.5);}
.pl-item.current::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:linear-gradient(to bottom,#4f8ef7,#a78bfa);border-radius:3px 0 0 3px;}
.pl-icon{font-size:20px;flex-shrink:0;line-height:1}
.pl-info{display:flex;flex-direction:column;gap:2px;flex:1;min-width:0}
.pl-name{font-size:14px;font-weight:600;letter-spacing:.2px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;}
.pl-sub{font-family:'DM Mono',monospace;font-size:10px;color:rgba(255,255,255,.35);letter-spacing:1px;}
.pl-now{display:none}
.pl-item.current .pl-now{display:flex;align-items:center;font-family:'DM Mono',monospace;font-size:9px;color:#4f8ef7;letter-spacing:1.5px;gap:4px;}
.pl-now-dot{width:5px;height:5px;border-radius:50%;background:#4f8ef7;animation:pulse 1.6s ease-in-out infinite;}
.pl-arrow{opacity:.3;flex-shrink:0;transition:opacity .15s,transform .15s;}
.pl-item:hover .pl-arrow{opacity:.7;transform:translateX(3px);}
.pl-item.current .pl-arrow{opacity:0}

/* ── TOAST ───────────────────────────────────────── */
.toast{position:fixed;bottom:90px;left:50%;transform:translateX(-50%) translateY(20px);background:rgba(18,20,28,.95);border:1px solid rgba(255,255,255,.12);border-radius:12px;padding:10px 18px;font-family:'DM Mono',monospace;font-size:11px;letter-spacing:1.5px;color:rgba(255,255,255,.8);z-index:200;opacity:0;pointer-events:none;transition:opacity .3s,transform .3s;white-space:nowrap;}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0);}
.empty-msg{text-align:center;padding:20px;font-family:'DM Mono',monospace;font-size:11px;color:rgba(255,255,255,.3);letter-spacing:1.5px;}
</style>
</head>
<body>

<div class="stage" id="stage">
    <div id="yt-player"></div>
    <div class="grad grad-top"></div>
    <div class="grad grad-bot"></div>
    <div class="spinner" id="spinner"><div class="spin-ring"></div></div>

    <div class="ui" id="ui">
        <!-- TOP -->
        <div class="top">
            <div class="logo">StreamBox</div>
            <div style="display:flex;align-items:center;gap:10px">
                <button class="pl-btn" id="plMenuBtn" title="Playlist sec">
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

        <!-- CENTER -->
        <div class="center">
            <div class="center-ring" id="centerRing">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="#fff"><polygon points="6,3 20,12 6,21"/></svg>
            </div>
        </div>

        <!-- BOTTOM -->
        <div class="bottom">
            <div class="meta-title" id="metaTitle"><?= htmlspecialchars($video["title"]) ?></div>
            <div class="progress-track" id="progressTrack">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <div class="controls">
                <div class="ctrl-left">
                    <button class="ibtn" id="playBtn">
                        <svg id="playIcon" width="14" height="14" viewBox="0 0 24 24" fill="#fff"><polygon points="6,3 20,12 6,21"/></svg>
                        <svg id="pauseIcon" width="14" height="14" viewBox="0 0 24 24" fill="#fff" style="display:none"><rect x="5" y="3" width="4" height="18"/><rect x="15" y="3" width="4" height="18"/></svg>
                        <span id="playLabel">OYNAT</span>
                    </button>
                    <button class="ibtn" id="prevBtn" title="Onceki bolum" <?= $index===0?'style="opacity:.35;cursor:default"':'' ?>>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><polygon points="19,20 9,12 19,4"/><line x1="5" y1="19" x2="5" y2="5"/></svg>
                    </button>
                    <button class="ibtn" id="nextBtn" title="Sonraki bolum" <?= $index===$total-1?'style="opacity:.35;cursor:default"':'' ?>>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><polygon points="5,4 15,12 5,20"/><line x1="19" y1="5" x2="19" y2="19"/></svg>
                    </button>
                    <span class="time" id="timeDisplay">0:00 / 0:00</span>
                </div>

                <div class="ctrl-right">
                    <!-- DUB BUTONU -->
                    <button class="ibtn-sm" id="dubBtn" title="Ses dili sec">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                            <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                            <line x1="12" y1="19" x2="12" y2="23"/>
                            <line x1="8" y1="23" x2="16" y2="23"/>
                        </svg>
                        DUB
                    </button>

                    <!-- ALTYAZI BUTONU -->
                    <button class="ibtn-sm" id="subBtn" title="Altyazi sec">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="4" width="20" height="16" rx="2"/>
                            <line x1="6" y1="12" x2="10" y2="12"/>
                            <line x1="13" y1="12" x2="18" y2="12"/>
                            <line x1="6" y1="16" x2="14" y2="16"/>
                        </svg>
                        ALT
                    </button>

                    <!-- CAST BUTONU -->
                    <button class="ibtn-sm" id="castBtn" title="TV'ye yansit">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M2 16.1A5 5 0 0 1 5.9 20"/>
                            <path d="M2 12.05A9 9 0 0 1 9.95 20"/>
                            <path d="M2 8V6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-6"/>
                            <line x1="2" y1="20" x2="2.01" y2="20"/>
                        </svg>
                        YANSIT
                    </button>

                    <div class="vol-wrap">
                        <button class="ibtn" id="muteBtn" title="Ses kapat/ac">
                            <svg id="volIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2">
                                <polygon points="11,5 6,9 2,9 2,15 6,15 11,19"/>
                                <path d="M19.07,4.93a10,10,0,0,1,0,14.14"/>
                                <path d="M15.54,8.46a5,5,0,0,1,0,7.07"/>
                            </svg>
                        </button>
                        <input type="range" class="vol-slider" id="volSlider" min="0" max="100" value="100">
                    </div>

                    <div class="ep-dots" id="epDots">
                        <?php foreach($videos as $i => $v): ?>
                        <button class="ep-dot <?= $i===$index?'active':'' ?>" data-index="<?= $i ?>" title="<?= htmlspecialchars($v['ep']) ?>"></button>
                        <?php endforeach; ?>
                    </div>

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
    </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<!-- ===== PLAYLIST OVERLAY ===== -->
<div class="overlay" id="plOverlay">
    <div class="panel">
        <div class="panel-header">
            <span class="panel-title">Playlist Sec</span>
            <button class="panel-close" id="plClose">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="item-list">
            <?php foreach($PLAYLISTS as $pi => $pl): ?>
            <a class="pl-item <?= $pi === $playlistParam ? 'current' : '' ?>" href="?pl=<?= $pi ?>&v=0">
                <span class="pl-icon"><?= htmlspecialchars($pl['icon']) ?></span>
                <div class="pl-info">
                    <span class="pl-name"><?= htmlspecialchars($pl['name']) ?></span>
                    <span class="pl-sub">PLAYLIST <?= $pi + 1 ?></span>
                </div>
                <?php if($pi === $playlistParam): ?>
                <span class="pl-now"><span class="pl-now-dot"></span>OYNATILIYOR</span>
                <?php else: ?>
                <svg class="pl-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><polyline points="9,18 15,12 9,6"/></svg>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ===== DUBLAJ (Audio Track) OVERLAY ===== -->
<div class="overlay" id="dubOverlay">
    <div class="panel">
        <div class="panel-header">
            <span class="panel-title">Ses Dili</span>
            <button class="panel-close" id="dubClose">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="item-list" id="dubList">
            <div class="empty-msg">YUKLENIYOR...</div>
        </div>
    </div>
</div>

<!-- ===== ALTYAZI (Captions) OVERLAY ===== -->
<div class="overlay" id="subOverlay">
    <div class="panel">
        <div class="panel-header">
            <span class="panel-title">Altyazi</span>
            <button class="panel-close" id="subClose">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="item-list" id="subList">
            <div class="empty-msg">YUKLENIYOR...</div>
        </div>
    </div>
</div>

<!-- ===== CAST OVERLAY ===== -->
<div class="overlay" id="castOverlay">
    <div class="panel">
        <div class="panel-header">
            <span class="panel-title">TV'ye Yansit</span>
            <button class="panel-close" id="castClose">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div id="castContent">
            <div class="cast-scanning" id="castScanning">
                <div class="cast-scan-ring">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#4f8ef7" stroke-width="2">
                        <path d="M2 16.1A5 5 0 0 1 5.9 20"/><path d="M2 12.05A9 9 0 0 1 9.95 20"/>
                        <path d="M2 8V6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-6"/>
                        <line x1="2" y1="20" x2="2.01" y2="20"/>
                    </svg>
                </div>
                <div class="cast-scan-label" id="castScanLabel">CIHAZLAR ARANIYOR...</div>
            </div>
            <div class="item-list" id="castDeviceList" style="display:none"></div>
            <div id="castConnectedInfo" style="display:none;text-align:center;padding:10px 0;">
                <div style="font-size:32px;margin-bottom:10px">&#128250;</div>
                <div style="font-size:13px;font-weight:600;margin-bottom:6px" id="castConnectedName"></div>
                <div class="cast-connected-label"><span class="cast-status-dot"></span>YAYINDA</div>
                <button class="cast-disconnect-btn" id="castDisconnectBtn">BAGLANTIY KAPAT</button>
            </div>
        </div>
        <div class="cast-tip" id="castTip">Chromecast, Android TV veya Smart TV'niz ile<br>ayni Wi-Fi aginda oldugunuzdan emin olun.</div>
    </div>
</div>

<script src="https://www.youtube.com/iframe_api"></script>
<script>
// =====================================================
// TEMEL CONFIG
// =====================================================
const VIDEOS = <?= json_encode(array_values($videos)) ?>;
let currentIndex = <?= $index ?>;
let player, isPlaying = false, isMuted = false, ticker;

// ─── Toast ───────────────────────────────────────────
let toastTimer;
function showToast(msg, dur) {
    dur = dur || 2500;
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function(){ t.classList.remove('show'); }, dur);
}

function sleep(ms){ return new Promise(function(r){ setTimeout(r, ms); }); }

// ─── YT API Ready ────────────────────────────────────
window.onYouTubeIframeAPIReady = function(){
    createPlayer(VIDEOS[currentIndex].id);
};

function createPlayer(videoId){
    if(player){ player.destroy(); }
    player = new YT.Player('yt-player',{
        videoId: videoId,
        playerVars:{
            controls:0, rel:0, modestbranding:1, playsinline:1,
            disablekb:1, iv_load_policy:3, fs:0, enablejsapi:1, cc_load_policy:0
        },
        events:{ onReady:onPlayerReady, onStateChange:onStateChange }
    });
}

function onPlayerReady(e){
    var key = 'sb_pos_' + VIDEOS[currentIndex].id;
    var saved = parseFloat(localStorage.getItem(key));
    if(saved > 0) player.seekTo(saved, true);
    clearInterval(ticker);
    ticker = setInterval(tick, 500);
}

function onStateChange(e){
    var S = YT.PlayerState;
    if(e.data === S.PLAYING){ setPlaying(true); showSpinner(false); }
    else if(e.data === S.PAUSED || e.data === S.ENDED){ setPlaying(false); }
    else if(e.data === S.BUFFERING){ showSpinner(true); }
    if(e.data === S.ENDED){
        var next = currentIndex + 1;
        if(next < VIDEOS.length) goTo(next);
    }
}

function tick(){
    if(!player || typeof player.getCurrentTime !== 'function') return;
    var cur = player.getCurrentTime();
    var dur = player.getDuration() || 1;
    document.getElementById('progressFill').style.width = (cur/dur*100).toFixed(2) + '%';
    document.getElementById('timeDisplay').textContent = fmt(cur) + ' / ' + fmt(dur);
    localStorage.setItem('sb_pos_' + VIDEOS[currentIndex].id, cur);
}

function fmt(s){
    s = Math.floor(s);
    var m = Math.floor(s/60), sec = s%60;
    return m + ':' + (sec < 10 ? '0' : '') + sec;
}

function setPlaying(val){
    isPlaying = val;
    document.getElementById('playIcon').style.display  = val ? 'none' : '';
    document.getElementById('pauseIcon').style.display = val ? '' : 'none';
    document.getElementById('playLabel').textContent   = val ? 'DURAKLAT' : 'OYNAT';
    document.getElementById('centerRing').classList.toggle('playing', val);
    showSpinner(false);
}

function showSpinner(v){ document.getElementById('spinner').classList.toggle('show', v); }

function toggle(){ if(!player) return; if(isPlaying) player.pauseVideo(); else player.playVideo(); }

function goTo(idx){
    if(idx < 0 || idx >= VIDEOS.length) return;
    currentIndex = idx;
    var v = VIDEOS[idx];
    document.getElementById('metaTitle').textContent = v.title;
    document.getElementById('epBadge').textContent   = v.ep;
    document.getElementById('progressFill').style.width = '0%';
    document.getElementById('timeDisplay').textContent  = '0:00 / 0:00';
    document.querySelectorAll('.ep-dot').forEach(function(d,i){ d.classList.toggle('active', i === idx); });
    document.getElementById('prevBtn').style.opacity = idx === 0 ? '.35' : '1';
    document.getElementById('nextBtn').style.opacity = idx === VIDEOS.length-1 ? '.35' : '1';
    setPlaying(false);
    if(player){
        player.loadVideoById(v.id);
        setTimeout(function(){
            var s = parseFloat(localStorage.getItem('sb_pos_' + v.id));
            if(s > 0) player.seekTo(s, true);
        }, 800);
    }
    // Dub/sub cache'ini temizle
    dubTracksCache = null;
    subTracksCache = null;
    document.getElementById('dubBtn').classList.remove('btn-active');
    document.getElementById('subBtn').classList.remove('btn-active');
}

// ─── Controls ────────────────────────────────────────
document.getElementById('playBtn').addEventListener('click', toggle);
document.getElementById('centerRing').addEventListener('click', toggle);
document.getElementById('prevBtn').addEventListener('click', function(){ goTo(currentIndex - 1); });
document.getElementById('nextBtn').addEventListener('click', function(){ goTo(currentIndex + 1); });
document.querySelectorAll('.ep-dot').forEach(function(btn){
    btn.addEventListener('click', function(){ goTo(parseInt(btn.dataset.index)); });
});

document.getElementById('progressTrack').addEventListener('click', function(e){
    if(!player) return;
    var r = this.getBoundingClientRect();
    player.seekTo(player.getDuration() * ((e.clientX - r.left) / r.width), true);
});

document.getElementById('volSlider').addEventListener('input', function(){
    if(!player) return;
    var v = parseInt(this.value);
    player.setVolume(v);
    if(v === 0){ player.mute(); isMuted = true; }
    else { player.unMute(); isMuted = false; }
});

document.getElementById('muteBtn').addEventListener('click', function(){
    if(!player) return;
    isMuted = !isMuted;
    if(isMuted){ player.mute(); document.getElementById('volSlider').value = 0; }
    else { player.unMute(); document.getElementById('volSlider').value = 100; player.setVolume(100); }
});

var stage = document.getElementById('stage');
document.getElementById('fsBtn').addEventListener('click', function(){
    if(!document.fullscreenElement) stage.requestFullscreen && stage.requestFullscreen();
    else document.exitFullscreen && document.exitFullscreen();
});
document.addEventListener('fullscreenchange', function(){
    var fs = !!document.fullscreenElement;
    document.getElementById('fsIcon').style.display     = fs ? 'none' : '';
    document.getElementById('fsExitIcon').style.display = fs ? '' : 'none';
});

// UI auto-hide
var hideTimer;
var ui = document.getElementById('ui');
function showUI(){
    ui.classList.remove('hide');
    clearTimeout(hideTimer);
    hideTimer = setTimeout(function(){ if(isPlaying) ui.classList.add('hide'); }, 3000);
}
stage.addEventListener('mousemove', showUI);
stage.addEventListener('touchstart', function(){ if(ui.classList.contains('hide')) showUI(); });

document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closeAll();
});

function closeAll(){
    document.querySelectorAll('.overlay').forEach(function(o){ o.classList.remove('open'); });
}
function openOverlay(id){
    closeAll();
    document.getElementById(id).classList.add('open');
    clearTimeout(hideTimer);
    ui.classList.remove('hide');
}
function closeOverlay(id){ document.getElementById(id).classList.remove('open'); }

// Dis tiklama & close butonlari
['plOverlay','dubOverlay','subOverlay','castOverlay'].forEach(function(id){
    var el = document.getElementById(id);
    el.addEventListener('click', function(e){ if(e.target === el) closeOverlay(id); });
});
document.getElementById('plClose').addEventListener('click',   function(){ closeOverlay('plOverlay'); });
document.getElementById('dubClose').addEventListener('click',  function(){ closeOverlay('dubOverlay'); });
document.getElementById('subClose').addEventListener('click',  function(){ closeOverlay('subOverlay'); });
document.getElementById('castClose').addEventListener('click', function(){ closeOverlay('castOverlay'); });

document.getElementById('plMenuBtn').addEventListener('click', function(e){
    e.stopPropagation();
    openOverlay('plOverlay');
});

// =====================================================
// ORTAK: DIL & BAYRAK
// =====================================================
var LANG_FLAGS = {tr:'🇹🇷',en:'🇬🇧',de:'🇩🇪',fr:'🇫🇷',ja:'🇯🇵',es:'🇪🇸',it:'🇮🇹',pt:'🇵🇹',ar:'🇸🇦',ko:'🇰🇷',ru:'🇷🇺',zh:'🇨🇳',hi:'🇮🇳',id:'🇮🇩',nl:'🇳🇱',pl:'🇵🇱',sv:'🇸🇪',da:'🇩🇰',fi:'🇫🇮',no:'🇳🇴'};
var LANG_NAMES = {tr:'Turkce',en:'Ingilizce',de:'Almanca',fr:'Fransizca',ja:'Japonca',es:'Ispanyolca',it:'Italyanca',pt:'Portekizce',ar:'Arapca',ko:'Korece',ru:'Rusca',zh:'Cince',hi:'Hintce',id:'Endonezce',nl:'Felemenkce',pl:'Lehce',sv:'Isvecce',da:'Danimarkaca',fi:'Fince',no:'Norvecce'};

function getLangFlag(code){ return LANG_FLAGS[code] || '🌐'; }
function getLangName(code, displayName){ return LANG_NAMES[code] || displayName || (code ? code.toUpperCase() : '?'); }

// YouTube player modulu tespiti (HTML5=captions, Flash=cc)
function getYTModule(){
    try {
        var mods = player.getOptions();
        if(mods && mods.indexOf('captions') !== -1) return 'captions';
        if(mods && mods.indexOf('cc') !== -1) return 'cc';
    } catch(e){}
    return 'captions';
}

// Ortak item olusturucu
function makeListItem(icon, name, sub, isActive){
    var div = document.createElement('div');
    div.className = 'list-item' + (isActive ? ' active' : '');
    div.innerHTML =
        '<div class="item-icon">' + icon + '</div>' +
        '<div class="item-info">' +
            '<span class="item-name">' + name + '</span>' +
            '<span class="item-sub">' + sub + '</span>' +
        '</div>' +
        '<div class="item-check">' +
            '<svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="#4f8ef7" stroke-width="3"><polyline points="20,6 9,17 4,12"/></svg>' +
        '</div>';
    return div;
}

// =====================================================
// DUBLAJ — YouTube IFrame API audioTrack modulu
// Dokumantasyon: player.getOption('audioTrack','list')
//                player.setOption('audioTrack','track', {...})
// =====================================================
var dubTracksCache = null;

function loadDubTracks(){
    if(!player || typeof player.getOption !== 'function') return [];
    var tracks = [];
    try {
        // Birden fazla ses dili olan videolarda dolu gelir
        var raw = player.getOption('audioTrack', 'list');
        if(raw && raw.length > 0){
            var cur = player.getOption('audioTrack', 'track') || {};
            tracks = raw.map(function(t, i){
                return {
                    code:        t.languageCode || ('track'+i),
                    displayName: t.displayName  || getLangName(t.languageCode, ''),
                    isSelected:  cur && cur.languageCode === t.languageCode
                };
            });
        }
    } catch(e){}
    return tracks;
}

function renderDubList(tracks){
    var list = document.getElementById('dubList');
    list.innerHTML = '';

    if(!tracks || tracks.length === 0){
        var msg = document.createElement('div');
        msg.className = 'empty-msg';
        msg.textContent = 'BU VIDEODA EK SES DILI YOK';
        list.appendChild(msg);
        return;
    }

    tracks.forEach(function(t){
        var item = makeListItem(
            getLangFlag(t.code),
            getLangName(t.code, t.displayName),
            t.code.toUpperCase() + ' · DUB',
            t.isSelected
        );
        item.addEventListener('click', function(){
            try {
                // Gercek YouTube audioTrack degisimi
                player.setOption('audioTrack', 'track', {
                    languageCode: t.code,
                    displayName:  t.displayName || getLangName(t.code, '')
                });
                showToast('Ses dili: ' + getLangName(t.code, t.displayName));
                // Cache'i guncelle
                dubTracksCache = dubTracksCache.map(function(tr){
                    return { code:tr.code, displayName:tr.displayName, isSelected: tr.code === t.code };
                });
                renderDubList(dubTracksCache);
                // Orijinal dil disinda ise buton rengini mavi yap
                var anyNonDefault = dubTracksCache.some(function(tr){ return tr.isSelected && tracks.length > 1; });
                document.getElementById('dubBtn').classList.toggle('btn-active', anyNonDefault);
            } catch(e){
                showToast('Ses dili degistirilemedi');
            }
            setTimeout(function(){ closeOverlay('dubOverlay'); }, 280);
        });
        list.appendChild(item);
    });
}

document.getElementById('dubBtn').addEventListener('click', function(e){
    e.stopPropagation();
    openOverlay('dubOverlay');
    document.getElementById('dubList').innerHTML = '<div class="empty-msg">YUKLENIYOR...</div>';

    // Player hazir olmayabilir, birkac deneme yap
    var tries = 0;
    var check = setInterval(function(){
        tries++;
        if(player && typeof player.getOption === 'function'){
            clearInterval(check);
            dubTracksCache = loadDubTracks();
            renderDubList(dubTracksCache);
        } else if(tries > 15){
            clearInterval(check);
            renderDubList([]);
        }
    }, 200);
});

// =====================================================
// ALTYAZI — YouTube IFrame API Captions modulu
// Kaynak: https://terrillthompson.com/648
// player.getOption(mod,'tracklist') → mevcut altyazilari listeler
// player.setOption(mod,'track',{languageCode:...}) → altyazi secimi
// player.setOption(mod,'track',{}) → altyaziyi kapatir
// =====================================================
var subTracksCache = null;

function loadSubTracks(){
    if(!player || typeof player.getOption !== 'function') return [];
    var tracks = [];
    try {
        var mod = getYTModule();
        var raw = player.getOption(mod, 'tracklist');
        if(raw && raw.length > 0){
            var cur = player.getOption(mod, 'track') || {};
            tracks = raw.map(function(t){
                return {
                    languageCode: t.languageCode || 'unk',
                    displayName:  t.displayName  || t.languageName || getLangName(t.languageCode, ''),
                    isSelected:   cur && cur.languageCode === t.languageCode
                };
            });
        }
    } catch(e){}
    return tracks;
}

function isSubOff(){
    try {
        var mod = getYTModule();
        var cur = player.getOption(mod, 'track');
        return !cur || !cur.languageCode;
    } catch(e){ return true; }
}

function renderSubList(tracks){
    var list = document.getElementById('subList');
    list.innerHTML = '';

    // "Kapali" secenegi her zaman uste
    var offItem = makeListItem('🚫', 'Kapali', 'ALTYAZI YOK', isSubOff());
    offItem.addEventListener('click', function(){
        try {
            var mod = getYTModule();
            player.setOption(mod, 'track', {});  // bos nesne = kapali
            showToast('Altyazi kapatildi');
            document.getElementById('subBtn').classList.remove('btn-active');
        } catch(e){ showToast('Altyazi kapatilamadi'); }
        subTracksCache = null;
        setTimeout(function(){ closeOverlay('subOverlay'); }, 280);
    });
    list.appendChild(offItem);

    if(!tracks || tracks.length === 0){
        var msg = document.createElement('div');
        msg.className = 'empty-msg';
        msg.textContent = 'BU VIDEODA ALTYAZI YOK';
        list.appendChild(msg);
        return;
    }

    tracks.forEach(function(t){
        var item = makeListItem(
            getLangFlag(t.languageCode),
            getLangName(t.languageCode, t.displayName),
            t.languageCode.toUpperCase(),
            t.isSelected
        );
        item.addEventListener('click', function(){
            try {
                var mod = getYTModule();
                player.setOption(mod, 'track', {
                    languageCode: t.languageCode,
                    displayName:  t.displayName
                });
                showToast('Altyazi: ' + getLangName(t.languageCode, t.displayName));
                document.getElementById('subBtn').classList.add('btn-active');
            } catch(e){ showToast('Altyazi degistirilemedi'); }
            subTracksCache = null;
            setTimeout(function(){ closeOverlay('subOverlay'); }, 280);
        });
        list.appendChild(item);
    });
}

document.getElementById('subBtn').addEventListener('click', function(e){
    e.stopPropagation();
    openOverlay('subOverlay');
    document.getElementById('subList').innerHTML = '<div class="empty-msg">YUKLENIYOR...</div>';

    // Video oynatilirken captions modulu yuklenir; hazir olmasi icin bekle
    var tries = 0;
    var check = setInterval(function(){
        tries++;
        if(player && typeof player.getOption === 'function'){
            var mod = getYTModule();
            try {
                var raw = player.getOption(mod, 'tracklist');
                // tracklist null degil demek moduel hazir
                clearInterval(check);
                subTracksCache = loadSubTracks();
                renderSubList(subTracksCache);
                return;
            } catch(e){}
        }
        if(tries > 20){
            clearInterval(check);
            subTracksCache = [];
            renderSubList([]);
        }
    }, 250);
});

// =====================================================
// CAST — Chromecast (Cast Framework) + PresentationRequest
// =====================================================
var castConnected   = false;
var castSession     = null;
var castDeviceName  = '';

// Cast Framework hazir oldugunda cagrilir
window['__onGCastApiAvailable'] = function(isAvailable){
    if(isAvailable){
        try {
            var ctx = cast.framework.CastContext.getInstance();
            ctx.setOptions({
                receiverApplicationId: chrome.cast.media.DEFAULT_MEDIA_RECEIVER_APP_ID,
                autoJoinPolicy: chrome.cast.AutoJoinPolicy.ORIGIN_SCOPED
            });
            ctx.addEventListener(
                cast.framework.CastContextEventType.SESSION_STATE_CHANGED,
                onCastSessionChange
            );
        } catch(e){}
    }
};

function onCastSessionChange(event){
    try {
        var ss = cast.framework.SessionState;
        if(event.sessionState === ss.SESSION_STARTED || event.sessionState === ss.SESSION_RESUMED){
            castConnected  = true;
            castSession    = cast.framework.CastContext.getInstance().getCurrentSession();
            castDeviceName = castSession.getCastDevice().friendlyName || 'TV';
            document.getElementById('castBtn').classList.add('btn-active');
            showToast('TV\'ye baglandi: ' + castDeviceName);
            sendYouTubeUrlToCast();
            if(document.getElementById('castOverlay').classList.contains('open')){
                showCastConnectedUI(castDeviceName);
            }
        } else if(event.sessionState === cast.framework.SessionState.SESSION_ENDED){
            castConnected  = false;
            castSession    = null;
            castDeviceName = '';
            document.getElementById('castBtn').classList.remove('btn-active');
            showToast('Cast baglantisi kesildi');
        }
    } catch(e){}
}

function sendYouTubeUrlToCast(){
    if(!castSession) return;
    try {
        var videoId = VIDEOS[currentIndex].id;
        var ytUrl   = 'https://www.youtube.com/watch?v=' + videoId;
        var mInfo   = new chrome.cast.media.MediaInfo(ytUrl, 'video/mp4');
        mInfo.metadata = new chrome.cast.media.GenericMediaMetadata();
        mInfo.metadata.title = VIDEOS[currentIndex].title;
        var req = new chrome.cast.media.LoadRequest(mInfo);
        req.currentTime = player ? player.getCurrentTime() : 0;
        castSession.loadMedia(req);
    } catch(e){}
}

function showCastConnectedUI(name){
    document.getElementById('castScanning').style.display    = 'none';
    document.getElementById('castDeviceList').style.display  = 'none';
    document.getElementById('castConnectedInfo').style.display = 'block';
    document.getElementById('castConnectedName').textContent = name;
    document.getElementById('castTip').style.display = 'none';
}

function showCastScanningUI(){
    document.getElementById('castScanning').style.display    = 'flex';
    document.getElementById('castDeviceList').style.display  = 'none';
    document.getElementById('castConnectedInfo').style.display = 'none';
    document.getElementById('castTip').style.display = 'block';
}

function showCastNoDeviceUI(){
    document.getElementById('castScanning').style.display    = 'none';
    document.getElementById('castConnectedInfo').style.display = 'none';
    document.getElementById('castTip').style.display = 'block';
    var dl = document.getElementById('castDeviceList');
    dl.innerHTML = '<div class="empty-msg">CIHAZ BULUNAMADI</div>';
    dl.style.display = 'block';
}

// Baglantiy kes butonu
document.getElementById('castDisconnectBtn').addEventListener('click', function(){
    try {
        if(typeof cast !== 'undefined'){
            cast.framework.CastContext.getInstance().endCurrentSession(true);
        }
        castConnected = false;
        castSession   = null;
        document.getElementById('castBtn').classList.remove('btn-active');
    } catch(e){}
    closeOverlay('castOverlay');
    showToast('Cast baglantisi kesildi');
});

// Cast butonu
document.getElementById('castBtn').addEventListener('click', function(e){
    e.stopPropagation();

    // Zaten bagliysa baglanma durumunu goster
    if(castConnected){
        openOverlay('castOverlay');
        showCastConnectedUI(castDeviceName || 'TV');
        return;
    }

    openOverlay('castOverlay');
    showCastScanningUI();

    // 1) Chromecast API
    if(typeof cast !== 'undefined' && typeof chrome !== 'undefined' && chrome.cast){
        document.getElementById('castScanLabel').textContent = 'CHROMECAST ARANIYOR...';
        cast.framework.CastContext.getInstance().requestSession()
            .then(function(){
                // onCastSessionChange halleder
            })
            .catch(function(err){
                if(err && err.code !== 'cancel'){
                    showNoDeviceFallback();
                } else {
                    closeOverlay('castOverlay');
                }
            });
    }
    // 2) PresentationRequest API (bazi Smart TV'ler, Miracast)
    else if('PresentationRequest' in window){
        document.getElementById('castScanLabel').textContent = 'SMART TV ARANIYOR...';
        var videoId = VIDEOS[currentIndex].id;
        var presUrl = 'https://www.youtube.com/tv#/watch?v=' + videoId;
        var req = new PresentationRequest([presUrl]);
        req.start()
            .then(function(conn){
                castConnected  = true;
                castDeviceName = 'Smart TV';
                document.getElementById('castBtn').classList.add('btn-active');
                showToast('Smart TV\'ye yansitiliyor');
                showCastConnectedUI('Smart TV');
                conn.onclose = function(){
                    castConnected = false;
                    document.getElementById('castBtn').classList.remove('btn-active');
                };
            })
            .catch(function(){ showNoDeviceFallback(); });
    }
    // 3) Fallback: YouTube TV'yi yeni sekmede ac
    else {
        showNoDeviceFallback();
    }
});

function showNoDeviceFallback(){
    showCastNoDeviceUI();
    // YouTube TV linkini fallback olarak sun
    var videoId = VIDEOS[currentIndex].id;
    var t = player ? Math.floor(player.getCurrentTime()) : 0;
    var ytTvUrl = 'https://www.youtube.com/tv#/watch?v=' + videoId + (t > 0 ? '&t=' + t + 's' : '');
    var dl = document.getElementById('castDeviceList');
    var fallback = document.createElement('div');
    fallback.className = 'list-item';
    fallback.style.marginTop = '4px';
    fallback.innerHTML =
        '<div class="item-icon">&#128250;</div>' +
        '<div class="item-info">' +
            '<span class="item-name">YouTube TV\'yi Ac</span>' +
            '<span class="item-sub">TARAYICI TV MODU</span>' +
        '</div>' +
        '<svg class="item-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><polyline points="9,18 15,12 9,6"/></svg>';
    fallback.addEventListener('click', function(){
        window.open(ytTvUrl, '_blank');
        closeOverlay('castOverlay');
        showToast('YouTube TV acildi');
    });
    dl.appendChild(fallback);
    dl.style.display = 'block';
}

// Cast SDK'yi sayfa sonunda async yukle
(function(){
    var s = document.createElement('script');
    s.src = 'https://www.gstatic.com/cv/js/sender/v1/cast_sender.js?loadCastFramework=1';
    s.async = true;
    document.head.appendChild(s);
})();
</script>
</body>
</html>
<?php
$season  = isset($_GET['season'])  ? intval($_GET['season'])  : 1;
$episode = isset($_GET['episode']) ? intval($_GET['episode']) : 0;

$seriesData = [
    1 => [
        ['name' => '1. Bölüm: Will Byers\'ın Kayboluşu',  'link' => 'https://vs15.photour.org/v/Stranger.Things.S01E01.WEBRip.1080p.DUAL.x264-HDM/master.m3u8'],
        ['name' => '2. Bölüm: Maple Sokağı\'ndaki Kaçık',  'link' => 'https://vs19.photofunia.pro/v/Stranger.Things.S01E02.WEBRip.1080p.DUAL.x264-HDM/master.m3u8'],
        ['name' => '3. Bölüm: Noel Işıkları',               'link' => 'https://vs9.photomag.biz/v/Stranger.Things.S01E03.WEBRip.1080p.DUAL.x264-HDM/master.m3u8'],
        ['name' => '4. Bölüm: Ceset',                       'link' => 'https://vs18.gamephotos.pro/v/Stranger.Things.S01E04.WEBRip.1080p.DUAL.x264-HDM/master.m3u8'],
        ['name' => '5. Bölüm: Pire ve İp Cambazı',          'link' => 'https://vs14.photofunny.org/v/Stranger.Things.S01E05.WEBRip.1080p.DUAL.x264-HDM/master.m3u8'],
        ['name' => '6. Bölüm: Canavar',                     'link' => 'https://vs14.gamephotos.pro/v/Stranger.Things.S01E06.WEBRip.1080p.DUAL.x264-HDM/master.m3u8'],
        ['name' => '7. Bölüm: Küvet',                       'link' => 'https://vs12.photoflax.org/v/Stranger.Things.S01E07.WEBRip.1080p.DUAL.x264-HDM/master.m3u8'],
        ['name' => '8. Bölüm: Baş Aşağı',                  'link' => 'https://vs14.photoflix.org/v/Stranger.Things.S01E08.WEBRip.1080p.DUAL.x264-HDM/master.m3u8'],
    ],
    2 => [
        ['name' => '1. Bölüm: Madmax',                     'link' => 'https://vs15.photour.org/v/Stranger.Things.S02E01.WEB-DL.1080p.DUAL.H.264-HDM/master.m3u8'],
        ['name' => '2. Bölüm: Şeker mi Şaka mı Kaçık',     'link' => 'https://vs15.photostack.net/v/Stranger.Things.S02E02.WEB-DL.1080p.DUAL.H.264-HDM/master.m3u8'],
        ['name' => '3. Bölüm: İribaş',                     'link' => 'https://vs9.photomag.biz/v/Stranger.Things.S02E03.WEB-DL.1080p.DUAL.H.264-HDM/master.m3u8'],
        ['name' => '4. Bölüm: Bilge Will',                  'link' => 'https://vs18.photomag.biz/v/Stranger.Things.S02E04.WEB-DL.1080p.DUAL.H.264-HDM/master.m3u8'],
        ['name' => '5. Bölüm: Dig Dug',                    'link' => 'https://vs12.photostack.net/v/Stranger.Things.S02E05.WEB-DL.1080p.DUAL.H.264-HDM/master.m3u8'],
        ['name' => '6. Bölüm: Casus',                       'link' => 'https://vs11.photoflix.org/v/Stranger.Things.S02E06.WEB-DL.1080p.DUAL.H.264-HDM/master.m3u8'],
        ['name' => '7. Bölüm: Kayıp Kız Kardeş',           'link' => 'https://vs9.gamephotos.pro/v/Stranger.Things.S02E07.WEB-DL.1080p.DUAL.H.264-HDM/master.m3u8'],
        ['name' => '8. Bölüm: Zihin Hırsızı',              'link' => 'https://vs15.photostack.net/v/Stranger.Things.S02E08.WEB-DL.1080p.DUAL.H.264-HDM/master.m3u8'],
        ['name' => '9. Bölüm: Kapı',                        'link' => 'https://vs10.pictureflix.org/v/Stranger.Things.S02E09.WEB-DL.1080p.DUAL.H.264-HDM/master.m3u8'],
    ],
];

if (!isset($seriesData[$season])) $season = 1;
$episodes      = $seriesData[$season];
$totalSeasons  = count($seriesData);
if ($episode < 0 || $episode >= count($episodes)) $episode = 0;

$currentEp   = $episodes[$episode];
$prevEpisode = $episode > 0                     ? $episode - 1 : null;
$nextEpisode = $episode < count($episodes) - 1  ? $episode + 1 : null;
$epLabel     = 'S'.str_pad($season,2,'0',STR_PAD_LEFT).' E'.str_pad($episode+1,2,'0',STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title><?= $epLabel ?> · <?= htmlspecialchars($currentEp['name']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --a:#29b6f6;--a2:#0288d1;
  --ag:rgba(41,182,246,.4);
  --adim:rgba(41,182,246,.15);
  --font:'Outfit',sans-serif;
  --ease:cubic-bezier(.4,0,.2,1);
}
html,body{width:100%;height:100%;background:#000;overflow:hidden;font-family:var(--font);color:#fff;-webkit-tap-highlight-color:transparent;user-select:none;}

/* ───── PLAYER ───── */
#player{
  position:fixed;inset:0;background:#000;
  display:flex;align-items:center;justify-content:center;
  cursor:none;
}
#player.ui{cursor:default}
video{width:100%;height:100%;object-fit:contain;display:block;}

/* ───── GRADIENTS ───── */
.g-top{
  position:absolute;top:0;left:0;right:0;height:140px;
  background:linear-gradient(to bottom,rgba(0,0,0,.8) 0%,transparent 100%);
  pointer-events:none;opacity:0;transition:opacity .35s var(--ease);
}
.g-bot{
  position:absolute;bottom:0;left:0;right:0;height:220px;
  background:linear-gradient(to top,rgba(0,0,0,.9) 0%,rgba(0,0,0,.4) 60%,transparent 100%);
  pointer-events:none;opacity:0;transition:opacity .35s var(--ease);
}
#player.ui .g-top,
#player.ui .g-bot,
#player.paused .g-top,
#player.paused .g-bot{opacity:1}

/* ───── TOP BAR ───── */
.top-bar{
  position:absolute;top:0;left:0;right:0;
  padding:20px 24px;
  display:flex;align-items:center;gap:12px;
  opacity:0;transform:translateY(-10px);
  transition:opacity .3s var(--ease),transform .3s var(--ease);
  pointer-events:none;
}
#player.ui .top-bar,
#player.paused .top-bar{opacity:1;transform:translateY(0);pointer-events:all}

.top-badge{
  font-size:10px;font-weight:700;letter-spacing:2px;
  color:var(--a);background:var(--adim);
  border:1px solid rgba(41,182,246,.3);
  padding:4px 11px;border-radius:20px;white-space:nowrap;
}
.top-title{
  font-size:13px;font-weight:400;color:rgba(255,255,255,.75);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1;
}
.hd-badge{
  font-size:10px;font-weight:700;letter-spacing:1.5px;
  color:var(--a);border:1px solid rgba(41,182,246,.3);
  padding:3px 9px;border-radius:20px;white-space:nowrap;
}

/* ───── CENTER CONTROLS ───── */
.ctrls{
  position:absolute;top:50%;left:50%;
  transform:translate(-50%,-50%);
  display:flex;align-items:center;gap:40px;
  opacity:0;transition:opacity .3s var(--ease);
  pointer-events:none;
}
#player.ui .ctrls,
#player.paused .ctrls{opacity:1;pointer-events:all}

/* seek buttons */
.sk{
  width:54px;height:54px;
  background:rgba(255,255,255,.07);
  border:1.5px solid rgba(255,255,255,.15);
  border-radius:50%;
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  cursor:pointer;gap:1px;
  transition:background .2s,border-color .2s,transform .15s;
}
.sk:hover{background:rgba(41,182,246,.18);border-color:var(--a);}
.sk:active{transform:scale(.87);}
.sk svg{width:22px;height:22px;fill:#fff;}
.sk span{font-size:9px;font-weight:700;letter-spacing:.4px;color:rgba(255,255,255,.65);}

/* main play */
.pbtn{
  width:72px;height:72px;
  background:rgba(41,182,246,.15);
  border:2px solid var(--a);
  border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  cursor:pointer;
  transition:background .2s,transform .15s,box-shadow .2s;
  box-shadow:0 0 0 0 var(--ag);
}
.pbtn:hover{background:rgba(41,182,246,.28);box-shadow:0 0 28px var(--ag);}
.pbtn:active{transform:scale(.9);}
.pbtn svg{width:30px;height:30px;fill:#fff;}
#pi{margin-left:3px;}
#pai{display:none;}
#player.playing .pbtn #pi{display:none;}
#player.playing .pbtn #pai{display:block;}

/* ───── BOTTOM BAR ───── */
.bot{
  position:absolute;bottom:0;left:0;right:0;
  padding:0 22px 20px;
  opacity:0;transform:translateY(10px);
  transition:opacity .3s var(--ease),transform .3s var(--ease);
  pointer-events:none;
}
#player.ui .bot,
#player.paused .bot{opacity:1;transform:translateY(0);pointer-events:all}

/* progress */
.prog-row{display:flex;align-items:center;gap:10px;margin-bottom:10px;}
.tt{font-size:11px;font-weight:500;color:rgba(255,255,255,.65);white-space:nowrap;min-width:36px;}
.tt.r{text-align:right;}

.trk{
  flex:1;height:3px;background:rgba(255,255,255,.15);
  border-radius:3px;position:relative;cursor:pointer;
  transition:height .15s;
}
.trk:hover{height:5px;}
.trk:hover .thb{transform:translate(-50%,-50%) scale(1);}
.tbuf{position:absolute;left:0;top:0;bottom:0;background:rgba(41,182,246,.2);border-radius:3px;pointer-events:none;}
.tfill{
  position:absolute;left:0;top:0;bottom:0;
  background:linear-gradient(90deg,var(--a2),var(--a));
  border-radius:3px;pointer-events:none;
}
.thb{
  position:absolute;top:50%;
  width:13px;height:13px;background:#fff;border-radius:50%;
  transform:translate(-50%,-50%) scale(0);
  box-shadow:0 0 10px var(--ag);
  pointer-events:none;transition:transform .15s;
}

/* btn row */
.brow{display:flex;align-items:center;gap:4px;}
.cb{
  background:none;border:none;width:36px;height:36px;
  display:flex;align-items:center;justify-content:center;
  border-radius:8px;cursor:pointer;color:#fff;
  transition:background .15s;flex-shrink:0;
}
.cb:hover{background:rgba(255,255,255,.1);}
.cb svg{width:19px;height:19px;fill:currentColor;}

.vwrap{display:flex;align-items:center;gap:5px;}
.vsl{
  -webkit-appearance:none;width:72px;height:3px;
  border-radius:3px;background:rgba(255,255,255,.22);
  outline:none;cursor:pointer;accent-color:var(--a);
}
.vsl::-webkit-slider-thumb{-webkit-appearance:none;width:12px;height:12px;border-radius:50%;background:var(--a);cursor:pointer;}

.sp{flex:1;}

/* ep btn */
.epbtn{
  background:var(--adim);border:1px solid rgba(41,182,246,.3);
  border-radius:8px;color:var(--a);
  font-family:var(--font);font-size:11px;font-weight:600;letter-spacing:.4px;
  padding:7px 13px;display:flex;align-items:center;gap:6px;
  cursor:pointer;transition:background .15s,border-color .15s;white-space:nowrap;
}
.epbtn:hover{background:rgba(41,182,246,.25);border-color:var(--a);}
.epbtn svg{width:14px;height:14px;fill:var(--a);}

/* ───── EPISODE PANEL ───── */
.panel{
  position:absolute;top:0;right:0;bottom:0;
  width:min(310px,88vw);
  background:rgba(10,14,22,.94);
  backdrop-filter:blur(22px);-webkit-backdrop-filter:blur(22px);
  border-left:1px solid rgba(255,255,255,.07);
  display:flex;flex-direction:column;
  transform:translateX(100%);
  transition:transform .38s var(--ease);
  z-index:50;
}
.panel.open{transform:translateX(0);}

.ph{
  display:flex;align-items:center;
  padding:18px 16px 14px;
  border-bottom:1px solid rgba(255,255,255,.07);gap:10px;
}
.ph-title{font-size:14px;font-weight:600;flex:1;letter-spacing:.3px;}
.ph-close{
  width:30px;height:30px;
  background:rgba(255,255,255,.07);
  border:none;border-radius:7px;
  display:flex;align-items:center;justify-content:center;
  cursor:pointer;color:#fff;transition:background .15s;
}
.ph-close:hover{background:rgba(255,255,255,.14);}
.ph-close svg{width:16px;height:16px;fill:currentColor;}

.stabs{display:flex;gap:6px;padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.06);flex-wrap:wrap;}
.stab{
  font-size:10px;font-weight:700;letter-spacing:.6px;
  padding:5px 13px;border-radius:20px;
  border:1px solid rgba(255,255,255,.12);
  background:transparent;color:rgba(255,255,255,.45);
  cursor:pointer;text-decoration:none;transition:all .2s;
}
.stab:hover{border-color:var(--a);color:var(--a);}
.stab.act{background:var(--a);border-color:var(--a);color:#000;font-weight:700;}

.elist{flex:1;overflow-y:auto;padding:6px 10px 20px;}
.elist::-webkit-scrollbar{width:3px;}
.elist::-webkit-scrollbar-thumb{background:rgba(41,182,246,.25);border-radius:3px;}

.ei{
  display:flex;align-items:center;gap:11px;
  padding:10px 10px;border-radius:10px;
  cursor:pointer;text-decoration:none;color:#fff;
  transition:background .15s;position:relative;
}
.ei:hover{background:rgba(255,255,255,.06);}
.ei.act{background:rgba(41,182,246,.1);}
.ei.act::before{content:'';position:absolute;left:0;top:18%;bottom:18%;width:2.5px;background:var(--a);border-radius:2px;}
.en{font-size:17px;font-weight:700;color:rgba(255,255,255,.18);min-width:26px;text-align:center;transition:color .15s;}
.ei:hover .en,.ei.act .en{color:var(--a);}
.einfo{flex:1;}
.ename{font-size:12px;font-weight:500;line-height:1.4;}
.esub{font-size:10px;color:rgba(255,255,255,.38);margin-top:2px;}
.earr{
  width:27px;height:27px;background:rgba(255,255,255,.06);
  border-radius:50%;display:flex;align-items:center;justify-content:center;
  flex-shrink:0;transition:background .15s;
}
.ei:hover .earr,.ei.act .earr{background:var(--a);}
.earr svg{width:11px;height:11px;fill:rgba(255,255,255,.5);margin-left:2px;}
.ei:hover .earr svg,.ei.act .earr svg{fill:#000;}

/* ───── BACKDROP ───── */
.bkdrop{
  position:absolute;inset:0;background:rgba(0,0,0,.5);
  z-index:49;opacity:0;pointer-events:none;transition:opacity .35s;
}
.bkdrop.open{opacity:1;pointer-events:all;}

/* ───── LOADER ───── */
.ld{
  position:absolute;inset:0;
  display:flex;align-items:center;justify-content:center;
  background:rgba(0,0,0,.35);
  opacity:0;pointer-events:none;transition:opacity .25s;z-index:5;
}
.ld.on{opacity:1;}
.spin{width:40px;height:40px;border:2.5px solid rgba(41,182,246,.2);border-top-color:var(--a);border-radius:50%;animation:sp .8s linear infinite;}
@keyframes sp{to{transform:rotate(360deg);}}

/* ───── TOAST ───── */
.toast{
  position:absolute;bottom:96px;left:50%;
  transform:translateX(-50%) translateY(14px);
  background:rgba(8,12,20,.92);
  border:1px solid rgba(41,182,246,.28);
  color:#fff;font-size:12px;font-weight:500;
  padding:7px 18px;border-radius:20px;
  opacity:0;transition:opacity .2s,transform .22s;
  pointer-events:none;white-space:nowrap;z-index:60;
  backdrop-filter:blur(12px);
}
.toast.on{opacity:1;transform:translateX(-50%) translateY(0);}

/* ───── MOBILE ───── */
@media(max-width:540px){
  .vwrap{display:none;}.hd-badge{display:none;}
  .ctrls{gap:26px;}.pbtn{width:62px;height:62px;}.sk{width:48px;height:48px;}
  .panel{width:min(292px,93vw);}
}
</style>
</head>
<body>

<div id="player" class="paused" onclick="handleClick(event)">
  <video id="vid" playsinline></video>
  <div class="g-top"></div>
  <div class="g-bot"></div>
  <div class="ld" id="ld"><div class="spin"></div></div>

  <!-- TOP -->
  <div class="top-bar">
    <div class="top-badge"><?= $epLabel ?></div>
    <div class="top-title"><?= htmlspecialchars($currentEp['name']) ?></div>
    <div class="hd-badge">1080p</div>
  </div>

  <!-- CENTER -->
  <div class="ctrls" onclick="sp(event)">
    <button class="sk" onclick="seek(-10)">
      <svg viewBox="0 0 24 24"><path d="M12.5 3a9 9 0 1 0 6.36 2.64l-1.42 1.42A7 7 0 1 1 12.5 5V3zm-.5 0L8 7l4 4V3z"/></svg>
      <span>10</span>
    </button>
    <button class="pbtn" onclick="togglePlay()">
      <svg id="pi" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
      <svg id="pai" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
    </button>
    <button class="sk" onclick="seek(10)">
      <svg viewBox="0 0 24 24"><path d="M11.5 3a9 9 0 1 1-6.36 2.64l1.42 1.42A7 7 0 1 0 11.5 5V3zm.5 0l4 4-4 4V3z"/></svg>
      <span>10</span>
    </button>
  </div>

  <!-- BOTTOM -->
  <div class="bot" onclick="sp(event)">
    <div class="prog-row">
      <span class="tt" id="ct">0:00</span>
      <div class="trk" id="trk">
        <div class="tbuf" id="tbuf" style="width:0%"></div>
        <div class="tfill" id="tfill" style="width:0%"></div>
        <div class="thb" id="thb" style="left:0%"></div>
      </div>
      <span class="tt r" id="dt">0:00</span>
    </div>
    <div class="brow">
      <button class="cb" onclick="togglePlay()">
        <svg id="psm" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
      </button>
      <div class="vwrap">
        <button class="cb" onclick="toggleMute()">
          <svg id="vi" viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z"/></svg>
        </button>
        <input class="vsl" id="vsl" type="range" min="0" max="1" step="0.05" value="1" oninput="setVol(this.value)">
      </div>
      <div class="sp"></div>
      <button class="epbtn" onclick="openPanel()">
        <svg viewBox="0 0 24 24"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/></svg>
        Bölümler
      </button>
      <button class="cb" onclick="toggleFS()">
        <svg id="fsi" viewBox="0 0 24 24"><path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/></svg>
      </button>
    </div>
  </div>

  <!-- BACKDROP -->
  <div class="bkdrop" id="bk" onclick="closePanel()"></div>

  <!-- PANEL -->
  <div class="panel" id="panel" onclick="sp(event)">
    <div class="ph">
      <div class="ph-title">Bölümler</div>
      <button class="ph-close" onclick="closePanel()">
        <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
      </button>
    </div>
    <div class="stabs">
      <?php for ($s=1;$s<=$totalSeasons;$s++): ?>
        <a href="?season=<?=$s?>&episode=0" class="stab <?=$s===$season?'act':''?>">Sezon <?=$s?></a>
      <?php endfor; ?>
    </div>
    <div class="elist">
      <?php foreach($episodes as $i=>$ep): ?>
        <a href="?season=<?=$season?>&episode=<?=$i?>" class="ei <?=$i===$episode?'act':''?>">
          <div class="en"><?=str_pad($i+1,2,'0',STR_PAD_LEFT)?></div>
          <div class="einfo">
            <div class="ename"><?=htmlspecialchars($ep['name'])?></div>
            <div class="esub">Sezon <?=$season?> &middot; 1080p &middot; Türkçe</div>
          </div>
          <div class="earr"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="toast" id="toast"></div>
</div>

<script>
const SRC = <?=json_encode($currentEp['link'])?>;
const vid = document.getElementById('vid');
const player = document.getElementById('player');
const ld = document.getElementById('ld');
const tfill = document.getElementById('tfill');
const tbuf  = document.getElementById('tbuf');
const thb   = document.getElementById('thb');
const trk   = document.getElementById('trk');
const ct    = document.getElementById('ct');
const dt    = document.getElementById('dt');
const toast = document.getElementById('toast');
const psm   = document.getElementById('psm');
const PL='M8 5v14l11-7z', PA='M6 19h4V5H6v14zm8-14v14h4V5h-4z';

/* HLS */
(function(){
  ld.classList.add('on');
  if(Hls.isSupported()){
    const h=new Hls({enableWorker:true});
    h.loadSource(SRC);h.attachMedia(vid);
    h.on(Hls.Events.MANIFEST_PARSED,()=>{ld.classList.remove('on');vid.play().catch(()=>{});});
    h.on(Hls.Events.ERROR,(_,d)=>{if(d.fatal){ld.classList.remove('on');showT('Video yüklenemedi');}});
  }else if(vid.canPlayType('application/vnd.apple.mpegurl')){
    vid.src=SRC;
    vid.addEventListener('loadedmetadata',()=>{ld.classList.remove('on');vid.play().catch(()=>{});});
  }
})();

vid.addEventListener('waiting',()=>ld.classList.add('on'));
vid.addEventListener('canplay',()=>ld.classList.remove('on'));

vid.addEventListener('play',()=>{
  player.classList.add('playing');player.classList.remove('paused');
  psm.querySelector('path').setAttribute('d',PA);
});
vid.addEventListener('pause',()=>{
  player.classList.remove('playing');player.classList.add('paused');
  psm.querySelector('path').setAttribute('d',PL);
});

function togglePlay(){vid.paused?vid.play():vid.pause();}

/* progress */
function fmt(s){if(!isFinite(s))return'0:00';const m=Math.floor(s/60),sc=Math.floor(s%60);return m+':'+(sc<10?'0':'')+sc;}
vid.addEventListener('timeupdate',()=>{
  if(!vid.duration)return;
  const p=(vid.currentTime/vid.duration)*100;
  tfill.style.width=p+'%';thb.style.left=p+'%';
  ct.textContent=fmt(vid.currentTime);dt.textContent=fmt(vid.duration);
});
vid.addEventListener('progress',()=>{
  if(vid.buffered.length&&vid.duration)
    tbuf.style.width=(vid.buffered.end(vid.buffered.length-1)/vid.duration*100)+'%';
});

function seekPct(e){const r=trk.getBoundingClientRect();vid.currentTime=Math.max(0,Math.min(1,(e.clientX-r.left)/r.width))*vid.duration;}
trk.addEventListener('click',seekPct);
let sc2=false;
trk.addEventListener('mousedown',e=>{sc2=true;seekPct(e);});
document.addEventListener('mousemove',e=>{if(sc2)seekPct(e);});
document.addEventListener('mouseup',()=>sc2=false);
trk.addEventListener('touchstart',e=>{const r=trk.getBoundingClientRect();vid.currentTime=Math.max(0,Math.min(1,(e.touches[0].clientX-r.left)/r.width))*vid.duration;},{passive:true});
trk.addEventListener('touchmove',e=>{const r=trk.getBoundingClientRect();vid.currentTime=Math.max(0,Math.min(1,(e.touches[0].clientX-r.left)/r.width))*vid.duration;},{passive:true});

function seek(d){vid.currentTime=Math.max(0,Math.min(vid.duration||0,vid.currentTime+d));showT(d>0?`+${d}s ileri`:`${Math.abs(d)}s geri`);}

/* volume */
function setVol(v){vid.volume=v;vid.muted=(v==0);updateVI();}
function toggleMute(){vid.muted=!vid.muted;document.getElementById('vsl').value=vid.muted?0:vid.volume;updateVI();}
function updateVI(){
  const p=document.getElementById('vi').querySelector('path');
  p.setAttribute('d',vid.muted||vid.volume===0
    ?'M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z'
    :'M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z');
}

/* fullscreen */
function toggleFS(){!document.fullscreenElement?player.requestFullscreen&&player.requestFullscreen():document.exitFullscreen&&document.exitFullscreen();}
document.addEventListener('fullscreenchange',()=>{
  const p=document.getElementById('fsi').querySelector('path');
  p.setAttribute('d',document.fullscreenElement
    ?'M5 16h3v3h2v-5H5v2zm3-8H5v2h5V5H8v3zm6 11h2v-3h3v-2h-5v5zm2-11V5h-2v5h5V8h-3z'
    :'M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z');
});

/* ui auto-hide */
let uiT;
function showUI(){
  player.classList.add('ui');clearTimeout(uiT);
  if(!vid.paused) uiT=setTimeout(()=>{if(!vid.paused)player.classList.remove('ui');},3000);
}
player.addEventListener('mousemove',showUI);
player.addEventListener('touchstart',showUI,{passive:true});

function handleClick(e){
  if(e.target===vid||e.target===player){
    if(!player.classList.contains('ui'))showUI();
    else togglePlay();
  }
}
function sp(e){e.stopPropagation();}

/* panel */
function openPanel(){document.getElementById('panel').classList.add('open');document.getElementById('bk').classList.add('open');player.classList.add('ui');}
function closePanel(){document.getElementById('panel').classList.remove('open');document.getElementById('bk').classList.remove('open');}

/* toast */
let tT;
function showT(m){toast.textContent=m;toast.classList.add('on');clearTimeout(tT);tT=setTimeout(()=>toast.classList.remove('on'),1800);}

/* keyboard */
document.addEventListener('keydown',e=>{
  if(['INPUT','TEXTAREA'].includes(document.activeElement.tagName))return;
  if(e.code==='Space'){e.preventDefault();togglePlay();}
  if(e.code==='ArrowRight')seek(10);
  if(e.code==='ArrowLeft')seek(-10);
  if(e.code==='ArrowUp'){setVol(Math.min(1,vid.volume+0.1));document.getElementById('vsl').value=vid.volume;}
  if(e.code==='ArrowDown'){setVol(Math.max(0,vid.volume-0.1));document.getElementById('vsl').value=vid.volume;}
  if(e.code==='KeyF')toggleFS();
  if(e.code==='KeyM')toggleMute();
  if(e.code==='KeyE')openPanel();
  if(e.code==='Escape')closePanel();
});

showUI();
</script>
</body>
</html>
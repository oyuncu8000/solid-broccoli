<?php
require_once __DIR__ . '/token_check.php';

$result = null;
$reason = $_GET['reason'] ?? '';
$msg    = $_GET['msg']    ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_token'])) {
    $result = wox_issue();
}

$reason_labels = [
    'no_token'       => '🔑 Token Gerekli',
    'expired'        => '⏰ Token Süresi Doldu',
    'invalid'        => '❌ Geçersiz Token',
    'device_mismatch'=> '🔒 Farklı Cihaz Tespit Edildi',
];
$reason_label = $reason_labels[$reason] ?? '🔑 Erişim Gerekli';

// Tam base URL
$scheme   = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir      = rtrim(dirname($_SERVER['PHP_SELF']), '/');
$base_url = $scheme . '://' . $host . $dir . '/';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>WOXPLUS — Token Al</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Syne:wght@400;600;700;800&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{
  --bg:#07090b;--bg2:#0d1014;--bg3:#111520;
  --txt:#f0f2f5;--txt2:#7a8490;--txt3:#3a4050;
  --silver:#b8bdc8;--gold:#e8c87a;--gold2:#c8a850;
  --border:rgba(255,255,255,0.07);--border2:rgba(255,255,255,0.14);
  --green:#6ee7b7;--red:#f08080;
  --ease:cubic-bezier(.4,0,.2,1);
}
html{height:100%;}
body{
  background:var(--bg);color:var(--txt);
  font-family:'Syne',system-ui,sans-serif;
  min-height:100vh;display:flex;flex-direction:column;
  align-items:center;justify-content:center;
  overflow-x:hidden;padding:20px;position:relative;
}
body::before{
  content:'';position:fixed;inset:0;z-index:0;pointer-events:none;
  background:
    radial-gradient(ellipse 70% 50% at 50% 0%,rgba(232,200,122,.06) 0%,transparent 60%),
    radial-gradient(ellipse 50% 40% at 0% 100%,rgba(255,255,255,.02) 0%,transparent 55%);
}
.grid{
  position:fixed;inset:0;z-index:0;pointer-events:none;opacity:.022;
  background-image:linear-gradient(rgba(255,255,255,.5) 1px,transparent 1px),
    linear-gradient(90deg,rgba(255,255,255,.5) 1px,transparent 1px);
  background-size:60px 60px;
}
@keyframes floatUp{from{opacity:0;transform:translateY(28px);}to{opacity:1;transform:translateY(0);}}
@keyframes scanline{0%{transform:translateY(-100%);}100%{transform:translateY(100vh);}}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:.35;}}
@keyframes glow{0%,100%{box-shadow:0 0 20px rgba(232,200,122,.1);}50%{box-shadow:0 0 42px rgba(232,200,122,.28);}}
@keyframes shine{0%{left:-100%;}100%{left:200%;}}

.scanline{
  position:fixed;top:0;left:0;right:0;height:2px;
  background:linear-gradient(90deg,transparent,rgba(232,200,122,.15),transparent);
  z-index:1;pointer-events:none;animation:scanline 4s linear infinite;
}
.wrap{position:relative;z-index:2;width:100%;max-width:460px;animation:floatUp .55s var(--ease) both;}

/* Logo */
.logo-area{text-align:center;margin-bottom:28px;}
.logo-img{height:36px;width:auto;object-fit:contain;}
.logo-sub{font-family:'DM Mono',monospace;font-size:.57rem;letter-spacing:.22em;color:var(--txt3);text-transform:uppercase;margin-top:7px;}

/* Kart */
.card{background:var(--bg2);border:1px solid var(--border);border-radius:20px;overflow:hidden;box-shadow:0 32px 80px rgba(0,0,0,.65);}

/* Kart üst */
.card-top{
  padding:26px 28px 20px;border-bottom:1px solid var(--border);
  background:linear-gradient(135deg,rgba(232,200,122,.04) 0%,transparent 60%);
  position:relative;overflow:hidden;
}
.card-top::before{content:'';position:absolute;top:-1px;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,var(--gold),transparent);}
.reason-badge{
  display:inline-flex;align-items:center;gap:6px;
  background:rgba(232,200,122,.08);border:1px solid rgba(232,200,122,.2);
  color:var(--gold);padding:3px 11px;border-radius:20px;
  font-size:.6rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;
  font-family:'DM Mono',monospace;margin-bottom:13px;
}
.card-title{
  font-family:'Bebas Neue',sans-serif;font-size:2rem;letter-spacing:.04em;
  background:linear-gradient(135deg,#fff 0%,#b8bdc8 60%,#7a8490 100%);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
  line-height:1.1;margin-bottom:7px;
}
.card-desc{font-size:.77rem;color:var(--txt2);line-height:1.65;}

/* Kart gövde */
.card-body{padding:22px 28px;}

/* Uyarı */
.alert{display:flex;align-items:center;gap:9px;padding:11px 15px;border-radius:10px;margin-bottom:16px;font-size:.74rem;font-family:'DM Mono',monospace;}
.alert-warn{background:rgba(240,128,128,.07);border:1px solid rgba(240,128,128,.2);color:var(--red);}
.alert-ok{background:rgba(110,231,183,.07);border:1px solid rgba(110,231,183,.2);color:var(--green);}

/* Sonuç kutusu */
.result-box{
  background:linear-gradient(135deg,rgba(232,200,122,.06),rgba(200,168,80,.03));
  border:1px solid rgba(232,200,122,.28);border-radius:14px;
  padding:20px 18px;margin-bottom:18px;
  animation:glow 3s ease-in-out infinite;
}
.result-label{
  font-size:.6rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;
  color:var(--gold);font-family:'DM Mono',monospace;margin-bottom:12px;
  display:flex;align-items:center;gap:7px;
}
.result-label::before{content:'';width:6px;height:6px;border-radius:50%;background:var(--gold);animation:pulse 1.5s infinite;}

/* Link kutusu */
.link-box{
  background:var(--bg3);border:1px solid var(--border2);border-radius:10px;
  padding:13px 14px;display:flex;align-items:center;gap:10px;
  margin-bottom:12px;cursor:pointer;transition:.2s;
}
.link-box:hover{border-color:rgba(232,200,122,.4);background:rgba(232,200,122,.04);}
.link-text{
  font-family:'DM Mono',monospace;font-size:.7rem;color:var(--txt2);
  flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;letter-spacing:.03em;
}
.link-text .link-host{color:var(--txt3);}
.link-text .link-token{color:var(--gold);font-weight:500;}
.link-copy-ico{flex-shrink:0;color:var(--txt3);transition:.2s;}
.link-box:hover .link-copy-ico{color:var(--gold);}

/* Kopyala butonu */
.btn-copy{
  width:100%;padding:11px;
  background:rgba(232,200,122,.1);border:1px solid rgba(232,200,122,.3);
  color:var(--gold);font-family:'DM Mono',monospace;
  font-size:.72rem;font-weight:700;letter-spacing:.08em;
  border-radius:9px;cursor:pointer;transition:.2s;
  display:flex;align-items:center;justify-content:center;gap:8px;
  margin-bottom:12px;
}
.btn-copy:hover{background:rgba(232,200,122,.18);border-color:rgba(232,200,122,.5);}
.btn-copy.copied{background:rgba(110,231,183,.12);border-color:rgba(110,231,183,.35);color:var(--green);}

/* Token ile Giriş Yap butonu */
.btn-login{
  width:100%;padding:11px;
  background:linear-gradient(135deg,var(--gold) 0%,var(--gold2) 100%);
  border:none;
  color:#0d0a00;font-family:'Syne',sans-serif;
  font-size:.78rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;
  border-radius:9px;cursor:pointer;transition:.25s;
  display:flex;align-items:center;justify-content:center;gap:8px;
  margin-bottom:12px;position:relative;overflow:hidden;
}
.btn-login::after{
  content:'';position:absolute;top:0;left:-100%;width:60%;height:100%;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,.28),transparent);
  animation:shine 2.5s infinite;
}
.btn-login:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(232,200,122,.35);}
.btn-login:active{transform:none;}

/* Meta */
.result-meta{display:flex;gap:14px;flex-wrap:wrap;}
.result-meta-item{font-size:.61rem;font-family:'DM Mono',monospace;color:var(--txt2);display:flex;align-items:center;gap:4px;}
.result-meta-item span{color:var(--silver);}

/* Talimat */
.instruction{
  background:rgba(110,231,183,.05);border:1px solid rgba(110,231,183,.15);
  border-radius:10px;padding:14px 16px;margin-bottom:18px;
}
.instruction-title{font-size:.62rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--green);font-family:'DM Mono',monospace;margin-bottom:10px;}
.instruction ol{padding-left:18px;}
.instruction li{font-size:.74rem;color:var(--txt2);line-height:1.9;font-family:'DM Mono',monospace;}
.instruction li strong{color:var(--silver);}

/* Bilgi grid */
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:9px;margin-bottom:18px;}
.info-item{background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:13px 15px;}
.info-icon{font-size:1rem;margin-bottom:5px;}
.info-label{font-size:.57rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--txt3);font-family:'DM Mono',monospace;margin-bottom:3px;}
.info-val{font-size:.8rem;font-weight:700;color:var(--silver);}

/* Ana buton */
.btn-main{
  width:100%;padding:13px 20px;
  background:linear-gradient(135deg,var(--gold) 0%,var(--gold2) 100%);
  color:#0d0a00;border:none;border-radius:10px;
  font-family:'Syne',sans-serif;font-size:.8rem;font-weight:800;
  letter-spacing:.06em;text-transform:uppercase;cursor:pointer;
  transition:.25s;position:relative;overflow:hidden;
}
.btn-main::after{
  content:'';position:absolute;top:0;left:-100%;width:60%;height:100%;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,.28),transparent);
  animation:shine 2.5s infinite;
}
.btn-main:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(232,200,122,.35);}
.btn-main:active{transform:none;}

/* Footer */
.footer{text-align:center;margin-top:20px;font-size:.59rem;color:var(--txt3);font-family:'DM Mono',monospace;letter-spacing:.06em;line-height:1.8;}

@media(max-width:480px){
  .info-grid{grid-template-columns:1fr;}
  .card-top,.card-body{padding:18px 16px;}
  .link-text{font-size:.62rem;}
}
</style>
</head>
<body>
<div class="grid"></div>
<div class="scanline"></div>

<div class="wrap">

  <div class="logo-area">
    <img class="logo-img" src="https://yourfiles.cloud/uploads/d4380759d9fbe11bdf5c47e65f91921c/%2B-removebg-preview.png" alt="WOXPLUS">
    <div class="logo-sub">Erişim Sistemi</div>
  </div>

  <div class="card">

    <div class="card-top">
      <?php if ($reason): ?>
      <div class="reason-badge"><?= htmlspecialchars($reason_label) ?></div>
      <?php endif; ?>
      <div class="card-title">Erişim Tokenı</div>
      <div class="card-desc">
        WOXPLUS'a erişmek için cihazınıza özel token almanız gerekmektedir.
        Token <strong style="color:var(--silver)"><?= TOKEN_DAYS ?> gün</strong> geçerlidir,
        <strong style="color:var(--silver)">her cihaz için ayrı</strong> oluşturulur.
      </div>
    </div>

    <div class="card-body">

      <?php if ($msg && !$result): ?>
      <div class="alert alert-warn">⚠ <?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <?php if ($result): ?>
        <?php $full_link = $base_url . $result['link']; ?>

        <div class="result-box">
          <div class="result-label">
            <?= $result['is_new'] ? '✦ Tokenınız Oluşturuldu' : '✦ Mevcut Tokenınız' ?>
          </div>

          <!-- Tıklanabilir link kutusu -->
          <div class="link-box" onclick="copyLink()" id="linkBox" title="Kopyalamak için tıkla">
            <div class="link-text">
              <span class="link-host"><?= htmlspecialchars($base_url) ?></span><span class="link-token">darkwinds.php?<?= htmlspecialchars($result['token']) ?></span>
            </div>
            <svg class="link-copy-ico" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
          </div>

          <button class="btn-copy" onclick="copyLink()" id="copyBtn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
            Linki Kopyala
          </button>

          <button class="btn-login" onclick="openLink()">
            🔑 Token ile Giriş Yap
          </button>

          <div class="result-meta">
            <div class="result-meta-item">📅 Bitiş: <span><?= htmlspecialchars($result['expires']) ?></span></div>
            <div class="result-meta-item">⏳ Kalan: <span><?= htmlspecialchars($result['remaining']) ?> gün</span></div>
            <div class="result-meta-item">📱 <span>Bu cihaza özel</span></div>
          </div>
        </div>

        <div class="instruction">
          <div class="instruction-title">📌 Nasıl kullanılır?</div>
          <ol>
            <li>Yukarıdaki linki <strong>kopyala</strong></li>
            <li>Tarayıcına yapıştır ve <strong>aç</strong></li>
            <li>Sayfa açılır, token <strong>otomatik kaydedilir</strong></li>
            <li>Sonraki girişlerde <strong>link gerekmez</strong> — direkt açılır</li>
            <li>Token <strong><?= TOKEN_DAYS ?> gün</strong> sonra yenilenmesi gerekir</li>
          </ol>
        </div>

        <div class="alert alert-ok">✓ Link hazır. Kopyalayıp tarayıcınıza yapıştırın.</div>

      <?php else: ?>

        <div class="info-grid">
          <div class="info-item">
            <div class="info-icon">📱</div>
            <div class="info-label">Cihaz Başına</div>
            <div class="info-val">1 Token</div>
          </div>
          <div class="info-item">
            <div class="info-icon">🔄</div>
            <div class="info-label">Geçerlilik</div>
            <div class="info-val"><?= TOKEN_DAYS ?> Gün</div>
          </div>
          <div class="info-item">
            <div class="info-icon">🔐</div>
            <div class="info-label">Güvenlik</div>
            <div class="info-val">Cihaza Kilitli</div>
          </div>
          <div class="info-item">
            <div class="info-icon">⚡</div>
            <div class="info-label">Aktivasyon</div>
            <div class="info-val">Anlık</div>
          </div>
        </div>

        <form method="POST" action="">
          <input type="hidden" name="get_token" value="1">
          <button type="submit" class="btn-main">🔑 Token Al</button>
        </form>

      <?php endif; ?>

    </div>
  </div>

  <div class="footer">
    Token bilgileri cihazınıza özgüdür. Farklı cihazda yeni token alınmalıdır.<br>
    © <?= date('Y') ?> WOXPLUS — Tüm hakları saklıdır.
  </div>

</div>

<script>
const FULL_LINK = <?= $result ? json_encode($base_url . $result['link']) : 'null' ?>;

function copyLink() {
  if (!FULL_LINK) return;
  const btn = document.getElementById('copyBtn');
  const box = document.getElementById('linkBox');

  navigator.clipboard?.writeText(FULL_LINK).then(done).catch(() => {
    const inp = document.createElement('input');
    inp.value = FULL_LINK;
    document.body.appendChild(inp);
    inp.select();
    document.execCommand('copy');
    document.body.removeChild(inp);
    done();
  });

  function done() {
    btn.classList.add('copied');
    btn.innerHTML = '✓ Kopyalandı!';
    if (box) box.style.borderColor = 'rgba(110,231,183,.4)';
    setTimeout(() => {
      btn.classList.remove('copied');
      btn.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg> Linki Kopyala`;
      if (box) box.style.borderColor = '';
    }, 2500);
  }
}

function openLink() {
  if (!FULL_LINK) return;
  window.location.href = FULL_LINK;
}
</script>

</body>
</html>
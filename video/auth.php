<?php
// ═══════════════════════════════════════════════════════════════════
//  WOXPLUS — auth.php  (Kayıt / Giriş / Paket Seçimi)
//  Veritabanı: SQLite (config.php üzerinden)
// ═══════════════════════════════════════════════════════════════════
require_once __DIR__ . '/config.php';
session_start();

// ─── YARDIMCILAR ─────────────────────────────────────────────────
function genUserId(): string {
    $c='ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $id='WX-';
    for($i=0;$i<8;$i++) $id.=$c[random_int(0,strlen($c)-1)];
    return $id;
}
function genToken(): string   { return 'wxt_'.bin2hex(random_bytes(16)); }
function genSession(): string { return bin2hex(random_bytes(32)); }
function jsonOut(array $d): void { header('Content-Type: application/json'); echo json_encode($d); exit; }
function esc(string $s): string  { return htmlspecialchars($s,ENT_QUOTES,'UTF-8'); }

function currentUser(): ?array {
    if (empty($_SESSION['sk'])) return null;
    $st = db()->prepare("SELECT s.user_id_fk, u.* FROM sessions s JOIN users u ON s.user_id_fk=u.user_id WHERE s.session_key=? AND s.expires_at>datetime('now') LIMIT 1");
    $st->execute([$_SESSION['sk']]);
    return $st->fetch() ?: null;
}
function planValid(?array $u): bool {
    if (!$u || $u['plan']==='none') return false;
    if ($u['plan']==='premium') return true;
    return $u['plan']==='free' && !empty($u['plan_expiry']) && strtotime($u['plan_expiry']) > time();
}

// ─── LOGOUT GET ───────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    if (!empty($_SESSION['sk'])) db()->prepare("DELETE FROM sessions WHERE session_key=?")->execute([$_SESSION['sk']]);
    session_destroy();
    header('Location: auth.php'); exit;
}

// ─── AJAX POST ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $act = trim($_POST['action'] ?? '');

    // KAYIT
    if ($act==='register') {
        $name  = trim($_POST['name']  ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass  = $_POST['pass'] ?? '';
        if (!$name||!$email||!$pass)               jsonOut(['ok'=>false,'msg'=>'Tüm alanları doldurun.']);
        if (!filter_var($email,FILTER_VALIDATE_EMAIL)) jsonOut(['ok'=>false,'msg'=>'Geçerli e-posta girin.']);
        if (strlen($pass)<6)                        jsonOut(['ok'=>false,'msg'=>'Şifre en az 6 karakter.']);

        $st=db()->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $st->execute([$email]);
        if ($st->fetch()) jsonOut(['ok'=>false,'msg'=>'Bu e-posta zaten kayıtlı.']);

        do {
            $uid=genUserId();
            $ch=db()->prepare("SELECT id FROM users WHERE user_id=? LIMIT 1");
            $ch->execute([$uid]);
        } while ($ch->fetch());

        $token = genToken();
        $hash  = password_hash($pass, PASSWORD_BCRYPT);
        db()->prepare("INSERT INTO users (user_id,token,name,email,pass_hash) VALUES (?,?,?,?,?)")
            ->execute([$uid,$token,$name,$email,$hash]);

        $sk  = genSession();
        $exp = date('Y-m-d H:i:s', strtotime('+'.SESSION_LIFE.' days'));
        db()->prepare("INSERT INTO sessions (user_id_fk,session_key,ip,user_agent,expires_at) VALUES (?,?,?,?,?)")
            ->execute([$uid,$sk,$_SERVER['REMOTE_ADDR']??'',substr($_SERVER['HTTP_USER_AGENT']??'',0,255),$exp]);
        $_SESSION['sk'] = $sk;

        jsonOut(['ok'=>true,'user_id'=>$uid,'token'=>$token,'name'=>$name]);
    }

    // GİRİŞ
    if ($act==='login') {
        $email = strtolower(trim($_POST['email']??''));
        $pass  = $_POST['pass']??'';
        if (!$email||!$pass) jsonOut(['ok'=>false,'msg'=>'E-posta ve şifre giriniz.']);

        $st=db()->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $st->execute([$email]);
        $u=$st->fetch();
        if (!$u||!password_verify($pass,$u['pass_hash'])) jsonOut(['ok'=>false,'msg'=>'E-posta veya şifre hatalı.']);

        $sk  = genSession();
        $exp = date('Y-m-d H:i:s', strtotime('+'.SESSION_LIFE.' days'));
        db()->prepare("INSERT INTO sessions (user_id_fk,session_key,ip,user_agent,expires_at) VALUES (?,?,?,?,?)")
            ->execute([$u['user_id'],$sk,$_SERVER['REMOTE_ADDR']??'',substr($_SERVER['HTTP_USER_AGENT']??'',0,255),$exp]);
        db()->prepare("UPDATE users SET last_login=datetime('now') WHERE user_id=?")->execute([$u['user_id']]);
        $_SESSION['sk'] = $sk;

        jsonOut(['ok'=>true,'need_plan'=>!planValid($u),'name'=>$u['name'],'user_id'=>$u['user_id'],'token'=>$u['token']]);
    }

    // PAKET ETKİNLEŞTİR
    if ($act==='activate_plan') {
        $u = currentUser();
        if (!$u) jsonOut(['ok'=>false,'msg'=>'Oturum bulunamadı.']);
        if ($_POST['plan']==='free') {
            if ($u['used_free']) jsonOut(['ok'=>false,'msg'=>'Ücretsiz paketi daha önce kullandınız.']);
            $exp = date('Y-m-d H:i:s', strtotime('+1 year'));
            db()->prepare("UPDATE users SET plan='free',plan_expiry=?,used_free=1 WHERE user_id=?")->execute([$exp,$u['user_id']]);
            jsonOut(['ok'=>true,'msg'=>'Reklamlı paket aktif!','expiry'=>$exp]);
        }
        jsonOut(['ok'=>false,'msg'=>'Bilinmeyen paket.']);
    }

    // ÇIKIŞ
    if ($act==='logout') {
        if (!empty($_SESSION['sk'])) db()->prepare("DELETE FROM sessions WHERE session_key=?")->execute([$_SESSION['sk']]);
        session_destroy();
        jsonOut(['ok'=>true]);
    }

    jsonOut(['ok'=>false,'msg'=>'Bilinmeyen işlem.']);
}

// ─── SAYFA DURUMU ────────────────────────────────────────────────
$user        = currentUser();
$validPlan   = planValid($user);
if ($user && $validPlan) { header('Location: index.php'); exit; }
$showPayment = ($user && !$validPlan);
$usedFree    = $user ? (bool)$user['used_free'] : false;
$planExpiry  = ($user && $user['plan_expiry']) ? date('d.m.Y',strtotime($user['plan_expiry'])) : '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>WOXPLUS – Giriş & Üyelik</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{--ac:#26ccc2;--ac-dim:rgba(38,204,194,.13);--ac-b:rgba(38,204,194,.28);--bg:#0a0c0f;--card:#13161c;--txt:#eef0f3;--txt2:#8a9099;--txt3:#484d58;--border:rgba(255,255,255,.065);--r:10px;--ease:cubic-bezier(.4,0,.2,1);}
body{background:var(--bg);color:var(--txt);font-family:'DM Sans',sans-serif;min-height:100vh;-webkit-font-smoothing:antialiased;}
.mesh{position:fixed;inset:0;pointer-events:none;z-index:0;background:radial-gradient(ellipse 80% 60% at 20% 50%,rgba(38,204,194,.07),transparent 60%),radial-gradient(ellipse 60% 80% at 80% 30%,rgba(38,204,194,.04),transparent 50%);}
.grid{position:fixed;inset:0;pointer-events:none;z-index:0;background-image:repeating-linear-gradient(0deg,transparent,transparent 60px,rgba(255,255,255,.011) 60px,rgba(255,255,255,.011) 61px),repeating-linear-gradient(90deg,transparent,transparent 60px,rgba(255,255,255,.007) 60px,rgba(255,255,255,.007) 61px);}
.page{position:relative;z-index:1;min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:32px 20px 80px;}
.logo-w{text-align:center;margin-bottom:36px;animation:fsu .5s var(--ease) both;}
.logo-w a{font-family:'DM Serif Display',serif;font-size:2.8rem;font-style:italic;color:var(--ac);text-decoration:none;text-shadow:0 0 40px rgba(38,204,194,.4);}
.logo-w p{color:var(--txt3);font-size:.75rem;margin-top:6px;letter-spacing:.1em;text-transform:uppercase;font-weight:600;}
@keyframes fsu{from{opacity:0;transform:translateY(28px);}to{opacity:1;transform:none;}}
@keyframes fi{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:none;}}
@keyframes pop{from{transform:scale(0);}to{transform:scale(1);}}
.w{width:100%;max-width:500px;animation:fi .35s var(--ease) both;}
.card{background:linear-gradient(145deg,rgba(19,22,28,.97),rgba(13,16,20,.99));border:1px solid var(--border);border-radius:20px;padding:36px 32px;box-shadow:0 24px 80px rgba(0,0,0,.7),inset 0 1px 0 rgba(255,255,255,.06);}
.card h2{font-family:'DM Serif Display',serif;font-size:1.6rem;margin-bottom:6px;}
.sub{color:var(--txt2);font-size:.82rem;margin-bottom:28px;line-height:1.6;}
.tabs{display:flex;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:12px;padding:4px;margin-bottom:28px;}
.tab{flex:1;padding:10px;border:none;background:transparent;color:var(--txt2);font-family:inherit;font-size:.84rem;font-weight:600;border-radius:9px;cursor:pointer;transition:all .25s;}
.tab.on{background:linear-gradient(135deg,rgba(38,204,194,.18),rgba(38,204,194,.08));color:var(--ac);border:1px solid var(--ac-b);}
.fg{margin-bottom:18px;}
.fg label{display:block;font-size:.73rem;font-weight:700;color:var(--txt2);letter-spacing:.06em;text-transform:uppercase;margin-bottom:8px;}
.fg input{width:100%;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);color:var(--txt);font-family:inherit;font-size:.92rem;padding:13px 16px;border-radius:var(--r);outline:none;transition:.2s;}
.fg input::placeholder{color:var(--txt3);}
.fg input:focus{border-color:var(--ac-b);background:rgba(38,204,194,.04);box-shadow:0 0 0 3px rgba(38,204,194,.1);}
.btn{width:100%;padding:14px;background:linear-gradient(135deg,var(--ac),#1fa89f);color:#0a0c0f;border:none;border-radius:var(--r);font-family:inherit;font-size:.92rem;font-weight:800;cursor:pointer;transition:all .2s;box-shadow:0 6px 24px rgba(38,204,194,.3);margin-top:6px;}
.btn:hover{transform:translateY(-1px);box-shadow:0 10px 32px rgba(38,204,194,.4);}
.btn:disabled{opacity:.4;cursor:not-allowed;transform:none;}
.msg{padding:10px 14px;border-radius:8px;font-size:.8rem;font-weight:600;margin-bottom:18px;display:none;}
.msg.err{background:rgba(255,80,80,.1);border:1px solid rgba(255,80,80,.3);color:#ff6b6b;}
.msg.ok{background:var(--ac-dim);border:1px solid var(--ac-b);color:var(--ac);}
.terms{text-align:center;font-size:.7rem;color:var(--txt3);margin-top:20px;line-height:1.6;}
.terms a{color:var(--ac);text-decoration:none;}
/* token box */
.tbox{background:rgba(38,204,194,.07);border:1px solid var(--ac-b);border-radius:14px;padding:22px;margin:20px 0;}
.tbox h3{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--ac);margin-bottom:14px;}
.tr{display:flex;align-items:center;gap:10px;margin-bottom:10px;}
.tl{font-size:.72rem;color:var(--txt3);width:60px;flex-shrink:0;}
.tv{flex:1;background:rgba(0,0,0,.3);border:1px solid var(--border);border-radius:8px;padding:9px 14px;font-size:.82rem;font-family:monospace;color:var(--ac);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.cp{padding:8px 12px;background:rgba(38,204,194,.1);border:1px solid var(--ac-b);color:var(--ac);border-radius:7px;font-size:.7rem;font-weight:700;cursor:pointer;white-space:nowrap;transition:.2s;}
.cp:hover{background:rgba(38,204,194,.22);}
.tnote{font-size:.75rem;color:var(--txt3);line-height:1.6;background:rgba(255,255,255,.03);border-radius:8px;padding:12px 14px;margin-top:6px;}
/* ödeme */
.pay-w{width:100%;max-width:680px;animation:fi .35s var(--ease) both;}
.phead{text-align:center;margin-bottom:32px;}
.phead h2{font-family:'DM Serif Display',serif;font-size:2rem;margin-bottom:10px;}
.phead p{color:var(--txt2);font-size:.88rem;line-height:1.6;}
.plans{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:28px;}
.plan{border:2px solid var(--border);border-radius:18px;padding:26px 22px;text-align:center;cursor:pointer;transition:all .28s;position:relative;background:rgba(19,22,28,.7);}
.plan:hover{border-color:rgba(38,204,194,.4);background:var(--ac-dim);transform:translateY(-3px);}
.plan.sel{border-color:var(--ac);background:var(--ac-dim);box-shadow:0 0 0 1px rgba(38,204,194,.2),0 8px 32px rgba(38,204,194,.2);}
.plan.dis{opacity:.5;cursor:not-allowed;pointer-events:none;}
.pbadge{position:absolute;top:-13px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,var(--ac),#1fa89f);color:#0a0c0f;padding:4px 14px;border-radius:20px;font-size:.66rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;white-space:nowrap;}
.pbadge.cm{background:linear-gradient(135deg,#444,#333);color:var(--txt2);}
.plan-icon{font-size:2.2rem;margin-bottom:10px;}
.plan-name{font-weight:800;font-size:1.05rem;margin-bottom:6px;}
.plan-price{font-family:'DM Serif Display',serif;font-size:2rem;color:var(--ac);line-height:1;}
.plan-price span{font-family:'DM Sans',sans-serif;font-size:.75rem;color:var(--txt2);}
.plan-desc{font-size:.75rem;color:var(--txt2);line-height:1.5;margin-top:8px;}
.plan-feats{list-style:none;margin-top:14px;text-align:left;}
.plan-feats li{font-size:.76rem;color:var(--txt2);padding:4px 0;display:flex;align-items:center;gap:7px;}
.plan-feats li::before{content:'✓';color:var(--ac);font-weight:700;}
.plan-feats li.bad::before{content:'✕';color:#ff6b6b;}
.pay-btn{display:block;width:100%;max-width:360px;margin:0 auto;padding:16px;background:linear-gradient(135deg,var(--ac),#1fa89f);color:#0a0c0f;border:none;border-radius:var(--r);font-family:inherit;font-size:1rem;font-weight:800;cursor:pointer;transition:all .2s;box-shadow:0 8px 28px rgba(38,204,194,.35);}
.pay-btn:hover{transform:translateY(-2px);}
.pay-note{text-align:center;font-size:.72rem;color:var(--txt3);margin-top:14px;line-height:1.6;}
.steps{display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:30px;}
.sdot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.12);transition:all .3s;}
.sdot.a{background:var(--ac);width:24px;border-radius:4px;box-shadow:0 0 8px rgba(38,204,194,.5);}
.suc-icon{font-size:3.5rem;display:block;text-align:center;margin-bottom:16px;animation:pop .4s var(--ease);}
/* modal */
#tok-modal{position:fixed;inset:0;z-index:8000;background:rgba(0,0,0,.82);display:none;align-items:center;justify-content:center;padding:20px;}
#tok-modal .inner{background:linear-gradient(145deg,rgba(19,22,28,.99),rgba(13,16,20,1));border:1px solid var(--border);border-radius:20px;padding:36px 32px;max-width:500px;width:100%;box-shadow:0 24px 80px rgba(0,0,0,.8);animation:fi .3s var(--ease);}
/* toast */
.toast{position:fixed;z-index:9999;bottom:24px;right:20px;left:20px;max-width:320px;margin:0 auto;background:var(--card);border:1px solid var(--border);color:var(--txt);padding:12px 16px;border-radius:10px;font-size:.82rem;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,.5);border-left:3px solid var(--ac);transform:translateY(90px) scale(.96);opacity:0;transition:all .3s var(--ease);pointer-events:none;}
.toast.show{transform:none;opacity:1;}
@media(max-width:480px){.card{padding:24px 18px;}.plans{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="mesh"></div>
<div class="grid"></div>
<div class="page">
  <div class="logo-w">
    <a href="auth.php">WOXPLUS</a>
    <p>Film &amp; Dizi Platformu</p>
  </div>

<?php if ($showPayment): ?>
<!-- ══ PAKET SEÇİMİ ══ -->
<div class="pay-w">
  <div class="steps"><div class="sdot"></div><div class="sdot"></div><div class="sdot a"></div></div>
  <div class="card" style="max-width:680px;width:100%">
    <div class="phead">
      <h2>Paket Seç</h2>
      <p>Merhaba <strong><?=esc($user['name'])?></strong>! İçeriklere erişmek için bir paket seç.</p>
    </div>
    <?php if ($user['plan']!=='none' && !$validPlan): ?>
    <div style="background:rgba(255,80,80,.07);border:1px solid rgba(255,80,80,.22);border-radius:14px;padding:16px 20px;margin-bottom:28px;display:flex;align-items:center;gap:12px;font-size:.82rem;color:#ff8080;">
      <span style="font-size:1.4rem">🔒</span><span>Aboneliğin sona erdi. Yeni paket seç.</span>
    </div>
    <?php endif; ?>
    <div class="msg" id="pay-msg"></div>
    <div class="plans">
      <div class="plan <?=$usedFree?'dis':''?>" id="plan-free" onclick="selPlan('free')">
        <div class="pbadge">🎁 1. Yıl Bedava</div>
        <div class="plan-icon">📺</div>
        <div class="plan-name">Reklamlı</div>
        <div class="plan-price">₺0 <span>/ yıl</span></div>
        <div class="plan-desc"><?=$usedFree?'Bu paketi daha önce kullandınız.':'İlk yıl tamamen ücretsiz!'?></div>
        <ul class="plan-feats">
          <li>HD içerik erişimi</li><li>Tüm dizi &amp; filmler</li><li>Çoklu cihaz</li>
          <li class="bad">Reklamlar mevcut</li><li class="bad">Sadece 1 kez</li>
        </ul>
      </div>
      <div class="plan dis">
        <div class="pbadge cm">⏳ Çok Yakında</div>
        <div class="plan-icon">⭐</div>
        <div class="plan-name">Reklamsız</div>
        <div class="plan-price">— <span>/ ay</span></div>
        <div class="plan-desc">Premium deneyim çok yakında!</div>
        <ul class="plan-feats"><li>4K Ultra HD</li><li>Sıfır reklam</li><li>Yeni içerik önceliği</li><li>Sınırsız indirme</li></ul>
      </div>
    </div>
    <?php if (!$usedFree): ?>
    <button class="pay-btn" onclick="activatePlan()">✓ Seçili Paketi Etkinleştir</button>
    <?php else: ?>
    <p style="text-align:center;color:#ff8080;font-size:.8rem;padding:12px">Ücretsiz paket hakkınız doldu. Reklamsız paket aktif olana kadar bekleyin.</p>
    <?php endif; ?>
    <div style="text-align:center;margin-top:18px"><a href="auth.php?logout=1" style="color:var(--txt3);font-size:.75rem;text-decoration:none">Farklı hesapla giriş yap →</a></div>
  </div>
</div>

<?php else: ?>
<!-- ══ GİRİŞ / KAYIT ══ -->
<div class="w">
  <div class="steps"><div class="sdot a"></div><div class="sdot"></div><div class="sdot"></div></div>
  <div class="card">
    <div class="tabs">
      <button class="tab on" id="t-login" onclick="sw('login')">Giriş Yap</button>
      <button class="tab"    id="t-reg"   onclick="sw('reg')">Kayıt Ol</button>
    </div>
    <!-- GİRİŞ -->
    <div id="p-login">
      <h2>Tekrar Hoş Geldin 👋</h2>
      <p class="sub">Hesabına giriş yap, kaldığın yerden devam et.</p>
      <div class="msg" id="l-msg"></div>
      <div class="fg"><label>E-posta</label><input type="email" id="l-email" placeholder="ornek@email.com"></div>
      <div class="fg"><label>Şifre</label><input type="password" id="l-pass" placeholder="••••••••" onkeydown="if(event.key==='Enter')doLogin()"></div>
      <button class="btn" onclick="doLogin()">Giriş Yap →</button>
      <div class="terms">Giriş yaparak <a href="#">Kullanım Şartları</a>'nı kabul edersiniz.</div>
    </div>
    <!-- KAYIT -->
    <div id="p-reg" style="display:none">
      <h2>Hesap Oluştur ✨</h2>
      <p class="sub">Ücretsiz üye ol, tokenini al, hemen izlemeye başla!</p>
      <div class="msg" id="r-msg"></div>
      <div class="fg"><label>Ad Soyad</label><input type="text" id="r-name" placeholder="Adın Soyadın"></div>
      <div class="fg"><label>E-posta</label><input type="email" id="r-email" placeholder="ornek@email.com"></div>
      <div class="fg"><label>Şifre</label><input type="password" id="r-pass" placeholder="En az 6 karakter" onkeydown="if(event.key==='Enter')doReg()"></div>
      <button class="btn" onclick="doReg()">Hesap Oluştur →</button>
      <div class="terms">Kaydolarak <a href="#">Kullanım Şartları</a>'nı kabul edersiniz.</div>
    </div>
  </div>
</div>
<?php endif; ?>
</div>

<!-- TOKEN MODAL -->
<div id="tok-modal">
  <div class="inner">
    <span class="suc-icon">🎉</span>
    <h2 style="font-family:'DM Serif Display',serif;font-size:1.7rem;text-align:center;margin-bottom:8px">Hesabın Hazır!</h2>
    <p style="text-align:center;color:var(--txt2);font-size:.84rem;line-height:1.6;margin-bottom:4px">Bilgilerini <strong>güvenli bir yere kaydet</strong>.<br>Başka cihazlarda e-posta + şifrenle giriş yap.</p>
    <div class="tbox">
      <h3>🔑 Hesap Bilgilerin</h3>
      <div class="tr"><span class="tl">Kullanıcı ID</span><span class="tv" id="m-uid">—</span><button class="cp" onclick="cp('m-uid','ID kopyalandı!')">Kopyala</button></div>
      <div class="tr"><span class="tl">Token</span><span class="tv" id="m-tok">—</span><button class="cp" onclick="cp('m-tok','Token kopyalandı!')">Kopyala</button></div>
      <div class="tnote"><strong>⚠ Not:</strong> Token bilgin profiline kayıtlıdır. E-posta ve şifren ile her zaman giriş yapabilirsin.</div>
    </div>
    <button class="btn" onclick="goplan()">Paket Seç →</button>
  </div>
</div>

<div class="toast" id="toast"></div>
<script>
function sw(t){
  document.getElementById('t-login').classList.toggle('on',t==='login');
  document.getElementById('t-reg').classList.toggle('on',t==='reg');
  document.getElementById('p-login').style.display=t==='login'?'block':'none';
  document.getElementById('p-reg').style.display=t==='reg'?'block':'none';
}
async function post(fd){const r=await fetch('auth.php',{method:'POST',body:fd});return r.json();}
function showMsg(el,type,txt){el.className='msg '+type;el.textContent=txt;el.style.display='block';}
function setBtn(id,dis,txt){const b=document.querySelector('#'+id+' .btn');if(b){b.disabled=dis;b.textContent=txt;}}

async function doLogin(){
  const email=document.getElementById('l-email').value.trim();
  const pass=document.getElementById('l-pass').value;
  const msg=document.getElementById('l-msg');
  if(!email||!pass){showMsg(msg,'err','E-posta ve şifre giriniz.');return;}
  setBtn('p-login',true,'Giriş yapılıyor...');
  const fd=new FormData();fd.append('action','login');fd.append('email',email);fd.append('pass',pass);
  try{
    const d=await post(fd);
    if(!d.ok){showMsg(msg,'err',d.msg);setBtn('p-login',false,'Giriş Yap →');return;}
    showMsg(msg,'ok','✓ Giriş başarılı!');
    setTimeout(()=>location.href=d.need_plan?'auth.php':'index.php',800);
  }catch(e){showMsg(msg,'err','⚠ Sunucu hatası.');setBtn('p-login',false,'Giriş Yap →');}
}

async function doReg(){
  const name=document.getElementById('r-name').value.trim();
  const email=document.getElementById('r-email').value.trim();
  const pass=document.getElementById('r-pass').value;
  const msg=document.getElementById('r-msg');
  if(!name||!email||!pass){showMsg(msg,'err','Tüm alanları doldurun.');return;}
  if(pass.length<6){showMsg(msg,'err','Şifre en az 6 karakter.');return;}
  setBtn('p-reg',true,'Hesap oluşturuluyor...');
  const fd=new FormData();fd.append('action','register');fd.append('name',name);fd.append('email',email);fd.append('pass',pass);
  try{
    const d=await post(fd);
    if(!d.ok){showMsg(msg,'err',d.msg);setBtn('p-reg',false,'Hesap Oluştur →');return;}
    document.getElementById('m-uid').textContent=d.user_id;
    document.getElementById('m-tok').textContent=d.token;
    const modal=document.getElementById('tok-modal');
    modal.style.display='flex';
  }catch(e){showMsg(msg,'err','⚠ Sunucu hatası.');setBtn('p-reg',false,'Hesap Oluştur →');}
}

function goplan(){document.getElementById('tok-modal').style.display='none';location.reload();}

let selP=null;
function selPlan(p){selP=p;document.querySelectorAll('.plan:not(.dis)').forEach(el=>el.classList.remove('sel'));document.getElementById('plan-'+p)?.classList.add('sel');}
async function activatePlan(){
  if(!selP){showToast('⚠ Lütfen bir paket seçin.');return;}
  const msg=document.getElementById('pay-msg');
  const fd=new FormData();fd.append('action','activate_plan');fd.append('plan',selP);
  try{
    const d=await post(fd);
    if(!d.ok){showMsg(msg,'err',d.msg);return;}
    showMsg(msg,'ok','✓ '+d.msg+' Yönlendiriliyor...');
    setTimeout(()=>location.href='index.php',1200);
  }catch(e){showMsg(msg,'err','⚠ Sunucu hatası.');}
}

function cp(id,txt){navigator.clipboard?.writeText(document.getElementById(id).textContent).catch(()=>{});showToast('✓ '+txt);}
let tT;
function showToast(m){const t=document.getElementById('toast');t.textContent=m;t.classList.add('show');clearTimeout(tT);tT=setTimeout(()=>t.classList.remove('show'),2800);}
</script>
</body>
</html>

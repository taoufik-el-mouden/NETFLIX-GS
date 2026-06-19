<?php
// ============================================================
// register.php — Netflix-Style Registration Page
// ============================================================
require_once 'config/db.php';
require_once 'config/auth.php';

if (is_logged_in()) { header('Location: index.php'); exit; }

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username']  ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email']     ?? '');
    $password  =       $_POST['password'] ?? '';
    $confirm   =       $_POST['confirm']  ?? '';

    if (empty($username) || empty($full_name) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'يرجى ملء جميع الحقول.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $error = 'اسم المستخدم: 3-30 حرف إنجليزي أو رقم أو _ فقط.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'صيغة البريد الإلكتروني غير صحيحة.';
    } elseif (strlen($password) < 6) {
        $error = 'كلمة المرور يجب أن تحتوي على 6 أحرف على الأقل.';
    } elseif ($password !== $confirm) {
        $error = 'كلمة المرور وتأكيدها غير متطابقتين.';
    } else {
        $pdo   = get_pdo();
        $check = $pdo->prepare("SELECT id FROM users WHERE LOWER(username)=LOWER(:u) OR LOWER(email)=LOWER(:e) LIMIT 1");
        $check->execute([':u' => $username, ':e' => $email]);
        if ($check->fetch()) {
            $error = 'اسم المستخدم أو البريد الإلكتروني مستخدم مسبقاً.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $ins    = $pdo->prepare("INSERT INTO users (username, email, password, full_name) VALUES (:u,:e,:p,:n)");
            $ins->execute([':u' => $username, ':e' => $email, ':p' => $hashed, ':n' => $full_name]);
            $success = 'تم إنشاء حسابك بنجاح!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب — Netflix GS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #000; color: #fff; min-height: 100vh;
        }
        /* Background grid (same as login) */
        .nf-bg {
            position: fixed; inset: 0; z-index: 0;
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            grid-template-rows: repeat(5, 1fr);
            overflow: hidden;
        }
        .nf-bg-tile {
            animation: tileFade 6s ease-in-out infinite alternate;
        }
        @keyframes tileFade { from { opacity:.5; } to { opacity:.75; } }
        .nf-overlay {
            position: fixed; inset: 0; z-index: 1;
            background: linear-gradient(
                to bottom,
                rgba(0,0,0,.8) 0%, rgba(0,0,0,.6) 30%,
                rgba(0,0,0,.7) 70%, rgba(0,0,0,.9) 100%
            );
        }
        .nf-topline {
            position: fixed; top:0; left:0; right:0;
            height: 3px; z-index: 10;
            background: linear-gradient(90deg,#00f3ff,#b20710,#00f3ff);
        }
        /* Header */
        .nf-header {
            position: fixed; top:0; left:0; right:0; z-index:10;
            padding: 24px 48px; display:flex; align-items:center;
            background: linear-gradient(180deg, rgba(0,0,0,.9) 0%, transparent 100%);
        }
        @media(max-width:520px){ .nf-header{ padding:20px 24px; } }
        .nf-logo-text {
            font-size: 2rem; font-weight: 900; color:#00f3ff;
            letter-spacing: -1px; text-transform: uppercase;
            text-shadow: 0 2px 12px rgba(0,243,255,.4); user-select:none;
        }
        /* Card */
        .nf-card {
            position: relative; z-index:5;
            background: rgba(0,0,0,.80);
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
            border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);
            padding: 48px 68px;
            width: 100%; max-width: 480px;
            box-shadow: 0 8px 48px rgba(0,0,0,.8), inset 0 1px 0 rgba(255,255,255,.05);
        }
        @media(max-width:520px){ .nf-card{ padding:36px 24px; border-radius:0; } }
        .nf-card h1 { font-size:2rem; font-weight:700; color:#fff; margin-bottom:24px; }
        /* Inputs */
        .nf-input-wrap { position:relative; margin-bottom:14px; }
        .nf-input {
            width:100%; height:56px;
            background: rgba(51,51,51,.85);
            border: 1px solid rgba(255,255,255,.12);
            border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);
            padding: 18px 16px 6px;
            color:#fff; font-size:1rem;
            outline:none; transition:border-color .2s, background .2s;
            font-family:inherit;
        }
        .nf-input:focus {
            border-color:#00f3ff; background:rgba(58,58,58,.9);
            box-shadow: 0 0 0 2px rgba(0,243,255,.2);
        }
        .nf-input::placeholder { color:transparent; }
        .nf-label {
            position:absolute; top:50%; right:16px;
            transform:translateY(-50%);
            color:#8c8c8c; font-size:1rem;
            pointer-events:none; transition:all .2s;
        }
        .nf-input:focus ~ .nf-label,
        .nf-input:not(:placeholder-shown) ~ .nf-label {
            top:14px; transform:translateY(0); font-size:.7rem; color:#a0a0a0;
        }
        .nf-input.valid   { border-color: rgba(34,197,94,.5) !important; }
        .nf-input.invalid { border-color: rgba(0,243,255,.6)  !important; }
        /* Password toggle */
        .pw-toggle {
            position:absolute; left:14px; top:50%; transform:translateY(-50%);
            background:none; border:none; color:#8c8c8c; cursor:pointer;
            font-size:.8rem; font-weight:600; letter-spacing:.5px;
            transition:color .2s; font-family:inherit;
        }
        .pw-toggle:hover { color:#ccc; }
        /* Strength bar */
        .str-wrap { height:3px; background:rgba(255,255,255,.1); border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%); margin-top:8px; overflow:hidden; }
        .str-bar   { height:100%; border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%); transition: width .35s, background-color .35s; width:0; }
        /* Button */
        .nf-btn {
            display:block; width:100%; height:52px; margin-top:20px;
            background:#00f3ff; color:#fff; font-size:1rem; font-weight:700;
            border:none; border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%); cursor:pointer; letter-spacing:.5px;
            transition:background .2s, transform .1s, box-shadow .2s;
            box-shadow: 0 4px 20px rgba(0,243,255,.35); font-family:inherit;
        }
        .nf-btn:hover  { background:#5ce6ff; box-shadow:0 6px 28px rgba(0,243,255,.55); }
        .nf-btn:active { transform:scale(.98); background:#c40612; }
        /* Misc */
        .nf-error {
            background:rgba(0,243,255,.12); border:1px solid rgba(0,243,255,.4);
            border-right:3px solid #00f3ff; color:#ff6b6b;
            font-size:.875rem; padding:14px 16px; border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);
            margin-bottom:18px; line-height:1.5;
        }
        .nf-success {
            background:rgba(34,197,94,.1); border:1px solid rgba(34,197,94,.3);
            border-right:3px solid #22c55e; color:#4ade80;
            font-size:.875rem; padding:14px 16px; border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);
            margin-bottom:18px; line-height:1.5;
        }
        .nf-small { color:#8c8c8c; font-size:.875rem; }
        .nf-link  { color:#00f3ff; font-weight:600; text-decoration:none; transition:color .2s; }
        .nf-link:hover { color:#ff1a24; text-decoration:underline; }
        .field-msg { font-size:.72rem; margin-top:4px; min-height:16px; padding-right:4px; }
        .field-ok  { color:#22c55e; }
        .field-err { color:#ef4444; }
        ::-webkit-scrollbar { width:5px; }
        ::-webkit-scrollbar-thumb { background:#00f3ff; border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%); }
    </style>
</head>
<body>

<div class="nf-topline"></div>
<div class="nf-bg" id="nf-bg"></div>
<div class="nf-overlay"></div>

<!-- Header -->
<header class="nf-header">
    <a href="login.php" style="text-decoration:none">
        <div class="nf-logo-text">NETFLIX GS</div>
    </a>
</header>

<!-- Card -->
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:96px 20px 48px;position:relative;z-index:5;">
<div class="nf-card">

    <h1>إنشاء حساب جديد</h1>

    <?php if ($success): ?>
    <div class="nf-success">
        ✅ <?= htmlspecialchars($success) ?>
        — <a href="login.php" class="nf-link">تسجيل الدخول الآن</a>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="nf-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php" id="reg-form" novalidate>

        <!-- Full name -->
        <div class="nf-input-wrap">
            <input type="text" id="full_name" name="full_name"
                   class="nf-input" placeholder=" "
                   required autocomplete="name"
                   value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
            <label for="full_name" class="nf-label">الاسم الكامل</label>
        </div>
        <div class="field-msg" id="fn-msg"></div>

        <!-- Username -->
        <div class="nf-input-wrap" style="margin-top:4px">
            <input type="text" id="reg-username" name="username"
                   class="nf-input" placeholder=" "
                   required autocomplete="username"
                   pattern="[a-zA-Z0-9_]{3,30}"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            <label for="reg-username" class="nf-label">اسم المستخدم (بالإنجليزية)</label>
        </div>
        <div class="field-msg" id="un-msg"></div>

        <!-- Email -->
        <div class="nf-input-wrap" style="margin-top:4px">
            <input type="email" id="reg-email" name="email"
                   class="nf-input" placeholder=" "
                   required autocomplete="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            <label for="reg-email" class="nf-label">البريد الإلكتروني</label>
        </div>
        <div class="field-msg" id="em-msg"></div>

        <!-- Password -->
        <div class="nf-input-wrap" style="margin-top:4px">
            <input type="password" id="reg-pw" name="password"
                   class="nf-input" placeholder=" "
                   required autocomplete="new-password"
                   style="padding-left:72px"
                   oninput="checkStr(this.value)">
            <label for="reg-pw" class="nf-label">كلمة المرور</label>
            <button type="button" class="pw-toggle" id="toggle-reg-pw" tabindex="-1">
                <span id="reg-pw-text">إظهار</span>
            </button>
        </div>
        <!-- Strength indicator -->
        <div class="str-wrap"><div class="str-bar" id="str-bar"></div></div>
        <div class="field-msg" id="str-msg" style="color:#737373">قوة كلمة المرور</div>

        <!-- Confirm password -->
        <div class="nf-input-wrap" style="margin-top:10px">
            <input type="password" id="reg-confirm" name="confirm"
                   class="nf-input" placeholder=" "
                   required autocomplete="new-password"
                   oninput="checkMatch()">
            <label for="reg-confirm" class="nf-label">تأكيد كلمة المرور</label>
        </div>
        <div class="field-msg" id="cm-msg"></div>

        <button type="submit" class="nf-btn" id="btn-register">إنشاء الحساب</button>
    </form>

    <!-- Divider -->
    <div style="display:flex;align-items:center;gap:12px;margin:20px 0;color:#8c8c8c;font-size:.85rem;">
        <div style="flex:1;height:1px;background:rgba(255,255,255,.12)"></div>
        أو
        <div style="flex:1;height:1px;background:rgba(255,255,255,.12)"></div>
    </div>

    <p class="nf-small" style="text-align:center">
        لديك حساب بالفعل؟
        <a href="login.php" class="nf-link" style="margin-right:4px">تسجيل الدخول</a>
    </p>

</div>
</div>

<script>
// ── Build background mosaic ──
(function(){
    const bg     = document.getElementById('nf-bg');
    const colors = [
        '#1a0000','#0d0d1a','#1a0a00','#000d1a',
        '#0d1a00','#1a001a','#001a1a','#0a0a0a',
        '#1a0505','#05051a','#1a1005','#051a10',
        '#100505','#05100a','#0f0010','#100f00',
    ];
    for(let i=0;i<40;i++){
        const t = document.createElement('div');
        t.className = 'nf-bg-tile';
        t.style.background = colors[i % colors.length];
        if(i%4===0) t.style.boxShadow='inset 0 0 30px rgba(0,243,255,.15)';
        else if(i%7===0) t.style.boxShadow='inset 0 0 25px rgba(100,100,255,.08)';
        t.style.animationDelay    = (Math.random()*4).toFixed(1)+'s';
        t.style.animationDuration = (5+Math.random()*5).toFixed(1)+'s';
        bg.appendChild(t);
    }
})();

// ── Password visibility toggle ──
const regPw = document.getElementById('reg-pw');
document.getElementById('toggle-reg-pw').addEventListener('click',()=>{
    const show = regPw.type==='password';
    regPw.type = show ? 'text' : 'password';
    document.getElementById('reg-pw-text').textContent = show ? 'إخفاء' : 'إظهار';
});

// ── Password strength ──
function checkStr(pw){
    const bar  = document.getElementById('str-bar');
    const msg  = document.getElementById('str-msg');
    let score  = 0;
    if(pw.length>=6)  score++;
    if(pw.length>=10) score++;
    if(/[A-Z]/.test(pw)) score++;
    if(/[0-9]/.test(pw)) score++;
    if(/[^a-zA-Z0-9]/.test(pw)) score++;
    const levels=[
        { w:'0',   bg:'#4b4b4b', label:'أدخل كلمة المرور', col:'#737373' },
        { w:'20%', bg:'#00f3ff', label:'⚠️ ضعيفة جداً',     col:'#ef4444' },
        { w:'40%', bg:'#f97316', label:'🔸 ضعيفة',           col:'#f97316' },
        { w:'65%', bg:'#eab308', label:'🔶 متوسطة',          col:'#eab308' },
        { w:'85%', bg:'#22c55e', label:'✅ قوية',             col:'#22c55e' },
        { w:'100%',bg:'#15803d', label:'💪 قوية جداً',       col:'#4ade80' },
    ];
    const l = levels[Math.min(score, 5)];
    bar.style.width           = l.w;
    bar.style.backgroundColor = l.bg;
    msg.textContent           = l.label;
    msg.style.color           = l.col;

    const inp = document.getElementById('reg-pw');
    inp.classList.toggle('valid',   score>=3);
    inp.classList.toggle('invalid', score<3 && pw.length>0);
}

// ── Confirm password match ──
function checkMatch(){
    const conf = document.getElementById('reg-confirm');
    const msg  = document.getElementById('cm-msg');
    const match = conf.value === regPw.value && conf.value !== '';
    if(conf.value===''){
        msg.textContent=''; conf.classList.remove('valid','invalid'); return;
    }
    if(match){
        msg.textContent='✅ كلمتا المرور متطابقتان'; msg.className='field-msg field-ok';
        conf.classList.add('valid'); conf.classList.remove('invalid');
    } else {
        msg.textContent='❌ كلمتا المرور غير متطابقتين'; msg.className='field-msg field-err';
        conf.classList.add('invalid'); conf.classList.remove('valid');
    }
}

// ── Username live validation ──
document.getElementById('reg-username').addEventListener('input', function(){
    const msg = document.getElementById('un-msg');
    const ok  = /^[a-zA-Z0-9_]{3,30}$/.test(this.value);
    if(this.value===''){
        msg.textContent=''; this.classList.remove('valid','invalid'); return;
    }
    if(ok){
        msg.textContent='✅ اسم مستخدم صالح'; msg.className='field-msg field-ok';
        this.classList.add('valid'); this.classList.remove('invalid');
    } else {
        msg.textContent='3-30 حرف: a-z، 0-9، أو _ فقط'; msg.className='field-msg field-err';
        this.classList.add('invalid'); this.classList.remove('valid');
    }
});

// ── Email live validation ──
document.getElementById('reg-email').addEventListener('input', function(){
    const msg = document.getElementById('em-msg');
    const ok  = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value);
    if(this.value===''){
        msg.textContent=''; this.classList.remove('valid','invalid'); return;
    }
    if(ok){
        msg.textContent='✅ بريد إلكتروني صالح'; msg.className='field-msg field-ok';
        this.classList.add('valid'); this.classList.remove('invalid');
    } else {
        msg.textContent='أدخل بريدًا إلكترونيًا صحيحًا'; msg.className='field-msg field-err';
        this.classList.add('invalid'); this.classList.remove('valid');
    }
});
</script>
</body>
</html>



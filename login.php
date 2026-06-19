<?php
// ============================================================
// login.php — Netflix-Style Login Page
// ============================================================
require_once 'config/db.php';
require_once 'config/auth.php';

if (is_logged_in()) { header('Location: index.php'); exit; }

$error      = '';
$logged_out = isset($_GET['logged_out']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password =       $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'يرجى إدخال اسم المستخدم وكلمة المرور.';
    } else {
        $pdo  = get_pdo();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(username) = LOWER(:u) LIMIT 1");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']        = $user['id'];
            $_SESSION['user_username']  = $user['username'];
            $_SESSION['user_full_name'] = $user['full_name'];
            $_SESSION['user_role']      = $user['role'];
            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'اسم المستخدم أو كلمة المرور غير صحيحة. حاول مجدداً.';
            sleep(1);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول — Netflix GS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #000;
            color: #fff;
            min-height: 100vh;
        }

        /* ── Background: Netflix-style movie grid ── */
        .nf-bg {
            position: fixed; inset: 0; z-index: 0;
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            grid-template-rows: repeat(5, 1fr);
            overflow: hidden;
        }
        .nf-bg-tile {
            background-size: cover;
            background-position: center;
            animation: tileFade 6s ease-in-out infinite alternate;
        }
        .nf-bg-tile:nth-child(odd)  { animation-delay: 0s; }
        .nf-bg-tile:nth-child(even) { animation-delay: 1.5s; }
        @keyframes tileFade {
            from { opacity: .55; }
            to   { opacity: .75; }
        }
        /* Dark overlay on top of grid */
        .nf-overlay {
            position: fixed; inset: 0; z-index: 1;
            background: linear-gradient(
                to bottom,
                rgba(0,0,0,.75) 0%,
                rgba(0,0,0,.55) 30%,
                rgba(0,0,0,.65) 70%,
                rgba(0,0,0,.85) 100%
            );
        }
        /* Netflix-red top border line */
        .nf-topline {
            position: fixed; top: 0; left: 0; right: 0;
            height: 3px; z-index: 10;
            background: linear-gradient(90deg, #00f3ff, #b20710, #00f3ff);
        }

        /* ── Header bar ── */
        .nf-header {
            position: fixed; top: 0; left: 0; right: 0;
            z-index: 10;
            padding: 24px 48px;
            display: flex; align-items: center;
            background: linear-gradient(180deg, rgba(0,0,0,.9) 0%, transparent 100%);
        }
        .nf-logo-text {
            font-size: 2rem; font-weight: 900;
            color: #00f3ff;
            letter-spacing: -1px;
            text-transform: uppercase;
            text-shadow: 0 2px 12px rgba(0,243,255,.4);
            user-select: none;
        }

        /* ── Sign-in card ── */
        .nf-card {
            position: relative; z-index: 5;
            background: rgba(0, 0, 0, 0.78);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);
            padding: 56px 68px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 8px 48px rgba(0,0,0,.8), inset 0 1px 0 rgba(255,255,255,.05);
        }
        @media (max-width: 520px) {
            .nf-card { padding: 40px 28px; border-radius: 0; }
            .nf-header { padding: 20px 24px; }
        }
        .nf-card h1 {
            font-size: 2rem; font-weight: 700;
            color: #fff; margin-bottom: 28px;
        }

        /* ── Inputs ── */
        .nf-input-wrap {
            position: relative; margin-bottom: 16px;
        }
        .nf-input {
            width: 100%; height: 56px;
            background: rgba(51,51,51,.85);
            border: 1px solid rgba(255,255,255,.12);
            border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);
            padding: 18px 16px 6px;
            color: #fff; font-size: 1rem;
            outline: none;
            transition: border-color .2s, background .2s;
            font-family: inherit;
        }
        .nf-input:focus {
            border-color: #00f3ff;
            background: rgba(58,58,58,.9);
            box-shadow: 0 0 0 2px rgba(0,243,255,.2);
        }
        .nf-input::placeholder { color: transparent; }
        .nf-label {
            position: absolute; top: 50%; right: 16px;
            transform: translateY(-50%);
            color: #8c8c8c; font-size: 1rem;
            pointer-events: none;
            transition: all .2s;
        }
        /* Float label when input has value or is focused */
        .nf-input:focus   ~ .nf-label,
        .nf-input:not(:placeholder-shown) ~ .nf-label {
            top: 14px; transform: translateY(0);
            font-size: .7rem; color: #a0a0a0;
        }
        /* Password toggle */
        .pw-toggle {
            position: absolute; left: 14px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: #8c8c8c; cursor: pointer;
            font-size: .8rem; font-weight: 600;
            letter-spacing: .5px;
            transition: color .2s;
            font-family: inherit;
        }
        .pw-toggle:hover { color: #ccc; }

        /* ── Sign-in button ── */
        .nf-btn {
            display: block; width: 100%;
            height: 52px; margin-top: 24px;
            background: #00f3ff;
            color: #fff; font-size: 1rem; font-weight: 700;
            border: none; border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%); cursor: pointer;
            letter-spacing: .5px;
            transition: background .2s, transform .1s, box-shadow .2s;
            box-shadow: 0 4px 20px rgba(0,243,255,.35);
            font-family: inherit;
        }
        .nf-btn:hover  { background: #5ce6ff; box-shadow: 0 6px 28px rgba(0,243,255,.55); }
        .nf-btn:active { transform: scale(.98); background: #c40612; }

        /* ── Misc ── */
        .nf-divider {
            display: flex; align-items: center; gap: 12px;
            margin: 20px 0; color: #8c8c8c; font-size: .85rem;
        }
        .nf-divider::before, .nf-divider::after {
            content: ''; flex: 1; height: 1px; background: rgba(255,255,255,.15);
        }
        .nf-link { color: #00f3ff; font-weight: 600; text-decoration: none; transition: color .2s; }
        .nf-link:hover { color: #ff1a24; text-decoration: underline; }
        .nf-small { color: #8c8c8c; font-size: .85rem; line-height: 1.6; }
        .nf-error {
            background: rgba(0,243,255,.12);
            border: 1px solid rgba(0,243,255,.4);
            border-right: 3px solid #00f3ff;
            color: #ff6b6b; font-size: .875rem;
            padding: 14px 16px; border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);
            margin-bottom: 20px; line-height: 1.5;
        }
        .nf-success {
            background: rgba(34,197,94,.1);
            border: 1px solid rgba(34,197,94,.3);
            border-right: 3px solid #22c55e;
            color: #4ade80; font-size: .875rem;
            padding: 14px 16px; border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);
            margin-bottom: 20px; line-height: 1.5;
        }
        .nf-hint {
            margin-top: 20px; padding: 14px 16px;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.07);
            border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);
            color: #737373; font-size: .8rem; text-align: center;
        }
        .nf-hint span { color: #b3b3b3; font-family: monospace; }

        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-thumb { background: #00f3ff; border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%); }
    </style>
</head>
<body>

<!-- Red top accent line -->
<div class="nf-topline"></div>

<!-- ── Background: Mosaic of colored cards simulating movie grid ── -->
<div class="nf-bg" id="nf-bg"></div>
<div class="nf-overlay"></div>

<!-- ── Top Header with Logo ── -->
<header class="nf-header">
    <a href="login.php" style="text-decoration:none">
        <div class="nf-logo-text">NETFLIX GS</div>
    </a>
</header>

<!-- ── Centered Sign-in Card ── -->
<div style="min-height:100vh; display:flex; align-items:center; justify-content:center; padding: 96px 20px 40px; position:relative; z-index:5;">
    <div class="nf-card">

        <h1>تسجيل الدخول</h1>

        <!-- Logged-out success message -->
        <?php if ($logged_out): ?>
        <div class="nf-success">👋 تم تسجيل خروجك بنجاح. نراك قريباً!</div>
        <?php endif; ?>

        <!-- Error -->
        <?php if ($error): ?>
        <div class="nf-error" id="login-err">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" action="login.php" id="login-form">

            <!-- Username (floating label) -->
            <div class="nf-input-wrap">
                <input type="text"
                       id="username" name="username"
                       class="nf-input" placeholder=" "
                       required autocomplete="username" autofocus
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                <label for="username" class="nf-label">اسم المستخدم</label>
            </div>

            <!-- Password (floating label + toggle) -->
            <div class="nf-input-wrap">
                <input type="password"
                       id="login-pw" name="password"
                       class="nf-input" placeholder=" "
                       required autocomplete="current-password"
                       style="padding-left: 72px;">
                <label for="login-pw" class="nf-label">كلمة المرور</label>
                <button type="button" class="pw-toggle" id="toggle-pw" tabindex="-1">
                    <span id="pw-toggle-text">إظهار</span>
                </button>
            </div>

            <button type="submit" class="nf-btn" id="btn-login">تسجيل الدخول</button>
        </form>

        <!-- OR divider -->
        <div class="nf-divider">أو</div>

        <!-- Register link -->
        <div style="text-align:center; margin-bottom: 32px;">
            <p class="nf-small">
                ليس لديك حساب؟
                <a href="register.php" class="nf-link" style="margin-right:4px">إنشاء حساب جديد</a>
            </p>
        </div>

        <!-- Default credentials hint -->
        <div class="nf-hint">
            الحساب الافتراضي — اسم: <span>admin</span> / كلمة المرور: <span>admin123</span><br>
            <span style="color:#555">احذف هذا التلميح في الإنتاج</span>
        </div>
    </div>
</div>

<script>
// ── Build the background mosaic ──
(function() {
    const bg = document.getElementById('nf-bg');
    // Netflix-palette inspired colors for tiles
    const colors = [
        '#1a0000','#0d0d1a','#1a0a00','#000d1a',
        '#0d1a00','#1a001a','#001a1a','#0a0a0a',
        '#1a0505','#05051a','#1a1005','#051a10',
        '#100505','#05100a','#0f0010','#100f00',
    ];
    // 8 cols × 5 rows = 40 tiles
    for (let i = 0; i < 40; i++) {
        const tile = document.createElement('div');
        tile.className = 'nf-bg-tile';
        const hue = Math.floor(Math.random() * 30) - 5; // slight hue variation
        tile.style.background = colors[i % colors.length];
        // Add subtle inner glow on some tiles
        if (i % 4 === 0) {
            tile.style.boxShadow = 'inset 0 0 30px rgba(0,243,255,0.15)';
        } else if (i % 7 === 0) {
            tile.style.boxShadow = 'inset 0 0 25px rgba(100,100,255,0.08)';
        }
        tile.style.animationDelay = (Math.random() * 4).toFixed(1) + 's';
        tile.style.animationDuration = (5 + Math.random() * 5).toFixed(1) + 's';
        bg.appendChild(tile);
    }
})();

// ── Password toggle ──
const pwInput      = document.getElementById('login-pw');
const toggleBtn    = document.getElementById('toggle-pw');
const toggleText   = document.getElementById('pw-toggle-text');
toggleBtn.addEventListener('click', () => {
    const show = pwInput.type === 'password';
    pwInput.type       = show ? 'text' : 'password';
    toggleText.textContent = show ? 'إخفاء' : 'إظهار';
});

// ── Auto-dismiss error ──
const errEl = document.getElementById('login-err');
if (errEl) {
    setTimeout(() => {
        errEl.style.transition = 'opacity .5s';
        errEl.style.opacity = '0';
        setTimeout(() => errEl.remove(), 500);
    }, 6000);
}
</script>
</body>
</html>



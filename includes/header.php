<?php
// ============================================================
// includes/header.php — Netflix-Style Global Navigation
// ============================================================
require_once __DIR__ . '/../config/auth.php';

// 🔒 Protect every page that includes this header
require_login();

// Get logged-in user for navbar display
$_nav_user    = current_user();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Netflix GS Manager') ?></title>
    <meta name="description" content="نظام إدارة وبيع بروفايلات Netflix الرقمية">

    <!-- Fonts (Cyberpunk) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700;900&family=Rajdhani:wght@500;600;700&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        :root {
            --primary: #00f3ff;
            --primary-hover: #5ce6ff;
            --accent: #ff00e6;
            --bg-base: #050510;
            --bg-card: #0b0b1a;
            --text-main: #ffffff;
            --text-muted: #a0a0a0;
        }

        /* ── Base ── */
        *, *::before, *::after { box-sizing: border-box; }
        html, body { scroll-behavior: smooth; overflow-x: hidden; width: 100%; }
        body {
            font-family: 'Tajawal', 'Rajdhani', sans-serif;
            background-color: var(--bg-base);
            background-image: 
                linear-gradient(rgba(0, 243, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 243, 255, 0.03) 1px, transparent 1px);
            background-size: 30px 30px;
            background-position: center top;
            color: var(--text-main);
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
        }

        /* ── Navbar ── */
        .nf-navbar {
            background: rgba(5,5,16,0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0,243,255,0.2);
            position: sticky; top: 0; z-index: 9000;
            box-shadow: 0 4px 30px rgba(0,243,255,0.05);
        }
        .nf-navbar-inner {
            max-width: 1400px; margin: 0 auto; padding: 0 32px;
            display: flex; align-items: center; justify-content: space-between;
            height: 64px;
        }

        /* Logo */
        .nf-logo {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.6rem; font-weight: 900;
            color: var(--primary);
            letter-spacing: 1px;
            text-shadow: 0 0 10px rgba(0,243,255,0.6);
            text-decoration: none;
            white-space: nowrap; user-select: none;
        }
        .nf-logo span { color: var(--accent); font-weight: 700; font-size: .9rem; text-shadow: 0 0 10px rgba(255,0,230,0.6); }

        /* Nav links */
        .nf-nav-links {
            display: flex; align-items: center; gap: 4px;
            flex: 1; justify-content: center;
        }
        .nf-nav-link {
            padding: 7px 14px; border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);
            font-size: .82rem; font-weight: 500; letter-spacing: .2px;
            color: #b3b3b3; text-decoration: none;
            transition: color .15s, background .15s;
            white-space: nowrap;
            display: flex; align-items: center; gap: 6px;
        }
        .nf-nav-link:hover  { color: #fff; background: rgba(255,255,255,.07); }
        .nf-nav-link.active { color: #fff; background: rgba(0,243,255,.15); }
        .nf-nav-link .dot {
            width: 5px; height: 5px; border-radius: 50%;
            background: #00f3ff; opacity: 0;
            transition: opacity .15s;
            margin-top: 1px;
        }
        .nf-nav-link.active .dot { opacity: 1; }

        /* User section */
        .nf-user-section { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
        .nf-avatar {
            width: 34px; height: 34px; border-radius: 0;
            clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);
            background: linear-gradient(135deg,#00f3ff,#b20710);
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: .85rem; color: #fff;
            cursor: pointer; user-select: none;
            border: 2px solid rgba(0,243,255,.4);
            transition: border-color .2s;
            position: relative;
        }
        .nf-avatar:hover { border-color: #00f3ff; }
        /* Dropdown */
        .nf-dropdown {
            position: absolute; top: calc(100% + 10px); left: 0;
            min-width: 200px;
            background: rgba(20,20,20,.97);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 0;
            padding: 6px;
            display: none;
            box-shadow: 0 12px 40px rgba(0,0,0,.8);
            z-index: 100;
        }
        .nf-dropdown.open { display: block; }
        .nf-dropdown::before {
            content: ''; position: absolute;
            top: -6px; left: 14px;
            border-left: 6px solid transparent;
            border-right: 6px solid transparent;
            border-bottom: 6px solid rgba(255,255,255,.1);
        }
        .nf-dd-info { padding: 10px 12px 8px; border-bottom: 1px solid rgba(0,243,255,0.2); margin-bottom: 4px; }
        .nf-dd-name  { font-weight: 600; font-size: .85rem; color: #fff; }
        .nf-dd-user  { font-size: .72rem; color: #737373; margin-top: 2px; }
        .nf-dd-item {
            display: flex; align-items: center; gap: 8px;
            padding: 8px 12px; border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);
            font-size: .82rem; color: #b3b3b3;
            text-decoration: none;
            transition: background .15s, color .15s;
            cursor: pointer;
        }
        .nf-dd-item:hover { background: rgba(255,255,255,.07); color: #fff; }
        .nf-dd-item.danger { color: #ef4444; }
        .nf-dd-item.danger:hover { background: rgba(239,68,68,.1); color: #f87171; }

        /* Mobile hamburger */
        .nf-hamburger {
            display: none; flex-direction: column; gap: 5px;
            background: none; border: none; cursor: pointer; padding: 6px;
        }
        .nf-hamburger span {
            display: block; width: 22px; height: 2px;
            background: #fff; border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);
            transition: all .3s;
        }
        .nf-hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
        .nf-hamburger.open span:nth-child(2) { opacity: 0; }
        .nf-hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

        /* Mobile nav drawer */
        .nf-mobile-menu {
            display: none; position: fixed; inset: 64px 0 0 0; z-index: 8999;
            background: rgba(0,0,0,.97); backdrop-filter: blur(8px);
            padding: 24px 20px;
            overflow-y: auto;
            flex-direction: column; gap: 6px;
        }
        .nf-mobile-menu.open { display: flex; }
        .nf-mobile-link {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px; border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);
            font-size: .95rem; font-weight: 500;
            color: #b3b3b3; text-decoration: none;
            transition: all .15s;
        }
        .nf-mobile-link:hover { color: #fff; background: rgba(0,243,255,0.15); }
        .nf-mobile-link.active { color: #fff; background: rgba(0,243,255,.12); }
        .nf-mobile-divider { height: 1px; background: rgba(0,243,255,0.2); margin: 8px 0; }

        @media (max-width: 768px) {
            .nf-nav-links { display: none; }
            .nf-hamburger { display: flex; }
            .nf-user-section .nf-avatar { display: none; }
            .nf-navbar-inner { padding: 0 16px; }
        }

        /* ── Page layout ── */
        .nf-page { max-width: 1400px; margin: 0 auto; padding: 32px 32px 64px; }
        @media(max-width:768px){ .nf-page { padding: 20px 16px 48px; } }

        /* ── Cards / Surfaces ── */
        .nf-card {
            background: #0b0b1a;
            border: 1px solid rgba(0,243,255,0.3);
            border-radius: 0;
            clip-path: polygon(0 0, 100% 0, 100% calc(100% - 20px), calc(100% - 20px) 100%, 0 100%);
            position: relative;
        }
        .nf-card::after {
            content: ''; position: absolute; bottom: 0; right: 0;
            width: 20px; height: 3px; background: var(--primary);
        }
        .nf-card-sm { background: #101026; border: 1px solid rgba(0,243,255,0.2); border-radius: 0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%); }

        /* ── Page heading ── */
        .nf-heading { font-family: 'Rajdhani', sans-serif; font-size: 2rem; font-weight: 700; color: #fff; line-height: 1.2; text-shadow: 0 0 5px rgba(255,255,255,0.3); }
        .nf-subheading { color: var(--text-muted); font-size: .9rem; margin-top: 4px; font-family: 'Rajdhani', sans-serif; letter-spacing: 0.5px; }

        /* ── Stats cards ── */
        .nf-stat {
            background: var(--bg-card);
            border: 1px solid rgba(0,243,255,0.3);
            border-radius: 0;
            clip-path: polygon(0 0, 100% 0, 100% calc(100% - 15px), calc(100% - 15px) 100%, 0 100%);
            padding: 20px 22px;
            position: relative; overflow: hidden;
            transition: all .2s;
            box-shadow: inset 0 0 15px rgba(0,243,255,0.05);
        }
        .nf-stat::after {
            content: ''; position: absolute; bottom: 0; right: 0;
            width: 15px; height: 3px; background: var(--primary);
        }
        .nf-stat:hover { border-color: var(--primary); transform: translateY(-3px); box-shadow: inset 0 0 20px rgba(0,243,255,0.2), 0 0 15px rgba(0,243,255,0.2); }
        .nf-stat-glow {
            position: absolute; top: -20px; right: -20px;
            width: 80px; height: 80px; border-radius: 50%;
            filter: blur(30px); opacity: .4;
        }
        .nf-stat-label { font-size: .75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; font-weight: 700; font-family: 'Rajdhani', sans-serif; }
        .nf-stat-value { font-family: 'Orbitron', sans-serif; font-size: 1.8rem; font-weight: 700; color: #fff; margin: 8px 0 4px; line-height: 1; text-shadow: 0 0 10px rgba(0,243,255,0.5); }
        .nf-stat-unit  { font-size: .85rem; font-weight: 500; font-family: 'Rajdhani', sans-serif; }
        .nf-stat-sub   { font-size: .75rem; color: #737373; font-family: 'Rajdhani', sans-serif; font-weight: 600; }

        /* ── Tables ── */
        .nf-table-wrap { overflow-x: auto; width: 100%; -webkit-overflow-scrolling: touch; }
        .nf-table { width: 100%; border-collapse: collapse; font-size: .82rem; min-width: 600px; }
        .nf-table thead th {
            padding: 10px 16px; text-align: right;
            color: #737373; font-size: .72rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: .6px;
            border-bottom: 1px solid rgba(255,255,255,.07);
            white-space: nowrap;
        }
        .nf-table tbody td { padding: 11px 16px; border-bottom: 1px solid rgba(255,255,255,.05); }
        .nf-table tbody tr:last-child td { border-bottom: none; }
        .nf-table tbody tr { transition: background .12s; }
        .nf-table tbody tr:hover { background: rgba(255,255,255,.03); }

        /* ── Badges ── */
        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 10px; border-radius: 0;
            clip-path: polygon(0 0, 100% 0, calc(100% - 6px) 100%, 0 100%);
            font-size: .7rem; font-weight: 700; font-family: 'Rajdhani', sans-serif; white-space: nowrap; text-transform: uppercase;
        }
        .badge-green  { background: rgba(34,197,94,.12);  color: #4ade80; border: 1px solid rgba(34,197,94,.2); }
        .badge-red    { background: rgba(239,68,68,.12);  color: #f87171; border: 1px solid rgba(239,68,68,.2); }
        .badge-amber  { background: rgba(245,158,11,.12); color: #fbbf24; border: 1px solid rgba(245,158,11,.2); }
        .badge-blue   { background: rgba(59,130,246,.12); color: #60a5fa; border: 1px solid rgba(59,130,246,.2); }
        .badge-purple { background: rgba(168,85,247,.12); color: #c084fc; border: 1px solid rgba(168,85,247,.2); }

        /* ── Form inputs ── */
        .nf-input, .nf-select {
            width: 100%;
            background: rgba(11,11,26,0.8);
            border: 1px solid rgba(0,243,255,0.3);
            border-radius: 0;
            clip-path: polygon(0 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%);
            padding: 10px 14px;
            color: #fff; font-size: .875rem;
            font-family: 'Rajdhani', sans-serif; font-weight: 600; letter-spacing: 0.5px;
            outline: none;
            transition: all .2s;
        }
        .nf-input:focus, .nf-select:focus {
            border-color: var(--primary);
            background: rgba(0,243,255,0.05);
            box-shadow: inset 0 0 10px rgba(0,243,255,.2);
        }
        .nf-input::placeholder { color: #4a4a4a; }
        .nf-select option { background: #0b0b1a; color: #fff; }
        .nf-label { display: block; font-size: .75rem; color: #a0a0a0; font-weight: 600;
                    margin-bottom: 6px; letter-spacing: .3px; }

        /* ── Buttons ── */
        .nf-btn-primary {
            background: rgba(0,243,255,0.15); color: var(--primary);
            border: 1px solid var(--primary); border-radius: 0;
            clip-path: polygon(0 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%);
            padding: 12px 20px; font-size: 1rem; font-weight: 700;
            cursor: pointer; width: 100%; font-family: 'Orbitron', sans-serif;
            transition: all .15s;
            box-shadow: 0 0 10px rgba(0,243,255,0.2) inset;
            letter-spacing: 1px; text-transform: uppercase;
            text-shadow: 0 0 5px rgba(0,243,255,0.5);
            position: relative;
        }
        .nf-btn-primary:hover  { background: var(--primary); color: #000; box-shadow: 0 0 15px rgba(0,243,255,0.5) inset, 0 0 25px rgba(0,243,255,0.6); text-shadow: none; }
        .nf-btn-primary:active { transform: scale(.98); }
        
        .nf-btn-danger {
            background: rgba(255,0,230,0.1); color: var(--accent);
            border: 1px solid var(--accent); border-radius: 0;
            clip-path: polygon(0 0, 100% 0, 100% calc(100% - 8px), calc(100% - 8px) 100%, 0 100%);
            padding: 6px 12px; font-size: .75rem; font-weight: 700;
            cursor: pointer; font-family: 'Orbitron', sans-serif; letter-spacing: 1px;
            transition: all .15s;
        }
        .nf-btn-danger:hover { background: rgba(255,0,230,0.2); box-shadow: 0 0 15px rgba(255,0,230,0.4); text-shadow: 0 0 5px var(--accent); }

        /* ── Alerts ── */
        .nf-alert-success {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 14px 18px; border-radius: 0; font-size: .875rem;
            clip-path: polygon(0 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%);
            background: rgba(34,197,94,.08); border: 1px solid rgba(34,197,94,.25);
            border-right: 3px solid #22c55e; color: #4ade80;
            margin-bottom: 24px; font-family: 'Rajdhani', sans-serif; font-weight: 600;
        }
        .nf-alert-error {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 14px 18px; border-radius: 0; font-size: .875rem;
            clip-path: polygon(0 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%);
            background: rgba(0,243,255,.08); border: 1px solid rgba(0,243,255,.25);
            border-right: 3px solid #00f3ff; color: #ff6b6b;
            margin-bottom: 24px; font-family: 'Rajdhani', sans-serif; font-weight: 600;
        }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #050510; }
        ::-webkit-scrollbar-thumb { background: #00f3ff; border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%); }

        /* ── Urgent row colors ── */
        tr.row-expired { background: rgba(239,68,68,.07) !important; border-right: 3px solid rgba(239,68,68,.5); }
        tr.row-urgent  { background: rgba(245,158,11,.07) !important; border-right: 3px solid rgba(245,158,11,.5); }

        /* ── Topline accent ── */
        .nf-topline {
            height: 3px; width: 100%;
            background: linear-gradient(90deg, var(--primary), var(--accent), var(--primary));
            box-shadow: 0 0 10px var(--primary);
        }

        /* ── Empty state ── */
        .nf-empty { text-align: center; padding: 56px 20px; color: #737373; }
        .nf-empty-icon { font-size: 3.5rem; margin-bottom: 12px; }
        .nf-empty-title { font-size: 1rem; font-weight: 600; color: #a0a0a0; }
        .nf-empty-sub   { font-size: .82rem; margin-top: 4px; }

        /* ── Section header ── */
        .nf-section-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,.07);
        }
        .nf-section-title { font-size: .9rem; font-weight: 700; color: #fff; }
    </style>
</head>
<body>

<!-- Netflix red top accent line -->
<div class="nf-topline"></div>

<!-- ═══════════════════════════════════════════════
     NAVBAR
═══════════════════════════════════════════════ -->
<nav class="nf-navbar" id="main-navbar">
    <div class="nf-navbar-inner">

        <!-- Logo -->
        <a href="index.php" class="nf-logo" style="text-decoration:none">
            NETFLIX<span>GS</span>
        </a>

        <!-- Desktop nav links (centered) -->
        <div class="nf-nav-links">
            <a href="index.php"
               class="nf-nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>">
                <span class="dot"></span>
                <svg style="width:14px;height:14px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                لوحة التحكم
            </a>
            <a href="accounts.php"
               class="nf-nav-link <?= $current_page === 'accounts.php' ? 'active' : '' ?>">
                <span class="dot"></span>
                <svg style="width:14px;height:14px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
                إدارة الحسابات
            </a>
            <a href="sales.php"
               class="nf-nav-link <?= $current_page === 'sales.php' ? 'active' : '' ?>">
                <span class="dot"></span>
                <svg style="width:14px;height:14px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                تسجيل المبيعات
            </a>
            <a href="expenses.php"
               class="nf-nav-link <?= $current_page === 'expenses.php' ? 'active' : '' ?>">
                <span class="dot"></span>
                <svg style="width:14px;height:14px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                المصاريف ADS
            </a>
        </div>

        <!-- Desktop: user dropdown -->
        <div class="nf-user-section">
            <div style="position:relative;" id="avatar-container">
                <div class="nf-avatar" id="avatar-btn" title="حساب المستخدم">
                    <?= mb_strtoupper(mb_substr($_nav_user['full_name'], 0, 1)) ?>
                </div>
                <!-- Dropdown -->
                <div class="nf-dropdown" id="nav-dropdown" style="left:auto; right:0;">
                    <div class="nf-dd-info">
                        <div class="nf-dd-name"><?= htmlspecialchars($_nav_user['full_name']) ?></div>
                        <div class="nf-dd-user">@<?= htmlspecialchars($_nav_user['username']) ?></div>
                    </div>
                    <a href="logout.php" class="nf-dd-item danger" id="btn-logout">
                        <svg style="width:14px;height:14px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        تسجيل الخروج
                    </a>
                </div>
            </div>
        </div>

        <!-- Mobile hamburger -->
        <button class="nf-hamburger" id="hamburger-btn" aria-label="قائمة التنقل">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

<!-- Mobile full-screen menu -->
<div class="nf-mobile-menu" id="mobile-menu">
    <!-- User info -->
    <div style="display:flex;align-items:center;gap:12px;padding:12px 16px 20px;border-bottom:1px solid rgba(0,243,255,0.2);margin-bottom:8px;">
        <div style="width:40px;height:40px;border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);background:linear-gradient(135deg,#00f3ff,#b20710);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1rem;color:#fff;flex-shrink:0">
            <?= mb_strtoupper(mb_substr($_nav_user['full_name'], 0, 1)) ?>
        </div>
        <div>
            <div style="font-weight:700;color:#fff;font-size:.9rem"><?= htmlspecialchars($_nav_user['full_name']) ?></div>
            <div style="color:#737373;font-size:.75rem">@<?= htmlspecialchars($_nav_user['username']) ?></div>
        </div>
    </div>

    <a href="index.php"    class="nf-mobile-link <?= $current_page==='index.php'    ? 'active':'' ?>">
        <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        لوحة التحكم
    </a>
    <a href="accounts.php" class="nf-mobile-link <?= $current_page==='accounts.php' ? 'active':'' ?>">
        <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
        إدارة الحسابات
    </a>
    <a href="sales.php"    class="nf-mobile-link <?= $current_page==='sales.php'    ? 'active':'' ?>">
        <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        تسجيل المبيعات
    </a>
    <a href="expenses.php" class="nf-mobile-link <?= $current_page==='expenses.php' ? 'active':'' ?>">
        <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        المصاريف ADS
    </a>

    <div class="nf-mobile-divider"></div>
    <a href="logout.php" class="nf-mobile-link" style="color:#ff00e6">
        <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        تسجيل الخروج
    </a>
</div>

<!-- ═══════════════════════════════════════════════
     PAGE CONTENT WRAPPER
═══════════════════════════════════════════════ -->
<div class="nf-page">

<script>
// ── Avatar dropdown toggle ──
const avatarBtn  = document.getElementById('avatar-btn');
const navDropdown = document.getElementById('nav-dropdown');
if (avatarBtn) {
    avatarBtn.addEventListener('click', e => {
        e.stopPropagation();
        navDropdown.classList.toggle('open');
    });
    document.addEventListener('click', () => navDropdown.classList.remove('open'));
}

// ── Mobile hamburger ──
const hamburger   = document.getElementById('hamburger-btn');
const mobileMenu  = document.getElementById('mobile-menu');
hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('open');
    mobileMenu.classList.toggle('open');
    document.body.style.overflow = mobileMenu.classList.contains('open') ? 'hidden' : '';
});

// Close mobile menu on nav link click
mobileMenu.querySelectorAll('.nf-mobile-link').forEach(l => {
    l.addEventListener('click', () => {
        hamburger.classList.remove('open');
        mobileMenu.classList.remove('open');
        document.body.style.overflow = '';
    });
});
</script>



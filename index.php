<?php
// ============================================================
// index.php — Dashboard (لوحة التحكم) — Netflix Design
// ============================================================
require_once 'config/db.php';

$page_title = 'لوحة التحكم — Netflix GS';
$pdo = get_pdo();

// ── HANDLE EDIT PROFILE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_profile') {
    $prof_id   = (int)($_POST['profile_id'] ?? 0);
    $new_name  = trim($_POST['new_name'] ?? '');
    $new_pin   = trim($_POST['new_pin'] ?? '');
    if ($prof_id > 0) {
        $pdo->prepare("UPDATE profiles SET profile_name = ?, pin_code = ? WHERE id = ?")
            ->execute([$new_name, $new_pin === '' ? null : $new_pin, $prof_id]);
        header("Location: index.php");
        exit;
    }
}

// Ensure expenses table exists if not already created
$pdo->exec("CREATE TABLE IF NOT EXISTS `expenses` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category`     ENUM('ads','other') NOT NULL DEFAULT 'ads',
    `description`  VARCHAR(500) NOT NULL,
    `amount`       DECIMAL(10,2) NOT NULL,
    `expense_date` DATE NOT NULL,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// 1. Total sales revenue
$total_revenue = (float) $pdo->query("SELECT COALESCE(SUM(selling_price), 0) FROM sales")->fetchColumn();

// 2. Main accounts count
$total_main_accounts = (int) $pdo->query("SELECT COUNT(*) FROM main_accounts")->fetchColumn();

// 3. Accounts cost
$accounts_cost = $total_main_accounts * COST_PER_MAIN_ACCOUNT;

// 4. Expenses (ADS and Others)
$exp_query = $pdo->query("
    SELECT 
        COALESCE(SUM(CASE WHEN category = 'ads' THEN amount ELSE 0 END), 0) AS total_ads,
        COALESCE(SUM(CASE WHEN category = 'other' THEN amount ELSE 0 END), 0) AS total_other
    FROM expenses
")->fetch();
$total_ads = (float)$exp_query['total_ads'];
$total_other = (float)$exp_query['total_other'];
$total_expenses = $total_ads + $total_other;

// 5. Net profit = revenue - accounts_cost - total_expenses
$total_net_profit = $total_revenue - $accounts_cost - $total_expenses;

// 6. Available profiles in stock
$available_profiles = (int) $pdo->query("SELECT COUNT(*) FROM profiles WHERE is_sold = 0")->fetchColumn();

// 7. Total sales count
$total_sales_count = (int) $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn();

// 6. Urgent alerts: subscriptions expiring ≤ 3 days or already expired
$alerts = $pdo->query("
    SELECT s.id AS sale_id, s.customer_name, s.customer_phone, s.selling_price,
           s.sale_date, s.expiry_date, ma.email AS account_email,
           p.id AS profile_id, p.profile_number, p.profile_name, p.pin_code, DATEDIFF(s.expiry_date, CURDATE()) AS days_left
    FROM sales s
    JOIN profiles p ON s.profile_id = p.id
    JOIN main_accounts ma ON p.account_id = ma.id
    WHERE DATEDIFF(s.expiry_date, CURDATE()) <= 3
    ORDER BY days_left ASC
")->fetchAll();

// 7. Recent 10 sales
$recent_sales = $pdo->query("
    SELECT s.id, s.customer_name, s.customer_phone, s.selling_price,
           s.sale_date, s.expiry_date, ma.email AS account_email,
           p.id AS profile_id, p.profile_number, p.profile_name, p.pin_code, DATEDIFF(s.expiry_date, CURDATE()) AS days_left
    FROM sales s
    JOIN profiles p ON s.profile_id = p.id
    JOIN main_accounts ma ON p.account_id = ma.id
    ORDER BY s.sale_date DESC LIMIT 10
")->fetchAll();

// 8. Chart Data (Last 7 days sales)
$chart_raw = $pdo->query("
    SELECT sale_date, SUM(selling_price) as total 
    FROM sales 
    WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
    GROUP BY sale_date 
    ORDER BY sale_date ASC
")->fetchAll(PDO::FETCH_KEY_PAIR);

$chart_labels = [];
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('m/d', strtotime($d));
    $chart_data[] = isset($chart_raw[$d]) ? (float)$chart_raw[$d] : 0;
}

include 'includes/header.php';
?>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<!-- Page heading -->
<div style="margin-bottom:28px">
    <h1 class="nf-heading">لوحة التحكم</h1>
    <p class="nf-subheading">نظرة عامة على المبيعات والمخزون — <?= date('d/m/Y') ?></p>
</div>

<!-- ═══ STATS CARDS ═══ -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:32px">

    <!-- Total Revenue -->
    <div class="nf-stat">
        <div class="nf-stat-glow" style="background:#00f3ff"></div>
        <div class="nf-stat-label">المداخيل الإجمالية</div>
        <div class="nf-stat-value"><?= number_format($total_revenue, 2) ?> <span class="nf-stat-unit" style="color:#00f3ff">DH</span></div>
        <div class="nf-stat-sub"><?= $total_sales_count ?> بيعة مسجلة</div>
    </div>

    <!-- Expenses (ADS & Other) -->
    <div class="nf-stat">
        <div class="nf-stat-glow" style="background:#8b5cf6"></div>
        <div class="nf-stat-label">المصاريف والإعلانات</div>
        <div class="nf-stat-value" style="color:#a78bfa">-<?= number_format($total_expenses, 2) ?> <span class="nf-stat-unit">DH</span></div>
        <div class="nf-stat-sub">
            <span style="color:#60a5fa">ADS: <?= number_format($total_ads, 0) ?></span> | 
            <span style="color:#9ca3af">أخرى: <?= number_format($total_other, 0) ?></span>
        </div>
    </div>

    <!-- Net Profit -->
    <div class="nf-stat">
        <div class="nf-stat-glow" style="background:<?= $total_net_profit >= 0 ? '#22c55e' : '#ef4444' ?>"></div>
        <div class="nf-stat-label">الأرباح الصافية الحقيقية</div>
        <div class="nf-stat-value" style="color:<?= $total_net_profit >= 0 ? '#4ade80' : '#f87171' ?>">
            <?= number_format($total_net_profit, 2) ?> <span class="nf-stat-unit">DH</span>
        </div>
        <div class="nf-stat-sub">ناقص ثمن الحسابات والمصاريف</div>
    </div>

    <!-- Main Accounts -->
    <div class="nf-stat">
        <div class="nf-stat-glow" style="background:#3b82f6"></div>
        <div class="nf-stat-label">الحسابات المشتراة</div>
        <div class="nf-stat-value" style="color:#60a5fa"><?= $total_main_accounts ?></div>
        <div class="nf-stat-sub">تكلفة: <?= number_format($accounts_cost, 0) ?> DH</div>
    </div>

    <!-- Available Stock -->
    <div class="nf-stat">
        <div class="nf-stat-glow" style="background:#eab308"></div>
        <div class="nf-stat-label">المخزون المتاح</div>
        <div class="nf-stat-value" style="color:#fbbf24"><?= $available_profiles ?></div>
        <div class="nf-stat-sub">بروفايل جاهز للبيع</div>
    </div>
</div>

<!-- ═══ SALES CHART (CYBERPUNK) ═══ -->
<div class="nf-card" style="padding:20px; margin-bottom:32px">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
        <svg style="width:20px;height:20px;color:var(--primary)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
        </svg>
        <h2 style="font-size:1.1rem;font-weight:700;color:#fff;font-family:'Rajdhani'">أداء المبيعات (آخر 7 أيام)</h2>
    </div>
    <div style="position:relative;height:250px;width:100%">
        <canvas id="salesChart"></canvas>
    </div>
</div>
<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    // Create Cyberpunk Gradient
    const gradient = ctx.createLinearGradient(0, 0, 0, 250);
    gradient.addColorStop(0, 'rgba(0, 243, 255, 0.5)');
    gradient.addColorStop(1, 'rgba(0, 243, 255, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'المبيعات (DH)',
                data: <?= json_encode($chart_data) ?>,
                borderColor: '#00f3ff',
                backgroundColor: gradient,
                borderWidth: 3,
                pointBackgroundColor: '#ff00e6',
                pointBorderColor: '#000',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    grid: { color: 'rgba(0,243,255,0.05)', borderColor: 'rgba(0,243,255,0.2)' },
                    ticks: { color: '#a0a0a0', font: { family: 'Rajdhani' } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,243,255,0.05)', borderColor: 'rgba(0,243,255,0.2)' },
                    ticks: { color: '#a0a0a0', font: { family: 'Orbitron' } }
                }
            }
        }
    });
</script>

<!-- ═══ URGENT EXPIRY ALERTS ═══ -->
<?php if (!empty($alerts)): ?>
<div style="margin-bottom:32px">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px">
        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#ef4444;animation:pulse 1.5s infinite"></span>
        <style>@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}</style>
        <h2 style="font-size:1rem;font-weight:700;color:#fff">تنبيهات الانتهاء العاجلة (<?= count($alerts) ?>)</h2>
    </div>

    <div class="nf-card" style="overflow:hidden">
        <div class="nf-table-wrap">
            <table class="nf-table">
                <thead>
                    <tr>
                        <th>الكليان</th>
                        <th>رقم الواتساب</th>
                        <th>الحساب / البروفايل</th>
                        <th>تاريخ الانتهاء</th>
                        <th style="text-align:center">المتبقي</th>
                        <th style="text-align:center">تذكير</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alerts as $a):
                        $d = (int)$a['days_left'];
                        $row_cls = $d < 0 ? 'row-expired' : 'row-urgent';
                        $badge   = $d < 0
                            ? '<span class="badge badge-red">منتهي</span>'
                            : '<span class="badge badge-amber">' . $d . ' أيام</span>';
                        $wa = "https://wa.me/{$a['customer_phone']}?text=" . rawurlencode(
                            "مرحباً {$a['customer_name']}،\nاشتراكك Netflix ينتهي {$a['expiry_date']}.\nللتجديد تواصل معنا 🎬"
                        );
                    ?>
                    <tr class="<?= $row_cls ?>">
                        <td style="font-weight:600;color:#fff"><?= htmlspecialchars($a['customer_name']) ?></td>
                        <td style="font-family:monospace;color:#a0a0a0"><?= htmlspecialchars($a['customer_phone']) ?></td>
                        <td>
                            <div style="color:#a0a0a0;font-size:.78rem" title="<?= htmlspecialchars($a['account_email']) ?>"><?= htmlspecialchars(mb_substr($a['account_email'], 0, 20)) ?></div>
                            <div style="color:#4a4a4a;font-size:.72rem;display:flex;align-items:center;gap:4px">
                                <span><?= htmlspecialchars($a['profile_name'] ?? 'Profile ' . $a['profile_number']) ?></span>
                                <?= $a['pin_code'] ? "<span style='color:#737373'>(PIN:{$a['pin_code']})</span>" : '' ?>
                                <svg onclick="editProfile(<?= $a['profile_id'] ?>, '<?= htmlspecialchars(addslashes($a['profile_name'] ?? 'Profile '.$a['profile_number'])) ?>', '<?= htmlspecialchars(addslashes($a['pin_code'] ?? '')) ?>')" style="width:12px;height:12px;color:var(--primary);cursor:pointer;display:inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </div>
                        </td>
                        <td style="color:#a0a0a0"><?= $a['expiry_date'] ?></td>
                        <td style="text-align:center"><?= $badge ?></td>
                        <td style="text-align:center">
                            <a href="<?= $wa ?>" target="_blank" id="wa-alert-<?= $a['sale_id'] ?>"
                               style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);
                                      background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);
                                      color:#4ade80;font-size:.75rem;font-weight:600;text-decoration:none;
                                      transition:background .15s;white-space:nowrap"
                               onmouseover="this.style.background='rgba(34,197,94,.2)'"
                               onmouseout="this.style.background='rgba(34,197,94,.1)'">
                                <svg style="width:12px;height:12px" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                                تذكير
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ═══ RECENT SALES ═══ -->
<div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
        <div style="display:flex;align-items:center;gap:10px">
            <span style="display:inline-block;width:8px;height:8px;background:var(--accent);box-shadow:0 0 10px var(--accent);animation:pulse 1.5s infinite;clip-path:polygon(0 0, 100% 0, 100% calc(100% - 2px), calc(100% - 2px) 100%, 0 100%);"></span>
            <h2 style="font-size:1rem;font-weight:700;color:#fff;font-family:'Rajdhani',sans-serif;letter-spacing:1px;text-transform:uppercase;">آخر المبيعات <span style="color:var(--primary);font-size:0.7rem;vertical-align:top">(Live Activity)</span></h2>
        </div>
        <a href="sales.php" style="color:#00f3ff;font-size:.8rem;font-weight:600;text-decoration:none;transition:color .15s"
           onmouseover="this.style.color='#5ce6ff'" onmouseout="this.style.color='#00f3ff'">
            عرض الكل ←
        </a>
    </div>

    <?php if (empty($recent_sales)): ?>
    <div class="nf-card">
        <div class="nf-empty">
            <div class="nf-empty-icon">
                <svg style="width:48px;height:48px;margin:0 auto;color:#4a4a4a" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            <div class="nf-empty-title">لا توجد مبيعات بعد</div>
            <div class="nf-empty-sub">ابدأ بتسجيل أول عملية بيع</div>
            <a href="sales.php" class="nf-btn-primary" style="display:inline-block;width:auto;margin-top:16px;padding:10px 24px">
                تسجيل بيعة →
            </a>
        </div>
    </div>
    <?php else: ?>
    <div class="nf-card" style="overflow:hidden">
        <div class="nf-table-wrap">
            <table class="nf-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الكليان</th>
                        <th>الحساب / البروفايل</th>
                        <th>ثمن البيع</th>
                        <th>الربح</th>
                        <th>تاريخ البيع</th>
                        <th>ينتهي</th>
                        <th style="text-align:center">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_sales as $s):
                        $profit = (float)$s['selling_price'] - COST_PER_PROFILE;
                        $days   = (int)$s['days_left'];
                        if ($days < 0) {
                            $badge = '<span class="badge badge-red">منتهي</span>';
                        } elseif ($days <= 3) {
                            $badge = '<span class="badge badge-amber">' . $days . ' أيام</span>';
                        } else {
                            $badge = '<span class="badge badge-green">' . $days . ' يوم</span>';
                        }
                    ?>
                    <tr>
                        <td style="color:#4a4a4a;font-family:monospace"><?= $s['id'] ?></td>
                        <td>
                            <div style="font-weight:600;color:#fff"><?= htmlspecialchars($s['customer_name']) ?></div>
                            <div style="color:#4a4a4a;font-size:.72rem;font-family:monospace"><?= htmlspecialchars($s['customer_phone']) ?></div>
                        </td>
                        <td>
                            <div style="color:#a0a0a0;font-size:.78rem" title="<?= htmlspecialchars($s['account_email']) ?>"><?= htmlspecialchars(mb_substr($s['account_email'], 0, 20)) ?></div>
                            <div style="color:#4a4a4a;font-size:.72rem;display:flex;align-items:center;gap:4px">
                                <span><?= htmlspecialchars($s['profile_name'] ?? 'Profile ' . $s['profile_number']) ?></span>
                                <?= $s['pin_code'] ? "<span style='color:#737373'>(PIN:{$s['pin_code']})</span>" : '' ?>
                                <svg onclick="editProfile(<?= $s['profile_id'] ?>, '<?= htmlspecialchars(addslashes($s['profile_name'] ?? 'Profile '.$s['profile_number'])) ?>', '<?= htmlspecialchars(addslashes($s['pin_code'] ?? '')) ?>')" style="width:12px;height:12px;color:var(--primary);cursor:pointer;display:inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </div>
                        </td>
                        <td style="font-weight:700;color:#fff"><?= number_format($s['selling_price'], 2) ?> DH</td>
                        <td style="font-weight:700;color:<?= $profit >= 0 ? '#4ade80' : '#f87171' ?>"><?= number_format($profit, 2) ?> DH</td>
                        <td style="color:#737373"><?= $s['sale_date'] ?></td>
                        <td style="color:#737373"><?= $s['expiry_date'] ?></td>
                        <td style="text-align:center"><?= $badge ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>



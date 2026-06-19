<?php
// ============================================================
// sales.php — Register Sales & Archive — Netflix Design
// ============================================================
require_once 'config/db.php';

$page_title = 'تسجيل المبيعات — Netflix GS';
$pdo        = get_pdo();
$success    = '';
$error      = '';

// ── HANDLE EDIT PROFILE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_profile') {
    $prof_id   = (int)($_POST['profile_id'] ?? 0);
    $new_name  = trim($_POST['new_name'] ?? '');
    $new_pin   = trim($_POST['new_pin'] ?? '');
    if ($prof_id > 0) {
        $pdo->prepare("UPDATE profiles SET profile_name = ?, pin_code = ? WHERE id = ?")
            ->execute([$new_name, $new_pin === '' ? null : $new_pin, $prof_id]);
        $success = "تم تحديث البروفايل بنجاح!";
    }
}

// ── HANDLE SALE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_sale') {
    $profile_id     = (int)  ($_POST['profile_id']     ?? 0);
    $customer_name  = trim(   $_POST['customer_name']  ?? '');
    $customer_phone = trim(   $_POST['customer_phone'] ?? '');
    $custom_expiry  = trim(   $_POST['expiry_date']    ?? '');

    // Price logic: preset or custom
    $price_mode = $_POST['price_mode'] ?? 'preset';
    $selling_price = ($price_mode === 'custom')
        ? (float)($_POST['custom_price']  ?? 0)
        : (float)($_POST['selling_price'] ?? 0);

    // Validation
    if ($profile_id <= 0) {
        $error = 'يرجى اختيار بروفايل.';
    } elseif (empty($customer_name)) {
        $error = 'يرجى إدخال اسم الكليان.';
    } elseif (empty($customer_phone)) {
        $error = 'يرجى إدخال رقم واتساب.';
    } elseif ($selling_price <= 0) {
        $error = 'يرجى إدخال ثمن بيع صحيح (أكبر من 0).';
    } elseif (!empty($custom_expiry) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $custom_expiry)) {
        $error = 'صيغة تاريخ الانتهاء غير صحيحة.';
    } else {
        $check = $pdo->prepare("SELECT is_sold FROM profiles WHERE id = ?");
        $check->execute([$profile_id]);
        $pr = $check->fetch();

        if (!$pr) {
            $error = 'البروفايل غير موجود.';
        } elseif ($pr['is_sold'] == 1) {
            $error = 'البروفايل تم بيعه مسبقاً.';
        } else {
            try {
                $pdo->beginTransaction();
                $sale_date   = date('Y-m-d');
                $expiry_date = (!empty($custom_expiry) && strtotime($custom_expiry) > strtotime($sale_date))
                    ? $custom_expiry
                    : date('Y-m-d', strtotime('+30 days'));

                $pdo->prepare("
                    INSERT INTO sales (profile_id, customer_name, customer_phone, selling_price, sale_date, expiry_date)
                    VALUES (?,?,?,?,?,?)
                ")->execute([$profile_id, $customer_name, $customer_phone, $selling_price, $sale_date, $expiry_date]);

                $pdo->prepare("UPDATE profiles SET is_sold = 1 WHERE id = ?")->execute([$profile_id]);
                $pdo->commit();

                $profit = $selling_price - COST_PER_PROFILE;
                $success = "تم تسجيل البيعة بنجاح! الانتهاء: <strong>{$expiry_date}</strong> | الربح: <strong>" . number_format($profit, 2) . " DH</strong>";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'خطأ: ' . $e->getMessage();
            }
        }
    }
}

// ── Available profiles ──
$available = $pdo->query("
    SELECT p.id, p.profile_number, p.pin_code, ma.email AS account_email, ma.expiry_date AS account_expiry
    FROM profiles p JOIN main_accounts ma ON p.account_id = ma.id
    WHERE p.is_sold = 0 AND ma.status != 'expired'
    ORDER BY ma.email, p.profile_number
")->fetchAll();

// ── All sales ──
$all_sales_stmt = $pdo->prepare("
    SELECT s.id, s.customer_name, s.customer_phone, s.selling_price,
           s.sale_date, s.expiry_date, ma.email AS account_email,
           p.id AS profile_id, p.profile_number, p.profile_name, p.pin_code,
           DATEDIFF(s.expiry_date, CURDATE()) AS days_left,
           (s.selling_price - :cost) AS net_profit
    FROM sales s
    JOIN profiles p ON s.profile_id = p.id
    JOIN main_accounts ma ON p.account_id = ma.id
    ORDER BY s.sale_date DESC, s.id DESC
");
$all_sales_stmt->execute([':cost' => COST_PER_PROFILE]);
$all_sales = $all_sales_stmt->fetchAll();

include 'includes/header.php';
?>

<!-- Page heading -->
<div style="margin-bottom:28px">
    <h1 class="nf-heading">تسجيل المبيعات</h1>
    <p class="nf-subheading">تقييد بيعة جديدة وعرض أرشيف المبيعات</p>
</div>

<!-- Alerts -->
<?php if ($success): ?>
<div class="nf-alert-success">✅ <?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="nf-alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:360px 1fr;gap:24px;align-items:start">

    <!-- ═══ SALE FORM ═══ -->
    <div class="nf-card" style="padding:24px">
        <h2 style="font-size:.95rem;font-weight:700;color:#fff;margin-bottom:20px;display:flex;align-items:center;gap:10px">
            <span style="width:28px;height:28px;border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);background:rgba(34,197,94,.15);display:flex;align-items:center;justify-content:center;color:#4ade80;font-size:.9rem">💰</span>
            تسجيل بيعة جديدة
        </h2>

        <?php if (empty($available)): ?>
        <div style="text-align:center;padding:32px 12px">
            <div style="font-size:2.5rem;margin-bottom:8px">📦</div>
            <div style="font-weight:700;color:#fbbf24;font-size:.9rem">المخزون فارغ!</div>
            <div style="color:#4a4a4a;font-size:.78rem;margin-top:4px">لا توجد بروفايلات متاحة</div>
            <a href="accounts.php" class="nf-btn-primary" style="display:inline-block;width:auto;margin-top:14px;padding:8px 20px;font-size:.82rem">
                ← إضافة حساب جديد
            </a>
        </div>
        <?php else: ?>
        <form method="POST" action="sales.php" id="sale-form" style="display:flex;flex-direction:column;gap:14px">
            <input type="hidden" name="action" value="add_sale">
            <input type="hidden" name="price_mode" id="price_mode" value="preset">

            <!-- Profile Dropdown -->
            <div>
                <label class="nf-label">اختر البروفايل <span style="color:#00f3ff">*</span>
                    <span style="color:#4a4a4a;font-weight:400">(<?= count($available) ?> متاح)</span>
                </label>
                <select id="profile_id" name="profile_id" required class="nf-select">
                    <option value="" disabled selected>-- اختر بروفايل --</option>
                    <?php foreach ($available as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= (($_POST['profile_id'] ?? '') == $p['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['account_email']) ?> — [<?= htmlspecialchars($p['profile_name'] ?? 'Profile ' . $p['profile_number']) ?>]
                        <?= $p['pin_code'] ? "(PIN:{$p['pin_code']})" : '' ?>
                        [<?= $p['account_expiry'] ?>]
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Customer Name -->
            <div>
                <label class="nf-label">اسم الكليان <span style="color:#00f3ff">*</span></label>
                <input type="text" name="customer_name" required placeholder="محمد أمين"
                       value="<?= htmlspecialchars($_POST['customer_name'] ?? '') ?>" class="nf-input">
            </div>

            <!-- WhatsApp -->
            <div>
                <label class="nf-label">رقم واتساب <span style="color:#00f3ff">*</span></label>
                <input type="tel" name="customer_phone" required placeholder="212612345678"
                       value="<?= htmlspecialchars($_POST['customer_phone'] ?? '') ?>" class="nf-input">
                <div style="font-size:.68rem;color:#4a4a4a;margin-top:3px">الصيغة الدولية: 212XXXXXXXXX</div>
            </div>

            <!-- ═══ SELLING PRICE ═══ -->
            <div>
                <label class="nf-label">ثمن البيع <span style="color:#00f3ff">*</span></label>
                <?php
                    $sel_price = (float)($_POST['selling_price'] ?? 0);
                    $is_custom = ($_POST['price_mode'] ?? 'preset') === 'custom';
                ?>
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:10px" id="price-cards">
                    <?php foreach ([30, 40, 50] as $p):
                        $prof = $p - COST_PER_PROFILE;
                        $active = (!$is_custom && $sel_price == $p);
                    ?>
                    <label style="display:flex;flex-direction:column;align-items:center;justify-content:center;
                                  padding:12px 4px;border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);cursor:pointer;transition:all .15s;
                                  border:1px solid <?= $active ? 'rgba(0,243,255,.5)' : 'rgba(0,243,255,0.2)' ?>;
                                  background:<?= $active ? 'rgba(0,243,255,.08)' : 'transparent' ?>"
                           class="price-label">
                        <input type="radio" name="selling_price" value="<?= $p ?>" class="price-radio"
                               style="display:none" <?= $active ? 'checked' : '' ?>>
                        <span style="font-weight:800;color:#fff;font-size:1.1rem"><?= $p ?></span>
                        <span style="color:#4a4a4a;font-size:.7rem">DH</span>
                        <span style="color:#4ade80;font-size:.68rem;margin-top:3px">+<?= $prof ?> ربح</span>
                    </label>
                    <?php endforeach; ?>

                    <!-- Custom price card -->
                    <label style="display:flex;flex-direction:column;align-items:center;justify-content:center;
                                  padding:12px 4px;border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);cursor:pointer;transition:all .15s;
                                  border:1px solid <?= $is_custom ? 'rgba(168,85,247,.5)' : 'rgba(0,243,255,0.2)' ?>;
                                  background:<?= $is_custom ? 'rgba(168,85,247,.08)' : 'transparent' ?>"
                           class="price-label" id="custom-card">
                        <input type="radio" name="selling_price" value="0" class="price-radio"
                               id="price-custom" style="display:none" <?= $is_custom ? 'checked' : '' ?>>
                        <span style="font-weight:800;color:#c084fc;font-size:1rem">✏️</span>
                        <span style="color:#4a4a4a;font-size:.7rem">يدوي</span>
                        <span style="color:#c084fc;font-size:.68rem;margin-top:3px">حر</span>
                    </label>
                </div>

                <!-- Custom price input -->
                <div id="custom-box" style="display:<?= $is_custom ? 'block' : 'none' ?>">
                    <div style="position:relative">
                        <input type="number" id="custom_price" name="custom_price"
                               min="1" max="9999" step="0.5" placeholder="أدخل الثمن"
                               value="<?= $is_custom ? htmlspecialchars($_POST['custom_price'] ?? '') : '' ?>"
                               class="nf-input" style="padding-left:44px;border-color:rgba(168,85,247,.3)">
                        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);
                                     color:#c084fc;font-size:.78rem;font-weight:700">DH</span>
                    </div>
                    <div id="custom-profit" style="font-size:.72rem;color:#4a4a4a;margin-top:5px"></div>
                </div>
            </div>

            <!-- ═══ EXPIRY DATE — editable ═══ -->
            <div>
                <label class="nf-label">
                    تاريخ انتهاء الاشتراك
                    <span style="color:#4a4a4a;font-weight:400">(تلقائي +30 يوم — قابل للتعديل)</span>
                </label>
                <div style="position:relative">
                    <input type="date" id="expiry_date" name="expiry_date"
                           value="<?= htmlspecialchars($_POST['expiry_date'] ?? date('Y-m-d', strtotime('+30 days'))) ?>"
                           min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                           class="nf-input" style="padding-left:100px">
                    <button type="button" id="reset-expiry"
                            style="position:absolute;left:6px;top:50%;transform:translateY(-50%);
                                   padding:4px 10px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);
                                   border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);color:#737373;font-size:.7rem;font-weight:600;cursor:pointer;
                                   font-family:inherit;transition:all .15s"
                            onmouseover="this.style.borderColor='rgba(0,243,255,.4)';this.style.color='#00f3ff'"
                            onmouseout="this.style.borderColor='rgba(255,255,255,.1)';this.style.color='#737373'">
                        ↺ +30 يوم
                    </button>
                </div>
                <div id="expiry-preview" style="font-size:.72rem;color:#4a4a4a;margin-top:5px"></div>
                <!-- Duration quick selection -->
                <div style="display:flex;gap:6px;margin-top:10px">
                    <button type="button" class="dur-btn" data-months="1">شهر واحد</button>
                    <button type="button" class="dur-btn" data-months="3">3 أشهر</button>
                    <button type="button" class="dur-btn" data-months="6">6 أشهر</button>
                    <button type="button" class="dur-btn" data-months="12">سنة</button>
                </div>
                <style>
                    .dur-btn {
                        flex:1; padding:6px 0; border:1px solid rgba(255,255,255,.1); border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);
                        background:rgba(255,255,255,.03); color:#a0a0a0; font-size:.75rem; font-weight:600; cursor:pointer;
                        transition:all .15s; font-family:inherit;
                    }
                    .dur-btn:hover { background:rgba(0,243,255,.1); border-color:rgba(0,243,255,.3); color:#00f3ff; }
                </style>
            </div>

            <!-- Submit -->
            <button type="submit" id="btn-save-sale"
                    style="background:#16a34a;box-shadow:0 4px 16px rgba(22,163,74,.3)"
                    class="nf-btn-primary"
                    onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
                💾 تسجيل البيعة
            </button>
        </form>
        <?php endif; ?>
    </div>

    <!-- ═══ SALES ARCHIVE ═══ -->
    <div class="nf-card" style="overflow:hidden">
        <div class="nf-section-header">
            <span class="nf-section-title">أرشيف المبيعات (<?= count($all_sales) ?>)</span>
            <?php
                $tot_rev  = array_sum(array_column($all_sales, 'selling_price'));
                $tot_prof = array_sum(array_column($all_sales, 'net_profit'));
            ?>
            <div style="display:flex;gap:16px;font-size:.72rem">
                <span style="color:#737373">المداخيل: <strong style="color:#fff"><?= number_format($tot_rev, 2) ?> DH</strong></span>
                <span style="color:#737373">الأرباح: <strong style="color:#4ade80"><?= number_format($tot_prof, 2) ?> DH</strong></span>
            </div>
        </div>

        <?php if (empty($all_sales)): ?>
        <div class="nf-empty">
            <div class="nf-empty-icon">🧾</div>
            <div class="nf-empty-title">لا توجد مبيعات بعد</div>
        </div>
        <?php else: ?>
        <div class="nf-table-wrap">
            <table class="nf-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الكليان</th>
                        <th>البروفايل</th>
                        <th>الثمن</th>
                        <th>الربح</th>
                        <th>البيع</th>
                        <th>ينتهي</th>
                        <th style="text-align:center">الحالة</th>
                        <th style="text-align:center">واتساب</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_sales as $s):
                        $d  = (int)$s['days_left'];
                        $pr = (float)$s['net_profit'];

                        if ($d < 0) {
                            $rc = 'row-expired';
                            $badge = '<span class="badge badge-red">منتهي</span>';
                        } elseif ($d <= 3) {
                            $rc = 'row-urgent';
                            $badge = '<span class="badge badge-amber">' . $d . ' أيام</span>';
                        } else {
                            $rc = '';
                            $badge = '<span class="badge badge-green">' . $d . ' يوم</span>';
                        }

                        $wa = "https://wa.me/{$s['customer_phone']}?text=" . rawurlencode(
                            "مرحباً {$s['customer_name']}،\nاشتراكك Netflix ينتهي {$s['expiry_date']}.\nللتجديد تواصل معنا 🎬"
                        );
                    ?>
                    <tr class="<?= $rc ?>">
                        <td style="color:#4a4a4a;font-family:monospace"><?= $s['id'] ?></td>
                        <td>
                            <div style="font-weight:600;color:#fff"><?= htmlspecialchars($s['customer_name']) ?></div>
                            <div style="color:#4a4a4a;font-size:.7rem;font-family:monospace"><?= htmlspecialchars($s['customer_phone']) ?></div>
                        </td>
                        <td>
                            <div style="color:#737373;font-size:.78rem" title="<?= htmlspecialchars($s['account_email']) ?>">
                                <?= htmlspecialchars(mb_substr($s['account_email'], 0, 20)) ?>
                            </div>
                            <div style="color:#4a4a4a;font-size:.7rem;display:flex;align-items:center;gap:4px">
                                <span><?= htmlspecialchars($s['profile_name'] ?? 'Profile ' . $s['profile_number']) ?></span>
                                <?= $s['pin_code'] ? "<span style='color:#737373'>(PIN:{$s['pin_code']})</span>" : '' ?>
                                <svg onclick="editProfile(<?= $s['profile_id'] ?>, '<?= htmlspecialchars(addslashes($s['profile_name'] ?? 'Profile '.$s['profile_number'])) ?>', '<?= htmlspecialchars(addslashes($s['pin_code'] ?? '')) ?>')" style="width:12px;height:12px;color:var(--primary);cursor:pointer;display:inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </div>
                        </td>
                        <td style="font-weight:700;color:#fff"><?= number_format($s['selling_price'], 2) ?> DH</td>
                        <td style="font-weight:700;color:<?= $pr >= 0 ? '#4ade80' : '#f87171' ?>"><?= number_format($pr, 2) ?> DH</td>
                        <td style="color:#737373;font-size:.78rem"><?= $s['sale_date'] ?></td>
                        <td style="color:#737373;font-size:.78rem"><?= $s['expiry_date'] ?></td>
                        <td style="text-align:center"><?= $badge ?></td>
                        <td style="text-align:center">
                            <a href="<?= $wa ?>" target="_blank" id="wa-sale-<?= $s['id'] ?>"
                               style="display:inline-flex;align-items:center;justify-content:center;
                                      width:30px;height:30px;border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);
                                      background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.15);
                                      color:#4ade80;text-decoration:none;transition:all .15s"
                               onmouseover="this.style.background='rgba(34,197,94,.2)'"
                               onmouseout="this.style.background='rgba(34,197,94,.1)'">
                                <svg style="width:14px;height:14px" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
    @media(max-width:1024px){
        div[style*="grid-template-columns:360px"]{ grid-template-columns:1fr !important; }
    }
</style>

<script>
const COST = <?= COST_PER_PROFILE ?>;

// ── Price cards logic ──
const labels  = document.querySelectorAll('.price-label');
const radios  = document.querySelectorAll('.price-radio');
const modeIn  = document.getElementById('price_mode');
const cBox    = document.getElementById('custom-box');
const cInput  = document.getElementById('custom_price');
const cProfit = document.getElementById('custom-profit');

function clearCards() {
    labels.forEach(l => {
        l.style.borderColor = 'rgba(0,243,255,0.2)';
        l.style.background  = 'transparent';
    });
}

radios.forEach((r, i) => {
    r.addEventListener('change', () => {
        clearCards();
        const isCustom = r.id === 'price-custom';
        if (isCustom) {
            labels[i].style.borderColor = 'rgba(168,85,247,.5)';
            labels[i].style.background  = 'rgba(168,85,247,.08)';
            modeIn.value = 'custom';
            cBox.style.display = 'block';
            cInput.focus();
            updateCProfit();
        } else {
            labels[i].style.borderColor = 'rgba(0,243,255,.5)';
            labels[i].style.background  = 'rgba(0,243,255,.08)';
            modeIn.value = 'preset';
            cBox.style.display = 'none';
            cProfit.textContent = '';
        }
    });
});

function updateCProfit() {
    const v = parseFloat(cInput.value);
    if (isNaN(v) || v <= 0) { cProfit.textContent = ''; cProfit.style.color = '#4a4a4a'; return; }
    const p = (v - COST).toFixed(2);
    cProfit.textContent = p >= 0 ? '✅ الربح: +' + p + ' DH' : '⚠️ خسارة: ' + p + ' DH';
    cProfit.style.color = p >= 0 ? '#4ade80' : '#f87171';
    cProfit.style.fontWeight = '600';
}
cInput.addEventListener('input', updateCProfit);
if (modeIn.value === 'custom') updateCProfit();

// ── Expiry date ──
const expiryIn  = document.getElementById('expiry_date');
const expiryPrv = document.getElementById('expiry-preview');
const resetBtn  = document.getElementById('reset-expiry');

function autoExpiry() {
    const d = new Date(); d.setDate(d.getDate() + 30);
    return d.toISOString().split('T')[0];
}

function showExpiryPreview() {
    if (!expiryIn.value) { expiryPrv.textContent = ''; return; }
    const today = new Date(); today.setHours(0,0,0,0);
    const exp   = new Date(expiryIn.value);
    const diff  = Math.round((exp - today) / 86400000);
    if (diff < 0) {
        expiryPrv.textContent = '⚠️ التاريخ في الماضي!';
        expiryPrv.style.color = '#f87171';
    } else if (diff === 0) {
        expiryPrv.textContent = '⚠️ ينتهي اليوم';
        expiryPrv.style.color = '#fbbf24';
    } else {
        expiryPrv.textContent = '📅 مدة الاشتراك: ' + diff + ' يوم';
        expiryPrv.style.color = diff < 15 ? '#fbbf24' : '#4ade80';
    }
}

resetBtn.addEventListener('click', () => {
    expiryIn.value = autoExpiry();
    showExpiryPreview();
    expiryIn.style.borderColor = 'rgba(0,243,255,.5)';
    setTimeout(() => expiryIn.style.borderColor = '', 600);
});

expiryIn.addEventListener('input', showExpiryPreview);
showExpiryPreview();

// ── Duration quick buttons ──
document.querySelectorAll('.dur-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const m = parseInt(btn.getAttribute('data-months'));
        const d = new Date();
        // Add months. Handle edge cases if current day is 31 and next month has 30, setMonth handles it but wraps to next month.
        // For simple subscription logic, just setMonth.
        d.setMonth(d.getMonth() + m);
        expiryIn.value = d.toISOString().split('T')[0];
        showExpiryPreview();
        
        expiryIn.style.borderColor = 'rgba(0,243,255,.5)';
        setTimeout(() => expiryIn.style.borderColor = '', 600);
    });
});
</script>

<?php include 'includes/footer.php'; ?>




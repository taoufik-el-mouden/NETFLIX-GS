<?php
// ============================================================
// accounts.php — Stock & Accounts Management — Netflix Design
// ============================================================
require_once 'config/db.php';

$page_title = 'إدارة الحسابات — Netflix GS';
$pdo        = get_pdo();
$success    = '';
$error      = '';

// ── MIGRATION ──
try {
    $pdo->exec("ALTER TABLE profiles ADD COLUMN profile_name VARCHAR(100) DEFAULT NULL AFTER profile_number");
    $pdo->exec("UPDATE profiles SET profile_name = CONCAT('Profile ', profile_number) WHERE profile_name IS NULL");
} catch(PDOException $e) { /* Column probably exists */ }

// ── HANDLE NEW ACCOUNT ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_account') {
    $email         = trim($_POST['email'] ?? '');
    $password      = $_POST['password'] ?? '';
    $purchase_date = $_POST['purchase_date'] ?? '';
    $expiry_date   = $_POST['expiry_date'] ?? '';
    $pins          = $_POST['pin_codes'] ?? [];
    $names         = $_POST['profile_names'] ?? [];

    // Validation — accepts ANY email format (Gmail, Hotmail, Yahoo, Outlook, etc.)
    if (empty($email) || empty($password) || empty($purchase_date) || empty($expiry_date)) {
        $error = 'يرجى ملء جميع الحقول المطلوبة.';
    } elseif (!preg_match('/^.+@.+\..+$/', $email)) {
        // Very permissive regex: just needs something@something.something
        $error = 'صيغة البريد الإلكتروني غير صحيحة.';
    } elseif ($expiry_date <= $purchase_date) {
        $error = 'تاريخ الانتهاء يجب أن يكون بعد تاريخ الشراء.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO main_accounts (email, password, purchase_date, expiry_date)
                VALUES (:email, :password, :purchase_date, :expiry_date)
            ");
            $stmt->execute([
                ':email'         => $email,
                ':password'      => $password,
                ':purchase_date' => $purchase_date,
                ':expiry_date'   => $expiry_date,
            ]);
            $account_id = $pdo->lastInsertId();

            // 3. Create 5 profiles
            $stmt = $pdo->prepare("INSERT INTO profiles (account_id, profile_number, profile_name, pin_code) VALUES (?, ?, ?, ?)");
            for ($i = 1; $i <= 5; $i++) {
                $pin = !empty($pins[$i-1]) ? trim($pins[$i-1]) : null;
                $pname = !empty($names[$i-1]) ? trim($names[$i-1]) : 'Profile ' . $i;
                $stmt->execute([$account_id, $i, $pname, $pin]);
            }

            $pdo->commit();
            $success = "تم إضافة الحساب بنجاح وإنشاء 5 بروفايلات تلقائياً! (#{$account_id})";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
    }
}

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

// ── DELETE ACCOUNT ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_account') {
    $del_id = (int)($_POST['account_id'] ?? 0);
    if ($del_id > 0) {
        try {
            $check = $pdo->prepare("SELECT COUNT(*) FROM profiles WHERE account_id = ? AND is_sold = 1");
            $check->execute([$del_id]);
            $sold = (int)$check->fetchColumn();
            if ($sold > 0) {
                $error = "لا يمكن حذف هذا الحساب — {$sold} بروفايل تم بيعه.";
            } else {
                $pdo->prepare("DELETE FROM main_accounts WHERE id = ?")->execute([$del_id]);
                $success = "تم حذف الحساب وبروفايلاته بنجاح.";
            }
        } catch (PDOException $e) {
            $error = 'خطأ أثناء الحذف: ' . $e->getMessage();
        }
    }
}

// ── FETCH ACCOUNTS ──
$accounts = $pdo->query("
    SELECT ma.*, COUNT(p.id) AS total_profiles,
           SUM(CASE WHEN p.is_sold=0 THEN 1 ELSE 0 END) AS avail,
           SUM(CASE WHEN p.is_sold=1 THEN 1 ELSE 0 END) AS sold
    FROM main_accounts ma
    LEFT JOIN profiles p ON ma.id = p.account_id
    GROUP BY ma.id ORDER BY ma.purchase_date DESC
")->fetchAll();

include 'includes/header.php';
?>

<!-- Page heading -->
<div style="margin-bottom:28px">
    <h1 class="nf-heading">إدارة الحسابات والمخزون</h1>
    <p class="nf-subheading">إضافة حسابات Netflix الرئيسية وعرض حالة البروفايلات</p>
</div>

<!-- Alerts -->
<?php if ($success): ?>
<div class="nf-alert-success">✅ <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="nf-alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:360px 1fr;gap:24px;align-items:start">

    <!-- ═══ ADD ACCOUNT FORM ═══ -->
    <div class="nf-card" style="padding:24px">
        <h2 style="font-size:.95rem;font-weight:700;color:#fff;margin-bottom:20px;display:flex;align-items:center;gap:10px">
            <span style="width:28px;height:28px;border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);background:rgba(0,243,255,.15);display:flex;align-items:center;justify-content:center;color:#00f3ff;font-weight:900;font-size:.9rem">+</span>
            إضافة حساب رئيسي جديد
        </h2>

        <form method="POST" action="accounts.php" id="add-account-form" style="display:flex;flex-direction:column;gap:14px">
            <input type="hidden" name="action" value="add_account">

            <!-- Email — type=text to accept ALL formats -->
            <div>
                <label class="nf-label">البريد الإلكتروني <span style="color:#00f3ff">*</span></label>
                <input type="text" id="email" name="email" required
                       placeholder="example@gmail.com أو example@hotmail.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       class="nf-input">
                <div style="font-size:.7rem;color:#4a4a4a;margin-top:4px">يقبل Gmail, Hotmail, Outlook, Yahoo وأي صيغة أخرى</div>
            </div>

            <!-- Password -->
            <div>
                <label class="nf-label">كلمة المرور <span style="color:#00f3ff">*</span></label>
                <div style="position:relative">
                    <input type="password" id="acc-password" name="password" required
                           placeholder="••••••••••"
                           class="nf-input" style="padding-left:52px">
                    <button type="button" id="toggle-pw"
                            style="position:absolute;left:12px;top:50%;transform:translateY(-50%);
                                   background:none;border:none;color:#737373;cursor:pointer;font-size:.75rem;font-weight:600;font-family:inherit;transition:color .15s"
                            onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#737373'">
                        <span id="pw-text">إظهار</span>
                    </button>
                </div>
            </div>

            <!-- Purchase Date -->
            <div>
                <label class="nf-label">تاريخ الشراء <span style="color:#00f3ff">*</span></label>
                <input type="date" id="purchase_date" name="purchase_date" required
                       value="<?= $_POST['purchase_date'] ?? date('Y-m-d') ?>"
                       class="nf-input">
            </div>

            <!-- Expiry Date -->
            <div>
                <label class="nf-label">
                    تاريخ انتهاء الحساب <span style="color:#00f3ff">*</span>
                    <span style="color:#4a4a4a;font-weight:400"> (تلقائي +30 يوم)</span>
                </label>
                <input type="date" id="expiry_date" name="expiry_date" required
                       value="<?= $_POST['expiry_date'] ?? '' ?>"
                       class="nf-input">
            </div>

            <!-- Names & PIN codes -->
            <div>
                <label class="nf-label">البروفايلات (الأسماء + أكواد PIN) <span style="color:#4a4a4a;font-weight:400">(اختياري)</span></label>
                <div style="display:grid;grid-template-columns:1fr;gap:8px">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <div style="display:flex;gap:6px">
                        <div style="width:25px;text-align:center;color:#4a4a4a;line-height:36px;font-size:.75rem">#<?= $i ?></div>
                        <input type="text" name="profile_names[]" placeholder="اسم البروفايل" class="nf-input" style="flex:1;padding:7px 10px;font-size:.8rem">
                        <input type="text" name="pin_codes[]" maxlength="10" placeholder="PIN" class="nf-input" style="width:80px;text-align:center;padding:7px 4px;font-size:.8rem">
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Cost hint -->
            <div style="padding:10px 14px;background:rgba(0,243,255,.05);border:1px solid rgba(0,243,255,.12);border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);font-size:.75rem;color:#a0a0a0">
                💡 تكلفة الحساب: <strong style="color:#fff"><?= COST_PER_MAIN_ACCOUNT ?> DH</strong>
                — تكلفة البروفايل: <strong style="color:#fff"><?= COST_PER_PROFILE ?> DH</strong>
            </div>

            <!-- Submit -->
            <button type="submit" id="btn-save-account" class="nf-btn-primary" style="margin-top:4px">
                💾 حفظ الحساب وإنشاء 5 بروفايلات
            </button>
        </form>
    </div>

    <!-- ═══ ACCOUNTS LIST ═══ -->
    <div>
        <div class="nf-card" style="overflow:hidden">
            <div class="nf-section-header">
                <span class="nf-section-title">الحسابات المضافة (<?= count($accounts) ?>)</span>
                <span style="font-size:.72rem;color:#4a4a4a">كل حساب = 5 بروفايلات</span>
            </div>

            <?php if (empty($accounts)): ?>
            <div class="nf-empty">
                <div class="nf-empty-icon">📭</div>
                <div class="nf-empty-title">لا توجد حسابات بعد</div>
                <div class="nf-empty-sub">أضف أول حساب Netflix باستخدام الفورم</div>
            </div>
            <?php else: ?>
            <div class="nf-table-wrap">
                <table class="nf-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الإيميل</th>
                            <th>انتهاء الحساب</th>
                            <th style="text-align:center">الكل</th>
                            <th style="text-align:center">متاح</th>
                            <th style="text-align:center">مباع</th>
                            <th style="text-align:center">الحالة</th>
                            <th style="text-align:center">إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accounts as $acc):
                            $av = (int)$acc['avail'];
                            $sd = (int)$acc['sold'];
                            $is_past = $acc['expiry_date'] < date('Y-m-d');
                            $days_exp = (int)(new DateTime())->diff(new DateTime($acc['expiry_date']))->days;

                            if ($is_past) $status_badge = '<span class="badge badge-red">منتهي</span>';
                            elseif ($days_exp <= 7) $status_badge = '<span class="badge badge-amber">قريب</span>';
                            else $status_badge = '<span class="badge badge-green">نشط</span>';

                            $avail_color = $av === 0 ? '#f87171' : ($av <= 2 ? '#fbbf24' : '#4ade80');
                        ?>
                        <tr>
                            <td style="color:#4a4a4a;font-family:monospace">#<?= $acc['id'] ?></td>
                            <td>
                                <div style="font-weight:600;color:#fff;font-size:.82rem"><?= htmlspecialchars($acc['email']) ?></div>
                                <div style="color:#333;font-size:.72rem;font-family:monospace;margin-top:2px"><?= str_repeat('•', min(strlen($acc['password']), 10)) ?></div>
                            </td>
                            <td style="color:#737373;font-size:.82rem"><?= $acc['expiry_date'] ?></td>
                            <td style="text-align:center;color:#a0a0a0;font-weight:700"><?= $acc['total_profiles'] ?></td>
                            <td style="text-align:center;font-weight:800;color:<?= $avail_color ?>"><?= $av ?></td>
                            <td style="text-align:center;color:#737373"><?= $sd ?></td>
                            <td style="text-align:center"><?= $status_badge ?></td>
                            <td style="text-align:center">
                                <?php if ($sd === 0): ?>
                                <form method="POST" style="display:inline"
                                      onsubmit="return confirm('حذف هذا الحساب و5 بروفايلاته؟')">
                                    <input type="hidden" name="action" value="delete_account">
                                    <input type="hidden" name="account_id" value="<?= $acc['id'] ?>">
                                    <button type="submit" class="nf-btn-danger" id="btn-delete-<?= $acc['id'] ?>">🗑️ حذف</button>
                                </form>
                                <?php else: ?>
                                <span style="color:#333;font-size:.72rem">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- ═══ PROFILE DETAILS PER ACCOUNT ═══ -->
        <?php foreach ($accounts as $acc):
            $prof_stmt = $pdo->prepare("
                SELECT p.*, s.customer_name 
                FROM profiles p
                LEFT JOIN sales s ON p.id = s.profile_id
                WHERE p.account_id = ?
                ORDER BY p.profile_number ASC
            ");
            $prof_stmt->execute([$acc['id']]);
            $profiles = $prof_stmt->fetchAll();
        ?>
        <div class="nf-card" style="margin-top:12px;overflow:hidden">
            <div class="nf-section-header">
                <span style="font-size:.82rem;font-weight:600;color:#a0a0a0">
                    📧 <?= htmlspecialchars($acc['email']) ?>
                </span>
                <span style="font-size:.72rem;color:#4a4a4a"><?= $acc['avail'] ?> متاح / <?= $acc['sold'] ?> مباع</span>
            </div>
            <div style="display:grid;grid-template-columns:repeat(5,1fr)">
                <?php foreach ($profiles as $idx => $pr): ?>
                <div style="padding:14px 8px;text-align:center;position:relative;<?= $pr['is_sold'] ? 'opacity:.45;' : '' ?><?= $idx < 4 ? 'border-left:1px solid rgba(255,255,255,.05);' : '' ?>">
                    <div style="font-size:1.1rem;margin-bottom:4px"><?= $pr['is_sold'] ? '<span style="color:#f87171">🔴</span>' : '<span style="color:#4ade80">🟢</span>' ?></div>
                    <div style="font-size:.75rem;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:center;gap:4px">
                        <?= htmlspecialchars($pr['profile_name'] ?? 'Profile ' . $pr['profile_number']) ?>
                        <svg onclick="editProfile(<?= $pr['id'] ?>, '<?= htmlspecialchars(addslashes($pr['profile_name'] ?? 'Profile '.$pr['profile_number'])) ?>', '<?= htmlspecialchars(addslashes($pr['pin_code'] ?? '')) ?>')" style="width:12px;height:12px;color:var(--primary);cursor:pointer" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    </div>
                    <?php if ($pr['pin_code']): ?>
                    <div style="font-size:.68rem;color:#4a4a4a;font-family:monospace;margin-top:3px">PIN: <?= $pr['pin_code'] ?></div>
                    <?php endif; ?>
                    <?php if ($pr['is_sold'] && $pr['customer_name']): ?>
                    <div style="font-size:.68rem;color:#737373;margin-top:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                         title="<?= htmlspecialchars($pr['customer_name']) ?>">
                        <?= htmlspecialchars(mb_substr($pr['customer_name'], 0, 10)) ?>
                    </div>
                    <?php else: ?>
                    <div style="font-size:.68rem;color:#4ade80;margin-top:4px">متاح للبيع</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    @media(max-width:1024px){
        div[style*="grid-template-columns:360px"]{ grid-template-columns:1fr !important; }
    }
</style>

<script>
// Password toggle
const pwInput  = document.getElementById('acc-password');
const pwToggle = document.getElementById('toggle-pw');
const pwText   = document.getElementById('pw-text');
if (pwToggle) {
    pwToggle.addEventListener('click', () => {
        const show = pwInput.type === 'password';
        pwInput.type = show ? 'text' : 'password';
        pwText.textContent = show ? 'إخفاء' : 'إظهار';
    });
}

// Auto-fill expiry date (+30 days from purchase)
const purchaseIn = document.getElementById('purchase_date');
const expiryIn   = document.getElementById('expiry_date');
purchaseIn.addEventListener('change', () => {
    if (purchaseIn.value) {
        const d = new Date(purchaseIn.value);
        d.setDate(d.getDate() + 30);
        expiryIn.value = d.toISOString().split('T')[0];
    }
});
</script>

<?php include 'includes/footer.php'; ?>



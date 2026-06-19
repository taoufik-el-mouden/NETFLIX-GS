<?php
// ============================================================
// expenses.php — Manage Ads and Other Expenses
// ============================================================
require_once 'config/db.php';

$page_title = 'إدارة المصاريف — Netflix GS';
$pdo        = get_pdo();
$success    = '';
$error      = '';

// ── ADD EXPENSE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_expense') {
    $category    = $_POST['category'] ?? 'ads';
    $description = trim($_POST['description'] ?? '');
    $amount      = (float)($_POST['amount'] ?? 0);
    $expense_date= trim($_POST['expense_date'] ?? date('Y-m-d'));

    if (empty($description) || $amount <= 0 || empty($expense_date)) {
        $error = 'يرجى ملء جميع الحقول بشكل صحيح.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO expenses (category, description, amount, expense_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$category, $description, $amount, $expense_date]);
            $success = 'تم تسجيل المصروف بنجاح!';
        } catch (PDOException $e) {
            $error = 'خطأ: ' . $e->getMessage();
        }
    }
}

// ── DELETE EXPENSE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_expense') {
    $del_id = (int)($_POST['expense_id'] ?? 0);
    if ($del_id > 0) {
        try {
            $pdo->prepare("DELETE FROM expenses WHERE id = ?")->execute([$del_id]);
            $success = 'تم حذف المصروف بنجاح.';
        } catch (PDOException $e) {
            $error = 'خطأ أثناء الحذف: ' . $e->getMessage();
        }
    }
}

// ── FETCH EXPENSES ──
$expenses = $pdo->query("SELECT * FROM expenses ORDER BY expense_date DESC, id DESC")->fetchAll();

include 'includes/header.php';
?>

<!-- Page heading -->
<div style="margin-bottom:28px">
    <h1 class="nf-heading">إدارة المصاريف والإعلانات</h1>
    <p class="nf-subheading">تسجيل وتتبع مصاريف الإعلانات (ADS) والمصاريف الأخرى</p>
</div>

<!-- Alerts -->
<?php if ($success): ?>
<div class="nf-alert-success">✅ <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="nf-alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:360px 1fr;gap:24px;align-items:start">

    <!-- ═══ ADD EXPENSE FORM ═══ -->
    <div class="nf-card" style="padding:24px">
        <h2 style="font-size:.95rem;font-weight:700;color:#fff;margin-bottom:20px;display:flex;align-items:center;gap:10px">
            <span style="width:28px;height:28px;border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);background:rgba(234,179,8,.15);display:flex;align-items:center;justify-content:center;color:#eab308;font-size:.9rem">💸</span>
            تسجيل مصروف جديد
        </h2>

        <form method="POST" action="expenses.php" style="display:flex;flex-direction:column;gap:14px">
            <input type="hidden" name="action" value="add_expense">

            <!-- Category -->
            <div>
                <label class="nf-label">نوع المصروف <span style="color:#00f3ff">*</span></label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px" id="cat-cards">
                    <label style="display:flex;flex-direction:column;align-items:center;justify-content:center;
                                  padding:12px;border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);cursor:pointer;transition:all .15s;
                                  border:1px solid rgba(59,130,246,.5);background:rgba(59,130,246,.08)"
                           class="cat-label">
                        <input type="radio" name="category" value="ads" class="cat-radio" style="display:none" checked>
                        <span style="font-weight:800;color:#60a5fa;font-size:1.1rem">📢 ADS</span>
                        <span style="color:#a0a0a0;font-size:.7rem;margin-top:2px">إعلانات</span>
                    </label>
                    <label style="display:flex;flex-direction:column;align-items:center;justify-content:center;
                                  padding:12px;border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%);cursor:pointer;transition:all .15s;
                                  border:1px solid rgba(0,243,255,0.2);background:transparent"
                           class="cat-label">
                        <input type="radio" name="category" value="other" class="cat-radio" style="display:none">
                        <span style="font-weight:800;color:#a0a0a0;font-size:1.1rem">⚙️ أخرى</span>
                        <span style="color:#737373;font-size:.7rem;margin-top:2px">مصاريف عامة</span>
                    </label>
                </div>
            </div>

            <!-- Description -->
            <div>
                <label class="nf-label">الوصف <span style="color:#00f3ff">*</span></label>
                <input type="text" name="description" required placeholder="مثال: حملة فيسبوك أدس للويكاند"
                       value="<?= htmlspecialchars($_POST['description'] ?? '') ?>" class="nf-input">
            </div>

            <!-- Amount -->
            <div>
                <label class="nf-label">المبلغ <span style="color:#00f3ff">*</span></label>
                <div style="position:relative">
                    <input type="number" name="amount" min="0.1" step="0.1" required placeholder="0.00"
                           value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>" class="nf-input" style="padding-left:44px">
                    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#a0a0a0;font-size:.78rem;font-weight:700">DH</span>
                </div>
            </div>

            <!-- Expense Date -->
            <div>
                <label class="nf-label">التاريخ <span style="color:#00f3ff">*</span></label>
                <input type="date" name="expense_date" required
                       value="<?= $_POST['expense_date'] ?? date('Y-m-d') ?>" class="nf-input">
            </div>

            <!-- Submit -->
            <button type="submit" class="nf-btn-primary" style="margin-top:4px; background:#eab308; color:#000; box-shadow:0 4px 16px rgba(234,179,8,.3)"
                    onmouseover="this.style.background='#ca8a04'" onmouseout="this.style.background='#eab308'">
                💾 تسجيل المصروف
            </button>
        </form>
    </div>

    <!-- ═══ EXPENSES ARCHIVE ═══ -->
    <div class="nf-card" style="overflow:hidden">
        <div class="nf-section-header">
            <span class="nf-section-title">أرشيف المصاريف (<?= count($expenses) ?>)</span>
            <?php
                $tot_ads   = array_sum(array_map(fn($e) => $e['category'] === 'ads' ? $e['amount'] : 0, $expenses));
                $tot_other = array_sum(array_map(fn($e) => $e['category'] === 'other' ? $e['amount'] : 0, $expenses));
            ?>
            <div style="display:flex;gap:16px;font-size:.72rem">
                <span style="color:#737373">ADS: <strong style="color:#60a5fa"><?= number_format($tot_ads, 2) ?> DH</strong></span>
                <span style="color:#737373">أخرى: <strong style="color:#a0a0a0"><?= number_format($tot_other, 2) ?> DH</strong></span>
            </div>
        </div>

        <?php if (empty($expenses)): ?>
        <div class="nf-empty">
            <div class="nf-empty-icon">💸</div>
            <div class="nf-empty-title">لا توجد مصاريف مسجلة</div>
        </div>
        <?php else: ?>
        <div class="nf-table-wrap">
            <table class="nf-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>التاريخ</th>
                        <th style="text-align:center">النوع</th>
                        <th>الوصف</th>
                        <th>المبلغ</th>
                        <th style="text-align:center">إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expenses as $ex):
                        $is_ads = $ex['category'] === 'ads';
                        $badge = $is_ads 
                            ? '<span class="badge badge-blue">📢 ADS</span>' 
                            : '<span class="badge" style="background:rgba(255,255,255,.1);color:#a0a0a0;border:1px solid rgba(255,255,255,.2)">⚙️ أخرى</span>';
                    ?>
                    <tr>
                        <td style="color:#4a4a4a;font-family:monospace"><?= $ex['id'] ?></td>
                        <td style="color:#a0a0a0"><?= $ex['expense_date'] ?></td>
                        <td style="text-align:center"><?= $badge ?></td>
                        <td style="color:#fff;font-weight:500"><?= htmlspecialchars($ex['description']) ?></td>
                        <td style="font-weight:700;color:#f87171">-<?= number_format($ex['amount'], 2) ?> DH</td>
                        <td style="text-align:center">
                            <form method="POST" style="display:inline"
                                  onsubmit="return confirm('هل أنت متأكد من حذف هذا المصروف؟')">
                                <input type="hidden" name="action" value="delete_expense">
                                <input type="hidden" name="expense_id" value="<?= $ex['id'] ?>">
                                <button type="submit" class="nf-btn-danger">🗑️ حذف</button>
                            </form>
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
// Category cards selection
const labels = document.querySelectorAll('.cat-label');
const radios = document.querySelectorAll('.cat-radio');

radios.forEach((r, i) => {
    r.addEventListener('change', () => {
        labels.forEach(l => {
            l.style.borderColor = 'rgba(0,243,255,0.2)';
            l.style.background = 'transparent';
        });
        if (r.value === 'ads') {
            labels[i].style.borderColor = 'rgba(59,130,246,.5)';
            labels[i].style.background = 'rgba(59,130,246,.08)';
        } else {
            labels[i].style.borderColor = 'rgba(160,160,160,.5)';
            labels[i].style.background = 'rgba(160,160,160,.08)';
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>



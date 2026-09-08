<?php
// ============================================================
// customers.php — Customer Relationship Management (CRM)
// ============================================================
require_once 'config/auth.php';
require_once 'config/db.php';

require_login();
$page_title = 'سجل الزبناء والعملاء (CRM) — NETFLIX GS';
$pdo        = get_pdo();
$uid        = current_user_id();
$success    = '';
$error      = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'settle_debt') {
    if (!verify_csrf_token()) {
        $error = 'انتهت صلاحية جلسة الأمان (CSRF). يرجى إعادة المحاولة.';
    } else {
        $sale_id = (int)($_POST['sale_id'] ?? 0);
        if ($sale_id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE sales SET payment_status = 'paid' WHERE id = ? AND user_id = ?");
                $stmt->execute([$sale_id, $uid]);
                $success = $stmt->rowCount() > 0 ? 'تم استخلاص الدين وتسجيل المعاملة كمدفوعة بالكامل بنجاح!' : 'المعاملة غير موجودة أو غير مصرح لك بالتعديل عليها.';
            } catch (PDOException $e) {
                $error = 'خطأ في تسوية الدين: ' . $e->getMessage();
            }
        }
    }
}

$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'all';
$query = "SELECT customer_phone, MAX(customer_name) customer_name, COUNT(id) total_orders, SUM(selling_price) total_spent, SUM(CASE WHEN payment_status='unpaid' THEN selling_price ELSE 0 END) unpaid_debt, SUM(CASE WHEN expiry_date >= CURDATE() THEN 1 ELSE 0 END) active_subscriptions, MAX(sale_date) last_sale_date, MIN(sale_date) first_sale_date FROM sales WHERE user_id = :uid";
$params = [':uid' => $uid];
if ($search !== '') { $query .= " AND (customer_name LIKE :s OR customer_phone LIKE :s)"; $params[':s'] = "%$search%"; }
$query .= " GROUP BY customer_phone";
$having = [];
if ($filter === 'vip') $having[] = '(total_orders >= 3 OR total_spent >= 120)';
elseif ($filter === 'active') $having[] = 'active_subscriptions > 0';
elseif ($filter === 'debt') $having[] = 'unpaid_debt > 0';
if ($having) $query .= ' HAVING ' . implode(' AND ', $having);
$query .= ' ORDER BY total_spent DESC, last_sale_date DESC';
$stmt = $pdo->prepare($query); $stmt->execute($params); $customers = $stmt->fetchAll();

$st = $pdo->prepare('SELECT COUNT(DISTINCT customer_phone) FROM sales WHERE user_id = ?'); $st->execute([$uid]); $stat_total_cust = (int)$st->fetchColumn();
$st = $pdo->prepare('SELECT COUNT(*) FROM (SELECT customer_phone FROM sales WHERE user_id = ? GROUP BY customer_phone HAVING COUNT(id) >= 3 OR SUM(selling_price) >= 120) v'); $st->execute([$uid]); $stat_vip_cust = (int)$st->fetchColumn();
$st = $pdo->prepare('SELECT COUNT(*) FROM sales WHERE user_id = ? AND expiry_date >= CURDATE()'); $st->execute([$uid]); $stat_active_sub = (int)$st->fetchColumn();
$st = $pdo->prepare("SELECT COALESCE(SUM(selling_price),0) FROM sales WHERE user_id = ? AND payment_status = 'unpaid'"); $st->execute([$uid]); $stat_total_debt = (float)$st->fetchColumn();

$all_sales_stmt = $pdo->prepare("SELECT s.id,s.customer_name,s.customer_phone,s.selling_price,s.sale_date,s.expiry_date,s.payment_method,s.payment_status,p.profile_number,p.profile_name,p.pin_code,ma.email account_email,ma.password account_password,ma.product_name FROM sales s JOIN profiles p ON s.profile_id=p.id JOIN main_accounts ma ON p.account_id=ma.id WHERE s.user_id=? ORDER BY s.sale_date DESC,s.id DESC");
$all_sales_stmt->execute([$uid]);
$customer_sales_map = [];
foreach ($all_sales_stmt->fetchAll() as $sr) $customer_sales_map[$sr['customer_phone']][] = $sr;
require_once 'includes/header.php';
?>
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;margin-bottom:24px"><div><h1 class="nf-heading">👥 سجل وإدارة الزبناء (CRM)</h1><p class="nf-subheading">قاعدة بيانات العملاء وتتبع المشتريات والزبناء VIP والديون</p></div><a href="sales.php" class="nf-btn-primary" style="width:auto;padding:10px 20px;text-decoration:none">+ تسجيل بيعة جديدة</a></div>
<?php if($success): ?><div class="nf-alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if($error): ?><div class="nf-alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px">
<div class="nf-stat"><div class="nf-stat-label">👥 إجمالي الزبناء</div><div class="nf-stat-value"><?= number_format($stat_total_cust) ?></div></div>
<div class="nf-stat"><div class="nf-stat-label">👑 زبناء VIP</div><div class="nf-stat-value"><?= number_format($stat_vip_cust) ?></div></div>
<div class="nf-stat"><div class="nf-stat-label">🎬 اشتراكات نشطة</div><div class="nf-stat-value"><?= number_format($stat_active_sub) ?></div></div>
<div class="nf-stat"><div class="nf-stat-label">💸 الديون المعلقة</div><div class="nf-stat-value"><?= number_format($stat_total_debt,2) ?> DH</div></div>
</div>
<div class="nf-card" style="padding:16px 20px;margin-bottom:24px"><form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center"><input class="nf-input" style="flex:1;min-width:240px" type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 ابحث بالاسم أو رقم الواتساب..."><div style="display:flex;gap:8px;flex-wrap:wrap"><a href="?filter=all&search=<?= urlencode($search) ?>" class="nf-btn-primary" style="width:auto;padding:7px 14px;text-decoration:none">الكل</a><a href="?filter=vip&search=<?= urlencode($search) ?>" class="nf-btn-primary" style="width:auto;padding:7px 14px;text-decoration:none">👑 VIP</a><a href="?filter=active&search=<?= urlencode($search) ?>" class="nf-btn-primary" style="width:auto;padding:7px 14px;text-decoration:none">🟢 نشطين</a><a href="?filter=debt&search=<?= urlencode($search) ?>" class="nf-btn-primary" style="width:auto;padding:7px 14px;text-decoration:none">💸 ديون</a></div></form></div>
<div class="nf-card" style="overflow:hidden"><div style="overflow:auto"><table class="nf-table"><thead><tr><th>الزبون</th><th>WhatsApp</th><th>الطلبات</th><th>المجموع</th><th>نشطة</th><th>الدين</th><th>آخر بيعة</th><th></th></tr></thead><tbody>
<?php foreach($customers as $c): $phone_clean=preg_replace('/[^0-9]/','',$c['customer_phone']); $has_debt=(float)$c['unpaid_debt']>0; ?><tr><td><strong><?= htmlspecialchars($c['customer_name']) ?></strong></td><td><a href="https://wa.me/<?= $phone_clean ?>" target="_blank" style="color:#25d366;text-decoration:none">💬 <?= htmlspecialchars($c['customer_phone']) ?></a></td><td style="text-align:center"><?= (int)$c['total_orders'] ?></td><td style="text-align:center;color:#4ade80;font-weight:800"><?= number_format((float)$c['total_spent'],2) ?> DH</td><td style="text-align:center"><?= (int)$c['active_subscriptions'] ?> 🟢</td><td style="text-align:center;color:<?= $has_debt?'#ff00e6':'#4ade80' ?>"><?= $has_debt ? number_format((float)$c['unpaid_debt'],2).' DH' : '✅ خالص' ?></td><td style="text-align:center"><?= htmlspecialchars($c['last_sale_date']) ?></td><td><button type="button" class="nf-btn-primary" style="width:auto;padding:5px 12px" onclick='openCustomerDrawer(<?= json_encode($c['customer_phone']) ?>)'>👁️ السجل</button></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<div id="customer-drawer" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.88);z-index:99999;align-items:center;justify-content:center;padding:20px"><div class="nf-card" style="width:100%;max-width:900px;padding:24px;max-height:90vh;overflow:auto"><div style="display:flex;justify-content:space-between"><h3 id="drawer-cust-name">سجل الزبون</h3><button onclick="closeCustomerDrawer()">✕</button></div><div id="drawer-cust-phone" style="color:#25d366;margin:8px 0"></div><div id="drawer-sales-list"></div></div></div>
<script>
const customerSalesData=<?= json_encode($customer_sales_map) ?>;
function openCustomerDrawer(phone){const d=document.getElementById('customer-drawer'),l=document.getElementById('drawer-sales-list'),s=customerSalesData[phone]||[];if(!s.length)return;document.getElementById('drawer-cust-name').textContent='👤 '+s[0].customer_name;document.getElementById('drawer-cust-phone').textContent='WhatsApp: '+phone;let h='<table class="nf-table"><thead><tr><th>المنتج</th><th>الحساب/البروفايل</th><th>البداية</th><th>الانتهاء</th><th>المبلغ</th><th>الدفع</th></tr></thead><tbody>';s.forEach(x=>{h+=`<tr><td>${x.product_name}</td><td>${x.account_email}<br>${x.profile_name||'Profile '+x.profile_number}</td><td>${x.sale_date}</td><td>${x.expiry_date}</td><td>${parseFloat(x.selling_price).toFixed(2)} DH</td><td>${x.payment_status}</td></tr>`});l.innerHTML=h+'</tbody></table>';d.style.display='flex'}
function closeCustomerDrawer(){document.getElementById('customer-drawer').style.display='none'}
document.getElementById('customer-drawer').addEventListener('click',e=>{if(e.target===e.currentTarget)closeCustomerDrawer()});
</script>
<?php include 'includes/footer.php'; ?>
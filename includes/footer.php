<?php
// includes/footer.php — Global Footer
?>
</div><!-- /nf-page -->

<!-- Edit Profile Modal -->
<div id="edit-profile-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); z-index:99999; align-items:center; justify-content:center; padding:20px;">
    <div class="nf-card" style="width:100%; max-width:400px; padding:24px; position:relative; box-shadow:0 10px 40px rgba(0,0,0,0.9); border-color:var(--primary)">
        <h3 style="font-size:1.1rem; font-weight:700; color:#fff; margin-bottom:16px; display:flex; align-items:center; gap:8px;">
            <span style="color:var(--primary)">✏️</span> تعديل البروفايل
        </h3>
        <form method="POST" id="modal-edit-profile-form" style="display:flex; flex-direction:column; gap:14px;">
            <input type="hidden" name="action" value="edit_profile">
            <input type="hidden" name="profile_id" id="modal_ep_id">
            
            <div>
                <label class="nf-label">اسم البروفايل</label>
                <input type="text" name="new_name" id="modal_ep_name" required class="nf-input">
            </div>
            
            <div>
                <label class="nf-label">كود PIN (اختياري)</label>
                <input type="text" name="new_pin" id="modal_ep_pin" placeholder="لا يوجد PIN" class="nf-input">
            </div>
            
            <div style="display:flex; gap:10px; margin-top:8px;">
                <button type="submit" class="nf-btn-primary" style="flex:1; padding:10px; font-size:.85rem;">💾 حفظ التعديل</button>
                <button type="button" onclick="closeEditModal()" style="flex:1; padding:10px; font-size:.85rem; background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.1); color:#fff; border-radius:0; clip-path: polygon(0 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%); cursor:pointer;">❌ إلغاء</button>
            </div>
        </form>
    </div>
</div>

<script>
function editProfile(id, currentName, currentPin) {
    document.getElementById('modal_ep_id').value = id;
    document.getElementById('modal_ep_name').value = currentName;
    document.getElementById('modal_ep_pin').value = currentPin;
    const modal = document.getElementById('edit-profile-modal');
    modal.style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('edit-profile-modal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('edit-profile-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>

<footer style="border-top:1px solid rgba(0,243,255,0.15);margin-top:auto;">
    <div style="max-width:1400px;margin:0 auto;padding:20px 32px;
                display:flex;align-items:center;justify-content:space-between;gap:12px;
                flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:10px;">
            <span style="font-size:1rem;font-weight:900;color:#00f3ff;letter-spacing:-.5px;text-transform:uppercase">
                NETFLIX<span style="color:#fff;font-weight:400;font-size:.8rem">GS</span>
            </span>
            <span style="color:#737373;font-size:.78rem">— نظام إدارة الحسابات الرقمية</span>
        </div>
        <span style="color:#4a4a4a;font-size:.75rem">© <?= date('Y') ?> — جميع الحقوق محفوظة</span>
    </div>
</footer>
</body>
</html>


<?php
defined( 'ABSPATH' ) || exit;
?>
<style>
#pf-subadmin-overlay {
	position: fixed; top: 0; left: 0; width: 100%; height: 100%;
	background: rgba(15, 23, 42, 0.97);
	z-index: 999999;
	display: flex; align-items: center; justify-content: center;
	font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}
#pf-subadmin-popup {
	background: #fff;
	border-radius: 20px;
	padding: 40px;
	max-width: 640px;
	width: 90%;
	max-height: 90vh;
	overflow-y: auto;
	box-shadow: 0 25px 60px rgba(0,0,0,.5);
	text-align: center;
}
#pf-subadmin-popup .popup-icon { font-size: 48px; margin-bottom: 8px; }
#pf-subadmin-popup h2 { font-size: 1.4rem; color: #1e1b4b; margin: 0 0 8px; }
#pf-subadmin-popup .popup-sub { color: #64748b; font-size: 14px; margin-bottom: 24px; }
#pf-subadmin-popup .popup-body { text-align: left; background: #f8fafc; border-radius: 12px; padding: 20px; margin-bottom: 24px; font-size: 14px; line-height: 1.7; color: #334155; }
#pf-subadmin-popup .popup-body ul { padding-left: 20px; margin: 8px 0; }
#pf-subadmin-popup .popup-body li { margin-bottom: 6px; }
#pf-subadmin-popup .popup-link { display: block; text-align: center; margin: 12px 0; color: #4f46e5; font-size: 13px; }
#pf-subadmin-popup .popup-check { display: flex; align-items: flex-start; gap: 12px; margin: 20px 0; text-align: left; background: #fef3c7; padding: 16px; border-radius: 10px; }
#pf-subadmin-popup .popup-check input { width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px; accent-color: #4f46e5; }
#pf-subadmin-popup .popup-check label { font-size: 14px; font-weight: 600; color: #1e1b4b; cursor: pointer; line-height: 1.5; }
#pf-subadmin-confirm-btn {
	width: 100%; padding: 16px;
	background: #4f46e5; color: #fff;
	border: none; border-radius: 10px;
	font-size: 16px; font-weight: 700;
	cursor: pointer; transition: all .2s;
	opacity: .4; pointer-events: none;
}
#pf-subadmin-confirm-btn.ready { opacity: 1; pointer-events: all; }
#pf-subadmin-confirm-btn.ready:hover { background: #4338ca; transform: translateY(-1px); }
#pf-subadmin-logout { display: block; margin-top: 12px; font-size: 13px; color: #94a3b8; cursor: pointer; background: none; border: none; width: 100%; }
</style>

<div id="pf-subadmin-overlay">
	<div id="pf-subadmin-popup">
		<div class="popup-icon">👑</div>

		<?php if ( $lang === 'en' ) : ?>
		<h2>Sub-Admin Commitment Required</h2>
		<p class="popup-sub">Welcome, <strong><?php echo esc_html( $user->display_name ); ?></strong>. Before accessing the admin panel, please read and confirm the Sub-Admin Code of Conduct.</p>
		<div class="popup-body">
			<strong>Key obligations you're committing to:</strong>
			<ul>
				<li>🔒 Never leak or share member personal data</li>
				<li>📍 Only moderate your assigned sections</li>
				<li>⚖️ Always act objectively and impartially</li>
				<li>🔐 Maintain 2FA on your admin account</li>
				<li>📝 Log all significant moderation actions</li>
			</ul>
			<strong>Violations will result in immediate removal and potential legal action.</strong>
		</div>
		<a href="<?php echo esc_url( $conduct_url ); ?>" target="_blank" rel="noopener" class="popup-link">
			📄 Read full Sub-Admin Code of Conduct →
		</a>
		<div class="popup-check">
			<input type="checkbox" id="pf-subadmin-agree">
			<label for="pf-subadmin-agree">I have read and fully commit to the Sub-Admin Code of Conduct and Data Security requirements. I understand violations have legal consequences.</label>
		</div>
		<button type="button" id="pf-subadmin-confirm-btn">✅ I Commit to Data Security and Sub-Admin Rules</button>
		<button type="button" id="pf-subadmin-logout">← Log out and return later</button>

		<?php else : ?>
		<h2>Yêu cầu Xác nhận Cam kết Sub-Admin</h2>
		<p class="popup-sub">Chào mừng, <strong><?php echo esc_html( $user->display_name ); ?></strong>. Trước khi vào trang điều hành, vui lòng đọc và xác nhận Quy tắc nghề nghiệp Sub-Admin.</p>
		<div class="popup-body">
			<strong>Những cam kết chính bạn đang xác nhận:</strong>
			<ul>
				<li>🔒 Không rò rỉ hoặc chia sẻ dữ liệu cá nhân thành viên</li>
				<li>📍 Chỉ quản lý trong section được phân công</li>
				<li>⚖️ Luôn hành động khách quan, công tâm</li>
				<li>🔐 Duy trì 2FA cho tài khoản quản trị</li>
				<li>📝 Ghi log tất cả các hành động quản trị quan trọng</li>
			</ul>
			<strong>Vi phạm sẽ bị bãi nhiệm ngay lập tức và chịu trách nhiệm pháp lý.</strong>
		</div>
		<a href="<?php echo esc_url( $conduct_url ); ?>" target="_blank" rel="noopener" class="popup-link">
			📄 Đọc đầy đủ Quy tắc Sub-Admin →
		</a>
		<div class="popup-check">
			<input type="checkbox" id="pf-subadmin-agree">
			<label for="pf-subadmin-agree">Tôi đã đọc đầy đủ và cam kết tuân thủ hoàn toàn Quy tắc nghề nghiệp và Bảo mật dành cho Sub-Admin. Tôi hiểu rằng vi phạm có thể dẫn đến hậu quả pháp lý.</label>
		</div>
		<button type="button" id="pf-subadmin-confirm-btn">✅ Tôi cam kết bảo mật dữ liệu và tuân thủ quy tắc Sub-Admin</button>
		<button type="button" id="pf-subadmin-logout">← Đăng xuất và quay lại sau</button>
		<?php endif; ?>
	</div>
</div>

<script>
(function() {
	var checkbox = document.getElementById('pf-subadmin-agree');
	var btn      = document.getElementById('pf-subadmin-confirm-btn');
	var logout   = document.getElementById('pf-subadmin-logout');
	var overlay  = document.getElementById('pf-subadmin-overlay');
	var saving   = <?php echo $lang === 'en' ? "'⏳ Saving...'" : "'⏳ Đang lưu...'"; ?>;

	if (!checkbox || !btn || !overlay) return;

	document.body.style.overflow = 'hidden';

	checkbox.addEventListener('change', function() {
		btn.classList.toggle('ready', this.checked);
	});

	btn.addEventListener('click', function() {
		if (!checkbox.checked) return;
		btn.textContent = saving;
		btn.style.opacity = '0.7';

		fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams({
				action: 'pf_accept_subadmin_terms',
				_ajax_nonce: '<?php echo esc_js( $nonce ); ?>'
			})
		})
		.then(function(r) { return r.json(); })
		.then(function(data) {
			if (data.success) {
				overlay.style.transition = 'opacity .4s';
				overlay.style.opacity = '0';
				setTimeout(function() {
					overlay.remove();
					document.body.style.overflow = '';
				}, 400);
			}
		});
	});

	if (logout) {
		logout.addEventListener('click', function() {
			window.location.href = '<?php echo esc_url( wp_logout_url( home_url() ) ); ?>';
		});
	}
})();
</script>

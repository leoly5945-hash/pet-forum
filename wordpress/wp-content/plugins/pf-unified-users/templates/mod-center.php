<?php
defined( 'ABSPATH' ) || exit;

$pending_posts = PF_Mod_Center::get_pending_posts( $forum_ids );
$warned_users  = PF_Mod_Center::get_warned_users();
$my_logs       = PF_Mod_Center::get_my_logs( $uid );
$pinned_topics = PF_Mod_Center::get_pinned_topics( $forum_ids );

$role_labels = [
	PF_Constants::ROLE_GLOBAL_ADMIN => '🌐 Global Sub-Admin',
	PF_Constants::ROLE_DOG_MOD      => '🐕 Dog Mod',
	PF_Constants::ROLE_CAT_MOD      => '🐈 Cat Mod',
	PF_Constants::ROLE_BIRD_MOD     => '🐦 Bird Mod',
	PF_Constants::ROLE_MARKET_MOD   => '🛒 Market Mod',
];

$active_tab = sanitize_key( wp_unslash( $_GET['tab'] ?? 'pending' ) );
$mod_center_url = get_permalink();
?>

<div class="pf-mc-wrap">

	<div class="pf-mc-header">
		<div class="pf-mc-identity">
			<div class="pf-mc-avatar"><?php echo get_avatar( $uid, 48 ); ?></div>
			<div>
				<h1><?php echo esc_html( $user->display_name ); ?></h1>
				<span class="pf-mc-role-badge"><?php echo esc_html( $role_labels[ $role ] ?? ( $role ?: 'Super Admin' ) ); ?></span>
			</div>
		</div>
		<div class="pf-mc-stats">
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'pending', $mod_center_url ) ); ?>" class="pf-mc-stat <?php echo $active_tab === 'pending' ? 'active' : ''; ?>">
				<span class="pf-mc-stat-num <?php echo $pending_count > 0 ? 'has-items' : ''; ?>">
					<?php echo (int) $pending_count; ?>
				</span>
				<span>📋 Chờ duyệt</span>
			</a>
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'warned', $mod_center_url ) ); ?>" class="pf-mc-stat <?php echo $active_tab === 'warned' ? 'active' : ''; ?>">
				<span class="pf-mc-stat-num <?php echo $warned_count > 0 ? 'has-items' : ''; ?>">
					<?php echo (int) $warned_count; ?>
				</span>
				<span>⚠️ Vi phạm</span>
			</a>
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'log', $mod_center_url ) ); ?>" class="pf-mc-stat <?php echo $active_tab === 'log' ? 'active' : ''; ?>">
				<span class="pf-mc-stat-num"><?php echo (int) $log_count; ?></span>
				<span>📊 Log của tôi</span>
			</a>
			<a href="<?php echo esc_url( add_query_arg( 'tab', 'pinned', $mod_center_url ) ); ?>" class="pf-mc-stat <?php echo $active_tab === 'pinned' ? 'active' : ''; ?>">
				<span class="pf-mc-stat-num"><?php echo count( $pinned_topics ); ?></span>
				<span>📌 Pin/Lock</span>
			</a>
		</div>
	</div>

	<?php if ( $active_tab === 'pending' ) : ?>
	<div class="pf-mc-section">
		<h2>📋 Bài viết chờ duyệt <span class="pf-mc-count"><?php echo count( $pending_posts ); ?></span></h2>

		<?php if ( empty( $pending_posts ) ) : ?>
			<div class="pf-mc-empty">✅ Không có bài nào chờ duyệt!</div>
		<?php else : ?>
			<div class="pf-mc-list">
			<?php foreach ( $pending_posts as $post ) : ?>
			<div class="pf-mc-card" id="post-card-<?php echo (int) $post['postid']; ?>">
				<div class="pf-mc-card-meta">
					<span class="pf-mc-forum-tag"><?php echo esc_html( $post['forum_title'] ?? '' ); ?></span>
					<span class="pf-mc-time"><?php echo esc_html( $post['created'] ?? '' ); ?></span>
				</div>
				<div class="pf-mc-card-author">
					👤 <strong><?php echo esc_html( $post['display_name'] ?? 'Unknown' ); ?></strong>
					<small><?php echo esc_html( $post['user_email'] ?? '' ); ?></small>
				</div>
				<div class="pf-mc-card-body">
					<?php echo wp_kses_post( wp_trim_words( wp_strip_all_tags( $post['body'] ?? '' ), 40 ) ); ?>
				</div>
				<div class="pf-mc-card-actions">
					<button type="button" class="pf-mc-btn pf-mc-btn-approve"
						onclick="pfMC.approvePost(<?php echo (int) $post['postid']; ?>)">
						✅ Duyệt
					</button>
					<button type="button" class="pf-mc-btn pf-mc-btn-delete"
						onclick="pfMC.deletePost(<?php echo (int) $post['postid']; ?>)">
						🗑 Xóa
					</button>
					<a href="<?php echo esc_url( home_url( '/forum/?wpforo=' . (int) ( $post['topicid'] ?? 0 ) ) ); ?>"
						target="_blank" rel="noopener" class="pf-mc-btn pf-mc-btn-view">
						👁 Xem
					</a>
				</div>
			</div>
			<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<?php elseif ( $active_tab === 'warned' ) : ?>
	<div class="pf-mc-section">
		<h2>⚠️ Thành viên đang bị cảnh báo <span class="pf-mc-count"><?php echo count( $warned_users ); ?></span></h2>

		<?php if ( empty( $warned_users ) ) : ?>
			<div class="pf-mc-empty">✅ Không có thành viên nào bị cảnh báo!</div>
		<?php else : ?>
		<table class="pf-mc-table">
			<thead>
				<tr><th>Thành viên</th><th>Cấp độ</th><th>Lý do</th><th>Số lần</th><th>Hành động</th></tr>
			</thead>
			<tbody>
			<?php
			foreach ( $warned_users as $wu ) :
				$wlevel      = (int) get_user_meta( $wu->ID, PF_Constants::META_WARN_LEVEL, true );
				$wreason     = get_user_meta( $wu->ID, PF_Constants::META_WARN_REASON, true );
				$wcount      = (int) get_user_meta( $wu->ID, PF_Constants::META_WARN_COUNT, true );
				$wbanned     = get_user_meta( $wu->ID, PF_Constants::META_BANNED, true );
				$level_icons = [ 1 => '⚠️', 2 => '🔴', 3 => '🔒', 4 => '🚫' ];
				$level_names = [ 1 => 'Cảnh báo', 2 => 'Cảnh cáo', 3 => 'Hạn chế', 4 => 'Bị khóa' ];
				?>
			<tr id="warn-row-<?php echo (int) $wu->ID; ?>">
				<td>
					<strong><?php echo esc_html( $wu->display_name ); ?></strong><br>
					<small style="color:#94a3b8"><?php echo esc_html( $wu->user_email ); ?></small>
				</td>
				<td>
					<span class="pf-warn-badge pf-warn-<?php echo (int) $wlevel; ?>">
						<?php echo esc_html( ( $level_icons[ $wlevel ] ?? '' ) . ' ' . ( $level_names[ $wlevel ] ?? $wlevel ) ); ?>
					</span>
				</td>
				<td style="font-size:13px"><?php echo esc_html( $wreason ); ?></td>
				<td style="text-align:center"><?php echo (int) $wcount; ?> lần</td>
				<td>
					<?php if ( $wlevel < PF_Constants::WARN_BANNED && ! $wbanned ) : ?>
					<button type="button" class="pf-mc-btn pf-mc-btn-warn"
						onclick="pfMC.warnUser(<?php echo (int) $wu->ID; ?>, <?php echo min( $wlevel + 1, PF_Constants::WARN_CAUTION ); ?>)">
						⬆️ Leo thang
					</button>
					<?php endif; ?>
					<?php if ( $wlevel < PF_Constants::WARN_WARNING ) : ?>
					<button type="button" class="pf-mc-btn pf-mc-btn-delete"
						onclick="pfMC.warnUser(<?php echo (int) $wu->ID; ?>, 1)">
						⚠️ Cảnh báo
					</button>
					<?php endif; ?>
				</td>
			</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>

	<?php elseif ( $active_tab === 'log' ) : ?>
	<div class="pf-mc-section">
		<h2>📊 Lịch sử hành động của tôi</h2>
		<?php
		$action_labels = array_merge(
			PF_Logger::action_labels(),
			[
				'unpin_topic'  => '📌 Bỏ ghim',
				'unlock_topic' => '🔓 Mở khóa',
			]
		);

		if ( empty( $my_logs ) ) :
			?>
			<div class="pf-mc-empty">Chưa có hành động nào được ghi lại.</div>
		<?php else : ?>
		<table class="pf-mc-table">
			<thead><tr><th>Hành động</th><th>Đối tượng</th><th>Lý do</th><th>Thời gian</th></tr></thead>
			<tbody>
			<?php foreach ( $my_logs as $log ) : ?>
			<tr>
				<td><?php echo esc_html( $action_labels[ $log['action'] ] ?? $log['action'] ); ?></td>
				<td style="font-size:13px"><?php echo esc_html( $log['target_info'] ?: '#' . (int) $log['target_id'] ); ?></td>
				<td style="font-size:13px;color:#64748b"><?php echo esc_html( $log['reason'] ?? '' ); ?></td>
				<td style="font-size:12px;color:#94a3b8;white-space:nowrap"><?php echo esc_html( $log['created_at'] ); ?></td>
			</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
	</div>

	<?php elseif ( $active_tab === 'pinned' ) : ?>
	<div class="pf-mc-section">
		<h2>📌 Topic đang được ghim / khóa</h2>
		<?php if ( empty( $pinned_topics ) ) : ?>
			<div class="pf-mc-empty">Không có topic nào đang ghim hoặc khóa.</div>
		<?php else : ?>
		<div class="pf-mc-list">
		<?php foreach ( $pinned_topics as $topic ) : ?>
		<div class="pf-mc-card" id="topic-card-<?php echo (int) $topic['topicid']; ?>">
			<div class="pf-mc-card-meta">
				<span class="pf-mc-forum-tag"><?php echo esc_html( $topic['forum_title'] ?? '' ); ?></span>
				<?php if ( ( $topic['type'] ?? '' ) === 'sticky' ) : ?>
					<span class="pf-mc-tag-pin">📌 Đang ghim</span>
				<?php endif; ?>
				<?php if ( ! empty( $topic['closed'] ) ) : ?>
					<span class="pf-mc-tag-lock">🔒 Đang khóa</span>
				<?php endif; ?>
			</div>
			<div class="pf-mc-card-body">
				<strong><?php echo esc_html( $topic['title'] ?? '' ); ?></strong><br>
				<small>Bởi: <?php echo esc_html( $topic['display_name'] ?? '' ); ?></small>
			</div>
			<div class="pf-mc-card-actions">
				<?php if ( ( $topic['type'] ?? '' ) === 'sticky' ) : ?>
				<button type="button" class="pf-mc-btn pf-mc-btn-warn"
					onclick="pfMC.unpinTopic(<?php echo (int) $topic['topicid']; ?>)">
					📌 Bỏ ghim
				</button>
				<?php endif; ?>
				<?php if ( ! empty( $topic['closed'] ) ) : ?>
				<button type="button" class="pf-mc-btn pf-mc-btn-approve"
					onclick="pfMC.unlockTopic(<?php echo (int) $topic['topicid']; ?>)">
					🔓 Mở khóa
				</button>
				<?php endif; ?>
				<a href="<?php echo esc_url( home_url( '/forum/?wpforo=' . (int) $topic['topicid'] ) ); ?>"
					target="_blank" rel="noopener" class="pf-mc-btn pf-mc-btn-view">👁 Xem</a>
			</div>
		</div>
		<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<div id="pf-mc-reason-modal" style="display:none">
		<div class="pf-mc-reason-overlay">
			<div class="pf-mc-reason-box">
				<h3 id="pf-mc-reason-title">Nhập lý do</h3>
				<textarea id="pf-mc-reason-input" rows="3" placeholder="Mô tả lý do xử lý..."></textarea>
				<div class="pf-mc-reason-actions">
					<button type="button" id="pf-mc-reason-confirm" class="pf-mc-btn pf-mc-btn-approve">✅ Xác nhận</button>
					<button type="button" onclick="pfMC.closeReason()" class="pf-mc-btn">Hủy</button>
				</div>
			</div>
		</div>
	</div>

</div>

<?php
defined( 'ABSPATH' ) || exit;
/** @var string $status_filter */
/** @var array $pending_vets */
/** @var string|false $msg */
?>
<div class="wrap">
  <h1>🩺 Duyệt Bác sĩ Thú y / Chuyên gia</h1>

  <?php if ( $msg ) : ?>
  <div class="notice notice-success is-dismissible"><p><?php echo esc_html( $msg ); ?></p></div>
  <?php endif; ?>

  <nav class="nav-tab-wrapper" style="margin-bottom:20px">
    <?php foreach ( [ 'pending' => '⏳ Chờ duyệt', 'approved' => '✅ Đã duyệt', 'rejected' => '❌ Từ chối' ] as $s => $label ) : ?>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=pf-vet-approval&vet_status=' . $s ) ); ?>"
       class="nav-tab <?php echo $status_filter === $s ? 'nav-tab-active' : ''; ?>">
      <?php echo esc_html( $label ); ?>
    </a>
    <?php endforeach; ?>
  </nav>

  <?php if ( empty( $pending_vets ) ) : ?>
  <p style="color:#94a3b8">Không có hồ sơ nào.</p>
  <?php else : ?>
  <table class="widefat striped" style="max-width:1100px">
    <thead>
      <tr>
        <th>Họ tên</th><th>Email</th><th>Điện thoại</th>
        <th>Nơi công tác</th><th>Chuyên khoa</th><th>Bằng cấp</th><th>Đăng ký</th><th>Hành động</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ( $pending_vets as $vet ) :
        $phone     = get_user_meta( $vet->ID, 'pf_phone', true );
        $workplace = get_user_meta( $vet->ID, 'pf_workplace', true );
        $specialty = get_user_meta( $vet->ID, 'pf_specialty', true );
        $reg_at    = get_user_meta( $vet->ID, 'pf_registered_at', true );
        $cert_url  = wp_nonce_url(
          admin_url( 'admin-post.php?action=pf_view_cert&user_id=' . $vet->ID ),
          'pf_view_cert_' . $vet->ID
        );
        ?>
      <tr>
        <td><strong><?php echo esc_html( $vet->display_name ); ?></strong></td>
        <td><?php echo esc_html( $vet->user_email ); ?></td>
        <td><?php echo esc_html( $phone ); ?></td>
        <td><?php echo esc_html( $workplace ); ?></td>
        <td><?php echo esc_html( $specialty ?: '—' ); ?></td>
        <td>
          <?php if ( get_user_meta( $vet->ID, 'pf_certificate_path', true ) ) : ?>
          <a href="<?php echo esc_url( $cert_url ); ?>" class="button button-small" target="_blank">📎 Xem</a>
          <?php else : ?>—<?php endif; ?>
        </td>
        <td style="font-size:12px;color:#94a3b8"><?php echo esc_html( $reg_at ); ?></td>
        <td>
          <?php if ( $status_filter === 'pending' ) : ?>
          <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
            <?php wp_nonce_field( 'pf_approve_vet_' . $vet->ID ); ?>
            <input type="hidden" name="action" value="pf_approve_vet">
            <input type="hidden" name="user_id" value="<?php echo (int) $vet->ID; ?>">
            <input type="submit" class="button button-primary" value="✅ Duyệt">
          </form>
          <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;margin-left:6px">
            <?php wp_nonce_field( 'pf_reject_vet_' . $vet->ID ); ?>
            <input type="hidden" name="action" value="pf_reject_vet">
            <input type="hidden" name="user_id" value="<?php echo (int) $vet->ID; ?>">
            <input type="submit" class="button" value="❌ Từ chối"
              onclick="return confirm('Từ chối hồ sơ của <?php echo esc_js( $vet->display_name ); ?>?')">
          </form>
          <?php elseif ( $status_filter === 'approved' ) : ?>
            <span style="color:green;font-weight:600">✔ Verified Vet</span>
          <?php else : ?>
            <span style="color:#dc2626">✗ Đã từ chối</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

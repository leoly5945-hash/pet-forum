<?php
defined( 'ABSPATH' ) || exit;

include PFU_DIR . 'templates/partials/register-i18n.php';
?>

<div class="pf-split-register">
  <?php include PFU_DIR . 'templates/partials/lang-switcher.php'; ?>

  <?php if ( ! empty( $_GET['pf_status'] ) && sanitize_text_field( wp_unslash( $_GET['pf_status'] ) ) === 'vet_pending' ) : ?>
  <div class="pf-pending-notice">
    <div class="pf-pending-icon">⏳</div>
    <h3><?php echo esc_html( $t( 'vet_pending_title' ) ); ?></h3>
    <p><?php echo wp_kses_post( $t( 'vet_pending_msg' ) ); ?></p>
  </div>
  <?php else : ?>

  <div class="pf-type-selector" id="pfTypeSelector">
    <h2 class="pf-register-headline"><?php echo esc_html( $t( 'split_heading' ) ); ?></h2>
    <p class="pf-register-sub"><?php echo esc_html( $t( 'split_subheading' ) ); ?></p>

    <div class="pf-type-cards">
      <a href="<?php echo esc_url( $member_url ); ?>" class="pf-type-card pf-type-card--link" id="pfCardMember">
        <div class="pf-type-icon">🐾</div>
        <h3><?php echo esc_html( $t( 'member_title' ) ); ?></h3>
        <p><?php echo esc_html( $t( 'member_desc' ) ); ?></p>
        <ul class="pf-type-perks">
          <li>✓ <?php echo esc_html( $t( 'member_feat_1' ) ); ?></li>
          <li>✓ <?php echo esc_html( $t( 'member_feat_2' ) ); ?></li>
          <li>✓ <?php echo esc_html( $t( 'member_feat_3' ) ); ?></li>
        </ul>
        <span class="pf-select-type-btn"><?php echo esc_html( $t( 'member_btn' ) ); ?></span>
      </a>

      <a href="<?php echo esc_url( $vet_url ); ?>" class="pf-type-card pf-type-card--link pf-type-card--vet" id="pfCardVet">
        <div class="pf-type-badge"><?php echo esc_html( $t( 'vet_badge' ) ); ?></div>
        <div class="pf-type-icon">🩺</div>
        <h3><?php echo esc_html( $t( 'vet_title' ) ); ?></h3>
        <p><?php echo esc_html( $t( 'vet_desc' ) ); ?></p>
        <ul class="pf-type-perks">
          <li>✓ <?php echo esc_html( $t( 'vet_feat_1' ) ); ?></li>
          <li>✓ <?php echo esc_html( $t( 'vet_feat_2' ) ); ?></li>
          <li>✓ <?php echo esc_html( $t( 'vet_feat_3' ) ); ?></li>
        </ul>
        <span class="pf-select-type-btn pf-btn-vet"><?php echo esc_html( $t( 'vet_btn' ) ); ?></span>
      </a>
    </div>
  </div>

  <?php endif; ?>

</div>

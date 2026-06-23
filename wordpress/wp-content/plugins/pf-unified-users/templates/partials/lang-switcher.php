<?php
defined( 'ABSPATH' ) || exit;
?>
<div class="pf-lang-switcher">
	<a href="<?php echo esc_url( $lang_url_vi ); ?>" class="<?php echo $pf_lang === 'vi' ? 'active' : ''; ?>">🇻🇳 VI</a>
	<span>|</span>
	<a href="<?php echo esc_url( $lang_url_en ); ?>" class="<?php echo $pf_lang === 'en' ? 'active' : ''; ?>">🇺🇸 EN</a>
</div>

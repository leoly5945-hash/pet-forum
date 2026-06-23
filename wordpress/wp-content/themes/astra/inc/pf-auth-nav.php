<?php
/**
 * Header auth menu — đăng ký / đăng nhập (guest) hoặc tài khoản (logged in).
 *
 * @package Astra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function petforum_auth_nav_lang() {
	if ( class_exists( 'PF_I18n' ) ) {
		return PF_I18n::menu_lang();
	}

	return ( isset( $_COOKIE['pf_lang'] ) && sanitize_key( wp_unslash( $_COOKIE['pf_lang'] ) ) === 'en' ) ? 'en' : 'vi';
}

function petforum_auth_nav_t( $key ) {
	$lang = petforum_auth_nav_lang();

	if ( class_exists( 'PF_I18n' ) ) {
		return PF_I18n::get( $key, $lang );
	}

	$fallback = [
		'menu_account'          => [ 'vi' => 'Tài khoản', 'en' => 'Account' ],
		'menu_section_register' => [ 'vi' => 'ĐĂNG KÝ', 'en' => 'REGISTER' ],
		'menu_btn_member'       => [ 'vi' => '🐾 Đăng ký thành viên', 'en' => '🐾 Register as Member' ],
		'menu_btn_vet'            => [ 'vi' => '🩺 Đăng ký bác sĩ', 'en' => '🩺 Register as Vet' ],
		'menu_btn_login'          => [ 'vi' => '🔑 Đăng nhập', 'en' => '🔑 Log in' ],
		'menu_community'          => [ 'vi' => '💬 Diễn đàn', 'en' => '💬 Community' ],
		'menu_profile'            => [ 'vi' => '👤 Hồ sơ', 'en' => '👤 Profile' ],
		'menu_logout'             => [ 'vi' => '🚪 Đăng xuất', 'en' => '🚪 Log out' ],
	];

	return $fallback[ $key ][ $lang ] ?? $fallback[ $key ]['vi'] ?? $key;
}

function petforum_auth_nav_lang_url( $lang ) {
	if ( class_exists( 'PF_I18n' ) ) {
		return PF_I18n::lang_switch_url( $lang );
	}

	return add_query_arg( 'lang', $lang, remove_query_arg( 'lang' ) );
}

function petforum_auth_nav_urls() {
	$lang = petforum_auth_nav_lang();

	if ( class_exists( 'PF_Register_V2' ) ) {
		$register_member = PF_Register_V2::register_url( 'member', $lang );
		$register_vet    = PF_Register_V2::register_url( 'vet', $lang );
	} else {
		$register_member = add_query_arg( [ 'type' => 'member', 'lang' => $lang ], home_url( '/register/' ) );
		$register_vet    = add_query_arg( [ 'type' => 'vet', 'lang' => $lang ], home_url( '/register/' ) );
	}

	return [
		'register_member' => $register_member,
		'register_vet'    => $register_vet,
		'login'           => wp_login_url( home_url( '/' ) ),
		'community'       => home_url( '/community/' ),
		'profile'         => home_url( '/profile/' ),
		'logout'          => wp_logout_url( home_url( '/' ) ),
		'lang_vi'         => petforum_auth_nav_lang_url( 'vi' ),
		'lang_en'         => petforum_auth_nav_lang_url( 'en' ),
	];
}

function petforum_auth_nav_lang_switcher() {
	$lang = petforum_auth_nav_lang();
	$urls = petforum_auth_nav_urls();
	?>
	<div class="pf-lang-switch-mini">
		<a href="<?php echo esc_url( $urls['lang_vi'] ); ?>" class="<?php echo $lang === 'vi' ? 'is-active' : ''; ?>">🇻🇳 VI</a>
		<span>|</span>
		<a href="<?php echo esc_url( $urls['lang_en'] ); ?>" class="<?php echo $lang === 'en' ? 'is-active' : ''; ?>">🇺🇸 EN</a>
	</div>
	<?php
}

function petforum_auth_nav_markup() {
	if ( is_admin() ) {
		return '';
	}

	$urls = petforum_auth_nav_urls();
	$lang = petforum_auth_nav_lang();
	ob_start();

	if ( is_user_logged_in() ) {
		$user = wp_get_current_user();
		?>
		<div class="pf-auth-nav pf-auth-nav--logged-in" id="pfAuthNav">
			<button type="button" class="pf-auth-btn" aria-expanded="false" aria-controls="pfAuthMenu">
				<span class="pf-auth-avatar"><?php echo get_avatar( $user->ID, 28, '', '', [ 'class' => 'pf-auth-avatar-img' ] ); ?></span>
				<span class="pf-auth-label"><?php echo esc_html( wp_trim_words( $user->display_name, 2, '' ) ); ?></span>
				<span class="pf-auth-caret">▾</span>
			</button>
			<div class="pf-auth-menu" id="pfAuthMenu" hidden>
				<?php petforum_auth_nav_lang_switcher(); ?>
				<a class="pf-auth-item" href="<?php echo esc_url( $urls['community'] ); ?>"><?php echo esc_html( petforum_auth_nav_t( 'menu_community' ) ); ?></a>
				<a class="pf-auth-item" href="<?php echo esc_url( $urls['profile'] ); ?>"><?php echo esc_html( petforum_auth_nav_t( 'menu_profile' ) ); ?></a>
				<div class="pf-auth-divider"></div>
				<a class="pf-auth-item pf-auth-item--logout" href="<?php echo esc_url( $urls['logout'] ); ?>"><?php echo esc_html( petforum_auth_nav_t( 'menu_logout' ) ); ?></a>
			</div>
		</div>
		<?php
	} else {
		?>
		<div class="pf-auth-nav" id="pfAuthNav">
			<button type="button" class="pf-auth-btn pf-auth-btn--guest" aria-expanded="false" aria-controls="pfAuthMenu">
				<span class="pf-auth-icon">👤</span>
				<span class="pf-auth-label"><?php echo esc_html( petforum_auth_nav_t( 'menu_account' ) ); ?></span>
				<span class="pf-auth-caret">▾</span>
			</button>
			<div class="pf-auth-menu" id="pfAuthMenu" hidden>
				<span class="pf-auth-menu-heading"><?php echo esc_html( petforum_auth_nav_t( 'menu_section_register' ) ); ?></span>
				<a class="pf-auth-item" href="<?php echo esc_url( $urls['register_member'] ); ?>">
					<?php echo esc_html( petforum_auth_nav_t( 'menu_btn_member' ) ); ?>
				</a>
				<a class="pf-auth-item" href="<?php echo esc_url( $urls['register_vet'] ); ?>">
					<?php echo esc_html( petforum_auth_nav_t( 'menu_btn_vet' ) ); ?>
				</a>
				<?php petforum_auth_nav_lang_switcher(); ?>
				<div class="pf-auth-divider"></div>
				<a class="pf-auth-item pf-auth-item--login" href="<?php echo esc_url( $urls['login'] ); ?>">
					<?php echo esc_html( petforum_auth_nav_t( 'menu_btn_login' ) ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	return ob_get_clean();
}

function petforum_render_header_auth_nav() {
	echo petforum_auth_nav_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function petforum_enqueue_auth_nav_assets() {
	if ( is_admin() ) {
		return;
	}

	$theme_dir = get_template_directory();
	$theme_uri = get_template_directory_uri();
	$css_path  = $theme_dir . '/css/pf-auth-nav.css';
	$js_path   = $theme_dir . '/js/pf-auth-nav.js';

	if ( file_exists( $css_path ) ) {
		wp_enqueue_style( 'pf-auth-nav', $theme_uri . '/css/pf-auth-nav.css', array( 'pf-nature-theme' ), filemtime( $css_path ) );
	}
	if ( file_exists( $js_path ) ) {
		wp_enqueue_script( 'pf-auth-nav', $theme_uri . '/js/pf-auth-nav.js', [], filemtime( $js_path ), true );
	}
}

add_action( 'wp_enqueue_scripts', 'petforum_enqueue_auth_nav_assets', 20 );
add_action( 'astra_main_header_bar_top', 'petforum_render_header_auth_nav', 20 );
add_action( 'astra_mobile_header_bar_top', 'petforum_render_header_auth_nav', 20 );

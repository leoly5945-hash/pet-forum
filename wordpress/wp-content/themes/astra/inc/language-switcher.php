<?php
/**
 * Language Switcher + Universal Translation Layer cho Pet Forum.
 *
 * @package Astra
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render universal translation switcher markup.
 *
 * @return string
 */
function petforum_language_switcher() {
	$languages = array(
		'original' => array( 'flag' => '📄', 'name' => 'Gốc / Original' ),
		'vi'       => array( 'flag' => '🇻🇳', 'name' => 'Tiếng Việt' ),
		'en'       => array( 'flag' => '🇺🇸', 'name' => 'English' ),
		'ru'       => array( 'flag' => '🇷🇺', 'name' => 'Русский' ),
		'zh-CN'    => array( 'flag' => '🇨🇳', 'name' => '简体中文' ),
		'zh-TW'    => array( 'flag' => '🇹🇼', 'name' => '繁體中文' ),
		'ja'       => array( 'flag' => '🇯🇵', 'name' => '日本語' ),
		'ko'       => array( 'flag' => '🇰🇷', 'name' => '한국어' ),
		'th'       => array( 'flag' => '🇹🇭', 'name' => 'ภาษาไทย' ),
		'fr'       => array( 'flag' => '🇫🇷', 'name' => 'Français' ),
		'de'       => array( 'flag' => '🇩🇪', 'name' => 'Deutsch' ),
		'es'       => array( 'flag' => '🇪🇸', 'name' => 'Español' ),
	);

	ob_start();
	?>
	<div class="pf-universal-translate" id="pfUniversalTranslate">
		<button type="button" class="pf-universal-btn" onclick="PfTranslate.toggleMenu()" aria-label="<?php esc_attr_e( 'Select Language', 'astra' ); ?>">
			<span id="pfCurrentFlag">🌐</span>
			<span id="pfCurrentName"><?php esc_html_e( 'Ngôn ngữ', 'astra' ); ?></span> ▾
		</button>
		<div class="pf-lang-menu" id="pfLangMenu">
			<?php foreach ( $languages as $code => $lang ) : ?>
				<a class="pf-lang-item" href="#" data-lang="<?php echo esc_attr( $code ); ?>">
					<?php echo esc_html( $lang['flag'] . ' ' . $lang['name'] ); ?>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

add_shortcode( 'lang_switcher', 'petforum_language_switcher' );

/**
 * Google Translate hidden widget (optional site-wide fallback).
 */
function petforum_google_translate_head() {
	?>
	<script type="text/javascript">
	function googleTranslateElementInit() {
		new google.translate.TranslateElement({
			pageLanguage: 'vi',
			includedLanguages: 'vi,en,ru,zh-CN,zh-TW,ja,ko,th,fr,de,es,id,ms',
			layout: google.translate.TranslateElement.InlineLayout.NONE,
			autoDisplay: false
		}, 'google_translate_element');
	}
	</script>
	<script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
	<div id="google_translate_element" style="display:none"></div>
	<?php
}
add_action( 'wp_head', 'petforum_google_translate_head', 5 );

/**
 * Inject switcher into Astra header.
 */
function petforum_render_header_language_switcher() {
	echo petforum_language_switcher(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'astra_main_header_bar_top', 'petforum_render_header_language_switcher', 15 );
add_action( 'astra_mobile_header_bar_top', 'petforum_render_header_language_switcher', 15 );

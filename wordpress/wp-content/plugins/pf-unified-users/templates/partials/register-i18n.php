<?php
defined( 'ABSPATH' ) || exit;

$lang             = PF_I18n::current_lang();
$pf_lang          = $lang;
$t                = function ( string $key ) use ( $lang ): string {
	return PF_I18n::get( $key, $lang );
};
$pf_register_type = sanitize_key( wp_unslash( $_GET['type'] ?? '' ) );
$register_base    = PF_I18n::register_page_url( '', $lang );
$member_url       = PF_I18n::register_page_url( 'member', $lang );
$vet_url          = PF_I18n::register_page_url( 'vet', $lang );
$lang_url_vi      = PF_I18n::register_page_url( $pf_register_type, 'vi' );
$lang_url_en      = PF_I18n::register_page_url( $pf_register_type, 'en' );

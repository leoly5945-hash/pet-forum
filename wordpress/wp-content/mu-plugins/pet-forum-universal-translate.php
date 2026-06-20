<?php
/**
 * Enqueue Universal Translation Layer for wpForo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_enqueue_scripts',
	static function () {
		$js_path = get_template_directory() . '/js/pf-universal-translate.js';
		if ( ! file_exists( $js_path ) ) {
			return;
		}

		wp_enqueue_script(
			'pf-universal-translate',
			get_template_directory_uri() . '/js/pf-universal-translate.js',
			array(),
			filemtime( $js_path ),
			true
		);
	},
	20
);

add_action(
	'wp_footer',
	static function () {
		?>
		<div id="pfTranslateStatus" class="pf-translate-status" aria-live="polite">
			<span id="pfStatusText"></span>
		</div>
		<style>
		.pf-universal-translate { position: relative; display: inline-block; margin-left: 12px; vertical-align: middle; z-index: 10001; }
		.pf-universal-btn {
			background: rgba(255,255,255,0.12);
			border: 1px solid rgba(255,255,255,0.25);
			color: #fff;
			padding: 6px 14px;
			border-radius: 20px;
			cursor: pointer;
			font-size: 0.85rem;
			font-weight: 500;
			line-height: 1.4;
			display: inline-flex;
			align-items: center;
			gap: 6px;
		}
		.pf-universal-btn:hover { background: rgba(255,255,255,0.22); }
		.pf-lang-menu {
			display: none;
			position: absolute;
			right: 0;
			top: calc(100% + 8px);
			background: #1e293b;
			border: 1px solid #334155;
			border-radius: 10px;
			min-width: 200px;
			max-height: 320px;
			overflow-y: auto;
			box-shadow: 0 8px 24px rgba(0,0,0,0.3);
			z-index: 10002;
		}
		.pf-lang-menu.open { display: block; }
		.pf-lang-item {
			display: block;
			padding: 10px 16px;
			color: #e2e8f0 !important;
			text-decoration: none !important;
			font-size: 0.88rem;
			cursor: pointer;
			transition: background 0.15s;
		}
		.pf-lang-item:hover, .pf-lang-item.selected { background: #334155; color: #fff !important; }
		.pf-translate-status {
			display: none;
			position: fixed;
			bottom: 24px;
			left: 50%;
			transform: translateX(-50%);
			background: #0f172a;
			color: #e2e8f0;
			padding: 10px 20px;
			border-radius: 24px;
			font-size: 0.88rem;
			box-shadow: 0 4px 20px rgba(0,0,0,0.25);
			z-index: 99999;
		}
		.pf-translate-status.show { display: block; }
		.pf-post-translated {
			margin: 12px 0;
			padding: 14px 16px;
			background: #f0f9ff;
			border-left: 3px solid #0ea5e9;
			border-radius: 0 8px 8px 0;
			font-size: 0.95rem;
			line-height: 1.6;
			color: #1e293b;
		}
		.pf-lang-badge {
			display: inline-block;
			font-size: 0.75rem;
			font-weight: 700;
			background: #dbeafe;
			color: #1e40af;
			padding: 2px 8px;
			border-radius: 10px;
			margin-bottom: 6px;
		}
		.pf-post-original-toggle {
			display: inline-block;
			font-size: 0.78rem;
			color: #0ea5e9;
			cursor: pointer;
			margin: 4px 0 8px;
		}
		.pf-post-original-toggle:hover { text-decoration: underline; }
		.pf-original-text {
			display: none;
			margin: 8px 0;
			padding: 10px 12px;
			background: #f8fafc;
			border: 1px dashed #cbd5e1;
			border-radius: 6px;
			font-size: 0.88rem;
			color: #64748b;
			white-space: pre-wrap;
		}
		.pf-original-text.show { display: block; }
		.ast-header-break-point .pf-universal-translate { margin: 8px 0 0; }
		#pfBilingualToolbar {
			background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
			border: 1px solid #bae6fd;
			border-radius: 10px;
			padding: 12px 16px;
			margin-bottom: 16px;
			display: flex;
			align-items: center;
			gap: 10px;
			flex-wrap: wrap;
		}
		#pfBilingualToolbar label {
			font-size: 0.83rem;
			font-weight: 700;
			color: #0369a1;
			white-space: nowrap;
		}
		#pfBilingualToolbar select {
			padding: 6px 12px;
			border-radius: 8px;
			border: 1px solid #7dd3fc;
			font-size: 0.83rem;
			background: #fff;
			color: #1e293b;
			cursor: pointer;
			flex: 1;
			min-width: 160px;
			max-width: 220px;
		}
		#pfAddTransBtn {
			padding: 7px 16px;
			border-radius: 20px;
			border: none;
			background: #0ea5e9;
			color: #fff;
			font-size: 0.83rem;
			font-weight: 700;
			cursor: pointer;
			white-space: nowrap;
			transition: background 0.2s;
		}
		#pfAddTransBtn:hover { background: #0284c7; }
		#pfAddTransBtn:disabled { background: #94a3b8; cursor: wait; }
		#pfBilingualToolbar .pf-hint {
			font-size: 0.76rem;
			color: #64748b;
			width: 100%;
			margin-top: 2px;
		}
		#pfTransResult {
			margin-top: 10px;
			padding: 10px 14px;
			background: #fffbeb;
			border: 1px solid #fcd34d;
			border-radius: 8px;
			font-size: 0.85rem;
			color: #1e293b;
			display: none;
			width: 100%;
		}
		#pfTransResult.show { display: block; }
		</style>
		<?php
	},
	5
);

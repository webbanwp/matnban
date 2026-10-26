<?php
/**
 * Plugin Name:       Matnban
 * Plugin URI:        https://webbanwp.ir/
 * Description:       Persian writing & typography assistant — automatic Persian spelling and half-space (ZWNJ) correction, Arabic character cleanup, readability analysis and a site-wide writing health report.
 * Version:           1.0.2
 * Requires at least: 5.8
 * Requires PHP:      8.1
 * Author:            وب‌بان
 * Text Domain:       matnban
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 *
 * @package Matnban
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MATNBAN_VERSION', '1.0.2' );
define( 'MATNBAN_FILE', __FILE__ );
define( 'MATNBAN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MATNBAN_URL', plugin_dir_url( __FILE__ ) );
define( 'MATNBAN_OPTION', 'matnban_settings' );

function matnban_bootstrap() {
	require_once MATNBAN_DIR . 'includes/class-matnban-normalizer.php';
	require_once MATNBAN_DIR . 'includes/class-matnban-html-walker.php';
	require_once MATNBAN_DIR . 'includes/class-matnban-filename.php';
	require_once MATNBAN_DIR . 'includes/class-matnban-readability.php';
	require_once MATNBAN_DIR . 'includes/class-matnban-settings.php';
	require_once MATNBAN_DIR . 'includes/class-matnban-editor.php';
	require_once MATNBAN_DIR . 'includes/class-matnban-content.php';
	require_once MATNBAN_DIR . 'includes/class-matnban-media.php';
	require_once MATNBAN_DIR . 'includes/class-matnban-report.php';
		Matnban::instance();
}
add_action( 'plugins_loaded', 'matnban_bootstrap' );

function matnban_deactivate() {
	wp_clear_scheduled_hook( 'matnban_weekly_scan' );
	wp_clear_scheduled_hook( 'matnban_scan_continue' );
	delete_option( 'matnban_cron_progress' );
}
register_deactivation_hook( __FILE__, 'matnban_deactivate' );

final class Matnban {
	private static $instance = null;

	private $settings;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		if ( false === get_option( MATNBAN_OPTION ) ) {
			add_option( MATNBAN_OPTION, self::default_settings() );
		}
		$this->settings = self::get_settings();


		new MATNBAN_Settings();
		new MATNBAN_Editor();
		new MATNBAN_Content();
		new MATNBAN_Media();
		new MATNBAN_Report();
	}


	public static function default_settings() {
		return array(
			'rules'             => MATNBAN_Normalizer::default_rules(),
			'post_types'        => array( 'post', 'page', 'product' ),
			'auto_fix_on_save'  => 0,
			'auto_fix_title'    => 1,
			'fix_on_display'    => 0,
			'fix_comments'      => 0,
			'media_rename'      => 1,
			'media_alt'         => 1,
			'readability_panel' => 1,
			'text_align'        => '',
			'align_scope'       => 'content',
			'align_editor'      => 1,
			'align_force'       => 0,
			'justify_selector'  => '',
			'report_schedule'   => 'weekly',
			'report_email'      => 0,
		);
	}

	public static function get_settings() {
		$saved = get_option( MATNBAN_OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		$defaults = self::default_settings();
		if ( ! empty( $saved['justify_text'] ) && empty( $saved['text_align'] ) ) {
			$saved['text_align'] = 'justify';
		}
		$settings = array_merge( $defaults, $saved );
		$settings['rules'] = array_merge( $defaults['rules'], isset( $saved['rules'] ) && is_array( $saved['rules'] ) ? $saved['rules'] : array() );
		return $settings;
	}

	public static function get( $key, $default = null ) {
		$s = self::get_settings();
		return isset( $s[ $key ] ) ? $s[ $key ] : $default;
	}

	public static function normalizer() {
		static $n = null;
		if ( null === $n ) {
			$n = new MATNBAN_Normalizer( self::get( 'rules', array() ) );
		}
		return $n;
	}

	public static function walker() {
		static $w = null;
		if ( null === $w ) {
			$w = new MATNBAN_Html_Walker( self::normalizer() );
		}
		return $w;
	}



}

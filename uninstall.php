<?php
/**
 * حذف افزونهٔ رایگان متن‌بان.
 * اگر متن‌بان پرو نصب باشد، تنظیمات و گزارش مشترک پاک نمی‌شود
 * (پرو همان آپشن‌ها را می‌خواند؛ حذف رایگان نباید داده‌های پرو را نابود کند).
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$matnban_keep_shared = false;
if ( defined( 'WP_PLUGIN_DIR' ) ) {
	foreach ( array( 'matnban-pro/matnban.php', 'matnban-pro-pro/matnban.php' ) as $matnban_pro_file ) {
		if ( file_exists( WP_PLUGIN_DIR . '/' . $matnban_pro_file ) ) {
			$matnban_keep_shared = true;
			break;
		}
	}
}
if ( function_exists( 'is_plugin_active' ) ) {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
	if ( is_plugin_active( 'matnban-pro/matnban.php' ) ) {
		$matnban_keep_shared = true;
	}
}

if ( ! $matnban_keep_shared ) {
	delete_option( 'matnban_settings' );
	delete_option( 'matnban_report' );
	delete_option( 'matnban_scan_progress' );
	delete_option( 'matnban_cron_progress' );
	delete_metadata( 'post', 0, '_matnban_scan', '', true );
	delete_metadata( 'post', 0, '_matnban_in_summary', '', true );
}

wp_clear_scheduled_hook( 'matnban_weekly_scan' );
wp_clear_scheduled_hook( 'matnban_scan_continue' );

if ( ! $matnban_keep_shared ) {
	$GLOBALS['wpdb']->query( "DELETE FROM {$GLOBALS['wpdb']->options} WHERE option_name LIKE '\_transient\_matnban\_orig\_%' OR option_name LIKE '\_transient\_timeout\_matnban\_orig\_%'" );
}

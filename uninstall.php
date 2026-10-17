<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
delete_option( 'matnban_settings' );
delete_option( 'matnban_report' );
delete_option( 'matnban_scan_progress' );
delete_option( 'matnban_cron_progress' );
delete_metadata( 'post', 0, '_matnban_scan', '', true );
delete_metadata( 'post', 0, '_matnban_in_summary', '', true );
wp_clear_scheduled_hook( 'matnban_weekly_scan' );
wp_clear_scheduled_hook( 'matnban_scan_continue' );

$GLOBALS['wpdb']->query( "DELETE FROM {$GLOBALS['wpdb']->options} WHERE option_name LIKE '\_transient\_matnban\_orig\_%' OR option_name LIKE '\_transient\_timeout\_matnban\_orig\_%'" );

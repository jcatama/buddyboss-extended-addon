<?php
/**
 * Plugin Name: BuddyBoss Extended Add-on
 * Plugin URI:  https://github.com/jcatama/buddyboss-extended-addon
 * Description: 🚀 All-in-one enhancement plugin that improves WordPress & BuddyBoss integration.
 * Author:      John Albert Catama
 * Author URI:  https://github.com/jcatama
 * Version:     1.2.2
 * Text Domain: buddyboss-extended-addon
 * Domain Path: /languages/
 * License:     GPL2
 *
 * @package BuddyBossExtendedAddon
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! defined( 'BBEA_VERSION' ) ) {
	define( 'BBEA_VERSION', 'v1.2.2' );
}

if ( ! defined( 'BBEA_PLUGIN_DIR' ) ) {
	define( 'BBEA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// WP Activation hook.
register_activation_hook( __FILE__, 'on_bbea_activation' );
/**
 * Check for BuddyBoss dependency.
 *
 * @return void
 */
function on_bbea_activation() {
	if ( ! is_plugin_active( 'buddyboss-platform/bp-loader.php' ) && is_admin() ) {

		wp_die(
			sprintf(
				/* translators: %s: plugin home url */
				__(
					'Sorry, but this plugin requires BuddyBoss Platform Plugin to be installed and active.<br><a href="%1$s">&laquo; Return to Plugins</a>',
					'buddyboss-extended-addon'
				),
				admin_url( 'plugins.php' )
			)
		);
		deactivate_plugins( plugin_basename( __FILE__ ) );

	}
}

// Plugin action link hook.
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'bbea_plugin_page_settings_link' );
/**
 * Add setting page link.
 *
 * @param array $links wp links.
 *
 * @return string, url link
 */
function bbea_plugin_page_settings_link( $links ) {

	$links[] = sprintf(
		/* translators: %s: plugin setting page url, %s: settings */
		__(
			'<a href="%1$s">%2$s</a>',
			'buddyboss-extended-addon'
		),
		admin_url( 'options-general.php?page=bbea' ),
		'Settings'
	);

	return $links;

}

// Hook scripts and styles.
add_action( 'wp_enqueue_scripts', 'bbea_scripts_styles', 9999 );
/**
 * Enqueues scripts and styles.
 *
 * @return void
 */
function bbea_scripts_styles() {
	/**
	 * Scripts and Styles loaded by the parent theme can be unloaded if needed
	 * using wp_deregister_script or wp_deregister_style.
	 *
	 * See the WordPress Codex for more information about those functions:
	 * http://codex.wordpress.org/Function_Reference/wp_deregister_script
	 * http://codex.wordpress.org/Function_Reference/wp_deregister_style
	 */

	wp_enqueue_style( 'bbea-css', plugins_url( 'assets/css/index.css', __FILE__ ), [], BBEA_VERSION );

	wp_localize_script( 'jquery', 'bbea', [ 'ajaxurl' => admin_url( 'admin-ajax.php' ) ] );

}

// Check if bbea_option_all_unsubscribe is enabled.
if ( 1 === absint( get_option( 'bbea_option_all_unsubscribe' ) ) ) :

	// BuddyPress registration hooks.
	add_action( 'bbp_register_theme_packages', 'bbea_register_plugin_template' );

	/**
	 * Register BBPress overrides.
	 *
	 * @return void
	 */
	function bbea_register_plugin_template() {
		/**
		 * This function registers a new template stack location.
		 *
		 * @param string $location_callback
		 * @param int $priority
		 *
		 * @return void
		 */
		bbp_register_template_stack( 'bbea_get_template_path', 12 );
	}

	/**
	 * Return custom bbpress overrides.
	 *
	 * @return string
	 */
	function bbea_get_template_path() {
		return BBEA_PLUGIN_DIR . 'includes/templates/bbpress/';
	}

endif;


// Plugin hook.
add_action( 'plugins_loaded', 'bbea_init' );
/**
 * Register plugin hook.
 *
 * @return void
 */
function bbea_init() {

	// Load local translations.
	load_plugin_textdomain( 'buddyboss-extended-addon', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	// Include classes.
	require_once BBEA_PLUGIN_DIR . 'includes/classes/class-index.php';

}

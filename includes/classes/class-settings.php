<?php
/**
 * Setting page for plugin dashboard
 *
 * @package    BuddyBossExtendedAddon
 * @subpackage includes/classes
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BBEA_Setting' ) ) :

	/**
	 * BBEA_Setting
	 */
	class BBEA_Setting {

		/**
		 * Instance of BBEA_Setting
		 *
		 * @var BBEA_Setting
		 */
		protected static $instance;

		/**
		 * Initialize admin setting dashboard.
		 */
		public function __construct() {
			add_action( 'admin_init', [ $this, 'register_settings' ] );
			add_action( 'admin_menu', [ $this, 'register_options_page' ] );
			add_action( 'admin_head', [ $this, 'settings_style' ] );
		}

		/**
		 * Add option value.
		 *
		 * @return void
		 */
		public function register_settings() {
			add_option( 'bbea_option_auto_subscribe', 1 );
			add_option( 'bbea_option_discussion_types', 'publish,private,inherit,closed' );
			add_option( 'bbea_option_subscribe', 1 );
			add_option( 'bbea_option_all_unsubscribe', 1 );
			add_option( 'bbea_subscribe_icon', 'bb-icon-bell-plus' );
			add_option( 'bbea_unsubscribe_icon', 'bb-icon-bell-off' );
			register_setting( 'bbea_options_group', 'bbea_option_auto_subscribe' );
			register_setting( 'bbea_options_group', 'bbea_option_discussion_types' );
			register_setting( 'bbea_options_group', 'bbea_option_subscribe' );
			register_setting( 'bbea_options_group', 'bbea_option_all_unsubscribe' );
			register_setting( 'bbea_options_group', 'bbea_subscribe_icon' );
			register_setting( 'bbea_options_group', 'bbea_unsubscribe_icon' );
		}

		/**
		 * Create admin menu page
		 *
		 * @return void
		 */
		public function register_options_page() {
			add_options_page(
				'Buddyboss Extended Add-on',
				'Buddyboss Extended Add-on',
				'manage_options',
				'bbea',
				[ $this, 'bbea_options_page' ]
			);
		}

		/**
		 * Add inline style to setting page.
		 *
		 * @return void
		 */
		public function settings_style() {
			echo '<style>#bbea-settings input[type="text"] {width: 300px;}</style>';
		}

		/**
		 * Render the setting page.
		 *
		 * @return void
		 */
		public function bbea_options_page() { ?>
			<div id="bbea-settings">
			<h2>Buddyboss Extended Add-on</h2>
			<form method="post" action="options.php">
						<?php settings_fields( 'bbea_options_group' ); ?>
				<table style="text-align: left;">
				<tr valign="top">
					<th scope="row">
					<label for="bbea_option_auto_subscribe">Enable auto forum & discussion subscription on group?: </label>
					</th>
					<td>
					<select name="bbea_option_auto_subscribe" id="bbea_option_auto_subscribe">
						<option value="1"
								<?php echo absint( get_option( 'bbea_option_auto_subscribe' ) ) === 1 ? 'selected' : ''; ?>>Yes
						</option>
						<option value="0"
								<?php echo absint( get_option( 'bbea_option_auto_subscribe' ) ) === 0 ? 'selected' : ''; ?>>No
						</option>
					</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
					<label for="bbea_option_discussion_types">User within group will be only subcribe to selected discussion type/s: </label>
					</th>
					<td>
					<input
						type="text"
						id="bbea_option_discussion_types"
						name="bbea_option_discussion_types"
						value="<?php echo get_option( 'bbea_option_discussion_types' ); ?>"
						placeholder="Default: publish,private,inherit,closed"
					/>
					</td>
				</tr>
				</table>
				<hr>
				<table style="text-align: left;">
				<tr valign="top">
					<th scope="row">
					<label for="bbea_option_subscribe">Enable subscribe/unsubscribe button in forum cards? : </label>
					</th>
					<td>
					<select name="bbea_option_subscribe" id="bbea_option_subscribe">
						<option value="1"
								<?php echo absint( get_option( 'bbea_option_subscribe' ) ) === 1 ? 'selected' : ''; ?>>Yes
						</option>
						<option value="0"
								<?php echo absint( get_option( 'bbea_option_subscribe' ) ) === 0 ? 'selected' : ''; ?>>No
						</option>
					</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
					<label for="bbea_option_all_unsubscribe">Enable unsubcribe button in /forums/subscriptions page? : </label>
					</th>
					<td>
					<select name="bbea_option_all_unsubscribe" id="bbea_option_all_unsubscribe">
						<option value="1"
								<?php echo absint( get_option( 'bbea_option_all_unsubscribe' ) ) === 1 ? 'selected' : ''; ?>>Yes
						</option>
						<option value="0"
								<?php echo absint( get_option( 'bbea_option_all_unsubscribe' ) ) === 0 ? 'selected' : ''; ?>>No
						</option>
					</select>
					</td>
				</tr>
				</table>
				<hr>
				<h3>Change subscribe & unsubcribe icon</h3>
				<a target="_blank" href="https://www.buddyboss.com/resources/font-cheatsheet/">Check the font-cheatsheet? click me!</a>
				<table style="text-align: left;">
				<tr valign="top">
					<th scope="row">
					<label for="bbea_subscribe_icon">Subcribe Icon : </label>
					</th>
					<td>
					<input
						type="text"
						id="bbea_subscribe_icon"
						name="bbea_subscribe_icon"
						value="<?php echo get_option( 'bbea_subscribe_icon' ); ?>"
						placeholder="bb-icon-bell-plus"
						required
					/>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
					<label for="bbea_unsubscribe_icon">Unsubcribe Icon : </label>
					</th>
					<td>
					<input
						type="text"
						id="bbea_unsubscribe_icon"
						name="bbea_unsubscribe_icon"
						value="<?php echo get_option( 'bbea_unsubscribe_icon' ); ?>"
						placeholder="bb-icon-bell-off"
						required
					/>
					</td>
				</tr>
				</table>
						<?php submit_button(); ?>
			</form>
			</div>
			<?php
		}

		/**
		 * Singlestone instance
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

	}

	BBEA_Setting::get_instance();

endif;

?>

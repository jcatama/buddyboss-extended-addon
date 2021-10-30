<?php
/**
 * Admin hooks responsible for single unsubscribe
 *
 * @package    BuddyBossExtendedAddon
 * @subpackage includes/classes
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BBEA_Un_Subscribe' ) ) :

	/**
	 * BBEA_Un_Subscribe
	 */
	class BBEA_Un_Subscribe {

		/**
		 * Instance of BBEA_Un_Subscribe
		 *
		 * @var BBEA_Un_Subscribe
		 */
		protected static $instance;

		/**
		 * Initialize hooks.
		 */
		public function __construct() {
			if ( 1 === absint( get_option( 'bbea_option_subscribe' ) ) ) :
				add_action(
					'bbp_theme_after_forum_description',
					function() {
						require BBEA_PLUGIN_DIR . 'includes/templates/forum/template-unsubscribe-single.php';
					}
				);

				add_action( 'wp_ajax_bbea_subscribe', [ $this, 'un_subscribe' ] );
				add_action( 'wp_ajax_nopriv_bbea_subscribe', [ $this, 'un_subscribe_no_priv' ] );
				add_action( 'wp_ajax_bbea_unsubscribe', [ $this, 'un_subscribe' ] );
				add_action( 'wp_ajax_nopriv_bbea_unsubscribe', [ $this, 'un_subscribe_no_priv' ] );
			endif;
		}

		/**
		 * Admin AJAX for subscribing/unsubscribing to a forum.
		 *
		 * @return void
		 */
		public function un_subscribe() {
			if ( ! isset( $_REQUEST['forum'] ) ) {
				exit( 'Invalid forum request' );
				die();
			}

			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				exit( 'Invalid user' );
				die();
			}

			$forum_id = sanitize_text_field( $_REQUEST['forum'] );

			if ( wp_verify_nonce( $_REQUEST['nonce'], 'bbea_subscribe' ) ) {
				$this->subscribe( $user_id, $forum_id );
			} elseif ( wp_verify_nonce( $_REQUEST['nonce'], 'bbea_unsubscribe' ) ) {
				$this->unsubscribe( $user_id, $forum_id );
			} else {
				exit( 'Invalid request' );
				die();
			}
		}

		/**
		 * Subscribe users to forums and its discussions.
		 *
		 * @param int $user_id wp user id.
		 * @param int $forum_id bb forum id.
		 *
		 * @return void
		 */
		private function subscribe( $user_id, $forum_id ) {
			bbp_add_user_forum_subscription( $user_id, $forum_id );

			$topics = bbea_get_forum_topics( $forum_id );

			if ( ! empty( $topics ) ) :
				$topics_arr_ids = explode( ',', $topics->ids );
				foreach ( $topics_arr_ids as $topic_id ) :
					bbp_add_user_topic_subscription( $user_id, $topic_id );
				endforeach;
			endif;

			wp_redirect( $_SERVER['HTTP_REFERER'] );
			die();
		}

		/**
		 * Unsubscribe users to forums and its discussions.
		 *
		 * @param int $user_id wp user id.
		 * @param int $forum_id bb forum id.
		 *
		 * @return void.
		 */
		private function unsubscribe( $user_id, $forum_id ) {
			bbp_remove_user_forum_subscription( $user_id, $forum_id );

			$topics = bbea_get_forum_topics( $forum_id );

			if ( ! empty( $topics ) ) :
				$topics_arr_ids = explode( ',', $topics->ids );
				foreach ( $topics_arr_ids as $topic_id ) :
					bbp_remove_user_topic_subscription( $user_id, $topic_id );
				endforeach;
			endif;

			wp_redirect( $_SERVER['HTTP_REFERER'] );
			die();
		}

		/**
		 * No priv unsubscribe users to forums and its discussions.
		 *
		 * @return void
		 */
		public function un_subscribe_no_priv() {
			wp_die( 'Permissin denied.' );
			die();
		}

		/**
		 * Singlestone instance.
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

	}

	BBEA_Un_Subscribe::get_instance();

endif;

<?php
/**
 * Admin hooks responsible for user group subscription
 *
 * @package    BuddyBossExtendedAddon
 * @subpackage includes/classes
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BBEA_Subscription' ) ) :

	/**
	 * BBEA_Subscription
	 */
	class BBEA_Subscription {

		/**
		 * Instance of BBEA_Subscription.
		 *
		 * @var BBEA_Subscription
		 */
		protected static $instance;

		/**
		 * Initialize hooks.
		 */
		public function __construct() {
			// Auto Subscribe.
			if ( 1 === absint( get_option( 'bbea_option_auto_subscribe' ) ) ) :
				add_action( 'groups_join_group', [ $this, 'join_group' ], 10, 2 );
				add_action( 'groups_membership_accepted', [ $this, 'groups_membership_accepted' ], 10, 2 );
				add_action( 'groups_leave_group', [ $this, 'leave_group' ], 10, 2 );
				add_action( 'bbp_new_topic', [ $this, 'subscribe_all_users_to_new_topic' ], 20, 4 );
			endif;

			// Unsubscribe to all.
			if ( 1 === absint( get_option( 'bbea_option_all_unsubscribe' ) ) ) :
				add_action( 'wp_ajax_bbea_unsubscribe_to_all', [ $this, 'unsubscribe_to_all' ] );
				add_action( 'wp_ajax_nopriv_bbea_unsubscribe_to_all', [ $this, 'unsubscribe_to_all_no_priv' ] );
			endif;
		}

		/**
		 * Add member who joins a group to its forum.
		 *
		 * @param int $group_id bb group id.
		 * @param int $user_id wp user id.
		 *
		 * @return boolean
		 */
		public function join_group( $group_id, $user_id ) {

			if ( ! $user_id ) {
				return false;
			}

			$forum_ids = bbp_get_group_forum_ids( $group_id );

			if ( ! empty( $forum_ids ) ) {
				$forum_id = array_shift( $forum_ids );
			}

			$forum_response = $this->subscribe_user_to_forum( $user_id, $forum_id );
			if ( $forum_response ) :

				$topics_response = $this->subscribe_user_to_all_discussions( $user_id, $forum_id );
				if ( false === $topics_response ) :
					error_log( 'Unable to subcribe user ' . $user_id . ' to all topics under ' . $forum_id );
				endif;

			else :

				error_log( 'Unable to subcribe user ' . $user_id . ' to forum ' . $forum_id );

			endif;

		}

		/**
		 * Add member who accepted to its forum.
		 *
		 * @param int $user_id wp user id.
		 * @param int $group_id bb group id.
		 *
		 * @return void
		 */
		public function groups_membership_accepted( $user_id, $group_id ) {
			$this->join_group( $group_id, $user_id );
		}

		/**
		 * Remove all user subscription when leaving group.
		 *
		 * @param int $group_id bb group id.
		 * @param int $user_id wp user id.
		 *
		 * @return boolean
		 */
		function leave_group( $group_id, $user_id ) {
			if ( ! $user_id ) {
				return false;
			}

			$forum_ids = bbp_get_group_forum_ids( $group_id );

			if ( ! empty( $forum_ids ) ) {
				$forum_id = array_shift( $forum_ids );
			}

			$forum_response = $this->leave_user_to_forum( $user_id, $forum_id );
			if ( $forum_response ) {

				$defaul_post_status = '"publish","private","inherit","closed"';

				$bbea_option_discussion_types = trim( get_option( 'bbea_option_discussion_types' ) );

				if ( ! empty( $bbea_option_discussion_types ) ) {
					$defaul_post_status = $this->parse_bbea_option_discussion_types( $bbea_option_discussion_types );
				}

				$topics = bbea_get_bp_row(
					'posts',
					'GROUP_CONCAT(ID) as ids',
					'WHERE `post_status` in (' . $defaul_post_status . ') AND `post_parent` = ' . $forum_id . ' GROUP BY "all"'
				);

				if ( ! empty( $topics ) ) :

					$existing_topics = get_user_option( '_bbp_subscriptions', $user_id );

					if ( ! empty( $existing_topics ) ) :

						$topics          = explode( ',', $topics->ids );
						$existing_topics = explode( ',', $existing_topics );
						$topics          = array_diff( $existing_topics, $topics );
						$topics          = implode( ',', $topics );
						update_user_option( $user_id, '_bbp_subscriptions', $topics );

					else :

						delete_user_option( $user_id, '_bbp_subscriptions' );

					endif;

				endif;

			} else {
				error_log( 'Unable to leave user ' . $user_id . ' from forum ' . $forum_id );
			}
		}

		/**
		 * Subscribe all users to new topic if they are a part of group.
		 *
		 * @param int   $topic_id bb topic/discussion id.
		 * @param int   $forum_id bb forum id.
		 * @param array $anonymous_data form data.
		 * @param int   $topic_author wp/bb author id.
		 *
		 * @return boolean
		 */
		public function subscribe_all_users_to_new_topic( $topic_id, $forum_id, $anonymous_data, $topic_author ) {
			if ( $topic_author ) :
				if ( false === bbp_is_user_subscribed_to_forum( $topic_author, $forum_id ) ) :
					$this->subscribe_user_to_forum( $topic_author, $forum_id );
				endif;
				bbp_add_user_topic_subscription( $topic_author, $topic_id );
			endif;

			if ( bp_get_current_group_id() ) {
				$group_id = bp_get_current_group_id();
				$this->check_member_subscription( $group_id, $forum_id, $topic_id );
			}

			$post = get_post( $topic_id );
			if ( $post ) :
				$bbea_option_discussion_types = trim( get_option( 'bbea_option_discussion_types' ) );
				if ( ! empty( $bbea_option_discussion_types ) ) :
					if ( ! ( false !== strpos( $bbea_option_discussion_types, $post->post_status ) ) ) :
						error_log( 'Discussion type ' . $post->post_status . 'is excluded from subscription.' );
						return false;
					endif;
				endif;
			endif;

			$users = bbea_get_bp_row(
				'usermeta',
				'GROUP_CONCAT(user_id) as ids',
				'WHERE `meta_key` LIKE "%_bbp_forum_subscriptions" AND FIND_IN_SET(' . $forum_id . ', `meta_value`) GROUP BY "all"'
			);

			if ( ! empty( $users ) ) :
				$user_ids = explode( ',', $users->ids );
				foreach ( $user_ids as $user_id ) :
					if ( $topic_author === $user_id ) {
						continue;
					}
					bbp_add_user_topic_subscription( $user_id, $topic_id );
				endforeach;
			else :
				error_log( 'No users are subscribe to forum ' . $forum_id );
			endif;
		}

		/**
		 * Subscribe user to forum.
		 *
		 * @param integer $user_id wp user id.
		 * @param integer $forum_id bb forum id.
		 *
		 * @return boolean always true
		 */
		private function subscribe_user_to_forum( $user_id, $forum_id ) {
			return bbp_add_user_forum_subscription( $user_id, $forum_id );
		}

		/**
		 * Unsubscribe user to forum.
		 *
		 * @param integer $user_id wp user id.
		 * @param integer $forum_id bb forum id.
		 *
		 * @return boolean always true
		 */
		private function leave_user_to_forum( $user_id, $forum_id ) {
			return bbp_remove_user_forum_subscription( $user_id, $forum_id );
		}

		/**
		 * Subscribe user to all discussions in the Forum.
		 *
		 * @param integer $user_id wp user id.
		 * @param integer $forum_id bb forum id.
		 *
		 * @return boolean
		 */
		private function subscribe_user_to_all_discussions( $user_id, $forum_id ) {
			$defaul_post_status = '"publish","private","inherit","closed"';

			$bbea_option_discussion_types = trim( get_option( 'bbea_option_discussion_types' ) );

			if ( ! empty( $bbea_option_discussion_types ) ) {
				$defaul_post_status = $this->parse_bbea_option_discussion_types( $bbea_option_discussion_types );
			}

			$topics = bbea_get_bp_row(
				'posts',
				'GROUP_CONCAT(ID) as ids',
				'WHERE `post_status` in (' . $defaul_post_status . ') AND `post_parent` = ' . $forum_id . ' GROUP BY "all"'
			);

			if ( ! empty( $topics ) ) :

				$existing_topics     = '';
				$existing_topics_tmp = get_user_option( '_bbp_subscriptions', $user_id );
				if ( ! empty( $existing_topics_tmp ) && is_array( $existing_topics_tmp ) ) :

					$existing_topics = implode( ',', array_values( get_user_option( '_bbp_subscriptions', $user_id ) ) );

				else :

					$existing_topics_tmp = trim( $existing_topics_tmp );
					if ( ! empty( $existing_topics_tmp ) ) :
						$existing_topics = $existing_topics_tmp . ',';
					endif;

				endif;

				update_user_option( $user_id, '_bbp_subscriptions', $existing_topics . $topics->ids );

				$topics_arr_ids = explode( ',', $topics->ids );
				foreach ( $topics_arr_ids as $topic_id ) {
					wp_cache_delete( 'bbp_get_topic_subscribers_' . $topic_id, 'bbpress_users' );
				}
				return true;

			else :
				error_log( 'Forum ' . $forum_id . ' does not have any topics' );
				return false;
			endif;
		}

		/**
		 * Parse bbea_option_discussion_types to SQL friendly query.
		 *
		 * @param string $bbea_option_discussion_types wp post status types.
		 *
		 * @return string
		 */
		private function parse_bbea_option_discussion_types( $bbea_option_discussion_types ) {
			$discussions                  = explode( ',', $bbea_option_discussion_types );
			$bbea_option_discussion_types = '';

			foreach ( $discussions as $discussion ) {
				$bbea_option_discussion_types .= '"' . $discussion . '",';
			}

			return rtrim( $bbea_option_discussion_types, ',' );
		}

		/**
		 * Check if user subscribe to main discussion.
		 *
		 * @param integer $group_id bb group id.
		 * @param integer $forum_id bb forum id.
		 * @param integer $topic_id bb topic id.
		 *
		 * @return void
		 */
		private function check_member_subscription( $group_id, $forum_id, $topic_id ) {
			$member_args = [
				'group_id'            => $group_id,
				'per_page'            => -1,
				'exclude_admins_mods' => 0,
			];

			$members = groups_get_group_members( $member_args );

			if ( ! isset( $members['members'] ) ) {
				return;
			}

			if ( count( $members['members'] ) > 0 ) :
				foreach ( $members['members'] as $member ) :
					if ( false === bbp_is_user_subscribed_to_forum( $member->ID, $forum_id ) ) :
						$this->subscribe_user_to_forum( $member->ID, $forum_id );
					endif;
				endforeach;
			endif;
		}

		/**
		 * Admin AJAX for unsubscribing to subscriptions.
		 *
		 * @return void
		 */
		public function unsubscribe_to_all() {
			if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'bbea_unsubscribe_to_all' ) ) {
				exit( 'Invalid Nonce' );
				die();
			}

			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				exit( 'Invalid User' );
				die();
			}

			$forum_ids = bbp_get_user_subscribed_forum_ids( $user_id );
			if ( $forum_ids ) {
				foreach ( $forum_ids as $forum_id ) {
					bbp_remove_user_forum_subscription( $user_id, $forum_id );
				}
			}

			delete_user_option( $user_id, '_bbp_subscriptions' );

			wp_redirect( trailingslashit( bp_displayed_user_domain() . 'forums/subscriptions' ) );
			die();
		}

		/**
		 * Admin No priv AJAX for unsubscribing to subscriptions.
		 *
		 * @return void
		 */
		public function unsubscribe_to_all_no_priv() {
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

	BBEA_Subscription::get_instance();

endif;

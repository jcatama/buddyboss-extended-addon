<?php
/**
 * Callable functions.
 *
 * @package    BuddyBossExtendedAddon
 * @subpackage includes/classes
 */

/**
 * Generic row query for bbea.
 *
 * @param string $table sql table name.
 * @param string $selector sql selecter.
 * @param string $q sql query statement.
 *
 * @return object Database query result
 */
function bbea_get_bp_row( $table, $selector, $q ) {
	global $wpdb;
	$table  = $wpdb->prefix . $table;
	$bp_row = $wpdb->get_row( "SELECT $selector FROM $table $q" );
	return $bp_row;
}

/**
 * Get forum topics.
 *
 * @param int $forum_id buddyboss forum id.
 *
 * @return object Database query result
 */
function bbea_get_forum_topics( $forum_id ) {
	return bbea_get_bp_row(
		'posts',
		'GROUP_CONCAT(ID) as ids',
		'WHERE `post_parent` = ' . $forum_id . ' GROUP BY "all"'
	);
}

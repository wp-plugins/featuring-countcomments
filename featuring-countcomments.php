<?php

/*
Plugin Name: Featuring CountComments
Plugin URI: http://www.neotrinity.at/projects/
Description: Counts the number of comments by user IDs or author names (display name). - Attention! This means, that your commenters have to be registered and logged in to comment! Thus, it will not work in weblogs where anonymous comments are allowed! original code by Martijn van der Kwast
Author: Bernhard Riedl
Version: 0.21
Author URI: http://www.neotrinity.at
*/

/*  Copyright 2006-2009  Bernhard Riedl  (email : neo@neotrinity.at)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/*
called from init hook
*/

function fcc_init() {
	add_action('admin_head', 'fcc_wp_head');
	add_action('wp_head', 'fcc_wp_head');
}

function fcc_wp_head() {
	echo("<meta name=\"Featuring CountComments\" content=\"0.21\" />\n");
}

$_fcc_cache=array();

function _fcc_precache_posts_counts($post_ID)
{
	global $wpdb;
	global $_fcc_cache;

	$post_ID=intval($post_ID);
	if (!$post_ID) {
		return;
	}

	// get all commenters for current post
	$q = "SELECT DISTINCT $wpdb->comments.user_id FROM $wpdb->comments " .
		"WHERE $wpdb->comments.comment_post_ID = $post_ID AND $wpdb->comments.user_id <> '' ";

	$authors = $wpdb->get_col($q);
	if (!$authors) {
		return;
	}

	$aq='';
	foreach ($authors as $author) {
		$_fcc_cache[$author] = -1;
		$aq.="'".$wpdb->escape($author)."', ";
	}
	$aq=substr($aq, 0, -2);

	$q = "SELECT $wpdb->comments.user_id, COUNT(*) AS count FROM $wpdb->comments " .
		"WHERE $wpdb->comments.comment_approved = '1' and $wpdb->comments.user_id in ( $aq ) " .
		"GROUP BY $wpdb->comments.user_id";

	$rows = $wpdb->get_results($q);

	// save results
	foreach ($rows as $row) {
		$_fcc_cache[$row->user_id] = $row->count;
	}

}

function _fcc_cache_user_counts($user_id)
{
	global $wpdb;
	global $_fcc_cache;

	if (!isset($_fcc_cache[$user_id])) {

		// get global comment counts for all mentioned authors
		$q = "SELECT $wpdb->comments.user_id, COUNT(*) AS count FROM $wpdb->comments " .
			"WHERE $wpdb->comments.comment_approved = '1' and $wpdb->comments.user_id = ".$user_id." ".
			"GROUP BY $wpdb->comments.user_id";

		$rows = $wpdb->get_results($q);

		// save results
		if ($rows) {
			$_fcc_cache[$rows[0]->user_id] = $rows[0]->count;
		}

		// no results - maybe users deleted account
		else {
			$_fcc_cache[$rows[0]->user_id] = -1;
		}

	}
}

function _fcc_get_comment_authorID($author) {
	global $wpdb;

	$q = "SELECT $wpdb->users.ID FROM $wpdb->users " .
	     "WHERE $wpdb->users.display_name = '$author' ORDER BY $wpdb->users.ID DESC";

	$authors = $wpdb->get_col($q);
	if (!$authors) {
		return -1;
	}
	else {
		return $authors[0];
	}
}

/*
 * Returns the number of comments made by the author of this comment
 * parameters:
 * $comment: current comment structure.
 */

function fcc_get_comment_count($comment)
{
	global $_fcc_cache;

	if (empty($comment->user_id)) {
		return 0;
	}

	if (!isset($_fcc_cache[$comment->user_id])) {
		_fcc_precache_posts_counts($comment->comment_post_ID);
	}

	return $_fcc_cache[$comment->user_id];
}

/*
 * Displays the number of comments made by the author of the current comment in the comment loop
 * parameters:
 * $comment: current comment structure.
 * $zero, $one, $more: %c get replaced with the number of comments.
 */

function fcc_comment_count($zero='0 comments', $one='1 comment', $more='%c comments')
{
	global $comment;

	$num = fcc_get_comment_count($comment);

	if ($num == 0) {
		print str_replace('%c', $num, $zero);
	} else if ($num == 1) {
		print str_replace('%c', $num, $one);
	} else if( $num > 1) {
		print str_replace('%c', $num, $more);
	}
}

/*
 * Returns the number of comments made by someone with this display name
 * parameters:
 * $author: name of user to count comments for
 */

function fcc_get_count_comments_author($author)
{
	return fcc_get_count_comments_authorID( _fcc_get_comment_authorID($author) );
}

/*
 * Returns the number of comments made by someone with this id
 * parameters:
 * $authorID: id of user to count comments for
 */

function fcc_get_count_comments_authorID($authorID)
{
	global $_fcc_cache;

	if (!isset($_fcc_cache[$authorID])) {
		_fcc_cache_user_counts($authorID);
	}

	return $_fcc_cache[$authorID];
}

/*
 * Displays the number of comments made by the author who is currently logged in
 * parameters:
 * $zero, $one, $more: %c get replaced with the number of comments.
 */

function fcc_count_comments_author($zero='0 comments', $one='1 comment', $more='%c comments')
{
	global $user_ID;

	$num = fcc_get_count_comments_authorID($user_ID);

	if ($num == 0) {
		print str_replace('%c', $num, $zero);
	} else if ($num == 1) {
		print str_replace('%c', $num, $one);
	} else if( $num > 1) {
		print str_replace('%c', $num, $more);
	}
}

/*
 * Displays the number of comments made by a specified author with this display name
 * parameters:
 * $zero, $one, $more: %c get replaced with the number of comments
 * $author: the authors nickname
 */

function fcc_count_comments_by_author($zero='0 comments', $one='1 comment', $more='%c comments', $author)
{
	fcc_count_comments_by_authorID($zero, $one, $more, _fcc_get_comment_authorID($author) );
}

/*
 * Displays the number of comments made by a specified author with this ID
 * parameters:
 * $comment: current comment structure.
 * $zero, $one, $more: %c get replaced with the number of comments
 * $authorID: the authors id
 */

function fcc_count_comments_by_authorID($zero='0 comments', $one='1 comment', $more='%c comments', $authorID)
{
	$num = fcc_get_count_comments_authorID($authorID);

	if ($num == 0) {
		print str_replace('%c', $num, $zero);
	} else if ($num == 1) {
		print str_replace('%c', $num, $one);
	} else if( $num > 1) {
		print str_replace('%c', $num, $more);
	}
}

add_action('init', 'fcc_init');

?>
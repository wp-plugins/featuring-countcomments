<?php

/*
Plugin Name: Featuring CountComments
Plugin URI: http://www.neotrinity.at/projects/
Description: Count the number of comments by authornames. - Attention! This means, that your commenters have to be registered and logged in to comment! This will not work in weblogs where everyone can comment! original code by Martijn van der Kwast (http://www.stilglog.com/wordpress-plugins/count-comments/)
Author: Bernhard Riedl
Version: 0.11 (beta)
Author URI: http://www.neotrinity.at
*/

/*  Copyright 2006  Bernhard Riedl  (email : neo@neotrinity.at)

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

add_action('wp_head', 'fcc_wp_head');

function fcc_wp_head() {
  echo("<meta name=\"Featuring CountComments\" content=\"0.11\" />\n");
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
	$q = "SELECT DISTINCT comment_author FROM $wpdb->comments " .
	     "WHERE comment_post_ID = $post_ID AND comment_author <> '' ";

	$authors = $wpdb->get_col($q);
	if (!$authors) {
		return;
	}

	$aq='';
	foreach ($authors as $author) {
		$aq.="'".$wpdb->escape($author)."', ";
	}
	$aq=substr($aq, 0, -2);

	$q = "SELECT comment_author, COUNT(*) AS count FROM $wpdb->comments " .
	     "WHERE comment_approved = '1' and comment_author in ( $aq ) " .
		 "GROUP BY comment_author";

	$rows = $wpdb->get_results($q);

	// save results
	foreach ($rows as $row) {
		$_fcc_cache[$row->comment_author] = $row->count;
	}
}

function _fcc_precache_all_counts()
{
	global $wpdb;
	global $_fcc_cache;

	// get global comment counts for all mentionned authors
	$q = "SELECT comment_author, COUNT(*) AS count FROM $wpdb->comments " .
		 "WHERE comment_author <> '' ".
		 "GROUP BY comment_author";

	$rows = $wpdb->get_results($q);

	// save results
	foreach ($rows as $row) {
		$_fcc_cache[$row->comment_author] = $row->count;
	}
}

/*
 * Return number of comments made by the author of this comment.
 * parameters:
 * $comment: current comment structure.
 */

function fcc_get_comment_count($comment)
{
	global $_fcc_cache;

	if (empty($comment->comment_author)) {
		return 0;
	}

	if (!isset($_fcc_cache[$comment->comment_author])) {
		_fcc_precache_posts_counts($comment->comment_post_ID);
	}

	return $_fcc_cache[$comment->comment_author];
}

/*
 * Display the number of comments made by the author of the current comment in the comment loop.
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
 * Get the number of comments made by someone with this name, outside the comment context.
 * parameters:
 * $author: name of user to count comments for
 */

function fcc_get_count_comments_author($author)
{
	global $_fcc_cache;

	if (!isset($_fcc_cache[$author])) {
		_fcc_precache_all_counts();
	}

	return $_fcc_cache[$author];
}

/*
 * Display the number of comments made by the author of the current comment in the comment loop.
 * parameters:
 * $comment: current comment structure.
 * $zero, $one, $more: %c get replaced with the number of comments.
 */

function fcc_count_comments_author($zero='0 comments', $one='1 comment', $more='%c comments')
{
	global $user_identity;

	$num = fcc_get_count_comments_author($user_identity);

	if ($num == 0) {
		print str_replace('%c', $num, $zero);
	} else if ($num == 1) {
		print str_replace('%c', $num, $one);
	} else if( $num > 1) {
		print str_replace('%c', $num, $more);
	}
}

/*
 * Display the number of comments made by an specified author
 * parameters:
 * $comment: current comment structure.
 * $zero, $one, $more: %c get replaced with the number of comments, $author: the authors nickname
 */

function fcc_count_comments_by_author($zero='0 comments', $one='1 comment', $more='%c comments', $author)
{
	$num = fcc_get_count_comments_author($author);

	if ($num == 0) {
		print str_replace('%c', $num, $zero);
	} else if ($num == 1) {
		print str_replace('%c', $num, $one);
	} else if( $num > 1) {
		print str_replace('%c', $num, $more);
	}
}

?>
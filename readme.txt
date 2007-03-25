=== Featuring CountComments ===
Contributors: neoxx
Donate link: http://www.neotrinity.at/projects/
Tags: countComments, comments, counting, count
Requires at least: 1.5
Tested up to: 2.1
Stable tag: trunk

Count the number of comments by authornames

== Description ==

Count the number of comments by authornames. - Attention! This means, that your commenters have to be registered and logged in to comment! This will not work in weblogs where anonymous comments are allowed! original code by Martijn van der Kwast [stilglog.com](http://www.stilglog.com/wordpress-plugins/count-comments/)

== Installation ==

1. Put both the featuring-count-comments.php file in your WordPress plugins directory (usually wp-content/plugins).

2. In the WordPress admin console, go to the Plugins tab, and activate the Featuring CountComments plugin.

3. To display the count to a comment display add this in the comment loop

	(`$comment` must be globally defined).
	`<?php fcc_comment_count(); ?>`

	Example in comments.php in the Kubrick theme:
	Find

	`<cite><?php comment_author_link</cite>` Says:

	and add insert this in that line:

	`<?php fcc_comment_count('','(1)','(%c)'); ?>`

4. Add additional function calls anywhere you like.

5. Drink a beer, smoke a cigarette or celebrate in a way you like!

(Additional) You can use the other functions as well. - please read the documentation in the plugin class (php file) itself.


= Functions =

`function fcc_get_comment_count($comment)`
 * Return number of comments made by the author of this comment.
 * parameters:
     - $comment: current comment structure.

`function fcc_comment_count($zero='0 comments', $one='1 comment', $more='%c comments')`
 * Display the number of comments made by the author of the current comment in the comment loop.
 * parameters:
     - $comment: current comment structure.
     - $zero, $one, $more: %c get replaced with the number of comments.

`function fcc_get_count_comments_author($author)`
 * Get the number of comments made by someone with this name, outside the comment context.
 * parameters:
     - $author: name of user to count comments for

`function fcc_count_comments_author($zero='0 comments', $one='1 comment', $more='%c comments')`
 * Display the number of comments made by the author of the current comment in the comment loop.
 * parameters:
     - $comment: current comment structure.
     - $zero, $one, $more: %c get replaced with the number of comments.

`function fcc_count_comments_by_author($zero='0 comments', $one='1 comment', $more='%c comments', $author)`
 * Display the number of comments made by an specified author
 * parameters:
     - $comment: current comment structure.
     - $zero, $one, $more: %c get replaced with the number of comments, $author: the authors nickname


= Remarks =

Author nicknames are used to recognize commenters.

The results are cached to avoid using too many queries to increase performance.

If the number of comments gets really big, it would be better to cache the counts in the database instead of calculating them.
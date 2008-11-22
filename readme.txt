=== Featuring CountComments ===
Contributors: neoxx
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=neo%40neotrinity%2eat&item_name=neotrinity%2eat&no_shipping=1&no_note=1&tax=0&currency_code=USD&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: countComments, comments, comment, counting, count
Requires at least: 1.5
Tested up to: 2.7
Stable tag: trunk

Counts the number of comments by authornames.

== Description ==

Counts the number of comments by user IDs or author names (display name).

**Attention! This means, that your commenters have to be registered and logged in to comment!** Thus, it will not work in weblogs where anonymous comments are allowed!

Original code by Martijn van der Kwast.

== Installation ==

1. Copy the `featuring-countcomments` directory into your WordPress plugins directory (usually wp-content/plugins). Hint: With WordPress 2.7 and higher you can conduct this step within your Admin Menu.

2. In the WordPress Admin Menu, go to the Plugins tab, and activate the Featuring CountComments plugin.

3. To display the comment count of a certain author, add this in the comment loop

	(`$comment` must be globally defined).
	`<?php fcc_comment_count(); ?>`

	Example for comments.php in the Kubrick theme:
	Find

	`<cite><?php comment_author_link</cite>`

	and insert this in that line:

	`<?php fcc_comment_count('','(1)','(%c)'); ?>`

4. Add additional [function calls](http://wordpress.org/extend/plugins/featuring-countcomments/other_notes/) according to your desires.

5. Be happy and celebrate! (and maybe you want to add a link to [http://www.neotrinity.at/projects/](http://www.neotrinity.at/projects/))

== Functions ==

= Functions for Post or Comment Templates =

* `function fcc_get_comment_count($comment)`
 - Returns the number of comments made by the author of this comment
 - parameters: $comment: current comment structure

* `function fcc_comment_count($zero='0 comments', $one='1 comment', $more='%c comments')`
 - Displays the number of comments made by the author of the current comment in the comment loop
 - parameters: $zero, $one, $more: %c get replaced with the number of comments.

= Functions for Author Pages =

* `function fcc_get_count_comments_author($author)`
 - Returns the number of comments made by someone with this display name
 - parameters: $author: name of user to count comments for

* `function fcc_get_count_comments_authorID($authorID)`
 - same as above, but based on ID instead of the author's name
 - parameters: $authorID: name of user to count comments for

* `function fcc_count_comments_author($zero='0 comments', $one='1 comment', $more='%c comments')`
 - Displays the number of comments made by the author who is currently logged in
 - parameters: $zero, $one, $more: %c get replaced with the number of comments.

* `function fcc_count_comments_by_author($zero='0 comments', $one='1 comment', $more='%c comments', $author)`
 - Displays the number of comments made by a specified author with this display name
 - parameters: $zero, $one, $more: %c get replaced with the number of comments, $author: the authors display name

* `function fcc_count_comments_by_authorID($zero='0 comments', $one='1 comment', $more='%c comments', $authorID)`
 - same as above, but based on ID instead of the author's name
 - parameters: $zero, $one, $more: %c get replaced with the number of comments, $authorID: the authors id

== Remarks ==

* Authors' nicknames (display names) and ids can be used to query for commenters. Though, the internal structure is based on the authors' id and will therefore only produce results of comments, which have been written by authors who have been logged in at the time of writing the comment.
* Already queried results are re-used within a single page-call to avoid performing too many queries. This results in increased performance.
* Nevertheless, if the number of comments gets really big, it would be better to cache the counts in the database instead of calculating them on every page-call.
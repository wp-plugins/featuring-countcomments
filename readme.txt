=== Featuring CountComments ===
Contributors: neoxx
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=bernhard%40riedl%2ename&item_name=Donation%20for%20Featuring%20CountComments&no_shipping=1&no_note=1&tax=0&currency_code=EUR&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: count, comment, comments, author, authors, user, users, widget, dashboard, sidebar, shortcode, multisite, multi-site
Requires at least: 3.2
Tested up to: 3.4
Stable tag: trunk
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Counts the number of comments for each user, who has been logged in at the time of commenting.

== Description ==

Counts the number of comments for each user who has been logged in at the time of commenting.

**starting from version 1.00 with a new API and widget functionality**

* extends information on Users page in Admin Menu with comment counts
* optionpage-configurable for standard functions
* easy to integrate (ships with multi/sidebar- and dashboard-widget functionality)
* possible to integrate in "Right Now" box or to display as widget on the dashboard and on the user's profile page
* high performance because users' comment counts are re-used within a page-call
* fully compatible with [https/SSL/TLS-sites](http://codex.wordpress.org/Administration_Over_SSL)
* fully WP 3.0 multi-site network compatible
* clean uninstall

Requirements for current version:

* Your users have to be registered and logged in to comment - **Thus, Featuring CountComments will not work properly in weblogs where anonymous comments are allowed!**
* PHP 5 or higher (find the version for PHP 4 [here](http://downloads.wordpress.org/plugin/featuring-countcomments.wordpressminor2.8.zip))
* You can check your PHP version with the [Health Check](http://wordpress.org/extend/plugins/health-check/) plugin.

Please find the version for WordPress

* 3.2 and higher [here](http://downloads.wordpress.org/plugin/featuring-countcomments.zip)
* 2.8 to 3.1 [here](http://downloads.wordpress.org/plugin/featuring-countcomments.wordpress2.8-3.1.zip)
* minor 2.8 [here](http://downloads.wordpress.org/plugin/featuring-countcomments.wordpressminor2.8.zip)

**Plugin's website:** [http://www.neotrinity.at/projects/](http://www.neotrinity.at/projects/)

**Author's website:** [http://www.bernhard.riedl.name/](http://www.bernhard.riedl.name/)

== Installation ==

1. Copy the `featuring-countcomments` directory into your WordPress plugins directory (usually wp-content/plugins). Hint: You can also conduct this step within your Admin Menu.

2. In the WordPress Admin Menu go to the Plugins tab and activate the Featuring CountComments plugin.

3. Navigate to the Settings/Featuring Countcomments tab and optionally customize the defaults according to your desires.

4. If you have widget functionality just drag and drop Featuring CountComments on your widget area in the Appearance Menu. Add additional [function and shortcode calls](http://wordpress.org/extend/plugins/featuring-countcomments/other_notes/) according to your desires.

5. Be happy and celebrate! (and maybe you want to add a link to [http://www.neotrinity.at/projects/](http://www.neotrinity.at/projects/))

== Frequently Asked Questions ==

= After updating to 1.00 I get warnings in my debug-log? - What's wrong? =

As I've completly reworked Featuring CountComments, I deprecated the old functions. You should still be able to use them, but I'd rather recommend you to have a look at the new [function calls](http://wordpress.org/extend/plugins/featuring-countcomments/other_notes/).

= Why do my users have to be registered to comment? =

Various user attributes can be used in queries. Though, the internal structure is based on the authors' id to avoid confusion in case of changed user-names, e-mail addresses, etc. Featuring CountComments will therefore only count comments, which have been written by authors who have been logged in at the time of writing a comment.

= How about the efficiency in Featuring CountComments? =

Already queried results are cached within a single page-call to avoid executing too many queries. This results in increased performance.

Moreover, in case of querying the comment count of a certain post's comment, only two SQL statements will be used to retrieve the comment count of all users who contributed to this post.

= If I click on the comment count in the Users page of the Admin Menu I do not receive all comments of the associated user and sometimes comments from other users. - What's wrong? =

Actually, there is nothing wrong. - In previous versions, Featuring CountComments used the built-in full-text comment search of WordPress.

With WordPress >= 3.1 and Featuring CountComments >= 1.20 [the results are queried by user-id instead of display-name](https://core.trac.wordpress.org/ticket/14163).

== Other Notes ==

**Attention! - Geeks' stuff ahead! ;)**

= API =

Parameters can either be passed [as an array or a URL query type string (e.g. "display=0&format=0")](http://codex.wordpress.org/Function_Reference/wp_parse_args). Please note that WordPress parses all arguments as strings, thus booleans have to be 0 or 1 if used in query type strings whereas for arrays [real booleans](http://php.net/manual/en/language.types.boolean.php) should be used.

**`$featuring_countcomments->count_by_user($params=array())`**

Counts the number of comments made by a user who is currently logged in or has a particular attribute.

$params:

- `user_attribute`: one of the user's attributes (matching `query_type`), for example, the user_id or a WP_User object; if no user_attribute is given, will fallback to currently logged in user

- `query_type`: corresponding sql-field of user's attribute or WP_User object; default is `user_id`

 - user_id
 - display_name
 - user_nicename
 - user_email
 - user_login
 - WP_User object

- `format`: if set to true (default), the output will be formatted using the attributes `zero`, `one`, `more` and `thousands_separator`; false = process plain count value

- `zero`, `one`, `more`: for formatted output - %c gets replaced 	with the number of comments

 - 'zero' => '0 comments'
 - 'one' => '1 comment'
 - 'more' => '%c comments'

- `thousands_separator`: divides counts by thousand delimiters; default `,` => e.g. 1,386

- `display`: if you want to return the count (e.g. for storing it in a variable) instead of echoing it with this function-call, set `display` to `false`; default setting is `true`

The following example outputs the comment count of the user with the registered e-mail address 'j.doe@mydomain.com'.

`<?php

global $featuring_countcomments;

$params=array(
	'query_type' => 'user_email',
	'user_attribute' => 'j.doe@mydomain.com'
);

$featuring_countcomments->count_by_user($params);

?>`

**`$featuring_countcomments->count_by_comment($params=array())`**

Counts the number of comments made by a user who wrote a certain comment or the current comment in the comment-loop.

$params:

- `comment`: a comment object or comment id; if empty 	retrieves current comment

- `format`: if set to true (default), the output will be formatted using the attributes `zero`, `one`, `more` and `thousands_separator`; false = process plain count value

- `zero`, `one`, `more`: for formatted output - %c gets replaced 	with the number of comments

 - 'zero' => '0 comments'
 - 'one' => '1 comment'
 - 'more' => '%c comments'

- `thousands_separator`: divides counts by thousand delimiters default `,` => e.g. 1,386

- `display`: if you want to return the count (e.g. for storing it in a variable) instead of echoing it with this function-call, set `display` to `false`; default setting is `true`

- `in_loop`: if set to true (default), the query count for all user who wrote a comment which belongs to the post of the handed over `comment` will be cached; otherwise the comment count will be retrieved only for the user who posted the `comment`

The following example outputs the number of comments of the author with the current comment in the comment loop:

`<?php

global $featuring_countcomments;

$featuring_countcomments->count_by_comment();

?>`

= Shortcodes =

[How-to for shortcodes](http://codex.wordpress.org/Shortcode_API)

**General Example:**

Enter the following text anywhere in a post or page to output the comment count of user `xyz`:

`[featuring_countcomments_count_by_user query_type="user_nicename" user_attribute="xyz"] by xyz so far...`

**Available Shortcode:**

`featuring_countcomments_count_by_user`

Invokes `$featuring_countcomments->count_by_user($params)`.

`featuring_countcomments_count_by_comment`

Invokes `$featuring_countcomments->count_by_comment($params)`.

= Filters =

[How-To for filters](http://codex.wordpress.org/Function_Reference/add_filter)

**General Example:**

`function my_featuring_countcomments_defaults($params=array()) {
	$params['query_type'] = 'user_nicename';
	return $params;
}

add_filter('featuring_countcomments_defaults', 'my_featuring_countcomments_defaults');`

**Available Filters:**

`featuring_countcomments_defaults`

In case you want to set the default parameters globally rather than handing them over on every function call, you can add the [filter](http://codex.wordpress.org/Function_Reference/add_filter) `featuring_countcomments_defaults` in for example featuring-countcomments.php or your [own customization plugin](http://codex.wordpress.org/Writing_a_Plugin) (recommended).

Please note that parameters which you hand over to a function call (`$featuring_countcomments->count_by_user` or `$featuring_countcomments->count_by_comment`) will always override the defaults parameters, even if they have been set by a filter or in the admin menu.

`featuring_countcomments_dashboard_widget`

Receives an array which is used for the dashboard-widget-function call to `$featuring_countcomments->count_by_user`. `display` and `format` will automatically be set to true and `user_parameter` to null to receive the current user's count.

`featuring_countcomments_dashboard_widget_text`

Receives a string which is used in the dashboard-widget. `%c` will be replaced by the comment count of the user who is currently logged in.

`featuring_countcomments_dashboard_right_now`

Receives an array which is used for the dashboard-right-now-box-function call to `$featuring_countcomments->count_by_user`. `display` and `format` will automatically be set to true and `user_parameter` to null to retrieve the comment count of currently logged in user.

`featuring_countcomments_dashboard_right_now_text`

Receives a string which is used in the right-now box on the dashboard. `%c` will be replaced by the comment count of the user who is currently logged in.

`featuring_countcomments_user_profile`

Receives an array which is used for the user-profile-function call to `$featuring_countcomments->count_by_user`. `display` and `format` will automatically be set to true and `user_parameter` to null to retrieve the comment count of currently logged in user.

`featuring_countcomments_user_profile_text`

Receives a string which is used in the user's profile page. `%c` will be replaced by the comment count of the user who is currently logged in.

`featuring_countcomments_users_custom_column`

Receives an array which is used for the users-page-function call to `$featuring_countcomments->count_by_user`. `display` and `format` will automatically be set to true and `user_parameter` to the user-id of each row to retrieve the user's comment count.

== Screenshots ==

1. This screenshot shows the extended users table in the Admin Menu.

2. This picture shows an example widget output in the sidebar.

3. This screenshot shows the Settings/Featuring CountComments Tab in the Admin Menu.

== Upgrade Notice ==

= 1.30 =

The minimum requirement is now WordPress 3.2

= 1.00 =

All old functions have been deprecated in favor of `$featuring_countcomments->count_by_user()` and `featuring_countcomments->count_by_comment()`.

== Changelog ==

= 1.31 =

* changed handling of contextual help for WordPress 3.3
* adopted handling of default settings
* external files are now registered in init-hook

= 1.30 =

* adoption of JavaScript code for jQuery 1.6.1 (ships with WordPress 3.2 => increased minimum requirement to WordPress 3.2 for this and all upcoming releases)
* small enhancements

= 1.20 =

* use [new WordPress 3.1 query parameter](https://core.trac.wordpress.org/ticket/14163) to retrieve results for comments in Admin Menu by user-id instead of display-name
* Changed settings-page JS library to jQuery
* added CSS for comments column in Users Page of WordPress 3.1 Admin Menu

= 1.11 =

* use WordPress style for comment-counts in users table

= 1.10 =

* admins are able to view the users comment-counts in the Admin Menu
* the access to the user's comment-counts can be restricted
* corrected a few typos and fixed potential bugs

= 1.00 =

* start Changelog
* completely reworked API methods and internal structure
* Security improvements
* added Admin Menu
* adapted to WordPress `wp_parse_args` function
* included Admin Menu and filter to set default values
* added sidebar widget
* added dashboard widget
* possible to add in "Right Now" box on dashboard
* added profile page add-on
* added log functionality
* added test-suite
* deprecated old functions
* added contextual help to settings menu
* updated license to GPLv3